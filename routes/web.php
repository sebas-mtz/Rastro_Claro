<?php
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomController;
use App\Http\Controllers\EventoSaludController;
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
use App\Http\Controllers\EventoReproductivoController;
use App\Http\Controllers\ServicioReproductivoController;
use App\Http\Controllers\DiagnosticoGestacionController;
use App\Http\Controllers\PartoController;
use App\Http\Controllers\CriaController;
use App\Http\Controllers\RacionController;
use App\Http\Controllers\ProgramacionAlimentacionController;
use App\Http\Controllers\PesajeController;
use App\Http\Controllers\TratamientoController;
use App\Http\Controllers\VacunaController;
use App\Http\Controllers\ConversionAlimenticiaController;
use App\Http\Controllers\GenealogiasController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\PrediccionController;
use App\Http\Controllers\TrabajadorController;
use App\Http\Controllers\CostoController;


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
   // Vista principal del módulo de salud
Route::get('/salud', [EventoSaludController::class, 'index'])->name('salud.index');
// Redirige cualquier GET a /eventos-salud hacia el dashboard de salud
Route::get('/eventos-salud', fn() => redirect()->route('salud.index'));
// Catálogo de vacunas
Route::resource('vacunas', VacunaController::class);

// Acciones custom antes del resource para evitar colisiones de rutas
Route::post('eventos-salud/marcar-vencidos', [EventoSaludController::class, 'marcarVencidos'])
     ->name('eventos-salud.marcar-vencidos');
Route::patch('eventos-salud/{eventoSalud}/aplicar', [EventoSaludController::class, 'aplicar'])
     ->name('eventos-salud.aplicar');
Route::patch('eventos-salud/{eventoSalud}/completar', [EventoSaludController::class, 'completar'])
     ->name('eventos-salud.completar');

// Resource sin index (el index es /salud)
Route::resource('eventos-salud', EventoSaludController::class)
     ->except(['index']);

// Tratamientos
Route::post('tratamientos/marcar-vencidos', [TratamientoController::class, 'marcarVencidos'])
     ->name('tratamientos.marcar-vencidos');
Route::patch('tratamientos/{tratamiento}/completar', [TratamientoController::class, 'completar'])
     ->name('tratamientos.completar');
Route::resource('tratamientos', TratamientoController::class);
    
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
    Route::get('/producciones/create', [ProduccionController::class, 'create'])->name('producciones.create');
    Route::post('/producciones', [ProduccionController::class, 'store'])->name('producciones.store');
    Route::get('/producciones/{produccion}', [ProduccionController::class, 'show'])->name('producciones.show');
    Route::get('/producciones/{produccion}/edit', [ProduccionController::class, 'edit'])->name('producciones.edit');
    Route::put('/producciones/{produccion}', [ProduccionController::class, 'update'])->name('producciones.update');
    Route::delete('/producciones/{produccion}', [ProduccionController::class, 'destroy'])->name('producciones.destroy');
    
    // 🔥 Ruta extra (producciones por animal)
    Route::get('/animales/{animal}/producciones', [ProduccionController::class, 'getProduccionesAnimal'])
        ->name('animales.producciones');


    /*
    |----------------------------------------------------------------------
    | CRUD Alimentación (Raciones) (AlimentacionController)
    |----------------------------------------------------------------------
    */
    Route::get('/alimentacion', [AlimentacionController::class, 'index'])->name('alimentacion.index');
    Route::post('/alimentacion', [AlimentacionController::class, 'store'])->name('alimentacion.store');
    Route::put('/alimentacion/{alimentacion}', [AlimentacionController::class, 'update'])->name('alimentacion.update');
    Route::delete('/alimentaciones/{alimentacion}', [AlimentacionController::class, 'destroy'])->name('alimentaciones.destroy');
    // Crear nuevo alimento en inventario
    Route::post('/alimentacion/inventario', [InventarioInsumoController::class, 'store'])->name('alimentacion.inventario.store');
