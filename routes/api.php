<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\OperateCityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->group(function () {
    Route::group(['prefix' => 'auth'], function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('recover', [AuthController::class, 'recoverPassword']);
        Route::post('change-password', [AuthController::class, 'saveNewPassword']);

        Route::group(['middleware' => 'auth:api'], function () {
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });
    Route::group(['middleware' => 'auth:api'], function () {
        Route::resource('users',UserController::class);
        Route::resource('roles',RolController::class);
        Route::resource('vehicles',VehicleController::class);
        Route::post('vehicles/{vehicle}/registrationphotography',[VehicleController::class, 'updateRegistrationPhotography']);
        Route::delete('vehicles/{vehicle}/registrationphotography',[VehicleController::class, 'deleteRegistrationPhotography']);
        Route::post('vehicles/{vehicle}/location',[VehicleController::class, 'storeLocation']);
        Route::put('vehicles/{vehicle}/connect',[VehicleController::class, 'connect']);
        Route::post('/roles/{role}/access', [RolController::class, 'setAccess']);
        Route::delete('/roles/{role}/access', [RolController::class, 'removeAccess']);
        Route::get('/access', [AccessController::class, 'index']);
        Route::resource('/cities',OperateCityController::class);
        Route::post('/users/{user}/operate',[UserController::class,'operateCityStore']);
        Route::put('/users/{user}/operate',[UserController::class,'operateCityUpdate']);
        Route::post('/conversations/{conversation}',[ConversationController::class,'acceptConversation']);
        Route::get('/conversations/{conversation}/messages',[MessagesController::class,'index']);
        Route::post('/conversations/{conversation}/messages',[MessagesController::class,'store']);
        Route::get('/vehicles/{vehicle}/trip',[VehicleController::class,'trip']);
        Route::put('/vehicles/{vehicle}/trip',[VehicleController::class,'updateTrip']);

        Route::group(['prefix' => 'profile'], function () {
            Route::get('/', [ProfileController::class, 'me']);
            Route::put('/', [ProfileController::class, 'update']);
            Route::post('photography', [ProfileController::class, 'updatePhotography']);
            Route::post('identification', [ProfileController::class, 'updateIdentificationPhotography']);

        });

    });
    Route::get('/file/{file}',[FileController::class,'viewFile']);
    Route::post('/whatsapp', [WhatsAppController::class, 'receiveMessages']);
    Route::get('/whatsapp', [WhatsAppController::class, 'verificationWhatsapp']);
    Route::get('/demo', [ConversationController::class, 'assignConversation']);
});
