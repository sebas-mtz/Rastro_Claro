import React from 'react';
import { useForm, router } from '@inertiajs/react';
import { X } from 'lucide-react';

export default function ReproduccionModal({
  show,
  onClose,
  hembras = [],
  machos = [],
  lotes = [],
  tipos = [],
  estados = [],
}) {
  const { data, setData, post, processing, errors, reset } = useForm({
    hembra_id: '',
    macho_id: '',
    lote_id: '',
    tipo_evento: 'celo',
    fecha: new Date().toISOString().split('T')[0],
    estado: 'pendiente',
    metodo: '',
    semen_codigo: '',
    diagnostico: '',
    costo: '',
    numero_crias: '',
    peso_total_crias: '',
    complicaciones: false,
    detalle_complicaciones: '',
    observaciones: '',
  });

  if (!show) return null;

  const close = () => {
    onClose();
    reset();
  };

  const handleSubmit = (e) => {
    e.preventDefault();

    if (!data.hembra_id) {
      alert('Selecciona una hembra');
      return;
    }

    if (data.tipo_evento === 'parto') {
      const n = data.numero_crias === '' ? null : Number(data.numero_crias);
      if (n === null || Number.isNaN(n) || n < 0) {
        alert('Para parto, indica el número de crías (0 o más).');
        return;
      }
    }

    post(route('reproducciones.store'), {
      preserveScroll: true,
      onSuccess: () => {
        close();
        router.reload({ only: ['reproducciones', 'filters'] });
      },
    });
  };

  const isParto = data.tipo_evento === 'parto';
  const isInseminacion = data.tipo_evento === 'inseminacion';
  const isDiagnostico = data.tipo_evento === 'diagnostico_gestacion';

  return (
    <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div className="flex items-center justify-between px-6 py-4 border-b">
          <h3 className="text-lg font-bold text-gray-900">Registrar evento reproductivo</h3>
          <button onClick={close} className="text-gray-500 hover:text-gray-700" disabled={processing}>
            <X className="w-5 h-5" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-5">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {/* Hembra */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Hembra *</label>
              <select
                value={data.hembra_id ?? ''}
                onChange={(e) => setData('hembra_id', e.target.value)}
                className="w-full border rounded-lg px-3 py-2"
                required
              >
                <option value="">Seleccionar hembra...</option>
                {hembras.map((a) => (
                  <option key={a.id} value={a.id}>
                    {a.alias} ({a.especie}) - {a.lote_nombre}
                  </option>
                ))}
              </select>
              {errors.hembra_id && <p className="text-red-600 text-xs mt-1">{errors.hembra_id}</p>}
            </div>

            {/* Macho */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Macho (opcional)</label>
              <select
                value={data.macho_id ?? ''}
                onChange={(e) => setData('macho_id', e.target.value)}
                className="w-full border rounded-lg px-3 py-2"
              >
                <option value="">—</option>
                {machos.map((a) => (
                  <option key={a.id} value={a.id}>
                    {a.alias} ({a.especie}) - {a.lote_nombre}
                  </option>
                ))}
              </select>
              {errors.macho_id && <p className="text-red-600 text-xs mt-1">{errors.macho_id}</p>}
            </div>

            {/* Lote */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Lote (opcional)</label>
              <select
                value={data.lote_id ?? ''}
                onChange={(e) => setData('lote_id', e.target.value)}
                className="w-full border rounded-lg px-3 py-2"
              >
                <option value="">—</option>
                {lotes.map((l) => (
                  <option key={l.id} value={l.id}>
                    {l.nombre} ({l.especie}) - {l.animales_count} disponibles
                  </option>
                ))}
              </select>
              {errors.lote_id && <p className="text-red-600 text-xs mt-1">{errors.lote_id}</p>}
            </div>

            {/* Fecha */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
              <input
                type="date"
                value={data.fecha ?? ''}
                onChange={(e) => setData('fecha', e.target.value)}
                className="w-full border rounded-lg px-3 py-2"
                required
              />
              {errors.fecha && <p className="text-red-600 text-xs mt-1">{errors.fecha}</p>}
            </div>

            {/* Tipo */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
              <select
                value={data.tipo_evento ?? 'celo'}
                onChange={(e) => setData('tipo_evento', e.target.value)}
                className="w-full border rounded-lg px-3 py-2"
                required
              >
                {tipos.map((t) => (
                  <option key={t.value} value={t.value}>{t.label}</option>
                ))}
              </select>
              {errors.tipo_evento && <p className="text-red-600 text-xs mt-1">{errors.tipo_evento}</p>}
            </div>

            {/* Estado */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
              <select
                value={data.estado ?? 'pendiente'}
                onChange={(e) => setData('estado', e.target.value)}
                className="w-full border rounded-lg px-3 py-2"
                required
              >
                {estados.map((s) => (
                  <option key={s.value} value={s.value}>{s.label}</option>
                ))}
              </select>
              {errors.estado && <p className="text-red-600 text-xs mt-1">{errors.estado}</p>}
            </div>

            {/* Método */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Método (opcional)</label>
              <input
                type="text"
                value={data.metodo ?? ''}
                onChange={(e) => setData('metodo', e.target.value)}
                className="w-full border rounded-lg px-3 py-2"
                placeholder="Natural / IA / etc."
              />
              {errors.metodo && <p className="text-red-600 text-xs mt-1">{errors.metodo}</p>}
            </div>

            {/* Semen */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Código semen (opcional)</label>
              <input
                type="text"
                value={data.semen_codigo ?? ''}
                onChange={(e) => setData('semen_codigo', e.target.value)}
                className="w-full border rounded-lg px-3 py-2"
                placeholder="SEM-2025-0001"
                disabled={!isInseminacion}
              />
              {errors.semen_codigo && <p className="text-red-600 text-xs mt-1">{errors.semen_codigo}</p>}
            </div>

            {/* Diagnóstico */}
            <div className="md:col-span-2">
              <label className="block text-sm font-medium text-gray-700 mb-1">Diagnóstico (opcional)</label>
              <input
                type="text"
                value={data.diagnostico ?? ''}
                onChange={(e) => setData('diagnostico', e.target.value)}
                className="w-full border rounded-lg px-3 py-2"
                placeholder="Gestante / No gestante..."
                disabled={!isDiagnostico}
              />
              {errors.diagnostico && <p className="text-red-600 text-xs mt-1">{errors.diagnostico}</p>}
            </div>

            {/* Parto */}
            {isParto && (
              <>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Número de crías *</label>
                  <input
                    type="number"
                    min="0"
                    value={data.numero_crias ?? ''}
                    onChange={(e) => setData('numero_crias', e.target.value)}
                    className="w-full border rounded-lg px-3 py-2"
                    required
                  />
                  {errors.numero_crias && <p className="text-red-600 text-xs mt-1">{errors.numero_crias}</p>}
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Peso total crías (kg)</label>
                  <input
                    type="number"
                    min="0"
                    step="0.01"
                    value={data.peso_total_crias ?? ''}
                    onChange={(e) => setData('peso_total_crias', e.target.value)}
                    className="w-full border rounded-lg px-3 py-2"
                  />
                  {errors.peso_total_crias && <p className="text-red-600 text-xs mt-1">{errors.peso_total_crias}</p>}
                </div>

                <div className="md:col-span-2 flex items-center gap-2">
                  <input
                    type="checkbox"
                    checked={!!data.complicaciones}
                    onChange={(e) => setData('complicaciones', e.target.checked)}
                    className="w-4 h-4"
                  />
                  <span className="text-sm text-gray-700">Hubo complicaciones</span>
                </div>

                {data.complicaciones && (
                  <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Detalle complicaciones</label>
                    <textarea
                      value={data.detalle_complicaciones ?? ''}
                      onChange={(e) => setData('detalle_complicaciones', e.target.value)}
                      rows="2"
                      className="w-full border rounded-lg px-3 py-2"
                    />
                    {errors.detalle_complicaciones && <p className="text-red-600 text-xs mt-1">{errors.detalle_complicaciones}</p>}
                  </div>
                )}
              </>
            )}

            {/* Costo */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Costo (opcional)</label>
              <input
                type="number"
                min="0"
                step="0.01"
                value={data.costo ?? ''}
                onChange={(e) => setData('costo', e.target.value)}
                className="w-full border rounded-lg px-3 py-2"
                placeholder="0.00"
              />
              {errors.costo && <p className="text-red-600 text-xs mt-1">{errors.costo}</p>}
            </div>

            {/* Observaciones */}
            <div className="md:col-span-2">
              <label className="block text-sm font-medium text-gray-700 mb-1">Observaciones (opcional)</label>
              <textarea
                value={data.observaciones ?? ''}
                onChange={(e) => setData('observaciones', e.target.value)}
                rows="3"
                className="w-full border rounded-lg px-3 py-2"
                placeholder="Notas adicionales..."
              />
              {errors.observaciones && <p className="text-red-600 text-xs mt-1">{errors.observaciones}</p>}
            </div>
          </div>

          <div className="flex justify-end gap-2 pt-2 border-t">
            <button
              type="button"
              onClick={close}
              className="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"
              disabled={processing}
            >
              Cancelar
            </button>
            <button
              type="submit"
              className="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700"
              disabled={processing}
            >
              {processing ? 'Guardando...' : 'Guardar'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
