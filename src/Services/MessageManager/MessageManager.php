<?php

namespace HolartWeb\AxoraCMS\Services\MessageManager;

use HolartWeb\AxoraCMS\Jobs\SendMessageActionJob;
use HolartWeb\AxoraCMS\Models\Commerce\TOrderItems;
use HolartWeb\AxoraCMS\Models\Commerce\TOrders;
use HolartWeb\AxoraCMS\Models\Integrations\TIntegrationSettings;
use HolartWeb\AxoraCMS\Models\MessageManager\TMessageAction;
use HolartWeb\AxoraCMS\Models\MessageManager\TMessageLog;
use HolartWeb\AxoraCMS\Models\MessageManager\TMessageSubscription;
use HolartWeb\AxoraCMS\Models\MessageManager\TMessageTemplate;
use HolartWeb\AxoraCMS\Models\TModule;
use HolartWeb\AxoraCMS\Services\Integrations\TelegramService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Heart of the "Менеджер сообщений" module.
 *
 * dispatch() is called when an event happens: it finds the active subscriptions,
 * checks their conditions and schedules the actions in order. Delivery never
 * throws into the caller — a failed message must not break an order or a comment.
 */
class MessageManager
{
    public const MODULE_NAME = 'messagemanager';

    public const TELEGRAM_LIMIT = 4000;

    /**
     * Whether the module is installed. While it is, the built-in Telegram
     * notifications of orders and payments are switched off in favour of subscriptions.
     */
    public static function isActive(): bool
    {
        return TModule::isInstalled(self::MODULE_NAME);
    }

