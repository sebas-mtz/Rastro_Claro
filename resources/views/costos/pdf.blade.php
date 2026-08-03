<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Costos — {{ now()->format('d/m/Y') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #1f2937; }
        .header { border-bottom: 2px solid #2563eb; padding-bottom: 8px; margin-bottom: 14px; }
        .header h1 { font-size: 16px; font-weight: 700; color: #1e40af; }
        .header p { font-size: 8px; color: #6b7280; margin-top: 2px; }
        .resumen { display: table; width: 100%; border-collapse: separate; border-spacing: 4px; margin-bottom: 14px; }
        .resumen .row { display: table-row; }
        .stat { display: table-cell; width: 25%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 6px 8px; }
        .stat .val { font-size: 14px; font-weight: 700; color: #1e40af; }
        .stat .lbl { font-size: 7px; color: #6b7280; margin-top: 2px; }
        table.data { width: 100%; border-collapse: collapse; font-size: 8px; }
        table.data thead th { background: #f1f5f9; padding: 4px 5px; text-align: left; font-weight: 700; color: #475569; border-bottom: 1px solid #cbd5e1; font-size: 7px; text-transform: uppercase; }
        table.data tbody tr { border-bottom: 1px solid #f1f5f9; }
        table.data tbody tr:nth-child(even) { background: #f8fafc; }
        table.data tbody td { padding: 4px 5px; vertical-align: top; color: #374151; }
        table.data tbody td.num { text-align: right; font-weight: 600; }
        .pos { color: #15803d; } .neg { color: #b91c1c; }
    </style>
</head>
<body>

<div class="header">
    <h1>Reporte de Costos</h1>
    <p>Rastro Claro &nbsp;·&nbsp; Generado el {{ now()->format('d/m/Y H:i') }}</p>
</div>

<div class="resumen">
    <div class="row">
        <div class="stat">
            <div class="val">${{ number_format($totales['total_general'], 2) }} MXN</div>
            <div class="lbl">Total costos ({{ $totales['cantidad_registros'] }} registros)</div>
        </div>
        <div class="stat">
            <div class="val">${{ number_format($comparacion['ingresos'], 2) }} MXN</div>
            <div class="lbl">Ingresos por ventas</div>
        </div>
        <div class="stat">
            <div class="val {{ $comparacion['utilidad'] >= 0 ? 'pos' : 'neg' }}">
                ${{ number_format($comparacion['utilidad'], 2) }} MXN
            </div>
            <div class="lbl">{{ $comparacion['estado'] === 'utilidad' ? 'Utilidad' : 'Pérdida' }}</div>
        </div>
        <div class="stat">
            <div class="val">${{ number_format($totales['costo_promedio_por_animal'], 2) }} MXN</div>
            <div class="lbl">Costo promedio por animal</div>
        </div>
    </div>
</div>

<table class="data">
    <thead>
        <tr>
            <th>Fecha</th><th>Concepto</th><th>Categoría</th><th>Tipo</th>
            <th>Monto</th><th>Animal</th><th>Lote</th><th>Proveedor</th><th>Comprobante</th>
        </tr>
    </thead>
    <tbody>
        @forelse($costos as $costo)
        <tr>
            <td>{{ $costo->fecha->format('d/m/Y') }}</td>
            <td>{{ $costo->concepto }}</td>
            <td>{{ $costo->categoria }}</td>
            <td>{{ $costo->tipo_costo }}</td>
            <td class="num">${{ number_format($costo->monto, 2) }}</td>
            <td>{{ $costo->animal?->arete ?? '—' }}</td>
            <td>{{ $costo->lote?->nombre ?? '—' }}</td>
            <td>{{ $costo->proveedor ?? '—' }}</td>
            <td>{{ $costo->numero_comprobante ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center; color:#9ca3af; padding: 10px;">Sin registros para los filtros aplicados.</td></tr>
        @endforelse
    </tbody>
</table>

</body>
</html>
