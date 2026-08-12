<?php

namespace App\Support;

/**
 * Qué módulos existen, qué se puede hacer en cada uno y a qué rutas
 * corresponden.
 *
 * Es la única fuente de verdad de los permisos. El middleware, los Gates, el
 * menú lateral y la pantalla de administración leen todos de aquí, para que no
 * puedan desincronizarse.
 */
class ModuloSistema
{
    // ── Acciones ──────────────────────────────────────────────────────────
    public const VER = 'ver';
    public const REGISTRAR = 'registrar';
    public const EDITAR = 'editar';
    public const ELIMINAR = 'eliminar';

    public const ACCIONES = [
        self::VER => 'Consultar',
        self::REGISTRAR => 'Registrar',
        self::EDITAR => 'Editar',
        self::ELIMINAR => 'Eliminar',
    ];

    /** Todas las acciones, para los puestos con mando completo. */
    public const TODAS = [self::VER, self::REGISTRAR, self::EDITAR, self::ELIMINAR];

    // ── Módulos ───────────────────────────────────────────────────────────
    public const ANIMALES = 'animales';
    public const LOTES = 'lotes';
    public const PESAJES = 'pesajes';
    public const ALIMENTACION = 'alimentacion';
    public const SALUD = 'salud';
    public const REPRODUCCION = 'reproduccion';
    public const PRODUCCIONES = 'producciones';
    public const BAJAS = 'bajas';
    public const TRABAJADORES = 'trabajadores';
    public const COSTOS = 'costos';
    public const VALUACION = 'valuacion';
    public const VENTAS = 'ventas';
    public const FAENAS = 'faenas';
    public const REPORTES = 'reportes';
    public const TAREAS = 'tareas';
    public const DOCUMENTOS = 'documentos';

    /**
     * Nombre y descripción de cada módulo, tal como se muestran al configurar
     * un puesto.
     */
    public const MODULOS = [
        self::ANIMALES => ['Ejemplares', 'Altas, fichas, identificadores y genealogía del rebaño'],
        self::LOTES => ['Lotes', 'Corrales, potreros y movimientos entre lotes'],
        self::PESAJES => ['Pesajes', 'Pesos y condición corporal'],
        self::ALIMENTACION => ['Alimentación', 'Raciones, consumo e inventario de insumos'],
        self::SALUD => ['Salud', 'Vacunas, tratamientos y calendario sanitario'],
        self::REPRODUCCION => ['Reproducción', 'Servicios, gestaciones, partos, crías y genética'],
        self::PRODUCCIONES => ['Producción', 'Registro de producción'],
        self::BAJAS => ['Bajas', 'Salidas del rebaño'],
        self::TRABAJADORES => ['Trabajadores', 'Personal del rancho y sus actividades'],
        self::COSTOS => ['Costos', 'Gastos del rancho — información económica'],
        self::VALUACION => ['Valuación', 'Precios estimados y cotizaciones — información económica'],
        self::VENTAS => ['Ventas', 'Ventas y compradores — información económica'],
        self::FAENAS => ['Faena y sacrificio', 'Faenas, sacrificios y rendimientos'],
        self::REPORTES => ['Reportes', 'Informes e indicadores del rebaño'],
        self::TAREAS => ['Tareas', 'Pendientes y recordatorios'],
        self::DOCUMENTOS => ['Documentos', 'Evidencias y archivos adjuntos'],
    ];

    /**
     * Módulos con información económica.
     *
     * Vienen apagados en TODOS los puestos: se conceden a mano, uno por uno.
     * Un empleado no ve el dinero del rancho salvo que se decida lo contrario.
     */
    public const ECONOMICOS = [self::COSTOS, self::VALUACION, self::VENTAS];

