import { useForm, usePage, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function AlimentacionModal() {
    const {
        programaciones = [],
        animales = [],
        lotes = [],
        raciones = [],
    } = usePage().props;

    const [search, setSearch] = useState('');

    const { data, setData, post, reset, processing, errors } = useForm({
        fecha_inicio: new Date().toISOString().slice(0, 10),
        fecha_fin: '',
        indefinido: false,
        hora: '',
        animal_id: '',
        lote_id: '',
        racion_id: '',
        cantidad: '',
        unidad: 'kg',
        frecuencia: 'diaria',
        activa: true,
        notas: '',
    });

    const animalesById = useMemo(() => {
        const map = {};
        animales.forEach((a) => {
            map[a.id] = a.alias || a.arete || `Animal #${a.id}`;
        });
        return map;
    }, [animales]);

    const lotesById = useMemo(() => {
        const map = {};
        lotes.forEach((l) => {
            map[l.id] = l.nombre || `Lote #${l.id}`;
        });
        return map;
    }, [lotes]);

    const racionesById = useMemo(() => {
        const map = {};
        raciones.forEach((r) => {
            map[r.id] = r.nombre || `Ración #${r.id}`;
        });
        return map;
    }, [raciones]);

    const selectedRacion = useMemo(() => {
        return raciones.find((r) => String(r.id) === String(data.racion_id)) || null;
    }, [raciones, data.racion_id]);

    const costoEstimado = useMemo(() => {
        const cantidad = parseFloat(data.cantidad || 0);
        const precioKg = parseFloat(selectedRacion?.precio_kg || 0);

        if (!cantidad || !precioKg) return null;

        return (cantidad * precioKg).toFixed(2);
    }, [data.cantidad, selectedRacion]);

    const filtered = useMemo(() => {
        const term = search.toLowerCase().trim();

        if (!term) return programaciones;

        return programaciones.filter((row) => {
            const racionNombre = row.racion?.nombre || racionesById[row.racion_id] || '';
            const animalNombre =
                row.animal?.alias ||
                row.animal?.arete ||
                animalesById[row.animal_id] ||
                '';
            const loteNombre = row.lote?.nombre || lotesById[row.lote_id] || '';

            return (
                String(row.fecha_inicio || '').toLowerCase().includes(term) ||
                String(row.fecha_fin || '').toLowerCase().includes(term) ||
                String(row.hora || '').toLowerCase().includes(term) ||
                String(racionNombre).toLowerCase().includes(term) ||
                String(row.cantidad || '').toLowerCase().includes(term) ||
                String(animalNombre).toLowerCase().includes(term) ||
                String(loteNombre).toLowerCase().includes(term)
            );
        });
    }, [programaciones, search, racionesById, animalesById, lotesById]);

    const handleAnimalChange = (value) => {
        setData('animal_id', value);
        if (value) setData('lote_id', '');
    };

    const handleLoteChange = (value) => {
        setData('lote_id', value);
        if (value) setData('animal_id', '');
    };

    const handleIndefinidoChange = (checked) => {
        setData('indefinido', checked);
        if (checked) {
            setData('fecha_fin', '');
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
    
        post(route('programaciones-alimentacion.store'), {
            preserveState: true,   // ← mantiene el estado del componente (pestaña activa)
            preserveScroll: true,  // ← no salta al top de la página
            onSuccess: () => {
                reset(
                    'hora', 'animal_id', 'lote_id', 'racion_id',
                    'cantidad', 'notas', 'fecha_fin', 'indefinido'
                );
            },
        });
    };

    const handleDelete = (id) => {
        if (!confirm('¿Eliminar esta programación?')) return;
        router.delete(route('programaciones-alimentacion.destroy', id));
    };

    const handleToggleActiva = (id) => {
        router.patch(route('programaciones-alimentacion.toggleActiva', id));
    };

    return (
        <div>
            <h2 className="mb-4 text-xl font-semibold text-gray-800">
                Programación de alimentación
            </h2>

            <div className="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <h3 className="mb-3 text-sm font-semibold text-gray-800">
                    Asignar ración automática
                </h3>

                <form
                    onSubmit={handleSubmit}
                    className="grid grid-cols-1 gap-4 text-sm md:grid-cols-3"
                >
                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-600">
                            Fecha de inicio *
                        </label>
                        <input
                            type="date"
                            value={data.fecha_inicio}
                            onChange={(e) => setData('fecha_inicio', e.target.value)}
                            className="w-full rounded-md border-gray-300"
                        />
                        {errors.fecha_inicio && (
                            <p className="mt-1 text-xs text-red-500">{errors.fecha_inicio}</p>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-600">
                            Fecha de fin
                        </label>
                        <input
                            type="date"
                            value={data.fecha_fin}
                            onChange={(e) => setData('fecha_fin', e.target.value)}
                            disabled={data.indefinido}
                            className="w-full rounded-md border-gray-300 disabled:bg-gray-100"
                        />
                        {errors.fecha_fin && (
                            <p className="mt-1 text-xs text-red-500">{errors.fecha_fin}</p>
                        )}
                    </div>

                    <div className="flex items-end">
                        <label className="flex items-center gap-2 text-sm text-gray-700">
                            <input
                                type="checkbox"
                                checked={data.indefinido}
                                onChange={(e) => handleIndefinidoChange(e.target.checked)}
                                className="rounded border-gray-300"
                            />
                            Indefinido
                        </label>
                    </div>

                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-600">
                            Hora *
                        </label>
                        <input
                            type="time"
                            value={data.hora}
                            onChange={(e) => setData('hora', e.target.value)}
                            className="w-full rounded-md border-gray-300"
                        />
                        {errors.hora && (
                            <p className="mt-1 text-xs text-red-500">{errors.hora}</p>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-600">
                            Ración *
                        </label>
                        <select
                            value={data.racion_id}
                            onChange={(e) => setData('racion_id', e.target.value)}
                            className="w-full rounded-md border-gray-300"
                        >
                            <option value="">Selecciona una ración</option>
                            {raciones.map((r) => (
                                <option key={r.id} value={r.id}>
                                    {r.nombre || `Ración #${r.id}`}
                                </option>
                            ))}
                        </select>
                        {errors.racion_id && (
                            <p className="mt-1 text-xs text-red-500">{errors.racion_id}</p>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-600">
                            Cantidad *
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            value={data.cantidad}
                            onChange={(e) => setData('cantidad', e.target.value)}
                            className="w-full rounded-md border-gray-300"
                            placeholder="Ej. 12.50"
                        />
                        {errors.cantidad && (
                            <p className="mt-1 text-xs text-red-500">{errors.cantidad}</p>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-600">
                            Unidad *
                        </label>
                        <input
                            type="text"
                            value={data.unidad}
                            onChange={(e) => setData('unidad', e.target.value)}
                            className="w-full rounded-md border-gray-300"
                            placeholder="kg"
                        />
                        {errors.unidad && (
                            <p className="mt-1 text-xs text-red-500">{errors.unidad}</p>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-600">
                            Frecuencia *
                        </label>
                        <select
                            value={data.frecuencia}
                            onChange={(e) => setData('frecuencia', e.target.value)}
                            className="w-full rounded-md border-gray-300"
                        >
                            <option value="diaria">Diaria</option>
                            <option value="una_vez">Una vez</option>
                        </select>
                        {errors.frecuencia && (
                            <p className="mt-1 text-xs text-red-500">{errors.frecuencia}</p>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-600">
                            Costo estimado por aplicación
                        </label>
                        <div className="rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                            {costoEstimado ? `$${costoEstimado}` : '—'}
                        </div>
                    </div>

                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-600">
                            Animal
                        </label>
                        <select
                            value={data.animal_id}
                            onChange={(e) => handleAnimalChange(e.target.value)}
                            className="w-full rounded-md border-gray-300"
                        >
                            <option value="">Sin animal específico</option>
                            {animales.map((a) => (
                                <option key={a.id} value={a.id}>
                                    {a.alias || a.arete || `Animal #${a.id}`}
                                </option>
                            ))}
                        </select>
                        {errors.animal_id && (
                            <p className="mt-1 text-xs text-red-500">{errors.animal_id}</p>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-600">
                            Lote
                        </label>
                        <select
                            value={data.lote_id}
                            onChange={(e) => handleLoteChange(e.target.value)}
                            className="w-full rounded-md border-gray-300"
                        >
                            <option value="">Sin lote</option>
                            {lotes.map((l) => (
                                <option key={l.id} value={l.id}>
                                    {l.nombre || `Lote #${l.id}`}
                                </option>
                            ))}
                        </select>
                        {errors.lote_id && (
                            <p className="mt-1 text-xs text-red-500">{errors.lote_id}</p>
                        )}
                    </div>

                    {errors.destino && (
                        <div className="md:col-span-3">
                            <p className="text-xs text-red-500">{errors.destino}</p>
                        </div>
                    )}

                    <div className="md:col-span-3">
                        <label className="mb-1 block text-xs font-medium text-gray-600">
                            Notas
                        </label>
                        <textarea
                            value={data.notas}
                            onChange={(e) => setData('notas', e.target.value)}
                            className="w-full rounded-md border-gray-300"
                            rows={3}
                        />
                        {errors.notas && (
                            <p className="mt-1 text-xs text-red-500">{errors.notas}</p>
                        )}
                    </div>

                    <div className="flex justify-end pt-2 md:col-span-3">
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-md bg-green-600 px-4 py-2 text-xs text-white hover:bg-green-700 disabled:opacity-50"
                        >
                            {processing ? 'Guardando...' : 'Guardar programación'}
                        </button>
                    </div>
                </form>
            </div>

            <div className="mb-4 w-full sm:w-72">
                <label className="mb-1 block text-xs font-medium text-gray-600">
                    Buscar
                </label>
                <input
                    type="text"
                    className="w-full rounded-md border-gray-300 text-sm shadow-sm"
                    placeholder="Ración, animal, lote..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                />
            </div>

            <div className="overflow-hidden rounded-lg border border-gray-100 bg-white shadow-sm">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-2 text-left">Inicio</th>
                                <th className="px-4 py-2 text-left">Fin</th>
                                <th className="px-4 py-2 text-left">Hora</th>
                                <th className="px-4 py-2 text-left">Ración</th>
                                <th className="px-4 py-2 text-left">Cantidad</th>
                                <th className="px-4 py-2 text-left">Animal</th>
                                <th className="px-4 py-2 text-left">Lote</th>
                                <th className="px-4 py-2 text-left">Estado</th>
                                <th className="px-4 py-2 text-right">Acciones</th>
                            </tr>
                        </thead>

                        <tbody className="divide-y divide-gray-200 bg-white">
                            {filtered.map((r) => (
                                <tr key={r.id}>
                                    <td className="px-4 py-2">{r.fecha_inicio || '—'}</td>
                                    <td className="px-4 py-2">
                                        {r.fecha_fin || 'Indefinido'}
                                    </td>
                                    <td className="px-4 py-2">{r.hora || '—'}</td>
                                    <td className="px-4 py-2">
                                        {r.racion
                                            ? r.racion.nombre
                                            : r.racion_id
                                            ? racionesById[r.racion_id]
                                            : '—'}
                                    </td>
                                    <td className="px-4 py-2">
                                        {r.cantidad} {r.unidad || 'kg'}
                                    </td>
                                    <td className="px-4 py-2">
                                        {r.animal
                                            ? r.animal.alias || r.animal.arete
                                            : r.animal_id
                                            ? animalesById[r.animal_id]
                                            : '—'}
                                    </td>
                                    <td className="px-4 py-2">
                                        {r.lote
                                            ? r.lote.nombre
                                            : r.lote_id
                                            ? lotesById[r.lote_id]
                                            : '—'}
                                    </td>
                                    <td className="px-4 py-2">
                                        {r.activa ? 'Activa' : 'Pausada'}
                                    </td>
                                    <td className="px-4 py-2 text-right text-xs">
                                        <div className="flex justify-end gap-3">
                                            <button
                                                onClick={() => handleToggleActiva(r.id)}
                                                className="text-blue-600 hover:underline"
                                            >
                                                {r.activa ? 'Pausar' : 'Reanudar'}
                                            </button>
                                            <button
                                                onClick={() => handleDelete(r.id)}
                                                className="text-red-600 hover:underline"
                                            >
                                                Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}

                            {filtered.length === 0 && (
                                <tr>
                                    <td
                                        colSpan="9"
                                        className="px-4 py-6 text-center text-sm text-gray-500"
                                    >
                                        No hay programaciones registradas aún.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}