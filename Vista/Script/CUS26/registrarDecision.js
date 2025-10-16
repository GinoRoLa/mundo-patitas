function registrarDecision() {
    const idConsolidacion = document.getElementById('idConsolidacion').value;
    const idOrdenPedido = document.getElementById('idOrdenPedido').value;
    const decision = document.getElementById('decision').value;
    const motivo = document.getElementById('motivo').value;
    const casoEspecial = document.getElementById('casoEspecial').checked ? 1 : 0;
    const fechaReprogramacion = document.getElementById('fechaReprogramacion').value;
    const observaciones = document.getElementById('observaciones').value;

    if (!decision) {
        alert('Seleccione una decisión antes de continuar.');
        return;
    }

    // Validación básica
    if (decision === 'Reprogramación' && !fechaReprogramacion) {
        alert('Debe ingresar la fecha de reprogramación.');
        return;
    }

    const datos = new FormData();
    datos.append('idConsolidacion', idConsolidacion);
    datos.append('idOrdenPedido', idOrdenPedido);
    datos.append('decision', decision);
    datos.append('motivo', motivo);
    datos.append('casoEspecial', casoEspecial);
    datos.append('fechaReprogramacion', fechaReprogramacion);
    datos.append('observaciones', observaciones);

    fetch('../../Controlador/CUS26/registrarDecision.php', {
        method: 'POST',
        body: datos
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Decisión registrada correctamente.');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error en la solicitud:', error);
        alert('Ocurrió un error al registrar la decisión.');
    });
}
