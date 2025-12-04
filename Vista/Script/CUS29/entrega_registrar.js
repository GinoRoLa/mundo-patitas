// Vista/Script/CUS29/entrega_registrar.js
async function registrarEntrega({ idPedido, idDetalleAsignacion, idNotaCaja, idTrabajador, fechaEntrega, horaEntrega, estadoEntrega, observacion }) {
    try {
        const res = await fetch('/mundo-patitas/Vista/Ajax/CUS29/registrarEntrega.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ idPedido, idDetalleAsignacion, idNotaCaja, idTrabajador, fechaEntrega, horaEntrega, estadoEntrega, observacion })
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.error || 'Error al registrar entrega');
        return { success: true, idEntrega: json.idEntrega };
    } catch (err) {
        console.error('Error registrar entrega:', err);
        return { success: false, error: err.message };
    }
}
