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

function formatRut(rut) {
    rut = rut.replace(/[^0-9kK]/g, '').toUpperCase();
    if (rut.length < 2) return rut;
    
    let body = rut.slice(0, -1);
    let dv = rut.slice(-1);
    
    let formattedBody = body.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    
    return `${formattedBody}-${dv}`;
}