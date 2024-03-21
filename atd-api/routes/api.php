<?php

use App\Http\Controllers\ProblemController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitController;
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
use App\Http\Controllers\FileController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ActivityController;
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
        Route::get('/', [TicketController::class, 'getTickets'])->middleware('authorization:' . serialize([1, 5]));
        Route::get('/{id_ticket}', [TicketController::class, 'getTicket'])->middleware('ticket');
        Route::post('/{id_ticket}', [MessageController::class, 'createMessage'])->middleware('ticket');
        Route::post('/', [TicketController::class, 'createTicket']);
        Route::patch('/{id_ticket}', [TicketController::class, 'patchTicket'])->middleware('authorization:' . serialize([1, 5]));
        Route::delete('/{id_ticket}', [TicketController::class, 'deleteTicket'])->middleware('authorization:' . serialize([1, 5]));
    });

    Route::prefix('/type')->middleware('validity.token')->group(function(){
        Route::post('/', [TypeController::class, 'createType'])->middleware('authorization:' . serialize([1]));
        Route::get('/', [TypeController::class, 'getTypes']);
        Route::get('/all', [TypeController::class, 'getTypesAll'])->middleware('authorization:' . serialize([1]));
        Route::get('/{id}', [TypeController::class, 'getType']);
        Route::delete('/{id}', [TypeController::class, 'deleteType'])->middleware('authorization:' . serialize([1]));
        Route::post('/{id}', [TypeController::class, 'updateType'])->middleware('authorization:' . serialize([1]));
    });
    Route::get('/types', [TypeController::class, 'getTypeAll'])->middleware('validity.token');

    Route::prefix('/user')->middleware('validity.token')->group(function(){
        Route::get('/', [UserController::class, 'getUsers']);
        Route::get('/{id}', [UserController::class, 'getUser'])->middleware('ValidateUserId');
        Route::patch('/{id}', [UserController::class, 'patchUser']);
        Route::patch('/a/{id}', [UserController::class, 'patchUserAdmin'])->middleware('authorization:' . serialize([1]));
        Route::delete('/{id}', [UserController::class, 'deleteUser']);
        Route::get('/{id}/tickets', [TicketController::class, 'getMyTickets'])->middleware('ValidateUserId');
    });

    Route::prefix('/role')->group(function(){
        Route::post('/', [RoleController::class, 'createRole'])->middleware('authorization:' . serialize([1]));
        Route::get('/', [RoleController::class, 'getRoles']);
        Route::get('/all', [RoleController::class, 'getAllRoles'])->middleware('authorization:' . serialize([1]));
        Route::get('/{id}', [RoleController::class, 'getRole']);
        Route::delete('/{id}', [RoleController::class, 'deleteRole'])->middleware('authorization:' . serialize([1]));
        Route::patch('/{id}', [RoleController::class, 'updateRole'])->middleware('authorization:' . serialize([1]));
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
       Route::get('/product/{id}', [WarehouseController::class, 'getWarehousesStock']);
       Route::get('/{id}', [WarehouseController::class, 'getWarehouse']);
       Route::delete('/{id}', [WarehouseController::class, 'deleteWarehouse'])->middleware('authorization:' . serialize([1]));
       Route::patch('/{id}', [WarehouseController::class, 'updateWarehouse'])->middleware('authorization:' . serialize([1]));
    });

    Route::prefix('/annexe')->middleware('validity.token')->group(function(){
        Route::post('/', [AnnexesController::class, 'createAnnexe'])->middleware('authorization:' . serialize([1]));
        Route::get('/', [AnnexesController::class, 'getAnnexes']);
        Route::get('/all', [AnnexesController::class, 'getAnnexesAll'])->middleware('authorization:' . serialize([1]));
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
        Route::post('/{journey_id}', [StepController::class, 'calculusJourney'])->middleware('authorization:' . serialize([1]));
        Route::get('/', [JourneyController::class, 'getJourneys']);
        Route::get('/{id}', [JourneyController::class, 'getJourney']);
        Route::delete('/{id}', [JourneyController::class, 'deleteJourney'])->middleware('authorization:' . serialize([1]));
        Route::patch('/{id}', [JourneyController::class, 'updateJourney'])->middleware('authorization:' . serialize([1]));
        Route::post('/{journey_id}/step', [StepController::class, 'createStep'])->middleware('authorization:' . serialize([1]));
        Route::get('/{journey_id}/step', [StepController::class, 'getJourneySteps']);
        Route::get('/{journey_id}/step/{step_id}', [StepController::class, 'getOneStep']);
        Route::delete('/{journey_id}/step/{step_id}', [StepController::class, 'deleteStep'])->middleware('authorization:' . serialize([1]));
        Route::patch('/{journey_id}/step/{step_id}', [StepController::class, 'updateStep'])->middleware('authorization:' . serialize([1]));
    });

    Route::get('/step', [StepController::class, 'getSteps'])->middleware('validity.token')->middleware('authorization:' . serialize([1]));

    Route::prefix('/product')->middleware('validity.token')->group(function (){
        Route::post('/', [ProductController::class, 'createProduct'])->middleware('authorization:' . serialize([1]));
        Route::get('/', [ProductController::class, 'getProducts']);
        Route::get('/filter', [ProductController::class, 'getProductsFilter']);
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

    Route::prefix('/user')->middleware('validity.token')->group(function (){
        Route::post('/{id}/file', [FileController::class, 'createUserFile']);
        Route::get('/{id}/file', [FileController::class, 'getUserFiles']);
        Route::get('/{id}/file/{idFile}', [FileController::class, 'getUserFile']);
        Route::delete('/{id}/file/{idFile}', [FileController::class, 'deleteUserFile']);
    });

    Route::prefix('/activity')->group(function (){
        Route::post('/', [ActivityController::class, 'createActivity'])->middleware('validity.token');
        Route::get('/', [ActivityController::class, 'getActivities'])->middleware('validity.token');
        Route::get('/between', [ActivityController::class, 'getActivitiesBetween']);
        Route::get('/{id}', [ActivityController::class, 'getActivity'])->middleware('validity.token');
        Route::delete('/{id}', [ActivityController::class, 'deleteActivity'])->middleware('validity.token');
        Route::post('/{id}/file', [FileController::class, 'createActivityFile'])->middleware('validity.token');
        Route::get('/{id}/file', [FileController::class, 'getActivityFiles'])->middleware('validity.token');
        Route::get('/{id}/file/{idFile}', [FileController::class, 'getActivityFile'])->middleware('validity.token');
        Route::delete('/{id}/file/{idFile}', [FileController::class, 'deleteActivityFile'])->middleware('validity.token');
        Route::patch('/{id}', [ActivityController::class, 'updateActivity'])->middleware('validity.token');
        Route::patch('/{id}/recipe', [ActivityController::class, 'updateActivityRecipe'])->middleware('validity.token');
        Route::patch('/{id}/product', [ActivityController::class, 'updateActivityProduct'])->middleware('validity.token');
        Route::patch('/{id}/role', [ActivityController::class, 'updateActivityRole'])->middleware('validity.token');
    });

    Route::prefix('/visit')->middleware('validity.token')->group(function (){
        Route::post('/', [VisitController::class, 'createVisit'])->middleware('authorization:' . serialize([1]));
        Route::get('/', [VisitController::class, 'getVisits'])->middleware('authorization:' . serialize([1, 2, 3]));
        Route::get('/{visit_id}', [VisitController::class, 'getVisit']);
        Route::patch('/{visit_id}', [VisitController::class, 'updateVisit'])->middleware('authorization:' . serialize([1, 2]));
        Route::delete('/{visit_id}', [VisitController::class, 'deleteVisit'])->middleware('authorization:' . serialize([1]));
    });

    Route::prefix('/language')->group(function () {
        Route::post('/', [LanguageController::class, 'createLanguage'])->middleware('validity.token')->middleware('authorization:' . serialize([1]));
        Route::get('/{abbreviation}', [LanguageController::class, 'getLanguageJSON']);
        Route::get('/', [LanguageController::class, 'getLanguages']);
        Route::delete('/{abbreviation}', [LanguageController::class, 'deleteLanguage'])->middleware('validity.token')->middleware('authorization:' . serialize([1]));
    });

    Route::prefix('/languages')->group(function (){
        Route::get('/', [LanguageController::class, 'getLanguagesList'])->middleware('validity.token')->middleware('authorization:' . serialize([1]));
        Route::get('/{abbreviation}', [LanguageController::class, 'getLanguageDetails'])->middleware('validity.token')->middleware('authorization:' . serialize([1]));
    });

    Route::prefix('/problem')->middleware('validity.token')->group(function (){
        Route::post('/', [ProblemController::class, 'createProblem'])->middleware('authorization:' . serialize([1, 5]));
        Route::delete('/{problem_id}', [ProblemController::class, 'deleteProblem'])->middleware('authorization:' . serialize([1, 5]));
        Route::patch('/{problem_id}', [ProblemController::class, 'patchProblem'])->middleware('authorization:' . serialize([1, 5]));
        Route::get('/', [ProblemController::class, 'getProblems']);
        Route::get('/admin', [ProblemController::class, 'getAdminProblems'])->middleware('authorization:' . serialize([1, 5]));
    });
