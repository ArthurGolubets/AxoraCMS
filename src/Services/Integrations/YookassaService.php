<?php

namespace HolartWeb\AxoraCMS\Services\Integrations;

use HolartWeb\AxoraCMS\Models\Integrations\TIntegrationSettings;
use HolartWeb\AxoraCMS\Models\Commerce\TPaymentTransaction;
use HolartWeb\AxoraCMS\Models\TModule;
use YooKassa\Client;
use YooKassa\Model\Payment\ConfirmationType;
use YooKassa\Request\Payments\CreatePaymentRequest;

class YookassaService
{
    private $vatCodeData = 2; // https://yookassa.ru/developers/payment-acceptance/receipts/54fz/other-services/parameters-values
    /**
     * Get shop ID
     *
     * @return string|null
     */
    public function getShopId(): ?string
    {
        return TIntegrationSettings::get('yookassa', 'shop_id');
    }

    /**
     * Get secret key
     *
     * @return string|null
     */
    public function getSecretKey(): ?string
    {
        return TIntegrationSettings::get('yookassa', 'secret_key');
    }

    /**
     * Check if Yookassa is configured
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->getShopId()) && !empty($this->getSecretKey());
    }

    /**
     * Create payment for order
     *
     * @param array $params Payment parameters (order_id, amount, currency, description, email, phone, items)
     * @param string $routeName Route name for return URL
     * @return string|null Confirmation URL or null on error
     */
    public function createOrderPayment(array $params, string $routeName = 'order.success'): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $client = new Client();
        $client->setAuth($this->getShopId(), $this->getSecretKey());

        $builder = CreatePaymentRequest::builder();
        $builder->setAmount($params['amount'])
                ->setCurrency($params['currency'])
                ->setCapture(true)
                ->setDescription($params['description'] ?? $this->getDefaultDescription())
                ->setMetadata([
                    'order_id' => $params['order_id'],
                    'language' => 'ru',
                ]);

        $builder->setConfirmation([
            'type' => ConfirmationType::REDIRECT,
            'returnUrl' => $this->renderReturnUrl($params['order_id'], $routeName),
        ]);

        if (!empty($params['email'])) {
            $builder->setReceiptEmail($params['email']);
        }

        if (!empty($params['phone'])) {
            $phone = str_replace(['+', ' ', '(', ')', '-', ' '], '', $params['phone']);
            $builder->setReceiptPhone($phone);
        }

        if (!empty($params['items']) && count($params['items']) > 0) {
            foreach ($params['items'] as $item) {
                $builder->addReceiptItem(
                    $item['name'],
                    $item['price'],
                    $item['quantity'],
                    $this->vatCodeData,
                    'full_payment',
                    'commodity'
                );
            }
        }

        try {
            $request = $builder->build();
            $idempotenceKey = uniqid('', true);
            $response = $client->createPayment($request, $idempotenceKey);

            $confirmationUrl = $response->getConfirmation()->getConfirmationUrl();

            if ($confirmationUrl) {
                TPaymentTransaction::create([
                    'order_id' => $params['order_id'],
                    'transaction_id' => $response->getId(),
                    'link' => $confirmationUrl,
                    'status' => TPaymentTransaction::STATUS_PENDING,
                ]);
            }

            return $confirmationUrl;
        } catch (\Exception $e) {
            // Log error instead of dd()
            \Log::error('Yookassa payment creation error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check payment status and update transaction
     *
     * @param TPaymentTransaction $transaction Payment transaction
     * @return string|null Payment status or null on error
     */
    public function checkPayment(TPaymentTransaction $transaction): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $client = new Client();
        $client->setAuth($this->getShopId(), $this->getSecretKey());

        try {
            $response = $client->getPaymentInfo($transaction->transaction_id);
            $status = $response->getStatus();

            if ($status === 'succeeded') {
                $transaction->order->payment_status = 'paid';
                $transaction->order->save();

                $transaction->status = TPaymentTransaction::STATUS_SUCCESS;
                $transaction->save();

                // Send Telegram notification if module is installed
                if (TModule::isInstalled('telegram')) {
                    $order = $transaction->order;
                    $message = "✅ Заказ №{$order->id} успешно оплачен\n\n";
                    $message .= "Сумма: {$order->total_price} руб.\n";
                    $message .= "Клиент: {$order->name}\n";
                    $message .= "Телефон: {$order->phone}\n";
                    $message .= "Email: {$order->email}";

                    (new TelegramService())->sendMessage($message);
                }
            }

            if ($status === 'canceled') {
                $transaction->status = TPaymentTransaction::STATUS_CANCEL;
                $transaction->save();

                $transaction->order->payment_status = 'failed';
                $transaction->order->save();

                // Send Telegram notification if module is installed
                if (TModule::isInstalled('telegram')) {
                    $order = $transaction->order;
                    $message = "❌ Оплата заказа №{$order->id} отменена\n\n";
                    $message .= "Сумма: {$order->total_price} руб.\n";
                    $message .= "Клиент: {$order->name}\n";
                    $message .= "Телефон: {$order->phone}\n";
                    $message .= "Email: {$order->email}";

                    (new TelegramService())->sendMessage($message);
                }
            }

            return $status;
        } catch (\Exception $e) {
            \Log::error('Yookassa payment check error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Capture payment
     * TODO: Implement payment capture logic
     *
     * @param string $paymentId
     * @param array $data
     * @return array|null
     */
    public function capturePayment(string $paymentId, array $data = []): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        // TODO: Implement Yookassa API call for payment capture

        return null;
    }

    /**
     * Refund payment
     * TODO: Implement refund logic
     *
     * @param string $paymentId
     * @param array $data
     * @return array|null
     */
    public function refundPayment(string $paymentId, array $data): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        // TODO: Implement Yookassa API call for refund

        return null;
    }

    /**
     * Get default payment description
     *
     * @return string
     */
    private function getDefaultDescription(): string
    {
        return "Оплата заказа на сайте " . config('app.url');
    }

    /**
     * Render return URL for payment
     *
     * @param int $orderId Order ID
     * @param string $routeName Route name
     * @return string
     */
    private function renderReturnUrl(int $orderId, string $routeName = 'order.success'): string
    {
        return route($routeName, ['id' => $orderId]);
    }
}
