<?php

namespace App\Models;
use App\Models\Tarea;
use App\Support\ModuloSistema;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // ----------- ROLES -----------
    /** Acceso completo: usuarios, roles, configuración y fórmulas económicas. */
    public const ROLE_SUPER_ADMIN = 'super_admin';

    /** Operación completa del rancho, sin administrar usuarios ni el sistema. */
    public const ROLE_ADMIN = 'admin';

    /** Rol operativo: trabaja según su puesto. */
    public const ROLE_TRABAJADOR = 'worker';

    /**
     * Nombre anterior del rol operativo.
     *
     * La migración 2026_08_05_010000 renombró los valores 'user' a 'worker'.
     * La constante se conserva porque el código y las pruebas existentes la
     * usan, y porque una fila sin normalizar no debe quedarse sin rol válido.
     *
     * @deprecated Usa ROLE_TRABAJADOR.
     */
    public const ROLE_USER = 'user';

    /** Roles asignables desde la interfaz, con su etiqueta. */
    public const ROLES = [
        self::ROLE_SUPER_ADMIN => 'Superadministrador',
        self::ROLE_ADMIN => 'Administrador',
        self::ROLE_TRABAJADOR => 'Trabajador',
    ];

    /**
     * Puestos del manejo ovino. Describen la función en el rancho; los
     * permisos siguen dependiendo de `role`.
     */
    public const PUESTOS = [
        'gerente'                  => 'Gerente',
        'encargado_rebano'         => 'Encargado del rebaño',
        'pastor'                   => 'Pastor',
        'veterinario'              => 'Veterinario',
        'ayudante_veterinario'     => 'Ayudante veterinario',
        'encargado_alimentacion'   => 'Encargado de alimentación',
        'encargado_reproduccion'   => 'Encargado de reproducción',
        'encargado_partos'         => 'Encargado de partos',
        'responsable_pesaje'       => 'Responsable de pesaje',
        'responsable_identificacion' => 'Responsable de identificación',
        'responsable_limpieza'     => 'Responsable de limpieza',
        'transportista'            => 'Transportista',
        'responsable_ventas'       => 'Responsable de ventas',
        'trabajador_general'       => 'Trabajador general',
    ];

    /**
     * Campos que se pueden asignar en masa.
     *
     * `role` está aquí a propósito, pero ningún controlador lo toma del
     * request sin pasar antes por UserPolicy: ver Admin\UserController.
     */
    protected $fillable = [
        'name',
        'email',
        'password',

        // permisos / negocio:
        'cuenta_id', // rancho al que pertenece esta persona
        'role',   // 'super_admin', 'admin' o 'worker'
        'puesto', // texto original del puesto (se conserva por historia)
        'puesto_id', // puesto del catálogo, de donde salen los permisos
        'permisos_extra', // excepciones concedidas o quitadas a esta persona
        'activo', // 1 / 0
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'activo'            => 'boolean',
        'password'          => 'hashed',
        'permisos_extra'    => 'array',
    ];

    /** Permisos ya resueltos, para no recalcularlos en cada comprobación. */
    private ?array $permisosResueltos = null;

    protected static function booted(): void
    {
        // Una cuenta nueva es su propio rancho mientras nadie la asigne a otro.
        // Se hace después de crear porque hace falta el id, y con saveQuietly
        // para no disparar observadores por un valor que es puro cableado.
        static::created(function (User $user): void {
            if ($user->cuenta_id === null) {
                $user->forceFill(['cuenta_id' => $user->id])->saveQuietly();
            }
        });
    }

    // ======== CUENTA / RANCHO ========

    /**
     * Id del rancho sobre el que trabaja esta persona.
     *
     * Es el valor que sella y filtra `owner_id` en todos los módulos. Cuando
     * la columna todavía no está resuelta —un modelo recién creado que aún no
     * se ha releído— se cae al propio id, que es el significado correcto:
     * quien no pertenece al rancho de nadie es dueño del suyo.
     */
    public function cuentaId(): ?int
    {
        return $this->cuenta_id ? (int) $this->cuenta_id : ($this->id ? (int) $this->id : null);
    }

    /** El rancho al que pertenece. Para un dueño, él mismo. */
    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(self::class, 'cuenta_id');
    }

    /** Las personas que trabajan en el rancho de esta cuenta. */
    public function miembros(): HasMany
    {
        return $this->hasMany(self::class, 'cuenta_id');
    }

    /**
     * ¿Es el dueño de su rancho, o un empleado dentro del rancho de otro?
     *
     * De esto dependerá, en la siguiente etapa, quién ve la parte económica.
     */
    public function esDuenoDeCuenta(): bool
    {
        return $this->cuentaId() === (int) $this->id;
    }

    /** ¿Esta persona y la otra trabajan en el mismo rancho? */
    public function comparteCuentaCon(?User $otro): bool
    {
        return $otro !== null && $this->cuentaId() === $otro->cuentaId();
    }

    // ======== HELPERS DE ROLE ========

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Nivel administrador o superior.
     *
     * Nota: devuelve true también para el superadministrador. Tres puntos del
     * sistema ya usaban isAdmin() con el sentido de "al menos administrador"
     * —el margen genético extendido y los datos reservados de trabajadores—,
     * así que restringirlo al rol exacto le
     * quitaría esos accesos justamente a quien tiene acceso completo.
     * Para el rol exacto existe esAdministradorExacto().
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN || $this->isSuperAdmin();
    }

    public function esAdministradorExacto(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Rol operativo. Reconoce el nombre anterior 'user'. */
    public function esTrabajador(): bool
    {
        return in_array($this->role, [self::ROLE_TRABAJADOR, self::ROLE_USER], true)
            || $this->role === null;
    }

    /**
     * @deprecated Usa esTrabajador().
     */
    public function isUser(): bool
    {
        return $this->esTrabajador();
    }

    /** Solo el superadministrador administra cuentas, roles y permisos. */
    public function canManageUsers(): bool
    {
        return $this->isSuperAdmin();
    }

    /** Alias en español del anterior, para leerse igual que el resto. */
    public function puedeGestionarUsuarios(): bool
    {
        return $this->canManageUsers();
    }

    public function rolLegible(): string
    {
        return self::ROLES[$this->role] ?? self::ROLES[self::ROLE_TRABAJADOR];
    }

    // ======== REGLAS DEL SUPERADMINISTRADOR ========

    /**
     * ¿Es el único superadministrador activo que queda?
     *
     * Sirve para impedir que el sistema se quede sin nadie capaz de
     * administrar cuentas, que dejaría la instalación bloqueada.
     */
    public function esUltimoSuperAdminActivo(): bool
    {
        if (! $this->isSuperAdmin() || ! $this->activo) {
            return false;
        }

        return self::query()
            ->where('role', self::ROLE_SUPER_ADMIN)
            ->where('activo', true)
            ->where('id', '!=', $this->id)
            ->doesntExist();
    }

    public static function haySuperAdminActivo(): bool
    {
        return self::query()
            ->where('role', self::ROLE_SUPER_ADMIN)
            ->where('activo', true)
            ->exists();
    }

    // ======== PERMISOS POR MÓDULO ========

    /** Puesto del catálogo que ocupa esta cuenta. De ahí salen sus permisos. */
    public function puestoCatalogo(): BelongsTo
    {
        return $this->belongsTo(PuestoTrabajador::class, 'puesto_id');
    }

    /**
     * Qué puede hacer esta persona en cada módulo.
     *
     * @return array<string, array<string>>  ['salud' => ['ver','registrar'], …]
     */
    public function permisos(): array
    {
        if ($this->permisosResueltos !== null) {
            return $this->permisosResueltos;
        }

        return $this->permisosResueltos = $this->resolverPermisos();
    }

    public function puede(string $modulo, string $accion = ModuloSistema::VER): bool
    {
        return in_array($accion, $this->permisos()[$modulo] ?? [], true);
    }

    /** Módulos en los que puede al menos entrar. Sirve para pintar el menú. */
    public function modulosVisibles(): array
    {
        return array_values(array_keys(array_filter(
            $this->permisos(),
            fn (array $acciones) => in_array(ModuloSistema::VER, $acciones, true)
        )));
    }

    /**
     * Tres niveles, de mayor a menor:
     *
     *   1. El superadministrador y el dueño del rancho no tienen restricción.
     *      Quien manda no se limita a sí mismo.
     *   2. Un `admin` dentro del rancho de otro maneja toda la operación,
     *      pero no las acciones críticas, que van por Gate aparte.
     *   3. Un trabajador recibe lo de su puesto, más lo que se le haya
     *      concedido a título personal, menos lo que se le haya quitado.
     *
     * Una cuenta desactivada no conserva permisos: ni siquiera llega aquí,
     * pero se deja explícito por si alguien consulta el modelo directamente.
     */
    private function resolverPermisos(): array
    {
        if ($this->activo === false) {
            return [];
        }

        if ($this->isSuperAdmin() || $this->esDuenoDeCuenta()) {
            return array_fill_keys(ModuloSistema::claves(), ModuloSistema::TODAS);
        }

        if ($this->esAdministradorExacto()) {
            return array_fill_keys(ModuloSistema::claves(), ModuloSistema::TODAS);
        }

        $permisos = $this->puestoCatalogo?->permisosNormalizados() ?? [];

        $extra = $this->permisos_extra ?? [];

        foreach ($extra['conceder'] ?? [] as $modulo => $acciones) {
            $permisos[$modulo] = array_values(array_unique(
                array_merge($permisos[$modulo] ?? [], (array) $acciones)
            ));
        }

        foreach ($extra['revocar'] ?? [] as $modulo => $acciones) {
            $restantes = array_diff($permisos[$modulo] ?? [], (array) $acciones);

            if ($restantes === []) {
                unset($permisos[$modulo]);
            } else {
                $permisos[$modulo] = array_values($restantes);
            }
        }

        // Solo módulos reconocidos: una clave inventada no concede nada.
        return array_intersect_key($permisos, array_flip(ModuloSistema::claves()));
    }

    // ======== RELACIONES ========

    /** Persona del rancho ligada a esta cuenta, cuando existe. Opcional. */
    public function trabajador(): HasOne
    {
        return $this->hasOne(Trabajador::class, 'user_id');
    }

    public function tareasAsignadas()
    {
        return $this->hasMany(Tarea::class, 'asignado_a');
    }

    public function tareasCreadas()
    {
        return $this->hasMany(Tarea::class, 'creado_por');
    }

    /** Movimientos de auditoría que esta cuenta provocó. */
    public function auditorias()
    {
        return $this->hasMany(Auditoria::class, 'usuario_id');
    }

    /** Movimientos de auditoría que recayeron sobre esta cuenta. */
    public function auditoriasRecibidas()
    {
        return $this->hasMany(Auditoria::class, 'afectado_id');
    }

}
