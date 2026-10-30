<?php

namespace HolartWeb\AxoraCMS\Services\MessageManager;

use HolartWeb\AxoraCMS\Models\Callback\TComments;
use HolartWeb\AxoraCMS\Models\Callback\TCustomFormSubmission;
use HolartWeb\AxoraCMS\Models\Callback\TUserRequests;
use HolartWeb\AxoraCMS\Models\Commerce\TOrderItems;
use HolartWeb\AxoraCMS\Models\Commerce\TOrders;
use HolartWeb\AxoraCMS\Models\TModule;
use Illuminate\Support\Collection;

/**
 * Catalogue of events the "Менеджер сообщений" module can react to, the
 * #VARIABLES# each one offers, and builders that turn a model into the values.
 *
 * Variable values are plain strings. HTML versions (for email) are produced only
 * where the plain text would lose structure (the product list).
 */
class MessageEvents
{
    public const COMMON_VARIABLES = [
        'SITE_NAME' => 'Название сайта',
        'SITE_URL' => 'Адрес сайта',
        'DATE' => 'Текущая дата',
        'DATETIME' => 'Текущие дата и время',
    ];

    public const ORDER_VARIABLES = [
        'ORDER_ID' => 'Номер заказа',
        'ORDER_NAME' => 'Имя покупателя',
        'ORDER_EMAIL' => 'Email покупателя',
        'ORDER_PHONE' => 'Телефон покупателя',
        'ORDER_ADDRESS' => 'Адрес доставки',
        'ORDER_COMMENT' => 'Комментарий к заказу',
        'DELIVERY_TYPE' => 'Способ доставки',
        'PAYMENT_TYPE' => 'Способ оплаты',
        'PAYMENT_STATUS' => 'Статус оплаты',
        'PAYMENT_STATUS_CODE' => 'Код статуса оплаты (pending, paid, failed, refunded)',
        'DELIVERY_STATUS' => 'Статус доставки',
        'DELIVERY_STATUS_CODE' => 'Код статуса доставки (pending, processing, shipped, delivered, cancelled)',
        'PRODUCTS' => 'Список товаров',
        'PRODUCTS_COUNT' => 'Количество позиций',
        'GOODS_PRICE' => 'Сумма товаров',
        'DELIVERY_PRICE' => 'Стоимость доставки',
        'DISCOUNT' => 'Скидка по промокоду',
        'TOTAL' => 'ИТОГО',
        'TOTAL_RAW' => 'ИТОГО числом, без знака ₽ (для условия «больше/меньше»)',
        'ORDER_URL' => 'Ссылка на заказ в админке',
    ];

    public const ORDER_CHANGE_VARIABLES = [
        'OLD_STATUS' => 'Предыдущий статус',
        'NEW_STATUS' => 'Новый статус',
    ];

    public const COMMENT_VARIABLES = [
        'COMMENT_ID' => 'ID комментария',
        'COMMENT_NAME' => 'Имя автора',
        'COMMENT_EMAIL' => 'Email автора',
        'COMMENT_PHONE' => 'Телефон автора',
        'COMMENT_TEXT' => 'Текст комментария',
        'COMMENT_RATING' => 'Оценка',
        'COMMENT_PRODUCT' => 'Товар, к которому оставлен комментарий',
        'COMMENT_URL' => 'Ссылка на комментарий в админке',
    ];

    public const USER_REQUEST_VARIABLES = [
        'REQUEST_ID' => 'ID заявки',
        'REQUEST_NAME' => 'Имя',
        'REQUEST_EMAIL' => 'Email',
        'REQUEST_PHONE' => 'Телефон',
        'REQUEST_COMMENT' => 'Сообщение',
        'REQUEST_URL' => 'Ссылка на заявку в админке',
    ];

    public const FORM_VARIABLES = [
        'FORM_NAME' => 'Название формы',
        'FORM_CODE' => 'Код формы',
        'FORM_FIELDS' => 'Все заполненные поля списком',
        'SUBMISSION_ID' => 'ID записи',
        'SUBMISSION_URL' => 'Ссылка на запись в админке',
    ];

