<?php

use App\Http\Controllers\Cart;
use App\Http\Controllers\User;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Review;
use App\Http\Controllers\Product;
use App\Http\Controllers\Order;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [User::class, 'home']); // user controller

//Contact
Route::view('/contact', 'contact');

Route::get('/login', [User::class, 'login'])->middleware('alreadyLoggedIn');
Route::post('/login', [User::class, 'loginPost'])->name('login');
Route::get('/register', [User::class, 'register'])->middleware('alreadyLoggedIn');
Route::post('/register', [User::class, 'registerPost'])->name('register');
Route::get('/profile', [User::class, 'profile'])->middleware('isLoggedIn');
Route::get('/logout', [User::class, 'logout']);
Route::post('/reset-password', [User::class, 'resetPassword'])->name('reset-password');
Route::post('/check-email', [User::class, 'checkEmail'])->name('check-email');
Route::post('/check-update-email', [User::class, 'checkUpdateEmail'])->name('check-update-email');
Route::post('/check-password', [User::class, 'checkPassword'])->name('check-password');
Route::post('/update-user-data', [User::class, 'updateUser'])->name('update-user-data')->middleware('isLoggedIn');


Route::get('/shop', [Product::class, 'shop'])->name('shop');
Route::get('/product/{id}', [Product::class, 'productDetails'])->name('product-details');

//Cart
Route::get('/cart', [Cart::class, 'cart'])->name('cart');
Route::post('/add-to-cart', [Cart::class, 'addToCart'])->name('add-to-cart');
Route::post('/update-cart', [Cart::class, 'updateCart'])->name('update-cart');
Route::post('/check-quantity', [Cart::class, 'checkQuantity'])->name('check-quantity');


Route::delete('/cart-delete-item/{product_id}', [Cart::class, 'cartDeleteItem'])->name('cart.delete.item');
Route::post('/coupon', [Cart::class, 'applyDiscount'])->name('coupon');

//Order
Route::get('/checkout', [Order::class, 'checkout'])->name('checkout')->middleware('isLoggedIn');
Route::post('/checkout', [Order::class, 'checkout'])->name('checkout')->middleware('isLoggedIn');
Route::post('/add-order', [Order::class, 'addOrder'])->name('add-order')->middleware('isLoggedIn');
Route::post('/user/address', [User::class, 'createOrUpdateAddress'])->name('user.address')->middleware('isLoggedIn');


Route::post('/get-cities', [Order::class, 'getCities'])->name('get.cities')->middleware('isLoggedIn');
Route::get('/payment/cancel', [Order::class, 'cancel'])->name('payment.cancel')->middleware('isLoggedIn');
Route::get('/payment/success', [Order::class, 'success'])->name('payment.success')->middleware('isLoggedIn');


//Review
Route::post('/add-review', [Review::class, 'addReview'])->name('add-review');
Route::post('/edit-review', [Review::class, 'editReview'])->name('edit-review');
Route::delete('/destory-review/{id}', [Review::class, 'destroy'])->name('destory-review');



Route::controller(GoogleController::class)->group(function(){
    Route::get('auth/google', 'redirectToGoogle')->name('auth.google');
    Route::get('auth/google/callback', 'handleGoogleCallback');
});



