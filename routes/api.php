<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\V1\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Api\V1\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Api\V1\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Api\V1\Admin\OverviewController as AdminOverviewController;
use App\Http\Controllers\Api\V1\Admin\EventController as AdminEventController;
use App\Http\Controllers\Api\V1\Admin\ManualSalesController as AdminManualSalesController;
use App\Http\Controllers\Api\V1\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Api\V1\Admin\SellerController as AdminSellerController;
use App\Http\Controllers\Api\V1\Seller\AuthController as SellerAuthController;
use App\Http\Controllers\Api\V1\Seller\SellerController;
use App\Http\Controllers\Api\V1\BookCheckoutController;
use App\Http\Controllers\Api\V1\ContactMessageController;
use App\Http\Controllers\Api\V1\EventTicketCheckoutController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('/request-otp', [AuthController::class, 'requestOtp'])
            ->middleware('throttle:portal-otp-request');
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:portal-register');
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
            ->middleware('throttle:portal-otp-verify');
        Route::post('/refresh', [AuthController::class, 'refresh'])
            ->middleware('throttle:portal-token-refresh');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    Route::prefix('checkout')->middleware('throttle:public-checkout')->group(function (): void {
        Route::post('/book-orders/online-intent', [BookCheckoutController::class, 'createOnlineIntent']);
        Route::post('/book-orders/cod', [BookCheckoutController::class, 'createCashOnDeliveryOrder']);
        Route::post('/event-tickets/online-intent', [EventTicketCheckoutController::class, 'createOnlineIntent']);
    });

    Route::post('/payments/lenco/intent', [PaymentController::class, 'intent'])
        ->middleware('throttle:payment-intents');
    Route::post('/payments/lenco/verify', [PaymentController::class, 'verify'])
        ->middleware('throttle:payment-verify');
    Route::post('/payments/lenco/webhook', [PaymentController::class, 'webhook'])
        ->middleware('throttle:lenco-webhook');

    Route::middleware('auth:sanctum')->prefix('portal')->group(function (): void {
        Route::get('/overview', [PortalController::class, 'overview']);
        Route::get('/orders', [PortalController::class, 'orders']);
        Route::get('/orders/{order}', [PortalController::class, 'showOrder']);
        Route::get('/orders/{order}/download', [PortalController::class, 'downloadOrder']);
        Route::get('/tickets', [PortalController::class, 'tickets']);
        Route::get('/tickets/{ticketPurchase}', [PortalController::class, 'showTicket']);
        Route::get('/tickets/{ticketPurchase}/pass', [PortalController::class, 'downloadTicketPass']);
    });

    Route::prefix('admin')->group(function (): void {
        Route::prefix('auth')->group(function (): void {
            Route::post('/login', [AdminAuthController::class, 'login'])
                ->middleware('throttle:admin-login');

            Route::middleware('auth:admin')->group(function (): void {
                Route::post('/logout', [AdminAuthController::class, 'logout']);
                Route::get('/me', [AdminAuthController::class, 'me']);
                Route::post('/change-password', [AdminAuthController::class, 'changePassword']);
            });
        });

        Route::middleware('auth:admin')->group(function (): void {
            Route::get('/overview', AdminOverviewController::class);
            Route::get('/events', [AdminEventController::class, 'index']);
            Route::post('/manual-sales', [AdminManualSalesController::class, 'store']);
            Route::post('/tickets/validate', [AdminTicketController::class, 'validateCode']);
            Route::get('/tickets', [AdminTicketController::class, 'index']);
            Route::get('/tickets/{ticketPurchase}', [AdminTicketController::class, 'show']);
            Route::get('/tickets/{ticketPurchase}/download', [AdminTicketController::class, 'download']);
            Route::post('/tickets/{ticketPurchase}/mark-used', [AdminTicketController::class, 'markUsed']);
            Route::post('/tickets/{ticketPurchase}/void', [AdminTicketController::class, 'voidTicket']);
            Route::post('/tickets/{ticketPurchase}/reissue', [AdminTicketController::class, 'reissue']);
            Route::post('/tickets/{ticketPurchase}/resend', [AdminTicketController::class, 'resend']);
            Route::get('/orders', [AdminOrderController::class, 'index']);
            Route::get('/orders/{record}', [AdminOrderController::class, 'show']);
            Route::get('/orders/{record}/invoice', [AdminOrderController::class, 'invoice']);
            Route::post('/orders/{record}/status', [AdminOrderController::class, 'updateStatus']);
            Route::post('/orders/{record}/confirm-payment', [AdminOrderController::class, 'confirmPayment']);
            Route::post('/orders/{record}/resend', [AdminOrderController::class, 'resend']);
            Route::post('/orders/{record}/refund', [AdminOrderController::class, 'refund']);
            Route::post('/orders/{record}/cancel', [AdminOrderController::class, 'cancel']);
            Route::get('/customers', [AdminCustomerController::class, 'index']);
            Route::get('/customers/{customer}', [AdminCustomerController::class, 'show']);
            Route::get('/contact-messages', [AdminContactMessageController::class, 'index']);
            Route::get('/contact-messages/{contactMessage}', [AdminContactMessageController::class, 'show']);
            Route::post('/contact-messages/{contactMessage}/reply', [AdminContactMessageController::class, 'reply']);
            Route::post('/contact-messages/{contactMessage}/status', [AdminContactMessageController::class, 'updateStatus']);
            Route::get('/payments', [AdminPaymentController::class, 'index']);
            Route::get('/payments/{paymentIntent}', [AdminPaymentController::class, 'show']);
            Route::post('/payments/{paymentIntent}/reconcile', [AdminPaymentController::class, 'reconcile']);
            Route::post('/payments/{paymentIntent}/mark-failed', [AdminPaymentController::class, 'markFailed']);
            Route::post('/payments/{paymentIntent}/refund', [AdminPaymentController::class, 'refund']);
            Route::post('/payments/{paymentIntent}/note', [AdminPaymentController::class, 'attachNote']);
            Route::get('/reports/summary', [AdminReportController::class, 'summary']);
            Route::get('/reports/export', [AdminReportController::class, 'export']);

            // Seller Management
            Route::prefix('sellers')->group(function (): void {
                Route::get('/', [AdminSellerController::class, 'index']);
                Route::get('/stats', [AdminSellerController::class, 'stats']);
                Route::get('/{seller}', [AdminSellerController::class, 'show']);
                Route::post('/', [AdminSellerController::class, 'store']);
                Route::put('/{seller}', [AdminSellerController::class, 'update']);
                Route::delete('/{seller}', [AdminSellerController::class, 'destroy']);
                Route::post('/{seller}/reset-pin', [AdminSellerController::class, 'resetPin']);
            });
        });
    });

    Route::prefix('seller')->group(function (): void {
        Route::prefix('auth')->group(function (): void {
            Route::post('/login', [SellerAuthController::class, 'login'])
                ->middleware('throttle:portal-otp-request');

            Route::middleware('auth:sanctum')->group(function (): void {
                Route::post('/logout', [SellerAuthController::class, 'logout']);
                Route::get('/me', [SellerAuthController::class, 'me']);
            });
        });

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/dashboard', [SellerController::class, 'dashboard']);
            Route::get('/sales/recent', [SellerController::class, 'recentSales']);
            Route::get('/sales', [SellerController::class, 'sales']);
            Route::get('/sales/{id}', [SellerController::class, 'showSale']);
            Route::post('/sales', [SellerController::class, 'storeSale']);
            Route::patch('/sales/{id}/sync', [SellerController::class, 'markSynced']);
            Route::post('/sales/bulk-sync', [SellerController::class, 'bulkSync']);
            Route::get('/ticket-types', [SellerController::class, 'ticketTypes']);
            Route::get('/current-round', [SellerController::class, 'currentRound']);
            Route::get('/events/active', [SellerController::class, 'activeEvent']);
            Route::get('/events/{id}', [SellerController::class, 'event']);
            Route::post('/sales/{id}/email/resend', [SellerController::class, 'resendEmail']);
        });
    });

    Route::post('/contact/messages', [ContactMessageController::class, 'store']);
});
