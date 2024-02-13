<?php

use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Http\Request;
use App\Http\Controllers\Volunteer\UserController;
use App\Http\Controllers\Volunteer\PartnerController;
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
        Route::post('/volunteer', [UserController::class, 'createUser']);
    Route::post('/beneficiary', [UserController::class, 'createUser']);
    Route::post('/partner', [PartnerController::class, 'createPartner']);
});
