// Vista/Script/CUS29/pago_registrar.js
async function registrarPagoProceso({ idEntrega, montoEsperado, montoRecibido, montoVuelto, denominacionesRecibidas = [], denominacionesVuelto = [] }) {
    try {
        // 1) Registrar cabecera de pago
        const resPago = await fetch('/mundo-patitas/Vista/Ajax/CUS29/registrarPago.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ idEntrega, montoEsperado, montoRecibido, montoVuelto })
        });
        const jsonPago = await resPago.json();
        if (!jsonPago.success) throw new Error(jsonPago.error || 'Error al registrar pago');

        const idPago = jsonPago.idPago;
        console.log('Pago registrado idPago=', idPago);

        // 2) Registrar denominaciones recibidas (array de {tipo, denominacion, cantidad})
        for (const d of denominacionesRecibidas) {
            const r = await fetch('/mundo-patitas/Vista/Ajax/CUS29/registrarDenominacionPago.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ idPago, tipo: d.tipo, denominacion: d.denominacion, cantidad: d.cantidad })
            });
            const jr = await r.json();
            if (!jr.success) console.warn('No se guardó denom recibida', d);
        }

        // 3) Registrar denominaciones de vuelto
        for (const v of denominacionesVuelto) {
            const r2 = await fetch('/mundo-patitas/Vista/Ajax/CUS29/registrarDenominacionVuelto.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ idPago, tipo: v.tipo, denominacion: v.denominacion, cantidad: v.cantidad })
            });
            const jr2 = await r2.json();
            if (!jr2.success) console.warn('No se guardó denom vuelto', v);
        }

        return { success: true, idPago };
    } catch (err) {
        console.error('Error proceso pago:', err);
        return { success: false, error: err.message };
    }
}
