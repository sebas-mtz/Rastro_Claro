import { router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import AppLayout from "@/Layouts/AppLayout";
import { Link } from "@inertiajs/react";

function Pesajes() {
    const { animales = [], flash = {} } = usePage().props;

    const [busqueda, setBusqueda]       = useState('');
    const [editingId, setEditingId]     = useState(null); // id del Pesaje editando
    const [animalAbierto, setAnimalAbierto] = useState(null); // id del Animal con historial abierto

    const { data, setData, post, put, processing, reset, errors, clearErrors } = useForm({
        animal_id: '',
        fecha:     new Date().toISOString().split('T')[0],
        peso:      '',
        notas:     '',
    });

    const isEditing = Boolean(editingId);

    const inputClass = isEditing
        ? 'w-full rounded-md border-blue-300 bg-blue-50 focus:border-blue-500 focus:ring-blue-300 text-sm'
        : 'w-full rounded-md border-gray-300 focus:border-blue-300 focus:ring-blue-200 text-sm';

    const preventWheelChange = (e) => e.target.blur();

    // ─── Filtro por búsqueda ──────────────────────────────────────────────────
    const animalesFiltrados = useMemo(() => {
        const q = busqueda.toLowerCase().trim();
        if (!q) return animales;
        return animales.filter(a =>
            a.arete?.toLowerCase().includes(q) ||
            a.alias?.toLowerCase().includes(q) ||
            a.especie?.toLowerCase().includes(q) ||
            a.raza?.toLowerCase().includes(q)
        );
    }, [animales, busqueda]);

    // ─── Helpers de formato ───────────────────────────────────────────────────
    const fmtPeso = (v) => (v != null ? `${v} kg` : '—');
    const fmtNum  = (v, dec = 3) => (v != null ? Number(v).toFixed(dec) : '—');

    const badgeGanancia = (ganancia) => {
        if (ganancia == null) return null;
        if (ganancia > 0)  return 'bg-green-50 text-green-700 border-green-200';
        if (ganancia < 0)  return 'bg-red-50 text-red-700 border-red-200';
        return 'bg-gray-100 text-gray-600 border-gray-200';
    };

    // ─── Seleccionar animal en el formulario ──────────────────────────────────
    const handleSelectAnimal = (animalId) => {
        clearErrors();
        setData(prev => ({ ...prev, animal_id: String(animalId) }));
    };

    // ─── Editar pesaje ────────────────────────────────────────────────────────
    const handleEdit = (pesaje, animalId) => {
        setEditingId(pesaje.id);
        setData({
            animal_id: String(animalId),
            fecha:     pesaje.fecha,
            peso:      pesaje.peso,
            notas:     pesaje.notas ?? '',
        });
        clearErrors();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    // ─── Limpiar formulario ───────────────────────────────────────────────────
    const clearForm = () => {
        setEditingId(null);
        reset();
        setData({
            animal_id: '',
            fecha:     new Date().toISOString().split('T')[0],
            peso:      '',
            notas:     '',
        });
        clearErrors();
    };

    // ─── Crear ────────────────────────────────────────────────────────────────
    const handleCreate = (e) => {
        e.preventDefault();
        post(route('pesajes.store'), { onSuccess: clearForm });
    };

    // ─── Actualizar ───────────────────────────────────────────────────────────
    const handleUpdate = (e) => {
        e.preventDefault();
        if (!editingId) return;
        put(route('pesajes.update', editingId), { onSuccess: clearForm });
    };

    // ─── Eliminar ─────────────────────────────────────────────────────────────
    const handleDelete = (pesaje) => {
        if (!confirm(`¿Eliminar el pesaje del ${pesaje.fecha} (${pesaje.peso} kg)?`)) return;
        router.delete(route('pesajes.destroy', pesaje.id));
    };

    // ─── Animal seleccionado en el form ───────────────────────────────────────
    const animalSeleccionado = useMemo(
        () => animales.find(a => String(a.id) === String(data.animal_id)) ?? null,
        [animales, data.animal_id]
    );

    // ─── Render ───────────────────────────────────────────────────────────────
    return (
        <div className="space-y-6">

            {/* Encabezado */}
            <div>
                <h2 className="text-xl font-semibold text-gray-800">Pesajes</h2>
                <p className="text-sm text-gray-500">
                    Registro de peso por animal a lo largo del tiempo. Base para calcular la conversión alimenticia.
                </p>
            </div>

            {/* Flash */}
            {flash.success && (
                <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {flash.success}
                </div>
            )}
            {flash.error && (
                <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {flash.error}
                </div>
            )}

            {/* Formulario */}
            <div className={`rounded-xl border shadow-sm p-5 ${isEditing ? 'bg-blue-50 border-blue-200' : 'bg-white border-blue-100'}`}>
                <div className="mb-4 flex items-center justify-between flex-wrap gap-2">
                    <h3 className={`text-sm font-semibold ${isEditing ? 'text-blue-800' : 'text-gray-800'}`}>
                        {isEditing ? 'Editar pesaje' : 'Registrar pesaje'}
                    </h3>
                    {isEditing && (
                        <p className="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-3 py-1">
                            Editando pesaje de: <strong>{animalSeleccionado?.arete ?? '—'}</strong>
                        </p>
                    )}
                </div>

                <form
                    onSubmit={isEditing ? handleUpdate : handleCreate}
                    className="grid grid-cols-1 gap-4 text-sm md:grid-cols-3"
                >
                    {/* Selector de animal */}
                    <div className="md:col-span-3">
                        <label className="mb-1 block text-xs font-medium text-gray-600">Animal *</label>
                        <select
                            className={inputClass}
                            value={data.animal_id}
                            onChange={e => handleSelectAnimal(e.target.value)}
                            disabled={isEditing}
                        >
                            <option value="">Selecciona un animal</option>
                            {animales.map(a => (
                                <option key={a.id} value={a.id}>
                                    {a.arete}{a.alias ? ` — ${a.alias}` : ''} · {a.especie} {a.raza ? `(${a.raza})` : ''}
                                </option>
                            ))}
                        </select>
                        {errors.animal_id && <p className="mt-1 text-xs text-red-500">{errors.animal_id}</p>}
                    </div>

                    {/* Fecha */}
                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-600">Fecha *</label>
                        <input
                            type="date"
                            className={inputClass}
                            value={data.fecha}
                            onChange={e => setData('fecha', e.target.value)}
                        />
                        {errors.fecha && <p className="mt-1 text-xs text-red-500">{errors.fecha}</p>}
                    </div>

                    {/* Peso */}
                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-600">Peso (kg) *</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            inputMode="decimal"
                            onWheel={preventWheelChange}
                            className={inputClass}
                            placeholder="Ej. 450.5"
                            value={data.peso}
                            onChange={e => setData('peso', e.target.value)}
                        />
                        {errors.peso && <p className="mt-1 text-xs text-red-500">{errors.peso}</p>}
                    </div>

                    {/* Notas */}
                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-600">Notas</label>
                        <input
                            type="text"
                            className={inputClass}
                            placeholder="Ej. Post-parto, ayuno previo..."
                            value={data.notas}
                            onChange={e => setData('notas', e.target.value)}
                        />
                    </div>

                    {/* Info del animal seleccionado */}
                    {animalSeleccionado && (
                        <div className="md:col-span-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-600 flex flex-wrap gap-4">
                            <span>Peso registrado actualmente: <strong>{fmtPeso(animalSeleccionado.peso)}</strong></span>
                            {animalSeleccionado.peso_actual != null && (
                                <span>Último pesaje: <strong>{fmtPeso(animalSeleccionado.peso_actual)}</strong></span>
                            )}
                            {animalSeleccionado.ganancia_total != null && (
                                <span>Ganancia total: <strong>{fmtNum(animalSeleccionado.ganancia_total, 2)} kg</strong></span>
                            )}
                            {animalSeleccionado.ganancia_diaria != null && (
                                <span>GDP: <strong>{fmtNum(animalSeleccionado.ganancia_diaria, 3)} kg/día</strong></span>
                            )}
                        </div>
                    )}

                    {/* Botones */}
                    <div className="md:col-span-3 flex justify-end gap-2 pt-1">
                        {isEditing && (
                            <button
                                type="button"
                                onClick={clearForm}
                                className="rounded-md border border-gray-300 px-4 py-2 text-xs text-gray-700 hover:bg-gray-50"
                            >
                                Cancelar
                            </button>
                        )}
                        <button
                            type="submit"
                            disabled={processing}
                            className={`rounded-md px-4 py-2 text-xs text-white disabled:opacity-50 ${
                                isEditing ? 'bg-blue-600 hover:bg-blue-700' : 'bg-green-600 hover:bg-green-700'
                            }`}
                        >
                            {processing ? 'Guardando...' : isEditing ? 'Guardar cambios' : 'Registrar pesaje'}
                        </button>
                    </div>
                </form>
            </div>

            {/* Buscador */}
            <div className="flex items-center gap-3">
                <input
                    type="text"
                    className="w-full max-w-sm rounded-md border border-gray-300 text-sm focus:border-blue-300 focus:ring-blue-200"
                    placeholder="Buscar por arete, alias, especie o raza..."
                    value={busqueda}
                    onChange={e => setBusqueda(e.target.value)}
                />
                <span className="text-xs text-gray-400">{animalesFiltrados.length} animal(es)</span>
            </div>

            {/* Tabla de animales con sus pesajes */}
            <div className="space-y-3">
                {animalesFiltrados.map(animal => {
                    const tieneHistorial = animal.pesajes?.length > 0;
                    const abierto = animalAbierto === animal.id;

                    return (
                        <div
                            key={animal.id}
                            className="rounded-xl border border-blue-50 bg-white shadow-sm overflow-hidden"
                        >
                            {/* Fila principal del animal */}
                            <div className="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <h3 className="text-sm font-semibold text-gray-900">{animal.arete}</h3>
                                        {animal.alias && (
                                            <span className="text-xs text-gray-400">({animal.alias})</span>
                                        )}
                                        <span className="rounded-full bg-blue-50 border border-blue-100 px-2 py-0.5 text-[11px] text-blue-700">
                                            {animal.especie}{animal.raza ? ` · ${animal.raza}` : ''}
                                        </span>
                                        <span className="text-[11px] text-gray-400">{animal.sexo}</span>
                                    </div>

                                    <div className="mt-1 flex flex-wrap gap-3 text-xs text-gray-500">
                                        <span>Peso actual: <strong className="text-gray-700">{fmtPeso(animal.peso_actual ?? animal.peso)}</strong></span>

                                        {animal.ganancia_total != null && (
                                            <span className={`rounded-full border px-2 py-0.5 text-[11px] ${badgeGanancia(animal.ganancia_total)}`}>
                                                {animal.ganancia_total >= 0 ? '+' : ''}{fmtNum(animal.ganancia_total, 2)} kg total
                                            </span>
                                        )}

                                        {animal.ganancia_diaria != null && (
                                            <span className="text-gray-500">
                                                GDP: <strong>{fmtNum(animal.ganancia_diaria, 3)} kg/día</strong>
                                            </span>
                                        )}

                                        {animal.dias_seguimiento != null && (
                                            <span className="text-gray-400">{animal.dias_seguimiento} días de seguimiento</span>
                                        )}
                                    </div>
                                </div>

                                <div className="flex items-center gap-2 flex-wrap">
                                    <button
                                        type="button"
                                        onClick={() => handleSelectAnimal(animal.id)}
                                        className="rounded-md bg-green-600 px-4 py-2 text-xs text-white hover:bg-green-700"
                                    >
                                        + Pesaje
                                    </button>

                                    {tieneHistorial && (
                                        <button
                                            type="button"
                                            onClick={() => setAnimalAbierto(abierto ? null : animal.id)}
                                            className="rounded-md border border-gray-200 px-4 py-2 text-xs text-gray-600 hover:bg-gray-50"
                                        >
                                            {abierto ? 'Ocultar historial' : `Ver historial (${animal.pesajes.length})`}
                                        </button>
                                    )}
                                </div>
                            </div>

                            {/* Historial de pesajes */}
                            {abierto && tieneHistorial && (
                                <div className="border-t border-gray-100 bg-gray-50 px-5 py-3">
                                    <table className="w-full text-xs">
                                        <thead>
                                            <tr className="text-left text-gray-400 border-b border-gray-200">
                                                <th className="pb-2 font-medium">Fecha</th>
                                                <th className="pb-2 font-medium">Peso</th>
                                                <th className="pb-2 font-medium">Δ vs anterior</th>
                                                <th className="pb-2 font-medium">Notas</th>
                                                <th className="pb-2 font-medium text-right">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {[...animal.pesajes]
                                                .sort((a, b) => new Date(b.fecha) - new Date(a.fecha))
                                                .map((pesaje, idx, arr) => {
                                                    const siguiente = arr[idx + 1]; // más antiguo en orden desc
                                                    const delta = siguiente
                                                        ? round2(pesaje.peso - siguiente.peso)
                                                        : null;

                                                    return (
                                                        <tr key={pesaje.id} className="border-b border-gray-100 last:border-0">
                                                            <td className="py-2 text-gray-700">{pesaje.fecha}</td>
                                                            <td className="py-2 font-medium text-gray-800">{pesaje.peso} kg</td>
                                                            <td className="py-2">
                                                                {delta != null ? (
                                                                    <span className={`rounded-full border px-2 py-0.5 text-[11px] ${badgeGanancia(delta)}`}>
                                                                        {delta >= 0 ? '+' : ''}{delta} kg
                                                                    </span>
                                                                ) : (
                                                                    <span className="text-gray-300">—</span>
                                                                )}
                                                            </td>
                                                            <td className="py-2 text-gray-400">{pesaje.notas ?? '—'}</td>
                                                            <td className="py-2 text-right">
                                                                <div className="flex justify-end gap-2">
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => handleEdit(pesaje, animal.id)}
                                                                        className="rounded border border-blue-200 px-3 py-1 text-blue-700 hover:bg-blue-50"
                                                                    >
                                                                        Editar
                                                                    </button>
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => handleDelete(pesaje)}
                                                                        className="rounded border border-red-200 px-3 py-1 text-red-600 hover:bg-red-50"
                                                                    >
                                                                        Eliminar
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            {/* Sin pesajes registrados */}
                            {abierto && !tieneHistorial && (
                                <div className="border-t border-gray-100 bg-gray-50 px-5 py-3 text-xs text-gray-400">
                                    Sin pesajes registrados.
                                </div>
                            )}
                        </div>
                    );
                })}

                {animalesFiltrados.length === 0 && (
                    <p className="text-sm text-gray-400">
                        {busqueda ? 'No se encontraron animales con esa búsqueda.' : 'No hay animales registrados.'}
                    </p>
                )}
            </div>
        </div>
    );
}

// Helper fuera del componente
Pesajes.layout = (page) => <AppLayout>{page}</AppLayout>;

export default Pesajes;
