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
use App\Http\Controllers\TareaController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\PrediccionController;
use App\Http\Controllers\PaymentController;
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
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\TermoController;
use App\Http\Controllers\PajillaController;
use App\Http\Controllers\DonadorExternoController;
use App\Http\Controllers\EstadisticasSaludController;


/*
|--------------------------------------------------------------------------
| Google OAuth
|--------------------------------------------------------------------------
*/
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

Route::get('/splash', [CustomController::class, 'splash'])->name('splash');

/*
|--------------------------------------------------------------------------
| Solo admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/usuarios', [AdminUserController::class, 'index'])
        ->name('admin.usuarios.index');
    Route::put('/admin/usuarios/{user}', [AdminUserController::class, 'update'])
        ->name('admin.usuarios.update');
});

/*
|--------------------------------------------------------------------------
| Rutas protegidas (auth + verified)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    /*
    |----------------------------------------------------------------------
    | Dashboard
    |----------------------------------------------------------------------
    */
    Route::get('/dashboard', [CustomController::class, 'home'])->name('dashboard');
    Route::get('/animals', [AnimalController::class, 'index'])->name('animals.custom');

    /*
    |----------------------------------------------------------------------
    | Módulo de Salud
    |----------------------------------------------------------------------
    */
    Route::get('/salud', [EventoSaludController::class, 'index'])->name('salud.index');
    Route::get('/eventos-salud', fn() => redirect()->route('salud.index'));

    Route::resource('vacunas', VacunaController::class);

    Route::post('eventos-salud/marcar-vencidos', [EventoSaludController::class, 'marcarVencidos'])
        ->name('eventos-salud.marcar-vencidos');
    Route::patch('eventos-salud/{eventoSalud}/aplicar', [EventoSaludController::class, 'aplicar'])
        ->name('eventos-salud.aplicar');
    Route::patch('eventos-salud/{eventoSalud}/completar', [EventoSaludController::class, 'completar'])
        ->name('eventos-salud.completar');
    Route::post('eventos-salud/{eventoSalud}/completar', [EventoSaludController::class, 'completar'])
        ->name('eventos-salud.completar.post');
    Route::resource('eventos-salud', EventoSaludController::class)->except(['index']);

    Route::post('tratamientos/marcar-vencidos', [TratamientoController::class, 'marcarVencidos'])
        ->name('tratamientos.marcar-vencidos');
    Route::patch('tratamientos/{tratamiento}/completar', [TratamientoController::class, 'completar'])
        ->name('tratamientos.completar');
    Route::resource('tratamientos', TratamientoController::class);
    Route::get('/salud/estadisticas', EstadisticasSaludController::class)
    ->name('salud.estadisticas');

    /*
    |----------------------------------------------------------------------
    | Animales
    |----------------------------------------------------------------------
    */
    Route::get('/animales', [AnimalController::class, 'index'])->name('animales.index');
    Route::get('/animales/create', [AnimalController::class, 'create'])->name('animales.create');
    Route::post('/animales', [AnimalController::class, 'store'])->name('animales.store');
    Route::get('/animales/{animal}', [AnimalController::class, 'show'])->name('animales.show');
    Route::get('/animales/{animal}/edit', [AnimalController::class, 'edit'])->name('animales.edit');
    Route::put('/animales/{animal}', [AnimalController::class, 'update'])->name('animales.update');
    Route::delete('/animales/{animal}', [AnimalController::class, 'destroy'])->name('animales.destroy');
    Route::get('/animales/{animal}/producciones', [ProduccionController::class, 'getProduccionesAnimal'])
        ->name('animales.producciones');

    /*
    |----------------------------------------------------------------------
    | Lotes
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
    | Producciones
    |----------------------------------------------------------------------
    */
    Route::get('/producciones', [ProduccionController::class, 'index'])->name('producciones.index');
    Route::get('/producciones/create', [ProduccionController::class, 'create'])->name('producciones.create');
    Route::post('/producciones', [ProduccionController::class, 'store'])->name('producciones.store');
    Route::get('/producciones/{produccion}', [ProduccionController::class, 'show'])->name('producciones.show');
    Route::get('/producciones/{produccion}/edit', [ProduccionController::class, 'edit'])->name('producciones.edit');
    Route::put('/producciones/{produccion}', [ProduccionController::class, 'update'])->name('producciones.update');
    Route::delete('/producciones/{produccion}', [ProduccionController::class, 'destroy'])->name('producciones.destroy');

    /*
    |----------------------------------------------------------------------
    | Alimentación
    |----------------------------------------------------------------------
    */
    Route::get('/alimentacion', [AlimentacionController::class, 'index'])->name('alimentacion.index');
    Route::post('/alimentacion', [AlimentacionController::class, 'store'])->name('alimentacion.store');
    Route::put('/alimentacion/{alimentacion}', [AlimentacionController::class, 'update'])->name('alimentacion.update');
    Route::delete('/alimentaciones/{alimentacion}', [AlimentacionController::class, 'destroy'])->name('alimentaciones.destroy');

    Route::post('/alimentacion/inventario', [InventarioInsumoController::class, 'store'])
        ->name('alimentacion.inventario.store');
    Route::put('/alimentacion/inventario/{item}', [InventarioInsumoController::class, 'update'])
        ->name('alimentacion.inventario.update');
    Route::put('/alimentacion/inventario/{item}/reabastecer', [InventarioInsumoController::class, 'reabastecer'])
        ->name('alimentacion.inventario.reabastecer');
    Route::patch('/alimentacion/inventario/{item}/reactivar', [InventarioInsumoController::class, 'reactivar'])
        ->name('alimentacion.inventario.reactivar');
    Route::delete('/alimentacion/inventario/{item}', [InventarioInsumoController::class, 'destroy'])
        ->name('alimentacion.inventario.destroy');

    Route::post('/raciones', [RacionController::class, 'store'])->name('raciones.store');
    Route::put('/raciones/{racion}', [RacionController::class, 'update'])->name('raciones.update');
    Route::delete('/raciones/{racion}', [RacionController::class, 'destroy'])->name('raciones.destroy');
    Route::post('/raciones/verificar-disponibilidad', [RacionController::class, 'verificarDisponibilidad'])
        ->name('raciones.verificarDisponibilidad');
    Route::patch('/raciones/{racion}/reactivar', [RacionController::class, 'reactivar'])
        ->name('raciones.reactivar');

    Route::get('/conversion-alimenticia', [ConversionAlimenticiaController::class, 'index'])
        ->name('conversion.index');

    Route::get('/programaciones-alimentacion', [ProgramacionAlimentacionController::class, 'index'])
        ->name('programaciones-alimentacion.index');
    Route::post('/programaciones-alimentacion', [ProgramacionAlimentacionController::class, 'store'])
        ->name('programaciones-alimentacion.store');
    Route::put('/programaciones-alimentacion/{programacionAlimentacion}', [ProgramacionAlimentacionController::class, 'update'])
        ->name('programaciones-alimentacion.update');
    Route::delete('/programaciones-alimentacion/{programacionAlimentacion}', [ProgramacionAlimentacionController::class, 'destroy'])
        ->name('programaciones-alimentacion.destroy');
    Route::patch('/programaciones-alimentacion/{programacionAlimentacion}/toggle-activa', [ProgramacionAlimentacionController::class, 'toggleActiva'])
        ->name('programaciones-alimentacion.toggleActiva');

    /*
    |----------------------------------------------------------------------
    | Pesajes
    |----------------------------------------------------------------------
    */
    Route::get('/pesajes', [PesajeController::class, 'index'])->name('pesajes.index');
    Route::post('/pesajes', [PesajeController::class, 'store'])->name('pesajes.store');
    Route::put('/pesajes/{pesaje}', [PesajeController::class, 'update'])->name('pesajes.update');
    Route::delete('/pesajes/{pesaje}', [PesajeController::class, 'destroy'])->name('pesajes.destroy');

    /*
    |----------------------------------------------------------------------
    | Faenas, Ventas, Sacrificios
    |----------------------------------------------------------------------
    */
    Route::resource('faenas', FaenaController::class);
    Route::resource('ventas', VentaController::class);
    Route::resource('sacrificios', SacrificioController::class);
    Route::put('/ventas/{venta}/estados', [VentaController::class, 'updateEstado'])
        ->name('ventas.update-estados');

    Route::get('/api/faenas/estadisticas', [FaenaController::class, 'estadisticas']);
    Route::get('/api/ventas/estadisticas', [VentaController::class, 'estadisticas']);
    Route::get('/api/sacrificios/estadisticas', [SacrificioController::class, 'estadisticas']);
    Route::get('/api/sacrificios/tendencias', [SacrificioController::class, 'tendencias']);

    /*
    |----------------------------------------------------------------------
    | Reproducción
    |----------------------------------------------------------------------
    */
    Route::get('/reproduccion', [EventoReproductivoController::class, 'index'])
        ->name('reproduccion.index');
    Route::get('/reproduccion/eventos/{eventoReproductivo}', [EventoReproductivoController::class, 'show'])
        ->name('reproduccion.eventos.show');
    Route::delete('/reproduccion/eventos/{eventoReproductivo}', [EventoReproductivoController::class, 'destroy'])
        ->name('reproduccion.eventos.destroy');
    Route::post('/reproduccion/servicios', [ServicioReproductivoController::class, 'store'])
        ->name('reproduccion.servicios.store');
    Route::post('/reproduccion/diagnosticos', [DiagnosticoGestacionController::class, 'store'])
        ->name('reproduccion.diagnosticos.store');
    Route::post('/reproduccion/partos', [PartoController::class, 'store'])
        ->name('reproduccion.partos.store');
    Route::get('/reproduccion/crias/{cria}', [CriaController::class, 'show'])
        ->name('reproduccion.crias.show');
    Route::patch('/reproduccion/crias/{cria}/asignar-arete', [CriaController::class, 'asignarArete'])
        ->name('reproduccion.crias.asignar-arete');

    Route::get('/api/reproduccion/estadisticas', [EventoReproductivoController::class, 'estadisticas'])
        ->name('reproduccion.estadisticas');
    Route::get('/api/reproduccion/alertas', [EventoReproductivoController::class, 'alertas'])
        ->name('reproduccion.alertas');
    Route::get('/api/reproduccion/calendario', [EventoReproductivoController::class, 'calendario'])
        ->name('reproduccion.calendario');

    /*
    |----------------------------------------------------------------------
    | Genealogías
    |----------------------------------------------------------------------
    */
    Route::get('/genealogias/{animal}', [GenealogiasController::class, 'show'])
        ->name('genealogias.show');


 /*
    |----------------------------------------------------------------------
    | Recordatorios
    |----------------------------------------------------------------------
    */
    Route::middleware(['auth'])->group(function () {
        Route::get('/tareas', [TareaController::class, 'index'])
            ->name('tareas.index');
    
        Route::post('/tareas', [TareaController::class, 'store'])
            ->name('tareas.store');
    
        Route::put('/tareas/{tarea}', [TareaController::class, 'update'])
            ->name('tareas.update');
    
        Route::patch('/tareas/{tarea}/completar', [TareaController::class, 'completar'])
            ->name('tareas.completar');
    
        Route::patch('/tareas/{tarea}/suspender', [TareaController::class, 'suspender'])
            ->name('tareas.suspender');
    
        Route::patch('/tareas/{tarea}/reactivar', [TareaController::class, 'reactivar'])
            ->name('tareas.reactivar');
    
        Route::delete('/tareas/{tarea}', [TareaController::class, 'destroy'])
            ->name('tareas.destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Material Genético (Termos + Pajillas)
    |----------------------------------------------------------------------
    */
    Route::resource('termos', TermoController::class)->except(['index']);
    Route::resource('pajillas', PajillaController::class)->except(['index', 'create', 'edit', 'show']);
    Route::post('/donadores-externos',[DonadorExternoController::class, 'store'])->name('donadores-externos.store');
    Route::get('/genetica', [TermoController::class, 'index'])
    ->name('genetica.index');
    /*
    |----------------------------------------------------------------------
    | Predicciones
    |----------------------------------------------------------------------
    */
    Route::get('/predicciones', [PrediccionController::class, 'index'])
        ->name('predicciones.index');

    /*
    |----------------------------------------------------------------------
    | Perfil
    |----------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |----------------------------------------------------------------------
    | Pagos
    |----------------------------------------------------------------------
    */
    Route::get('/planes', [PaymentController::class, 'showPlans'])->name('planes.index');
    Route::get('/pago/premium', [PaymentController::class, 'createCheckout'])->name('pago.premium');
    Route::get('/pago/success', [PaymentController::class, 'success'])->name('pago.success');
    Route::get('/pago/cancel', [PaymentController::class, 'cancel'])->name('pago.cancel');
});

/*
|--------------------------------------------------------------------------
| Reportes (sin middleware propio, ajusta si necesitas protegerlo)
|--------------------------------------------------------------------------
*/
Route::prefix('reportes')->name('reportes.')->group(function () {
    Route::get('/', [ReportesController::class, 'index'])->name('index');
    Route::get('/exportar/pdf', [ReportesController::class, 'exportarPdf'])->name('pdf');
    Route::get('/exportar/xml', [ReportesController::class, 'exportarXml'])->name('xml');
    Route::get('/ficha/pdf', [ReportesController::class, 'exportarFichaPdf'])->name('ficha.pdf');
});

/*
|--------------------------------------------------------------------------
| Imagenes
|--------------------------------------------------------------------------
*/
Route::post('/animales/{animal}/imagen', [AnimalController::class, 'imagen'])->name('animales.imagen');
Route::delete('/animales/{animal}/imagen', [AnimalController::class, 'eliminarImagen'])->name('animales.imagen.eliminar');
Route::post('/animales/{animal}/imagen', [AnimalController::class, 'guardarImagen'])
    ->name('animales.imagen');
/*
|--------------------------------------------------------------------------
| Auth scaffold (Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';