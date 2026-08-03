<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\PuestoTrabajador;
use App\Models\Trabajador;
use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

/**
 * Administración de cuentas del sistema.
 *
 * Toda la sección vive detrás del middleware `super_admin` (ver routes/web.php)
 * y además cada acción vuelve a comprobarse con UserPolicy: el middleware
 * protege la ruta y la política protege la operación concreta, que es donde
 * están las reglas de no tocarse a sí mismo y de no dejar el sistema sin
 * superadministrador.
 */
class UserController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected AuditoriaService $auditoria)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $usuarios = User::query()
            ->when($request->buscar, function ($q) use ($request) {
                $like = '%' . $request->buscar . '%';
                $q->where(fn ($sub) => $sub->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->when($request->role, fn ($q) => $q->where('role', $request->role))
            ->when($request->estado === 'activo', fn ($q) => $q->where('activo', true))
            ->when($request->estado === 'inactivo', fn ($q) => $q->where('activo', false))
            ->when($request->cuenta_id, fn ($q) => $q->where('cuenta_id', $request->cuenta_id))
            ->with(['trabajador:id,user_id,nombre,apellido_paterno,apellido_materno', 'cuenta:id,name'])
            ->orderByRaw($this->ordenPorJerarquia())
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $u) => $this->presentar($u));

        return Inertia::render('Admin/Users/Index', [
            'usuarios' => $usuarios,
            'roles' => User::ROLES,
            'ranchos' => $this->ranchos(),
            'puestosPorRancho' => $this->puestosPorRancho(),
            'filtros' => $request->only(['buscar', 'role', 'estado', 'cuenta_id']),
            'resumen' => [
                'total' => User::count(),
                'super_admins' => User::where('role', User::ROLE_SUPER_ADMIN)->count(),
                'admins' => User::where('role', User::ROLE_ADMIN)->count(),
                'inactivos' => User::where('activo', false)->count(),
            ],
            // Trabajadores del rancho todavía sin cuenta, para poder crearles una.
            'trabajadoresSinCuenta' => Trabajador::whereNull('user_id')
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'apellido_paterno', 'apellido_materno']),
            'auditoriaReciente' => Auditoria::with('usuario:id,name')
                ->orderByDesc('id')
                ->limit(10)
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $datos = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            // Rancho al que entra a trabajar. Vacío = es su propio rancho.
            'cuenta_id' => ['nullable', $this->reglaDeRancho()],
            'puesto_id' => ['nullable', Rule::exists('puestos_trabajador', 'id')],
            'activo' => 'boolean',
            // Enlace opcional con una persona ya registrada en el rancho.
            'trabajador_id' => ['nullable', Rule::exists('trabajadores', 'id')],
        ], [
            'email.unique' => 'Ya existe una cuenta con ese correo.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $cuentaId = $datos['cuenta_id'] ?? null;

        if ($error = $this->puestoInvalido($datos['puesto_id'] ?? null, $cuentaId)) {
            return back()->withErrors(['puesto_id' => $error])->withInput();
        }

        $puesto = $this->puesto($datos['puesto_id'] ?? null);

        $usuario = User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            // El cast `hashed` del modelo se encarga del cifrado; nunca se
            // guarda ni se devuelve la contraseña en claro.
            'password' => $datos['password'],
            'role' => $datos['role'],
            'cuenta_id' => $cuentaId,
            'puesto_id' => $puesto?->id,
            // La columna histórica se mantiene coherente con el catálogo.
            'puesto' => $puesto?->clave,
            'activo' => $request->boolean('activo', true),
            'email_verified_at' => now(),
        ]);

        if (! empty($datos['trabajador_id'])) {
            $this->enlazarTrabajador($usuario, (int) $datos['trabajador_id']);
        }

        $this->auditoria->registrarSobreUsuario(
            Auditoria::USUARIO_CREADO,
            $usuario,
            null,
            ['role' => $usuario->role, 'activo' => $usuario->activo, 'puesto' => $usuario->puesto],
            "Cuenta creada con rol {$usuario->rolLegible()}."
        );

        return back()->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Actualiza los datos generales. El rol se procesa aparte porque tiene su
     * propia política y sus propias salvaguardas.
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $datos = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'cuenta_id' => ['nullable', $this->reglaDeRancho($user)],
            'puesto_id' => ['nullable', Rule::exists('puestos_trabajador', 'id')],
            'trabajador_id' => ['nullable', Rule::exists('trabajadores', 'id')],
        ]);

        $anterior = $user->only(['name', 'email', 'role', 'cuenta_id', 'puesto', 'puesto_id']);
        $rolNuevo = $datos['role'];
        $cambiaRol = $rolNuevo !== $user->role;

        if ($cambiaRol) {
            if ($error = $this->motivoParaRechazarCambioDeRol($user, $rolNuevo)) {
                return back()->withErrors(['role' => $error])->withInput();
            }
        }

        // El rancho vacío significa "es dueño de sí mismo".
        $cuentaNueva = ($datos['cuenta_id'] ?? null) ?: $user->id;
        $cambiaRancho = (int) $cuentaNueva !== (int) $user->cuentaId();

        if ($cambiaRancho) {
            if ($error = $this->motivoParaRechazarCambioDeRancho($user)) {
                return back()->withErrors(['cuenta_id' => $error])->withInput();
            }
        }

        if ($error = $this->puestoInvalido($datos['puesto_id'] ?? null, $cuentaNueva)) {
            return back()->withErrors(['puesto_id' => $error])->withInput();
        }

        $puesto = $this->puesto($datos['puesto_id'] ?? null);

        $user->update([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'role' => $rolNuevo,
            'cuenta_id' => $cuentaNueva,
            'puesto_id' => $puesto?->id,
            'puesto' => $puesto?->clave,
        ]);

        if (array_key_exists('trabajador_id', $datos)) {
            $this->enlazarTrabajador($user, $datos['trabajador_id'] ? (int) $datos['trabajador_id'] : null);
        }

        $this->auditoria->registrarSobreUsuario(
            Auditoria::USUARIO_EDITADO,
            $user,
            $anterior,
            $user->only(['name', 'email', 'role', 'cuenta_id', 'puesto', 'puesto_id']),
            'Datos de la cuenta actualizados.'
        );

        // Cambiar de rancho cambia por completo qué datos ve esta cuenta:
        // se registra aparte para poder rastrearlo.
        if ($cambiaRancho) {
            $this->auditoria->registrarSobreUsuario(
                Auditoria::PERMISOS_CAMBIADOS,
                $user,
                ['cuenta_id' => $anterior['cuenta_id']],
                ['cuenta_id' => $cuentaNueva],
                (int) $cuentaNueva === (int) $user->id
                    ? 'La cuenta pasó a ser dueña de su propio rancho.'
                    : 'La cuenta pasó a trabajar en otro rancho.'
            );
        }

        // El cambio de rol se registra además por separado: es lo que más
        // interesa poder rastrear después.
        if ($cambiaRol) {
            $this->auditoria->registrarSobreUsuario(
                Auditoria::ROL_CAMBIADO,
                $user,
                ['role' => $anterior['role']],
                ['role' => $rolNuevo],
                sprintf(
                    'Rol cambiado de %s a %s.',
                    User::ROLES[$anterior['role']] ?? $anterior['role'],
                    User::ROLES[$rolNuevo] ?? $rolNuevo
                )
            );
        }

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function cambiarEstado(Request $request, User $user)
    {
        $this->authorize('cambiarEstado', $user);

        $datos = $request->validate([
            'activo' => 'required|boolean',
            'motivo' => 'nullable|string|max:500',
        ]);

        $activo = (bool) $datos['activo'];

        // Nunca se desactiva al último superadministrador: dejaría el sistema
        // sin nadie capaz de administrar cuentas.
        if (! $activo && $user->esUltimoSuperAdminActivo()) {
            return back()->withErrors([
                'activo' => 'No se puede desactivar al único superadministrador activo. Nombra otro antes.',
            ]);
        }

        $user->update(['activo' => $activo]);

        $this->auditoria->registrarSobreUsuario(
            $activo ? Auditoria::USUARIO_ACTIVADO : Auditoria::USUARIO_DESACTIVADO,
            $user,
            ['activo' => ! $activo],
            ['activo' => $activo],
            $datos['motivo'] ?? null
        );

        return back()->with(
            'success',
            $activo ? 'Cuenta activada.' : 'Cuenta desactivada. Sus registros se conservan.'
        );
    }

    /**
     * Asigna una contraseña nueva a otra cuenta.
     *
     * Nunca se consulta ni se muestra la anterior: no existe forma de leerla,
     * solo de reemplazarla. En la bitácora se registra el hecho, jamás el valor.
     */
    public function restablecerPassword(Request $request, User $user)
    {
        $this->authorize('restablecerPassword', $user);

        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $user->update(['password' => $request->password]);

        $this->auditoria->registrarSobreUsuario(
            Auditoria::PASSWORD_RESTABLECIDA,
            $user,
            null,
            null,
            'Contraseña restablecida por el superadministrador. El valor no se registra.'
        );

        return back()->with('success', 'Contraseña restablecida.');
    }

    /**
     * Bitácora completa, con filtros. Solo de lectura: no hay ruta que
     * permita editar ni eliminar movimientos.
     */
    public function auditoria(Request $request)
    {
        $this->authorize('verAuditoria', User::class);

        $movimientos = Auditoria::query()
            ->deAccion($request->accion)
            ->entreFechas($request->desde, $request->hasta)
            ->when($request->usuario_id, fn ($q) => $q->where('usuario_id', $request->usuario_id))
            ->with(['usuario:id,name', 'afectado:id,name'])
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Users/Auditoria', [
            'movimientos' => $movimientos,
            'acciones' => Auditoria::ACCIONES,
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
            'filtros' => $request->only(['accion', 'desde', 'hasta', 'usuario_id']),
        ]);
    }

    /**
     * Borrado físico. Solo procede para una cuenta recién creada y sin datos;
     * en cualquier otro caso se desactiva, para no romper el historial.
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        if ($relacionados = $this->registrosRelacionados($user)) {
            return back()->withErrors([
                'usuario' => 'No se puede eliminar: la cuenta tiene ' . implode(', ', $relacionados)
                    . '. Desactívala para conservar su historial.',
            ]);
        }

        $nombre = $user->name;
        $user->delete();

        $this->auditoria->registrarSobreUsuario(
            Auditoria::USUARIO_EDITADO,
            null,
            ['name' => $nombre],
            null,
            "Cuenta «{$nombre}» eliminada por no tener registros asociados."
        );

        return back()->with('success', 'Cuenta eliminada.');
    }

    // ─── Apoyos ───────────────────────────────────────────────────────────

    /**
     * Motivo por el que no se puede aplicar un cambio de rol, o null si sí.
     */
    private function motivoParaRechazarCambioDeRol(User $user, string $rolNuevo): ?string
    {
        if (! request()->user()->can('cambiarRol', $user)) {
            return 'No puedes cambiar tu propio rol. Pídeselo a otro superadministrador.';
        }

        // Quitarle el rol al último superadministrador activo dejaría el
        // sistema sin quien administre cuentas.
        if ($user->esUltimoSuperAdminActivo() && $rolNuevo !== User::ROLE_SUPER_ADMIN) {
            return 'No se puede quitar el rol al único superadministrador activo. Nombra otro antes.';
        }

        return null;
    }

    /**
     * Motivo por el que no se puede mover esta cuenta de rancho, o null.
     *
     * Es la protección importante de esta pantalla. Los datos se sellan con el
     * id del rancho: si una cuenta que es dueña de registros pasa a trabajar
     * en el rancho de otro, esos registros dejan de ser visibles para nadie
     * —ni para ella, que ahora mira otro rancho, ni para el resto—. No se
     * borran, pero quedan fuera de alcance, que en la práctica es peor porque
     * no se nota.
     */
    private function motivoParaRechazarCambioDeRancho(User $user): ?string
    {
        if ($user->id === request()->user()->id) {
            return 'No puedes cambiar tu propio rancho.';
        }

        if ($relacionados = $this->registrosRelacionados($user)) {
            return 'Esta cuenta es dueña de ' . implode(', ', $relacionados)
                . '. Si la pasas a otro rancho, esos registros quedarían fuera de alcance.';
        }

        return null;
    }

    /**
     * Comprueba que el puesto elegido pertenezca al rancho de la cuenta.
     *
     * Cada rancho tiene su propio catálogo, y de ahí salen los permisos: un
     * puesto de otro rancho concedería accesos que su dueño nunca configuró.
     */
    private function puestoInvalido(?int $puestoId, int|string|null $cuentaId): ?string
    {
        if (! $puestoId) {
            return null;
        }

        $puesto = $this->puesto($puestoId);

        if (! $puesto) {
            return 'El puesto seleccionado ya no existe.';
        }

        // Sin rancho explícito, la cuenta será dueña de sí misma y todavía no
        // tiene catálogo propio; en ese caso el puesto se asigna después.
        if (! $cuentaId) {
            return 'Elige primero el rancho al que pertenece esta cuenta.';
        }

        if ((int) $puesto->owner_id !== (int) $cuentaId) {
            return 'Ese puesto pertenece al catálogo de otro rancho.';
        }

        return null;
    }

    private function puesto(?int $puestoId): ?PuestoTrabajador
    {
        return $puestoId
            ? PuestoTrabajador::withoutGlobalScope('owner')->find($puestoId)
            : null;
    }

    /**
     * Regla de validación del campo rancho.
     *
     * Solo vale una cuenta que sea dueña de su propio rancho: así no se puede
     * formar una cadena (A depende de B, que depende de C) ni un ciclo.
     */
    private function reglaDeRancho(?User $editando = null): \Closure
    {
        return function (string $atributo, $valor, \Closure $falla) use ($editando) {
            $rancho = User::find($valor);

            if (! $rancho) {
                $falla('El rancho seleccionado no existe.');

                return;
            }

            if (! $rancho->esDuenoDeCuenta()) {
                $falla('Esa cuenta no es dueña de un rancho: pertenece al de otra persona.');

                return;
            }

            // Una cuenta con gente a su cargo no puede volverse empleada.
            if ($editando
                && (int) $valor !== (int) $editando->id
                && $editando->miembros()->where('id', '!=', $editando->id)->exists()) {
                $falla('Esta cuenta tiene personal a su cargo. Reasígnalo antes de moverla.');
            }
        };
    }

    /** Cuentas que son dueñas de un rancho, para el selector. */
    private function ranchos()
    {
        return User::whereColumn('cuenta_id', 'id')
            ->withCount(['miembros as personas' => fn ($q) => $q->whereColumn('id', '!=', 'cuenta_id')])
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'personas' => $u->personas,
            ]);
    }

    /**
     * Catálogo de puestos agrupado por rancho.
     *
     * El formulario necesita mostrar solo los del rancho elegido, y el rancho
     * se elige en el mismo formulario, así que se mandan todos de una vez.
     *
     * @return array<int, array<int, array{id:int, nombre:string}>>
     */
    private function puestosPorRancho(): array
    {
        return PuestoTrabajador::withoutGlobalScope('owner')
            ->activo()
            ->orderBy('nombre')
            ->get(['id', 'owner_id', 'nombre', 'area'])
            ->groupBy('owner_id')
            ->map(fn ($puestos) => $puestos->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'area' => $p->area,
            ])->values())
            ->toArray();
    }

    /**
     * Enlaza (o desenlaza) la cuenta con una persona del rancho.
     * La relación es 1 a 1: se libera cualquier enlace anterior.
     */
    private function enlazarTrabajador(User $usuario, ?int $trabajadorId): void
    {
        Trabajador::where('user_id', $usuario->id)->update(['user_id' => null]);

        if ($trabajadorId) {
            Trabajador::where('id', $trabajadorId)->update(['user_id' => $usuario->id]);
        }
    }

    /**
     * Descripción de lo que impide borrar la cuenta.
     *
     * @return array<string>
     */
    private function registrosRelacionados(User $user): array
    {
        // Los datos se sellan con el id del RANCHO. Para el dueño ese id es el
        // suyo, así que este conteo sigue respondiendo "¿borrar esta cuenta
        // dejaría datos huérfanos?". Un empleado no es dueño de nada: sus
        // capturas quedan a nombre del rancho y sobreviven a su baja.
        $conteos = [
            'ejemplares' => \App\Models\Animal::withoutGlobalScope('owner')->where('owner_id', $user->id)->count(),
            'lotes' => \App\Models\Lote::withoutGlobalScope('owner')->where('owner_id', $user->id)->count(),
            'costos' => \App\Models\Costo::withoutGlobalScope('owner')->where('owner_id', $user->id)->count(),
            'trabajadores' => Trabajador::withoutGlobalScope('owner')->where('owner_id', $user->id)->count(),
            // Borrar al dueño dejaría a su gente sin rancho al que pertenecer.
            'personas en su rancho' => $user->miembros()->where('id', '!=', $user->id)->count(),
        ];

        $descripciones = [];

        foreach ($conteos as $etiqueta => $total) {
            if ($total > 0) {
                $descripciones[] = "{$total} {$etiqueta}";
            }
        }

        return $descripciones;
    }

    /**
     * Lo que viaja al navegador. La contraseña queda fuera por el $hidden del
     * modelo; aquí se agrega lo que la interfaz necesita mostrar.
     */
    private function presentar(User $usuario): array
    {
        $actor = request()->user();

        return array_merge(
            $usuario->only([
                'id', 'name', 'email', 'role', 'cuenta_id', 'puesto', 'puesto_id',
                'activo', 'created_at', 'last_login_at',
            ]),
            [
                'rol_legible' => $usuario->rolLegible(),
                'es_uno_mismo' => $actor?->id === $usuario->id,
                'es_dueno' => $usuario->esDuenoDeCuenta(),
                'rancho' => $usuario->esDuenoDeCuenta() ? null : $usuario->cuenta?->name,
                'es_ultimo_super_admin' => $usuario->esUltimoSuperAdminActivo(),
                'trabajador' => $usuario->trabajador ? [
                    'id' => $usuario->trabajador->id,
                    'nombre_completo' => $usuario->trabajador->nombre_completo,
                ] : null,
                'permisos' => [
                    'editar' => $actor?->can('update', $usuario) ?? false,
                    'cambiarRol' => $actor?->can('cambiarRol', $usuario) ?? false,
                    'cambiarEstado' => $actor?->can('cambiarEstado', $usuario) ?? false,
                    'restablecerPassword' => $actor?->can('restablecerPassword', $usuario) ?? false,
                    'eliminar' => $actor?->can('delete', $usuario) ?? false,
                ],
            ]
        );
    }

    /** Superadministradores primero, luego administradores, luego el resto. */
    private function ordenPorJerarquia(): string
    {
        return sprintf(
            "CASE role WHEN '%s' THEN 0 WHEN '%s' THEN 1 ELSE 2 END",
            User::ROLE_SUPER_ADMIN,
            User::ROLE_ADMIN
        );
    }
}
