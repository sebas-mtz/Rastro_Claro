<?php
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomController;
use App\Http\Controllers\SaludController;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\FaenaController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\SacrificioController;
use App\Http\Controllers\AlimentacionController;
use App\Http\Controllers\InventarioInsumoController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\PrediccionController;
use App\Http\Controllers\ReproduccionController;
use App\Http\Controllers\PaymentController;
// GOOGLE
Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle'])
    ->name('auth.google');

Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);
/*
|--------------------------------------------------------------------------
| Página de bienvenida pública
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
    ]);
})->name('welcome');
/*
|--------------------------------------------------------------------------
| roles
|--------------------------------------------------------------------------
*/
// solo admin
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/usuarios', [AdminUserController::class, 'index'])
        ->name('admin.usuarios.index');

    Route::put('/admin/usuarios/{user}', [AdminUserController::class, 'update'])
        ->name('admin.usuarios.update');
});

/*
|--------------------------------------------------------------------------
| Interfaz pública personalizada (sin autenticación)
|--------------------------------------------------------------------------
*/
Route::get('/splash', [CustomController::class, 'splash'])
    ->name('splash');

/*
|--------------------------------------------------------------------------
| Rutas protegidas (auth + verified)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    /*
    |----------------------------------------------------------------------
    | Dashboard / Home personalizado
    |----------------------------------------------------------------------
    */
    Route::get('/dashboard', [CustomController::class, 'home'])
        ->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Vistas personalizadas
    |----------------------------------------------------------------------
    */
    // Vista de animales para el menú (usa index de AnimalController)
    Route::get('/animals', [AnimalController::class, 'index'])->name('animals.custom');

    /*
    |----------------------------------------------------------------------
    | Módulo de Salud (SaludController)
    |----------------------------------------------------------------------
    */
    Route::get('/health', [SaludController::class, 'calendar'])
        ->name('health.custom');

    Route::post('/health/appointments', [SaludController::class, 'storeAppointment'])
        ->name('health.appointments.store');

    Route::patch('/health/appointments/{salud}/complete', [SaludController::class, 'complete'])
        ->name('health.appointments.complete');

    /*
    |----------------------------------------------------------------------
    | CRUD Animales (AnimalController)
    |----------------------------------------------------------------------
    */
    Route::get('/animales', [AnimalController::class, 'index'])->name('animales.index');
    Route::get('/animales/create', [AnimalController::class, 'create'])->name('animales.create');
    Route::post('/animales', [AnimalController::class, 'store'])->name('animales.store');
    Route::get('/animales/{animal}', [AnimalController::class, 'show'])->name('animales.show');
    Route::get('/animales/{animal}/edit', [AnimalController::class, 'edit'])->name('animales.edit');
    Route::put('/animales/{animal}', [AnimalController::class, 'update'])->name('animales.update');
    Route::delete('/animales/{animal}', [AnimalController::class, 'destroy'])->name('animales.destroy');
    Route::put( '/alimentacion/inventario/{item}/reabastecer', [InventarioInsumoController::class, 'reabastecer'])->name('alimentacion.inventario.reabastecer');
    /*
    |----------------------------------------------------------------------
    | CRUD Lotes (LoteController)
    |----------------------------------------------------------------------
    */
    Route::get('/lotes', [LoteController::class, 'index'])->name('lotes.index');
    Route::get('/lotes/create', [LoteController::class, 'create'])->name('lotes.create');
    Route::post('/lotes', [LoteController::class, 'store'])->name('lotes.store');
    Route::get('/lotes/{lote}', [LoteController::class, 'show'])->name('lotes.show');
    Route::get('/lotes/{lote}/edit', [LoteController::class, 'edit'])->name('lotes.edit');
    Route::put('/lotes/{lote}', [LoteController::class, 'update'])->name('lotes.update');
    Route::delete('/lotes/{lote}', [LoteController::class, 'destroy'])->name('lotes.destroy');

    /*
    |----------------------------------------------------------------------
    | CRUD Producciones (ProduccionController)
    |----------------------------------------------------------------------
    */
   Route::get('/producciones', [ProduccionController::class, 'index'])->name('producciones.index');

    /*
    |----------------------------------------------------------------------
    | CRUD Alimentación (Raciones) (AlimentacionController)
    |----------------------------------------------------------------------
    */
    Route::get('/alimentacion', [AlimentacionController::class, 'index'])->name('alimentacion.index');
    Route::post('/alimentacion', [AlimentacionController::class, 'store'])->name('alimentacion.store');
    Route::put('/alimentacion/{alimentacion}', [AlimentacionController::class, 'update'])->name('alimentacion.update');
    // Crear nuevo alimento en inventario
    Route::post('/alimentacion/inventario', [InventarioInsumoController::class, 'store'])->name('alimentacion.inventario.store');
    // Reabastecer (la que ya teníamos)
    Route::put('/alimentacion/inventario/{item}/reabastecer',[InventarioInsumoController::class, 'reabastecer'])->name('alimentacion.inventario.reabastecer');
    /*
    |----------------------------------------------------------------------
    | Nuevos módulos: faenas, ventas, sacrificios
    |----------------------------------------------------------------------
    */
    Route::resource('faenas', FaenaController::class);
    Route::resource('ventas', VentaController::class);
    Route::resource('sacrificios', SacrificioController::class);
    Route::put('/ventas/{venta}/estados', [VentaController::class, 'updateEstado'])->name('ventas.update-estados');

    // Rutas API para estadísticas
    Route::get('/api/faenas/estadisticas', [FaenaController::class, 'estadisticas']);
    Route::get('/api/ventas/estadisticas', [VentaController::class, 'estadisticas']);
    Route::get('/api/sacrificios/estadisticas', [SacrificioController::class, 'estadisticas']);
    Route::get('/api/sacrificios/tendencias', [SacrificioController::class, 'tendencias']);

  

Route::middleware(['auth'])->group(function () {
    Route::get('/reproducciones', [ReproduccionController::class, 'index'])->name('reproducciones.index');
    Route::post('/reproducciones', [ReproduccionController::class, 'store'])->name('reproducciones.store');
    Route::put('/reproducciones/{reproduccion}', [ReproduccionController::class, 'update'])->name('reproducciones.update');
    Route::delete('/reproducciones/{reproduccion}', [ReproduccionController::class, 'destroy'])->name('reproducciones.destroy');
});

    /*
    |----------------------------------------------------------------------
    | Perfil (Breeze)
    |----------------------------------------------------------------------
    */
    Route::get('/profile',  [ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

        // 🔮 Módulo de Predicciones (Premium)
    Route::get('/predicciones', [PrediccionController::class, 'index'])
        ->name('predicciones.index');
});

/*
|--------------------------------------------------------------------------
| pagos
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/planes', [PaymentController::class, 'showPlans'])->name('planes.index');
    Route::get('/pago/premium', [PaymentController::class, 'createCheckout'])
    ->name('pago.premium');

    Route::get('/pago/success', [PaymentController::class, 'success'])->name('pago.success');
    Route::get('/pago/cancel', [PaymentController::class, 'cancel'])->name('pago.cancel');
});
/*
|--------------------------------------------------------------------------
| Auth scaffold (login, register, etc. de Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';
