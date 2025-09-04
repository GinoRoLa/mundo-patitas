function rellenarFilasVacias(tbodyId, minRows = 5) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody)
        return;

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
            url: '../Ajax/CUS04/buscarOrdenPedido.php',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (res) {
                if (res.success) {

                    $('#codigoOrden').val(res.orden.Id_OrdenPedido);
                    $('#totalOrden').val(res.orden.Total);
                    $('#fechaOrden').val(res.orden.Fecha);
                    $('#dniCliente').val(res.orden.DniCli);

                    const tbody = $('#table-body3');
                    tbody.empty();

                    if (res.productos.length > 0) {
                        res.productos.forEach(p => {
                            tbody.append(`
                                <tr>
                                    <td>${p.Id_Producto}</td>
                                    <td>${p.Descripcion}</td>
                                    <td>${p.PrecioUnitario}</td>
                                    <td>${p.Cantidad}</td>
                                </tr>`
                            );
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
                    const btn = $(".generar-preorden-button");
                    btn.prop("disabled", false);
                    btn.removeClass("style-button-disabled").addClass("style-button");
                    const form = $("#register-orden");
                    let hiddenInput = form.find("input[name='codigoOrdenHidden']");
                    if (hiddenInput.length === 0) {
                        form.append(`<input type="hidden" name="codigoOrdenHidden" value="${res.orden.Id_OrdenPedido}">`);
                    } else {
                        hiddenInput.val(res.orden.Id_OrdenPedido);
                    }
                    
                } else {
                    alert(res.message);
                    $('#codigoOrden, #totalOrden, #fechaOrden, #dniCliente').val('');
                    $('#table-body3').empty();
                    rellenarFilasVacias("table-body3", 5);
                    const btn = $(".generar-preorden-button");
                    btn.prop("disabled", true);
                    btn.removeClass("style-button").addClass("style-button-disabled");
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
