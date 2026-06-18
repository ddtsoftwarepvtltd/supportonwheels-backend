<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\BookingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Admin\UserController;
use App\Http\Middleware\CheckPermission;
use App\Http\Controllers\API\Admin\{
    ParticipantController,
    WorkerController,
    ProviderController
};

// ===== AUTH =====
Route::prefix('v1/auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('me',            [AuthController::class, 'me']);
        Route::post('logout',       [AuthController::class, 'logout']);
        Route::post('refresh',      [AuthController::class, 'refresh']);
        Route::put('profile',       [AuthController::class, 'updateProfile']);
        Route::get('stats',         [AuthController::class, 'stats']);
    });
});

// ===== SERVICES (public) =====
Route::prefix('v1')->group(function () {
    Route::get('services',      [ServiceController::class, 'index']);
    Route::get('services/{service}', [ServiceController::class, 'show']);
});

// ===== BOOKINGS (auth required) =====
Route::prefix('v1')->middleware('auth:api')->group(function () {
    Route::get('bookings',                  [BookingController::class, 'index']);
    Route::post('bookings',                 [BookingController::class, 'store']);
    Route::patch('bookings/{booking}/cancel', [BookingController::class, 'cancel']);
});

Route::prefix('v1/admin')->name('v1.admin.')->middleware(['auth:api', CheckPermission::class,])->group(function(){
    Route::resources([
        'users' => UserController::class,
        'workers' => WorkerController::class,
        'providers' => ProviderController::class,
        'participants' => ParticipantController::class
    ]);
});
