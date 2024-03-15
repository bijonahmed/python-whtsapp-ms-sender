<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cart\CartController;
use App\Http\Controllers\UnauthenticatedController;

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

Route::post('add-to-cart', [CartController::class, 'addToCart']);
Route::post('/remove-from-cart', 'CartController@removeFromCart');
Route::get('cart', [CartController::class, 'index']);
Route::get('clearCart', [CartController::class, 'clearCart']);
Route::get('/send-sms', function () {
    return view('send-sms');
})->name('send-sms');

Route::get('/', function () {
    return view('welcome');
   
});
Route::post('send-sms', [UnauthenticatedController::class, 'sendMessage']);