<?php

namespace HolartWeb\HolartCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\HolartCMS\Models\Commerce\TPaymentTransaction;
use HolartWeb\HolartCMS\Models\Commerce\TOrders;
use HolartWeb\HolartCMS\Models\Integrations\TIntegrationSettings;
use Illuminate\Support\Facades\Log;

class YKassaCheckPaymentCommand extends Command
{
    protected $signature = 'holartcms:ykassa-check-payment';
    protected $description = 'Check YooKassa payment statuses';

    public function handle(): int
    {
        try {
            // Проверяем, установлена ли интеграция ЮКасса
            $shopId = TIntegrationSettings::get('yookassa', 'shop_id');
            $secretKey = TIntegrationSettings::get('yookassa', 'secret_key');

            if (empty($shopId) || empty($secretKey)) {
                return self::SUCCESS;
            }
        } catch (\Exception $e) {
            // Database not configured or module not installed
            return self::SUCCESS;
        }

        // Проверяем, установлен ли SDK ЮКассы
        if (!class_exists('\YooKassa\Client')) {
            $this->error('YooKassa SDK not installed. Please install yoomoney/yookassa-sdk-php');
            return self::FAILURE;
        }

        try {
            $client = new \YooKassa\Client();
            $client->setAuth($shopId, $secretKey);

            // Получаем все транзакции со статусом pending
            $pendingTransactions = TPaymentTransaction::where('status', 'pending')
                ->where('payment_system', 'yookassa')
                ->where('created_at', '>=', now()->subDays(7)) // Проверяем только за последние 7 дней
                ->get();

            if ($pendingTransactions->isEmpty()) {
                $this->info('No pending YooKassa payments found.');
                return self::SUCCESS;
            }

            $this->info("Found {$pendingTransactions->count()} pending payments. Checking statuses...");

            $updated = 0;

            foreach ($pendingTransactions as $transaction) {
                try {
                    // Получаем информацию о платеже из ЮКассы
                    $payment = $client->getPaymentInfo($transaction->external_id);

                    $oldStatus = $transaction->status;
                    $newStatus = $this->mapYKassaStatus($payment->getStatus());

                    if ($oldStatus !== $newStatus) {
                        $transaction->status = $newStatus;
                        $transaction->save();

                        // Обновляем статус заказа, если платеж успешен
                        if ($newStatus === 'completed') {
                            $order = TOrders::find($transaction->order_id);
                            if ($order && $order->status === 'pending') {
                                $order->status = 'paid';
                                $order->save();
                                $this->info("Order #{$order->id} marked as paid.");
                            }
                        }

                        // Если платеж отменен/истек
                        if (in_array($newStatus, ['failed', 'cancelled'])) {
                            $order = TOrders::find($transaction->order_id);
                            if ($order && $order->status === 'pending') {
                                $order->status = 'cancelled';
                                $order->save();
                                $this->info("Order #{$order->id} marked as cancelled.");
                            }
                        }

                        $updated++;
                        $this->info("Transaction #{$transaction->id}: {$oldStatus} -> {$newStatus}");
                    }
                } catch (\Exception $e) {
                    Log::error("YKassa check payment error for transaction #{$transaction->id}: " . $e->getMessage());
                    $this->error("Error checking transaction #{$transaction->id}: " . $e->getMessage());
                }
            }

            $this->info("Payment check completed. Updated: {$updated}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            Log::error('YKassa check payment command error: ' . $e->getMessage());
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Map YooKassa status to internal status
     *
     * @param string $ykassaStatus
     * @return string
     */
    private function mapYKassaStatus(string $ykassaStatus): string
    {
        return match ($ykassaStatus) {
            'pending' => 'pending',
            'waiting_for_capture' => 'pending',
            'succeeded' => 'completed',
            'canceled' => 'cancelled',
            default => 'failed',
        };
    }
}
