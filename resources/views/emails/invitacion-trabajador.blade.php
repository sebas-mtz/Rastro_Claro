<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Invitación — {{ config('app.name') }}</title>
    <style>
        body { margin:0; padding:0; background:#f4f6f8; font-family: 'Segoe UI', Arial, sans-serif; }
        .wrapper { max-width:560px; margin:40px auto; background:#fff; border-radius:16px;
                   box-shadow:0 4px 24px rgba(0,0,0,.08); overflow:hidden; }
        .header  { background:linear-gradient(135deg,#059669,#10b981); padding:36px 32px; text-align:center; }
        .header h1 { color:#fff; margin:0; font-size:22px; font-weight:700; }
        .header p  { color:#d1fae5; margin:6px 0 0; font-size:14px; }
        .body    { padding:32px; color:#374151; }
        .body p  { line-height:1.7; margin:0 0 16px; font-size:15px; }
        .card    { background:#f9fafb; border:1px solid #e5e7eb; border-radius:12px;
                   padding:20px 24px; margin:24px 0; }
        .card .label { font-size:12px; color:#9ca3af; font-weight:600;
                       text-transform:uppercase; letter-spacing:.05em; margin-bottom:4px; }
        .card .value { font-size:17px; font-weight:700; color:#111827; word-break:break-all; }
        .badge   { display:inline-block; padding:4px 12px; border-radius:999px; font-size:13px;
                   font-weight:600; background:#dcfce7; color:#15803d; margin-left:8px; }
        .btn     { display:block; text-align:center; margin:28px 0 8px;
                   background:#059669; color:#fff; text-decoration:none;
                   padding:14px 28px; border-radius:10px; font-size:15px; font-weight:700; }
        .btn:hover { background:#047857; }
        .warn    { background:#fffbeb; border:1px solid #fde68a; border-radius:10px;
                   padding:14px 16px; font-size:13px; color:#92400e; margin-top:20px; }
        .footer  { background:#f9fafb; border-top:1px solid #e5e7eb; padding:20px 32px;
                   text-align:center; font-size:12px; color:#9ca3af; }
    </style>
</head>
<body>
<div class="wrapper">

    {{-- Header --}}
    <div class="header">
        <h1>🐄 {{ config('app.name') }}</h1>
        <p>Sistema de gestión ganadera</p>
    </div>

    {{-- Body --}}
    <div class="body">
        <p>Hola <strong>{{ $trabajador->name }}</strong>,</p>

        <p>
            <strong>{{ $invitadoPor->name }}</strong> te ha invitado a unirte a
            <strong>{{ config('app.name') }}</strong> con el rol de
            <span class="badge">{{ $trabajador->roleLabel() }}</span>
        </p>

        <p>Aquí están tus credenciales de acceso:</p>

        <div class="card">
            <div class="label">Correo electrónico</div>
            <div class="value">{{ $trabajador->email }}</div>
        </div>

        <div class="card">
            <div class="label">Contraseña temporal</div>
            <div class="value">{{ $passwordTemporal }}</div>
        </div>

        <a href="{{ config('app.url') }}/login" class="btn">
            Entrar al sistema →
        </a>

        <div class="warn">
            ⚠️ <strong>Importante:</strong> Esta es una contraseña temporal.
            Al ingresar por primera vez, el sistema te pedirá que la cambies
            por una nueva contraseña de tu elección.
        </div>

        <p style="margin-top:24px; font-size:14px; color:#6b7280;">
            Si tienes dudas, contacta a {{ $invitadoPor->name }}
            @if($invitadoPor->email)
                en <a href="mailto:{{ $invitadoPor->email }}" style="color:#059669;">{{ $invitadoPor->email }}</a>.
            @endif
        </p>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>© {{ date('Y') }} {{ config('app.name') }} · Este correo fue enviado automáticamente, no respondas a este mensaje.</p>
    </div>

</div>
</body>
</html>
