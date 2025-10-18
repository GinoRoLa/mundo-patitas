function cargarTabla() {
    fetch('../../Vista/Ajax/listarNoEntregados.php')

        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('bodyTabla');
            tbody.innerHTML = '';
            data.forEach(fila => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${fila.ID_Consolidacion}</td>
                    <td>${fila.Id_OrdenPedido}</td>
                    <td>${fila.Id_Cliente}</td>
                    <td>${fila.NombreCliente}</td>
                    <td>${fila.Fecha}</td>
                    <td>${fila.Estado}</td>
                    <td>${fila.Observaciones}</td>
                    <td><input type="checkbox" class="chkReprogramar"
                        data-idconsolidacion="${fila.ID_Consolidacion}"
                        data-idpedido="${fila.Id_OrdenPedido}"
                        data-idcliente="${fila.Id_Cliente}"
                        data-fecha="${fila.Fecha}"></td>`;
                tbody.appendChild(tr);
            });
        })
        .catch(err => console.error(err));
}

document.addEventListener('DOMContentLoaded', cargarTabla);

// Manejar selección única y formulario
document.addEventListener('change', function(e) {
    if(e.target.classList.contains('chkReprogramar')) {
        const checkboxes = document.querySelectorAll('.chkReprogramar');
        const form = document.getElementById('formReprogramacion');

        if(e.target.checked) {
            checkboxes.forEach(c => { if(c !== e.target) c.disabled = true; });
            form.hidden = false;
            document.getElementById('idPedidoRep').value = e.target.dataset.idpedido;
            document.getElementById('idclienteRep').value = e.target.dataset.idcliente;
            document.getElementById('fechaConsolRep').value = e.target.dataset.fecha;
        } else {
            checkboxes.forEach(c => c.disabled = false);
            form.hidden = true;
            document.getElementById('obsRep').value = '';
        }
    }
});

