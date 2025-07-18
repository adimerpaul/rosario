<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\CogController;
use App\Http\Controllers\OrdenController;
use App\Http\Controllers\OrdenPagoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');
Route::post('/login', [App\Http\Controllers\UserController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [App\Http\Controllers\UserController::class, 'logout']);
    Route::get('/me', [App\Http\Controllers\UserController::class, 'me']);


    Route::get('/users', [App\Http\Controllers\UserController::class, 'index']);
    Route::post('/users', [App\Http\Controllers\UserController::class, 'store']);
    Route::put('/users/{user}', [App\Http\Controllers\UserController::class, 'update']);
    Route::delete('/users/{user}', [App\Http\Controllers\UserController::class, 'destroy']);
    Route::put('/updatePassword/{user}', [App\Http\Controllers\UserController::class, 'updatePassword']);
    Route::post('/{user}/avatar', [App\Http\Controllers\UserController::class, 'updateAvatar']);

    Route::get('/clients', [ClientController::class, 'index']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::put('/clients/{client}', [ClientController::class, 'update']);
    Route::delete('/clients/{client}', [ClientController::class, 'destroy']);

    Route::get('/ordenes', [OrdenController::class, 'index']); // listado filtrado
    Route::post('/ordenes', [OrdenController::class, 'store']);
    Route::get('/ordenes/{orden}', [OrdenController::class, 'show']);
    Route::put('/ordenes/{orden}', [OrdenController::class, 'update']);
    Route::delete('/ordenes/{orden}', [OrdenController::class, 'destroy']);

    Route::get('cogs', [CogController::class, 'index']);
    Route::put('cogs/{cog}', [CogController::class, 'update']);
    Route::get('cogs/{id}', [CogController::class, 'show']);

    Route::get('/ordenes/{orden}/pagos', [OrdenPagoController::class, 'index']);
    Route::post('/ordenes/pagos', [OrdenPagoController::class, 'store']);
    Route::put('/ordenes/pagos/{pago}', [OrdenPagoController::class, 'update']);
});
