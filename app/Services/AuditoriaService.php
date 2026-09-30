<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Punto único de escritura de la bitácora.
 *
 * Se copian el nombre del autor y el del afectado además de sus ids: si una
 * cuenta se elimina algún día, el movimiento sigue diciendo quién fue.
 */
class AuditoriaService
{
    /**
     * Deja constancia de una acción sobre un usuario.
     */
    public function registrarSobreUsuario(
        string $accion,
        ?User $afectado = null,
        ?array $valorAnterior = null,
        ?array $valorNuevo = null,
        ?string $descripcion = null,
    ): Auditoria {
        return $this->escribir([
            'accion' => $accion,
            'afectado_id' => $afectado?->id,
            'afectado_nombre' => $afectado?->name,
            'valor_anterior' => $valorAnterior,
            'valor_nuevo' => $valorNuevo,
            'descripcion' => $descripcion,
        ]);
    }

    /**
     * Deja constancia de un cambio en la configuración, una fórmula o un
     * catálogo, donde lo afectado no es una persona sino un registro.
     */
    public function registrarSobreEntidad(
        string $accion,
        ?string $entidadTipo = null,
        int|string|null $entidadId = null,
        ?array $valorAnterior = null,
        ?array $valorNuevo = null,
        ?string $descripcion = null,
    ): Auditoria {
        return $this->escribir([
            'accion' => $accion,
            'entidad_tipo' => $entidadTipo,
            'entidad_id' => is_numeric($entidadId) ? (int) $entidadId : null,
            'valor_anterior' => $valorAnterior,
            'valor_nuevo' => $valorNuevo,
            'descripcion' => $descripcion,
        ]);
    }

    private function escribir(array $datos): Auditoria
    {
        $autor = Auth::user();

        return Auditoria::create(array_merge([
            'usuario_id' => $autor?->id,
            'usuario_nombre' => $autor?->name,
            'ip' => $this->ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 255) ?: null,
        ], $datos));
    }

    /**
     * La IP puede no existir (comandos de consola, tareas programadas).
     * En ese caso se deja vacía en vez de inventar un valor.
     */
    private function ip(): ?string
    {
        if (app()->runningInConsole()) {
            return null;
        }

        return Request::ip();
    }
}
