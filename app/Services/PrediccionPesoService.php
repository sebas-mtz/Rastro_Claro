<?php

namespace App\Services;

use App\Models\Animal;

/**
 * Proyección del peso futuro de un ejemplar a partir de sus pesajes.
 *
 * Es el mismo método del script de análisis (scripts/prediccion_pesajes.py),
 * portado a PHP para que la ficha no dependa de tener Python instalado en el
 * servidor donde se instale el sistema.
 *
 * Por qué no basta una recta
 * --------------------------
 * Un animal no crece en línea recta: crece rápido de joven, se frena al
 * acercarse a su peso adulto y termina en una meseta. Una recta ajustada a los
 * primeros meses proyecta borregas de 200 kg.
 *
 * Pero la recta tampoco sobra: en tramos cortos describe bien el crecimiento y
 * su pendiente ES la ganancia diaria de peso, la cifra con la que se trabaja.
 *
 * Así que se ajustan las dos y se elige por AICc, que premia el ajuste pero
 * castiga meter parámetros de más. Con pocos pesajes eso favorece a la recta,
 * y es lo correcto: con tres puntos nadie puede decir dónde está la meseta.
 */
class PrediccionPesoService
{
    /** Con menos pesajes que esto, afirmar dónde está la meseta es inventar. */
    public const MINIMO_PARA_CURVA = 5;

    /** Sin dos mediciones no hay forma de saber cómo crece. */
    public const MINIMO_ABSOLUTO = 2;

    public function __construct(private readonly int $diasFuturo = 90)
    {
    }

    /**
     * @return array|null  null cuando no hay datos suficientes; en ese caso la
     *                     ficha no muestra la sección, en vez de enseñar ceros.
     */
    public function para(Animal $animal): ?array
    {
        $pesajes = $animal->pesajes()
            ->whereNotNull('peso')
            ->where('peso', '>', 0)
            ->orderBy('fecha')
            ->get(['fecha', 'peso']);

        // Dos básculas el mismo día no son dos puntos: es uno medido dos veces.
        $porFecha = [];

        foreach ($pesajes as $p) {
            $clave = $p->fecha instanceof \DateTimeInterface
                ? $p->fecha->format('Y-m-d')
                : (string) $p->fecha;

            $porFecha[$clave][] = (float) $p->peso;
        }

        if (count($porFecha) < self::MINIMO_ABSOLUTO) {
            return null;
        }

        ksort($porFecha);

        $fechas = array_keys($porFecha);
        $origen = new \DateTimeImmutable($fechas[0]);

        $t = [];
        $y = [];

        foreach ($porFecha as $fecha => $pesos) {
            $t[] = (float) $origen->diff(new \DateTimeImmutable($fecha))->days;
            $y[] = array_sum($pesos) / count($pesos);
        }

        // Todos los pesajes el mismo día: no hay recorrido temporal que modelar.
        if (end($t) <= 0) {
            return null;
        }

        return $this->analizar($t, $y, $origen);
    }

