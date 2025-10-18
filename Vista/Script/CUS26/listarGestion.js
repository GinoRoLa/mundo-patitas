// Listar gestión de reprogramados
function listarGestion() {
    fetch('ajax/CUS26/listarGestion.php')
    .then(resp => resp.json())
    .then(data => {
        const tbody = document.getElementById('bodyTablaGestion');
        tbody.innerHTML = '';
        data.forEach(fila => {
            tbody.innerHTML += `
                <tr>
                    <td>${fila.Id_Gestion}</td>
                    <td>${fila.Id_Consolidacion}</td>
                    <td>${fila.Id_OrdenPedido}</td>
                    <td>${fila.Id_Cliente}</td>
                    <td>${fila.NombreCliente}</td>
                    <td>${fila.FechaGestion}</td>
                    <td>${fila.Estado}</td>
                </tr>
            `;
        });
    });
}

// Listar pedidos que cambiaron a pagado
function listarPedidos() {
    fetch('ajax/CUS26/listarPedidos.php')
    .then(resp => resp.json())
    .then(data => {
        const tbody = document.getElementById('bodyTablaPedidos');
        tbody.innerHTML = '';
        data.forEach(fila => {
            tbody.innerHTML += `
                <tr>
                    <td>${fila.Id_OrdenPedido}</td>
                    <td>${fila.Id_Cliente}</td>
                    <td>${fila.Fecha}</td>
                    <td>${fila.Estado}</td>
                </tr>
            `;
        });
    });
}

// Listar ordenes de servicio de entrega que cambiaron a emitido
function listarOSE() {
    fetch('ajax/CUS26/listarOSE.php')
    .then(resp => resp.json())
    .then(data => {
        const tbody = document.getElementById('bodyTablaOSE');
        tbody.innerHTML = '';
        data.forEach(fila => {
            tbody.innerHTML += `
                <tr>
                    <td>${fila.Id_OSE}</td>
                    <td>${fila.Id_OrdenPedido}</td>
                    <td>${fila.Estado}</td>
                </tr>
            `;
        });
    });
}

// Llamadas al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    listarGestion();
    listarPedidos();
    listarOSE();
});


