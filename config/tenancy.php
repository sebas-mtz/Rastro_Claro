<?php

use App\Models\ActividadTrabajador;
use App\Models\Alerta;
use App\Models\Alimentacion;
use App\Models\Animal;
use App\Models\Baja;
use App\Models\AnimalGenetica;
use App\Models\AnimalValuation;
use App\Models\AnimalValuationDetalle;
use App\Models\AnimalValuationHistorial;
use App\Models\Comprador;
use App\Models\CondicionCorporal;
use App\Models\ConfiguracionValuacion;
use App\Models\Costo;
use App\Models\Cria;
use App\Models\DiagnosticoGestacion;
use App\Models\Documento;
use App\Models\DonadorExterno;
use App\Models\EventoReproductivo;
use App\Models\EventoSalud;
use App\Models\Faena;
use App\Models\InventarioInsumo;
use App\Models\Lote;
use App\Models\MovimientoLote;
use App\Models\Pajilla;
use App\Models\Parto;
use App\Models\Pesaje;
use App\Models\Produccion;
use App\Models\ProgramacionAlimentacion;
use App\Models\PuestoTrabajador;
use App\Models\Racion;
use App\Models\Raza;
use App\Models\Reporte;
use App\Models\Sacrificio;
use App\Models\ServicioReproductivo;
use App\Models\Tarea;
use App\Models\Termo;
use App\Models\Trabajador;
use App\Models\Tratamiento;
use App\Models\Vacuna;
use App\Models\Venta;

return [
    /*
    | Every model in this list is private to the authenticated account.
    | AppServiceProvider applies the owner scope and owner assignment centrally,
    | so controllers and route-model binding cannot accidentally bypass it.
    */
    'models' => [
        ActividadTrabajador::class,
        Alerta::class,
        Alimentacion::class,
        Animal::class,
        Baja::class,
        AnimalGenetica::class,
        AnimalValuation::class,
        AnimalValuationDetalle::class,
        AnimalValuationHistorial::class,
        Comprador::class,
        CondicionCorporal::class,
        ConfiguracionValuacion::class,
        Costo::class,
        Cria::class,
        DiagnosticoGestacion::class,
        Documento::class,
        DonadorExterno::class,
        EventoReproductivo::class,
        EventoSalud::class,
        Faena::class,
        InventarioInsumo::class,
        Lote::class,
        MovimientoLote::class,
        Pajilla::class,
        Parto::class,
        Pesaje::class,
        Produccion::class,
        ProgramacionAlimentacion::class,
        PuestoTrabajador::class,
        Racion::class,
        Raza::class,
        Reporte::class,
        Sacrificio::class,
        ServicioReproductivo::class,
        Tarea::class,
        Termo::class,
        Trabajador::class,
        Tratamiento::class,
        Vacuna::class,
        Venta::class,
    ],

    /*
    | Tablas preexistentes que necesitaron una migración retroactiva para
    | agregar owner_id (ver 2026_07_19_000000_add_owner_id_to_tenant_tables.php)
    | y que participan en tenancy:claim-legacy / TenantPresenceVerifier.
    | Nota: 'costos' NO va aquí a propósito — su migración de creación ya
    | define owner_id desde el inicio, así que no hay nada que "reclamar"
    | y agregarla aquí rompería esa migración retroactiva (correría antes
    | de que la tabla costos exista).
    */
    'tables' => [
        'alertas',
        'alimentacions',
        'animals',
        'compradores',
        'crias',
        'diagnostico_gestacions',
        'donadores_externos',
        'evento_reproductivos',
        'eventos_salud',
        'faenas',
        'inventario_insumos',
        'lotes',
        'pajillas',
        'partos',
        'pesajes',
        'produccions',
        'programacion_alimentacions',
        'racions',
        'reportes',
        'sacrificios',
        'servicio_reproductivos',
        'tareas',
        'termos',
        'tratamientos',
        'vacunas',
        'ventas',
    ],
];
