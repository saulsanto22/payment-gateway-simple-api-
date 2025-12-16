# 📖 PHASE 6: API Documentation dengan Swagger

## 🎯 Apa itu API Documentation?

### **Analogi Sederhana: Menu Restoran**

**Bayangkan kamu punya Restoran (API):**

**❌ Tanpa Menu (No Documentation):**
- Customer: "Ada makanan apa aja?"
- Waiter harus jelasin satu-satu (capek!)
- Customer lupa detail (harga, ingredient, porsi)
- Banyak pertanyaan berulang
- Customer bingung cara pesan

**✅ Dengan Menu (Documentation):**
- Customer langsung baca sendiri
- Jelas: nama, harga, ingredient, porsi
- Foto makanan (contoh response)
- Cara pesan (endpoint & method)
- Self-service!

**API Documentation = Menu Restoran untuk Developer!**

---

## 🤔 Kenapa Perlu API Documentation?

### **Real Scenario: Frontend Developer Join Project**

**Tanpa Dokumentasi:**
```
Frontend Dev: "Endpoint login apa ya?"
Backend Dev: "POST /api/auth/login"

Frontend Dev: "Body nya apa aja?"
Backend Dev: "email sama password"

Frontend Dev: "Response nya gimana?"
Backend Dev: "Cek code-nya deh..."

Frontend Dev: "Error 422 itu kenapa?"
Backend Dev: *buka code lagi* "Oh validation error..."

(Repeat 50x untuk semua endpoint...) 😫
```

**Dengan Dokumentasi (Swagger):**
```
Frontend Dev: Buka https://your-api.com/api/documentation
├─> Lihat semua endpoint
├─> Baca request/response example
├─> Try-it-out langsung di browser!
└─> Kerja independent, no need ask backend! 🚀
```

**Benefit:**
- ✅ Frontend independent (no bottleneck!)
- ✅ Onboarding cepat (new dev langsung paham)
- ✅ Testing mudah (built-in API tester)
- ✅ Portfolio profesional
- ✅ Dokumentasi selalu update (from code!)

---

## 📊 Swagger/OpenAPI - Industry Standard

### **Apa itu Swagger?**

**Swagger = UI untuk tampilkan dokumentasi API**
- Beautiful interface
- Interactive testing
- Standard format (OpenAPI)
- Used by: Google, Microsoft, Amazon, dll

### **Apa itu OpenAPI?**

**OpenAPI = Format/Standard untuk define API**
- JSON/YAML format
- Machine-readable (tools bisa parse)
- Human-readable (developer bisa baca)

**Hubungan:**
```
Your Laravel Code (Controllers)
    ↓
Swagger Annotations (comments di code)
    ↓
OpenAPI Spec (JSON/YAML generated)
    ↓
Swagger UI (Beautiful interactive docs)
```

---

## 🎬 Preview: Apa yang Akan Kita Buat

### **Swagger UI Dashboard:**

```
╔═══════════════════════════════════════════════════════╗
║  Laravel Shop API Documentation                       ║
║  Version: 1.0.0                                      ║
╠═══════════════════════════════════════════════════════╣
║                                                       ║
║  🔐 Authentication                                    ║
║  ├─ POST   /api/auth/register    Register User       ║
║  ├─ POST   /api/auth/login       Login User          ║
║  └─ POST   /api/auth/logout      Logout User         ║
║                                                       ║
║  🛒 Cart Management                                   ║
║  ├─ GET    /api/cart             Get Cart Items      ║
║  ├─ POST   /api/cart/{product}   Add to Cart         ║
║  ├─ PUT    /api/cart/{product}   Update Quantity     ║
║  ├─ DELETE /api/cart/{product}   Remove Item         ║
║  └─ DELETE /api/cart/clear       Clear Cart          ║
║                                                       ║
║  📦 Order Management                                  ║
║  ├─ POST   /api/orders/checkout  Checkout Cart       ║
║  └─ GET    /api/orders           Order History       ║
║                                                       ║
║  🔔 Webhook                                           ║
║  └─ POST   /api/midtrans/webhook Payment Callback    ║
║                                                       ║
║  👤 Admin (Products)                                  ║
║  ├─ GET    /api/admin/products   List Products       ║
║  ├─ POST   /api/admin/products   Create Product      ║
║  └─ ...                                              ║
╚═══════════════════════════════════════════════════════╝
```

**Setiap endpoint bisa:**
- ✅ Expand detail (parameters, body, response)
- ✅ Try-it-out (execute langsung!)
- ✅ Copy cURL command
- ✅ Download response

---

## 🔍 Anatomy of Swagger Documentation

### **Contoh Endpoint: POST /api/auth/login**

**Yang Terdokumentasi:**