    /**
     * Example values for the preview and test send.
     */
    private const SAMPLES = [
        'SITE_NAME' => 'Мой магазин',
        'SITE_URL' => 'https://example.com',
        'ORDER_ID' => '1024',
        'ORDER_NAME' => 'Иван Петров',
        'ORDER_EMAIL' => 'ivan@example.com',
        'ORDER_PHONE' => '+7 900 000-00-00',
        'ORDER_ADDRESS' => 'Москва, ул. Тверская, 1',
        'ORDER_COMMENT' => 'Позвоните за час до доставки',
        'DELIVERY_TYPE' => 'Курьер',
        'PAYMENT_TYPE' => 'Онлайн',
        'PAYMENT_STATUS' => 'Оплачен',
        'PAYMENT_STATUS_CODE' => 'paid',
        'DELIVERY_STATUS' => 'Ожидает',
        'DELIVERY_STATUS_CODE' => 'pending',
        'PRODUCTS' => "1. Футболка (M, чёрная) × 2 — 2 400 ₽\n2. Кепка × 1 — 900 ₽",
        'PRODUCTS_COUNT' => '2',
        'GOODS_PRICE' => '3 300 ₽',
        'DELIVERY_PRICE' => '300 ₽',
        'DISCOUNT' => '0 ₽',
        'TOTAL' => '3 600 ₽',
        'TOTAL_RAW' => '3600',
        'ORDER_URL' => 'https://example.com/admin/orders/1024',
        'OLD_STATUS' => 'Ожидает',
        'NEW_STATUS' => 'Оплачен',
        'COMMENT_ID' => '15',
        'COMMENT_NAME' => 'Анна',
        'COMMENT_EMAIL' => 'anna@example.com',
        'COMMENT_PHONE' => '+7 900 111-11-11',
        'COMMENT_TEXT' => 'Отличный товар, всё понравилось!',
        'COMMENT_RATING' => '5',
        'COMMENT_PRODUCT' => 'Футболка',
        'COMMENT_URL' => 'https://example.com/admin/comments/15',
        'REQUEST_ID' => '7',
        'REQUEST_NAME' => 'Мария',
        'REQUEST_EMAIL' => 'maria@example.com',
        'REQUEST_PHONE' => '+7 900 222-22-22',
        'REQUEST_COMMENT' => 'Перезвоните, пожалуйста',
        'REQUEST_URL' => 'https://example.com/admin/user-requests/7',
        'FORM_NAME' => 'Заявка на консультацию',
        'FORM_CODE' => 'consultation',
        'FORM_FIELDS' => "Имя: Олег\nТелефон: +7 900 333-33-33",
        'SUBMISSION_ID' => '31',
        'SUBMISSION_URL' => 'https://example.com/admin/custom-forms/1/submissions/31',
    ];

    private const TECHNICAL_GROUP = 'Для условий (технические)';

    /**
     * Palette group of every variable; anything not listed falls under "Данные события".
     *
     * @var array<string, array<int, string>>
     */
    private const VARIABLE_GROUPS = [
        'Сайт и дата' => ['SITE_NAME', 'SITE_URL', 'DATE', 'DATETIME'],
        'Покупатель' => ['ORDER_NAME', 'ORDER_EMAIL', 'ORDER_PHONE', 'ORDER_ADDRESS', 'ORDER_COMMENT'],
        'Заказ' => ['ORDER_ID', 'PRODUCTS', 'PRODUCTS_COUNT', 'DELIVERY_TYPE', 'PAYMENT_TYPE', 'ORDER_URL'],
        'Суммы' => ['GOODS_PRICE', 'DELIVERY_PRICE', 'DISCOUNT', 'TOTAL'],
        'Статусы' => ['PAYMENT_STATUS', 'DELIVERY_STATUS', 'OLD_STATUS', 'NEW_STATUS'],
        'Автор' => ['COMMENT_NAME', 'COMMENT_EMAIL', 'COMMENT_PHONE', 'REQUEST_NAME', 'REQUEST_EMAIL', 'REQUEST_PHONE'],
        self::TECHNICAL_GROUP => ['PAYMENT_STATUS_CODE', 'DELIVERY_STATUS_CODE', 'TOTAL_RAW'],
    ];

    /**
     * Values a condition can pick from instead of typing.
     *
     * @return array<string, array<int, string>>
     */
    private static function valueOptions(): array
    {
        $payment = ['Ожидает', 'Оплачен', 'Ошибка', 'Возврат'];
        $delivery = ['Ожидает', 'В обработке', 'Отправлен', 'Доставлен', 'Отменен'];

        return [
            'PAYMENT_STATUS' => $payment,
            'DELIVERY_STATUS' => $delivery,
            'OLD_STATUS' => array_values(array_unique([...$payment, ...$delivery])),
            'NEW_STATUS' => array_values(array_unique([...$payment, ...$delivery])),
            'DELIVERY_TYPE' => ['Самовывоз', 'Курьер', 'Почта', 'СДЭК'],
            'PAYMENT_TYPE' => ['Онлайн', 'Наличными', 'Картой'],
        ];
    }

