# 🚀 Payment Gateway Laravel 12 + Midtrans

Proyek ini adalah contoh implementasi **Payment Gateway** menggunakan **Laravel 12** dan **Midtrans Snap** dengan arsitektur best practice:
- Menggunakan **Repository Pattern** & **Service Layer** untuk pemisahan logika bisnis.
- **Form Request Validation** untuk validasi input.
- **Enum** untuk status order.
- **Resource** untuk response API agar lebih rapi.
- **Cart & Order flow** hingga integrasi Midtrans Snap.

---

## 📦 Fitur
- 🔐 **Auth**: Register & Login user dengan Sanctum.
- 🛒 **Cart**: Tambah, lihat, hapus produk dari cart.
- 📦 **Order**: Checkout cart menjadi order.
- 💳 **Payment**: Integrasi Midtrans Snap untuk pembayaran.

---

## ⚙️ Instalasi

1. Clone repository
   ```bash
   git clone https://github.com/your-username/payment-gateway-laravel12.git
   cd payment-gateway-laravel12
   ```

2. Install dependencies
   ```bash
   composer install
   npm install && npm run build
   ```

3. Salin file `.env`
   ```bash
   cp .env.example .env
   ```

4. Generate key
   ```bash
   php artisan key:generate
   ```

5. Jalankan migrasi & seed
   ```bash
   php artisan migrate --seed
   ```

6. Jalankan server
   ```bash
   php artisan serve
   ```

---

## 🔑 Konfigurasi Midtrans

1. Buat akun di [Midtrans Dashboard](https://dashboard.midtrans.com/).
2. Masuk ke **Sandbox Mode**.
3. Ambil **Server Key** dan **Client Key**.
4. Tambahkan ke file `.env`:
   ```env
   MIDTRANS_SERVER_KEY=SB-Mid-server-xxxx
   MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxx
   MIDTRANS_IS_PRODUCTION=false
   ```

---

## 📡 Endpoint API

### Auth
- `POST /api/register` → Register user baru
- `POST /api/login` → Login user & dapatkan token

### Cart
- `POST /api/cart/add` → Tambah produk ke cart
- `GET /api/cart` → Lihat isi cart
- `DELETE /api/cart/{id}` → Hapus produk dari cart

### Order
- `POST /api/orders/checkout` → Checkout cart → buat order + Snap Token
- `GET /api/orders` → Lihat daftar order user
- `GET /api/orders/{id}` → Lihat detail order

---

## 💳 Alur Payment dengan Midtrans

1. User **daftar & login** → dapatkan token.
2. User **tambah produk** ke cart.
3. User **checkout cart** → backend membuat order & memanggil Midtrans API.
4. Backend mengembalikan **Snap Token** ke frontend.
5. Frontend memanggil `window.snap.pay(token)` (Midtrans Snap.js) untuk membuka UI pembayaran.
6. Midtrans mengirim callback/webhook ke backend → update status order.

---

## 🧪 Postman Collection

File **Postman Collection** tersedia di repo:
```
postman/PaymentGateway.postman_collection.json
```

Import ke Postman untuk langsung coba semua endpoint.

---

## 📝 Catatan
- Gunakan data dummy produk dari seeder.
- Semua pembayaran masih dalam **Sandbox Mode** (testing).
- Ubah `MIDTRANS_IS_PRODUCTION=true` hanya di mode produksi.

---

## 📚 Referensi
- [Laravel 12 Docs](https://laravel.com/docs)
- [Midtrans API Docs](https://docs.midtrans.com/)
