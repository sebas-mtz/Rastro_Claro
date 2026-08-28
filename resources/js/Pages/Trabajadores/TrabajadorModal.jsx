import React, { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { X, Lock } from 'lucide-react';

const VACIO = {
    nombre: '',
    apellido_paterno: '',
    apellido_materno: '',
    curp: '',
    rfc: '',
    telefono: '',
    email: '',
    direccion: '',
    fecha_nacimiento: '',
    fecha_contratacion: '',
    puesto_id: '',
    area: '',
    tipo_contratacion: '',
    sueldo: '',
    costo_jornada: '',
    costo_hora: '',
    horario: '',
    contacto_emergencia: '',
    telefono_emergencia: '',
    observaciones: '',
    user_id: '',
    activo: true,
};

/** Convierte null en cadena vacía: React se queja de los inputs no controlados. */
function desdeTrabajador(t) {
    if (!t) return { ...VACIO };

    const datos = { ...VACIO };

    Object.keys(VACIO).forEach((campo) => {
        if (t[campo] !== undefined && t[campo] !== null) {
            datos[campo] = t[campo];
        }
    });

    return datos;
}

export default function TrabajadorModal({
    show,
    trabajador = null,
    puestos = [],
    areas = [],
    tiposContratacion = {},
    usuariosDisponibles = [],
    puedeVerSensibles = false,
    onClose,
}) {
    const editando = Boolean(trabajador);

    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm(desdeTrabajador(trabajador));

    // Al abrir el modal se recarga el formulario con el registro elegido.
    useEffect(() => {
        if (show) {
            setData(desdeTrabajador(trabajador));
            clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [show, trabajador?.id]);

    const cerrar = () => {
        reset();
        clearErrors();
        onClose();
    };

    const enviar = (e) => {
        e.preventDefault();

        const opciones = {
            preserveScroll: true,
            onSuccess: () => cerrar(),
        };

        if (editando) {
            put(route('trabajadores.update', trabajador.id), opciones);
        } else {
            post(route('trabajadores.store'), opciones);
        }
    };

    const campo = (nombre, etiqueta, tipo = 'text', extra = {}) => (
        <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">{etiqueta}</label>
            <input
                type={tipo}
                value={data[nombre] ?? ''}
                onChange={(e) => setData(nombre, e.target.value)}
                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                {...extra}
            />
            {errors[nombre] && <p className="text-xs text-red-600 mt-1">{errors[nombre]}</p>}
        </div>
    );

    // El retorno temprano va después de todos los hooks y las constantes que
    // el propio componente usa; de lo contrario React se queda sin ellas.
    if (!show) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-start justify-center bg-black/50 overflow-y-auto p-4">
            <div className="bg-white rounded-2xl shadow-xl w-full max-w-3xl my-8">
                <div className="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                    <h3 className="text-lg font-semibold text-slate-800">
                        {editando ? 'Editar trabajador' : 'Nuevo trabajador'}
                    </h3>
                    <button onClick={cerrar} className="text-slate-400 hover:text-slate-700" aria-label="Cerrar">
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <form onSubmit={enviar} className="px-6 py-5 space-y-6">
                    {/* Identidad */}
                    <section className="space-y-3">
                        <h4 className="text-sm font-semibold text-slate-500 uppercase tracking-wide">Identidad</h4>
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            {campo('nombre', 'Nombre *', 'text', { required: true, maxLength: 120 })}
                            {campo('apellido_paterno', 'Apellido paterno', 'text', { maxLength: 120 })}
                            {campo('apellido_materno', 'Apellido materno', 'text', { maxLength: 120 })}
                        </div>
                    </section>

                    {/* Contacto */}
                    <section className="space-y-3">
                        <h4 className="text-sm font-semibold text-slate-500 uppercase tracking-wide">Contacto</h4>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            {campo('telefono', 'Teléfono', 'tel', { maxLength: 30 })}
                            {campo('email', 'Correo electrónico', 'email')}
                        </div>
                    </section>

                    {/* Relación laboral */}
                    <section className="space-y-3">
                        <h4 className="text-sm font-semibold text-slate-500 uppercase tracking-wide">Relación laboral</h4>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Puesto *</label>
                                <select
                                    value={data.puesto_id ?? ''}
                                    onChange={(e) => setData('puesto_id', e.target.value)}
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                    required
                                >
                                    <option value="">Selecciona un puesto</option>
                                    {puestos.map((p) => (
                                        <option key={p.id} value={p.id}>{p.nombre}</option>
                                    ))}
                                </select>
                                {errors.puesto_id && <p className="text-xs text-red-600 mt-1">{errors.puesto_id}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Área</label>
                                <input
                                    list="areas-trabajador"
                                    value={data.area ?? ''}
                                    onChange={(e) => setData('area', e.target.value)}
                                    placeholder="Se toma del puesto si la dejas vacía"
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                    maxLength={80}
                                />
                                <datalist id="areas-trabajador">
                                    {areas.map((a) => <option key={a} value={a} />)}
                                </datalist>
                                {errors.area && <p className="text-xs text-red-600 mt-1">{errors.area}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Tipo de contratación</label>
                                <select
                                    value={data.tipo_contratacion ?? ''}
                                    onChange={(e) => setData('tipo_contratacion', e.target.value)}
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                >
                                    <option value="">Sin especificar</option>
                                    {Object.entries(tiposContratacion).map(([valor, etiqueta]) => (
                                        <option key={valor} value={valor}>{etiqueta}</option>
                                    ))}
                                </select>
                                {errors.tipo_contratacion && <p className="text-xs text-red-600 mt-1">{errors.tipo_contratacion}</p>}
                            </div>

                            {campo('fecha_contratacion', 'Fecha de contratación', 'date')}
                            {campo('horario', 'Horario', 'text', { placeholder: 'Ej. Lunes a sábado, 7:00–15:00', maxLength: 120 })}

                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">
                                    Cuenta del sistema (opcional)
                                </label>
                                <select
                                    value={data.user_id ?? ''}
                                    onChange={(e) => setData('user_id', e.target.value)}
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                >
                                    <option value="">Sin acceso al sistema</option>
                                    {usuariosDisponibles.map((u) => (
                                        <option key={u.id} value={u.id}>{u.name} — {u.email}</option>
                                    ))}
                                    {/* La cuenta ya enlazada no aparece en la lista de libres. */}
                                    {editando && trabajador?.usuario && (
                                        <option value={trabajador.usuario.id}>
                                            {trabajador.usuario.name} — {trabajador.usuario.email}
                                        </option>
                                    )}
                                </select>
                                <p className="text-xs text-slate-400 mt-1">
                                    Un trabajador no necesita cuenta para registrar su trabajo.
                                </p>
                                {errors.user_id && <p className="text-xs text-red-600 mt-1">{errors.user_id}</p>}
                            </div>
                        </div>
                    </section>

                    {/* Datos reservados */}
                    {puedeVerSensibles ? (
                        <section className="space-y-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <h4 className="text-sm font-semibold text-amber-800 uppercase tracking-wide flex items-center gap-1">
                                <Lock className="w-3.5 h-3.5" /> Datos reservados
                            </h4>
                            <p className="text-xs text-amber-700">
                                Visibles solo para administradores.
                            </p>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                {campo('curp', 'CURP', 'text', { maxLength: 18, placeholder: '18 caracteres' })}
                                {campo('rfc', 'RFC', 'text', { maxLength: 13, placeholder: '12 o 13 caracteres' })}
                                {campo('fecha_nacimiento', 'Fecha de nacimiento', 'date')}
                                {campo('direccion', 'Dirección', 'text', { maxLength: 255 })}
                                {campo('sueldo', 'Sueldo', 'number', { step: '0.01', min: '0' })}
                                {campo('costo_jornada', 'Costo por jornada', 'number', { step: '0.01', min: '0' })}
                                {campo('costo_hora', 'Costo por hora', 'number', { step: '0.01', min: '0' })}
                                <div className="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    {campo('contacto_emergencia', 'Contacto de emergencia', 'text', { maxLength: 150 })}
                                    {campo('telefono_emergencia', 'Teléfono de emergencia', 'tel', { maxLength: 30 })}
                                </div>
                            </div>

                            <p className="text-xs text-amber-700">
                                Si solo capturas uno de los dos costos, el sistema deriva el otro
                                sobre una jornada de 8 horas.
                            </p>
                        </section>
                    ) : (
                        <p className="text-xs text-slate-500 rounded-lg bg-slate-50 border border-slate-200 p-3 flex items-center gap-2">
                            <Lock className="w-4 h-4 text-slate-400" />
                            Los datos personales y salariales solo puede capturarlos un administrador.
                        </p>
                    )}

                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">Observaciones</label>
                        <textarea
                            value={data.observaciones ?? ''}
                            onChange={(e) => setData('observaciones', e.target.value)}
                            rows={2}
                            maxLength={2000}
                            className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                        />
                        {errors.observaciones && <p className="text-xs text-red-600 mt-1">{errors.observaciones}</p>}
                    </div>

                    <div className="flex justify-end gap-3 pt-2 border-t border-slate-200">
                        <button
                            type="button"
                            onClick={cerrar}
                            className="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium disabled:opacity-60"
                        >
                            {processing ? 'Guardando…' : editando ? 'Guardar cambios' : 'Registrar trabajador'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