    /**
     * Variables as the editor shows them: plain label, group and an example value.
     *
     * @param  array<string, string>  $variables  CODE => label
     * @return array<int, array{code: string, label: string, group: string, sample: string, technical: bool, options: array<int, string>}>
     */
    public static function describe(array $variables): array
    {
        $samples = self::SAMPLES;
        $groupOf = [];

        foreach (self::VARIABLE_GROUPS as $group => $codes) {
            foreach ($codes as $code) {
                $groupOf[$code] = $group;
            }
        }

        $result = [];

        foreach ($variables as $code => $label) {
            $group = $groupOf[$code] ?? 'Данные события';
            $result[] = [
                'code' => $code,
                'label' => $label,
                'group' => $group,
                'sample' => $samples[$code] ?? '',
                'technical' => $group === self::TECHNICAL_GROUP,
                'options' => self::valueOptions()[$code] ?? [],
            ];
        }

        return $result;
    }

    /**
     * Events added by project code through register().
     *
     * @var array<string, array{label: string, group: string, variables: array<string, string>, requires: ?string}>
     */
    private static array $custom = [];

    /**
     * Let project code declare its own event (e.g. "user.registered") and the
     * variables it will pass to MessageManager::dispatch().
     *
     * @param  array<string, string>  $variables  CODE => description
     */
    public static function register(string $code, string $label, array $variables = [], string $group = 'Свои события'): void
    {
        self::$custom[$code] = ['label' => $label, 'group' => $group, 'variables' => $variables, 'requires' => null];
    }

    /**
     * Every event with the modules it needs.
     *
     * @return array<string, array{label: string, group: string, variables: array<string, string>, requires: ?string}>
     */
    public static function all(): array
    {
        $order = self::ORDER_VARIABLES;
        $orderChange = $order + self::ORDER_CHANGE_VARIABLES;

        $events = [
            'order.creating' => ['label' => 'До создания заказа', 'group' => 'Заказы', 'variables' => $order, 'requires' => 'commerce'],
            'order.created' => ['label' => 'После создания заказа', 'group' => 'Заказы', 'variables' => $order, 'requires' => 'commerce'],
            'order.payment_status_changed' => ['label' => 'Изменился статус оплаты заказа', 'group' => 'Заказы', 'variables' => $orderChange, 'requires' => 'commerce'],
            'order.delivery_status_changed' => ['label' => 'Изменился статус доставки заказа', 'group' => 'Заказы', 'variables' => $orderChange, 'requires' => 'commerce'],
            'comment.creating' => ['label' => 'До создания комментария', 'group' => 'Обратная связь', 'variables' => self::COMMENT_VARIABLES, 'requires' => 'callback'],
            'comment.created' => ['label' => 'После создания комментария', 'group' => 'Обратная связь', 'variables' => self::COMMENT_VARIABLES, 'requires' => 'callback'],
            'comment.moderated' => ['label' => 'Комментарий прошёл модерацию', 'group' => 'Обратная связь', 'variables' => self::COMMENT_VARIABLES, 'requires' => 'callback'],
            'user_request.creating' => ['label' => 'До создания заявки', 'group' => 'Обратная связь', 'variables' => self::USER_REQUEST_VARIABLES, 'requires' => 'callback'],
            'user_request.created' => ['label' => 'После создания заявки', 'group' => 'Обратная связь', 'variables' => self::USER_REQUEST_VARIABLES, 'requires' => 'callback'],
            'form_submission.created' => ['label' => 'Новая запись своей формы', 'group' => 'Обратная связь', 'variables' => self::FORM_VARIABLES, 'requires' => 'callback'],
        ];

        return $events + self::$custom;
    }

    /**
     * Events whose module is installed.
     *
     * @return array<string, array{label: string, group: string, variables: array<string, string>, requires: ?string}>
     */
    public static function available(): array
    {
        return array_filter(self::all(), fn (array $event) => ! $event['requires'] || TModule::isInstalled($event['requires']));
    }

    /**
     * Variables of an event, common ones first.
     *
     * @return array<string, string>
     */
    public static function variablesFor(?string $event): array
    {
        $specific = $event ? (self::all()[$event]['variables'] ?? []) : [];

        return self::COMMON_VARIABLES + $specific;
    }

