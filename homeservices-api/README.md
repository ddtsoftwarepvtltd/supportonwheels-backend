# 🏠 HomeServices Platform — Laravel API

Complete RESTful API for Home Services Platform.
Documentation ke hisab se pura system build kiya gaya hai.

---

## 📦 Project Structure

```
app/
├── Http/
│   ├── Controllers/API/
│   │   ├── AuthController.php              # OTP login, Admin 2FA, JWT
│   │   ├── Customer/
│   │   │   ├── ProfileController.php       # Profile CRUD
│   │   │   ├── AddressController.php       # Saved addresses
│   │   │   ├── ServiceController.php       # Browse & search services
│   │   │   ├── BookingController.php       # Booking lifecycle
│   │   │   ├── ReviewController.php        # Ratings & reviews
│   │   │   └── PaymentController.php       # Razorpay + coupon
│   │   ├── Provider/
│   │   │   ├── ProfileController.php       # Profile + KYC docs
│   │   │   ├── AvailabilityController.php  # Schedule, online/offline, location
│   │   │   ├── JobController.php           # Accept/reject/status
│   │   │   └── EarningsController.php      # Earnings + payout
│   │   └── Admin/
│   │       ├── DashboardController.php     # KPIs, map, alerts
│   │       ├── BookingController.php       # Full booking management
│   │       ├── CustomerController.php      # Customer management
│   │       ├── ProviderController.php      # KYC queue, approve/reject
│   │       ├── CategoryController.php      # Service categories
│   │       ├── ServiceController.php       # Services CRUD
│   │       ├── PaymentController.php       # Finance, payouts, refunds
│   │       ├── CouponController.php        # Promotions & coupons
│   │       ├── ReportController.php        # Analytics reports
│   │       └── AdminUserController.php     # Admin user management
│   └── Middleware/
│       ├── RoleMiddleware.php              # Role-based access
│       └── CheckPermission.php            # Spatie permission check (existing)
├── Models/
│   ├── User.php (existing + extended)
│   ├── ProviderProfile.php (existing + extended)
│   ├── CustomerAddress.php
│   ├── ServiceCategory.php
│   ├── Service.php (extended)
│   ├── ServiceSlot.php
│   ├── Booking.php (extended)
│   ├── BookingStatusHistory.php
│   ├── BookingNote.php
│   ├── Payment.php
│   ├── Review.php
│   ├── Coupon.php + CouponUsage.php
│   ├── Payout.php
│   └── OtpCode.php
└── Traits/
    └── ApiResponse.php (existing)
```

---

## 🚀 Setup Steps

### 1. Existing project mein files copy karo

```bash
# Ye files apne existing supportonwheels-backend project mein copy karo
cp -r app/ /path/to/your/project/
cp routes/api.php /path/to/your/project/routes/
cp database/migrations/2026_06_20_*.php /path/to/your/project/database/migrations/
```

### 2. Middleware register karo

`bootstrap/app.php` mein add karo:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role'       => \App\Http\Middleware\RoleMiddleware::class,
        'permission' => \App\Http\Middleware\CheckPermission::class,
    ]);
})
```

### 3. Migrations run karo

```bash
php artisan migrate
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=SuperAdminSeeder
```

### 4. Roles & Permissions update karo

`database/seeders/RolesAndPermissionsSeeder.php` mein ye roles add karo:
- `customer`
- `provider`
- `admin`
- `ops_admin`
- `finance_admin`
- `super_admin`

### 5. Storage link

```bash
php artisan storage:link
```

---

## 🔑 API Authentication

| User Type | Auth Method |
|-----------|------------|
| Customer  | OTP (phone) → JWT |
| Provider  | OTP (phone) → JWT |
| Admin     | Email + Password + 2FA (TOTP) → JWT |

### JWT Token use karo:
```
Authorization: Bearer {token}
```

---

## 📡 Endpoints Summary

### 🔐 Auth (6 endpoints)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /v1/auth/send-otp | OTP bhejo |
| POST | /v1/auth/verify-otp | OTP verify karo |
| POST | /v1/auth/admin/login | Admin login |
| POST | /v1/auth/admin/2fa | 2FA verify |
| POST | /v1/auth/refresh | Token refresh |
| POST | /v1/auth/logout | Logout |

### 👤 Customer (24 endpoints)
- Profile (4)
- Addresses (5)
- Services (6)
- Bookings (7)
- Payments & Coupons (4)

### 🔧 Provider (16 endpoints)
- Profile & KYC (3)
- Availability (4)
- Jobs (9)
- Earnings (6)

### ⚙️ Admin (45 endpoints)
- Dashboard (3)
- Booking Management (7)
- Customer Management (6)
- Provider Management (10)
- Services & Categories (9)
- Finance (7)
- Coupons (6)
- Reports (5)
- Admin Users (6)

**Total: ~91 Endpoints**

---

## 💳 Payment Integration (Razorpay)

```php
// .env mein add karo:
RAZORPAY_KEY=rzp_test_xxxxx
RAZORPAY_SECRET=xxxxx
```

Payment flow:
1. `POST /customer/payments/initiate` → Razorpay order create
2. Frontend se payment complete karo
3. `POST /customer/payments/verify` → Signature verify karo

---

## 📱 WebSocket Events (Laravel Reverb / Pusher)

```
booking:status_update      → Customer ko status change notify
provider:location_update   → Customer ko live location
job:new_request            → Provider ko new job alert
chat:message               → In-app chat
```

---

## 🗄️ Database Tables

| Table | Description |
|-------|-------------|
| users | All users (customer, provider, admin) |
| provider_profiles | Provider KYC, location, earnings |
| participant_profiles | Customer additional info |
| customer_addresses | Saved delivery addresses |
| service_categories | Cleaning, Plumbing, etc. |
| services | Individual service listings |
| service_slots | Available time slots |
| bookings | Booking records |
| booking_status_histories | Status audit trail |
| booking_notes | Admin internal notes |
| payments | Payment records |
| reviews | Customer ratings |
| coupons | Promo codes |
| coupon_usages | Coupon usage tracking |
| payouts | Provider payout records |

---

## 📬 Postman Collection

`HomeServices_Postman_Collection.json` import karo Postman mein.

**Variables set karo:**
- `base_url`: `http://localhost:8000/api`
- `customer_token`: Send OTP + Verify OTP se milega
- `provider_token`: Provider OTP verify se milega
- `admin_token`: Admin Login + 2FA se milega

---

## 🔧 TODO (Future Integration)

- [ ] Twilio SMS OTP
- [ ] Razorpay payment gateway
- [ ] Google Maps Directions API (ETA)
- [ ] Laravel Reverb WebSocket
- [ ] FCM Push Notifications
- [ ] Google2FA TOTP
- [ ] Background check API
- [ ] Support ticket system
- [ ] Export CSV (Laravel Excel)
