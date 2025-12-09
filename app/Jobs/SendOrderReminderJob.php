<?php

namespace App\Jobs;

use App\Mail\OrderReminderMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderReminderJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue;

    /**
     * Create a new job instance.
     */
    public $tries = 5; // Allow 5 attempts
    public $timeout = 120; // Allow 120 seconds for execution
    public $retryAfter = 60;
    protected $order;
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //

        try {
            Log::info($this->order->user->email);
            Mail::to($this->order->user->email)
                ->queue(new OrderReminderMail($this->order));

            Log::info('Reminder email sent for order ' . $this->order->order_number);
        } catch (\Exception $e) {
            Log::error('Error sending order reminder email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'order_id' => $this->order->id
            ]);

            throw $e;

        }
    }
}
