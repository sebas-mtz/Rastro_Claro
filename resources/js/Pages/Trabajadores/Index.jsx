import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { usePermiso, RolBadge } from '@/hooks/usePermiso';
import {
    Users, UserPlus, Edit2, Power, Activity, Mail, Trash2,
    CheckCircle, XCircle, Lock, Search, X,
} from 'lucide-react';

// ─── Modal formulario de trabajador ───────────────────────────────────────────
function ModalTrabajador({ isOpen, onClose, trabajador = null, roles = [] }) {
    const esEdicion = !!trabajador;
    const [form, setForm] = useState(trabajador ?? {
        name: '', email: '', telefono: '', role: 'trabajador',
        password: '', password_confirmation: '',
    });
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);

    function update(field, value) {
        setForm(prev => ({ ...prev, [field]: value }));
        setErrors(prev => { const e = { ...prev }; delete e[field]; return e; });
    }

    function submit(e) {
        e.preventDefault();
        setProcessing(true);
        const url    = esEdicion ? route('trabajadores.update', trabajador.id) : route('trabajadores.store');
        const method = esEdicion ? 'put' : 'post';
        router[method](url, form, {
            preserveScroll: true,
            onError:   (errs) => { setErrors(errs); setProcessing(false); },
            onSuccess: () => { setProcessing(false); onClose(); },
        });
    }

    if (!isOpen) return null;
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div className="flex items-center justify-between px-6 py-4 border-b">
                    <h2 className="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <UserPlus className="w-5 h-5 text-emerald-600" />
                        {esEdicion ? 'Editar Trabajador' : 'Invitar Trabajador'}
                    </h2>
                    <button onClick={onClose} className="text-gray-400 hover:text-gray-600"><X className="w-5 h-5" /></button>
                </div>
                <form onSubmit={submit} className="px-6 py-4 space-y-4">

                    {/* Aviso de invitación — solo en creación */}
                    {!esEdicion && (
                        <div className="flex items-start gap-3 bg-emerald-50 border border-emerald-200
                                        rounded-xl px-4 py-3 text-sm text-emerald-800">
                            <Mail className="w-4 h-4 mt-0.5 flex-shrink-0 text-emerald-600" />
                            <p>
                                Se generará una <strong>contraseña temporal</strong> y se enviará
                                una invitación al correo del trabajador. Al ingresar por primera vez,
                                el sistema le pedirá crear su propia contraseña.
                            </p>
                        </div>
                    )}

                    {/* Nombre */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
                        <input type="text" value={form.name} onChange={e => update('name', e.target.value)}
                            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                            placeholder="Juan Pérez García" />
                        {errors.name && <p className="text-red-500 text-xs mt-1">{errors.name}</p>}
                    </div>

                    {/* Email */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Correo electrónico *</label>
                        <input type="email" value={form.email} onChange={e => update('email', e.target.value)}
                            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                            placeholder="juan@rancho.com" />
                        {errors.email && <p className="text-red-500 text-xs mt-1">{errors.email}</p>}
                    </div>

                    {/* Teléfono */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="tel" value={form.telefono ?? ''} onChange={e => update('telefono', e.target.value)}
                            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                            placeholder="+52 771 000 0000" />
                    </div>

                    {/* Rol */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Rol *</label>
                        <select value={form.role} onChange={e => update('role', e.target.value)}
                            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                            {roles.map(r => <option key={r.value} value={r.value}>{r.label}</option>)}
                        </select>
                        {errors.role && <p className="text-red-500 text-xs mt-1">{errors.role}</p>}
                        <p className="text-xs text-gray-400 mt-1">
                            {form.role === 'administrador' && '⚠️ Acceso total al sistema'}
                            {form.role === 'encargado'     && 'Puede crear/editar la mayoría de registros'}
                            {form.role === 'trabajador'    && 'Puede registrar actividades básicas'}
                            {form.role === 'solo_lectura'  && 'Solo puede consultar información'}
                        </p>
                    </div>

                    {/* Contraseña — solo en edición para cambio manual */}
                    {esEdicion && (
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Nueva contraseña <span className="text-gray-400">(dejar vacío para no cambiar)</span>
                            </label>
                            <input type="password" value={form.password ?? ''} onChange={e => update('password', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                                placeholder="••••••••" />
                            {errors.password && <p className="text-red-500 text-xs mt-1">{errors.password}</p>}
                            {form.password && (
                                <div className="mt-2">
                                    <input type="password" value={form.password_confirmation ?? ''}
                                        onChange={e => update('password_confirmation', e.target.value)}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                                        placeholder="Confirmar nueva contraseña" />
                                </div>
                            )}
                        </div>
                    )}

                    {/* Activo (solo edición) */}
                    {esEdicion && (
                        <div className="flex items-center gap-3">
                            <input type="checkbox" id="activo" checked={form.activo}
                                onChange={e => update('activo', e.target.checked)}
                                className="w-4 h-4 text-emerald-600 rounded" />
                            <label htmlFor="activo" className="text-sm text-gray-700">Trabajador activo</label>
                        </div>
                    )}

                    <div className="flex gap-3 pt-2">
                        <button type="button" onClick={onClose}
                            className="flex-1 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit" disabled={processing}
                            className="flex-1 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 disabled:opacity-50">
                            {processing ? 'Enviando...' : (esEdicion ? 'Guardar cambios' : '📧 Enviar invitación')}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

// ─── Componente principal ──────────────────────────────────────────────────────
export default function TrabajadoresIndex({ trabajadores = [], puedeCrear, puedeEditar, roles = [] }) {
    const { flash } = usePage().props || {};
    const { user } = usePermiso();
    const [modalOpen, setModalOpen]       = useState(false);
    const [editando, setEditando]         = useState(null);
    const [busqueda, setBusqueda]         = useState('');
    const [filtroRol, setFiltroRol]       = useState('todos');
    const [confirmarToggle, setConfirmarToggle] = useState(null);
    const [confirmarEliminar, setConfirmarEliminar] = useState(null);

    function eliminar(t) {
        router.delete(route('trabajadores.destroy', t.id), {
            preserveScroll: true,
            onSuccess: () => setConfirmarEliminar(null),
        });
    }

    const filtrados = trabajadores.filter(t => {
        const coincideBusqueda = !busqueda ||
            t.name.toLowerCase().includes(busqueda.toLowerCase()) ||
            t.email.toLowerCase().includes(busqueda.toLowerCase());
        const coincideRol = filtroRol === 'todos' || t.role === filtroRol;
        return coincideBusqueda && coincideRol;
    });

    function abrirEditar(t) { setEditando(t); setModalOpen(true); }
    function cerrarModal()   { setModalOpen(false); setEditando(null); }

    function toggleActivo(t) {
        router.patch(route('trabajadores.toggle-activo', t.id), {}, {
            preserveScroll: true,
            onSuccess: () => setConfirmarToggle(null),
        });
    }

    const stats = {
        total:    trabajadores.length,
        activos:  trabajadores.filter(t => t.activo).length,
        admins:   trabajadores.filter(t => t.role === 'administrador').length,
    };

    return (
        <>
            <Head title="Trabajadores" />
            <div className="py-8 px-4 max-w-7xl mx-auto space-y-6">

                {/* ── Encabezado ─────────────────────────────────────── */}
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-800 flex items-center gap-2">
                            <Users className="w-7 h-7 text-emerald-600" />
                            Trabajadores
                        </h1>
                        <p className="text-sm text-gray-500 mt-0.5">Gestión de usuarios y permisos del sistema</p>
                    </div>
                    <div className="flex gap-2">
                        {user?.isAdmin && (
                            <button onClick={() => router.get(route('trabajadores.historial-global'))}
                                className="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-xl text-sm hover:bg-gray-50">
                                <Activity className="w-4 h-4" /> Historial
                            </button>
                        )}
                        {puedeCrear && (
                            <button onClick={() => { setEditando(null); setModalOpen(true); }}
                                className="flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700">
                                <UserPlus className="w-4 h-4" /> Nuevo Trabajador
                            </button>
                        )}
                    </div>
                </div>

                {/* ── Flash ──────────────────────────────────────────── */}
                {flash?.success && (
                    <div className="flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
                        <CheckCircle className="w-4 h-4 flex-shrink-0" />{flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                        <XCircle className="w-4 h-4 flex-shrink-0" />{flash.error}
                    </div>
                )}

                {/* ── Stats ──────────────────────────────────────────── */}
                <div className="grid grid-cols-3 gap-4">
                    {[
                        { label: 'Total', value: stats.total,   color: 'text-gray-700', bg: 'bg-gray-50' },
                        { label: 'Activos', value: stats.activos, color: 'text-emerald-700', bg: 'bg-emerald-50' },
                        { label: 'Admins', value: stats.admins,  color: 'text-red-700',     bg: 'bg-red-50' },
                    ].map(s => (
                        <div key={s.label} className={`${s.bg} rounded-xl p-4 text-center`}>
                            <p className={`text-2xl font-bold ${s.color}`}>{s.value}</p>
                            <p className="text-xs text-gray-500">{s.label}</p>
                        </div>
                    ))}
                </div>

                {/* ── Filtros ─────────────────────────────────────────── */}
                <div className="flex flex-wrap gap-3">
                    <div className="relative flex-1 min-w-[200px]">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input type="text" placeholder="Buscar por nombre o correo..."
                            value={busqueda} onChange={e => setBusqueda(e.target.value)}
                            className="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400" />
                    </div>
                    <select value={filtroRol} onChange={e => setFiltroRol(e.target.value)}
                        className="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                        <option value="todos">Todos los roles</option>
                        <option value="administrador">Administrador</option>
                        <option value="encargado">Encargado</option>
                        <option value="trabajador">Trabajador</option>
                        <option value="solo_lectura">Solo lectura</option>
                    </select>
                </div>

                {/* ── Tabla ───────────────────────────────────────────── */}
                <div className="bg-white rounded-2xl shadow-sm border overflow-hidden">
                    {filtrados.length === 0 ? (
                        <div className="text-center py-12 text-gray-400">
                            <Users className="w-10 h-10 mx-auto mb-2 opacity-40" />
                            <p className="text-sm">No se encontraron trabajadores.</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-gray-50 border-b">
                                    <tr>
                                        <th className="text-left px-4 py-3 font-semibold text-gray-600">Trabajador</th>
                                        <th className="text-left px-4 py-3 font-semibold text-gray-600">Rol</th>
                                        <th className="text-left px-4 py-3 font-semibold text-gray-600 hidden md:table-cell">Registro</th>
                                        <th className="text-center px-4 py-3 font-semibold text-gray-600">Estado</th>
                                        <th className="text-right px-4 py-3 font-semibold text-gray-600">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {filtrados.map(t => (
                                        <tr key={t.id} className={`hover:bg-gray-50 ${!t.activo ? 'opacity-60' : ''}`}>
                                            <td className="px-4 py-3">
                                                <div className="font-semibold text-gray-800">{t.name}</div>
                                                <div className="text-gray-400 text-xs">{t.email}</div>
                                                {t.telefono && <div className="text-gray-400 text-xs">{t.telefono}</div>}
                                            </td>
                                            <td className="px-4 py-3">
                                                <RolBadge role={t.role} />
                                            </td>
                                            <td className="px-4 py-3 hidden md:table-cell text-gray-400 text-xs">
                                                {t.created_at}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                {t.activo
                                                    ? <span className="inline-flex items-center gap-1 text-emerald-600 text-xs"><CheckCircle className="w-3.5 h-3.5" />Activo</span>
                                                    : <span className="inline-flex items-center gap-1 text-red-500 text-xs"><XCircle className="w-3.5 h-3.5" />Inactivo</span>
                                                }
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center justify-end gap-1">
                                                    {user?.isAdmin && (
                                                        <button onClick={() => router.get(route('trabajadores.historial', t.id))}
                                                            title="Ver historial"
                                                            className="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg">
                                                            <Activity className="w-4 h-4" />
                                                        </button>
                                                    )}
                                                    {puedeEditar && t.id !== user?.id && (
                                                        <>
                                                            <button onClick={() => abrirEditar(t)}
                                                                title="Editar"
                                                                className="p-1.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg">
                                                                <Edit2 className="w-4 h-4" />
                                                            </button>
                                                            {confirmarToggle === t.id ? (
                                                                <div className="flex gap-1">
                                                                    <button onClick={() => toggleActivo(t)}
                                                                        className={`text-xs px-2 py-1 rounded-lg text-white ${t.activo ? 'bg-red-500 hover:bg-red-600' : 'bg-emerald-500 hover:bg-emerald-600'}`}>
                                                                        {t.activo ? 'Desactivar' : 'Activar'}
                                                                    </button>
                                                                    <button onClick={() => setConfirmarToggle(null)}
                                                                        className="text-xs px-2 py-1 rounded-lg bg-gray-200 text-gray-700">
                                                                        No
                                                                    </button>
                                                                </div>
                                                            ) : (
                                                                <button onClick={() => setConfirmarToggle(t.id)}
                                                                    title={t.activo ? 'Desactivar' : 'Activar'}
                                                                    className={`p-1.5 rounded-lg ${t.activo ? 'text-gray-400 hover:text-red-600 hover:bg-red-50' : 'text-gray-400 hover:text-emerald-600 hover:bg-emerald-50'}`}>
                                                                    <Power className="w-4 h-4" />
                                                                </button>
                                                            )}
                                                            {/* Botón eliminar con confirmación */}
                                                            {confirmarEliminar === t.id ? (
                                                                <div className="flex gap-1 items-center">
                                                                    <span className="text-xs text-red-600 font-medium">¿Eliminar?</span>
                                                                    <button onClick={() => eliminar(t)}
                                                                        className="text-xs px-2 py-1 rounded-lg bg-red-600 text-white hover:bg-red-700">
                                                                        Sí
                                                                    </button>
                                                                    <button onClick={() => setConfirmarEliminar(null)}
                                                                        className="text-xs px-2 py-1 rounded-lg bg-gray-200 text-gray-700">
                                                                        No
                                                                    </button>
                                                                </div>
                                                            ) : (
                                                                <button onClick={() => setConfirmarEliminar(t.id)}
                                                                    title="Eliminar trabajador"
                                                                    className="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg">
                                                                    <Trash2 className="w-4 h-4" />
                                                                </button>
                                                            )}
                                                        </>
                                                    )}
                                                    {t.id === user?.id && (
                                                        <span className="text-xs text-gray-400 italic px-2">(tú)</span>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {/* ── Tabla de permisos ───────────────────────────────── */}
                <details className="bg-white rounded-2xl shadow-sm border">
                    <summary className="px-6 py-4 cursor-pointer font-semibold text-gray-700 flex items-center gap-2">
                        <Lock className="w-4 h-4 text-gray-400" />
                        Tabla de permisos por rol
                    </summary>
                    <div className="px-6 pb-6 overflow-x-auto">
                        <table className="w-full text-xs mt-2">
                            <thead>
                                <tr className="border-b">
                                    <th className="text-left py-2 font-semibold text-gray-600">Módulo</th>
                                    {['Administrador','Encargado','Trabajador','Solo lectura'].map(r => (
                                        <th key={r} className="text-center py-2 font-semibold text-gray-600 px-2">{r}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {[
                                    ['Animales',     ['Ver,Crear,Editar,Eliminar','Ver,Crear,Editar','Ver','Ver']],
                                    ['Lotes',        ['Ver,Crear,Editar,Eliminar','Ver,Crear,Editar','Ver','Ver']],
                                    ['Salud',        ['Ver,Crear,Editar,Eliminar','Ver,Crear,Editar','Ver,Crear','Ver']],
                                    ['Costos',       ['Ver,Crear,Editar,Eliminar','Ver,Crear,Editar','—','Ver']],
                                    ['Alimentación', ['Ver,Crear,Editar,Eliminar','Ver,Crear,Editar','Ver,Crear','Ver']],
                                    ['Producciones', ['Ver,Crear,Editar,Eliminar','Ver,Crear,Editar','Ver,Crear','Ver']],
                                    ['Reproducción', ['Ver,Crear,Editar,Eliminar','Ver,Crear,Editar','Ver','Ver']],
                                    ['Ventas',       ['Ver,Crear,Editar,Eliminar','Ver,Crear,Editar','—','Ver']],
                                    ['Trabajadores', ['Ver,Crear,Editar','Ver','—','—']],
                                    ['Historial',    ['Ver','—','—','—']],
                                ].map(([mod, perms]) => (
                                    <tr key={mod} className="hover:bg-gray-50">
                                        <td className="py-2 font-medium text-gray-700">{mod}</td>
                                        {perms.map((p, i) => (
                                            <td key={i} className="text-center py-2 px-2">
                                                <span className={p === '—' ? 'text-gray-300' : 'text-emerald-600'}>{p}</span>
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>

            <ModalTrabajador
                isOpen={modalOpen}
                onClose={cerrarModal}
                trabajador={editando}
                roles={roles}
            />
        </>
    );
}

TrabajadoresIndex.layout = page => <AppLayout>{page}</AppLayout>;