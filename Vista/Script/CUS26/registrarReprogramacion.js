document.getElementById('btnRegistrarReprogramacion').addEventListener('click', function() {
    const data = {
        ID_Consolidacion: document.getElementById('ID_Consolidacion').value,
        Id_OrdenPedido: document.getElementById('idPedidoRep').value,
        Id_Cliente: document.getElementById('idClienteRep').value,
        FechaConsolidacion: document.getElementById('fechaConsolRep').value
    };

    console.log(data); 

    fetch('http://localhost/mundo-patitas/Vista/Ajax/CUS26/registrarReprogramacion.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            ID_Consolidacion: document.getElementById('ID_Consolidacion').value,
            Id_OrdenPedido: document.getElementById('Id_OrdenPedido').value,
            Id_Cliente: document.getElementById('Id_Cliente').value
        })
    })


    .then(res => res.json())
    .then(resp => {
        if(resp.success) {
            alert('Reprogramación registrada correctamente');
            listarGestion();
            listarPedidos();
            listarOSE();
        }else {
            alert('Error al registrar reprogramación');
        }
    });
});

// Capturar todos los radio buttons
document.querySelectorAll('.radio-seleccionar').forEach(radio => {
    radio.addEventListener('click', function() {
        const fila = this.closest('tr');
        document.getElementById('idConsolidacionRep').value = fila.dataset.id;
        document.getElementById('idPedidoRep').value = fila.dataset.idpedido;
        document.getElementById('idClienteRep').value = fila.dataset.idcliente;
        document.getElementById('nombreClienteRep').value = fila.dataset.nombrecliente;
        document.getElementById('fechaConsolRep').value = fila.dataset.fecha;
        document.getElementById('estadoRep').value = 'No Entregado';
    });
});

