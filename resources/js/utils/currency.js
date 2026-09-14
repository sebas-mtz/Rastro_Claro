const CURRENCY_LOCALES = {
    MXN: 'es-MX',
    USD: 'en-US',
    EUR: 'es-ES',
    COP: 'es-CO',
    ARS: 'es-AR',
};

export function formatCurrency(valor, currency = 'MXN') {
    const numero = Number(valor ?? 0);
    const monto = Number.isFinite(numero) ? numero : 0;
    const locale = CURRENCY_LOCALES[currency] ?? 'es-MX';

    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(monto);
}

// Si otros archivos aún llaman formatMXN, evita romperlos:
export function formatMXN(valor) {
    return formatCurrency(valor, 'MXN');
}