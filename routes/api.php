<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\Customer\AddressController;
use App\Http\Controllers\API\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\API\Customer\BookingController as CustomerBookingController;
use App\Http\Controllers\API\Provider\ProfileController as ProviderProfileController;
use App\Http\Controllers\API\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\API\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\API\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\API\Customer\ReviewController;
use App\Http\Controllers\API\Customer\PaymentController;
use App\Http\Controllers\API\Customer\ServiceController;
use App\Http\Controllers\API\Provider\AvailabilityController;
use App\Http\Controllers\API\Provider\JobController;
use App\Http\Controllers\API\Provider\EarningsController;
use App\Http\Controllers\API\Admin\DashboardController;
use App\Http\Controllers\API\Admin\CustomerController;
use App\Http\Controllers\API\Admin\ProviderController;
use App\Http\Controllers\API\Admin\CategoryController;
use App\Http\Controllers\API\Admin\CouponController;
use App\Http\Controllers\API\Admin\ReportController;
use App\Http\Controllers\API\Admin\AdminUserController;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\API\Admin\ServiceSlotController;

// ── Service Slots ─────────────────────────────────────────────
Route::prefix('services/{service_id}/slots')->group(function () {
    Route::get('/',               [ServiceSlotController::class, 'index']);
    Route::post('/',              [ServiceSlotController::class, 'store']);
    Route::post('bulk-generate',  [ServiceSlotController::class, 'bulkGenerate']);
    Route::delete('bulk-delete',  [ServiceSlotController::class, 'bulkDelete']);
    Route::patch('{slot_id}',     [ServiceSlotController::class, 'update']);
    Route::delete('{slot_id}',    [ServiceSlotController::class, 'destroy']);
});

// ============================================================
// AUTH ROUTES (Public)
// ============================================================
Route::prefix('v1/auth')->group(function () {
    Route::post('send-otp',    [AuthController::class, 'sendOtp']);
    Route::post('verify-otp',  [AuthController::class, 'verifyOtp']);
    Route::post('refresh',     [AuthController::class, 'refresh']);
    Route::post('admin/login', [AuthController::class, 'adminLogin']);
    Route::post('admin/2fa',   [AuthController::class, 'verify2FA']);
    Route::middleware('auth:api')->group(function () {
        Route::post('logout',  [AuthController::class, 'logout']);
        Route::get('me',       [AuthController::class, 'me']);
    });
});

// ============================================================
// CUSTOMER ROUTES
// ============================================================
Route::prefix('v1/customer')->middleware(['auth:api', RoleMiddleware::class . ':customer'])->group(function () {

    // Profile
    Route::get('profile',           [CustomerProfileController::class, 'show']);
    Route::put('profile',           [CustomerProfileController::class, 'update']);
    Route::post('profile/photo',    [CustomerProfileController::class, 'uploadPhoto']);
    Route::delete('account',        [CustomerProfileController::class, 'deleteAccount']);

    // Addresses
    Route::get('addresses',         [AddressController::class, 'index']);
    Route::post('addresses',        [AddressController::class, 'store']);
    Route::put('addresses/{id}',    [AddressController::class, 'update']);
    Route::delete('addresses/{id}', [AddressController::class, 'destroy']);
    Route::patch('addresses/{id}/default', [AddressController::class, 'setDefault']);

    // Services & Discovery
    Route::get('services/categories',      [ServiceController::class, 'categories']);
    Route::get('services',                 [ServiceController::class, 'index']);
    Route::get('services/search',          [ServiceController::class, 'search']);
    Route::get('services/{id}',            [ServiceController::class, 'show']);
    Route::get('services/{id}/slots',      [ServiceController::class, 'availableSlots']);
    Route::get('services/{id}/providers',  [ServiceController::class, 'providers']);

    // Bookings
    Route::get('bookings',                         [CustomerBookingController::class, 'index']);
    Route::post('bookings',                        [CustomerBookingController::class, 'store']);
    Route::get('bookings/{id}',                    [CustomerBookingController::class, 'show']);
    Route::post('bookings/{id}/cancel',            [CustomerBookingController::class, 'cancel']);
    Route::put('bookings/{id}/reschedule',         [CustomerBookingController::class, 'reschedule']);
    Route::get('bookings/{id}/track',              [CustomerBookingController::class, 'track']);
    Route::post('bookings/{id}/review',            [ReviewController::class, 'store']);

    // Payments
    Route::post('payments/initiate',   [PaymentController::class, 'initiate']);
    Route::post('payments/verify',     [PaymentController::class, 'verify']);
    Route::get('payments/history',     [PaymentController::class, 'history']);

    // Coupons
    Route::post('coupons/validate',    [PaymentController::class, 'validateCoupon']);
});