// admin routes
Route::controller(Admin::class)->group(function(){

    //User
    Route::get('/admin', 'dashboard')->name('admin.dashboard')->middleware('adminCheck');
    Route::get('/admin/users', 'users')->name('admin.users')->middleware('adminCheck');
    Route::get('/admin/user/add', 'add')->name('admin.user.add')->middleware('adminCheck');
    Route::post('/admin/user/add', 'create')->name('admin.user.add')->middleware('adminCheck');
    Route::get('/admin/user/update/{id}', 'update')->name('admin.user.update')->middleware('adminCheck');
    Route::post('/admin/user/store', 'store')->name('admin.user.store')->middleware('adminCheck');
    Route::get('/admin/user/delete', 'delete')->name('admin.user.delete')->middleware('adminCheck');

    //Produkt
    Route::get('admin/products', 'products')->name('admin.products')->middleware('adminCheck');
    Route::get('/admin/product/add', 'addProduct')->name('admin.product.add')->middleware('adminCheck');
    Route::post('/admin/product/add', 'createProduct')->name('admin.product.add')->middleware('adminCheck');
    Route::get('/admin/product/update/{id}', 'updateProduct')->name('admin.product.update')->middleware('adminCheck');
    Route::post('/admin/product/store', 'storeProduct')->name('admin.product.store')->middleware('adminCheck');
    Route::get('/admin/product/delete', 'deleteProduct')->name('admin.product.delete')->middleware('adminCheck');

    //Categories
    Route::get('admin/categories', 'categories')->name('admin.categories')->middleware('adminCheck');
    Route::get('/admin/category/add', 'addCategory')->name('admin.category.add')->middleware('adminCheck');
    Route::post('/admin/category/add', 'createCategory')->name('admin.category.add')->middleware('adminCheck');
    Route::get('/admin/category/update/{id}', 'updateCategory')->name('admin.category.update')->middleware('adminCheck');
    Route::post('/admin/category/store', 'storeCategory')->name('admin.category.store')->middleware('adminCheck');
    Route::get('/admin/category/delete', 'deleteCategory')->name('admin.category.delete')->middleware('adminCheck');

    // COLORS
    Route::get('admin/colors', 'colors')->name('admin.colors')->middleware('adminCheck');
    Route::get('/admin/color/add', 'addColor')->name('admin.color.add')->middleware('adminCheck');
    Route::post('/admin/color/add', 'createColor')->name('admin.color.add')->middleware('adminCheck');
    Route::get('/admin/color/update/{id}', 'updateColor')->name('admin.color.update')->middleware('adminCheck');
    Route::post('/admin/color/store', 'storeColor')->name('admin.color.store')->middleware('adminCheck');
    Route::get('/admin/color/delete', 'deleteColor')->name('admin.color.delete')->middleware('adminCheck');

    //SIZES
    Route::get('admin/sizes', 'sizes')->name('admin.sizes')->middleware('adminCheck');
    Route::get('/admin/size/add', 'addSize')->name('admin.size.add')->middleware('adminCheck');
    Route::post('/admin/size/add', 'createSize')->name('admin.size.add')->middleware('adminCheck');
    Route::get('/admin/size/update/{id}', 'updateSize')->name('admin.size.update')->middleware('adminCheck');
    Route::post('/admin/size/store', 'storeSize')->name('admin.size.store')->middleware('adminCheck');
    Route::get('/admin/size/delete', 'deleteSize')->name('admin.size.delete')->middleware('adminCheck');

    //COUPONS
    Route::get('admin/coupons', 'coupons')->name('admin.coupons')->middleware('adminCheck');
    Route::get('/admin/coupon/add', 'addCoupon')->name('admin.coupon.add')->middleware('adminCheck');
    Route::post('/admin/coupon/add', 'createCoupon')->name('admin.coupon.add')->middleware('adminCheck');
    Route::get('/admin/coupon/update/{id}', 'updateCoupon')->name('admin.coupon.update')->middleware('adminCheck');
    Route::post('/admin/coupon/store', 'storeCoupon')->name('admin.coupon.store')->middleware('adminCheck');
    Route::get('/admin/coupon/delete', 'deleteCoupon')->name('admin.coupon.delete')->middleware('adminCheck');


    //ORDERS
    Route::get('admin/orders', 'orders')->name('admin.orders')->middleware('adminCheck');
    Route::get('/admin/order/update/{id}', 'updateOrder')->name('admin.order.update')->middleware('adminCheck');
    Route::post('/admin/order/store', 'storeOrder')->name('admin.order.store')->middleware('adminCheck');
    Route::get('/admin/order/delete', 'deleteOrder')->name('admin.order.delete')->middleware('adminCheck');
});
// End Admin Routs