    private function analizar(array $t, array $y, \DateTimeImmutable $origen): array
    {
        $n = count($y);
        $ultimoT = end($t);

        $candidatos = array_filter([
            $this->ajustarLineal($t, $y),
            $n >= self::MINIMO_PARA_CURVA ? $this->ajustarGompertz($t, $y) : null,
        ]);

        usort($candidatos, fn ($a, $b) => $a['aicc'] <=> $b['aicc']);
        $mejor = $candidatos[0];

        // Ganancia observada, medida sobre los datos reales. No sale del
        // modelo: es lo que de hecho pasó.
        $gdpMedia = ($y[$n - 1] - $y[0]) / ($ultimoT - $t[0]);

        // Cómo va AHORA, que suele importar más que el promedio de su vida.
        $gdpReciente = $t[$n - 1] > $t[$n - 2]
            ? ($y[$n - 1] - $y[$n - 2]) / ($t[$n - 1] - $t[$n - 2])
            : null;

        $recorrido = max($ultimoT - $t[0], 1.0);

        // Curva ajustada sobre el tramo con datos, para poder dibujarla junto
        // a los puntos y ver si de verdad los sigue.
        $ajustada = [];

        foreach ($t as $i => $dia) {
            $ajustada[] = [
                'fecha' => $origen->modify("+{$dia} days")->format('Y-m-d'),
                'real' => round($y[$i], 2),
                'ajuste' => round($this->evaluar($mejor, $dia), 2),
            ];
        }

        // Proyección. Se muestrea cada pocos días: la ficha no necesita 90
        // puntos para dibujar una curva suave.
        $paso = max(1, (int) round($this->diasFuturo / 18));
        $futuro = [];

        for ($d = $paso; $d <= $this->diasFuturo; $d += $paso) {
            $dia = $ultimoT + $d;
            $peso = $this->evaluar($mejor, $dia);

            // El margen se ensancha al alejarse del último dato real. No es un
            // intervalo de confianza formal: es un recordatorio visual de que
            // extrapolar tiene coste.
            $margen = $mejor['rmse'] * (1 + $d / $recorrido);

            $futuro[] = [
                'fecha' => $origen->modify('+' . (int) $dia . ' days')->format('Y-m-d'),
                'dias' => $d,
                'peso' => round($peso, 2),
                'min' => round(max(0, $peso - $margen), 2),
                'max' => round($peso + $margen, 2),
            ];
        }

        $hitos = [];

        foreach (array_unique(array_merge(
            array_filter([30, 60, 90], fn ($d) => $d <= $this->diasFuturo),
            [$this->diasFuturo]
        )) as $d) {
            $hitos[] = [
                'dias' => $d,
                'fecha' => $origen->modify('+' . (int) ($ultimoT + $d) . ' days')->format('Y-m-d'),
                'peso' => round($this->evaluar($mejor, $ultimoT + $d), 2),
                'margen' => round($mejor['rmse'] * (1 + $d / $recorrido), 2),
            ];
        }

        return [
            'n_pesajes' => $n,
            'periodo_dias' => (int) $ultimoT,
            'peso_actual' => round($y[$n - 1], 2),
            'gdp_media' => round($gdpMedia, 4),
            'gdp_reciente' => $gdpReciente !== null ? round($gdpReciente, 4) : null,
            'modelo' => $mejor['nombre'],
            'r2' => round($mejor['r2'], 4),
            'rmse' => round($mejor['rmse'], 2),
            'serie' => $ajustada,
            'futuro' => $futuro,
            'hitos' => array_values($hitos),
            'aviso' => $this->aviso($n, $gdpMedia, $gdpReciente),
        ];
    }

    // ─── Modelos ──────────────────────────────────────────────────────────

    /** Recta: peso = a + b·días. `b` es la ganancia diaria. */
    private function ajustarLineal(array $t, array $y): array
    {
        $n = count($t);
        $sumT = array_sum($t);
        $sumY = array_sum($y);
        $sumTY = 0.0;
        $sumTT = 0.0;

        foreach ($t as $i => $ti) {
            $sumTY += $ti * $y[$i];
            $sumTT += $ti * $ti;
        }

        $denominador = $n * $sumTT - $sumT * $sumT;

        // Sin recorrido temporal la pendiente no existe; se devuelve la media.
        if (abs($denominador) < 1e-12) {
            $media = $sumY / $n;

            return $this->medir('Lineal', ['a' => $media, 'b' => 0.0], $t, $y, 2);
        }

        $b = ($n * $sumTY - $sumT * $sumY) / $denominador;
        $a = ($sumY - $b * $sumT) / $n;

        return $this->medir('Lineal', ['a' => $a, 'b' => $b], $t, $y, 2);
    }

    /**
     * Gompertz: peso = A · exp(−b · exp(−k · t))
     *
     * A es el peso adulto, k la tasa de madurez. Es el modelo estándar para
     * curvas de crecimiento en ovinos porque su punto de inflexión cae antes
     * de la mitad del peso adulto, que es como crecen de verdad los corderos.
     *
     * Se ajusta sin librerías de optimización: fijando A, la ecuación se
     * vuelve lineal —ln(−ln(W/A)) = ln(b) − k·t— y basta mínimos cuadrados.
     * Así que se barre A y se conserva el que menos error deje en el espacio
     * original. Dos pasadas: una gruesa y otra fina alrededor del ganador.
     */
    private function ajustarGompertz(array $t, array $y): ?array
    {
        $maximo = max($y);
        $mejor = null;

        // Primera pasada, amplia; segunda, estrecha alrededor del ganador.
        $desde = $maximo * 1.02;
        $hasta = $maximo * 3.0;

        for ($pasada = 0; $pasada < 2; $pasada++) {
            $pasos = 120;
            $ancho = ($hasta - $desde) / $pasos;

            for ($i = 0; $i <= $pasos; $i++) {
                $A = $desde + $i * $ancho;
                $ajuste = $this->gompertzConAsintota($t, $y, $A);

                if ($ajuste && ($mejor === null || $ajuste['rss'] < $mejor['rss'])) {
                    $mejor = $ajuste;
                }
            }

            if ($mejor === null) {
                return null;
            }

            $desde = max($maximo * 1.001, $mejor['params']['A'] - $ancho * 2);
            $hasta = $mejor['params']['A'] + $ancho * 2;
        }

        return $this->medir('Gompertz', $mejor['params'], $t, $y, 3);
    }