    /**
     * Example values for the given variable codes.
     *
     * @param  array<int, string>|null  $codes
     * @return array<string, string>
     */
    public static function samples(?string $event): array
    {
        $samples = [];

        $site = array_filter(['SITE_NAME' => (string) config('app.name'), 'SITE_URL' => rtrim((string) config('app.url'), '/')]);

        foreach (array_keys(self::variablesFor($event)) as $code) {
            $samples[$code] = $site[$code] ?? self::SAMPLES[$code] ?? ($code === 'DATE' ? now()->format('d.m.Y') : ($code === 'DATETIME' ? now()->format('d.m.Y H:i') : '['.$code.']'));
        }

        return $samples;
    }

    /**
     * HTML versions of example values (the product table), so a preview looks like the real email.
     *
     * @return array<string, string>
     */
    public static function sampleHtml(?string $event): array
    {
        if (! array_key_exists('PRODUCTS', self::variablesFor($event))) {
            return [];
        }

        $items = collect([
            new TOrderItems(['product_name' => 'Футболка', 'variant_data' => ['name' => 'M, чёрная'], 'amount' => 2, 'total_price' => 2400]),
            new TOrderItems(['product_name' => 'Кепка', 'amount' => 1, 'total_price' => 900]),
        ]);

        return self::orderValues(new TOrders, $items)['html'];
    }

    /**
     * Values every event shares.
     *
     * @return array<string, string>
     */
    public static function commonValues(): array
    {
        return [
            'SITE_NAME' => (string) config('app.name'),
            'SITE_URL' => rtrim((string) config('app.url'), '/'),
            'DATE' => now()->format('d.m.Y'),
            'DATETIME' => now()->format('d.m.Y H:i'),
        ];
    }

    /**
     * Order values. Works for a saved order and for an unsaved one built from
     * the request data ("До создания заказа": no id yet).
     *
     * @param  Collection<int, TOrderItems>  $items
     * @return array{values: array<string, string>, html: array<string, string>}
     */
    public static function orderValues(TOrders $order, Collection $items): array
    {
        $lines = [];
        $htmlRows = [];

        foreach ($items->values() as $index => $item) {
            $name = (string) $item->product_name;
            $variant = $item->variant_data['name'] ?? null;
            $title = $variant ? "{$name} ({$variant})" : $name;
            $line = ($index + 1).". {$title} × {$item->amount} — ".self::money($item->total_price);
            $lines[] = $line;
            $htmlRows[] = '<tr><td style="padding:6px 8px;border-bottom:1px solid #e5e7eb">'.e($title).'</td>'
                .'<td style="padding:6px 8px;border-bottom:1px solid #e5e7eb;text-align:center">'.e((string) $item->amount).'</td>'
                .'<td style="padding:6px 8px;border-bottom:1px solid #e5e7eb;text-align:right;white-space:nowrap">'.e(self::money($item->total_price)).'</td></tr>';
        }

        $prefix = rtrim((string) config('app.url'), '/').'/'.trim((string) config('axora-cms.route_prefix', 'admin'), '/');

        $values = [
            'ORDER_ID' => (string) ($order->id ?? ''),
            'ORDER_NAME' => (string) $order->name,
            'ORDER_EMAIL' => (string) $order->email,
            'ORDER_PHONE' => (string) $order->phone,
            'ORDER_ADDRESS' => (string) $order->delivery_address,
            'ORDER_COMMENT' => (string) $order->comments,
            'DELIVERY_TYPE' => self::label(['pickup' => 'Самовывоз', 'courier' => 'Курьер', 'post' => 'Почта', 'cdek' => 'СДЭК'], $order->delivery_type),
            'PAYMENT_TYPE' => self::label(['online' => 'Онлайн', 'cash' => 'Наличными', 'card' => 'Картой'], $order->payment_type),
            'PAYMENT_STATUS' => self::paymentStatusLabel($order->payment_status),
            'PAYMENT_STATUS_CODE' => (string) $order->payment_status,
            'DELIVERY_STATUS' => self::deliveryStatusLabel($order->delivery_status),
            'DELIVERY_STATUS_CODE' => (string) $order->delivery_status,
            'PRODUCTS' => implode("\n", $lines),
            'PRODUCTS_COUNT' => (string) $items->count(),
            'GOODS_PRICE' => self::money($order->goods_price),
            'DELIVERY_PRICE' => self::money($order->delivery_price),
            'DISCOUNT' => self::money($order->promocode_discount),
            'TOTAL' => self::money($order->total_price),
            'TOTAL_RAW' => (string) (float) $order->total_price,
            'ORDER_URL' => $order->id ? $prefix.'/orders/'.$order->id : '',
        ];

        $html = [];
        if ($htmlRows !== []) {
            $html['PRODUCTS'] = '<table style="width:100%;border-collapse:collapse;font-size:14px"><thead><tr>'
                .'<th style="padding:6px 8px;text-align:left;border-bottom:2px solid #d1d5db">Товар</th>'
                .'<th style="padding:6px 8px;text-align:center;border-bottom:2px solid #d1d5db">Кол-во</th>'
                .'<th style="padding:6px 8px;text-align:right;border-bottom:2px solid #d1d5db">Сумма</th></tr></thead><tbody>'
                .implode('', $htmlRows).'</tbody></table>';
        }

        return ['values' => $values, 'html' => $html];
    }