#### 1. **Basic Info:**
- Method: POST
- Path: /api/auth/login
- Description: "Login user dan dapatkan JWT token"
- Tags: Authentication

#### 2. **Request Body:**
```json
{
  "email": "string (required, email format)",
  "password": "string (required, min 8 chars)"
}
```

#### 3. **Response Success (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

#### 4. **Response Error (422):**
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

#### 5. **Try-it-out:**
- Input field untuk test
- Execute button
- Real response dari API kamu!

---

## 🛠️ Tools: Laravel Swagger Options

### **Option 1: L5-Swagger (Recommended!)**

**Package:** `darkaonline/l5-swagger`

**Pros:**
- ✅ Most popular (5k+ stars)
- ✅ Easy setup
- ✅ Good documentation
- ✅ Active maintenance
- ✅ Swagger UI included

**Cons:**
- ⚠️ Perlu banyak annotations (tapi worth it!)

**Use Case:** Project kamu (Perfect!)

---

### **Option 2: Scramble**

**Package:** `dedoc/scramble`

**Pros:**
- ✅ Auto-generate (minimal annotations!)
- ✅ Modern UI
- ✅ Fast setup

**Cons:**
- ⚠️ Kurang customizable
- ⚠️ Masih baru (less mature)

**Use Case:** Quick prototype, simple API

---

### **Pilihan Kita: L5-Swagger**

**Why?**
1. Industry standard
2. Full control (detail documentation)
3. Better untuk portfolio
4. Lebih flexible
5. Documentation jadi source of truth

---

## 📝 Swagger Annotations - Sneak Peek

### **Before (No Documentation):**

```php
public function login(LoginRequest $request)
{
    $credentials = $request->only('email', 'password');
    
    if (!$token = auth()->attempt($credentials)) {
        return ApiResponse::error('Invalid credentials', 401);
    }
    
    return ApiResponse::success([
        'token' => $token,
        'user' => auth()->user(),
    ], 'Login successful');
}
```

---

### **After (With Swagger Annotations):**

```php
/**
 * @OA\Post(
 *     path="/api/auth/login",
 *     tags={"Authentication"},
 *     summary="Login user",
 *     description="Login dengan email & password, return JWT token",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email", "password"},
 *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *             @OA\Property(property="password", type="string", format="password", example="password123")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Login successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Login successful"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
 *                 @OA\Property(property="user", type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="John Doe"),
 *                     @OA\Property(property="email", type="string", example="john@example.com")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Invalid credentials"
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     )
 * )
 */
public function login(LoginRequest $request)
{
    // ... same code
}
```

**Kelihatan panjang?** Tenang, ini **one-time effort**, hasil nya **lifetime value**! 🚀

---

## 🎯 What We'll Document

### **Endpoints di Project Ini:**

**✅ Will Document (Priority):**
1. **Authentication** (Register, Login, Logout)
2. **Cart** (CRUD operations)
3. **Orders** (Checkout, History)
4. **Webhook** (Midtrans callback)

**⏳ Optional (Bonus):**
5. **Admin - Products** (CRUD)
6. **Admin - Users** (Management)

---

## 🎓 Key Concepts Sebelum Mulai

### **1. Tags = Grouping**
```php
tags={"Authentication"}  // Endpoint masuk group Authentication
tags={"Cart"}           // Endpoint masuk group Cart
```

### **2. Parameters:**
- **Path:** `/cart/{product}` → product di URL
- **Query:** `/orders?status=pending` → status di query string
- **Body:** JSON body untuk POST/PUT

### **3. Security:**
```php
@OA\SecurityScheme(
    securityScheme="bearerAuth",
    type="http",
    scheme="bearer",
    bearerFormat="JWT"
)
```

Untuk endpoint yang perlu authentication (Bearer token)

### **4. Models/Schemas:**
Reusable response structures:
```php
@OA\Schema(
    schema="User",
    @OA\Property(property="id", type="integer"),
    @OA\Property(property="name", type="string"),
    @OA\Property(property="email", type="string")
)
```

---

## 🚀 Next Steps (Implementasi):

1. ✅ Install L5-Swagger package
2. ✅ Setup configuration
3. ✅ Add annotations ke controllers
4. ✅ Generate documentation
5. ✅ Test interactive docs
6. ✅ Customize & beautify

**Siap mulai implementasi?** Mari kita install dulu! 😊

---

## 💡 Fun Fact:

**Dengan Swagger:**
- Frontend dev bisa kerja parallel (no blocking!)
- Mobile dev bisa test endpoint without backend running
- QA bisa test API manual (no Postman import)
- Dokumentasi selalu sync dengan code (auto-generate!)

**ROI (Return on Investment):**
- Setup: 2-3 jam
- Maintenance: 5 menit per endpoint baru
- Time saved: Puluhan jam (no need explain API repeatedly!)

**Worth it banget!** 🎉
