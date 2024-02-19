<?php

use App\Http\Controllers\TicketController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TypeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AnnexesController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\JourneyController;
use App\Http\Controllers\DrivesController;
use App\Http\Controllers\StepController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\PieceController;
use App\Http\Controllers\RecipeController;
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

    Route::prefix('/ticket')->middleware('validity.token')->group(function () {
        Route::get('/mine', [TicketController::class, 'getMyTickets']);
        Route::get('/{id_ticket}', [TicketController::class, 'getTicket']);
        Route::post('/', [TicketController::class, 'createTicket']);
    });

    Route::prefix('/warehouse')->group(function(){
       Route::post('/', [WarehouseController::class, 'createWarehouse']);
       Route::get('/', [WarehouseController::class, 'getWarehouse']);
       Route::delete('/{id}', [WarehouseController::class, 'deleteWarehouse']);
       Route::patch('/{id}', [WarehouseController::class, 'updateWarehouse']);
    });
    Route::prefix('/annexe')->group(function(){
        Route::post('/', [AnnexesController::class, 'createAnnexe']);
        Route::get('/', [AnnexesController::class, 'getAnnexes']);
        Route::delete('/{id}', [AnnexesController::class, 'deleteAnnexe']);
        Route::patch('/{id}', [AnnexesController::class, 'updateAnnexe']);
    });

    Route::prefix('/vehicle')->group(function(){
        Route::post('/', [VehicleController::class, 'createVehicle']);
        Route::get('/', [VehicleController::class, 'getVehicles']);
        Route::delete('/{id}', [VehicleController::class, 'deleteVehicle']);
        Route::patch('/{id}', [VehicleController::class, 'updateVehicle']);
    });

    Route::prefix('/journey')->group(function(){
        Route::post('/', [JourneyController::class, 'createJourney']);
        Route::get('/', [JourneyController::class, 'getJourneys']);
        Route::delete('/{id}', [JourneyController::class, 'deleteJourney']);
        Route::patch('/{id}', [JourneyController::class, 'updateJourney']);
    });

    Route::get('/drives', [DrivesController::class, 'getDrives']);

    Route::prefix('step')->group(function(){
       Route::post('/', [StepController::class, 'createStep']);
       Route::get('/', [StepController::class, 'getSteps']);
       Route::get('/{id}', [StepController::class, 'getJourneySteps']);
       Route::delete('/{id}', [StepController::class, 'deleteStep']);
       Route::patch('/{id}', [StepController::class, 'updateStep']);
    });


    Route::prefix('/product')->group(function (){
        Route::post('/', [ProductController::class, 'createProduct']);
        Route::get('/', [ProductController::class, 'getProducts']);
        Route::get('/{id}', [ProductController::class, 'getProduct']);
        Route::delete('/{id}', [ProductController::class, 'deleteProduct']);
        Route::patch('/{id}', [ProductController::class, 'updateProduct']);
    });

    Route::prefix('/piece')->group(function (){
        Route::post('/', [PieceController::class, 'createPiece']);
        Route::get('/', [PieceController::class, 'getPieces']);
        Route::get('/{id}', [PieceController::class, 'getPiece']);
        Route::delete('/{id}', [PieceController::class, 'deletePiece']);
        Route::patch('/{id}', [PieceController::class, 'updatePiece']);
    });

    Route::prefix('/recipe')->group(function (){
        Route::post('/', [RecipeController::class, 'createRecipe']);
        Route::get('/', [RecipeController::class, 'getRecipes']);
        Route::get('/{id}', [RecipeController::class, 'getRecipe']);
        Route::delete('/{id}', [RecipeController::class, 'deleteRecipe']);
        Route::patch('/{id}', [RecipeController::class, 'updateRecipe']);
    });
