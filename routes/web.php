<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ZohoController;
use App\Http\Controllers\SDKUseController;

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

//Route::get('/', function () {
//    return view('welcome');
//});

Route::get('/',[ZohoController::class, 'index'])->name('index');
Route::get('/init',[ZohoController::class, 'authorization_request'])->name('init');
Route::get('/callback',[ZohoController::class, 'callback'])->name('callback');
Route::get('/token',[ZohoController::class, 'access_token_request'])->name('access_token_request');
Route::get('/work',[ZohoController::class, 'work'])->name('work');

Route::get('/sdk',[SDKUseController::class, 'index'])->name('sdk.index');
Route::get('/sdk/callback',[SDKUseController::class, 'callback'])->name('sdk.callback');

