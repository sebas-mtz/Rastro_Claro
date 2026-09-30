import { Link, useForm } from '@inertiajs/react';
import {
    BadgeDollarSign,
    Beef,
    Check,
    ChevronRight,
    CircleUserRound,
    Crown,
    LoaderCircle,
    MapPin,
    Moon,
    Scale,
    Settings2,
    Sun,
    WalletCards,
    X,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { DEFAULT_PREFERENCES, usePreferences } from '@/Contexts/PreferencesContext';

const tabs = [
    { id: 'account', label: 'Cuenta', icon: CircleUserRound },
    { id: 'preferences', label: 'Preferencias', icon: Settings2 },
    { id: 'operation', label: 'Operación', icon: Beef },
    { id: 'finances', label: 'Finanzas', icon: BadgeDollarSign },
    { id: 'plan', label: 'Cambiar plan', icon: Crown },
];

const currencies = [
    { value: 'MXN', label: 'Peso mexicano (MXN)' },
    { value: 'USD', label: 'Dólar estadounidense (USD)' },
    { value: 'EUR', label: 'Euro (EUR)' },
    { value: 'COP', label: 'Peso colombiano (COP)' },
    { value: 'ARS', label: 'Peso argentino (ARS)' },
];

function Field({ label, hint, error, children }) {
    return (
        <label className="block">
            <span className="text-sm font-semibold text-slate-700 dark:text-slate-200">{label}</span>
            {hint && <span className="ml-2 text-xs text-slate-400">{hint}</span>}
            <div className="mt-1.5">{children}</div>
            {error && <p className="mt-1 text-xs font-medium text-red-600">{error}</p>}
        </label>
    );
}

const inputClass = 'w-full rounded-xl border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:ring-blue-950 dark:disabled:bg-slate-800';

export default function SettingsModal({ isOpen, onClose, user, initialTab = 'account' }) {
    const [activeTab, setActiveTab] = useState(initialTab);
    const { setPreferences } = usePreferences();
    const initialSettings = useMemo(() => ({
        ...DEFAULT_PREFERENCES,
        ...(user?.settings ?? {}),
    }), [user?.settings]);

    const { data, setData, patch, errors, processing, reset, clearErrors } = useForm({
        name: user?.name ?? '',
        ...initialSettings,
    });

    useEffect(() => {
        if (!isOpen) return;
        setActiveTab(initialTab);
        clearErrors();
    }, [isOpen, initialTab]);

    useEffect(() => {
        if (!isOpen) return undefined;

        const closeOnEscape = (event) => {
            if (event.key === 'Escape') handleClose();
        };

        document.addEventListener('keydown', closeOnEscape);
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', closeOnEscape);
            document.body.style.overflow = '';
        };
    }, [isOpen, initialSettings]);

    if (!isOpen) return null;

    const handleClose = () => {
        setPreferences(initialSettings);
        reset();
        clearErrors();
        onClose();
    };

    const changeTheme = (theme) => {
        setData('theme', theme);
        setPreferences({ theme });
    };

    const submit = (event) => {
        event.preventDefault();
        patch(route('settings.update'), {
            preserveScroll: true,
            onSuccess: () => {
                setPreferences({
                    location: data.location,
                    weight_unit: data.weight_unit,
                    currency: data.currency,
                    theme: data.theme,
                    date_format: data.date_format,
                    animal_age_format: data.animal_age_format,
                    gestation_days: Number(data.gestation_days),
                    monthly_financial_goal: Number(data.monthly_financial_goal || 0),
                    inventory_capacity_kg: Number(data.inventory_capacity_kg),
                    daily_feed_kg: Number(data.daily_feed_kg),
                });
                onClose();
            },
        });
    };

    return (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-6" role="dialog" aria-modal="true" aria-label="Configuración">
            <button
                type="button"
                className="absolute inset-0 cursor-default bg-slate-950/55 backdrop-blur-[2px]"
                onClick={handleClose}
                aria-label="Cerrar configuración"
            />

            <form
                onSubmit={submit}
                className="relative flex max-h-[92vh] w-full max-w-4xl overflow-hidden rounded-3xl border border-white/40 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900"
            >
                <aside className="hidden w-56 flex-none border-r border-slate-100 bg-slate-50/80 p-4 sm:block dark:border-slate-800 dark:bg-slate-950/60">
                    <div className="mb-5 px-2 pt-2">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-200 dark:shadow-none">
                            <Settings2 size={20} />
                        </div>
                        <h2 className="mt-3 text-lg font-bold text-slate-900 dark:text-white">Configuración</h2>
                        <p className="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">Personaliza tu cuenta y la operación del rancho.</p>
                    </div>

                    <nav className="space-y-1">
                        {tabs.map(({ id, label, icon: Icon }) => (
                            <button
                                key={id}
                                type="button"
                                onClick={() => setActiveTab(id)}
                                className={`flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold transition ${
                                    activeTab === id
                                        ? 'bg-blue-600 text-white shadow-md shadow-blue-100 dark:shadow-none'
                                        : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700 dark:text-slate-300 dark:hover:bg-blue-950/60 dark:hover:text-blue-300'
                                }`}
                            >
                                <Icon size={17} />
                                <span>{label}</span>
                                <ChevronRight size={14} className="ml-auto opacity-60" />
                            </button>
                        ))}
                    </nav>
                </aside>

                <div className="flex min-w-0 flex-1 flex-col">
                    <header className="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-[0.16em] text-blue-600 dark:text-blue-400">Preferencias</p>
                            <h3 className="mt-0.5 text-xl font-bold text-slate-900 dark:text-white">
                                {tabs.find((tab) => tab.id === activeTab)?.label}
                            </h3>
                        </div>
                        <button
                            type="button"
                            onClick={handleClose}
                            className="flex h-10 w-10 items-center justify-center rounded-full text-slate-400 transition hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-slate-800"
                            aria-label="Cerrar"
                        >
                            <X size={20} />
                        </button>
                    </header>

                    <div className="border-b border-slate-100 px-4 py-2 sm:hidden dark:border-slate-800">
                        <select
                            value={activeTab}
                            onChange={(event) => setActiveTab(event.target.value)}
                            className={inputClass}
                            aria-label="Sección de configuración"
                        >
                            {tabs.map((tab) => <option key={tab.id} value={tab.id}>{tab.label}</option>)}
                        </select>
                    </div>

                    <div className="min-h-0 flex-1 overflow-y-auto p-5 sm:p-7">
                        {activeTab === 'account' && (
                            <section className="space-y-5">
                                <div>
                                    <h4 className="text-base font-bold text-slate-900 dark:text-white">Datos de la cuenta</h4>
                                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">El correo se muestra como referencia y no puede cambiarse desde aquí.</p>
                                </div>
                                <div className="grid gap-5 sm:grid-cols-2">
                                    <Field label="Nombre" error={errors.name}>
                                        <input className={inputClass} value={data.name} onChange={(event) => setData('name', event.target.value)} />
                                    </Field>
                                    <Field label="Correo electrónico">
                                        <input className={inputClass} value={user?.email ?? ''} disabled />
                                    </Field>
                                </div>
                                <Field label="Ubicación" hint="Ciudad, estado o región" error={errors.location}>
                                    <div className="relative">
                                        <MapPin size={17} className="pointer-events-none absolute left-3 top-3 text-slate-400" />
                                        <input
                                            className={`${inputClass} pl-10`}
                                            value={data.location}
                                            onChange={(event) => setData('location', event.target.value)}
                                            placeholder="Ej. Tepatitlán, Jalisco"
                                        />
                                    </div>
                                </Field>
                            </section>
                        )}

                        {activeTab === 'preferences' && (
                            <section className="space-y-6">
                                <div>
                                    <h4 className="text-base font-bold text-slate-900 dark:text-white">Formato y apariencia</h4>
                                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">Estas opciones se aplican a todas las pantallas de tu cuenta.</p>
                                </div>
                                <div className="grid gap-5 sm:grid-cols-2">
                                    <Field label="Unidad de peso" error={errors.weight_unit}>
                                        <select className={inputClass} value={data.weight_unit} onChange={(event) => setData('weight_unit', event.target.value)}>
                                            <option value="kg">Kilogramos (kg)</option>
                                            <option value="lb">Libras (lb)</option>
                                        </select>
                                    </Field>
                                    <Field label="Moneda" error={errors.currency}>
                                        <select className={inputClass} value={data.currency} onChange={(event) => setData('currency', event.target.value)}>
                                            {currencies.map((currency) => <option key={currency.value} value={currency.value}>{currency.label}</option>)}
                                        </select>
                                    </Field>
                                    <Field label="Formato de fecha" error={errors.date_format}>
                                        <select className={inputClass} value={data.date_format} onChange={(event) => setData('date_format', event.target.value)}>
                                            <option value="numeric">Todo numérico (20/07/26)</option>
                                            <option value="named_month">Mes con nombre (09/Jun/25)</option>
                                        </select>
                                    </Field>
                                    <Field label="Edad de los animales" error={errors.animal_age_format}>
                                        <select className={inputClass} value={data.animal_age_format} onChange={(event) => setData('animal_age_format', event.target.value)}>
                                            <option value="words">Años y meses (2 años 6 meses)</option>
                                            <option value="decimal">Año.mes (2.6 años)</option>
                                        </select>
                                    </Field>
                                </div>

                                <Field label="Tema">
                                    <div className="grid grid-cols-2 gap-3">
                                        {[
                                            { value: 'light', label: 'Claro', icon: Sun },
                                            { value: 'dark', label: 'Oscuro', icon: Moon },
                                        ].map(({ value, label, icon: Icon }) => (
                                            <button
                                                key={value}
                                                type="button"
                                                onClick={() => changeTheme(value)}
                                                className={`flex items-center gap-3 rounded-2xl border p-4 text-left transition ${
                                                    data.theme === value
                                                        ? 'border-blue-500 bg-blue-50 text-blue-700 ring-2 ring-blue-100 dark:bg-blue-950/60 dark:text-blue-300 dark:ring-blue-950'
                                                        : 'border-slate-200 text-slate-600 hover:border-blue-300 hover:bg-blue-50/60 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800'
                                                }`}
                                            >
                                                <Icon size={20} />
                                                <span className="font-semibold">{label}</span>
                                                {data.theme === value && <Check size={16} className="ml-auto" />}
                                            </button>
                                        ))}
                                    </div>
                                </Field>
                            </section>
                        )}

                        {activeTab === 'operation' && (
                            <section className="space-y-5">
                                <div>
                                    <h4 className="text-base font-bold text-slate-900 dark:text-white">Parámetros operativos</h4>
                                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">Ajusta los promedios usados en alertas y proyecciones del tablero.</p>
                                </div>
                                <div className="grid gap-5 sm:grid-cols-2">
                                    <Field label="Gestación esperada" hint="días" error={errors.gestation_days}>
                                        <input type="number" min="250" max="320" className={inputClass} value={data.gestation_days} onChange={(event) => setData('gestation_days', event.target.value)} />
                                    </Field>
                                    <Field label="Capacidad de inventario" hint="kg" error={errors.inventory_capacity_kg}>
                                        <input type="number" min="1" step="0.01" className={inputClass} value={data.inventory_capacity_kg} onChange={(event) => setData('inventory_capacity_kg', event.target.value)} />
                                    </Field>
                                    <Field label="Consumo diario estimado" hint="kg por día" error={errors.daily_feed_kg}>
                                        <input type="number" min="0.01" step="0.01" className={inputClass} value={data.daily_feed_kg} onChange={(event) => setData('daily_feed_kg', event.target.value)} />
                                    </Field>
                                </div>
                                <div className="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-blue-800 dark:border-blue-900 dark:bg-blue-950/50 dark:text-blue-200">
                                    Los valores de capacidad y consumo mejoran la estimación de inventario disponible; el promedio de gestación se usa como referencia para las fechas esperadas.
                                </div>
                            </section>
                        )}

                        {activeTab === 'finances' && (
                            <section className="space-y-5">
                                <div>
                                    <h4 className="text-base font-bold text-slate-900 dark:text-white">Metas financieras</h4>
                                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">Define un ingreso mensual de referencia para comparar el desempeño real.</p>
                                </div>
                                <Field label="Meta mensual de ingresos" hint={data.currency} error={errors.monthly_financial_goal}>
                                    <div className="relative">
                                        <WalletCards size={17} className="pointer-events-none absolute left-3 top-3 text-slate-400" />
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            className={`${inputClass} pl-10`}
                                            value={data.monthly_financial_goal}
                                            onChange={(event) => setData('monthly_financial_goal', event.target.value)}
                                        />
                                    </div>
                                </Field>
                                <p className="text-xs leading-5 text-slate-500 dark:text-slate-400">
                                    La moneda elegida cambia automáticamente la presentación de los importes. No modifica los valores históricos ni realiza conversiones cambiarias.
                                </p>
                            </section>
                        )}

                        {activeTab === 'plan' && (
                            <section className="space-y-5">
                                <div>
                                    <h4 className="text-base font-bold text-slate-900 dark:text-white">Tu plan</h4>
                                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">Administra las funciones disponibles para tu cuenta.</p>
                                </div>
                                <div className="overflow-hidden rounded-3xl border border-blue-100 bg-gradient-to-br from-blue-600 to-indigo-700 p-6 text-white shadow-xl shadow-blue-100 dark:border-blue-900 dark:shadow-none">
                                    <div className="flex items-start justify-between gap-4">
                                        <div>
                                            <p className="text-xs font-semibold uppercase tracking-[0.18em] text-blue-100">Plan actual</p>
                                            <h5 className="mt-2 text-2xl font-bold">{user?.plan === 'premium' ? 'Premium' : 'Normal'}</h5>
                                        </div>
                                        <div className="rounded-2xl bg-white/15 p-3"><Crown size={24} /></div>
                                    </div>
                                    <p className="mt-5 max-w-lg text-sm leading-6 text-blue-50">
                                        {user?.plan === 'premium'
                                            ? 'Ya tienes acceso a predicciones y análisis avanzados.'
                                            : 'Actualiza a Premium para desbloquear predicciones y herramientas avanzadas.'}
                                    </p>
                                    {user?.plan !== 'premium' && (
                                        <Link
                                            href={route('planes.index')}
                                            className="mt-6 inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-blue-700 transition hover:bg-blue-50"
                                        >
                                            Ver opciones Premium
                                            <ChevronRight size={16} />
                                        </Link>
                                    )}
                                </div>
                            </section>
                        )}
                    </div>

                    {activeTab !== 'plan' && (
                        <footer className="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/60 px-5 py-4 dark:border-slate-800 dark:bg-slate-950/40">
                            <button type="button" onClick={handleClose} className="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-800">
                                Cancelar
                            </button>
                            <button type="submit" disabled={processing} className="inline-flex min-w-36 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60 dark:shadow-none">
                                {processing ? <LoaderCircle size={17} className="animate-spin" /> : <Check size={17} />}
                                Guardar cambios
                            </button>
                        </footer>
                    )}
                </div>
            </form>
        </div>
    );
}
