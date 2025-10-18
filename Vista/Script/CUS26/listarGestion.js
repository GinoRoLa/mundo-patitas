// listarGestion.js
function listarGestion() {
    fetch('../../vista/ajax/CUS26/listarGestion.php')
    .then(r => r.json())
    .then(data => {
        const tbody = document.getElementById('bodyTablaGestion');
        tbody.innerHTML = '';
        data.forEach(f => {
            tbody.innerHTML += `<tr>
                <td>${f.Id_Gestion}</td>
                <td>${f.Id_Consolidacion}</td>
                <td>${f.Id_OrdenPedido}</td>
                <td>${f.Id_Cliente}</td>
                <td>${f.Decision}</td>
                <td>${f.Observaciones}</td>
                <td>${f.FechaGestion}</td>
                <td>${f.Estado}</td>
            </tr>`;
        });
    })
    .catch(err => console.error('Error listarGestion:', err));
}

function listarPedidos() {
    fetch('../../vista/ajax/CUS26/listarPedidos.php')
    .then(r => r.json())
    .then(data => {
        const tbody = document.getElementById('bodyTablaPedidos');
        tbody.innerHTML = '';
        data.forEach(f => {
            tbody.innerHTML += `<tr>
                <td>${f.Id_OrdenPedido}</td>
                <td>${f.Id_Cliente}</td>
                <td>${f.Fecha}</td>
                <td>${f.Estado}</td>
            </tr>`;
        });
    })
    .catch(err => console.error('Error listarPedidos:', err));
}

function listarOSE() {
    fetch('../../vista/ajax/CUS26/listarOSE.php')
    .then(r => r.json())
    .then(data => {
        const tbody = document.getElementById('bodyTablaOSE');
        tbody.innerHTML = '';
        data.forEach(f => {
            tbody.innerHTML += `<tr>
                <td>${f.Id_OrdenServicio || f.Id_OSE || ''}</td>
                <td>${f.Id_OrdenPedido}</td>
                <td>${f.Fecha || ''}</td>
                <td>${f.Estado}</td>
            </tr>`;
        });
    })
    .catch(err => console.error('Error listarOSE:', err));
}

function listarIngresoAlmacen() {
    fetch('../../vista/ajax/CUS26/listarIngresoAlmacen.php')
    .then(r => r.json())
    .then(data => {
        const tbody = document.getElementById('bodyTablaIngresoAlmacen');
        if (!tbody) return;
        tbody.innerHTML = '';
        data.forEach(f => {
            tbody.innerHTML += `<tr>
                <td>${f.Id_OrdenIngreso || ''}</td>
                <td>${f.Id_OrdenPedido}</td>
                <td>${f.FechaIngreso || ''}</td>
                <td>${f.Estado}</td>
            </tr>`;
        });
    })
    .catch(err => console.error('Error listarIngresoAlmacen:', err));
}

// Llamadas iniciales
document.addEventListener('DOMContentLoaded', () => {
    if (typeof listarGestion === 'function') listarGestion();
    if (typeof listarPedidos === 'function') listarPedidos();
    if (typeof listarOSE === 'function') listarOSE();
    if (typeof listarIngresoAlmacen === 'function') listarIngresoAlmacen();
});
