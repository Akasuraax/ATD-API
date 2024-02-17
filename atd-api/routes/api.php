<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TypeController;

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
        Route::post('/volunteer', [UserController::class, 'createVolunteer']);
        Route::post('/beneficiary', [UserController::class, 'createUser']);
        Route::post('/partner', [UserController::class, 'createPartner']);
});

       Route::get('/user', [UserController::class, 'getUsers']);


    Route::post('/login', [UserController::class, 'login']);

    Route::prefix('/type')->group(function(){
        Route::post('/', [TypeController::class, 'createType']);
        Route::get('/', [TypeController::class, 'getTypes']);
        Route::delete('/{id}', [TypeController::class, 'deleteType']);
        Route::patch('/{id}', [TypeController::class, 'updateType']);
    });

        Route::post('/volunteer', function (    Request $request) {
            return app(AuthController::class)->register($request, 2);
        });
        Route::post('/beneficiary', function (Request $request) {
            return app(AuthController::class)->register($request, 3);
        });
        Route::post('/partner', function (Request $request) {
            return app(AuthController::class)->register($request, 4);
        });

