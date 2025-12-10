@component('mail::message')
# Reminder: Your Order is Still Unpaid
Hallo, {{ $order->user->name }}!,

Order anda masih pending sejak {{ $order->created_at->format('d M Y H:i') }}. Silakan selesaikan pembayaran anda untuk
memproses order ini.

@component('mail::button', ['url' => $order->redirect_url])

Selesaikan Pembayaran


@endcomponent

Terimakasih,<br>
{{ config('app.name') }}
@endcomponent