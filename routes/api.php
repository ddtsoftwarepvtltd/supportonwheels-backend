<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Admin\UserController;
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
        Route::get('me',       [AuthController::class, 'me']);
        Route::post('logout',  [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

Route::prefix('v1/admin')->middleware('auth:api')->group(function(){
    Route::resources([
        'users' => UserController::class,
        'workers' => WorkerController::class,
        'providers' => ProviderController::class,
        'participants' => ParticipantController::class
    ]);
});
