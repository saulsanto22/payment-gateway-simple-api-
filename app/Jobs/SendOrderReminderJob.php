<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Mail\OrderReminderMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Order ID (bukan model!).
     * 
     * BEST PRACTICE: Pass ID, bukan Model
     * WHY:
     * - Model di-serialize ke queue (bisa stale data)
     * - Model bisa besar (waste storage)
     * - Model relationship tidak ter-serialize
     * - Lebih aman untuk long-delayed jobs
     */
    protected int $orderId;

    /**
     * Jumlah maksimal retry.
     * 
     * CONTEXT: Email sending bisa gagal karena:
     * - SMTP server down
     * - Rate limiting
     * - Network issues
     * 
     * BEST PRACTICE: 5x untuk email (lebih toleran)
     */
    public $tries = 5;

    /**
     * Maximum execution time.
     * 
     * BEST PRACTICE: 60s cukup untuk email
     * - Email sending biasanya quick (1-5s)
     * - Kalau >60s, kemungkinan ada masalah
     */
    public $timeout = 60;

    /**
     * Exponential backoff untuk retry.
     * 
     * WHY: Email provider sering punya rate limit
     * - Retry terlalu cepat = kena rate limit lagi
     * - Backoff kasih waktu rate limit reset
     */
    public $backoff = [60, 120, 300, 600]; // 1m, 2m, 5m, 10m

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     * 
     * FLOW:
     * 1. Fetch order dari DB (fresh data)
     * 2. Validasi order masih pending
     * 3. Kirim email reminder
     * 4. Log success
     */
    public function handle(): void
    {
        try {
            // Fetch order with user relationship
            $order = Order::with('user')->find($this->orderId);

            // Validasi: Order mungkin sudah tidak ada (dihapus)
            if (!$order) {
                Log::warning('Order not found for reminder', [
                    'order_id' => $this->orderId,
                ]);
                return; // Skip, tidak perlu retry
            }

            // Validasi: Order sudah dibayar, tidak perlu reminder
            if ($order->status !== OrderStatus::PENDING) {
                Log::info('Order no longer pending, skipping reminder', [
                    'order_id' => $this->orderId,
                    'status' => $order->status->value,
                ]);
                return; // Skip, tidak perlu retry
            }

            // Validasi: User mungkin tidak ada email (edge case)
            if (!$order->user || !$order->user->email) {
                Log::warning('User email not found for order reminder', [
                    'order_id' => $this->orderId,
                ]);
                return; // Skip, tidak perlu retry
            }

            Log::info('Sending reminder email', [
                'order_id' => $this->orderId,
                'order_number' => $order->order_number,
                'email' => $order->user->email,
                'attempt' => $this->attempts(),
            ]);

            // Kirim email (synchronous, karena sudah di queue)
            Mail::to($order->user->email)
                ->send(new OrderReminderMail($order));

            Log::info('Reminder email sent successfully', [
                'order_id' => $this->orderId,
                'order_number' => $order->order_number,
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending order reminder email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'order_id' => $this->orderId,
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
            ]);

            // Re-throw untuk retry
            throw $e;
        }
    }

    /**
     * Handle job failure setelah semua retry habis.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Order reminder job failed permanently', [
            'job' => self::class,
            'order_id' => $this->orderId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // TODO: Alert developer
        // Slack::send("🚨 Failed to send reminder for order: {$this->orderId}");
    }

    /**
     * Tags untuk Horizon monitoring.
     */
    public function tags(): array
    {
        return [
            'email',
            'reminder',
            'order:' . $this->orderId,
        ];
    }
}
