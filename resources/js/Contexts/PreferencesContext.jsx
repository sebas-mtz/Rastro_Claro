import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';

export const DEFAULT_PREFERENCES = {
    location: '',
    weight_unit: 'kg',
    currency: 'MXN',
    theme: 'light',
    date_format: 'numeric',
    animal_age_format: 'words',
    gestation_days: 283,
    monthly_financial_goal: 0,
    inventory_capacity_kg: 3000,
    daily_feed_kg: 200,
};

const PreferencesContext = createContext(null);

const SHORT_MONTHS = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

function parseDate(value) {
    if (value instanceof Date) return new Date(value.getTime());

    if (typeof value === 'string') {
        const dateOnly = value.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (dateOnly) {
            return new Date(Number(dateOnly[1]), Number(dateOnly[2]) - 1, Number(dateOnly[3]));
        }
    }

    return new Date(value);
}

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

    const formatDate = useCallback((value, options = {}) => {
        if (value === null || value === undefined || value === '') return options.empty ?? 'N/D';

        const date = parseDate(value);
        if (Number.isNaN(date.getTime())) return options.empty ?? 'N/D';

        const day = String(date.getDate()).padStart(2, '0');
        const month = preferences.date_format === 'named_month'
            ? SHORT_MONTHS[date.getMonth()]
            : String(date.getMonth() + 1).padStart(2, '0');
        const year = String(date.getFullYear()).slice(-2);

        return `${day}/${month}/${year}`;
    }, [preferences.date_format]);

    const formatAnimalAge = useCallback((birthDate, options = {}) => {
        if (!birthDate) return options.empty ?? 'N/D';

        const birth = parseDate(birthDate);
        const reference = options.referenceDate ? parseDate(options.referenceDate) : new Date();
        if (Number.isNaN(birth.getTime()) || Number.isNaN(reference.getTime()) || birth > reference) {
            return options.empty ?? 'N/D';
        }

        let totalMonths = (reference.getFullYear() - birth.getFullYear()) * 12
            + reference.getMonth() - birth.getMonth();
        if (reference.getDate() < birth.getDate()) totalMonths -= 1;
        totalMonths = Math.max(0, totalMonths);

        const years = Math.floor(totalMonths / 12);
        const months = totalMonths % 12;

        if (preferences.animal_age_format === 'decimal') {
            return `${years}.${months} ${years === 1 ? 'año' : 'años'}`;
        }

        const parts = [];
        if (years > 0) parts.push(`${years} ${years === 1 ? 'año' : 'años'}`);
        if (months > 0 || parts.length === 0) parts.push(`${months} ${months === 1 ? 'mes' : 'meses'}`);
        return parts.join(' ');
    }, [preferences.animal_age_format]);

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
        formatDate,
        formatAnimalAge,
        fromKilograms,
        toKilograms,
        weightUnit: preferences.weight_unit,
        currency: preferences.currency,
    }), [preferences, setPreferences, formatCurrency, formatWeight, formatDate, formatAnimalAge, fromKilograms, toKilograms]);

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