Route::put('/alimentacion/inventario/{item}', [InventarioInsumoController::class, 'update'])
->name('alimentacion.inventario.update');
    // Reabastecer (la que ya teníamos)
    Route::put('/alimentacion/inventario/{item}/reabastecer',[InventarioInsumoController::class, 'reabastecer'])->name('alimentacion.inventario.reabastecer');
    Route::patch('alimentacion/inventario/{item}/reactivar', [InventarioInsumoController::class, 'reactivar'])
    ->name('alimentacion.inventario.reactivar');
    Route::delete('/alimentacion/inventario/{item}', [InventarioInsumoController::class, 'destroy'])
    ->name('alimentacion.inventario.destroy');
Route::post('/raciones', [RacionController::class, 'store'])->name('raciones.store');
Route::put('/raciones/{racion}', [RacionController::class, 'update'])->name('raciones.update');
Route::delete('/raciones/{racion}', [RacionController::class, 'destroy'])->name('raciones.destroy');
Route::post('/raciones/verificar-disponibilidad', [RacionController::class, 'verificarDisponibilidad'])
    ->name('raciones.verificarDisponibilidad');
    Route::patch('raciones/{racion}/reactivar', [RacionController::class, 'reactivar'])
    ->name('raciones.reactivar');
 
     
Route::get('/conversion-alimenticia', [ConversionAlimenticiaController::class, 'index'])
->name('conversion.index');

    //Para programación de consumos
    Route::get('/programaciones-alimentacion', [ProgramacionAlimentacionController::class, 'index'])->name('programaciones-alimentacion.index');
Route::post('/programaciones-alimentacion', [ProgramacionAlimentacionController::class, 'store'])->name('programaciones-alimentacion.store');
Route::put('/programaciones-alimentacion/{programacionAlimentacion}', [ProgramacionAlimentacionController::class, 'update'])->name('programaciones-alimentacion.update');
Route::delete('/programaciones-alimentacion/{programacionAlimentacion}', [ProgramacionAlimentacionController::class, 'destroy'])->name('programaciones-alimentacion.destroy');
Route::patch('/programaciones-alimentacion/{programacionAlimentacion}/toggle-activa', [ProgramacionAlimentacionController::class, 'toggleActiva'])->name('programaciones-alimentacion.toggleActiva');
    

//pesajes
Route::get('/pesajes', [PesajeController::class, 'index'])->name('pesajes.index');
Route::post('/pesajes', [PesajeController::class, 'store'])->name('pesajes.store');
Route::put('/pesajes/{pesaje}', [PesajeController::class, 'update'])->name('pesajes.update');
Route::delete('/pesajes/{pesaje}', [PesajeController::class, 'destroy'])->name('pesajes.destroy');
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

  // Vista principal
  Route::get('/reproduccion', [EventoReproductivoController::class, 'index'])
  ->name('reproduccion.index');

// Detalle de un evento
Route::get('/reproduccion/eventos/{eventoReproductivo}', [EventoReproductivoController::class, 'show'])
  ->name('reproduccion.eventos.show');

// Eliminar evento
Route::delete('/reproduccion/eventos/{eventoReproductivo}', [EventoReproductivoController::class, 'destroy'])
  ->name('reproduccion.eventos.destroy');

// Servicios (monta natural / IA / IATF)
Route::post('/reproduccion/servicios', [ServicioReproductivoController::class, 'store'])
  ->name('reproduccion.servicios.store');

// Diagnósticos de gestación
Route::post('/reproduccion/diagnosticos', [DiagnosticoGestacionController::class, 'store'])
  ->name('reproduccion.diagnosticos.store');

// Partos
Route::post('/reproduccion/partos', [PartoController::class, 'store'])
  ->name('reproduccion.partos.store');

// Crías
Route::get('/reproduccion/crias/{cria}', [CriaController::class, 'show'])
  ->name('reproduccion.crias.show');

Route::patch('/reproduccion/crias/{cria}/asignar-arete', [CriaController::class, 'asignarArete'])
  ->name('reproduccion.crias.asignar-arete');

