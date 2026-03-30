import { Link, usePage, router } from '@inertiajs/react';

export default function Sidebar() {
  const { url, props } = usePage();
  const user = props?.auth?.user; // usuario logueado

  // helper para marcar activo
  const isActive = (path) => url.startsWith(path);

  return (
    <aside className="sidebar-light">
      {/* Branding */}
      <div className="sl-brand">
        <div className="sl-logo">
          <img src="/assets/rastro Facil.png" alt="Rastro Facil" />
        </div>
        <div className="sl-title">
          <div className="name">Rastro Facil</div>
          {/* 👇 rol del usuario */}
          <span className="ml-2 text-xs text-gray-400">
            {user?.role === 'admin' ? 'Administrador' : 'Usuario'}
          </span>
        </div>
      </div>

      {/* Navegación */}
      <nav className="sl-nav">
        <ul>
          <li>
            <Link
              href="/dashboard"
              className={'sl-item ' + (isActive('/dashboard') ? 'active' : '')}
            >
              <span className="sl-ico">▦</span>
              <span>home</span>
            </Link>
          </li>

          <li>
            <Link
              href={route('animales.index')}
              className={'sl-item ' + (isActive('/animales') ? 'active' : '')}
            >
              <span className="sl-ico">🐄</span>
              <span>Animales</span>
            </Link>
          </li>

          <li>
            <Link
              href={route('lotes.index')}
              className={'sl-item ' + (isActive('/lotes') ? 'active' : '')}
            >
              <span className="sl-ico">📦</span>
              <span>Lotes</span>
            </Link>
          </li>

          <li>
            <Link
              href="/health"
              className={'sl-item ' + (isActive('/health') ? 'active' : '')}
            >
              <span className="sl-ico">❤️</span>
              <span>Salud</span>
            </Link>
          </li>

          <li>
            <Link
              href={route('alimentacion.index')}
              className={
                'sl-item ' + (isActive('/alimentacion') ? 'active' : '')
              }
            >
              <span className="sl-ico">🍽️</span>
              <span>Alimentación</span>
            </Link>
          </li>

          <li>
            <Link
              href={route('producciones.index')}
              className={
                'sl-item ' + (isActive('/producciones') ? 'active' : '')
              }
            >
              <span className="sl-ico">🥚</span>
              <span>Producciones</span>
            </Link>
          </li>
          <li>
            <Link
              href={route('reproduccion.index')}
              className={
                'sl-item ' + (isActive('/reproduccion') ? 'active' : '')
              }
            >
              <span className="sl-ico"></span>
              <span>Reproduccion</span>
            </Link>
          </li>

          {/* 🔐 Módulo PREMIUM: Predicciones */}
          <li>
            <Link
              href={user?.plan === 'premium' ? route('predicciones.index') : '#'}
              className={
                'sl-item ' +
                (user?.plan !== 'premium'
                  ? ' text-gray-400 cursor-not-allowed'
                  : isActive('/predicciones')
                  ? ' active'
                  : '')
              }
              onClick={(e) => {
                if (user?.plan !== 'premium') {
                  e.preventDefault();
                  alert('Este módulo es parte del plan Premium.');
                }
              }}
            >
              <span className="sl-ico mr-2">👑</span>
              <span>Predicciones</span>
            </Link>
          </li>
          {user?.role === 'admin' && (
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
      {user?.plan !== 'premium' && (
            <li>
              <Link
                href={route('planes.index')}
                className={
                  'sl-item ' +
                  (isActive('/planes') ? 'active' : '') +
                  ' text-amber-600 font-semibold'
                }
              >
                <span className="sl-ico">💳</span>
                <span>Mejorar a Premium</span>
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
          <div className="u-role">Administrador</div>
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
