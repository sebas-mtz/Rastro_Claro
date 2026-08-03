export function formatMXN(valor) {
    const numero = Number(valor ?? 0);
    const monto = Number.isFinite(numero) ? numero : 0;

    return `$${monto.toLocaleString('es-MX', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} MXN`;
}