// ============================================================
// PROVIDER ROUTES
// ============================================================
Route::prefix('v1/provider')->middleware(['auth:api', RoleMiddleware::class . ':provider'])->group(function () {

    // Profile
    Route::get('profile',        [ProviderProfileController::class, 'show']);
    Route::put('profile',        [ProviderProfileController::class, 'update']);
    Route::post('profile/photo', [ProviderProfileController::class, 'uploadPhoto']);
    Route::post('kyc/documents', [ProviderProfileController::class, 'uploadKyc']);

    // Availability & Status
    Route::get('availability',   [AvailabilityController::class, 'show']);
    Route::put('availability',   [AvailabilityController::class, 'update']);
    Route::put('status',         [AvailabilityController::class, 'toggleStatus']);
    Route::post('location',      [AvailabilityController::class, 'updateLocation']);

    // Jobs
    Route::get('jobs/active',          [JobController::class, 'active']);
    Route::get('jobs/history',         [JobController::class, 'history']);
    Route::get('jobs/{id}',            [JobController::class, 'show']);
    Route::post('jobs/{id}/accept',    [JobController::class, 'accept']);
    Route::post('jobs/{id}/reject',    [JobController::class, 'reject']);
    Route::put('jobs/{id}/status',     [JobController::class, 'updateStatus']);
    Route::post('jobs/{id}/issue',     [JobController::class, 'raiseIssue']);

    // Earnings & Payouts
    Route::get('earnings',             [EarningsController::class, 'summary']);
    Route::get('earnings/history',     [EarningsController::class, 'history']);
    Route::post('payout/request',      [EarningsController::class, 'requestInstantPayout']);
    Route::get('payout/history',       [EarningsController::class, 'payoutHistory']);
});

// ============================================================
// ADMIN ROUTES
// ============================================================
Route::prefix('v1/admin')->middleware(['auth:api', RoleMiddleware::class . ':admin,super_admin,ops_admin,finance_admin', CheckPermission::class])->group(function () {

    // Dashboard
    Route::get('dashboard',        [DashboardController::class, 'index']);
    Route::get('dashboard/map',    [DashboardController::class, 'mapData']);
    Route::get('dashboard/alerts', [DashboardController::class, 'alerts']);

    // Booking Management
    Route::get('bookings',                    [AdminBookingController::class, 'index']);
    Route::get('bookings/{id}',               [AdminBookingController::class, 'show']);
    Route::post('bookings/{id}/reassign',     [AdminBookingController::class, 'reassign']);
    Route::post('bookings/{id}/cancel',       [AdminBookingController::class, 'forceCancel']);
    Route::post('bookings/{id}/note',         [AdminBookingController::class, 'addNote']);
    Route::get('bookings/{id}/chat',          [AdminBookingController::class, 'chatTranscript']);
    Route::get('bookings/export',             [AdminBookingController::class, 'export']);

    // Customer Management
    Route::get('customers',                   [CustomerController::class, 'index']);
    Route::get('customers/{id}',              [CustomerController::class, 'show']);
    Route::patch('customers/{id}/suspend',    [CustomerController::class, 'suspend']);
    Route::patch('customers/{id}/activate',   [CustomerController::class, 'activate']);
    Route::post('customers/{id}/credit',      [CustomerController::class, 'addCredit']);
    Route::get('customers/{id}/tickets',      [CustomerController::class, 'tickets']);

    // Provider Management
    Route::get('providers',                         [ProviderController::class, 'index']);
    Route::get('providers/kyc-queue',               [ProviderController::class, 'kycQueue']);  // static route PEHLE
    Route::get('providers/{id}',                    [ProviderController::class, 'show']);
    Route::patch('providers/{id}/approve',          [ProviderController::class, 'approve']);
    Route::patch('providers/{id}/reject',           [ProviderController::class, 'reject']);
    Route::patch('providers/{id}/suspend',          [ProviderController::class, 'suspend']);
    Route::patch('providers/{id}/activate',         [ProviderController::class, 'activate']);
    Route::delete('providers/{id}',                 [ProviderController::class, 'destroy']);
    Route::post('providers/{id}/payout-override',   [ProviderController::class, 'manualPayout']);
    Route::patch('providers/{id}/featured',         [ProviderController::class, 'toggleFeatured']);

    // Service & Category Management
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('services',   AdminServiceController::class);
    Route::patch('services/{id}/toggle', [AdminServiceController::class, 'toggle']);

    // Finance & Payments
    Route::get('finance/dashboard',          [AdminPaymentController::class, 'financeDashboard']);
    Route::get('finance/payouts/pending',    [AdminPaymentController::class, 'pendingPayouts']);
    Route::post('finance/payouts/batch',     [AdminPaymentController::class, 'batchPayout']);
    Route::post('finance/payouts/{id}',      [AdminPaymentController::class, 'manualPayout']);
    Route::get('finance/refunds',            [AdminPaymentController::class, 'refundQueue']);
    Route::post('finance/refunds',           [AdminPaymentController::class, 'processRefund']);
    Route::get('finance/revenue',            [AdminPaymentController::class, 'revenueStats']);

    // Coupons & Promotions
    Route::apiResource('coupons', CouponController::class);
    Route::get('coupons/{id}/stats', [CouponController::class, 'stats']);

    // Reports & Analytics
    Route::get('reports/bookings',       [ReportController::class, 'bookingFunnel']);
    Route::get('reports/providers',      [ReportController::class, 'providerLeaderboard']);
    Route::get('reports/customers',      [ReportController::class, 'customerRetention']);
    Route::get('reports/city',           [ReportController::class, 'cityPerformance']);
    Route::get('reports/revenue',        [ReportController::class, 'revenueReport']);

    // Admin User Management (Super Admin only)
    Route::apiResource('admin-users', AdminUserController::class);
    Route::post('admin-users/{id}/2fa/reset', [AdminUserController::class, 'reset2FA']);
});
