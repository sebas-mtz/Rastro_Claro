import { Link, usePage, router } from '@inertiajs/react';

/**
 * Menú lateral.
 *
 * Cada entrada declara a qué módulo pertenece y solo se pinta si el backend
 * autorizó ese módulo para quien tiene la sesión abierta (props.auth.user.modulos).
 *
 * Ocultar el enlace es cosmético: la ruta la protege el middleware
 * VerificarPermisoModulo y responde 403 aunque se escriba la dirección a mano.
 * Aquí solo se evita mostrar puertas que no abrirían.
 *
 * `modulo: null` marca las entradas que no dependen de permisos —el panel— y
 * las que llevan su propia comprobación aparte.
 */
export default function Sidebar() {
  const { url, props } = usePage();
  const user = props?.auth?.user;

  const isActive = (path) => url.startsWith(path);

  const modulos = user?.modulos ?? [];
  const puedeVer = (modulo) => modulo === null || modulos.includes(modulo);

  const entradas = [
    { modulo: null,            href: '/dashboard',                 activo: '/dashboard',           icono: '▦',   texto: 'home' },
    { modulo: 'animales',      href: route('animales.index'),      activo: '/animales',            icono: '🐄',  texto: 'Animales' },
    { modulo: 'lotes',         href: route('lotes.index'),         activo: '/lotes',               icono: '📦',  texto: 'Lotes' },
    { modulo: 'pesajes',       href: route('pesajes.index'),       activo: '/pesajes',             icono: '⚖️',  texto: 'Pesajes' },
    { modulo: 'alimentacion',  href: route('alimentacion.index'),  activo: '/alimentacion',        icono: '🍽️',  texto: 'Alimentación' },
    { modulo: 'salud',         href: '/eventos-salud',             activo: '/eventos-salud',       icono: '❤️',  texto: 'Salud' },
    { modulo: 'producciones',  href: route('producciones.index'),  activo: '/producciones',        icono: '🥚',  texto: 'Producciones' },
    { modulo: 'salud',         href: route('calendario.index'),    activo: '/calendario-sanitario', icono: '🗓️', texto: 'Calendario' },
    { modulo: 'reportes',      href: route('reportes.ovinos'),     activo: '/reportes-ovinos',     icono: '📊',  texto: 'Indicadores' },
    { modulo: 'bajas',         href: route('bajas.index'),         activo: '/bajas',               icono: '📉',  texto: 'Bajas' },
    { modulo: 'costos',        href: route('costos.index'),        activo: '/costos',              icono: '💵',  texto: 'Costos' },
    { modulo: 'trabajadores',  href: route('trabajadores.index'),  activo: '/trabajadores',        icono: '👷',  texto: 'Trabajadores' },
    { modulo: 'reproduccion',  href: route('reproduccion.index'),  activo: '/reproduccion',        icono: '',    texto: 'Reproduccion' },
    { modulo: 'reproduccion',  href: route('genetica.index'),      activo: '/genetica',            icono: '',    texto: 'Genetica' },
    { modulo: 'tareas',        href: route('tareas.index'),        activo: '/Tareas',              icono: '',    texto: 'Recordatorios' },
    { modulo: 'reportes',      href: route('reportes.index'),      activo: '/Reportes',            icono: '',    texto: 'Reportes' },
  ];

  return (
    <aside className="sidebar-light">
      {/* Branding */}
      <div className="sl-brand">
        <div className="sl-logo">
          <img src="/assets/rastro Facil.png" alt="Rastro Facil" />
        </div>
        <div className="sl-title">
          <div className="name">Rastro Facil</div>
          {/* Rol del usuario autenticado. La etiqueta la calcula el backend
              (User::rolLegible), para no repetir aquí el mapa de roles. */}
          <span
            className={
              'ml-2 text-xs ' +
              (user?.es_super_admin ? 'text-amber-600 font-semibold' : 'text-gray-400')
            }
          >
            {user?.rol_legible || 'Trabajador'}
          </span>
        </div>
      </div>

      {/* Navegación */}
      <nav className="sl-nav">
        <ul>
          {entradas.filter((e) => puedeVer(e.modulo)).map((e, i) => (
            <li key={`${e.href}-${i}`}>
              <Link
                href={e.href}
                className={'sl-item ' + (isActive(e.activo) ? 'active' : '')}
              >
                <span className="sl-ico">{e.icono}</span>
                <span>{e.texto}</span>
              </Link>
            </li>
          ))}

          {/* Solo el superadministrador administra cuentas. La ruta exige el
              middleware super_admin y responde 403 aunque se escriba a mano. */}
          {user?.es_super_admin && (
            <li>
              <Link
                href={route('admin.usuarios.index')}
                className={'sl-item ' + (isActive('/admin/usuarios') ? 'active' : '')}
              >
                <span className="sl-ico">👤</span>
                <span>Usuarios</span>
              </Link>
            </li>
          )}

        </ul>
      </nav>

      {/* Footer con usuario y logout */}
      <div className="sl-footer">
        <div className="sl-user">
          <div className="u-name">{user?.name ?? 'Usuario'}</div>
          <div className="u-email" title={user?.email || ''}>
            {user?.email ?? 'correo@example.com'}
          </div>
          {/* Antes decía "Administrador" fijo, para cualquiera. */}
          <div className="u-role">{user?.rol_legible ?? 'Trabajador'}</div>
        </div>

        <form
          onSubmit={(e) => {
            e.preventDefault();
            router.post('/logout');
          }}
        >
          <button type="submit" className="sl-logout">
            Cerrar sesión
          </button>
        </form>
      </div>

    </aside>
  );
}
