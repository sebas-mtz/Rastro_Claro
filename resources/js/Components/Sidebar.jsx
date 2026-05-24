import { Link, usePage, router } from '@inertiajs/react';

export default function Sidebar() {
  const { url, props } = usePage();
  const user = props?.auth?.user;

  const isActive = (path) => url.startsWith(path);

  const roleLabels = {
    administrador: 'Administrador',
    encargado: 'Encargado',
    trabajador: 'Trabajador',
    solo_lectura: 'Solo lectura',
  };

  const roleLabel = roleLabels[user?.role] ?? 'Usuario';

  const isAdmin = user?.role === 'administrador';

  const puede = (modulo, accion) => {
    const permisos = user?.permisos ?? [];
    return permisos.includes(`${modulo}.${accion}`);
  
  };

  const isPremium = user?.plan === 'premium' || isAdmin;

  return (
    <aside className="sidebar-light">
      <div className="sl-brand">
        <div className="sl-logo">
          <img src="/assets/rastro Facil.png" alt="Rastro Facil" />
        </div>

        <div className="sl-title">
          <div className="name">Rastro Facil</div>
          <span className="ml-2 text-xs text-gray-400">
            {roleLabel}
          </span>
        </div>
      </div>

      <nav className="sl-nav">
        <ul>
          <li>
            <Link
              href="/dashboard"
              className={'sl-item ' + (isActive('/dashboard') ? 'active' : '')}
            >
              <span className="sl-ico">▦</span>
              <span>Home</span>
            </Link>
          </li>

          {puede('animales', 'ver') && (
            <li>
              <Link
                href={route('animales.index')}
                className={'sl-item ' + (isActive('/animales') ? 'active' : '')}
              >
                <span className="sl-ico">🐄</span>
                <span>Animales</span>
              </Link>
            </li>
          )}

          {puede('lotes', 'ver') && (
            <li>
              <Link
                href={route('lotes.index')}
                className={'sl-item ' + (isActive('/lotes') ? 'active' : '')}
              >
                <span className="sl-ico">📦</span>
                <span>Lotes</span>
              </Link>
            </li>
          )}

          {puede('pesajes', 'ver') && (
            <li>
              <Link
                href={route('pesajes.index')}
                className={'sl-item ' + (isActive('/pesajes') ? 'active' : '')}
              >
                <span className="sl-ico">⚖️</span>
                <span>Pesajes</span>
              </Link>
            </li>
          )}

          {puede('alimentacion', 'ver') && (
            <li>
              <Link
                href={route('alimentacion.index')}
                className={'sl-item ' + (isActive('/alimentacion') ? 'active' : '')}
              >
                <span className="sl-ico">🍽️</span>
                <span>Alimentación</span>
              </Link>
            </li>
          )}

          {puede('salud', 'ver') && (
            <li>
              <Link
                href="/eventos-salud"
                className={'sl-item ' + (isActive('/eventos-salud') ? 'active' : '')}
              >
                <span className="sl-ico">❤️</span>
                <span>Salud</span>
              </Link>
            </li>
          )}

          {puede('trabajadores', 'ver') && (
            <li>
              <Link
                href={route('trabajadores.index')}
                className={'sl-item ' + (isActive('/trabajadores') ? 'active' : '')}
              >
                <span className="sl-ico">👥</span>
                <span>Trabajadores</span>
              </Link>
            </li>
          )}

          {puede('producciones', 'ver') && (
            <li>
              <Link
                href={route('producciones.index')}
                className={'sl-item ' + (isActive('/producciones') ? 'active' : '')}
              >
                <span className="sl-ico">🥚</span>
                <span>Producciones</span>
              </Link>
            </li>
          )}

          {puede('reproduccion', 'ver') && (
            <li>
              <Link
                href={route('reproduccion.index')}
                className={'sl-item ' + (isActive('/reproduccion') ? 'active' : '')}
              >
                <span className="sl-ico">♻️</span>
                <span>Reproducción</span>
              </Link>
            </li>
          )}

          <li>
            <Link
              href={isPremium ? route('costos.index') : '#'}
              className={
                'sl-item ' +
                (!isPremium
                  ? ' text-gray-400 cursor-not-allowed'
                  : isActive('/costos')
                  ? ' active'
                  : '')
              }
              onClick={(e) => {
                if (!isPremium) {
                  e.preventDefault();
                  alert('Este módulo es parte del plan Premium.');
                }
              }}
            >
              <span className="sl-ico">$</span>
              <span>Costos</span>
            </Link>
          </li>

          {isAdmin && (
            <li>
              <Link
                href={route('admin.usuarios.index')}
                className={'sl-item ' + (isActive('/admin/usuarios') ? 'active' : '')}
              >
                <span className="sl-ico">⚙️</span>
                <span>Admin</span>
              </Link>
            </li>
          )}

          {!isPremium && (
            <li>
              <Link
                href={route('planes.index')}
                className={
                  'sl-item ' +
                  (isActive('/planes') ? 'active ' : '') +
                  'text-amber-600 font-semibold'
                }
              >
                <span className="sl-ico">💳</span>
                <span>Mejorar a Premium</span>
              </Link>
            </li>
          )}
        </ul>
      </nav>

      <div className="sl-footer">
        <div className="sl-user">
          <div className="u-name">{user?.name ?? 'Usuario'}</div>

          <div className="u-email" title={user?.email || ''}>
            {user?.email ?? 'correo@example.com'}
          </div>

          <div className="u-role">{roleLabel}</div>
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