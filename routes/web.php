<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CuttingOptimizationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\HomeController as CustomerHomeController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\ProductController as CustomerProductController;
use App\Http\Controllers\Customer\ShippingController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [CustomerHomeController::class, 'index'])->name('home');
Route::get('/products', [CustomerProductController::class, 'catalog'])->name('catalog');
Route::get('/katalog', fn() => redirect()->route('catalog'));
Route::get('/products/{product}', [CustomerProductController::class, 'show'])->name('product.show');
Route::post('/payments/midtrans/notification', [PaymentController::class, 'midtransNotification'])->name('payments.midtrans.notification');

/*
|--------------------------------------------------------------------------
| Guest Routes (only accessible when NOT logged in)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    // Register
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    // Forgot Password
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');

    // Reset Password
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // User Pages
    Route::get('/home', [CustomerHomeController::class, 'index']);

    Route::get('/cart', [CartController::class, 'cart'])->name('cart');
    Route::post('/cart/{product}', [CartController::class, 'addToCart'])->name('cart.add');
    Route::patch('/cart/{cartKey}', [CartController::class, 'updateCart'])->name('cart.update');
    Route::delete('/cart/{cartKey}', [CartController::class, 'removeCartItem'])->name('cart.remove');
    Route::get('/keranjang', fn() => redirect()->route('cart'));

    Route::get('/checkout', [CheckoutController::class, 'checkout'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'storeCheckout'])->name('checkout.store');

    // Shipping API (Komerce)
    Route::get('/shipping/provinces', [ShippingController::class, 'shippingProvinces'])->name('shipping.provinces');
    Route::get('/shipping/cities', [ShippingController::class, 'shippingCities'])->name('shipping.cities');
    Route::get('/shipping/search-destination', [ShippingController::class, 'shippingSearchDestination'])->name('shipping.search-destination');
    Route::post('/shipping/calculate', [ShippingController::class, 'shippingCalculate'])->name('shipping.calculate');

    Route::get('/orders', [CustomerOrderController::class, 'ordersIndex'])->name('orders.index');
    Route::get('/orders/{order}', [CustomerOrderController::class, 'showOrder'])->name('orders.show');
    Route::patch('/orders/{order}/cancel', [CustomerOrderController::class, 'cancelOrder'])->name('orders.cancel');
    Route::get('/orders/{order}/snap-token', [CustomerOrderController::class, 'snapToken'])->name('orders.snap-token');
    Route::get('/orders/{order}/sync-midtrans-status', [CustomerOrderController::class, 'syncMidtransStatus'])->name('orders.sync-midtrans-status');
    Route::get('/riwayat-pesanan', fn() => redirect()->route('orders.index'));

    Route::get('/profile', [ProfileController::class, 'profile'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/profil', fn() => redirect()->route('profile'));
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Products
    Route::resource('products', ProductController::class)->except(['show']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::patch('/orders/{order}/confirm-payment', [OrderController::class, 'confirmPayment'])->name('orders.confirm-payment');

    // Cutting Optimization
    Route::get('/cutting-optimization', [CuttingOptimizationController::class, 'index'])->name('cutting-optimization.index');
    Route::get('/cutting-optimization/export', [CuttingOptimizationController::class, 'exportCsv'])->name('cutting-optimization.export');

    // Users
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserManagementController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])->name('users.toggle-active');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export-excel');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::patch('/settings/profile', [SettingController::class, 'updateProfile'])->name('settings.update-profile');
    Route::patch('/settings/password', [SettingController::class, 'updatePassword'])->name('settings.update-password');
    Route::patch('/settings/store', [SettingController::class, 'updateStore'])->name('settings.update-store');
});
