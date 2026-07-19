<?php

use App\Models\Alerta;
use App\Models\Alimentacion;
use App\Models\Animal;
use App\Models\Comprador;
use App\Models\Cria;
use App\Models\DiagnosticoGestacion;
use App\Models\DonadorExterno;
use App\Models\EventoReproductivo;
use App\Models\EventoSalud;
use App\Models\Faena;
use App\Models\InventarioInsumo;
use App\Models\Lote;
use App\Models\Pajilla;
use App\Models\Parto;
use App\Models\Pesaje;
use App\Models\Produccion;
use App\Models\ProgramacionAlimentacion;
use App\Models\Racion;
use App\Models\Reporte;
use App\Models\Sacrificio;
use App\Models\ServicioReproductivo;
use App\Models\Tarea;
use App\Models\Termo;
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
        Alerta::class,
        Alimentacion::class,
        Animal::class,
        Comprador::class,
        Cria::class,
        DiagnosticoGestacion::class,
        DonadorExterno::class,
        EventoReproductivo::class,
        EventoSalud::class,
        Faena::class,
        InventarioInsumo::class,
        Lote::class,
        Pajilla::class,
        Parto::class,
        Pesaje::class,
        Produccion::class,
        ProgramacionAlimentacion::class,
        Racion::class,
        Reporte::class,
        Sacrificio::class,
        ServicioReproductivo::class,
        Tarea::class,
        Termo::class,
        Tratamiento::class,
        Vacuna::class,
        Venta::class,
    ],

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
