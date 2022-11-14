<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MakananController;
use App\Http\Controllers\MinumanController;
use App\Http\Controllers\WholeBeanController;
use App\Http\Controllers\MerchandiseController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\NotificationController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('auth')->group(function () {
    Route::POST('/registrasi', [AuthController::class,'registrasi']);
    Route::GET('/profile/get', [AuthController::class,'getProfile']);
    Route::POST('/login', [AuthController::class,'login']); 
    Route::POST('/profile/update', [AuthController::class,'updateProfile']); 
    Route::POST('/logout', [AuthController::class,'Logout']); 
    Route::PUT('/profile/changePassword', [AuthController::class,'changePassword']); 
});

Route::prefix('produk')->group(function () {
    Route::POST('/makanan/insert', [MakananController::class,'insertMakanan']); 
    Route::GET('/makanan/get', [MakananController::class,'GetMakanan']); 
    Route::GET('/makanan/detail/get', [MakananController::class,'GetDetailMakanan']); 
    Route::POST('/minuman/insert', [MinumanController::class,'InsertMinuman']); 
    Route::GET('/minuman/get', [MinumanController::class,'GetMinuman']); 
    Route::GET('/minuman/detail/get', [MinumanController::class,'GetDetailMinuman']);
    Route::POST('/wholebean/insert', [WholeBeanController::class,'InsertWB']); 
    Route::GET('/wholebean/get', [WholeBeanController::class,'GetWB']); 
    Route::GET('/wholebean/detail/get', [WholeBeanController::class,'GetDetailWB']); 
    Route::POST('/merchandise/insert', [MerchandiseController::class,'InsertMerch']); 
    Route::GET('/merchandise/get', [MerchandiseController::class,'GetMerch']); 
    Route::GET('/merchandise/detail/get', [MerchandiseController::class,'GetDetailMerch']); 
    Route::PUT('/stok/update', [MakananController::class,'updateProduk']); 
});

Route::prefix('promo')->group(function () {
    Route::POST('/insert', [PromoController::class,'InsertPromo']);
    Route::GET('/detail/get', [PromoController::class,'GetDetailPromo']);
    Route::GET('/validasi', [PromoController::class,'ValidasiPromo']); 
    Route::GET('/get', [PromoController::class,'GetPromo']); 
});

Route::prefix('location')->group(function () {
    Route::POST('/insert', [LocationController::class,'InsertLocation']);
    Route::GET('/detail/get', [LocationController::class,'GetDetailLocation']);
    Route::GET('/get', [LocationController::class,'GetLocation']); 
});

Route::prefix('keranjang')->group(function () {
    Route::POST('/insert', [KeranjangController::class,'InsertKeranjang']);
    Route::GET('/get', [KeranjangController::class,'GetKeranjang']); 
    Route::POST('/checkout', [KeranjangController::class,'checkout']); 

});

Route::prefix('transaksi')->group(function () {
    Route::GET('/get', [TransaksiController::class,'GetTransaksiUser']);
    Route::GET('/kodeBayar/get', [TransaksiController::class,'GetKodeTransaksi']);
    Route::GET('/get/history', [TransaksiController::class,'GetHistoryTransaksi']);
    Route::POST('/bayar', [TransaksiController::class,'bayar']);
    Route::GET('/point/tukar', [TransaksiController::class,'tukarPoint']);
});

Route::prefix('notification')->group(function () {
    Route::POST('/sendnotif', [NotificationController::class,'sendNotification']);
    Route::POST('/birthday/sendnotif', [NotificationController::class,'birthday']);
});

 