<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiAnimalController;
use App\Http\Controllers\Api\ApiLoteController;
use App\Http\Controllers\Api\ApiPesajeController;
use App\Http\Controllers\Api\ApiSaludController;
use App\Http\Controllers\Api\ApiAlimentacionController;
use App\Http\Controllers\Api\ApiReproduccionController;
use App\Http\Controllers\Api\ApiVentaController;
use App\Http\Controllers\Api\ApiSacrificiController;
use App\Http\Controllers\Api\ApiCostoController;
use App\Http\Controllers\Api\ApiTrabajadorController;

// ── Rutas públicas ────────────────────────────────────────────────────────────
Route::post('/login',  [ApiAuthController::class, 'login']);

// ── Rutas protegidas con Sanctum ──────────────────────────────────────────────
//
// IMPORTANTE: los apiResource de abajo llevan ->names('api.…') a propósito.
// Sin eso generan los nombres animales.index, lotes.index, salud.index y
// ventas.index, que ya usan las rutas web. Como este archivo se carga después
// de web.php, la versión de la API ganaba en el mapa de nombres que Ziggy envía
// al navegador y el Sidebar terminaba navegando a /api/animales (protegida por
// Sanctum) en vez de a la página real, rebotando al dashboard.
//
// Las URL no cambian: siguen siendo /api/animales, /api/lotes, etc.
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [ApiAuthController::class, 'logout']);
    Route::get('/me',      [ApiAuthController::class, 'me']);

    // Animales
    Route::apiResource('animales', ApiAnimalController::class)->names('api.animales');

    // Lotes
    Route::apiResource('lotes', ApiLoteController::class)->names('api.lotes');

    // Pesajes
    Route::get('/pesajes',         [ApiPesajeController::class, 'index']);
    Route::post('/pesajes',        [ApiPesajeController::class, 'store']);
    Route::get('/pesajes/{pesaje}', [ApiPesajeController::class, 'show']);
    Route::delete('/pesajes/{pesaje}', [ApiPesajeController::class, 'destroy']);

    // Salud
    Route::apiResource('salud', ApiSaludController::class)->names('api.salud');

    // Alimentación
    Route::get('/alimentacion/inventario',           [ApiAlimentacionController::class, 'inventario']);
    Route::post('/alimentacion/inventario',          [ApiAlimentacionController::class, 'storeInventario']);
    Route::put('/alimentacion/inventario/{inventarioInsumo}', [ApiAlimentacionController::class, 'updateInventario']);
    Route::get('/alimentacion/raciones',             [ApiAlimentacionController::class, 'raciones']);
    Route::post('/alimentacion/raciones',            [ApiAlimentacionController::class, 'storeRacion']);

    // Reproducción
    Route::get('/reproduccion/gestaciones',  [ApiReproduccionController::class, 'gestaciones']);
    Route::post('/reproduccion/gestaciones', [ApiReproduccionController::class, 'storeGestacion']);
    Route::get('/reproduccion/partos',       [ApiReproduccionController::class, 'partos']);
    Route::post('/reproduccion/partos',      [ApiReproduccionController::class, 'storeParto']);

    // Ventas
    Route::apiResource('ventas', ApiVentaController::class)->names('api.ventas');

    // Sacrificios
    Route::get('/sacrificios',              [ApiSacrificiController::class, 'index']);
    Route::post('/sacrificios',             [ApiSacrificiController::class, 'store']);
    Route::get('/sacrificios/{sacrificio}', [ApiSacrificiController::class, 'show']);
    Route::delete('/sacrificios/{sacrificio}', [ApiSacrificiController::class, 'destroy']);

    // Costos
    Route::get('/costos',          [ApiCostoController::class, 'index']);
    Route::post('/costos',         [ApiCostoController::class, 'store']);
    Route::put('/costos/{faena}',  [ApiCostoController::class, 'update']);
    Route::delete('/costos/{faena}', [ApiCostoController::class, 'destroy']);

    // Trabajadores
    Route::get('/trabajadores',           [ApiTrabajadorController::class, 'index']);
    Route::post('/trabajadores',          [ApiTrabajadorController::class, 'store']);
    Route::get('/trabajadores/{user}',    [ApiTrabajadorController::class, 'show']);
    Route::put('/trabajadores/{user}',    [ApiTrabajadorController::class, 'update']);

    // Dashboard resumen
    Route::get('/dashboard', function () {
        return response()->json([
            'total_animales'      => \App\Models\Animal::count(),
            'animales_activos'    => \App\Models\Animal::count(),
            'alertas_pendientes'  => \App\Models\EventoSalud::where('estado', 'Pendiente')->count(),
            'lotes'               => \App\Models\Lote::count(),
            'trabajadores'        => \App\Models\User::where('activo', true)->count(),
        ]);
    });
});