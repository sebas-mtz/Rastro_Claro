import { useForm, usePage, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function Raciones() {
    const { alimentaciones = [], animales = [], lotes = [], raciones = [] } = usePage().props;

    const [search, setSearch] = useState('');

    const { data, setData, post, reset, processing, errors } = useForm({
        fecha: new Date().toISOString().slice(0, 10),
        animal_id: '',
        lote_id: '',
        racion_id: '',
        consumo_kg: '',
        costo: '',
    });

    const animalesById = useMemo(() => {
        const m = {};
        animales.forEach(a => {
            m[a.id] = a.alias || a.arete || `Animal #${a.id}`;
        });
        return m;
    }, [animales]);

    const lotesById = useMemo(() => {
        const m = {};
        lotes.forEach(l => {
            m[l.id] = l.nombre || `Lote #${l.id}`;
        });
        return m;
    }, [lotes]);

    const racionesById = useMemo(() => {
        const m = {};
        raciones.forEach(r => {
            m[r.id] = r.nombre || `Ración #${r.id}`;
        });
        return m;
    }, [raciones]);

    const filtered = useMemo(() => {
        const term = search.toLowerCase();
        if (!term) return alimentaciones;

        return alimentaciones.filter(r =>
            String(r.racion_id ?? '').toLowerCase().includes(term) ||
            String(r.consumo_kg ?? '').toLowerCase().includes(term)
        );
    }, [alimentaciones, search]);

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('alimentacion.store'), {
            onSuccess: () =>
                reset('racion_id', 'consumo_kg', 'costo', 'animal_id', 'lote_id'),
        });
    };

    const handleDelete = (id) => {
        if (!confirm('¿Eliminar este registro de alimentación?')) return;
        router.delete(route('alimentacion.destroy', id));
    };

    return (
        <div>
            <h2 className="text-xl font-semibold text-gray-800 mb-4">
                Raciones
            </h2>

            {/* FORMULARIO */}
            <div className="mb-6 bg-white border border-gray-200 rounded-lg shadow-sm p-4">
                <h3 className="text-sm font-semibold text-gray-800 mb-3">
                    Registrar nueva ración
                </h3>

                <form
                    onSubmit={handleSubmit}
                    className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm"
                >
                    {/* Fecha */}
                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">
                            Fecha *
                        </label>
                        <input
                            type="date"
                            value={data.fecha}
                            onChange={e => setData('fecha', e.target.value)}
                            className="w-full rounded-md border-gray-300"
                        />
                        {errors.fecha && (
                            <p className="text-xs text-red-500 mt-1">{errors.fecha}</p>
                        )}
                    </div>

                    {/* Ración (FK) */}
<div>
    <label className="block text-xs font-medium text-gray-600 mb-1">
        Ración *
    </label>
    <select
        value={data.racion_id}
        onChange={e => setData('racion_id', e.target.value)}
        className="w-full rounded-md border-gray-300"
    >
        <option value="">Selecciona una ración</option>
        {raciones.map(r => (
            <option key={r.id} value={r.id}>
                {r.nombre || `Ración #${r.id}`}
            </option>
        ))}
    </select>
    {errors.racion_id && (
        <p className="text-xs text-red-500 mt-1">{errors.racion_id}</p>
    )}
</div>


                    {/* Consumo kg */}
                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">
                            Consumo (kg) *
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            value={data.consumo_kg}
                            onChange={e => setData('consumo_kg', e.target.value)}
                            className="w-full rounded-md border-gray-300"
                        />
                        {errors.consumo_kg && (
                            <p className="text-xs text-red-500 mt-1">{errors.consumo_kg}</p>
                        )}
                    </div>

                    {/* Costo */}
                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">
                            Costo (opcional)
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            value={data.costo}
                            onChange={e => setData('costo', e.target.value)}
                            className="w-full rounded-md border-gray-300"
                        />
                        {errors.costo && (
                            <p className="text-xs text-red-500 mt-1">{errors.costo}</p>
                        )}
                    </div>

                    {/* Animal */}
                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">
                            Animal (opcional)
                        </label>
                        <select
                            value={data.animal_id}
                            onChange={e => setData('animal_id', e.target.value)}
                            className="w-full rounded-md border-gray-300"
                        >
                            <option value="">Sin animal específico</option>
                            {animales.map(a => (
                                <option key={a.id} value={a.id}>
                                    {a.alias || a.arete || `Animal #${a.id}`}
                                </option>
                            ))}
                        </select>
                        {errors.animal_id && (
                            <p className="text-xs text-red-500 mt-1">{errors.animal_id}</p>
                        )}
                    </div>

                    {/* Lote */}
                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">
                            Lote (opcional)
                        </label>
                        <select
                            value={data.lote_id}
                            onChange={e => setData('lote_id', e.target.value)}
                            className="w-full rounded-md border-gray-300"
                        >
                            <option value="">Sin lote</option>
                            {lotes.map(l => (
                                <option key={l.id} value={l.id}>
                                    {l.nombre || `Lote #${l.id}`}
                                </option>
                            ))}
                        </select>
                        {errors.lote_id && (
                            <p className="text-xs text-red-500 mt-1">{errors.lote_id}</p>
                        )}
                    </div>

                    <div className="md:col-span-3 flex justify-end pt-2">
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-4 py-2 text-xs rounded-md bg-green-600 text-white hover:bg-green-700 disabled:opacity-50"
                        >
                            Guardar ración
                        </button>
                    </div>
                </form>
            </div>

            {/* BUSCADOR */}
            <div className="mb-4 w-full sm:w-64">
                <label className="block text-xs font-medium text-gray-600 mb-1">
                    Buscar por ración o consumo
                </label>
                <input
                    type="text"
                    className="w-full rounded-md border-gray-300 text-sm shadow-sm"
                    placeholder="Ración 1, 100 kg..."
                    value={search}
                    onChange={e => setSearch(e.target.value)}
                />
            </div>

            {/* TABLA */}
            <div className="bg-white shadow-sm rounded-lg border border-gray-100 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-2">Fecha</th>
                                <th className="px-4 py-2">Ración</th>
                                <th className="px-4 py-2">Consumo (kg)</th>
                                <th className="px-4 py-2">Costo</th>
                                <th className="px-4 py-2">Animal</th>
                                <th className="px-4 py-2">Lote</th>
                                <th className="px-4 py-2" />
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {filtered.map(r => (
                                <tr key={r.id}>
                                    <td className="px-4 py-2">{r.fecha}</td>
                                    <td className="px-4 py-2">
                                        {r.racion
                                            ? (r.racion.nombre)
                                            : r.racion_id
                                            ? racionesById[r.racion_id]
                                            : '—'}
                                    </td>
                                    <td className="px-4 py-2">{r.consumo_kg} kg</td>
                                    <td className="px-4 py-2">
                                        {r.costo != null ? `$${r.costo}` : '—'}
                                    </td>
                                    <td className="px-4 py-2">
                                        {r.animal
                                            ? (r.animal.alias || r.animal.arete)
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
                                    <td className="px-4 py-2 text-right text-xs">
                                        <button
                                            onClick={() => handleDelete(r.id)}
                                            className="text-red-600 hover:underline"
                                        >
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                            ))}

                            {filtered.length === 0 && (
                                <tr>
                                    <td
                                        colSpan="7"
                                        className="px-4 py-6 text-center text-sm text-gray-500"
                                    >
                                        No hay raciones registradas aún.
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
