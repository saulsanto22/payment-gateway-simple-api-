<?php

namespace App\Console\Commands;

use App\Jobs\SendOrderReminderJob;
use App\Models\Order;
use App\Repositories\OrderRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
     * The OrderRepository instance.
     *
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * Create a new command instance.
     */
    public function __construct(OrderRepository $orderRepository)
    {
        parent::__construct();
        $this->orderRepository = $orderRepository;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Retrieve unpaid orders
            $orders = $this->orderRepository->GetPendingOrder();

            foreach ($orders as $order) {
                // Dispatch email reminder job
                SendOrderReminderJob::dispatch($order);
                Log::info('Reminder email dispatched to: ' . $order->user->email);
            }

            Log::info('Reminder command triggered at ' . now());
            $this->info('Reminder emails dispatched for ' . count($orders) . ' unpaid orders.');
        } catch (\Exception $e) {
            Log::error('Error in ReminderUnpaidOrder command: ' . $e->getMessage());
            $this->error('An error occurred while dispatching reminder emails.');
        }
    }
}
