<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización — {{ $animal->arete }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1f2937; }
        .header { border-bottom: 2px solid #15803d; padding-bottom: 8px; margin-bottom: 14px; }
        .header h1 { font-size: 16px; font-weight: 700; color: #15803d; }
        .header p { font-size: 8px; color: #6b7280; margin-top: 2px; }
        .aviso { background:#fffbeb; border:1px solid #fcd34d; border-radius:3px; padding:6px 8px; font-size:8px; color:#92400e; margin-bottom:14px; }
        .datos { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 9px; }
        .datos td { padding: 3px 5px; }
        .datos td.lbl { color: #6b7280; width: 22%; }
        .datos td.val { font-weight: 600; }
        table.desglose { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 6px; }
        table.desglose td { padding: 5px 6px; border-bottom: 1px solid #f1f5f9; }
        table.desglose td.num { text-align: right; font-weight: 600; }
        .sep td { border-top: 2px solid #cbd5e1; }
        .total td { font-size: 12px; font-weight: 700; color: #15803d; padding-top: 8px; }
        h2 { font-size: 11px; color: #374151; margin: 14px 0 6px; }
        table.detalle { width: 100%; border-collapse: collapse; font-size: 8px; }
        table.detalle thead th { background: #f1f5f9; padding: 4px; text-align: left; color: #475569; font-size: 7px; text-transform: uppercase; }
        table.detalle tbody td { padding: 3px 4px; border-bottom: 1px solid #f8fafc; }
        table.detalle tbody td.num { text-align: right; }
    </style>
</head>
<body>

<div class="header">
    <h1>Cotización de borrega</h1>
    <p>Rastro Claro &nbsp;·&nbsp; Generado el {{ now()->format('d/m/Y H:i') }}</p>
</div>

<div class="aviso">
    Estimación interna del precio, basada en costos acumulados, genética y estado reproductivo.
    No constituye un precio de mercado garantizado.
</div>

<table class="datos">
    <tr>
        <td class="lbl">Arete</td><td class="val">{{ $animal->arete }}</td>
        <td class="lbl">Alias</td><td class="val">{{ $animal->alias ?? '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">Especie / Raza</td><td class="val">{{ $animal->especie }} — {{ $animal->raza ?? '—' }}</td>
        <td class="lbl">Sexo</td><td class="val">{{ $animal->sexo === 'M' ? 'Macho' : 'Hembra' }}</td>
    </tr>
    <tr>
        <td class="lbl">Fecha de nacimiento</td><td class="val">{{ $animal->fecha_nac ? \Carbon\Carbon::parse($animal->fecha_nac)->format('d/m/Y') : '—' }}</td>
        <td class="lbl">Lote</td><td class="val">{{ $animal->lote?->nombre ?? '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">Madre</td><td class="val">{{ $animal->madre?->arete ?? '—' }}</td>
        <td class="lbl">Padre</td><td class="val">{{ $animal->padre?->arete ?? $animal->padreExterno?->nombre ?? '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">Identificador</td><td class="val">{{ $animal->microchip_codigo ?? '—' }}</td>
        <td class="lbl">N° de registro</td><td class="val">{{ $animal->genetica?->numero_registro ?? '—' }}</td>
    </tr>
</table>

@php $b = $calculo['buckets']; @endphp

<h2>Desglose del precio</h2>
<table class="desglose">
    <tr><td>Costo de gestación asignado</td><td class="num">${{ number_format($b['costo_gestacion'], 2) }}</td></tr>
    <tr><td>Costo de nacimiento o adquisición</td><td class="num">${{ number_format($b['costo_inicial'], 2) }}</td></tr>
    <tr><td>Costos sanitarios (vacunas, medicamentos, tratamientos)</td><td class="num">${{ number_format($b['costo_sanitario'], 2) }}</td></tr>
    <tr><td>Alimentación</td><td class="num">${{ number_format($b['costo_alimentacion'], 2) }}</td></tr>
    <tr><td>Registro de pureza</td><td class="num">${{ number_format($b['costo_registro'], 2) }}</td></tr>
    <tr><td>Mano de obra</td><td class="num">${{ number_format($b['costo_mano_obra'], 2) }}</td></tr>
    <tr><td>Transporte</td><td class="num">${{ number_format($b['costo_transporte'], 2) }}</td></tr>
    <tr><td>Otros costos</td><td class="num">${{ number_format($b['otros_costos'], 2) }}</td></tr>
    <tr class="sep"><td><strong>Costo total de producción</strong></td><td class="num">${{ number_format($calculo['costo_total_produccion'], 2) }}</td></tr>
    <tr><td>Margen genético ({{ number_format($calculo['porcentaje_margen_genetico'], 2) }} %)</td><td class="num">${{ number_format($calculo['valor_margen_genetico'], 2) }}</td></tr>
    <tr><td>Plus reproductivo{{ $calculo['estado_reproductivo_valuacion'] ? ' — ' . str_replace('_', ' ', $calculo['estado_reproductivo_valuacion']) : '' }}</td><td class="num">${{ number_format($calculo['plus_reproductivo'], 2) }}</td></tr>
    <tr><td>Ajuste manual</td><td class="num">${{ number_format($calculo['ajuste_manual'], 2) }}</td></tr>
    <tr class="sep total"><td>Precio final estimado</td><td class="num">${{ number_format($calculo['precio_estimado'], 2) }} MXN</td></tr>
</table>

@if(!empty($calculo['avisos']))
    <div class="aviso">
        @foreach($calculo['avisos'] as $aviso)
            {{ $aviso }}<br>
        @endforeach
    </div>
@endif

<h2>Movimientos que componen el costo</h2>
<table class="detalle">
    <thead>
        <tr>
            <th>Fecha</th><th>Categoría</th><th>Concepto</th>
            <th>Cantidad</th><th>Costo</th><th>Método de distribución</th>
        </tr>
    </thead>
    <tbody>
        @forelse($calculo['detalles'] as $d)
        <tr>
            <td>{{ $d['fecha'] ? \Carbon\Carbon::parse($d['fecha'])->format('d/m/Y') : '—' }}</td>
            <td>{{ ucfirst(str_replace('_', ' ', $d['categoria'])) }}</td>
            <td>{{ $d['concepto'] }}</td>
            <td class="num">{{ $d['cantidad'] !== null ? number_format($d['cantidad'], 2) . ' ' . ($d['unidad'] ?? '') : '—' }}</td>
            <td class="num">${{ number_format($d['costo_total'], 2) }}</td>
            <td>{{ $d['metodo_distribucion'] ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center; color:#9ca3af; padding:10px;">Este animal todavía no tiene costos registrados.</td></tr>
        @endforelse
    </tbody>
</table>

@if($valuacion && $valuacion->precio_real_venta !== null)
    <h2>Resultado de la venta</h2>
    <table class="desglose">
        <tr><td>Precio real de venta</td><td class="num">${{ number_format($valuacion->precio_real_venta, 2) }}</td></tr>
        <tr><td>Costo total de producción</td><td class="num">${{ number_format($valuacion->costo_total_produccion, 2) }}</td></tr>
        <tr class="sep"><td><strong>{{ $valuacion->utilidad >= 0 ? 'Utilidad' : 'Pérdida' }}</strong></td>
            <td class="num">${{ number_format($valuacion->utilidad, 2) }}
                @if($valuacion->porcentaje_utilidad !== null)
                    ({{ number_format($valuacion->porcentaje_utilidad, 2) }} %)
                @endif
            </td></tr>
    </table>
@endif

</body>
</html>