    /**
     * Prefijo de nombre de ruta → módulo.
     *
     * El middleware resuelve el módulo por el nombre de la ruta, de modo que
     * una ruta nueva queda cubierta sin tener que acordarse de ponerle nada.
     * El orden importa: se busca la coincidencia más larga primero.
     */
    public const RUTAS = [
        'animales' => self::ANIMALES,
        'animals' => self::ANIMALES,
        'genealogias' => self::ANIMALES,
        'identificadores' => self::ANIMALES,
        'lotes' => self::LOTES,
        'pesajes' => self::PESAJES,
        'condiciones-corporales' => self::PESAJES,
        'alimentacion' => self::ALIMENTACION,
        'alimentaciones' => self::ALIMENTACION,
        'raciones' => self::ALIMENTACION,
        'inventario' => self::ALIMENTACION,
        'programaciones-alimentacion' => self::ALIMENTACION,
        'conversion' => self::ALIMENTACION,
        'salud' => self::SALUD,
        'eventos-salud' => self::SALUD,
        'vacunas' => self::SALUD,
        'tratamientos' => self::SALUD,
        'calendario' => self::SALUD,
        'reproduccion' => self::REPRODUCCION,
        'partos' => self::REPRODUCCION,
        'crias' => self::REPRODUCCION,
        'servicios-reproductivos' => self::REPRODUCCION,
        'diagnosticos' => self::REPRODUCCION,
        'eventos-reproductivos' => self::REPRODUCCION,
        'genetica' => self::REPRODUCCION,
        'termos' => self::REPRODUCCION,
        'pajillas' => self::REPRODUCCION,
        'donadores-externos' => self::REPRODUCCION,
        'producciones' => self::PRODUCCIONES,
        'bajas' => self::BAJAS,
        'trabajadores' => self::TRABAJADORES,
        'actividades-trabajador' => self::TRABAJADORES,
        'costos' => self::COSTOS,
        'valuaciones' => self::VALUACION,
        'ventas' => self::VENTAS,
        'compradores' => self::VENTAS,
        'faenas' => self::FAENAS,
        'sacrificios' => self::FAENAS,
        'reportes' => self::REPORTES,
        'estadisticas' => self::REPORTES,
        'tareas' => self::TAREAS,
        'documentos' => self::DOCUMENTOS,
        'alertas' => self::TAREAS,
    ];

    /**
     * Rutas que nunca se restringen por módulo.
     *
     * Son navegación general y cuentas propias: el panel, el perfil, el cierre
     * de sesión, los planes y todo lo de autenticación. Las de administración
     * llevan su propia protección (`super_admin`), más estricta que ésta.
     */
    public const SIEMPRE_DISPONIBLES = [
        'dashboard', 'home', 'splash', 'welcome', 'profile', 'password', 'logout',
        'login', 'register', 'verification', 'admin', 'auth',
        'sanctum', 'ignition', 'storage', 'up',
        // Probar el lector no revela ningún dato del rancho, y quien necesita
        // probarlo suele ser quien todavía no tiene acceso a nada.
        'herramientas',
    ];

    /**
     * Módulo al que pertenece una ruta, o null si es de navegación general.
     */
    public static function desdeRuta(?string $nombreRuta): ?string
    {
        if (! $nombreRuta) {
            return null;
        }

        foreach (self::SIEMPRE_DISPONIBLES as $libre) {
            if ($nombreRuta === $libre || str_starts_with($nombreRuta, $libre . '.')) {
                return null;
            }
        }

        // Coincidencia más larga primero: 'eventos-salud' debe ganarle a
        // cualquier prefijo más corto que también encajara.
        $prefijos = array_keys(self::RUTAS);
        usort($prefijos, fn ($a, $b) => strlen($b) <=> strlen($a));

        foreach ($prefijos as $prefijo) {
            if ($nombreRuta === $prefijo || str_starts_with($nombreRuta, $prefijo . '.')) {
                return self::RUTAS[$prefijo];
            }
        }

        return null;
    }

    /**
     * Acción que representa una petición, según su método HTTP.
     *
     * Es una aproximación deliberada: distinguir "registrar" de "editar" por
     * la ruta exacta multiplicaría la configuración sin que el rancho gane
     * nada. POST crea, PUT/PATCH modifica, DELETE elimina.
     */
    public static function accionDesdeMetodo(string $metodo): string
    {
        return match (strtoupper($metodo)) {
            'POST' => self::REGISTRAR,
            'PUT', 'PATCH' => self::EDITAR,
            'DELETE' => self::ELIMINAR,
            default => self::VER,
        };
    }

    public static function nombre(string $modulo): string
    {
        return self::MODULOS[$modulo][0] ?? $modulo;
    }

    public static function esEconomico(string $modulo): bool
    {
        return in_array($modulo, self::ECONOMICOS, true);
    }

    /** @return array<string> */
    public static function claves(): array
    {
        return array_keys(self::MODULOS);
    }
}
