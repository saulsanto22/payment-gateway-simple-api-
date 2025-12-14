<?php

use App\Enums\OrderStatus;
use App\Jobs\ProcessMidtransWebhook;
use App\Jobs\SendOrderReminderJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class)->group('feature', 'jobs');

describe('Job Configuration & Best Practices', function () {
    
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->product = Product::factory()->create([
            'stock' => 10,
            'price' => 100000,
        ]);
    });

    it('ProcessMidtransWebhook memiliki konfigurasi yang benar', function () {
        $payload = [
            'order_id' => 'ORD-TEST-001',
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'signature_key' => 'test-signature',
            'transaction_status' => 'settlement',
        ];

        $job = new ProcessMidtransWebhook($payload);

        // Assert: Job properties sesuai best practice
        expect($job->tries)->toBe(3) // 3x retry untuk external API
            ->and($job->timeout)->toBe(90) // 90s timeout
            ->and($job->backoff)->toBe([10, 30, 60]) // Exponential backoff
            ->and($job->maxExceptions)->toBe(3);
            
        // Queue name di-set via onQueue() saat dispatch, bukan via property
    });

    it('SendOrderReminderJob menggunakan ID bukan Model', function () {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_price' => 100000,
            'status' => OrderStatus::PENDING,
        ]);

        // BEST PRACTICE: Pass ID, bukan Model
        $job = new SendOrderReminderJob($order->id);

        // Assert: Job configuration
        expect($job->tries)->toBe(5) // 5x retry untuk email
            ->and($job->timeout)->toBe(60) // 60s timeout
            ->and($job->backoff)->toBe([60, 120, 300, 600]); // Longer backoff untuk email
            
        // Queue name di-set via onQueue() saat dispatch, bukan via property
    });

    it('SendOrderReminderJob skip jika order sudah tidak pending', function () {
        Mail::fake();

        // Order sudah PAID
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_price' => 100000,
            'status' => OrderStatus::PAID,
        ]);

        // Dispatch job
        SendOrderReminderJob::dispatchSync($order->id);

        // Email TIDAK dikirim karena order sudah paid
        Mail::assertNothingSent();
    });

    it('SendOrderReminderJob skip jika order tidak ditemukan', function () {
        Mail::fake();

        // Order ID yang tidak ada
        SendOrderReminderJob::dispatchSync(99999);

        // Email TIDAK dikirim
        Mail::assertNothingSent();
    });

    it('webhook job dapat di-tag untuk monitoring', function () {
        $payload = [
            'order_id' => 'ORD-TEST-123',
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'signature_key' => 'test',
            'transaction_status' => 'settlement',
        ];

        $job = new ProcessMidtransWebhook($payload);
        $tags = $job->tags();

        // Assert: Tags untuk Horizon monitoring
        expect($tags)->toContain('webhook')
            ->and($tags)->toContain('midtrans')
            ->and($tags)->toContain('order:ORD-TEST-123');
    });

    it('reminder job dapat di-tag untuk monitoring', function () {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::PENDING,
        ]);

        $job = new SendOrderReminderJob($order->id);
        $tags = $job->tags();

        // Assert: Tags untuk monitoring
        expect($tags)->toContain('email')
            ->and($tags)->toContain('reminder')
            ->and($tags)->toContain('order:' . $order->id);
    });
});

describe('Job Queue Priority', function () {
    
    it('webhook job masuk ke queue webhooks (high priority)', function () {
        Queue::fake();

        $payload = [
            'order_id' => 'ORD-TEST',
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'signature_key' => 'test',
            'transaction_status' => 'settlement',
        ];

        // Dispatch dengan onQueue() untuk set priority
        ProcessMidtransWebhook::dispatch($payload)->onQueue('webhooks');

        // Assert: Job dispatched ke queue 'webhooks'
        Queue::assertPushedOn('webhooks', ProcessMidtransWebhook::class);
    });

    it('email job masuk ke queue emails (low priority)', function () {
        Queue::fake();

        $order = Order::factory()->create([
            'user_id' => User::factory()->create()->id,
            'status' => OrderStatus::PENDING,
        ]);

        // Dispatch dengan onQueue() untuk set priority
        SendOrderReminderJob::dispatch($order->id)->onQueue('emails');

        // Assert: Job dispatched ke queue 'emails'
        Queue::assertPushedOn('emails', SendOrderReminderJob::class);
    });
});

describe('Job Retry & Backoff', function () {
    
    it('webhook job retry dengan exponential backoff', function () {
        $payload = [
            'order_id' => 'ORD-TEST',
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'signature_key' => 'test',
            'transaction_status' => 'settlement',
        ];

        $job = new ProcessMidtransWebhook($payload);

        // Assert: Backoff configuration
        // Retry 1: tunggu 10 detik
        // Retry 2: tunggu 30 detik
        // Retry 3: tunggu 60 detik
        expect($job->backoff)->toBe([10, 30, 60])
            ->and($job->tries)->toBe(3);
    });

    it('email job retry dengan longer backoff', function () {
        $job = new SendOrderReminderJob(1);

        // Assert: Email butuh backoff lebih lama
        // Retry 1: tunggu 1 menit
        // Retry 2: tunggu 2 menit
        // Retry 3: tunggu 5 menit
        // Retry 4: tunggu 10 menit
        expect($job->backoff)->toBe([60, 120, 300, 600])
            ->and($job->tries)->toBe(5);
    });
});
