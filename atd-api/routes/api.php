<?php

use App\Http\Controllers\TicketController;
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
        Route::post('/volunteer', function (Request $request) {
            return app(UserController::class)->register($request, 2);
        });
        Route::post('/beneficiary', function (Request $request) {
            return app(UserController::class)->register($request, 3);
        });
        Route::post('/partner', function (Request $request) {
            return app(UserController::class)->register($request, 4);
        });
    });

    Route::post('/logIn', [AuthController::class, 'logIn']);
    Route::get('/logOut', [AuthController::class, 'logOut'])->middleware('validity.token');

    Route::prefix('/type')->group(function(){
        Route::post('/', [TypeController::class, 'createType']);
        Route::get('/', [TypeController::class, 'getTypes']);
        Route::delete('/{id}', [TypeController::class, 'deleteType']);
        Route::patch('/{id}', [TypeController::class, 'updateType']);
    });

    Route::prefix('/user')->group(function(){
        Route::get('/', [UserController::class, 'getUsers']);
        Route::get('/{id}', [UserController::class, 'getUser']);
        Route::delete('/{id}', [UserController::class, 'deleteUser']);
    });

    Route::prefix('/ticket')->middleware('validity.token')->group(function () {
        Route::get('/mine', [TicketController::class, 'getMyTickets']);
        Route::get('/{id_ticket}', [TicketController::class, 'getTicket']);
        Route::post('/', [TicketController::class, 'createTicket']);
    });
