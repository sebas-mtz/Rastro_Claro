import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Repeat, HeartPulse, Sparkles, Check, TrendingUp } from 'lucide-react';

function fmtFecha(f) {
    return f ? new Date(f).toLocaleDateString('es-MX') : '—';
}

/**
 * Panel del ciclo de vida del ejemplar: etapa sugerida (que solo se guarda si
 * el usuario la confirma), historial de movimientos entre lotes y condición
 * corporal con su escala documentada.
 */
export default function CicloVidaPanel({
    animal,
    movimientosLote = [],
    condicionesCorporales = [],
    etapaSugerida = {},
    etapasVida = {},
    desarrolloCorporal = {},
}) {
    const [guardando, setGuardando] = useState(false);

    const etapaActual = animal.etapa_vida;
    const sugerida = etapaSugerida?.etapa;
    const haySugerenciaNueva = sugerida && sugerida !== etapaActual;

    const confirmarEtapa = () => {
        setGuardando(true);
        router.put(route('animales.update', animal.id), {
            arete: animal.arete,
            sexo: animal.sexo,
            etapa_vida: sugerida,
        }, {
            preserveScroll: true,
            onFinish: () => setGuardando(false),
        });
    };

    return (
        <div className="bg-white shadow-xl rounded-2xl p-6 border border-gray-200 space-y-6">
            <h2 className="text-lg font-semibold text-gray-700">Ciclo de vida</h2>

            {/* Estado en el rebaño */}
            <div className="flex flex-wrap items-center gap-3">
                <span className={`inline-flex px-3 py-1 rounded-full text-sm font-medium ${
                    animal.activo === false
                        ? 'bg-slate-200 text-slate-700'
                        : 'bg-emerald-100 text-emerald-800'
                }`}>
                    {animal.activo === false ? 'Fuera del rebaño' : 'Activo en el rebaño'}
                </span>
                {animal.fecha_baja && (
                    <span className="text-xs text-slate-500">Baja: {fmtFecha(animal.fecha_baja)}</span>
                )}
            </div>

            {/* Etapa de vida */}
            <div className="border border-slate-200 rounded-xl p-4">
                <div className="flex items-start justify-between gap-3 flex-wrap">
                    <div>
                        <p className="text-xs font-medium text-slate-500 flex items-center gap-1">
                            <Sparkles className="w-3.5 h-3.5" /> Etapa de vida
                        </p>
                        <p className="mt-1 text-sm font-semibold text-slate-800">
                            {etapasVida[etapaActual] || 'Sin definir'}
                        </p>
                        {animal.etapa_vida_confirmada_at && (
                            <p className="text-xs text-slate-400 mt-0.5">
                                Confirmada el {fmtFecha(animal.etapa_vida_confirmada_at)}
                            </p>
                        )}
                    </div>

                    {haySugerenciaNueva && (
                        <div className="text-right">
                            <p className="text-xs text-slate-500">Sugerencia del sistema</p>
                            <p className="text-sm font-medium text-emerald-700">{etapasVida[sugerida]}</p>
                            <button
                                onClick={confirmarEtapa}
                                disabled={guardando}
                                className="mt-1 inline-flex items-center gap-1 text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg disabled:opacity-50"
                            >
                                <Check className="w-3.5 h-3.5" />
                                {guardando ? 'Guardando...' : 'Confirmar etapa'}
                            </button>
                        </div>
                    )}
                </div>

                {etapaSugerida?.motivo && (
                    <p className="mt-2 text-xs text-slate-500">{etapaSugerida.motivo}</p>
                )}

                {!sugerida && (
                    <p className="mt-2 text-xs text-amber-700">
                        El sistema no cambia la etapa por su cuenta: requiere tu confirmación.
                    </p>
                )}
            </div>

            {/* Desarrollo corporal */}
            <div className="border border-slate-200 rounded-xl p-4">
                <p className="text-xs font-medium text-slate-500 flex items-center gap-1 mb-2">
                    <TrendingUp className="w-3.5 h-3.5" /> Desarrollo corporal
                </p>

                {desarrolloCorporal?.aviso && (
                    <p className="text-xs text-slate-500">{desarrolloCorporal.aviso}</p>
                )}

                {desarrolloCorporal?.ganancia_acumulada != null && (
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                        <div>
                            <p className="text-xs text-slate-400">Peso inicial</p>
                            <p className="font-medium">{desarrolloCorporal.peso_inicial} kg</p>
                        </div>
                        <div>
                            <p className="text-xs text-slate-400">Peso actual</p>
                            <p className="font-medium">{desarrolloCorporal.peso_actual} kg</p>
                        </div>
                        <div>
                            <p className="text-xs text-slate-400">Ganancia acumulada</p>
                            <p className="font-medium text-emerald-700">
                                +{desarrolloCorporal.ganancia_acumulada} kg
                            </p>
                        </div>
                        <div>
                            <p className="text-xs text-slate-400">Ganancia diaria</p>
                            <p className="font-medium">
                                {desarrolloCorporal.ganancia_diaria_promedio != null
                                    ? `${desarrolloCorporal.ganancia_diaria_promedio} kg/día`
                                    : '—'}
                            </p>
                        </div>
                    </div>
                )}
            </div>

            {/* Condición corporal */}
            <div className="border border-slate-200 rounded-xl p-4">
                <p className="text-xs font-medium text-slate-500 flex items-center gap-1 mb-2">
                    <HeartPulse className="w-3.5 h-3.5" /> Condición corporal
                </p>
                <p className="text-xs text-slate-400 mb-3">
                    Escala 1 a 5 — 1 muy delgada · 3 óptima · 5 obesa. Rango óptimo en ovinos: 2.5 a 3.5.
                </p>

                {condicionesCorporales.length === 0 ? (
                    <p className="text-sm text-slate-400">Sin registros de condición corporal.</p>
                ) : (
                    <ul className="space-y-2">
                        {condicionesCorporales.map((cc) => {
                            const valor = Number(cc.calificacion);
                            const color = valor < 2.5 ? 'text-amber-700'
                                : valor > 3.5 ? 'text-orange-700'
                                : 'text-emerald-700';

                            return (
                                <li key={cc.id} className="flex items-center justify-between text-sm">
                                    <span className="text-slate-500">{fmtFecha(cc.fecha)}</span>
                                    <span className={`font-semibold ${color}`}>{valor.toFixed(1)}</span>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </div>

            {/* Movimientos entre lotes */}
            <div className="border border-slate-200 rounded-xl p-4">
                <p className="text-xs font-medium text-slate-500 flex items-center gap-1 mb-3">
                    <Repeat className="w-3.5 h-3.5" /> Movimientos entre lotes
                </p>

                {movimientosLote.length === 0 ? (
                    <p className="text-sm text-slate-400">
                        Sin movimientos registrados. El historial se registra a partir del próximo cambio de lote.
                    </p>
                ) : (
                    <ul className="space-y-3">
                        {movimientosLote.map((mov) => (
                            <li key={mov.id} className="text-sm">
                                <div className="flex flex-wrap items-baseline justify-between gap-2">
                                    <span className="text-slate-800">
                                        {mov.lote_anterior?.nombre || 'Sin lote'}
                                        {' → '}
                                        <strong>{mov.lote_nuevo?.nombre || 'Sin lote'}</strong>
                                    </span>
                                    <span className="text-xs text-slate-400">{fmtFecha(mov.fecha)}</span>
                                </div>
                                {mov.motivo && <p className="text-xs text-slate-500">{mov.motivo}</p>}
                                {mov.responsable?.name && (
                                    <p className="text-xs text-slate-400">Por: {mov.responsable.name}</p>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </div>
    );
}
