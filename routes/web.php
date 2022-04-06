<?php

use App\Http\Controllers\JokulController;
use Illuminate\Support\Facades\Route;

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

// Route::get('/', function () {
//     return view('embed-jokul');
// });

Route::get('/', [JokulController::class, 'payment']);
//Route::get('/validate/{invoice}', [JokulController::class, 'validatePayment']);

