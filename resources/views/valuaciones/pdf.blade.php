<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización {{ $animal->arete }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #172b22; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin-top: 22px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 8px; border-bottom: 1px solid #d8e2dc; text-align: left; }
        th { background: #edf3ef; }
        .importe { text-align: right; white-space: nowrap; }
        .total { font-size: 16px; font-weight: bold; }
        .nota { color: #52675b; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    @php($moneda = auth()->user()?->settings['currency'] ?? 'MXN')
    @php($dinero = fn ($monto) => number_format((float) $monto, 2) . ' ' . $moneda)
    <h1>Cotización de {{ $animal->alias ?: $animal->arete }}</h1>
    <p class="nota">Arete {{ $animal->arete }} · {{ $animal->especie }} · {{ $animal->raza }} · {{ now()->format('d/m/Y') }}</p>
    <table>
        <tr><th>Registro</th><td>{{ $animal->numero_registro ?: 'Sin registro' }}</td><th>Pureza</th><td>{{ $animal->grado_pureza ?: 'Sin dato' }}</td></tr>
        <tr><th>Microchip</th><td>{{ $animal->lectura_microchip ?: 'Sin dato' }}</td><th>Lote</th><td>{{ $animal->lote?->nombre ?: 'Sin lote' }}</td></tr>
    </table>
    <h2>Desglose de costos</h2>
    <table>
        <thead><tr><th>Concepto</th><th>Fecha</th><th class="importe">Importe</th></tr></thead>
        <tbody>
        @forelse($calculo['detalles'] as $detalle)
            <tr>
                <td>{{ $detalle['concepto'] }}
                    @if(!empty($detalle['metodo_distribucion']))
                        <br><span class="nota">{{ $detalle['metodo_distribucion'] }}</span>
                    @endif
                </td>
                <td>{{ empty($detalle['fecha']) ? 'Sin fecha' : \Carbon\Carbon::parse($detalle['fecha'])->format('d/m/Y') }}</td>
                <td class="importe">{{ $dinero($detalle['costo_total']) }}</td>
            </tr>
        @empty
            <tr><td colspan="3">No hay costos registrados.</td></tr>
        @endforelse
        </tbody>
    </table>
    <h2>Precio estimado</h2>
    <table>
        <tr><td>Costo total de producción</td><td class="importe">{{ $dinero($calculo['costo_total_produccion']) }}</td></tr>
        <tr><td>Margen genético ({{ $calculo['porcentaje_margen_genetico'] }} %)</td><td class="importe">{{ $dinero($calculo['valor_margen_genetico']) }}</td></tr>
        <tr><td>Plus reproductivo · {{ str_replace('_', ' ', $calculo['estado_reproductivo_valuacion']) }}</td><td class="importe">{{ $dinero($calculo['plus_reproductivo']) }}</td></tr>
        <tr><td>Ajuste manual</td><td class="importe">{{ $dinero($calculo['ajuste_manual']) }}</td></tr>
        <tr class="total"><td>Precio estimado</td><td class="importe">{{ $dinero($calculo['precio_estimado']) }}</td></tr>
    </table>
    @if($calculo['motivo_ajuste'])<p>Motivo del ajuste: {{ $calculo['motivo_ajuste'] }}</p>@endif
    @foreach($calculo['avisos'] as $aviso)<p class="nota">{{ $aviso }}</p>@endforeach
</body>
</html>
