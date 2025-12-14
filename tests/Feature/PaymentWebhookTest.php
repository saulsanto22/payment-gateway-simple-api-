<?php

use App\Enums\OrderStatus;
use App\Jobs\ProcessMidtransWebhook;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class)->group('feature', 'webhook');

beforeEach(function () {
    // Setup user & product untuk setiap test
    $this->user = User::factory()->create();

    $this->product = Product::factory()->create([
        'stock' => 10,
        'price' => 100000,
    ]);
});

describe('Payment Webhook - Midtrans Notification', function () {

    it('webhook menerima notifikasi dan masuk ke queue', function () {
        Queue::fake();

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-TEST-001',
            'total_price' => 100000,
            'status' => OrderStatus::PENDING,
        ]);

        $serverKey = config('midtrans.server_key');
        $orderId = $order->order_number;
        $statusCode = '200';
        $grossAmount = '100000.00';
        $transactionStatus = 'settlement';

        $signatureKey = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => $transactionStatus,
        ];

        $response = $this->postJson('/api/midtrans/webhook', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Webhook received and queued for processing',
            ]);

        // Pastikan job masuk ke queue
        Queue::assertPushed(ProcessMidtransWebhook::class);
    });

    it('webhook dengan signature tidak valid ditolak', function () {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-TEST-002',
            'total_price' => 100000,
            'status' => OrderStatus::PENDING,
        ]);

        $payload = [
            'order_id' => $order->order_number,
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'signature_key' => 'invalid-signature-xxx',
            'transaction_status' => 'settlement',
        ];

        // Job tetap dijalankan, tapi di dalam job akan di-reject
        $this->postJson('/api/midtrans/webhook', $payload);

        // Jalankan job secara sync untuk test
        ProcessMidtransWebhook::dispatchSync($payload);

        // Order status tidak berubah karena signature invalid
        expect($order->fresh()->status)->toBe(OrderStatus::PENDING);
    });

    it('payment success mengubah status menjadi PAID dan stok berkurang', function () {
        // Buat order dengan order item
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-TEST-003',
            'total_price' => 200000,
            'status' => OrderStatus::PENDING,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 100000,
        ]);

        $serverKey = config('midtrans.server_key');
        $orderId = $order->order_number;
        $statusCode = '200';
        $grossAmount = '200000.00';
        $transactionStatus = 'settlement';

        $signatureKey = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => $transactionStatus,
        ];

        // Jalankan job secara sync untuk test
        ProcessMidtransWebhook::dispatchSync($payload);

        // Assert: Status berubah jadi PAID
        expect($order->fresh()->status)->toBe(OrderStatus::PAID);

        // Assert: Stok berkurang (10 - 2 = 8)
        expect($this->product->fresh()->stock)->toBe(8);

        // Assert: Notifikasi tersimpan di log
        $this->assertDatabaseHas('payment_notifications', [
            'order_number' => $order->order_number,
            'transaction_status' => $transactionStatus,
        ]);
    });

    it('payment pending tidak mengubah stok', function () {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-TEST-004',
            'total_price' => 100000,
            'status' => OrderStatus::PENDING,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 3,
            'price' => 100000,
        ]);

        $serverKey = config('midtrans.server_key');
        $orderId = $order->order_number;
        $statusCode = '201';
        $grossAmount = '100000.00';
        $transactionStatus = 'pending';

        $signatureKey = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => $transactionStatus,
        ];

        ProcessMidtransWebhook::dispatchSync($payload);

        // Status tetap PENDING
        expect($order->fresh()->status)->toBe(OrderStatus::PENDING);

        // Stok tidak berubah
        expect($this->product->fresh()->stock)->toBe(10);
    });

    it('payment cancel mengubah status menjadi CANCELLED', function () {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-TEST-005',
            'total_price' => 100000,
            'status' => OrderStatus::PENDING,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => 100000,
        ]);

        $serverKey = config('midtrans.server_key');
        $orderId = $order->order_number;
        $statusCode = '200';
        $grossAmount = '100000.00';
        $transactionStatus = 'cancel';

        $signatureKey = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => $transactionStatus,
        ];

        ProcessMidtransWebhook::dispatchSync($payload);

        // Status menjadi CANCELLED
        expect($order->fresh()->status)->toBe(OrderStatus::CANCELLED);

        // Stok tidak berkurang karena payment cancel
        expect($this->product->fresh()->stock)->toBe(10);
    });

    it('payment expire mengubah status menjadi EXPIRED', function () {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-TEST-006',
            'total_price' => 100000,
            'status' => OrderStatus::PENDING,
        ]);

        $serverKey = config('midtrans.server_key');
        $orderId = $order->order_number;
        $statusCode = '407';
        $grossAmount = '100000.00';
        $transactionStatus = 'expire';

        $signatureKey = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => $transactionStatus,
        ];

        ProcessMidtransWebhook::dispatchSync($payload);

        // Status menjadi EXPIRED
        expect($order->fresh()->status)->toBe(OrderStatus::EXPIRED);
    });

    it('idempotency - status final tidak bisa diubah lagi', function () {
        // Order sudah PAID
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-TEST-007',
            'total_price' => 100000,
            'status' => OrderStatus::PAID,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 100000,
        ]);

        // Coba ubah ke cancel (tidak boleh)
        $serverKey = config('midtrans.server_key');
        $orderId = $order->order_number;
        $statusCode = '200';
        $grossAmount = '100000.00';
        $transactionStatus = 'cancel';

        $signatureKey = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => $transactionStatus,
        ];

        ProcessMidtransWebhook::dispatchSync($payload);

        // Status tetap PAID (tidak berubah)
        expect($order->fresh()->status)->toBe(OrderStatus::PAID);
    });

    it('webhook dengan gross_amount tidak sesuai ditolak', function () {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-TEST-008',
            'total_price' => 100000,
            'status' => OrderStatus::PENDING,
        ]);

        $serverKey = config('midtrans.server_key');
        $orderId = $order->order_number;
        $statusCode = '200';
        $grossAmount = '999999.00'; // Salah! Harusnya 100000.00
        $transactionStatus = 'settlement';

        $signatureKey = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => $transactionStatus,
        ];

        ProcessMidtransWebhook::dispatchSync($payload);

        // Status tidak berubah karena amount mismatch
        expect($order->fresh()->status)->toBe(OrderStatus::PENDING);
    });
});
