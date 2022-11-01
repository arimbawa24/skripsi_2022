<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MakananController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/insert', function() {
//     $stuRef = app('firebase.firestore')->database()->collection('User')->newDocument();
//     $stuRef->set([
//        'firstname' => 'Seven',
//        'lastname' => 'Stac',
//        'age'    => 19
// ]);
// echo "<h1>".'inserted'."</h1>";
// });

Route::GET('/insert', [AuthController::class,'registrasi']); 
Route::GET('/getProfile', [AuthController::class,'getProfile']); 
Route::GET('/login', [AuthController::class,'login']); 
Route::GET('/update', [AuthController::class,'updateProfile']); 
Route::GET('/insertMakanan', [MakananController::class,'insertMakanan']); 
Route::GET('/getMakanan', [MakananController::class,'GetMakanan']); 