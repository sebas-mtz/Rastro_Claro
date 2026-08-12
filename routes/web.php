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
use App\Http\Controllers\ConfiguracionLectorController;
use Illuminate\Foundation\Application;
use App\Http\Controllers\TareaController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\Admin\PermisoController as AdminPermisoController;
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
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\TermoController;
use App\Http\Controllers\PajillaController;
use App\Http\Controllers\DonadorExternoController;
use App\Http\Controllers\EstadisticasSaludController;
use App\Http\Controllers\CostoController;
use App\Http\Controllers\AnimalValuationController;
use App\Http\Controllers\BajaController;
use App\Http\Controllers\TrabajadorController;
use App\Http\Controllers\ActividadTrabajadorController;
use App\Http\Controllers\CalendarioSanitarioController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\ReporteOvinoController;


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
| Solo superadministrador
|--------------------------------------------------------------------------
| Administración de cuentas, roles, contraseñas y bitácora del sistema.
|
| Antes este grupo usaba `role:admin`, lo que permitía a cualquier
| administrador cambiar roles y planes de todas las cuentas. Ahora exige
| `super_admin`; los nombres de las rutas se conservan para no romper los
| enlaces existentes.
*/
Route::middleware(['auth', 'verified', 'super_admin'])->group(function () {
    Route::get('/admin/usuarios', [AdminUserController::class, 'index'])
        ->name('admin.usuarios.index');
    Route::post('/admin/usuarios', [AdminUserController::class, 'store'])
        ->name('admin.usuarios.store');
    Route::put('/admin/usuarios/{user}', [AdminUserController::class, 'update'])
        ->name('admin.usuarios.update');
    Route::patch('/admin/usuarios/{user}/estado', [AdminUserController::class, 'cambiarEstado'])
        ->name('admin.usuarios.estado');
    Route::patch('/admin/usuarios/{user}/password', [AdminUserController::class, 'restablecerPassword'])
        ->name('admin.usuarios.password');
    Route::delete('/admin/usuarios/{user}', [AdminUserController::class, 'destroy'])
        ->name('admin.usuarios.destroy');

    // Bitácora: solo lectura. No existe ruta para editarla ni borrarla.
    Route::get('/admin/auditoria', [AdminUserController::class, 'auditoria'])
        ->name('admin.auditoria.index');

    // Permisos: qué módulos toca cada puesto y las excepciones por persona.
    Route::get('/admin/permisos', [AdminPermisoController::class, 'index'])
        ->name('admin.permisos.index');
    Route::put('/admin/permisos/puesto/{puesto}', [AdminPermisoController::class, 'actualizarPuesto'])
        ->name('admin.permisos.puesto');
    Route::put('/admin/permisos/persona/{user}', [AdminPermisoController::class, 'actualizarPersona'])
        ->name('admin.permisos.persona');
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
    // ->parameters() es necesario: por el guion, el recurso generaba el parámetro
    // {eventos_salud}, que no coincide con el $eventoSalud del controlador. Sin esa
    // coincidencia el enlace implícito no ocurría y update()/destroy() recibían un
    // modelo vacío, así que editar o eliminar un evento no hacía nada.
    Route::resource('eventos-salud', EventoSaludController::class)
        ->except(['index'])
        ->parameters(['eventos-salud' => 'eventoSalud']);

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
    // Sin ruta /animales/create: el alta se hace por modal desde el listado.
    Route::post('/animales', [AnimalController::class, 'store'])->name('animales.store');
    Route::get('/animales/{animal}', [AnimalController::class, 'show'])->name('animales.show');
    Route::get('/animales/{animal}/edit', [AnimalController::class, 'edit'])->name('animales.edit');
    Route::put('/animales/{animal}', [AnimalController::class, 'update'])->name('animales.update');
    Route::delete('/animales/{animal}', [AnimalController::class, 'destroy'])->name('animales.destroy');
    Route::get('/animales/{animal}/producciones', [ProduccionController::class, 'getProduccionesAnimal'])
        ->name('animales.producciones');

    /*
    |----------------------------------------------------------------------
    | Identificación (microchip / RFID / QR)
    |----------------------------------------------------------------------
    */
    // Nota: NO se usa el prefijo /api/animales/... a propósito: routes/api.php (capa
    // móvil sin comitear, fuera de alcance) ya registra GET /api/animales/{animale},
    // y esa ruta comodín capturaría "buscar-identificador" como si fuera un ID.
    Route::get('/api/identificadores/buscar', [AnimalController::class, 'buscarPorIdentificador'])
        ->name('animales.buscar-identificador');
    Route::post('/animales/{animal}/identificador', [AnimalController::class, 'registrarIdentificador'])
        ->name('animales.identificador.store');
    Route::get('/animales/{animal}/qr', [AnimalController::class, 'qr'])
        ->name('animales.qr');
    Route::get('/escanear/{token}', [AnimalController::class, 'escanearQr'])
        ->name('animales.escanear');

    // Diagnóstico del lector. Sin permiso de módulo a propósito: quien tiene
    // que probar el equipo suele ser justamente quien todavía no puede entrar
    // a nada, y la pantalla no consulta ni un solo dato del rancho.
    Route::get('/herramientas/diagnostico-lector', function () {
        return Inertia::render('Herramientas/DiagnosticoLector', [
            // El navegador aplica las mismas reglas que el servidor, para que
            // lo que muestra el diagnóstico sea lo que de verdad se guardaría.
            'configuracion' => \App\Models\ConfiguracionLector::first()?->paraNavegador(),
        ]);
    })->name('herramientas.diagnostico-lector');

    // Ajustes del lector: los edita el dueño del rancho, no el
    // superadministrador. El controlador explica por qué.
    Route::get('/herramientas/lector', [ConfiguracionLectorController::class, 'show'])
        ->name('herramientas.lector');
    Route::put('/herramientas/lector', [ConfiguracionLectorController::class, 'update'])
        ->name('herramientas.lector.update');
    Route::post('/herramientas/lector/probar', [ConfiguracionLectorController::class, 'probar'])
        ->name('herramientas.lector.probar');

    /*
    |----------------------------------------------------------------------
    | Calendario sanitario y reportes ovinos
    |----------------------------------------------------------------------
    */
    Route::get('/calendario-sanitario', [CalendarioSanitarioController::class, 'index'])
        ->name('calendario.index');
    Route::get('/reportes-ovinos', [ReporteOvinoController::class, 'index'])
        ->name('reportes.ovinos');

    /*
    |----------------------------------------------------------------------
    | Documentos y evidencias
    |----------------------------------------------------------------------
    */
    Route::post('/documentos', [DocumentoController::class, 'store'])->name('documentos.store');
    Route::get('/documentos/{documento}/descargar', [DocumentoController::class, 'download'])->name('documentos.download');
    Route::delete('/documentos/{documento}', [DocumentoController::class, 'destroy'])->name('documentos.destroy');

    /*
    |----------------------------------------------------------------------
    | Bajas y salidas del rebaño
    |----------------------------------------------------------------------
    */
    Route::get('/bajas', [BajaController::class, 'index'])->name('bajas.index');
    Route::post('/bajas', [BajaController::class, 'store'])->name('bajas.store');
    Route::delete('/bajas/{baja}', [BajaController::class, 'destroy'])->name('bajas.destroy');

    /*
    |----------------------------------------------------------------------
    | Trabajadores y mano de obra
    |----------------------------------------------------------------------
    | El acceso a cada acción lo resuelve TrabajadorPolicy: consultar y
    | registrar actividades es de operación; alta, edición, cambio de estado
    | y datos salariales exigen rol de administrador.
    */
    Route::get('/trabajadores', [TrabajadorController::class, 'index'])
        ->name('trabajadores.index');
    Route::post('/trabajadores', [TrabajadorController::class, 'store'])
        ->name('trabajadores.store');
    Route::get('/trabajadores/{trabajador}', [TrabajadorController::class, 'show'])
        ->name('trabajadores.show');
    Route::put('/trabajadores/{trabajador}', [TrabajadorController::class, 'update'])
        ->name('trabajadores.update');
    Route::patch('/trabajadores/{trabajador}/estado', [TrabajadorController::class, 'cambiarEstado'])
        ->name('trabajadores.estado');
    Route::delete('/trabajadores/{trabajador}', [TrabajadorController::class, 'destroy'])
        ->name('trabajadores.destroy');

    Route::get('/actividades-trabajador', [ActividadTrabajadorController::class, 'index'])
        ->name('actividades-trabajador.index');
    Route::post('/actividades-trabajador', [ActividadTrabajadorController::class, 'store'])
        ->name('actividades-trabajador.store');
    Route::put('/actividades-trabajador/{actividad}', [ActividadTrabajadorController::class, 'update'])
        ->name('actividades-trabajador.update');
    Route::delete('/actividades-trabajador/{actividad}', [ActividadTrabajadorController::class, 'destroy'])
        ->name('actividades-trabajador.destroy');
    // Vista previa del importe; no persiste nada.
    Route::post('/actividades-trabajador/calcular', [ActividadTrabajadorController::class, 'calcular'])
        ->name('actividades-trabajador.calcular');

    /*
    |----------------------------------------------------------------------
    | Valuación y cotización
    |----------------------------------------------------------------------
    */
    Route::get('/valuaciones/{animal}', [AnimalValuationController::class, 'show'])
        ->name('valuaciones.show');
    Route::post('/valuaciones/{animal}/recalcular', [AnimalValuationController::class, 'recalcular'])
        ->name('valuaciones.recalcular');
    Route::post('/valuaciones/{animal}/simular', [AnimalValuationController::class, 'simular'])
        ->name('valuaciones.simular');
    Route::post('/valuaciones/{animal}/guardar', [AnimalValuationController::class, 'guardar'])
        ->name('valuaciones.guardar');
    Route::post('/valuaciones/{animal}/confirmar-venta', [AnimalValuationController::class, 'confirmarPrecioVenta'])
        ->name('valuaciones.confirmar-venta');
    Route::get('/valuaciones/{animal}/pdf', [AnimalValuationController::class, 'exportarPdf'])
        ->name('valuaciones.pdf');
    // Los valores del plus reproductivo entran en la fórmula del precio de
    // toda la explotación. Estaba abierta a cualquier usuario autenticado;
    // ahora es una modificación crítica y exige superadministrador.
    Route::put('/valuaciones-configuracion', [AnimalValuationController::class, 'actualizarConfiguracion'])
        ->middleware('super_admin')
        ->name('valuaciones.configuracion');

    /*
    |----------------------------------------------------------------------
    | Lotes
    |----------------------------------------------------------------------
    */
    Route::get('/lotes', [LoteController::class, 'index'])->name('lotes.index');
    // Sin ruta /lotes/create: el alta se hace por modal desde el listado.
    Route::post('/lotes', [LoteController::class, 'store'])->name('lotes.store');
    // Sin ruta /lotes/{lote}: el detalle se muestra en un modal del listado.
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
    // VentaController solo implementa index y store; el resto del recurso
    // apuntaba a métodos inexistentes y devolvía 500 al visitarlo.
    Route::resource('ventas', VentaController::class)->only(['index', 'store']);
    Route::resource('sacrificios', SacrificioController::class);
    Route::put('/ventas/{venta}/estados', [VentaController::class, 'updateEstado'])
        ->name('ventas.update-estados');

    Route::get('/api/faenas/estadisticas', [FaenaController::class, 'estadisticas']);
    Route::get('/api/ventas/estadisticas', [VentaController::class, 'estadisticas']);
    Route::get('/api/sacrificios/estadisticas', [SacrificioController::class, 'estadisticas']);
    Route::get('/api/sacrificios/tendencias', [SacrificioController::class, 'tendencias']);

    /*
    |----------------------------------------------------------------------
    | Costos
    |----------------------------------------------------------------------
    */
    Route::resource('costos', CostoController::class)->except(['create', 'edit', 'show']);
    Route::get('/costos/exportar/csv', [CostoController::class, 'exportarCsv'])->name('costos.exportar.csv');
    Route::get('/costos/exportar/pdf', [CostoController::class, 'exportarPdf'])->name('costos.exportar.pdf');
    Route::get('/api/costos/resumen', [CostoController::class, 'resumen'])->name('costos.resumen');

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
    | Perfil
    |----------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

/*
|--------------------------------------------------------------------------
| Reportes protegidos
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->prefix('reportes')->name('reportes.')->group(function () {
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
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/animales/{animal}/imagen', [AnimalController::class, 'imagen'])->name('animales.imagen');
    Route::delete('/animales/{animal}/imagen', [AnimalController::class, 'eliminarImagen'])->name('animales.imagen.eliminar');
});
/*
|--------------------------------------------------------------------------
| Auth scaffold (Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';
