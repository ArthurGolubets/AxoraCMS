<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\Integrations;

use HolartWeb\AxoraCMS\Models\Integrations\TBitrix24Log;
use HolartWeb\AxoraCMS\Models\Integrations\TBitrix24Rule;
use HolartWeb\AxoraCMS\Services\Integrations\Bitrix24\Bitrix24Client;
use HolartWeb\AxoraCMS\Services\Integrations\Bitrix24\Bitrix24Exception;
use HolartWeb\AxoraCMS\Services\Integrations\Bitrix24\Bitrix24Fields;
use HolartWeb\AxoraCMS\Services\Integrations\Bitrix24\Bitrix24Sync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class Bitrix24SettingsController extends Controller
{
    /**
     * The saved connection. The secret part of the webhook never leaves the server.
     */
    public function index(): JsonResponse
    {
        $webhook = Bitrix24Client::storedWebhook();

        return response()->json([
            'configured' => (bool) $webhook,
            'webhook_masked' => Bitrix24Client::maskedWebhook($webhook),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate($this->webhookRules(), $this->webhookMessages());

        Bitrix24Client::storeWebhook($data['webhook_url']);

        return response()->json(['success' => true, 'message' => 'Вебхук сохранён']);
    }

    /**
     * Check the address from the form, or the saved one when the field is empty.
     */
    public function test(Request $request): JsonResponse
    {
        $data = $request->validate(['webhook_url' => ['nullable', ...array_slice($this->webhookRules()['webhook_url'], 1)]], $this->webhookMessages());

        try {
            $scopes = (array) (new Bitrix24Client($data['webhook_url'] ?? null))->call('scope');
        } catch (Bitrix24Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        if (! in_array('crm', $scopes, true)) {
            return response()->json(['success' => false, 'message' => 'Вебхук работает, но у него нет права «CRM (crm)»'], 422);
        }

        return response()->json(['success' => true, 'message' => 'Подключение работает']);
    }

    /**
     * Fields of leads, deals, contacts and companies, read from the portal.
     */
    public function fields(Bitrix24Fields $fields): JsonResponse
    {
        try {
            return response()->json(['data' => $fields->catalog()]);
        } catch (Bitrix24Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function logs(Request $request): JsonResponse
    {
        $logs = TBitrix24Log::query()
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(30);

        return response()->json($logs->through(fn (TBitrix24Log $log) => [
            'id' => $log->id,
            'rule_id' => $log->rule_id,
            'rule_name' => $log->rule_name,
            'event' => $log->event,
            'status' => $log->status,
            'entity' => $log->entity,
            'entity_id' => $log->entity_id,
            'contact_id' => $log->contact_id,
            'company_id' => $log->company_id,
            'message' => $log->message,
            'created_at' => $log->created_at?->format('d.m.Y H:i:s'),
            'can_retry' => $log->status === TBitrix24Log::STATUS_FAILED && $log->rule_id !== null,
        ]));
    }

    /**
     * Run a failed rule again with the data of that moment. Records the failed attempt
     * managed to create are reused, so the retry does not double them.
     */
    public function retry(int $id, Bitrix24Sync $sync): JsonResponse
    {
        $log = TBitrix24Log::findOrFail($id);
        $rule = TBitrix24Rule::find($log->rule_id);

        if (! $rule || $log->status !== TBitrix24Log::STATUS_FAILED) {
            return response()->json(['message' => 'Эту запись нельзя отправить повторно'], 422);
        }

        $result = $sync->run($rule, $log->event, $log->payload['values'] ?? [], $log->payload['products'] ?? [], [
            'contact' => $log->contact_id,
            'company' => $log->company_id,
        ]);

        if ($result->status === TBitrix24Log::STATUS_FAILED) {
            return response()->json(['message' => $result->message], 422);
        }

        $log->delete();

        return response()->json(['success' => true, 'status' => $result->status]);
    }

    public function clearLogs(): JsonResponse
    {
        TBitrix24Log::query()->delete();

        return response()->json(['success' => true]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function webhookRules(): array
    {
        return ['webhook_url' => ['required', 'string', 'max:500', 'regex:'.Bitrix24Client::WEBHOOK_PATTERN]];
    }

    /**
     * @return array<string, string>
     */
    private function webhookMessages(): array
    {
        return ['webhook_url.regex' => 'Адрес должен выглядеть так: https://портал.bitrix24.ru/rest/1/ключ/'];
    }
}
