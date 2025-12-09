<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Jobs\SendOrderReminderJob;
use App\Models\Order;
use Illuminate\Console\Command;

class ReminderUnpaidOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:reminder-unpaid-order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for unpaid orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //

        //ambil data order yang statusnya unpaid dan kirimkan email reminder
        $orders = Order::where('status', OrderStatus::PENDING)
            ->where('created_at', '<=', now()->subHours(24))
            ->get();

        foreach ($orders as $order) {
            //kirim email reminder
            SendOrderReminderJob::dispatch($order);
            \Log::info($order->user->email);

        }
        \Log::info('Reminder command triggered at ' . now());


        $this->info('Reminder emails dispatched for ' . count($orders) . ' unpaid orders.');


    }
}
