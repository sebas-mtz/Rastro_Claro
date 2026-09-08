import { Link, router, usePage } from '@inertiajs/react';
import {
  BarChart3,
  BellRing,
  Boxes,
  CalendarDays,
  ChartNoAxesCombined,
  ChevronLeft,
  ChevronRight,
  ClipboardList,
  Crown,
  Dna,
  Gauge,
  HardHat,
  HeartPulse,
  LayoutDashboard,
  LogOut,
  Milk,
  PanelLeftClose,
  PanelLeftOpen,
  Scale,
  Settings,
  TrendingDown,
  UserRoundCog,
  UsersRound,
  Utensils,
  Wallet,
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

  // Módulos habilitados para quien tiene la sesión abierta. Vienen del backend
  // (users -> puesto -> permisos, con excepciones por persona). Ocultar el
  // enlace aquí es solo cosmético: la ruta real la protege
  // VerificarPermisoModulo y responde 403 aunque se escriba a mano.
  const modulos = user?.modulos ?? [];
  const puedeVer = (modulo) => modulo === null || modulos.includes(modulo);

  const items = useMemo(() => [
    { modulo: null, label: 'Inicio', href: route('dashboard'), paths: ['/dashboard'], icon: LayoutDashboard },
    { modulo: 'animales', label: 'Animales', href: route('animales.index'), paths: ['/animales'], icon: UsersRound },
    { modulo: 'lotes', label: 'Lotes', href: route('lotes.index'), paths: ['/lotes'], icon: Boxes },
    { modulo: 'pesajes', label: 'Pesajes', href: route('pesajes.index'), paths: ['/pesajes'], icon: Scale },
    { modulo: 'alimentacion', label: 'Alimentación', href: route('alimentacion.index'), paths: ['/alimentacion'], icon: Utensils },
    { modulo: 'salud', label: 'Salud', href: '/eventos-salud', paths: ['/salud', '/eventos-salud'], icon: HeartPulse },
    { modulo: 'salud', label: 'Calendario sanitario', href: route('calendario.index'), paths: ['/calendario-sanitario'], icon: CalendarDays },
    { modulo: 'producciones', label: 'Producciones', href: route('producciones.index'), paths: ['/producciones'], icon: Milk },
    { modulo: 'reproduccion', label: 'Reproducción', href: route('reproduccion.index'), paths: ['/reproduccion'], icon: Dna },
    { modulo: 'reproduccion', label: 'Genética', href: route('genetica.index'), paths: ['/genetica'], icon: ChartNoAxesCombined },
    { modulo: 'reportes', label: 'Indicadores', href: route('reportes.ovinos'), paths: ['/reportes-ovinos'], icon: BarChart3 },
    { modulo: 'bajas', label: 'Bajas', href: route('bajas.index'), paths: ['/bajas'], icon: TrendingDown },
    { modulo: 'costos', label: 'Costos', href: route('costos.index'), paths: ['/costos'], icon: Wallet },
    { modulo: 'trabajadores', label: 'Trabajadores', href: route('trabajadores.index'), paths: ['/trabajadores'], icon: HardHat },
    { modulo: 'tareas', label: 'Recordatorios', href: route('tareas.index'), paths: ['/tareas'], icon: BellRing },
    { modulo: 'reportes', label: 'Reportes', href: route('reportes.index'), paths: ['/reportes'], icon: ClipboardList },
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
            {items.filter((item) => puedeVer(item.modulo)).map(renderItem)}
            {renderItem({
              label: 'Predicciones',
              href: user?.plan === 'premium' ? route('predicciones.index') : '#',
              paths: ['/predicciones'],
              icon: Gauge,
              locked: user?.plan !== 'premium',
            })}
            {user?.es_super_admin && renderItem({
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