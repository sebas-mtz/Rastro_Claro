<?php

namespace App\Services;

use App\Models\Animal;

/**
 * Clasificación por etapa de vida del ejemplar ovino.
 *
 * El servicio SUGIERE una etapa a partir de la edad, el sexo y el estado
 * reproductivo, pero nunca la guarda por su cuenta: la sugerencia se presenta
 * al usuario y solo se persiste cuando la confirma. Si faltan datos para
 * decidir (por ejemplo, sin fecha de nacimiento), devuelve null en lugar de
 * inventar una etapa.
 */
class EtapaVidaService
{
    public const CORDERO_LACTANTE = 'cordero_lactante';
    public const CORDERA_LACTANTE = 'cordera_lactante';
    public const CORDERO_DESTETADO = 'cordero_destetado';
    public const CORDERA_DESTETADA = 'cordera_destetada';
    public const BORREGA_JOVEN = 'borrega_joven';
    public const BORREGA_DESARROLLO = 'borrega_desarrollo';
    public const BORREGA_EDAD_REPRODUCTIVA = 'borrega_edad_reproductiva';
    public const BORREGA_GESTANTE = 'borrega_gestante';
    public const BORREGA_LACTANTE = 'borrega_lactante';
    public const OVEJA_ADULTA = 'oveja_adulta';
    public const SEMENTAL_JOVEN = 'semental_joven';
    public const SEMENTAL_ADULTO = 'semental_adulto';
    public const DESCARTE = 'descarte';
    public const VENDIDO = 'vendido';
    public const FALLECIDO = 'fallecido';

    /**
     * Etiquetas legibles para la interfaz.
     */
    public const ETIQUETAS = [
        self::CORDERO_LACTANTE => 'Cordero lactante',
        self::CORDERA_LACTANTE => 'Cordera lactante',
        self::CORDERO_DESTETADO => 'Cordero destetado',
        self::CORDERA_DESTETADA => 'Cordera destetada',
        self::BORREGA_JOVEN => 'Borrega joven',
        self::BORREGA_DESARROLLO => 'Borrega en desarrollo',
        self::BORREGA_EDAD_REPRODUCTIVA => 'Borrega en edad reproductiva',
        self::BORREGA_GESTANTE => 'Borrega gestante',
        self::BORREGA_LACTANTE => 'Borrega lactante',
        self::OVEJA_ADULTA => 'Oveja adulta',
        self::SEMENTAL_JOVEN => 'Semental joven',
        self::SEMENTAL_ADULTO => 'Semental adulto',
        self::DESCARTE => 'Ejemplar de descarte',
        self::VENDIDO => 'Ejemplar vendido',
        self::FALLECIDO => 'Ejemplar fallecido',
    ];

    /**
     * Umbrales en meses. Se centralizan aquí para poder ajustarlos en un solo
     * lugar si el manejo del rancho usa otros criterios.
     */
    public const MESES_DESTETE = 3;
    public const MESES_EDAD_REPRODUCTIVA = 8;
    public const MESES_ADULTA = 24;

    public static function opciones(): array
    {
        return array_map(
            fn ($clave, $etiqueta) => ['valor' => $clave, 'etiqueta' => $etiqueta],
            array_keys(self::ETIQUETAS),
            self::ETIQUETAS
        );
    }

    public static function etiqueta(?string $etapa): ?string
    {
        return $etapa ? (self::ETIQUETAS[$etapa] ?? $etapa) : null;
    }

    /**
     * Sugiere una etapa. Devuelve null cuando no hay información suficiente.
     *
     * @return array{etapa: string|null, motivo: string}
     */
    public function sugerir(Animal $animal): array
    {
        if ($animal->esta_vendido) {
            return [
                'etapa' => self::VENDIDO,
                'motivo' => 'El ejemplar tiene una venta completada registrada.',
            ];
        }

        $nacimiento = AnimalValuationService::fechaNacimiento($animal);

        if (! $nacimiento) {
            return [
                'etapa' => null,
                'motivo' => 'Sin fecha de nacimiento no es posible determinar la etapa. Regístrala o elige la etapa manualmente.',
            ];
        }

        $meses = $nacimiento->diffInMonths(now());

        // ── Machos ────────────────────────────────────────────────────────
        if ($animal->sexo === 'M') {
            if ($meses < self::MESES_DESTETE) {
                return $this->armar(
                    self::CORDERO_LACTANTE,
                    "Tiene {$meses} mes(es), menos de " . self::MESES_DESTETE . '.'
                );
            }

            if ($meses < self::MESES_EDAD_REPRODUCTIVA) {
                return $this->armar(self::CORDERO_DESTETADO, "Tiene {$meses} mes(es).");
            }

            return $meses < self::MESES_ADULTA
                ? $this->armar(self::SEMENTAL_JOVEN, "Tiene {$meses} mes(es).")
                : $this->armar(self::SEMENTAL_ADULTO, "Tiene {$meses} mes(es).");
        }

        // ── Hembras ───────────────────────────────────────────────────────
        $estadoReproductivo = $animal->estado_reproductivo;

        if (in_array($estadoReproductivo, ['gestante', 'proxima_a_parir'], true)) {
            return $this->armar(self::BORREGA_GESTANTE, 'Tiene una gestación confirmada.');
        }

        if ($estadoReproductivo === 'parida') {
            return $this->armar(self::BORREGA_LACTANTE, 'Registró un parto reciente.');
        }

        if ($meses < self::MESES_DESTETE) {
            return $this->armar(self::CORDERA_LACTANTE, "Tiene {$meses} mes(es), menos de " . self::MESES_DESTETE . '.');
        }

        if ($meses < 6) {
            return $this->armar(self::CORDERA_DESTETADA, "Tiene {$meses} mes(es).");
        }

        if ($meses < self::MESES_EDAD_REPRODUCTIVA) {
            return $this->armar(self::BORREGA_DESARROLLO, "Tiene {$meses} mes(es).");
        }

        if ($meses < self::MESES_ADULTA) {
            return $this->armar(self::BORREGA_EDAD_REPRODUCTIVA, "Tiene {$meses} mes(es).");
        }

        return $this->armar(self::OVEJA_ADULTA, "Tiene {$meses} mes(es), más de " . self::MESES_ADULTA . '.');
    }

    private function armar(string $etapa, string $motivo): array
    {
        return ['etapa' => $etapa, 'motivo' => $motivo];
    }
}
