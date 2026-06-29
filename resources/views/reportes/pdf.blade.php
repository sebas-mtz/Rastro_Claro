<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte — {{ now()->format('d/m/Y') }}</title>
    <style>
        /* ── Reset básico ────────────────────────────────── */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            color: #1f2937;
            background: #fff;
        }

        /* ── Cabecera del documento ──────────────────────── */
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }
        .header-top {
            display: table;
            width: 100%;
        }
        .header-left  { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }

        .header h1 {
            font-size: 16px;
            font-weight: 700;
            color: #1e40af;
        }
        .header p {
            font-size: 8px;
            color: #6b7280;
            margin-top: 2px;
        }

        /* ── Filtros aplicados ───────────────────────────── */
        .filtros-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 14px;
            font-size: 8px;
            color: #0369a1;
        }
        .filtros-box strong { color: #0c4a6e; }

        /* ── Resumen ejecutivo ───────────────────────────── */
        .resumen-grid {
            display: table;
            width: 100%;
            margin-bottom: 14px;
            border-collapse: separate;
            border-spacing: 4px;
        }
        .resumen-row { display: table-row; }
        .stat-card {
            display: table-cell;
            width: 20%;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 8px;
            vertical-align: top;
        }
        .stat-card .val {
            font-size: 14px;
            font-weight: 700;
            color: #1e40af;
            line-height: 1;
        }
        .stat-card .lbl {
            font-size: 7px;
            color: #6b7280;
            margin-top: 2px;
        }
        .stat-card .sub {
            font-size: 7px;
            color: #9ca3af;
        }

        /* ── Sección ─────────────────────────────────────── */
        .section {
            margin-bottom: 18px;
            page-break-inside: avoid;
        }
        .section-header {
            background: #1e40af;
            color: #fff;
            padding: 5px 10px;
            border-radius: 3px 3px 0 0;
            font-size: 9px;
            font-weight: 700;
            display: table;
            width: 100%;
        }
        .section-header .title { display: table-cell; }
        .section-header .count {
            display: table-cell;
            text-align: right;
            font-weight: 400;
            opacity: .8;
        }

        /* Colores de header por módulo */
        .hdr-teal    { background: #0f766e; }
        .hdr-amber   { background: #b45309; }
        .hdr-purple  { background: #7c3aed; }
        .hdr-green   { background: #15803d; }
        .hdr-orange  { background: #c2410c; }
        .hdr-rose    { background: #be123c; }
        .hdr-sky     { background: #0284c7; }
        .hdr-emerald { background: #047857; }

        /* ── Tabla ───────────────────────────────────────── */
        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        table.data thead tr {
            background: #f1f5f9;
        }
        table.data thead th {
            padding: 4px 5px;
            text-align: left;
            font-weight: 700;
            color: #475569;
            border-bottom: 1px solid #cbd5e1;
            white-space: nowrap;
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        table.data tbody tr { border-bottom: 1px solid #f1f5f9; }
        table.data tbody tr:nth-child(even) { background: #f8fafc; }
        table.data tbody td {
            padding: 4px 5px;
            vertical-align: top;
            color: #374151;
        }
        table.data tbody td.mono { font-family: "Courier New", monospace; }
        table.data tbody td.num  { text-align: right; font-weight: 600; }
        table.data tbody td.gray { color: #9ca3af; }

        /* ── Badges ──────────────────────────────────────── */
        .badge {
            display: inline-block;
            border-radius: 99px;
            padding: 1px 5px;
            font-size: 7px;
            font-weight: 700;
            line-height: 1.4;
        }
        .badge-green  { background: #d1fae5; color: #065f46; }
        .badge-amber  { background: #fef3c7; color: #92400e; }
        .badge-red    { background: #fee2e2; color: #991b1b; }
        .badge-blue   { background: #dbeafe; color: #1e40af; }
        .badge-gray   { background: #f1f5f9; color: #475569; }
        .badge-purple { background: #ede9fe; color: #6d28d9; }
        .badge-teal   { background: #ccfbf1; color: #0f766e; }
        .badge-sky    { background: #e0f2fe; color: #075985; }
        .badge-pink   { background: #fce7f3; color: #9d174d; }
        .badge-orange { background: #ffedd5; color: #9a3412; }

        /* ── Alerta ──────────────────────────────────────── */
        .alert {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 3px;
            padding: 4px 8px;
            font-size: 8px;
            color: #92400e;
            margin-bottom: 6px;
        }

        /* ── Variación de peso ───────────────────────────── */
        .pos { color: #15803d; font-weight: 600; }
        .neg { color: #b91c1c; font-weight: 600; }

        /* ── Pie de página ───────────────────────────────── */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #e5e7eb;
            padding: 4px 0;
            font-size: 7px;
            color: #9ca3af;
            text-align: center;
        }

        /* ── Texto vacío ─────────────────────────────────── */
        .empty { color: #9ca3af; font-style: italic; padding: 8px 10px; font-size: 8px; }

        /* ── Salto de página ─────────────────────────────── */
        .page-break { page-break-before: always; }
    </style>
</head>
<body>

{{-- ── Pie de página fijo ─────────────────────────────────────────────────── --}}
<div class="footer">
    Generado el {{ now()->format('d/m/Y H:i') }} &nbsp;·&nbsp;
    Módulo: {{ ucfirst($filtros['modulo'] ?? 'general') }} &nbsp;·&nbsp;
    Página <span class="pagenum"></span>
</div>

{{-- ── Cabecera ────────────────────────────────────────────────────────────── --}}
<div class="header">
    <div class="header-top">
        <div class="header-left">
            <h1>📋 Reporte de Ganadería</h1>
            <p>Sistema de gestión pecuaria &nbsp;·&nbsp; {{ now()->format('d/m/Y H:i') }}</p>
        </div>
        <div class="header-right">
            <p style="font-size:9px; color:#374151;">
                Módulo: <strong>{{ ucfirst($filtros['modulo'] ?? 'General') }}</strong>
            </p>
            @if(!empty($filtros['fecha_inicio']) || !empty($filtros['fecha_fin']))
                <p style="font-size:8px; color:#6b7280;">
                    Período:
                    {{ !empty($filtros['fecha_inicio']) ? \Carbon\Carbon::parse($filtros['fecha_inicio'])->format('d/m/Y') : '—' }}
                    al
                    {{ !empty($filtros['fecha_fin'])    ? \Carbon\Carbon::parse($filtros['fecha_fin'])->format('d/m/Y')    : '—' }}
                </p>
            @endif
        </div>
    </div>
</div>

{{-- ── Filtros aplicados ───────────────────────────────────────────────────── --}}
@php
    $etiquetas = [
        'especie'              => 'Especie',
        'raza'                 => 'Raza',
        'sexo'                 => 'Sexo',
        'lote_id'              => 'Lote ID',
        'estado_productivo'    => 'Est. Productivo',
        'tipo_evento'          => 'Tipo Evento',
        'estado_salud'         => 'Est. Salud',
        'estado_trat'          => 'Est. Tratamiento',
        'vacuna_id'            => 'Vacuna ID',
        'racion_id'            => 'Ración ID',
        'tipo_alimentacion'    => 'Tipo Alim.',
        'tipo_insumo'          => 'Tipo Insumo',
        'activo_insumo'        => 'Activo',
        'tipo_evento_repro'    => 'Evento Repro.',
        'tipo_servicio'        => 'Tipo Servicio',
        'resultado_diagnostico'=> 'Resultado Dx',
        'tipo_produccion'      => 'Tipo Prod.',
        'tipo_venta'           => 'Tipo Venta',
        'estado_venta'         => 'Est. Venta',
        'estado_pago'          => 'Est. Pago',
        'metodo_pago'          => 'Método Pago',
    ];
    $filtrosActivos = collect($filtros)
        ->except(['modulo','fecha_inicio','fecha_fin'])
        ->filter(fn($v) => $v !== null && $v !== '');
@endphp

@if($filtrosActivos->isNotEmpty())
<div class="filtros-box">
    <strong>Filtros aplicados:</strong>
    @foreach($filtrosActivos as $key => $val)
        {{ $etiquetas[$key] ?? $key }}: <strong>{{ $val }}</strong>
        @if(!$loop->last) &nbsp;·&nbsp; @endif
    @endforeach
</div>
@endif

{{-- ── Resumen ejecutivo ───────────────────────────────────────────────────── --}}
@if(isset($datos['resumen']))
    @php $r = $datos['resumen']; @endphp
    <div style="margin-bottom:6px; font-size:8px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">Resumen</div>
    <table style="width:100%; border-collapse:separate; border-spacing:4px; margin-bottom:14px;">
        <tr>
            <td style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:4px; padding:6px 8px; width:20%;">
                <div style="font-size:13px; font-weight:700; color:#1d4ed8;">{{ $r['total_animales'] ?? '—' }}</div>
                <div style="font-size:7px; color:#6b7280;">Animales<br><span style="color:#9ca3af;">{{ ($r['animales_machos'] ?? 0) }}M / {{ ($r['animales_hembras'] ?? 0) }}H</span></div>
            </td>
            <td style="background:#f0fdfa; border:1px solid #99f6e4; border-radius:4px; padding:6px 8px; width:20%;">
                <div style="font-size:13px; font-weight:700; color:#0f766e;">{{ $r['total_eventos_salud'] ?? '—' }}</div>
                <div style="font-size:7px; color:#6b7280;">Eventos salud<br><span style="color:#9ca3af;">{{ ($r['eventos_pendientes'] ?? 0) }} pend. / {{ ($r['eventos_vencidos'] ?? 0) }} venc.</span></div>
            </td>
            <td style="background:#fefce8; border:1px solid #fde68a; border-radius:4px; padding:6px 8px; width:20%;">
                <div style="font-size:13px; font-weight:700; color:#b45309;">{{ $r['total_vacunaciones'] ?? '—' }}</div>
                <div style="font-size:7px; color:#6b7280;">Vacunaciones<br><span style="color:#9ca3af;">{{ ($r['vacunaciones_aplicadas'] ?? 0) }} aplicadas</span></div>
            </td>
            <td style="background:#f5f3ff; border:1px solid #ddd6fe; border-radius:4px; padding:6px 8px; width:20%;">
                <div style="font-size:13px; font-weight:700; color:#6d28d9;">{{ $r['tratamientos_activos'] ?? '—' }}</div>
                <div style="font-size:7px; color:#6b7280;">Tratam. activos<br><span style="color:#9ca3af;">de {{ ($r['tratamientos_total'] ?? 0) }} totales</span></div>
            </td>
            <td style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:4px; padding:6px 8px; width:20%;">
                <div style="font-size:13px; font-weight:700; color:#15803d;">{{ $r['total_pesajes'] ?? '—' }}</div>
                <div style="font-size:7px; color:#6b7280;">Pesajes</div>
            </td>
        </tr>
        <tr>
            <td style="background:#fff7ed; border:1px solid #fed7aa; border-radius:4px; padding:6px 8px;">
                <div style="font-size:13px; font-weight:700; color:#c2410c;">{{ $r['total_alimentaciones'] ?? '—' }}</div>
                <div style="font-size:7px; color:#6b7280;">Reg. alimentación</div>
            </td>
            <td style="background:#f0fdfa; border:1px solid #99f6e4; border-radius:4px; padding:6px 8px;">
                <div style="font-size:13px; font-weight:700; color:#0f766e;">{{ $r['insumos_activos'] ?? '—' }}</div>
                <div style="font-size:7px; color:#6b7280;">Insumos activos<br><span style="color:#9ca3af;">de {{ ($r['insumos_total'] ?? 0) }} totales</span></div>
            </td>
            <td style="background:#fdf4ff; border:1px solid #f0abfc; border-radius:4px; padding:6px 8px;">
                <div style="font-size:13px; font-weight:700; color:#7e22ce;">{{ $r['total_partos'] ?? '—' }}</div>
                <div style="font-size:7px; color:#6b7280;">Partos<br><span style="color:#9ca3af;">SPC: {{ $r['servicios_por_concepcion'] ?? '—' }}</span></div>
            </td>
            <td style="background:#ecfdf5; border:1px solid #6ee7b7; border-radius:4px; padding:6px 8px;">
                <div style="font-size:13px; font-weight:700; color:#047857;">{{ $r['total_ventas'] ?? '—' }}</div>
                <div style="font-size:7px; color:#6b7280;">Ventas<br><span style="color:#9ca3af;">${{ number_format($r['ingresos_ventas'] ?? 0, 2) }}</span></div>
            </td>
            <td style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:4px; padding:6px 8px;">
                <div style="font-size:13px; font-weight:700; color:#0284c7;">{{ $r['total_produccion'] ?? '—' }}</div>
                <div style="font-size:7px; color:#6b7280;">Reg. producción</div>
            </td>
        </tr>
    </table>
@endif

{{-- ═══════════════════════════════════════════════════════
     SECCIONES DE DATOS
═══════════════════════════════════════════════════════ --}}

{{-- ── Animales ──────────────────────────────────────────────────────────── --}}
@if(isset($datos['animales']) && $datos['animales']['total'] > 0)
<div class="section">
    <div class="section-header">
        <span class="title">🐄 Animales</span>
        <span class="count">{{ $datos['animales']['total'] }} registros</span>
    </div>
    <table class="data">
        <thead>
            <tr>
                <th>Arete</th><th>Alias</th><th>Especie</th><th>Raza</th>
                <th>Sexo</th><th>Fecha Nac.</th><th>Peso</th><th>BCS</th>
                <th>Est. Productivo</th><th>Lote</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos['animales']['registros'] as $a)
            <tr>
                <td class="mono">{{ $a->arete }}</td>
                <td>{{ $a->alias ?? '—' }}</td>
                <td>{{ $a->especie }}</td>
                <td class="gray">{{ $a->raza ?? '—' }}</td>
                <td>
                    <span class="badge {{ $a->sexo === 'M' ? 'badge-blue' : 'badge-pink' }}">
                        {{ $a->sexo === 'M' ? 'Macho' : 'Hembra' }}
                    </span>
                </td>
                <td class="gray">{{ $a->fecha_nac ?? '—' }}</td>
                <td class="num">{{ $a->peso ? $a->peso.' kg' : '—' }}</td>
                <td>{{ $a->BCS ?? '—' }}</td>
                <td class="gray">{{ $a->estado_productivo ?? '—' }}</td>
                <td class="gray">{{ $a->lote?->nombre ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Eventos de Salud ─────────────────────────────────────────────────── --}}
@if(isset($datos['salud']) && $datos['salud']['total'] > 0)
<div class="section">
    <div class="section-header hdr-teal">
        <span class="title">💊 Eventos de Salud</span>
        <span class="count">{{ $datos['salud']['total'] }} registros</span>
    </div>
    <table class="data">
        <thead>
            <tr>
                <th>Animal</th><th>Especie</th><th>Lote</th><th>Tipo</th>
                <th>F. Programada</th><th>F. Aplicación</th><th>Estado</th>
                <th>Diagnóstico</th><th>Responsable</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos['salud']['registros'] as $ev)
            <tr>
                <td class="mono">{{ $ev->animal?->arete }}{{ $ev->animal?->alias ? ' ('.$ev->animal->alias.')' : '' }}</td>
                <td>{{ $ev->animal?->especie }}</td>
                <td class="gray">{{ $ev->animal?->lote?->nombre ?? '—' }}</td>
                <td><span class="badge badge-sky">{{ $ev->tipo }}</span></td>
                <td class="gray">{{ $ev->fecha_programada }}</td>
                <td class="gray">{{ $ev->fecha_aplicacion ?? '—' }}</td>
                <td>
                    @php $ec = match($ev->estado){ 'aplicada'=>'badge-green','pendiente'=>'badge-amber','vencida'=>'badge-red', default=>'badge-gray' }; @endphp
                    <span class="badge {{ $ec }}">{{ $ev->estado }}</span>
                </td>
                <td>{{ $ev->diagnostico ?? '—' }}</td>
                <td class="gray">{{ $ev->responsable ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Vacunaciones ─────────────────────────────────────────────────────── --}}
@if(isset($datos['vacunacion']) && $datos['vacunacion']['total'] > 0)
<div class="section">
    <div class="section-header hdr-amber">
        <span class="title">💉 Vacunaciones</span>
        <span class="count">{{ $datos['vacunacion']['total'] }} registros</span>
    </div>
    @if($datos['vacunacion']['refuerzo_vencido'] > 0)
        <div class="alert">⚠ {{ $datos['vacunacion']['refuerzo_vencido'] }} animales con refuerzo vencido.</div>
    @endif
    <table class="data">
        <thead>
            <tr>
                <th>Animal</th><th>Lote</th><th>Vacuna</th><th>Dosis</th>
                <th>Lote Vacuna</th><th>F. Programada</th><th>F. Aplicación</th>
                <th>Estado</th><th>Responsable</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos['vacunacion']['registros'] as $ev)
            <tr>
                <td class="mono">{{ $ev->animal?->arete }}{{ $ev->animal?->alias ? ' ('.$ev->animal->alias.')' : '' }}</td>
                <td class="gray">{{ $ev->animal?->lote?->nombre ?? '—' }}</td>
                <td>{{ $ev->vacuna?->nombre ?? '—' }}</td>
                <td>{{ $ev->dosis ?? '—' }}</td>
                <td class="gray">{{ $ev->lote_vacuna ?? '—' }}</td>
                <td class="gray">{{ $ev->fecha_programada }}</td>
                <td class="gray">{{ $ev->fecha_aplicacion ?? '—' }}</td>
                <td>
                    @php $ec = match($ev->estado){ 'aplicada'=>'badge-green','pendiente'=>'badge-amber','vencida'=>'badge-red', default=>'badge-gray' }; @endphp
                    <span class="badge {{ $ec }}">{{ $ev->estado }}</span>
                </td>
                <td class="gray">{{ $ev->responsable ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Tratamientos ─────────────────────────────────────────────────────── --}}
@if(isset($datos['tratamientos']) && $datos['tratamientos']['total'] > 0)
<div class="section">
    <div class="section-header hdr-purple">
        <span class="title">🩺 Tratamientos</span>
        <span class="count">{{ $datos['tratamientos']['total'] }} registros</span>
    </div>
    <table class="data">
        <thead>
            <tr>
                <th>Animal</th><th>Lote</th><th>Tratamiento</th>
                <th>F. Inicio</th><th>F. Fin prevista</th><th>Estado</th>
                <th>Responsable</th><th>Notas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos['tratamientos']['registros'] as $tr)
            <tr>
                <td class="mono">{{ $tr->animal?->arete }}{{ $tr->animal?->alias ? ' ('.$tr->animal->alias.')' : '' }}</td>
                <td class="gray">{{ $tr->animal?->lote?->nombre ?? '—' }}</td>
                <td>{{ $tr->nombre }}</td>
                <td class="gray">{{ $tr->fecha_inicio }}</td>
                <td class="gray">{{ $tr->fecha_fin ?? '—' }}</td>
                <td>
                    <span class="badge {{ $tr->estado === 'activo' ? 'badge-blue' : 'badge-gray' }}">{{ $tr->estado }}</span>
                </td>
                <td class="gray">{{ $tr->responsable ?? '—' }}</td>
                <td class="gray" style="max-width:120px;">{{ \Illuminate\Support\Str::limit($tr->notas ?? '—', 40) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Pesajes ──────────────────────────────────────────────────────────── --}}
@if(isset($datos['pesajes']) && $datos['pesajes']['total'] > 0)
<div class="section">
    <div class="section-header hdr-green">
        <span class="title">⚖ Pesajes</span>
        <span class="count">{{ $datos['pesajes']['total'] }} registros &nbsp;·&nbsp; Prom: {{ $datos['pesajes']['peso_promedio'] ?? '—' }} kg</span>
    </div>
    <table class="data">
        <thead>
            <tr>
                <th>Animal</th><th>Alias</th><th>Especie</th><th>Lote</th>
                <th>Fecha</th><th>Peso (kg)</th><th>Variación</th><th>Notas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos['pesajes']['registros'] as $i => $p)
            @php
                $anterior   = $datos['pesajes']['registros'][$i + 1] ?? null;
                $mismoAnim  = $anterior && $anterior->animal?->id === $p->animal?->id;
                $variacion  = $mismoAnim ? round($p->peso - $anterior->peso, 2) : null;
            @endphp
            <tr>
                <td class="mono">{{ $p->animal?->arete ?? '—' }}</td>
                <td class="gray">{{ $p->animal?->alias ?? '—' }}</td>
                <td>{{ $p->animal?->especie ?? '—' }}</td>
                <td class="gray">{{ $p->animal?->lote?->nombre ?? '—' }}</td>
                <td class="gray">{{ $p->fecha }}</td>
                <td class="num">{{ $p->peso }} kg</td>
                <td>
                    @if($variacion !== null)
                        <span class="{{ $variacion >= 0 ? 'pos' : 'neg' }}">
                            {{ $variacion >= 0 ? '+' : '' }}{{ $variacion }} kg
                        </span>
                    @else
                        —
                    @endif
                </td>
                <td class="gray">{{ $p->notas ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Alimentación ─────────────────────────────────────────────────────── --}}
@if(isset($datos['alimentacion']) && $datos['alimentacion']['total'] > 0)
<div class="section">
    <div class="section-header hdr-orange">
        <span class="title">🌾 Alimentación</span>
        <span class="count">{{ $datos['alimentacion']['total'] }} registros &nbsp;·&nbsp; Total: {{ $datos['alimentacion']['total_kg'] }} kg</span>
    </div>
    <table class="data">
        <thead>
            <tr>
                <th>Fecha</th><th>Hora</th><th>Ración</th><th>Animal / Lote</th>
                <th>Cantidad</th><th>Unidad</th><th>MS%</th><th>PB%</th><th>Notas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos['alimentacion']['registros'] as $a)
            <tr>
                <td class="gray">{{ $a->fecha }}</td>
                <td class="gray">{{ $a->hora ?? '—' }}</td>
                <td>{{ $a->racion?->nombre ?? '—' }}</td>
                <td class="mono">
                    @if($a->animal){{ $a->animal->arete }}{{ $a->animal->alias ? ' ('.$a->animal->alias.')' : '' }}
                    @elseif($a->lote) Lote: {{ $a->lote->nombre }}
                    @else —
                    @endif
                </td>
                <td class="num">{{ $a->cantidad }}</td>
                <td class="gray">{{ $a->unidad }}</td>
                {{-- FIX: leer desde racion, no snapshot_nutricion --}}
                <td class="gray">{{ $a->racion?->MS ?? '—' }}</td>
                <td class="gray">{{ $a->racion?->PB ?? '—' }}</td>
                <td class="gray">{{ \Illuminate\Support\Str::limit($a->notas ?? '—', 30) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Inventario ───────────────────────────────────────────────────────── --}}
@if(isset($datos['inventario']) && $datos['inventario']['total'] > 0)
<div class="section">
    <div class="section-header">
        <span class="title">📦 Inventario de Insumos</span>
        <span class="count">{{ $datos['inventario']['total'] }} registros</span>
    </div>
    <table class="data">
        <thead>
            <tr>
                <th>Nombre</th><th>Tipo</th><th>Existencias</th><th>Unidad</th>
                <th>MS%</th><th>PB%</th><th>Costo prom.</th><th>Activo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos['inventario']['registros'] as $inv)
            <tr>
                <td>{{ $inv->nombre }}</td>
                <td class="gray">{{ $inv->tipo }}</td>
                <td class="num {{ $inv->existencias <= 0 ? 'neg' : '' }}">{{ $inv->existencias }}</td>
                <td class="gray">{{ $inv->unidad ?? '—' }}</td>
                <td class="gray">{{ $inv->MS ?? '—' }}</td>
                <td class="gray">{{ $inv->PB ?? '—' }}</td>
                <td class="num">{{ $inv->costo_promedio ? '$'.number_format($inv->costo_promedio,2) : '—' }}</td>
                <td>
                    <span class="badge {{ $inv->activo ? 'badge-green' : 'badge-gray' }}">
                        {{ $inv->activo ? 'Sí' : 'No' }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Reproducción — eventos generales ────────────────────────────────── --}}
@if(isset($datos['reproduccion']) && $datos['reproduccion']['total'] > 0)
<div class="section">
    <div class="section-header hdr-rose">
        <span class="title">🐣 Eventos Reproductivos</span>
        <span class="count">{{ $datos['reproduccion']['total'] }} registros</span>
    </div>
    <table class="data">
        <thead>
            <tr>
                <th>Hembra</th><th>Especie</th><th>Lote</th>
                <th>Tipo Evento</th><th>Fecha</th><th>Costo</th><th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos['reproduccion']['registros'] as $ev)
            <tr>
                <td class="mono">{{ $ev->hembra?->arete }}{{ $ev->hembra?->alias ? ' ('.$ev->hembra->alias.')' : '' }}</td>
                <td>{{ $ev->hembra?->especie ?? '—' }}</td>
                <td class="gray">{{ $ev->lote?->nombre ?? '—' }}</td>
                <td><span class="badge badge-purple">{{ $ev->tipo_evento }}</span></td>
                <td class="gray">{{ $ev->fecha }}</td>
                <td class="num">{{ $ev->costo ? '$'.number_format($ev->costo,2) : '—' }}</td>
                <td class="gray">{{ \Illuminate\Support\Str::limit($ev->observaciones ?? '—', 40) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Servicios ────────────────────────────────────────────────────────── --}}
@if(isset($datos['servicios']) && $datos['servicios']['total'] > 0)
<div class="section">
    <div class="section-header hdr-rose">
        <span class="title">🧬 Servicios</span>
        <span class="count">{{ $datos['servicios']['total'] }} registros</span>
    </div>
    <table class="data">
        <thead>
            <tr>
                <th>Hembra</th><th>Lote</th><th>Tipo Servicio</th><th>Fecha</th>
                <th># Serv.</th><th>Macho / Pajilla</th><th>Técnico</th><th>Costo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos['servicios']['registros'] as $ev)
            @php $srv = $ev->servicio; @endphp
            <tr>
                <td class="mono">{{ $ev->hembra?->arete }}{{ $ev->hembra?->alias ? ' ('.$ev->hembra->alias.')' : '' }}</td>
                <td class="gray">{{ $ev->lote?->nombre ?? '—' }}</td>
                <td><span class="badge badge-violet">{{ $srv?->tipo_servicio ?? '—' }}</span></td>
                <td class="gray">{{ $ev->fecha }}</td>
                <td>{{ $srv?->numero_servicio ?? '—' }}</td>
                <td class="mono gray">
                <td class="mono gray">
                    @if($srv?->macho)
                        {{ $srv->macho->arete }}
                    @elseif($srv?->pajilla)
                        🧬 {{ $srv->pajilla->codigo }}
                        @php
                            $razaDonador = $srv->pajilla->animal?->raza
                                ?? $srv->pajilla->donadorExterno?->raza;
                        @endphp
                        @if($razaDonador)
                            ({{ $razaDonador }})
                        @endif
                    @else
                        —
                    @endif
                </td>
                <td class="gray">{{ $srv?->tecnico?->name ?? $srv?->tecnico_externo ?? '—' }}</td>
                <td class="num">{{ $ev->costo ? '$'.number_format($ev->costo,2) : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Diagnósticos de gestación ────────────────────────────────────────── --}}
@if(isset($datos['diagnosticos']) && $datos['diagnosticos']['total'] > 0)
<div class="section">
    <div class="section-header hdr-teal">
        <span class="title">🔬 Diagnósticos de Gestación</span>
        <span class="count">{{ $datos['diagnosticos']['total'] }} registros &nbsp;·&nbsp; Tasa concepción: {{ $datos['diagnosticos']['tasa_concepcion'] ?? '—' }}%</span>
    </div>
    <table class="data">
        <thead>
            <tr>
                <th>Hembra</th><th>Lote</th><th>Fecha Dx</th><th>Método</th>
                <th>Resultado</th><th>Días Gest.</th><th>F. Prob. Parto</th><th>Veterinario</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos['diagnosticos']['registros'] as $ev)
            @php $dx = $ev->diagnostico; @endphp
            <tr>
                <td class="mono">{{ $ev->hembra?->arete }}{{ $ev->hembra?->alias ? ' ('.$ev->hembra->alias.')' : '' }}</td>
                <td class="gray">{{ $ev->lote?->nombre ?? '—' }}</td>
                <td class="gray">{{ $ev->fecha }}</td>
                <td class="gray">{{ str_replace('_', ' ', $dx?->metodo ?? '—') }}</td>
                <td>
                    @if($dx?->resultado)
                        @php $rc = match($dx->resultado){ 'positivo'=>'badge-green','negativo'=>'badge-red','repetir'=>'badge-amber', default=>'badge-gray' }; @endphp
                        <span class="badge {{ $rc }}">{{ $dx->resultado }}</span>
                    @else —
                    @endif
                </td>
                <td>{{ $dx?->dias_gestacion_estimados ?? '—' }}</td>
                <td class="gray">{{ $dx?->fecha_probable_parto ?? '—' }}</td>
                <td class="gray">{{ $dx?->veterinario?->name ?? $dx?->veterinario_externo ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Partos ───────────────────────────────────────────────────────────── --}}
@if(isset($datos['partos']) && $datos['partos']['total'] > 0)
<div class="section">
    <div class="section-header hdr-emerald">
        <span class="title">🐮 Partos</span>
        <span class="count">{{ $datos['partos']['total'] }} registros &nbsp;·&nbsp; {{ $datos['partos']['total_crias'] }} crías &nbsp;·&nbsp; {{ $datos['partos']['con_complicaciones'] }} con complicaciones</span>
    </div>
    <table class="data">
        <thead>
            <tr>
                <th>Hembra</th><th>Lote</th><th>Fecha Parto</th><th>Tipo</th>
                <th># Crías</th><th>Asistencia</th><th>Complicaciones</th><th>Crías (aretes)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos['partos']['registros'] as $ev)
            @php $p = $ev->parto; @endphp
            <tr>
                <td class="mono">{{ $ev->hembra?->arete }}{{ $ev->hembra?->alias ? ' ('.$ev->hembra->alias.')' : '' }}</td>
                <td class="gray">{{ $ev->lote?->nombre ?? '—' }}</td>
                <td class="gray">{{ $ev->fecha }}</td>
                <td><span class="badge badge-teal">{{ $p?->tipo_parto ?? '—' }}</span></td>
                <td class="num">{{ $p?->numero_crias ?? '—' }}</td>
                <td>
                    <span class="badge {{ $p?->asistencia_requerida ? 'badge-amber' : 'badge-green' }}">
                        {{ $p?->asistencia_requerida ? 'Sí' : 'No' }}
                    </span>
                </td>
                <td>
                    <span class="badge {{ $p?->complicaciones ? 'badge-red' : 'badge-green' }}">
                        {{ $p?->complicaciones ? 'Sí' : 'No' }}
                    </span>
                </td>
                <td class="mono gray" style="font-size:7px;">
                    @foreach($p?->crias ?? [] as $c)
                        {{ $c->animal?->arete ?? $c->arete_temporal ?? 's/a' }}({{ strtoupper(substr($c->sexo ?? '?', 0, 1)) }})
                    @endforeach
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Producción ───────────────────────────────────────────────────────── --}}
@if(isset($datos['produccion']) && $datos['produccion']['total'] > 0)
<div class="section">
    <div class="section-header hdr-sky">
        <span class="title">🥛 Producción</span>
        <span class="count">{{ $datos['produccion']['total'] }} registros</span>
    </div>
    <table class="data">
        <thead>
            <tr>
                <th>Animal</th><th>Alias</th><th>Especie</th><th>Lote</th>
                <th>Fecha</th><th>Tipo</th><th>Valor</th><th>Unidad</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos['produccion']['registros'] as $p)
            <tr>
                <td class="mono">{{ $p->animal?->arete ?? '—' }}</td>
                <td class="gray">{{ $p->animal?->alias ?? '—' }}</td>
                <td>{{ $p->animal?->especie ?? '—' }}</td>
                <td class="gray">{{ $p->animal?->lote?->nombre ?? '—' }}</td>
                <td class="gray">{{ $p->fecha }}</td>
                <td>
                    @php $pc = match($p->tipo){ 'leche'=>'badge-sky','lana'=>'badge-amber','carne'=>'badge-red','canal'=>'badge-orange', default=>'badge-gray' }; @endphp
                    <span class="badge {{ $pc }}">{{ $p->tipo }}</span>
                </td>
                <td class="num">{{ $p->valor !== null ? number_format($p->valor, 2) : '—' }}</td>
                <td class="gray">{{ $p->unidad ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Ventas ───────────────────────────────────────────────────────────── --}}
@if(isset($datos['ventas']) && $datos['ventas']['total'] > 0)
<div class="section">
    <div class="section-header hdr-green">
        <span class="title">💰 Ventas</span>
        <span class="count">{{ $datos['ventas']['total'] }} registros &nbsp;·&nbsp; Ingresos: ${{ number_format($datos['ventas']['total_ingresos'] ?? 0, 2) }}</span>
    </div>
    <table class="data">
        <thead>
            <tr>
                <th>Fecha</th><th>Factura</th><th>Tipo</th><th>Producto</th>
                <th>Cant.</th><th>Unidad</th><th>P. Unit.</th><th>P. Total</th>
                <th>Método</th><th>Est. Venta</th><th>Est. Pago</th>
                <th>Comprador</th><th>Vendedor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos['ventas']['registros'] as $v)
            @php
                $ev = match($v->estado_venta){ 'completada'=>'badge-green','cancelada'=>'badge-gray','pendiente'=>'badge-amber', default=>'badge-gray' };
                $ep = match($v->estado_pago){  'completado'=>'badge-green','parcial'=>'badge-amber','pendiente'=>'badge-red',    default=>'badge-gray' };
            @endphp
            <tr>
                <td class="gray">{{ $v->fecha_venta }}</td>
                <td class="mono gray" style="font-size:7px;">{{ $v->numero_factura ?? '—' }}</td>
                <td><span class="badge badge-blue">{{ str_replace('_',' ',$v->tipo_venta) }}</span></td>
                <td style="max-width:80px;">{{ \Illuminate\Support\Str::limit($v->producto, 20) }}</td>
                <td class="num">{{ number_format($v->cantidad, 2) }}</td>
                <td class="gray">{{ $v->unidad }}</td>
                <td class="num">${{ number_format($v->precio_unitario, 2) }}</td>
                <td class="num pos">${{ number_format($v->precio_total, 2) }}</td>
                <td><span class="badge badge-gray">{{ str_replace('_',' ',$v->metodo_pago) }}</span></td>
                <td><span class="badge {{ $ev }}">{{ $v->estado_venta }}</span></td>
                <td><span class="badge {{ $ep }}">{{ $v->estado_pago }}</span></td>
                <td class="gray">{{ $v->comprador?->nombre ?? '—' }}</td>
                <td class="gray">{{ $v->vendedor?->name ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

</body>
</html>