    /**
     * Trigger an event.
     *
     * @param  array<string, scalar|null>  $values  variable code (without #) => value
     * @param  array<string, string>  $html  ready HTML for variables that need markup in emails
     */
    public function dispatch(string $event, array $values = [], array $html = []): void
    {
        try {
            if (! self::isActive()) {
                return;
            }

            $values = array_map(fn ($value) => (string) ($value ?? ''), $values) + MessageEvents::commonValues();

            $subscriptions = TMessageSubscription::query()
                ->where('event', $event)
                ->where('is_active', true)
                ->with(['actions' => fn ($query) => $query->where('is_active', true)])
                ->orderBy('sort')
                ->orderBy('id')
                ->get();

            foreach ($subscriptions as $subscription) {
                if (! $this->conditionsMatch($subscription->conditions ?? [], $values)) {
                    continue;
                }

                $delay = 0;

                foreach ($subscription->actions as $action) {
                    $delay += max(0, (int) $action->delay_seconds);
                    $this->schedule($action, $event, $values, $html, $delay);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("MessageManager: event {$event} failed: ".$e->getMessage());
        }
    }

    /**
     * Trigger an order event for a saved order (its items must already exist).
     *
     * @param  array<string, string>  $extra  additional variables (e.g. OLD_STATUS)
     */
    public function dispatchOrder(string $event, TOrders $order, array $extra = []): void
    {
        try {
            if (! self::isActive()) {
                return;
            }

            $built = MessageEvents::orderValues($order, $order->items()->get());
            $this->dispatch($event, $extra + $built['values'], $built['html']);
        } catch (\Throwable $e) {
            Log::warning("MessageManager: event {$event} failed: ".$e->getMessage());
        }
    }

    /**
     * Trigger "До создания заказа": nothing is saved yet, so the order and its
     * items are built in memory from the incoming data.
     *
     * @param  array<string, mixed>  $orderData
     * @param  array<int, array<string, mixed>>  $items
     */
    public function dispatchOrderDraft(array $orderData, array $items): void
    {
        try {
            if (! self::isActive()) {
                return;
            }

            $built = MessageEvents::orderValues(
                new TOrders($orderData),
                Collection::make($items)->map(fn (array $item) => new TOrderItems($item)),
            );
            $this->dispatch('order.creating', $built['values'], $built['html']);
        } catch (\Throwable $e) {
            Log::warning('MessageManager: event order.creating failed: '.$e->getMessage());
        }
    }

    /**
     * Run one action now: render, send, write the log row.
     *
     * @param  array<string, string>  $values
     * @param  array<string, string>  $html
     */
    public function runAction(TMessageAction $action, string $event, array $values, array $html = []): void
    {
        $this->deliver(
            $action->channel,
            $action->config ?? [],
            $event,
            $values,
            $html,
            $action->subscription_id,
            $action->id,
        );
    }

    /**
     * Send a message built from sample values (the "Тест" button).
     *
     * @param  array<string, mixed>  $config
     * @return array{status: string, error: ?string}
     */
    public function sendTest(string $channel, array $config, ?string $event, ?string $testEmail = null): array
    {
        $values = MessageEvents::samples($event);

        if ($channel === TMessageAction::CHANNEL_EMAIL) {
            $config['recipients'] = (string) $testEmail;
        }

        return $this->deliver($channel, $config, $event ?? 'test', $values, [], null, null, true);
    }

    /**
     * Replace #CODE# tokens. Unknown tokens stay as typed so a typo is visible.
     *
     * @param  array<string, string>  $values
     * @param  array<string, string>  $html
     * @param  'plain'|'telegram'|'html'  $mode
     */
    public function render(string $template, array $values, array $html = [], string $mode = 'plain'): string
    {
        return preg_replace_callback('/#([A-Z0-9_]+)#/', function (array $match) use ($values, $html, $mode) {
            $code = $match[1];

            if (! array_key_exists($code, $values)) {
                return $match[0];
            }

            return match ($mode) {
                'telegram' => e($values[$code]),
                'html' => $html[$code] ?? nl2br(e($values[$code])),
                default => $values[$code],
            };
        }, $template) ?? $template;
    }

    /**
     * All conditions must hold. An unknown variable never matches.
     *
     * @param  array<int, array{variable?: string, operator?: string, value?: mixed}>  $conditions
     * @param  array<string, string>  $values
     */
    public function conditionsMatch(array $conditions, array $values): bool
    {
        foreach ($conditions as $condition) {
            $code = trim((string) ($condition['variable'] ?? ''), '# ');

            if ($code === '') {
                continue;
            }

            if (! $this->conditionHolds($values[$code] ?? null, (string) ($condition['operator'] ?? 'eq'), (string) ($condition['value'] ?? ''))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, string>  $values
     * @param  array<string, string>  $html
     * @return array{status: string, error: ?string}
     */
    protected function deliver(string $channel, array $config, string $event, array $values, array $html, ?int $subscriptionId, ?int $actionId, bool $isTest = false): array
    {
        $context = compact('event', 'subscriptionId', 'actionId', 'isTest');

        try {
            return match ($channel) {
                TMessageAction::CHANNEL_TELEGRAM => $this->deliverTelegram($config, $values, $context),
                TMessageAction::CHANNEL_EMAIL => $this->deliverEmail($config, $values, $html, $context),
                default => $this->log($context, $channel, TMessageLog::STATUS_SKIPPED, error: "Неизвестный канал «{$channel}»"),
            };
        } catch (\Throwable $e) {
            Log::warning("MessageManager: {$channel} delivery failed: ".$e->getMessage());

            return $this->log($context, $channel, TMessageLog::STATUS_FAILED, error: $e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, string>  $values
     * @param  array<string, mixed>  $context
     * @return array{status: string, error: ?string}
     */
    protected function deliverTelegram(array $config, array $values, array $context): array
    {
        $channel = TMessageAction::CHANNEL_TELEGRAM;

        if (! TModule::isInstalled('telegram')) {
            return $this->log($context, $channel, TMessageLog::STATUS_SKIPPED, error: 'Модуль Telegram не установлен');
        }

        $telegram = new TelegramService;

        if (! $telegram->isConfigured()) {
            return $this->log($context, $channel, TMessageLog::STATUS_FAILED, error: 'Telegram не настроен: укажите токен бота и чаты');
        }

        $known = array_map('strval', (array) TIntegrationSettings::get('telegram', 'chat_ids', []));
        $chosen = array_values(array_intersect($known, array_map('strval', (array) ($config['chat_ids'] ?? []))));
        $chats = $chosen !== [] ? $chosen : $known;

        $text = trim($this->render((string) ($config['text'] ?? ''), $values, [], 'telegram'));

        if ($text === '') {
            return $this->log($context, $channel, TMessageLog::STATUS_SKIPPED, implode(', ', $chats), error: 'Пустой текст сообщения');
        }

        $text = mb_substr($text, 0, self::TELEGRAM_LIMIT);
        $sent = $telegram->sendMessage($text, $chats);

        return $this->log(
            $context,
            $channel,
            $sent ? TMessageLog::STATUS_SENT : TMessageLog::STATUS_FAILED,
            implode(', ', $chats),
            body: $text,
            error: $sent ? null : 'Telegram вернул ошибку, подробности в логе приложения',
        );
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, string>  $values
     * @param  array<string, string>  $html
     * @param  array<string, mixed>  $context
     * @return array{status: string, error: ?string}
     */
    protected function deliverEmail(array $config, array $values, array $html, array $context): array
    {
        $channel = TMessageAction::CHANNEL_EMAIL;
        $template = TMessageTemplate::find($config['template_id'] ?? 0);

        if (! $template || ! $template->is_active) {
            return $this->log($context, $channel, TMessageLog::STATUS_SKIPPED, error: 'Шаблон письма не найден или выключен');
        }

        $recipients = $this->parseRecipients($this->render((string) ($config['recipients'] ?? ''), $values));

        if ($recipients === []) {
            return $this->log($context, $channel, TMessageLog::STATUS_SKIPPED, subject: $template->subject, error: 'Не найден ни один корректный email получателя');
        }

        $subject = str_replace(["\r", "\n"], ' ', $this->render($template->subject, $values));
        $body = $this->render($template->body, $values, $html, 'html');
        $result = ['status' => TMessageLog::STATUS_SENT, 'error' => null];

        foreach ($recipients as $recipient) {
            try {
                Mail::html($body, fn ($message) => $message->to($recipient)->subject($subject));
                $result = $this->log($context, $channel, TMessageLog::STATUS_SENT, $recipient, $subject, $body);
            } catch (\Throwable $e) {
                Log::warning("MessageManager: email to {$recipient} failed: ".$e->getMessage());
                $result = $this->log($context, $channel, TMessageLog::STATUS_FAILED, $recipient, $subject, $body, $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    protected function parseRecipients(string $line): array
    {
        $emails = preg_split('/[\s,;]+/', $line, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter($emails, fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))));
    }

    /**
     * @param  array<string, string>  $values
     * @param  array<string, string>  $html
     */
    protected function schedule(TMessageAction $action, string $event, array $values, array $html, int $delaySeconds): void
    {
        if ($delaySeconds > 0) {
            SendMessageActionJob::dispatch($action->id, $event, $values, $html)->delay(now()->addSeconds($delaySeconds));

            return;
        }

        // Actions without a pause run right after the response so a slow SMTP/Telegram
        // never delays checkout; in console (queue workers, imports) they run at once.
        if (app()->runningInConsole()) {
            SendMessageActionJob::dispatchSync($action->id, $event, $values, $html);
        } else {
            SendMessageActionJob::dispatchAfterResponse($action->id, $event, $values, $html);
        }
    }

    protected function conditionHolds(?string $actual, string $operator, string $expected): bool
    {
        if ($actual === null) {
            return false;
        }

        return match ($operator) {
            'eq' => mb_strtolower(trim($actual)) === mb_strtolower(trim($expected)),
            'neq' => mb_strtolower(trim($actual)) !== mb_strtolower(trim($expected)),
            'contains' => $expected !== '' && mb_stripos($actual, $expected) !== false,
            'not_contains' => $expected === '' || mb_stripos($actual, $expected) === false,
            'gt' => is_numeric($actual) && is_numeric($expected) && (float) $actual > (float) $expected,
            'lt' => is_numeric($actual) && is_numeric($expected) && (float) $actual < (float) $expected,
            'empty' => trim($actual) === '',
            'not_empty' => trim($actual) !== '',
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{status: string, error: ?string}
     */
    protected function log(array $context, string $channel, string $status, ?string $recipient = null, ?string $subject = null, ?string $body = null, ?string $error = null): array
    {
        TMessageLog::create([
            'subscription_id' => $context['subscriptionId'],
            'action_id' => $context['actionId'],
            'event' => $context['event'],
            'channel' => $channel,
            'recipient' => $recipient !== null ? mb_substr($recipient, 0, 500) : null,
            'subject' => $subject !== null ? mb_substr($subject, 0, 500) : null,
            'body' => $body,
            'status' => $status,
            'error' => $error !== null ? mb_substr($error, 0, 1000) : null,
            'is_test' => $context['isTest'],
        ]);

        return ['status' => $status, 'error' => $error];
    }
}
