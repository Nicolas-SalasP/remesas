export function calculateDv(rutBody) {
    let sum = 0;
    let multiplier = 2;
    for (let i = rutBody.length - 1; i >= 0; i--) {
        sum += parseInt(rutBody.charAt(i), 10) * multiplier;
        multiplier = multiplier === 7 ? 2 : multiplier + 1;
    }
    const rest = 11 - (sum % 11);
    if (rest === 11) return '0';
    if (rest === 10) return 'K';
    return rest.toString();
}

/**
 * Formatea un RUT a un formato visual (ej. 11.111.111-K).
 * @param {string} rut - El RUT sin formato.
 * @returns {string} - El RUT con formato.
 */
export function formatRut(rut) {
    rut = rut.replace(/[^0-9kK]/g, '').toUpperCase();
    if (rut.length < 2) return rut;
    
    let body = rut.slice(0, -1);
    let dv = rut.slice(-1);
    
    // ===================================================================
    // CAMBIO: Reemplazamos Intl.NumberFormat por una Expresión Regular.
    // Esta es la forma correcta y estándar de añadir los puntos al RUT.
    let formattedBody = body.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    // ===================================================================
    
    return `${formattedBody}-${dv}`;
}