    /** Mínimos cuadrados sobre la forma linealizada, con A fijo. */
    private function gompertzConAsintota(array $t, array $y, float $A): ?array
    {
        $tt = [];
        $yy = [];

        foreach ($y as $i => $peso) {
            $razon = $peso / $A;

            // Fuera del dominio del logaritmo: ese punto no informa con esta A.
            if ($razon <= 0 || $razon >= 1) {
                continue;
            }

            $interior = -log($razon);

            if ($interior <= 0) {
                continue;
            }

            $tt[] = $t[$i];
            $yy[] = log($interior);
        }

        if (count($tt) < 3) {
            return null;
        }

        $n = count($tt);
        $sumT = array_sum($tt);
        $sumY = array_sum($yy);
        $sumTY = 0.0;
        $sumTT = 0.0;

        foreach ($tt as $i => $ti) {
            $sumTY += $ti * $yy[$i];
            $sumTT += $ti * $ti;
        }

        $den = $n * $sumTT - $sumT * $sumT;

        if (abs($den) < 1e-12) {
            return null;
        }

        $pendiente = ($n * $sumTY - $sumT * $sumY) / $den;
        $intercepto = ($sumY - $pendiente * $sumT) / $n;

        $k = -$pendiente;
        $b = exp($intercepto);

        // Un crecimiento que no crece, o que explota, no describe a un animal.
        if ($k <= 0 || $k > 1 || $b <= 0 || ! is_finite($b)) {
            return null;
        }

        $params = ['A' => $A, 'b' => $b, 'k' => $k];
        $rss = 0.0;

        foreach ($t as $i => $ti) {
            $rss += ($y[$i] - $this->gompertz($ti, $params)) ** 2;
        }

        return ['params' => $params, 'rss' => $rss];
    }

    private function gompertz(float $t, array $p): float
    {
        return $p['A'] * exp(-$p['b'] * exp(-$p['k'] * $t));
    }

    private function evaluar(array $modelo, float $t): float
    {
        $p = $modelo['params'];

        return $modelo['nombre'] === 'Gompertz'
            ? $this->gompertz($t, $p)
            : $p['a'] + $p['b'] * $t;
    }

    // ─── Bondad de ajuste ─────────────────────────────────────────────────

    private function medir(string $nombre, array $params, array $t, array $y, int $nParams): array
    {
        $modelo = ['nombre' => $nombre, 'params' => $params];

        $n = count($y);
        $media = array_sum($y) / $n;
        $rss = 0.0;
        $tss = 0.0;

        foreach ($t as $i => $ti) {
            $rss += ($y[$i] - $this->evaluar($modelo, $ti)) ** 2;
            $tss += ($y[$i] - $media) ** 2;
        }

        $modelo['r2'] = $tss > 0 ? 1 - $rss / $tss : 0.0;
        $modelo['rmse'] = sqrt($rss / $n);
        $modelo['aicc'] = $this->aicc($rss, $n, $nParams);

        return $modelo;
    }

    /**
     * Criterio de Akaike corregido para muestras pequeñas.
     *
     * Con los pocos pesajes que suele tener un animal, el AIC normal premia
     * modelos demasiado complejos; la corrección lo evita. Devuelve infinito
     * cuando no hay grados de libertad suficientes, así ese modelo pierde la
     * comparación en vez de ganarla por casualidad.
     */
    private function aicc(float $rss, int $n, int $nParams): float
    {
        if ($n <= $nParams + 1) {
            return INF;
        }

        $rss = max($rss, 1e-12);
        $aic = $n * log($rss / $n) + 2 * $nParams;

        return $aic + (2 * $nParams * ($nParams + 1)) / ($n - $nParams - 1);
    }

    /**
     * Hacia dónde se equivoca la recta con este animal en concreto.
     *
     * El sentido del error depende de la fase de crecimiento, no del modelo:
     * un cordero que aún acelera hace que la recta se quede corta; uno que ya
     * se acerca a su peso adulto hace que se pase. Se distingue comparando la
     * ganancia del último tramo con la media.
     */
    private function aviso(int $n, float $media, ?float $reciente): ?string
    {
        if ($n >= self::MINIMO_PARA_CURVA) {
            return null;
        }

        $base = "Con {$n} pesajes solo se pudo ajustar una recta, y el crecimiento real "
            . 'es una curva que se aplana. ';

        if ($reciente === null || $media <= 0) {
            return $base . 'Registra más pesajes para afinar la proyección.';
        }

        if ($reciente > $media * 1.1) {
            return $base . 'Este ejemplar viene ganando más que su promedio, '
                . 'así que la proyección probablemente se queda corta.';
        }

        if ($reciente < $media * 0.9) {
            return $base . 'Este ejemplar viene ganando menos que su promedio, '
                . 'así que la proyección probablemente se pasa.';
        }

        return $base . 'Crece a ritmo parejo, así que a corto plazo es razonable.';
    }
}
