function rellenarFilasVacias(tbodyId, minRows = 5) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;

    const table = tbody.closest("table");
    const colCount = table ? table.querySelectorAll("thead tr th").length : 1;
    const currentRows = tbody.rows.length;

    for (let i = currentRows; i < minRows; i++) {
        const tr = document.createElement("tr");
        tr.innerHTML = `<td colspan="${colCount}">&nbsp;</td>`;
        tbody.appendChild(tr);
    }
}

$(function () {
    $('#buscarPreorden').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            type: 'POST',
            url: '../Ajax/buscarOrdenPedidoAjax.php',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (res) {
                if (res.success) {

                    $('#codigoOrden').val(res.orden.codigo);
                    $('#totalOrden').val(res.orden.total);
                    $('#fechaOrden').val(res.orden.fecha);
                    $('#dniCliente').val(res.orden.dniCliente);

                    const tbody = $('#table-body3');
                    tbody.empty();

                    if (res.productos.length > 0) {
                        res.productos.forEach(p => {
                            tbody.append(`
                                <tr>
                                    <td>${p.codigo}</td>
                                    <td>${p.descripcion}</td>
                                    <td>${p.precio}</td>
                                    <td>${p.cantidad}</td>
                                </tr>
                            `);
                        });
                    } else {
                        tbody.append(`
                            <tr>
                                <td colspan="4" style="text-align:center; color:#888;">
                                    Esta orden no tiene productos
                                </td>
                            </tr>
                        `);
                    }

                    rellenarFilasVacias("table-body3", 5);

                } else {
                    alert(res.message);
                    $('#codigoOrden, #totalOrden, #fechaOrden, #dniCliente').val('');
                    $('#table-body3').empty();
                    rellenarFilasVacias("table-body3", 5);
                }
            },
            error: function (xhr, status, error) {
                console.log("AJAX error:", status, error);
                console.log(xhr.responseText);
                alert('Error en la solicitud AJAX.');
            }
        });
    });
});
