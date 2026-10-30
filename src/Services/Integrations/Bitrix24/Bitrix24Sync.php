<?php

namespace HolartWeb\AxoraCMS\Services\Integrations\Bitrix24;

use HolartWeb\AxoraCMS\Jobs\SyncBitrix24Job;
use HolartWeb\AxoraCMS\Models\Commerce\TOrderItems;
use HolartWeb\AxoraCMS\Models\Commerce\TOrders;
use HolartWeb\AxoraCMS\Models\Integrations\TBitrix24Log;
use HolartWeb\AxoraCMS\Models\Integrations\TBitrix24Rule;
use HolartWeb\AxoraCMS\Models\TModule;
use HolartWeb\AxoraCMS\Services\MessageManager\MessageEvents;
use Illuminate\Support\Facades\Log;

/**
 * Heart of the "Битрикс24" module: finds the rules of an event and creates the
 * CRM records. Nothing here throws into the caller — a CRM failure must not break
 * an order or a request; it lands in the module log with a "Повторить" button.
 */
class Bitrix24Sync
{
    public const MODULE_NAME = 'bitrix24';

    public const EVENTS = ['order.created', 'user_request.created', 'form_submission.created'];

    /** Values of these fields are lists of {VALUE, VALUE_TYPE} in the REST API. */
    private const MULTI_FIELDS = ['PHONE', 'EMAIL', 'WEB', 'IM'];

    public function __construct(private Bitrix24Client $client) {}

    public static function isActive(): bool
    {
        return TModule::isInstalled(self::MODULE_NAME);
    }

    /**
     * Trigger an event. Rules run after the response, so the visitor never waits for the CRM.
     *
     * @param  array<string, string>  $values  variable code (without #) => value
     * @param  array<int, array{name: string, price: float, quantity: float}>  $products
     */
    public function dispatch(string $event, array $values, array $products = []): void
    {
        try {
            if (! self::isActive()) {
                return;
            }

            $values += MessageEvents::commonValues();
            $rules = TBitrix24Rule::query()
                ->where('event', $event)
                ->where('is_active', true)
                ->orderBy('sort')
                ->orderBy('id')
                ->get();

            foreach ($rules as $rule) {
                if ($rule->form_code && $rule->form_code !== ($values['FORM_CODE'] ?? null)) {
                    continue;
                }

                SyncBitrix24Job::dispatchAfterResponse($rule->id, $event, $values, $products);
            }
        } catch (\Throwable $e) {
            Log::warning("Bitrix24: event {$event} failed: ".$e->getMessage());
        }
    }

    /**
     * Trigger "order created" for a saved order (its items must already exist).
     */
    public function dispatchOrder(TOrders $order): void
    {
        try {
            if (! self::isActive()) {
                return;
            }

            $items = $order->items()->get();
            $values = MessageEvents::orderValues($order, $items)['values'];

            $this->dispatch('order.created', $values, $this->productRows($order, $items));
        } catch (\Throwable $e) {
            Log::warning('Bitrix24: event order.created failed: '.$e->getMessage());
        }
    }

    /**
     * Execute one rule and write the log row.
     *
     * @param  array<string, string>  $values
     * @param  array<int, array{name: string, price: float, quantity: float}>  $products
     * @param  array{contact?: ?int, company?: ?int}  $known  records already created by a failed attempt
     */
    public function run(TBitrix24Rule $rule, string $event, array $values, array $products = [], array $known = []): TBitrix24Log
    {
        $log = new TBitrix24Log([
            'rule_id' => $rule->id,
            'rule_name' => $rule->name,
            'event' => $event,
            'status' => TBitrix24Log::STATUS_SUCCESS,
            'entity' => $rule->entity,
            'company_id' => $known['company'] ?? null,
            'contact_id' => $known['contact'] ?? null,
            'payload' => ['values' => $values, 'products' => $products],
        ]);

        try {
            $log->company_id ??= $this->syncCompany($rule, $values);
            $log->contact_id ??= $this->syncContact($rule, $values, $log->company_id);

            if ($rule->entity === TBitrix24Rule::ENTITY_NONE) {
                $log->entity = null;
            } else {
                $log->entity_id = $this->createMain($rule, $values, $log->contact_id, $log->company_id);
                $this->sendProducts($rule, $log, $products);
            }
        } catch (\Throwable $e) {
            $log->status = TBitrix24Log::STATUS_FAILED;
            $log->message = $e instanceof Bitrix24Exception ? $e->getMessage() : 'Внутренняя ошибка при отправке в Битрикс24';
            Log::warning('Bitrix24: rule '.$rule->id.' failed: '.$e->getMessage());
        }

        $log->save();

        return $log;
    }

    /**
     * Replace #CODE# tokens. Unlike a message, a CRM field must not receive leftovers,
     * so a token without a value simply disappears.
     *
     * @param  array<string, string>  $values
     */
    public function render(string $template, array $values): string
    {
        return trim(preg_replace_callback(
            '/#([A-Z0-9_]+)#/',
            fn (array $match) => (string) ($values[$match[1]] ?? ''),
            $template
        ) ?? '');
    }

    /**
     * Turn mapping rows into REST "fields". Rows that came out empty are dropped,
     * so an empty optional value does not erase a default set on the portal.
     *
     * @param  array<int, array{field?: string, value?: string}>  $rows
     * @param  array<string, string>  $values
     * @return array<string, mixed>
     */
    public function buildFields(array $rows, array $values): array
    {
        $fields = [];

        foreach ($rows as $row) {
            $code = (string) ($row['field'] ?? '');
            $value = $this->render((string) ($row['value'] ?? ''), $values);

            if ($code === '' || $value === '') {
                continue;
            }

            if (in_array($code, self::MULTI_FIELDS, true)) {
                $fields[$code][] = ['VALUE' => $value, 'VALUE_TYPE' => 'WORK'];
            } else {
                $fields[$code] = $value;
            }
        }

        return $fields;
    }

