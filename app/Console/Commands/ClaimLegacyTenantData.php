<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClaimLegacyTenantData extends Command
{
    protected $signature = 'tenancy:claim-legacy {email : Email of the rightful owner} {--force : Skip confirmation}';

    protected $description = 'Assign legacy rows without an owner to one account';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No existe una cuenta con ese correo.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm(
            "¿Asignar todos los registros antiguos sin dueño a {$user->email}?"
        )) {
            $this->warn('No se modificó ningún registro.');

            return self::SUCCESS;
        }

        $updated = DB::transaction(function () use ($user): int {
            $total = 0;

            foreach (config('tenancy.tables') as $table) {
                $total += DB::table($table)
                    ->whereNull('owner_id')
                    ->update(['owner_id' => $user->id]);
            }

            return $total;
        });

        $this->info("Se asignaron {$updated} registros antiguos a {$user->email}.");

        return self::SUCCESS;
    }
}
