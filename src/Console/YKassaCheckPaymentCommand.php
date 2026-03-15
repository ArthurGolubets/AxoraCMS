<?php

namespace HolartWeb\AxoraCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\AxoraCMS\Models\Commerce\TPaymentTransaction;
use HolartWeb\AxoraCMS\Services\Integrations\YookassaService;
use Illuminate\Support\Facades\Log;

class YKassaCheckPaymentCommand extends Command
{
    protected $signature = 'axoracms:ykassa-check-payment';
    protected $description = 'Check YooKassa payment statuses';

    public function handle(): int
    {
        $yookassaService = new YookassaService();

        // Проверяем, установлена ли интеграция ЮКасса
        if (!$yookassaService->isConfigured()) {
            $this->info('YooKassa is not configured.');
            return self::SUCCESS;
        }

        // Проверяем, установлен ли SDK ЮКассы
        if (!class_exists('\YooKassa\Client')) {
            $this->error('YooKassa SDK not installed. Please install yoomoney/yookassa-sdk-php');
            return self::FAILURE;
        }

        try {
            // Получаем все транзакции со статусом pending
            $pendingTransactions = TPaymentTransaction::where('status', TPaymentTransaction::STATUS_PENDING)
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
                    $oldStatus = $transaction->status;

                    // Используем метод checkPayment из YookassaService
                    $paymentStatus = $yookassaService->checkPayment($transaction);

                    if ($paymentStatus && $transaction->fresh()->status !== $oldStatus) {
                        $updated++;
                        $this->info("Transaction #{$transaction->id}: {$oldStatus} -> {$transaction->fresh()->status} (YooKassa: {$paymentStatus})");
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
}
