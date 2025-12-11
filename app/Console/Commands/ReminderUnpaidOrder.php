<?php

namespace App\Console\Commands;

use App\Jobs\SendOrderReminderJob;
use App\Models\Order;
use App\Repositories\OrderRepository;
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

    protected $orderRepository;

    /**
     * Execute the console command.
     */

    // buat depedensi injection untuk orderRepository
    public function __construct(OrderRepository $orderRepository)
    {
        parent::__construct();
        $this->orderRepository = $orderRepository;

    }

    public function handle()
    {

        // ambil data order yang statusnya unpaid dan kirimkan email reminder
        $orders = $this->orderRepository->GetPendingOrder();

        foreach ($orders as $order) {
            // kirim email reminder
            SendOrderReminderJob::dispatch($order);
            \Log::info($order->user->email);

        }
        \Log::info('Reminder command triggered at ' . now());

        $this->info('Reminder emails dispatched for ' . count($orders) . ' unpaid orders.');

    }
}
