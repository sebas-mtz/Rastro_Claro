<?php

namespace App\Support;

use App\Models\ConfiguracionLector;

/**
 * Convierte lo que manda el lector en el código con el que trabaja el sistema.
 *
 * Es el punto donde se aplican los ajustes del rancho. Toda lectura pasa por
 * aquí —búsqueda, registro y diagnóstico— de modo que adaptar un lector nuevo
 * es cambiar una configuración, no el código.
 *
 * El orden importa:
 *
 *   1. Se quitan los espacios de los extremos y los saltos de línea.
 *   2. Se descarta el prefijo, si la lectura empieza por él.
 *   3. Se descarta el sufijo, si termina por él.
 *   4. Si el rancho lo pidió, se descarta todo lo que no sea dígito.
 *   5. Se quitan los espacios interiores y se pasa a mayúsculas.
 *
 * El prefijo se recorta ANTES que cualquier otra cosa porque un lector que
 * antepone, por ejemplo, «LA» a un código de 15 dígitos entregaría 17
 * caracteres, y ninguna validación de longitud tendría sentido antes de
 * quitarlo.
 */
class NormalizadorLectura
{
    public function __construct(private readonly ?ConfiguracionLector $config = null)
    {
    }

    /** Normalizador con los ajustes del rancho activo. */
    public static function delRancho(): self
    {
        return new self(ConfiguracionLector::first());
    }

    /** Normalizador sin ajustes, con el comportamiento de fábrica. */
    public static function porDefecto(): self
    {
        return new self(null);
    }

    public function aplicar(?string $crudo): string
    {
        $texto = trim((string) $crudo);

        if ($texto === '') {
            return '';
        }

        $prefijo = $this->config?->prefijo_descartar;
        $sufijo = $this->config?->sufijo_descartar;

        // Solo se recorta si realmente está: quitar ciegamente los primeros
        // caracteres mutilaría las lecturas que no traen el prefijo.
        if ($prefijo && str_starts_with($texto, $prefijo)) {
            $texto = substr($texto, strlen($prefijo));
        }

        if ($sufijo && str_ends_with($texto, $sufijo)) {
            $texto = substr($texto, 0, -strlen($sufijo));
        }

        if ($this->config?->solo_digitos) {
            $texto = preg_replace('/\D+/', '', $texto);
        }

        return strtoupper(preg_replace('/\s+/', '', trim($texto)));
    }

    /**
     * Longitud que debe tener un código electrónico ya limpio.
     * Sin configuración, la de la norma ISO 11784.
     */
    public function longitudEsperada(): int
    {
        return $this->config?->longitud() ?? CodigoIso11784::LONGITUD;
    }

    /**
     * Explicación de lo que se hizo con la lectura, para el diagnóstico.
     *
     * @return array{crudo:string, normalizado:string, longitud:int, pasos:array<string>}
     */
    public function explicar(?string $crudo): array
    {
        $pasos = [];
        $texto = trim((string) $crudo);

        $prefijo = $this->config?->prefijo_descartar;
        $sufijo = $this->config?->sufijo_descartar;

        if ($prefijo) {
            $pasos[] = str_starts_with($texto, $prefijo)
                ? "Se descartó el prefijo «{$prefijo}»."
                : "El prefijo «{$prefijo}» está configurado pero la lectura no empieza con él.";
        }

        if ($sufijo) {
            $pasos[] = str_ends_with($texto, $sufijo)
                ? "Se descartó el sufijo «{$sufijo}»."
                : "El sufijo «{$sufijo}» está configurado pero la lectura no termina con él.";
        }

        if ($this->config?->solo_digitos) {
            $pasos[] = 'Se descartó todo lo que no era dígito.';
        }

        $normalizado = $this->aplicar($crudo);

        return [
            'crudo' => (string) $crudo,
            'normalizado' => $normalizado,
            'longitud' => strlen($normalizado),
            'pasos' => $pasos,
        ];
    }
}
