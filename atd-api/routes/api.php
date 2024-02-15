<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

    Route::prefix('/signIn')->group(function (){
        Route::post('/volunteer', function (    Request $request) {
            return app(AuthController::class)->register($request, 2);
        });
        Route::post('/beneficiary', function (Request $request) {
            return app(AuthController::class)->register($request, 3);
        });
        Route::post('/partner', function (Request $request) {
            return app(AuthController::class)->register($request, 4);
        });});
