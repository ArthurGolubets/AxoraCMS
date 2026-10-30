<?php

namespace HolartWeb\AxoraCMS\Services\MessageManager;

use HolartWeb\AxoraCMS\Models\MessageManager\TMessageAction;
use HolartWeb\AxoraCMS\Models\MessageManager\TMessageSubscription;
use HolartWeb\AxoraCMS\Models\MessageManager\TMessageTemplate;
use HolartWeb\AxoraCMS\Models\TModule;

/**
 * Starting content of the module. While the module is installed the built-in
 * Telegram order/payment notifications are off, so when Telegram is already
 * there the same messages are recreated as ordinary subscriptions.
 */
class MessageManagerSeeder
{
    public function seed(): void
    {
        if (TMessageSubscription::exists() || TMessageTemplate::exists()) {
            return;
        }

        if (! TModule::isInstalled('commerce')) {
            return;
        }

        $template = TMessageTemplate::create([
            'name' => 'Заказ принят (клиенту)',
            'event' => 'order.created',
            'subject' => 'Ваш заказ №#ORDER_ID# принят',
            'body' => $this->orderEmailBody(),
            'is_active' => true,
        ]);

        $email = TMessageSubscription::create([
            'name' => 'Письмо клиенту о новом заказе',
            'event' => 'order.created',
            'is_active' => false,
            'sort' => 1,
        ]);
        $email->actions()->create([
            'channel' => TMessageAction::CHANNEL_EMAIL,
            'sort' => 0,
            'config' => ['template_id' => $template->id, 'recipients' => '#ORDER_EMAIL#'],
        ]);

        if (! TModule::isInstalled('telegram')) {
            return;
        }

        $this->telegramSubscription(
            'Telegram: новый заказ',
            'order.created',
            [],
            "Заказ №#ORDER_ID#\nИмя: #ORDER_NAME#\nТелефон: #ORDER_PHONE#\nАдрес: #ORDER_ADDRESS#\nСпособ оплаты: #PAYMENT_TYPE#\nСпособ доставки: #DELIVERY_TYPE#\n\nТовары:\n#PRODUCTS#\n\nИТОГО: #TOTAL#",
            2,
        );
        $this->telegramSubscription(
            'Telegram: заказ оплачен',
            'order.payment_status_changed',
            [['variable' => 'PAYMENT_STATUS', 'operator' => 'eq', 'value' => 'Оплачен']],
            "✅ Заказ №#ORDER_ID# успешно оплачен\n\nСумма: #TOTAL#\nКлиент: #ORDER_NAME#\nТелефон: #ORDER_PHONE#\nEmail: #ORDER_EMAIL#",
            3,
        );
        $this->telegramSubscription(
            'Telegram: оплата отклонена',
            'order.payment_status_changed',
            [['variable' => 'PAYMENT_STATUS', 'operator' => 'eq', 'value' => 'Ошибка']],
            "❌ Оплата заказа №#ORDER_ID# отменена\n\nСумма: #TOTAL#\nКлиент: #ORDER_NAME#\nТелефон: #ORDER_PHONE#\nEmail: #ORDER_EMAIL#",
            4,
        );
    }

    /**
     * @param  array<int, array<string, string>>  $conditions
     */
    private function telegramSubscription(string $name, string $event, array $conditions, string $text, int $sort): void
    {
        $subscription = TMessageSubscription::create([
            'name' => $name,
            'event' => $event,
            'is_active' => true,
            'conditions' => $conditions,
            'sort' => $sort,
        ]);
        $subscription->actions()->create([
            'channel' => TMessageAction::CHANNEL_TELEGRAM,
            'sort' => 0,
            'config' => ['text' => $text, 'chat_ids' => []],
        ]);
    }

    private function orderEmailBody(): string
    {
        return <<<'HTML'
<div style="font-family:Arial,sans-serif;font-size:15px;color:#111827;max-width:600px;margin:0 auto">
  <h2 style="margin:0 0 16px">Спасибо за заказ, #ORDER_NAME#!</h2>
  <p>Заказ <b>№#ORDER_ID#</b> принят в обработку. Мы свяжемся с вами по телефону #ORDER_PHONE#.</p>
  #PRODUCTS#
  <p style="text-align:right;margin-top:12px">Доставка: #DELIVERY_PRICE#<br><b>ИТОГО: #TOTAL#</b></p>
  <p style="color:#6b7280;font-size:13px">#SITE_NAME# · #SITE_URL#</p>
</div>
HTML;
    }
}