    /**
     * @param  array<string, string>  $values
     */
    private function syncCompany(TBitrix24Rule $rule, array $values): ?int
    {
        if ($rule->company_mode === TBitrix24Rule::MODE_NONE) {
            return null;
        }

        $fields = $this->buildFields($rule->mapping['company'] ?? [], $values);

        if ($fields === []) {
            return null;
        }

        if ($rule->company_mode === TBitrix24Rule::MODE_FIND_OR_CREATE) {
            $found = $this->findByCommunications('COMPANY', $fields) ?? $this->findCompanyByTitle($fields['TITLE'] ?? null);

            if ($found) {
                return $found;
            }
        }

        return (int) $this->client->call('crm.company.add', ['fields' => $fields]);
    }

    /**
     * @param  array<string, string>  $values
     */
    private function syncContact(TBitrix24Rule $rule, array $values, ?int $companyId): ?int
    {
        if ($rule->contact_mode === TBitrix24Rule::MODE_NONE) {
            return null;
        }

        $fields = $this->buildFields($rule->mapping['contact'] ?? [], $values);

        if ($fields === []) {
            return null;
        }

        if ($rule->contact_mode === TBitrix24Rule::MODE_FIND_OR_CREATE) {
            $found = $this->findByCommunications('CONTACT', $fields);

            if ($found) {
                return $found;
            }
        }

        if ($companyId) {
            $fields['COMPANY_ID'] = $companyId;
        }

        return (int) $this->client->call('crm.contact.add', ['fields' => $fields]);
    }

    /**
     * @param  array<string, string>  $values
     */
    private function createMain(TBitrix24Rule $rule, array $values, ?int $contactId, ?int $companyId): int
    {
        $fields = $this->buildFields($rule->mapping['main'] ?? [], $values);

        if ($contactId) {
            $fields['CONTACT_ID'] = $contactId;
        }

        if ($companyId) {
            $fields['COMPANY_ID'] = $companyId;
        }

        return (int) $this->client->call("crm.{$rule->entity}.add", [
            'fields' => $fields,
            'params' => ['REGISTER_SONET_EVENT' => 'Y'],
        ]);
    }

    /**
     * The record already exists at this point, so a failure here must not offer a
     * retry that would create it twice.
     *
     * @param  array<int, array{name: string, price: float, quantity: float}>  $products
     */
    private function sendProducts(TBitrix24Rule $rule, TBitrix24Log $log, array $products): void
    {
        if (! $rule->send_products || $products === []) {
            return;
        }

        $rows = array_map(fn (array $product) => [
            'PRODUCT_NAME' => $product['name'],
            'PRICE' => $product['price'],
            'QUANTITY' => $product['quantity'],
        ], $products);

        try {
            $this->client->call("crm.{$rule->entity}.productrows.set", ['id' => $log->entity_id, 'rows' => $rows]);
        } catch (Bitrix24Exception $e) {
            $log->status = TBitrix24Log::STATUS_PARTIAL;
            $log->message = 'Запись создана, но товары не добавлены: '.$e->getMessage();
        }
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function findByCommunications(string $entityType, array $fields): ?int
    {
        foreach (['PHONE', 'EMAIL'] as $type) {
            $values = array_column($fields[$type] ?? [], 'VALUE');

            if ($values === []) {
                continue;
            }

            $result = $this->client->call('crm.duplicate.findbycomm', [
                'type' => $type,
                'values' => $values,
                'entity_type' => $entityType,
            ]);

            // An empty result comes as [], a match as {"CONTACT": [ids]}.
            $ids = is_array($result) ? ($result[$entityType] ?? []) : [];

            if ($ids !== []) {
                return (int) min($ids);
            }
        }

        return null;
    }

    private function findCompanyByTitle(?string $title): ?int
    {
        if (! $title) {
            return null;
        }

        $found = $this->client->call('crm.company.list', [
            'filter' => ['=TITLE' => $title],
            'select' => ['ID'],
            'order' => ['ID' => 'ASC'],
        ]);

        return isset($found[0]['ID']) ? (int) $found[0]['ID'] : null;
    }

    /**
     * Bitrix24 recalculates the record amount from its rows, so the promo code
     * discount is spread over the goods and the delivery goes as a row of its own:
     * the rows must add up to the order total.
     *
     * @param  iterable<int, TOrderItems>  $items
     * @return array<int, array{name: string, price: float, quantity: float}>
     */
    private function productRows(TOrders $order, iterable $items): array
    {
        $rows = [];
        $goods = (float) $order->goods_price;
        $discountFactor = $goods > 0 ? max(0, $goods - (float) $order->promocode_discount) / $goods : 1;

        foreach ($items as $item) {
            $quantity = max(1, (float) $item->amount);
            $variant = $item->variant_data['name'] ?? null;

            $rows[] = [
                'name' => $variant ? "{$item->product_name} ({$variant})" : (string) $item->product_name,
                'price' => round((float) $item->total_price / $quantity * $discountFactor, 2),
                'quantity' => $quantity,
            ];
        }

        if ((float) $order->delivery_price > 0) {
            $rows[] = ['name' => 'Доставка', 'price' => (float) $order->delivery_price, 'quantity' => 1.0];
        }

        return $rows;
    }
}
