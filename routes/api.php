<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CartItemController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// AUTH (for both admin & cashier)
Route::post('/register', [AuthController::class, 'register']); // optional for cashier
Route::post('/login', [AuthController::class, 'login']);

// ----------------------
// CASHIER ROUTES
// ----------------------
Route::middleware(['auth:api', 'role:cashier'])->prefix('cashier')->group(function () {
    // CART & CART ITEMS
    Route::get('carts', [CartController::class, 'index']); // view carts
    Route::post('carts', [CartController::class, 'store']); // create cart
    Route::get('carts/{cart}', [CartController::class, 'show']); // view single cart

    Route::post('cart-items', [CartItemController::class, 'store']); // add item
    Route::put('cart-items/{cartItem}', [CartItemController::class, 'update']); // update item
    Route::delete('cart-items/{cartItem}', [CartItemController::class, 'destroy']); // remove item

    // CHECKOUT
    Route::post('carts/{cart}/checkout', [CartController::class, 'checkout']);

    // PAYMENTS
    Route::post('payments', [PaymentController::class, 'store']);
    Route::get('payments/bakong/{payment}/check', [PaymentController::class, 'checkBakong']);
    Route::post('payments/{payment}/cancel', [PaymentController::class, 'cancel']);

    // VIEW SALES (optional)
    Route::get('sales', [SaleController::class, 'index']); // cashier can view own sales
});

// ----------------------
// ADMIN ROUTES
// ----------------------
Route::middleware(['auth:api', 'role:admin'])->prefix('admin')->group(function () {

    // USER MANAGEMENT
    Route::apiResource('users', UserController::class);

    // CATEGORY MANAGEMENT
    Route::apiResource('categories', CategoryController::class);

    // PRODUCT MANAGEMENT
    Route::apiResource('products', ProductController::class);

    // SIZE MANAGEMENT
    Route::apiResource('sizes', SizeController::class);

    // CART MANAGEMENT
    Route::apiResource('carts', CartController::class)->only(['index', 'store', 'show']);

    // CART ITEMS
    Route::apiResource('cart-items', CartItemController::class)->only(['store', 'update', 'destroy']);

    // CHECKOUT
    Route::post('carts/{cart}/checkout', [CartController::class, 'checkout']);

    // SALES MANAGEMENT
    Route::apiResource('sales', SaleController::class);

    // REPORTS (optional)
    Route::get('reports/sales', [SaleController::class, 'report']);
});
