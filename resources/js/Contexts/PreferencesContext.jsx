import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';

export const DEFAULT_PREFERENCES = {
    location: '',
    weight_unit: 'kg',
    currency: 'MXN',
    theme: 'light',
    gestation_days: 283,
    monthly_financial_goal: 0,
    inventory_capacity_kg: 3000,
    daily_feed_kg: 200,
};

const PreferencesContext = createContext(null);

function applyTheme(theme) {
    const isDark = theme === 'dark';
    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
    window.localStorage.setItem('rastro-theme', theme);
}

export function PreferencesProvider({ initialPreferences = {}, children }) {
    const [preferences, setPreferencesState] = useState({
        ...DEFAULT_PREFERENCES,
        ...(initialPreferences ?? {}),
    });

    const setPreferences = useCallback((nextPreferences) => {
        setPreferencesState((current) => {
            const next = typeof nextPreferences === 'function'
                ? nextPreferences(current)
                : { ...current, ...nextPreferences };

            applyTheme(next.theme);
            return next;
        });
    }, []);

    useEffect(() => {
        applyTheme(preferences.theme);
    }, [preferences.theme]);

    const formatCurrency = useCallback((value, options = {}) => {
        const amount = Number(value ?? 0);
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: preferences.currency,
            minimumFractionDigits: options.minimumFractionDigits ?? 0,
            maximumFractionDigits: options.maximumFractionDigits ?? 2,
        }).format(Number.isFinite(amount) ? amount : 0);
    }, [preferences.currency]);

    const formatWeight = useCallback((value, options = {}) => {
        if (value === null || value === undefined || value === '') return options.empty ?? '—';

        const kilograms = Number(value);
        if (!Number.isFinite(kilograms)) return options.empty ?? '—';

        const converted = preferences.weight_unit === 'lb' ? kilograms * 2.2046226218 : kilograms;
        const digits = options.digits ?? 2;
        return `${converted.toLocaleString('es-MX', {
            minimumFractionDigits: options.minimumFractionDigits ?? 0,
            maximumFractionDigits: digits,
        })} ${preferences.weight_unit}`;
    }, [preferences.weight_unit]);

    const fromKilograms = useCallback((value) => {
        const kilograms = Number(value);
        if (!Number.isFinite(kilograms)) return value;
        return preferences.weight_unit === 'lb' ? kilograms * 2.2046226218 : kilograms;
    }, [preferences.weight_unit]);

    const toKilograms = useCallback((value) => {
        const displayedWeight = Number(value);
        if (!Number.isFinite(displayedWeight)) return value;
        return preferences.weight_unit === 'lb' ? displayedWeight / 2.2046226218 : displayedWeight;
    }, [preferences.weight_unit]);

    const value = useMemo(() => ({
        preferences,
        setPreferences,
        formatCurrency,
        formatWeight,
        fromKilograms,
        toKilograms,
        weightUnit: preferences.weight_unit,
        currency: preferences.currency,
    }), [preferences, setPreferences, formatCurrency, formatWeight, fromKilograms, toKilograms]);

    return (
        <PreferencesContext.Provider value={value}>
            {children}
        </PreferencesContext.Provider>
    );
}

export function usePreferences() {
    const context = useContext(PreferencesContext);
    if (!context) {
        throw new Error('usePreferences debe usarse dentro de PreferencesProvider.');
    }

    return context;
}