    /**
     * @return array<string, string>
     */
    public static function commentValues(TComments $comment): array
    {
        $prefix = rtrim((string) config('app.url'), '/').'/'.trim((string) config('axora-cms.route_prefix', 'admin'), '/');
        $product = '';

        if ($comment->product_id) {
            try {
                $product = (string) ($comment->product?->name ?? '');
            } catch (\Throwable $e) {
                $product = '';
            }
        }

        return [
            'COMMENT_ID' => (string) ($comment->id ?? ''),
            'COMMENT_NAME' => (string) $comment->name,
            'COMMENT_EMAIL' => (string) $comment->email,
            'COMMENT_PHONE' => (string) $comment->phone,
            'COMMENT_TEXT' => (string) $comment->comment,
            'COMMENT_RATING' => (string) ($comment->rating ?? ''),
            'COMMENT_PRODUCT' => $product,
            'COMMENT_URL' => $comment->id ? $prefix.'/comments/'.$comment->id : '',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function userRequestValues(TUserRequests $request): array
    {
        $prefix = rtrim((string) config('app.url'), '/').'/'.trim((string) config('axora-cms.route_prefix', 'admin'), '/');

        return [
            'REQUEST_ID' => (string) ($request->id ?? ''),
            'REQUEST_NAME' => (string) $request->name,
            'REQUEST_EMAIL' => (string) $request->email,
            'REQUEST_PHONE' => (string) $request->phone,
            'REQUEST_COMMENT' => (string) $request->comment,
            'REQUEST_URL' => $request->id ? $prefix.'/user-requests/'.$request->id : '',
        ];
    }

    /**
     * Besides the fixed variables every field is exposed as #FIELD_<CODE>#.
     *
     * @return array<string, string>
     */
    public static function formSubmissionValues(TCustomFormSubmission $submission): array
    {
        $form = $submission->form;
        $prefix = rtrim((string) config('app.url'), '/').'/'.trim((string) config('axora-cms.route_prefix', 'admin'), '/');
        $labels = $form ? $form->fields->pluck('name', 'code')->all() : [];
        $lines = [];
        $values = [];

        foreach ((array) $submission->data as $code => $value) {
            $text = is_array($value) ? implode(', ', array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE), $value)) : (string) $value;
            $lines[] = ($labels[$code] ?? $code).': '.$text;
            $values['FIELD_'.strtoupper((string) $code)] = $text;
        }

        return $values + [
            'FORM_NAME' => (string) ($form?->name ?? ''),
            'FORM_CODE' => (string) ($form?->code ?? ''),
            'FORM_FIELDS' => implode("\n", $lines),
            'SUBMISSION_ID' => (string) ($submission->id ?? ''),
            'SUBMISSION_URL' => $submission->id ? $prefix.'/custom-forms/'.$submission->form_id.'/submissions/'.$submission->id : '',
        ];
    }

    public static function paymentStatusLabel(?string $status): string
    {
        return self::label(['pending' => 'Ожидает', 'paid' => 'Оплачен', 'failed' => 'Ошибка', 'refunded' => 'Возврат'], $status);
    }

    public static function deliveryStatusLabel(?string $status): string
    {
        return self::label(['pending' => 'Ожидает', 'processing' => 'В обработке', 'shipped' => 'Отправлен', 'delivered' => 'Доставлен', 'cancelled' => 'Отменен'], $status);
    }

    /**
     * @param  array<string, string>  $labels
     */
    private static function label(array $labels, ?string $code): string
    {
        return $labels[$code] ?? (string) $code;
    }

    private static function money(mixed $amount): string
    {
        $formatted = number_format((float) $amount, 2, ',', ' ');

        return preg_replace('/,00$/', '', $formatted).' ₽';
    }
}
