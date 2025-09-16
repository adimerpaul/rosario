<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\CogController;
use App\Http\Controllers\DailyCashController;
use App\Http\Controllers\DailyCashesController;
use App\Http\Controllers\EgresoController;
use App\Http\Controllers\OrdenController;
use App\Http\Controllers\OrdenPagoController;
use App\Http\Controllers\PrestamoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');
Route::post('/login', [App\Http\Controllers\UserController::class, 'login']);
Route::get('/migrate', [App\Http\Controllers\MigracionController::class, 'migrate']);
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
    Route::post('/ordenes/{orden}/cancelar', [OrdenController::class, 'cancelar']);

    Route::get('daily-cash', [DailyCashController::class, 'show']);          // ?date=YYYY-MM-DD
    Route::post('daily-cash', [DailyCashController::class, 'storeOrUpdate']); // {date, opening_amount, note?}


    Route::get('prestamos', [PrestamoController::class, 'index']);
    Route::get('prestamos/{prestamo}', [PrestamoController::class, 'show']);
    Route::post('prestamos', [PrestamoController::class, 'store']);
    Route::put('prestamos/{prestamo}', [PrestamoController::class, 'update']);
    Route::delete('prestamos/{prestamo}', [PrestamoController::class, 'destroy']);

// pagos
    Route::get('prestamos/{prestamo}/pagos', [PrestamoController::class, 'pagos']);
    Route::post('prestamos/pagos', [PrestamoController::class, 'pagar']);
    Route::put('prestamos/pagos/{pago}/anular', [PrestamoController::class, 'anularPago']);

    Route::post('ordenes/{orden}/pagar-todo', [OrdenController::class, 'pagarTodo']);
    Route::get('ordenes/{orden}/pagos', [OrdenPagoController::class, 'index']); // si no lo tienes aún
    Route::post('ordenes/{orden}/pagos', [OrdenPagoController::class, 'store']);
    Route::post('ordenes/pagos/{pago}/anular', [OrdenPagoController::class, 'anular']); // usado en el front

    Route::get('ordenesRetrasadas', [OrdenController::class, 'atrasadas']);
    Route::get('prestamosRetrasados', [PrestamoController::class, 'retrasados']);

    // Lista y crea egresos
    Route::get('egresos', [EgresoController::class, 'index']);
    Route::post('egresos', [EgresoController::class, 'store']);
// Anular (admin)
    Route::post('egresos/{egreso}/anular', [EgresoController::class, 'anular']);

    Route::post('prestamos/{prestamo}/pagar-mensualidad', [PrestamoController::class, 'pagarMensualidad']);
    Route::post('prestamos/{prestamo}/pagar-cargos', [PrestamoController::class, 'pagarCargos']);
    Route::post('prestamos/{prestamo}/pagar-todo', [PrestamoController::class, 'pagarTodo']);

});
Route::get('/ordenes/{orden}/pdf', [OrdenController::class, 'pdf'])->name('ordenes.pdf');
Route::get('/ordenes/{orden}/garantia', [OrdenController::class, 'garantia'])->name('ordenes.garantia');
