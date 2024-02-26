<?php

use App\Http\Controllers\MessageController;
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
use App\Http\Controllers\StepController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\PieceController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\DemandController;
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

    Route::prefix('/ticket')->middleware('validity.token')->group(function () {
        Route::get('/', [TicketController::class, 'getMyTickets']);
        Route::get('/{id_ticket}', [TicketController::class, 'getTicket'])->middleware('ticket');
        Route::post('/{id_ticket}', [MessageController::class, 'createMessage'])->middleware('ticket');
        Route::post('/', [TicketController::class, 'createTicket']);
    });

    Route::prefix('/type')->middleware('validity.token')->group(function(){
        Route::post('/', [TypeController::class, 'createType'])->middleware('authorization:' . serialize([1]));
        Route::get('/', [TypeController::class, 'getTypes']);
        Route::get('/{id}', [TypeController::class, 'getType']);
        Route::delete('/{id}', [TypeController::class, 'deleteType'])->middleware('authorization:' . serialize([1]));
        Route::patch('/{id}', [TypeController::class, 'updateType'])->middleware('authorization:' . serialize([1]));
    });

    Route::prefix('/demand')->middleware('validity.token')->group(function(){
       Route::post('/', [DemandController::class, 'createDemand']);
       Route::get('/', [DemandController::class, 'getDemands']);
       Route::get('/{id}', [DemandController::class, 'getDemand']);
       Route::delete('/{id}', [DemandController::class, 'deleteDemand']);
       Route::patch('/{id}', [DemandController::class, 'updateDemand']);
    });

    Route::prefix('/warehouse')->middleware('validity.token')->group(function(){
       Route::post('/', [WarehouseController::class, 'createWarehouse'])->middleware('authorization:' . serialize([1]));
       Route::get('/', [WarehouseController::class, 'getWarehouses']);
       Route::get('/{id}', [WarehouseController::class, 'getWarehouse']);
       Route::delete('/{id}', [WarehouseController::class, 'deleteWarehouse'])->middleware('authorization:' . serialize([1]));
       Route::patch('/{id}', [WarehouseController::class, 'updateWarehouse'])->middleware('authorization:' . serialize([1]));
    });

    Route::prefix('/annexe')->middleware('validity.token')->group(function(){
        Route::post('/', [AnnexesController::class, 'createAnnexe'])->middleware('authorization:' . serialize([1]));
        Route::get('/', [AnnexesController::class, 'getAnnexes']);
        Route::get('/{id}', [AnnexesController::class, 'getAnnexe']);
        Route::delete('/{id}', [AnnexesController::class, 'deleteAnnexe'])->middleware('authorization:' . serialize([1]));
        Route::patch('/{id}', [AnnexesController::class, 'updateAnnexe'])->middleware('authorization:' . serialize([1]));
    });

    Route::prefix('/vehicle')->middleware('validity.token')->group(function(){
        Route::post('/', [VehicleController::class, 'createVehicle'])->middleware('authorization:' . serialize([1]));
        Route::get('/', [VehicleController::class, 'getVehicles']);
        Route::get('/{id}', [VehicleController::class, 'getVehicle']);
        Route::delete('/{id}', [VehicleController::class, 'deleteVehicle'])->middleware('authorization:' . serialize([1]));
        Route::patch('/{id}', [VehicleController::class, 'updateVehicle'])->middleware('authorization:' . serialize([1]));
    });

    Route::prefix('/journey')->middleware('validity.token')->group(function(){
        Route::post('/', [JourneyController::class, 'createJourney'])->middleware('authorization:' . serialize([1]));
        Route::get('/', [JourneyController::class, 'getJourneys']);
        Route::get('/{id}', [JourneyController::class, 'getJourney']);
        Route::delete('/{id}', [JourneyController::class, 'deleteJourney'])->middleware('authorization:' . serialize([1]));
        Route::patch('/{id}', [JourneyController::class, 'updateJourney'])->middleware('authorization:' . serialize([1]));
    });

    Route::prefix('step')->middleware('validity.token')->group(function(){
       Route::post('/', [StepController::class, 'createStep'])->middleware('authorization:' . serialize([1]));
       Route::get('/', [StepController::class, 'getSteps']);
       Route::get('/{id}', [StepController::class, 'getJourneySteps']);
       Route::delete('/{id}', [StepController::class, 'deleteStep'])->middleware('authorization:' . serialize([1]));
       Route::patch('/{id}', [StepController::class, 'updateStep'])->middleware('authorization:' . serialize([1]));
    });


    Route::prefix('/product')->middleware('validity.token')->group(function (){
        Route::post('/', [ProductController::class, 'createProduct'])->middleware('authorization:' . serialize([1]));
        Route::get('/', [ProductController::class, 'getProducts']);
        Route::get('/{id}', [ProductController::class, 'getProduct']);
        Route::delete('/{id}', [ProductController::class, 'deleteProduct'])->middleware('authorization:' . serialize([1]));
        Route::patch('/{id}', [ProductController::class, 'updateProduct'])->middleware('authorization:' . serialize([1]));
    });

    Route::prefix('/piece')->middleware('validity.token')->group(function (){
        Route::post('/', [PieceController::class, 'createPiece'])->middleware('authorization:' . serialize([1]));
        Route::get('/', [PieceController::class, 'getPieces']);
        Route::get('/{id}', [PieceController::class, 'getPiece']);
        Route::delete('/{id}', [PieceController::class, 'deletePiece'])->middleware('authorization:' . serialize([1]));
        Route::patch('/{id}', [PieceController::class, 'updatePiece'])->middleware('authorization:' . serialize([1]));
    });

    Route::prefix('/recipe')->middleware('validity.token')->group(function (){
        Route::post('/', [RecipeController::class, 'createRecipe'])->middleware('authorization:' . serialize([1]));
        Route::get('/', [RecipeController::class, 'getRecipes']);
        Route::get('/{id}', [RecipeController::class, 'getRecipe']);
        Route::delete('/{id}', [RecipeController::class, 'deleteRecipe'])->middleware('authorization:' . serialize([1]));
        Route::delete('/{id}/product', [RecipeController::class, 'deleteRecipeProduct'])->middleware('authorization:' . serialize([1]));
        Route::patch('/{id}', [RecipeController::class, 'updateRecipe'])->middleware('authorization:' . serialize([1]));
    });
