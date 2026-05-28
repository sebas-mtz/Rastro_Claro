<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Ficha — {{ $animal->arete }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #1f2937; background: #fff; }

        /* ── Cabecera ────────────────────────────────── */
        .header {
            border-bottom: 3px solid #16a34a;
            padding-bottom: 10px;
            margin-bottom: 14px;
            display: table;
            width: 100%;
        }
        .header-left  { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }
        .header h1    { font-size: 17px; font-weight: 700; color: #15803d; }
        .header .sub  { font-size: 8px; color: #6b7280; margin-top: 2px; }
        .arete-badge  {
            display: inline-block;
            background: #f0fdf4; border: 1px solid #86efac;
            border-radius: 99px; padding: 3px 10px;
            font-size: 10px; font-weight: 700; color: #166534;
        }

        /* ── Secciones ───────────────────────────────── */
        .section { margin-bottom: 16px; page-break-inside: avoid; }
        .section-hdr {
            display: table; width: 100%;
            border-radius: 3px 3px 0 0;
            padding: 5px 10px;
            font-size: 9px; font-weight: 700; color: #fff;
        }
        .section-hdr .ttl { display: table-cell; }
        .section-hdr .cnt { display: table-cell; text-align: right; font-weight: 400; opacity: .8; }

        .hdr-green   { background: #15803d; }
        .hdr-blue    { background: #1d4ed8; }
        .hdr-teal    { background: #0f766e; }
        .hdr-amber   { background: #b45309; }
        .hdr-purple  { background: #7c3aed; }
        .hdr-orange  { background: #c2410c; }
        .hdr-sky     { background: #0284c7; }
        .hdr-rose    { background: #be123c; }
        .hdr-emerald { background: #047857; }

        /* ── Grid de datos generales ─────────────────── */
        .datos-grid { display: table; width: 100%; border-collapse: collapse; }
        .datos-col  { display: table-cell; width: 50%; vertical-align: top; padding: 0 6px; }
        .dato-row {
            display: table; width: 100%;
            border-bottom: 1px solid #f1f5f9;
            padding: 3px 0;
        }
        .dato-lbl { display: table-cell; width: 45%; color: #6b7280; font-size: 8px; }
        .dato-val { display: table-cell; width: 55%; font-weight: 600; color: #111827; font-size: 8px; text-align: right; }

        /* ── Stat cards de peso ──────────────────────── */
        .stat-row { display: table; width: 100%; border-collapse: separate; border-spacing: 4px; margin-bottom: 8px; }
        .stat-cell {
            display: table-cell; width: 33%;
            border-radius: 4px; border: 1px solid #e5e7eb;
            background: #f9fafb; padding: 5px 8px; text-align: center;
        }
        .stat-val { font-size: 13px; font-weight: 700; color: #1d4ed8; }
        .stat-lbl { font-size: 7px; color: #6b7280; margin-top: 1px; }
        .stat-sub { font-size: 7px; color: #9ca3af; }

        /* ── Tablas ──────────────────────────────────── */
        table.data { width: 100%; border-collapse: collapse; font-size: 8px; }
        table.data thead tr { background: #f1f5f9; }
        table.data thead th {
            padding: 4px 5px; text-align: left; font-weight: 700;
            color: #475569; border-bottom: 1px solid #cbd5e1;
            white-space: nowrap; font-size: 7px;
            text-transform: uppercase; letter-spacing: .03em;
        }
        table.data tbody tr { border-bottom: 1px solid #f1f5f9; }
        table.data tbody tr:nth-child(even) { background: #f8fafc; }
        table.data tbody td { padding: 4px 5px; vertical-align: top; color: #374151; }
        table.data tbody td.mono  { font-family: "Courier New", monospace; font-size: 7.5px; }
        table.data tbody td.num   { text-align: right; font-weight: 600; }
        table.data tbody td.gray  { color: #9ca3af; }
        table.data tbody td.trunc { max-width: 120px; overflow: hidden; }

        /* ── Badges ──────────────────────────────────── */
        .badge { display: inline-block; border-radius: 99px; padding: 1px 5px; font-size: 7px; font-weight: 700; line-height: 1.4; }
        .bg-green  { background:#d1fae5; color:#065f46; }
        .bg-amber  { background:#fef3c7; color:#92400e; }
        .bg-red    { background:#fee2e2; color:#991b1b; }
        .bg-blue   { background:#dbeafe; color:#1e40af; }
        .bg-gray   { background:#f1f5f9; color:#475569; }
        .bg-purple { background:#ede9fe; color:#6d28d9; }
        .bg-teal   { background:#ccfbf1; color:#0f766e; }
        .bg-sky    { background:#e0f2fe; color:#075985; }
        .bg-pink   { background:#fce7f3; color:#9d174d; }
        .bg-orange { background:#ffedd5; color:#9a3412; }

        /* ── Alertas ─────────────────────────────────── */
        .alert { background:#fffbeb; border:1px solid #fcd34d; border-radius:3px; padding:4px 8px; font-size:8px; color:#92400e; margin-bottom:6px; }

        /* ── Variación peso ──────────────────────────── */
        .pos { color: #15803d; font-weight: 700; }
        .neg { color: #b91c1c; font-weight: 700; }

        /* ── Sub-título ──────────────────────────────── */
        .sub-title { font-size: 7.5px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; margin: 8px 0 4px; }

        /* ── Pie de página ───────────────────────────── */
        .footer { position: fixed; bottom: 0; left: 0; right: 0; border-top: 1px solid #e5e7eb; padding: 3px 0; font-size: 7px; color: #9ca3af; text-align: center; }

        /* ── Page break ──────────────────────────────── */
        .pb { page-break-before: always; }
    </style>
</head>
<body>

<div class="footer">
    Ficha generada el {{ now()->format('d/m/Y H:i') }} &nbsp;·&nbsp; Sistema de Gestión Pecuaria
</div>

{{-- ══ CABECERA ═══════════════════════════════════════════════════════════════ --}}
<div class="header">
    <div class="header-left">
        <h1>🐄 Ficha de Animal</h1>
        <div class="sub">Historia clínica completa &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}</div>
    </div>
    <div class="header-right">
        <div class="arete-badge">{{ $animal->arete }}</div>
        @if($animal->alias)
        <div style="font-size:9px; color:#374151; margin-top:3px; font-weight:600;">{{ $animal->alias }}</div>
        @endif
        <div style="font-size:8px; color:#6b7280; margin-top:1px;">{{ $animal->especie }} · {{ $animal->sexo === 'M' ? 'Macho' : 'Hembra' }}</div>
    </div>
</div>

{{-- ══ DATOS GENERALES ════════════════════════════════════════════════════════ --}}
<div class="section">
    <div class="section-hdr hdr-green">
        <span class="ttl">Datos Generales</span>
    </div>
    <div style="padding:6px 0;">
        <div class="datos-grid">
            <div class="datos-col">
                <p style="font-size:7px;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:4px;">Identificación</p>
                @php
                    function calcEdad($f) {
                        if (!$f) return 'N/D';
                        $nac = new \DateTime($f);
                        $hoy = new \DateTime();
                        $dif = $hoy->diff($nac);
                        return $dif->y . ' año' . ($dif->y !== 1 ? 's' : '');
                    }
                @endphp
                <div class="dato-row"><span class="dato-lbl">Arete</span><span class="dato-val">{{ $animal->arete }}</span></div>
                <div class="dato-row"><span class="dato-lbl">Alias</span><span class="dato-val">{{ $animal->alias ?? '—' }}</span></div>
                <div class="dato-row"><span class="dato-lbl">Especie</span><span class="dato-val">{{ $animal->especie }}</span></div>
                <div class="dato-row"><span class="dato-lbl">Raza</span><span class="dato-val">{{ $animal->raza ?? '—' }}</span></div>
                <div class="dato-row"><span class="dato-lbl">Sexo</span><span class="dato-val">{{ $animal->sexo === 'M' ? 'Macho' : 'Hembra' }}</span></div>
                <div class="dato-row"><span class="dato-lbl">Fecha Nacimiento</span><span class="dato-val">{{ $animal->fecha_nac ? \Carbon\Carbon::parse($animal->fecha_nac)->format('d/m/Y') : 'N/D' }}</span></div>
                <div class="dato-row"><span class="dato-lbl">Edad</span><span class="dato-val">{{ calcEdad($animal->fecha_nac) }}</span></div>
                <div class="dato-row"><span class="dato-lbl">Fecha de Registro</span><span class="dato-val">{{ \Carbon\Carbon::parse($animal->created_at)->format('d/m/Y') }}</span></div>
            </div>
            <div class="datos-col">
                <p style="font-size:7px;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:4px;">Estado actual</p>
                <div class="dato-row"><span class="dato-lbl">Lote / Potrero</span><span class="dato-val">{{ $animal->lote?->nombre ?? 'Sin lote' }}</span></div>
                <div class="dato-row"><span class="dato-lbl">Estado productivo</span><span class="dato-val">{{ $animal->estado_productivo ?? '—' }}</span></div>
                @php
                    $pesajesOrden = $animal->pesajes->sortBy('fecha');
                    $pesoActual   = $pesajesOrden->last()?->peso;
                    $pesoInicial  = $pesajesOrden->first()?->peso;
                    $ganancia     = ($pesoInicial && $pesoActual) ? round($pesoActual - $pesoInicial, 2) : null;
                @endphp
                <div class="dato-row"><span class="dato-lbl">Peso actual</span><span class="dato-val">{{ $pesoActual ? number_format($pesoActual,2).' kg' : ($animal->peso ? number_format($animal->peso,2).' kg' : '—') }}</span></div>
                <div class="dato-row"><span class="dato-lbl">BCS</span><span class="dato-val">{{ $animal->BCS ?? '—' }}</span></div>
                @if($animal->madre)
                <div class="dato-row"><span class="dato-lbl">Madre</span><span class="dato-val">{{ $animal->madre->arete }}{{ $animal->madre->alias ? ' — '.$animal->madre->alias : '' }}</span></div>
                @endif
                @if($animal->padre)
                <div class="dato-row"><span class="dato-lbl">Padre</span><span class="dato-val">{{ $animal->padre->arete }}{{ $animal->padre->alias ? ' — '.$animal->padre->alias : '' }}</span></div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ══ HISTORIAL DE PESO ══════════════════════════════════════════════════════ --}}
@if($animal->pesajes->count() > 0)
<div class="section">
    <div class="section-hdr hdr-blue">
        <span class="ttl">⚖ Historial de Peso</span>
        <span class="cnt">{{ $animal->pesajes->count() }} pesaje(s)</span>
    </div>
    <div style="padding: 6px 0;">
        <div class="stat-row">
            <div class="stat-cell">
                <div class="stat-val">{{ $pesoInicial ? number_format($pesoInicial,2).' kg' : '—' }}</div>
                <div class="stat-lbl">Peso inicial</div>
                <div class="stat-sub">{{ $pesajesOrden->first()?->fecha }}</div>
            </div>
            <div class="stat-cell" style="border-color:#bfdbfe; background:#eff6ff;">
                <div class="stat-val">{{ $pesoActual ? number_format($pesoActual,2).' kg' : '—' }}</div>
                <div class="stat-lbl" style="color:#1d4ed8;">Peso actual</div>
                <div class="stat-sub">{{ $pesajesOrden->last()?->fecha }}</div>
                </div>
<div class="stat-cell @css($ganancia > 0 ? 'border-[#6ee7b7] bg-[#ecfdf5]' : ($ganancia < 0 ? 'border-[#fca5a5] bg-[#fef2f2]' : ''))">
    <div class="stat-val {{ $ganancia > 0 ? 'text-[#15803d]' : ($ganancia < 0 ? 'text-[#b91c1c]' : '') }}">
        {{ $ganancia !== null ? ($ganancia > 0 ? '+' : '').$ganancia.' kg' : '—' }}
    </div>
    <div class="stat-lbl">Ganancia total</div>
    <div class="stat-sub">{{ $animal->pesajes->count() }} pesajes</div>
</div>
        </div>

        <table class="data">
            <thead>
                <tr><th>Fecha</th><th>Peso (kg)</th><th>Variación</th><th>Notas</th></tr>
            </thead>
            <tbody>
                @foreach($pesajesOrden->reverse() as $i => $p)
                @php
                    $arr = $pesajesOrden->values();
                    $idx = $arr->search(fn($x) => $x->id === $p->id);
                    $ant = $idx > 0 ? $arr[$idx - 1] : null;
                    $delta = $ant ? round($p->peso - $ant->peso, 2) : null;
                @endphp
                <tr>
                    <td class="gray">{{ $p->fecha }}</td>
                    <td class="num">{{ number_format($p->peso, 2) }} kg</td>
                    <td>
                        @if($delta !== null)
                            <span class="{{ $delta >= 0 ? 'pos' : 'neg' }}">
                                {{ $delta >= 0 ? '+' : '' }}{{ $delta }} kg
                            </span>
                        @else —
                        @endif
                    </td>
                    <td class="gray trunc">{{ $p->notas ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ══ EVENTOS DE SALUD ════════════════════════════════════════════════════════ --}}
@if($animal->eventos_salud->count() > 0)
<div class="section">
    @php
        $pendientes = $animal->eventos_salud->where('estado','pendiente')->count();
        $vencidos   = $animal->eventos_salud->where('estado','vencida')->count();
    @endphp
    <div class="section-hdr hdr-teal">
        <span class="ttl">💊 Eventos de Salud</span>
        <span class="cnt">{{ $animal->eventos_salud->count() }} evento(s)</span>
    </div>
    @if($pendientes > 0 || $vencidos > 0)
        <div class="alert" style="margin-top:4px;">
            ⚠
            @if($pendientes) {{ $pendientes }} pendiente(s) @endif
            @if($pendientes && $vencidos) · @endif
            @if($vencidos) {{ $vencidos }} vencido(s) @endif
        </div>
    @endif
    <table class="data" style="margin-top:4px;">
        <thead>
            <tr><th>Tipo</th><th>F. Programada</th><th>F. Aplicación</th><th>Estado</th><th>Diagnóstico</th><th>Responsable</th></tr>
        </thead>
        <tbody>
            @foreach($animal->eventos_salud as $ev)
            @php
                $badgeE = match($ev->estado){ 'aplicada'=>'bg-green','pendiente'=>'bg-amber','vencida'=>'bg-red', default=>'bg-gray' };
                $badgeT = match($ev->tipo){ 'vacunacion'=>'bg-teal','consulta'=>'bg-blue','revision'=>'bg-sky','emergencia'=>'bg-red', default=>'bg-gray' };
            @endphp
            <tr>
                <td><span class="badge {{ $badgeT }}">{{ $ev->tipo }}</span></td>
                <td class="gray">{{ $ev->fecha_programada }}</td>
                <td class="gray">{{ $ev->fecha_aplicacion ?? '—' }}</td>
                <td><span class="badge {{ $badgeE }}">{{ $ev->estado }}</span></td>
                <td class="trunc">{{ \Illuminate\Support\Str::limit($ev->diagnostico ?? '—', 35) }}</td>
                <td class="gray">{{ $ev->responsable ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ══ VACUNACIONES ═══════════════════════════════════════════════════════════ --}}
@php $vacunaciones = $animal->eventos_salud->where('tipo','vacunacion'); @endphp
@if($vacunaciones->count() > 0)
<div class="section">
    <div class="section-hdr hdr-amber">
        <span class="ttl">💉 Vacunaciones</span>
        <span class="cnt">{{ $vacunaciones->count() }} registro(s)</span>
    </div>
    <table class="data" style="margin-top:4px;">
        <thead>
            <tr><th>Vacuna</th><th>F. Programada</th><th>F. Aplicación</th><th>Dosis</th><th>Lote Vacuna</th><th>Estado</th></tr>
        </thead>
        <tbody>
            @foreach($vacunaciones as $ev)
            @php $badgeE = match($ev->estado){ 'aplicada'=>'bg-green','pendiente'=>'bg-amber','vencida'=>'bg-red', default=>'bg-gray' }; @endphp
            <tr>
                <td>{{ $ev->vacuna?->nombre ?? '—' }}</td>
                <td class="gray">{{ $ev->fecha_programada }}</td>
                <td class="gray">{{ $ev->fecha_aplicacion ?? '—' }}</td>
                <td>{{ $ev->dosis ?? '—' }}</td>
                <td class="gray">{{ $ev->lote_vacuna ?? '—' }}</td>
                <td><span class="badge {{ $badgeE }}">{{ $ev->estado }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ══ TRATAMIENTOS ═══════════════════════════════════════════════════════════ --}}
@if($animal->tratamientos->count() > 0)
<div class="section">
    <div class="section-hdr hdr-purple">
        <span class="ttl">🩺 Tratamientos</span>
        <span class="cnt">{{ $animal->tratamientos->count() }} tratamiento(s)</span>
    </div>
    <table class="data" style="margin-top:4px;">
        <thead>
            <tr><th>Tratamiento</th><th>F. Inicio</th><th>F. Fin prevista</th><th>Estado</th><th>Responsable</th><th>Notas</th></tr>
        </thead>
        <tbody>
            @foreach($animal->tratamientos as $tr)
            <tr>
                <td>{{ $tr->nombre }}</td>
                <td class="gray">{{ $tr->fecha_inicio }}</td>
                <td class="gray">{{ $tr->fecha_fin ?? '—' }}</td>
                <td><span class="badge {{ $tr->estado === 'activo' ? 'bg-blue' : 'bg-gray' }}">{{ $tr->estado }}</span></td>
                <td class="gray">{{ $tr->responsable ?? '—' }}</td>
                <td class="gray trunc">{{ \Illuminate\Support\Str::limit($tr->notas ?? '—', 30) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ══ ALIMENTACIÓN ═══════════════════════════════════════════════════════════ --}}
@if($animal->alimentaciones->count() > 0)
<div class="section">
    <div class="section-hdr hdr-orange">
        <span class="ttl">🌾 Alimentación</span>
        <span class="cnt">{{ $animal->alimentaciones->count() }} registro(s)</span>
    </div>
    <table class="data" style="margin-top:4px;">
        <thead>
            <tr><th>Fecha</th><th>Hora</th><th>Ración</th><th>Cantidad</th><th>Unidad</th><th>MS%</th><th>PB%</th><th>Notas</th></tr>
        </thead>
        <tbody>
            @foreach($animal->alimentaciones as $a)
            <tr>
                <td class="gray">{{ $a->fecha }}</td>
                <td class="gray">{{ $a->hora ?? '—' }}</td>
                <td>{{ $a->racion?->nombre ?? '—' }}</td>
                <td class="num">{{ $a->cantidad }}</td>
                <td class="gray">{{ $a->unidad }}</td>
                <td class="gray">{{ $a->racion?->MS ?? '—' }}</td>
                <td class="gray">{{ $a->racion?->PB ?? '—' }}</td>
                <td class="gray trunc">{{ \Illuminate\Support\Str::limit($a->notas ?? '—', 25) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ══ PRODUCCIÓN ══════════════════════════════════════════════════════════════ --}}
@if($animal->producciones->count() > 0)
<div class="section">
    <div class="section-hdr hdr-sky">
        <span class="ttl">🥛 Producción</span>
        <span class="cnt">{{ $animal->producciones->count() }} registro(s)</span>
    </div>
    <table class="data" style="margin-top:4px;">
        <thead>
            <tr><th>Fecha</th><th>Tipo</th><th>Valor</th><th>Unidad</th></tr>
        </thead>
        <tbody>
            @foreach($animal->producciones as $p)
            @php $pc = match($p->tipo){ 'leche'=>'bg-sky','lana'=>'bg-amber','carne'=>'bg-red','canal'=>'bg-orange', default=>'bg-gray' }; @endphp
            <tr>
                <td class="gray">{{ $p->fecha }}</td>
                <td><span class="badge {{ $pc }}">{{ $p->tipo }}</span></td>
                <td class="num">{{ $p->valor !== null ? number_format($p->valor, 2) : '—' }}</td>
                <td class="gray">{{ $p->unidad ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ══ HISTORIAL REPRODUCTIVO (solo hembras) ══════════════════════════════════ --}}
@if($animal->sexo === 'F' && $animal->eventos_reproductivos->count() > 0)
@php
    $servicios    = $animal->eventos_reproductivos->where('tipo_evento','servicio');
    $diagnosticos = $animal->eventos_reproductivos->where('tipo_evento','diagnostico');
    $partos       = $animal->eventos_reproductivos->where('tipo_evento','parto');
    $positivos    = $diagnosticos->filter(fn($e) => $e->diagnostico?->resultado === 'positivo')->count();
    $tasa         = $servicios->count() > 0 ? round(($positivos / $servicios->count()) * 100, 1) : null;
@endphp
<div class="section pb">
    <div class="section-hdr hdr-rose">
        <span class="ttl">🐣 Historial Reproductivo</span>
        <span class="cnt">
            {{ $servicios->count() }} serv. · {{ $diagnosticos->count() }} dx · {{ $partos->count() }} partos
            @if($tasa !== null) · Concepción: {{ $tasa }}% @endif
        </span>
    </div>
    <div style="padding:4px 0;">

        {{-- Todos los eventos --}}
        @if($animal->eventos_reproductivos->count() > 0)
        <p class="sub-title">Todos los eventos</p>
        <table class="data">
            <thead><tr><th>Tipo</th><th>Fecha</th><th>Costo</th><th>Observaciones</th></tr></thead>
            <tbody>
                @foreach($animal->eventos_reproductivos as $ev)
                @php $bc = match($ev->tipo_evento){ 'servicio'=>'bg-purple','diagnostico'=>'bg-sky','parto'=>'bg-emerald','celo'=>'bg-pink','aborto'=>'bg-red','destete'=>'bg-orange', default=>'bg-gray' }; @endphp
                <tr>
                    <td><span class="badge {{ $bc }}">{{ $ev->tipo_evento }}</span></td>
                    <td class="gray">{{ $ev->fecha }}</td>
                    <td class="num">{{ $ev->costo ? '$'.number_format($ev->costo,2) : '—' }}</td>
                    <td class="gray trunc">{{ \Illuminate\Support\Str::limit($ev->observaciones ?? '—', 40) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Servicios --}}
        @if($servicios->count() > 0)
        <p class="sub-title" style="margin-top:8px;">Servicios</p>
        <table class="data">
            <thead><tr><th>Fecha</th><th>Tipo</th><th># Serv.</th><th>Macho / Pajilla</th><th>Técnico</th></tr></thead>
            <tbody>
                @foreach($servicios as $ev)
                @php $srv = $ev->servicio; @endphp
                <tr>
                    <td class="gray">{{ $ev->fecha }}</td>
                    <td><span class="badge bg-purple">{{ str_replace('_',' ',$srv?->tipo_servicio ?? '—') }}</span></td>
                    <td>{{ $srv?->numero_servicio ?? '—' }}</td>
                    <td class="mono gray">{{ $srv?->macho?->arete ?? ($srv?->pajilla_codigo ? '🧬 '.$srv->pajilla_codigo : '—') }}</td>
                    <td class="gray">{{ $srv?->tecnico?->name ?? $srv?->tecnico_externo ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Diagnósticos --}}
        @if($diagnosticos->count() > 0)
        <p class="sub-title" style="margin-top:8px;">Diagnósticos de gestación</p>
        <table class="data">
            <thead><tr><th>Fecha</th><th>Método</th><th>Resultado</th><th>Días Gest.</th><th>F. Prob. Parto</th><th>Veterinario</th></tr></thead>
            <tbody>
                @foreach($diagnosticos as $ev)
                @php $dx = $ev->diagnostico; $br = match($dx?->resultado){ 'positivo'=>'bg-green','negativo'=>'bg-red','repetir'=>'bg-amber', default=>'bg-gray' }; @endphp
                <tr>
                    <td class="gray">{{ $ev->fecha }}</td>
                    <td class="gray">{{ str_replace('_',' ',$dx?->metodo ?? '—') }}</td>
                    <td>@if($dx?->resultado)<span class="badge {{ $br }}">{{ $dx->resultado }}</span>@else —@endif</td>
                    <td>{{ $dx?->dias_gestacion_estimados ?? '—' }}</td>
                    <td class="gray">{{ $dx?->fecha_probable_parto ?? '—' }}</td>
                    <td class="gray">{{ $dx?->veterinario?->name ?? $dx?->veterinario_externo ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Partos --}}
        @if($partos->count() > 0)
        <p class="sub-title" style="margin-top:8px;">Partos</p>
        <table class="data">
            <thead><tr><th>Fecha</th><th>Tipo</th><th># Crías</th><th>Asistencia</th><th>Complicaciones</th><th>Crías (aretes)</th></tr></thead>
            <tbody>
                @foreach($partos as $ev)
                @php $p = $ev->parto; @endphp
                <tr>
                    <td class="gray">{{ $ev->fecha }}</td>
                    <td><span class="badge bg-teal">{{ $p?->tipo_parto ?? '—' }}</span></td>
                    <td class="num">{{ $p?->numero_crias ?? '—' }}</td>
                    <td><span class="badge {{ $p?->asistencia_requerida ? 'bg-amber' : 'bg-green' }}">{{ $p?->asistencia_requerida ? 'Sí' : 'No' }}</span></td>
                    <td><span class="badge {{ $p?->complicaciones ? 'bg-red' : 'bg-green' }}">{{ $p?->complicaciones ? 'Sí' : 'No' }}</span></td>
                    <td class="mono gray" style="font-size:7px;">
                        @foreach($p?->crias ?? [] as $c)
                            {{ $c->animal?->arete ?? $c->arete_temporal ?? 's/a' }}({{ strtoupper(substr($c->sexo ?? '?',0,1)) }})
                        @endforeach
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endif

</body>
</html>