// Estadísticas, alertas y calendario — llamadas AJAX desde el frontend
Route::get('/api/reproduccion/estadisticas', [EventoReproductivoController::class, 'estadisticas'])
  ->name('reproduccion.estadisticas');

Route::get('/api/reproduccion/alertas', [EventoReproductivoController::class, 'alertas'])
  ->name('reproduccion.alertas');

Route::get('/api/reproduccion/calendario', [EventoReproductivoController::class, 'calendario'])
  ->name('reproduccion.calendario');

Route::middleware(['auth'])->group(function () {
   
});

//Genealogías
Route::get('/genealogias/{animal}', [GenealogiasController::class, 'show'])
    ->name('genealogias.show');    /*
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


// ============================================================
// SECCIÓN DE PAGOS Y PLANES — reemplaza el bloque de pagos
// ============================================================

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook');

/*
|--------------------------------------------------------------------------
| Planes y pagos (requiere auth)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // Ver planes (accesible para todos los usuarios)
    Route::get('/planes', [PaymentController::class, 'showPlans'])
        ->name('planes.index');

    // Iniciar checkout (solo si NO es ya premium)
    Route::get('/pago/premium', [PaymentController::class, 'createCheckout'])
        ->name('pago.premium');

    // Stripe redirige aquí tras pago exitoso
    Route::get('/pago/success', [PaymentController::class, 'success'])
        ->name('pago.success');

    // Stripe redirige aquí si el usuario cancela
    Route::get('/pago/cancel', [PaymentController::class, 'cancel'])
        ->name('pago.cancel');
});

/*
|--------------------------------------------------------------------------
| Rutas Premium — protegidas por middleware 'plan:premium'
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'plan:premium'])->group(function () {

    Route::get('/costos', [CostoController::class, 'index'])
        ->name('costos.index');

    // Agrega aquí cualquier otra ruta que requiera Premium:
    // Route::get('/reportes-avanzados', [...]);
});

// ============================================================
// TAMBIÉN AGREGA ESTO en bootstrap/app.php dentro de
// ->withMiddleware(function (Middleware $middleware) {
//
//   $middleware->alias([
//       'role' => \App\Http\Middleware\CheckRole::class,
//       'plan' => \App\Http\Middleware\CheckPlan::class,   // ← NUEVA
//   ]);
//
//   $middleware->validateCsrfTokens(except: [
//       'stripe/webhook',   // ← EXCLUIR de CSRF
//   ]);
//
// });
// ============================================================

/*
|--------------------------------------------------------------------------
| Auth scaffold (login, register, etc. de Breeze)
|--------------------------------------------------------------------------
*/
// ── Módulo de Trabajadores (requiere auth) ──────────────────
Route::middleware(['auth', 'verified', 'role:administrador'])->group(function () {
    Route::get('/trabajadores', [TrabajadorController::class, 'index'])
        ->name('trabajadores.index');
    Route::post('/trabajadores', [TrabajadorController::class, 'store'])
        ->name('trabajadores.store');
    Route::put('/trabajadores/{trabajador}', [TrabajadorController::class, 'update'])
        ->name('trabajadores.update');
    Route::patch('/trabajadores/{trabajador}/toggle-activo', [TrabajadorController::class, 'toggleActivo'])
        ->name('trabajadores.toggle-activo');
    Route::get('/trabajadores/{trabajador}/historial', [TrabajadorController::class, 'historial'])
        ->name('trabajadores.historial');
    Route::delete('/trabajadores/{trabajador}', [TrabajadorController::class, 'destroy'])
        ->name('trabajadores.destroy');
    Route::get('/historial-actividad', [TrabajadorController::class, 'historialGlobal'])
        ->name('trabajadores.historial-global');
});
// ── Cambio obligatorio de contraseña (primer login) ────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/cambiar-password', [\App\Http\Controllers\CambiarPasswordController::class, 'show'])
        ->name('password.cambiar');
    Route::post('/cambiar-password', [\App\Http\Controllers\CambiarPasswordController::class, 'update'])
        ->name('password.cambiar.update');
});

require __DIR__ . '/auth.php';