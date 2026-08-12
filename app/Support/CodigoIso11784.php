<?php

namespace App\Support;

/**
 * Código de identificación electrónica de animales, norma ISO 11784.
 *
 * Es lo que devuelve un lector cuando pasa por un arete electrónico, un bolo
 * ruminal o un microchip. Son 64 bits que, presentados en decimal, se leen
 * como 15 dígitos:
 *
 *     484 000123456789
 *     └┬┘ └─────┬────┘
 *      │        └── código nacional del animal (12 dígitos)
 *      └── país o fabricante (3 dígitos)
 *
 * Sobre HDX y FDX-B: son las dos formas de transmisión que define la norma
 * ISO 11785 (media y plena duplicidad). Las resuelve el LECTOR, no el sistema:
 * con cualquiera de las dos llega este mismo código. Por eso aquí no se
 * distinguen; la tecnología del arete se guarda aparte, en el animal, porque
 * sirve para saber qué lector funciona en campo.
 */
class CodigoIso11784
{
    /** Código de México en la norma ISO 3166-1 numérica, que es la que usa ICAR. */
    public const MEXICO = '484';

    /** Longitud del código completo en decimal. */
    public const LONGITUD = 15;

    private function __construct(
        public readonly string $codigo,
        public readonly string $pais,
        public readonly string $nacional,
    ) {
    }

    /**
     * Interpreta lo que entregó el lector.
     *
     * Se queda solo con los dígitos porque cada marca presenta el código a su
     * manera: con espacios, con guiones, con un prefijo de texto o con puntos
     * separando país y código nacional. Devuelve null si no es un código ISO.
     */
    public static function desde(?string $crudo): ?self
    {
        $digitos = preg_replace('/\D+/', '', (string) $crudo);

        if (strlen($digitos) !== self::LONGITUD) {
            return null;
        }

        return new self(
            codigo: $digitos,
            pais: substr($digitos, 0, 3),
            nacional: substr($digitos, 3),
        );
    }

    public static function esValido(?string $crudo): bool
    {
        return self::desde($crudo) !== null;
    }

    public function esMexico(): bool
    {
        return $this->pais === self::MEXICO;
    }

    /**
     * Los códigos 900 a 999 no identifican un país sino al fabricante del
     * dispositivo. Son válidos, pero un arete así no viene del padrón
     * nacional: conviene que la interfaz lo diga en vez de dar a entender
     * que el animal está registrado en SINIIGA.
     */
    public function esDeFabricante(): bool
    {
        return (int) $this->pais >= 900;
    }

    /** El código con el país separado, como se imprime en el arete. */
    public function formateado(): string
    {
        return $this->pais . ' ' . $this->nacional;
    }

    /**
     * Explicación de a qué corresponde el prefijo, para mostrar junto al
     * código sin que el usuario tenga que conocer la norma.
     */
    public function origen(): string
    {
        if ($this->esMexico()) {
            return 'México (SINIIGA)';
        }

        if ($this->esDeFabricante()) {
            return 'Código de fabricante, no de país';
        }

        return 'País ' . $this->pais;
    }

    /**
     * Lo que se envía al navegador para explicar el código leído.
     *
     * @return array{codigo:string, pais:string, nacional:string, formateado:string, origen:string, es_mexico:bool, es_fabricante:bool}
     */
    public function aArreglo(): array
    {
        return [
            'codigo' => $this->codigo,
            'pais' => $this->pais,
            'nacional' => $this->nacional,
            'formateado' => $this->formateado(),
            'origen' => $this->origen(),
            'es_mexico' => $this->esMexico(),
            'es_fabricante' => $this->esDeFabricante(),
        ];
    }
}
