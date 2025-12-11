<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Repositories\OrderRepository;
use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessMidtransWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $payload;

    protected $tries = 3;

    protected $timeout = 120;

    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(OrderRepository $orderRepository, OrderService $orderService): void
    {
        try {
            Log::info('Processing Midtrans webhook in queue', $this->payload);

            $serverKey = config('midtrans.server_key');
            $orderId = $this->payload['order_id'];
            $statusCode = $this->payload['status_code'];
            $grossAmount = $this->payload['gross_amount'];
            $signatureKey = $this->payload['signature_key'];
            $transactionStatus = $this->payload['transaction_status'];
            $fraudStatus = $this->payload['fraud_status'] ?? null;

            // Validasi signature sesuai dokumentasi Midtrans
            $mySignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

            if ($signatureKey != $mySignature) {
                Log::warning('Invalid signature key in queued job', [
                    'order_id' => $orderId,
                    'received_signature' => $signatureKey,
                    'expected_signature' => $mySignature,
                ]);

                return;
            }

            // Cari order menggunakan order_number
            $order = $orderRepository->findOrderByNumber($orderId);

            if (! $order) {
                Log::warning('Order not found in queued job', ['order_id' => $orderId]);

                return;
            }

            // Validasi tambahan: gross_amount harus sama dengan total_price di DB
            $orderAmountString = number_format((float) $order->total_price, 2, '.', '');
            if ((string) $grossAmount !== $orderAmountString) {
                Log::warning('Gross amount mismatch in queued job', [
                    'order_number' => $order->order_number,
                    'midtrans_gross_amount' => $grossAmount,
                    'order_total_price' => $orderAmountString,
                ]);

                return;
            }

            // Idempotensi sederhana: jika status sudah final, jangan timpa lagi
            if (in_array($order->status, [OrderStatus::PAID, OrderStatus::CANCELLED, OrderStatus::EXPIRED], true)) {
                Log::info('Order already in final state', [
                    'order_id' => $orderId,
                    'status' => $order->status,
                ]);

                return;
            }

            // Log notifikasi untuk idempotensi yang lebih kuat
            $payloadJson = json_encode($this->payload);
            $payloadHash = hash('sha256', $payloadJson);

            // Simpan log; jika payload sama sudah ada, anggap duplikat
            DB::table('payment_notifications')->updateOrInsert(
                ['payload_hash' => $payloadHash],
                [
                    'order_number' => $order->order_number,
                    'transaction_status' => $transactionStatus,
                    'signature_key' => $signatureKey,
                    'payload' => $payloadJson,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            // Proses callback menggunakan service
            $updatedOrder = $orderService->handleCallback($order, $transactionStatus, $fraudStatus);

            // Update stock produk jika status paid
            if ($updatedOrder->status === OrderStatus::PAID) {
                $orderService->updateProductStock($updatedOrder);
            }

            Log::info('Successfully processed Midtrans webhook', [
                'order_id' => $orderId,
                'status' => $updatedOrder->status,
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing Midtrans webhook job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $this->payload,
            ]);

            // Re-throw exception agar job bisa di-retry
            throw $e;
        }

    }
}
