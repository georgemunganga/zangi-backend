<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookCheckoutController;
use App\Http\Controllers\Api\V1\ContactMessageController;
use App\Http\Controllers\Api\V1\EventTicketCheckoutController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('/request-otp', [AuthController::class, 'requestOtp']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('/refresh', [AuthController::class, 'refresh']);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    Route::prefix('checkout')->group(function (): void {
        Route::post('/book-orders/online-intent', [BookCheckoutController::class, 'createOnlineIntent']);
        Route::post('/book-orders/cod', [BookCheckoutController::class, 'createCashOnDeliveryOrder']);
        Route::post('/event-tickets/online-intent', [EventTicketCheckoutController::class, 'createOnlineIntent']);
    });

    Route::post('/payments/lenco/intent', [PaymentController::class, 'intent']);
    Route::post('/payments/lenco/verify', [PaymentController::class, 'verify']);
    Route::post('/payments/lenco/webhook', [PaymentController::class, 'webhook']);

    Route::middleware('auth:sanctum')->prefix('portal')->group(function (): void {
        Route::get('/overview', [PortalController::class, 'overview']);
        Route::get('/orders', [PortalController::class, 'orders']);
        Route::get('/orders/{order}', [PortalController::class, 'showOrder']);
        Route::get('/orders/{order}/download', [PortalController::class, 'downloadOrder']);
        Route::get('/tickets', [PortalController::class, 'tickets']);
        Route::get('/tickets/{ticketPurchase}', [PortalController::class, 'showTicket']);
        Route::get('/tickets/{ticketPurchase}/pass', [PortalController::class, 'downloadTicketPass']);
    });

    Route::post('/contact/messages', [ContactMessageController::class, 'store']);
});
