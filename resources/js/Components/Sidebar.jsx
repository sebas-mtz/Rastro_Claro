import { Link, router, usePage } from '@inertiajs/react';
import {
  BellRing,
  Boxes,
  ChartNoAxesCombined,
  ChevronLeft,
  ChevronRight,
  ClipboardList,
  Crown,
  Dna,
  Gauge,
  HeartPulse,
  LayoutDashboard,
  LogOut,
  Milk,
  PanelLeftClose,
  PanelLeftOpen,
  Scale,
  Settings,
  UserRoundCog,
  UsersRound,
  Utensils,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import SettingsModal from '@/Components/SettingsModal';

export default function Sidebar() {
  const { url, props } = usePage();
  const user = props?.auth?.user;
  const [collapsed, setCollapsed] = useState(() => window.localStorage.getItem('rastro-sidebar-collapsed') === 'true');
  const [settingsOpen, setSettingsOpen] = useState(false);
  const [settingsTab, setSettingsTab] = useState('account');

  useEffect(() => {
    window.localStorage.setItem('rastro-sidebar-collapsed', String(collapsed));
  }, [collapsed]);

  const currentPath = url.split('?')[0].toLowerCase();
  const isActive = (paths) => paths.some((path) => currentPath.startsWith(path.toLowerCase()));

  const items = useMemo(() => [
    { label: 'Inicio', href: route('dashboard'), paths: ['/dashboard'], icon: LayoutDashboard },
    { label: 'Animales', href: route('animales.index'), paths: ['/animales'], icon: UsersRound },
    { label: 'Lotes', href: route('lotes.index'), paths: ['/lotes'], icon: Boxes },
    { label: 'Pesajes', href: route('pesajes.index'), paths: ['/pesajes'], icon: Scale },
    { label: 'Alimentación', href: route('alimentacion.index'), paths: ['/alimentacion'], icon: Utensils },
    { label: 'Salud', href: route('salud.index'), paths: ['/salud', '/eventos-salud'], icon: HeartPulse },
    { label: 'Producciones', href: route('producciones.index'), paths: ['/producciones'], icon: Milk },
    { label: 'Reproducción', href: route('reproduccion.index'), paths: ['/reproduccion'], icon: Dna },
    { label: 'Genética', href: route('genetica.index'), paths: ['/genetica'], icon: ChartNoAxesCombined },
    { label: 'Recordatorios', href: route('tareas.index'), paths: ['/tareas'], icon: BellRing },
    { label: 'Reportes', href: route('reportes.index'), paths: ['/reportes'], icon: ClipboardList },
  ], []);

  const openSettings = (tab = 'account') => {
    setSettingsTab(tab);
    setSettingsOpen(true);
  };

  const renderItem = ({ label, href, paths, icon: Icon, locked = false }) => {
    const active = isActive(paths);
    const className = `sl-item${active ? ' active' : ''}${locked ? ' locked' : ''}`;
    const content = (
      <>
        <Icon className="sl-ico" size={19} strokeWidth={2} />
        <span className="sl-label">{label}</span>
        {locked && <Crown className="sl-trailing" size={15} />}
      </>
    );

    if (locked) {
      return (
        <button key={label} type="button" className={className} title={collapsed ? label : undefined} onClick={() => openSettings('plan')}>
          {content}
        </button>
      );
    }

    return (
      <Link key={label} href={href} className={className} title={collapsed ? label : undefined}>
        {content}
      </Link>
    );
  };

  return (
    <>
      <aside className={`sidebar-light${collapsed ? ' is-collapsed' : ''}`}>
        <div className="sl-brand">
          <div className="sl-logo">
            <img src="/assets/rastro Facil.png" alt="Rastro Fácil" />
          </div>
          <div className="sl-title">
            <div className="name">Rastro Fácil</div>
            <span>{user?.role === 'admin' ? 'Administrador' : 'Usuario'}</span>
          </div>
          <button
            type="button"
            className="sl-collapse"
            onClick={() => setCollapsed((value) => !value)}
            aria-label={collapsed ? 'Desplegar menú lateral' : 'Replegar menú lateral'}
            title={collapsed ? 'Desplegar menú' : 'Replegar menú'}
          >
            {collapsed ? <PanelLeftOpen size={18} /> : <PanelLeftClose size={18} />}
          </button>
        </div>

        <nav className="sl-nav" aria-label="Navegación principal">
          <div className="sl-section-label">Menú</div>
          <ul>
            {items.map(renderItem)}
            {renderItem({
              label: 'Predicciones',
              href: user?.plan === 'premium' ? route('predicciones.index') : '#',
              paths: ['/predicciones'],
              icon: Gauge,
              locked: user?.plan !== 'premium',
            })}
            {user?.role === 'admin' && renderItem({
              label: 'Usuarios',
              href: route('admin.usuarios.index'),
              paths: ['/admin/usuarios'],
              icon: UserRoundCog,
            })}
          </ul>
        </nav>

        <div className="sl-footer">
          <button type="button" className="sl-settings" onClick={() => openSettings('account')} title={collapsed ? 'Configuración' : undefined}>
            <Settings className="sl-ico" size={19} />
            <span className="sl-label">Configuración</span>
            <ChevronRight className="sl-trailing" size={16} />
          </button>

          <div className="sl-account">
            <div className="sl-avatar">{(user?.name ?? 'U').charAt(0).toUpperCase()}</div>
            <div className="sl-user">
              <div className="u-name">{user?.name ?? 'Usuario'}</div>
              <div className="u-email" title={user?.email || ''}>{user?.email ?? ''}</div>
            </div>
            <button
              type="button"
              className="sl-logout"
              onClick={() => router.post('/logout')}
              title="Cerrar sesión"
              aria-label="Cerrar sesión"
            >
              <LogOut size={18} />
            </button>
          </div>
        </div>

        <button
          type="button"
          className="sl-edge-toggle"
          onClick={() => setCollapsed((value) => !value)}
          aria-label={collapsed ? 'Desplegar menú lateral' : 'Replegar menú lateral'}
          title={collapsed ? 'Desplegar menú' : 'Replegar menú'}
        >
          {collapsed ? <ChevronRight size={14} /> : <ChevronLeft size={14} />}
        </button>
      </aside>

      <SettingsModal
        isOpen={settingsOpen}
        onClose={() => setSettingsOpen(false)}
        user={user}
        initialTab={settingsTab}
      />
    </>
  );
}
