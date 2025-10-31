function calculateDv(rutBody) {
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

function cleanRut(rutCompleto) {
    return rutCompleto.replace(/[^0-9kK]/g, '').toUpperCase();
}

function formatRut(rutLimpio) {
    if (!rutLimpio) return "";
    
    let body = rutLimpio.slice(0, -1);
    let dv = rutLimpio.slice(-1);
    let formattedBody = body.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    
    return `${formattedBody}-${dv}`;
}

function validateRut(rutLimpio) {
    if (!rutLimpio || rutLimpio.length < 2) {
        return false;
    }
    const body = rutLimpio.slice(0, -1);
    const dvIngresado = rutLimpio.slice(-1);
    const dvCalculado = calculateDv(body);
    
    return dvIngresado === dvCalculado;
}