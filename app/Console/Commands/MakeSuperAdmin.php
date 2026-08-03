<?php

namespace App\Console\Commands;

use App\Models\Auditoria;
use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Console\Command;

/**
 * Convierte una cuenta existente en superadministrador.
 *
 * Es la forma prevista de crear el primero: no hay ningún correo escrito en el
 * código ni ninguna cuenta privilegiada de fábrica. El comando nunca crea
 * cuentas nuevas, solo eleva una que ya existe, para no dejar accesos que
 * nadie recuerde haber pedido.
 */
class MakeSuperAdmin extends Command
{
    protected $signature = 'user:make-super-admin
                            {email : Correo de la cuenta que será superadministrador}
                            {--force : Aplicar el cambio sin pedir confirmación}';

    protected $description = 'Otorga el rol de superadministrador a un usuario existente';

    public function handle(AuditoriaService $auditoria): int
    {
        $email = trim($this->argument('email'));

        $usuario = User::where('email', $email)->first();

        if (! $usuario) {
            $this->error("No existe ninguna cuenta con el correo {$email}.");
            $this->newLine();
            $this->line('Regístrate primero en la aplicación con ese correo y vuelve a ejecutar el comando.');
            $this->line('Cuentas registradas actualmente:');

            User::orderBy('id')->get(['email', 'role'])->each(
                fn (User $u) => $this->line("  · {$u->email}  ({$u->rolLegible()})")
            );

            return self::FAILURE;
        }

        if ($usuario->isSuperAdmin()) {
            $this->info("{$usuario->name} <{$usuario->email}> ya es superadministrador. No se hizo ningún cambio.");

            return self::SUCCESS;
        }

        $rolAnterior = $usuario->role;

        $this->newLine();
        $this->line("Cuenta:      {$usuario->name} <{$usuario->email}>");
        $this->line("Rol actual:  {$usuario->rolLegible()}");
        $this->line('Rol nuevo:   Superadministrador');
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm('¿Aplicar el cambio?', true)) {
            $this->warn('Cancelado. No se modificó nada.');

            return self::SUCCESS;
        }

        $usuario->update([
            'role' => User::ROLE_SUPER_ADMIN,
            // Un superadministrador desactivado no podría entrar a arreglar nada.
            'activo' => true,
        ]);

        $auditoria->registrarSobreUsuario(
            Auditoria::ROL_CAMBIADO,
            $usuario,
            ['role' => $rolAnterior],
            ['role' => User::ROLE_SUPER_ADMIN],
            'Rol otorgado desde la consola con user:make-super-admin.'
        );

        $this->newLine();
        $this->info("Listo. {$usuario->email} ahora es superadministrador.");
        $this->line('Cierra la sesión y vuelve a entrar para que el menú se actualice.');

        return self::SUCCESS;
    }
}
