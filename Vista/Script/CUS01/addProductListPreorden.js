let productosSeleccionados = [];

$(document).ready(function () {
    const minRows = 5;
    const tbody2 = document.getElementById("table-body2");
    const generarPreordenBtn = $("#generar-preorden");
    
    $(".button-add-product").on("click", function () {
        const selected = $("input[name='productoSeleccionado']:checked");
        if (selected.length === 0) {
            alert("Seleccione un producto primero.");
            return;
        }

        const codigo = selected.attr("codigo-producto");
        const nombre = selected.attr("nombre-producto");
        const precio = parseFloat(selected.attr("precio-producto"));
        const cantidad = parseInt($(".quantity input[type='number']").val()) || 1;

        stockDisponible[codigo] -= cantidad;
        if (stockDisponible[codigo] < 0)
            stockDisponible[codigo] = 0;

        renderTabla(productosOriginales);

        let existente = false;
        $("#table-body2 tr").each(function () {
            const codigoExistente = $(this).find("td:eq(0)").text();
            if (codigoExistente === codigo) {
                let cantidadActual = parseInt($(this).find("td:eq(3)").text());
                cantidadActual += cantidad;
                $(this).find("td:eq(3)").text(cantidadActual);
                existente = true;
            }
        });

        if (!existente) {
            const nuevaFila = `
                <tr>
                    <td>${codigo}</td>
                    <td>${nombre}</td>
                    <td>${precio.toFixed(2)}</td>
                    <td>${cantidad}</td>
                    <td><button class="style-button btn-eliminar">❌</button></td>
                </tr>
            `;
            $("#table-body2").append(nuevaFila);
        }
        
        const productoEnArray = productosSeleccionados.find(p => p.codigo === codigo);
        if (productoEnArray) {
            productoEnArray.cantidad += cantidad;
        } else {
            productosSeleccionados.push({ codigo, cantidad, precio });
        }
        
        actualizarTablaOrdenada();
        actualizarBotonPreorden();
    });

    $(document).on("click", ".btn-eliminar", function () {
        const fila = $(this).closest("tr");
        const codigo = fila.find("td:eq(0)").text();
        const cantidad = parseInt(fila.find("td:eq(3)").text()) || 0;

        stockDisponible[codigo] += cantidad;

        fila.remove();
        productosSeleccionados = productosSeleccionados.filter(p => p.codigo !== codigo);
        actualizarTablaOrdenada();
        renderTabla(productosOriginales);
        actualizarBotonPreorden();
    });

    function actualizarTablaOrdenada() {
        const filasReales = [];

        $("#table-body2 tr").each(function () {
            const celdas = $(this).find("td");
            if (celdas.length === 5 && celdas.eq(0).text().trim() !== " ") {
                filasReales.push($(this).prop("outerHTML"));
            }
        });

        $("#table-body2").empty();

        filasReales.forEach(fila => $("#table-body2").append(fila));

        let filasActuales = $("#table-body2 tr").length;
        for (let i = filasActuales; i < minRows; i++) {
            const tr = document.createElement("tr");
            tr.innerHTML = `<td colspan="5">&nbsp;</td>`;
            tbody2.appendChild(tr);
        }
        actualizarTotal();
    }

    function actualizarTotal() {
        let total = 0;
        $("#table-body2 tr").each(function () {
            const precio = parseFloat($(this).find("td:eq(2)").text());
            const cantidad = parseInt($(this).find("td:eq(3)").text());
            if (!isNaN(precio) && !isNaN(cantidad)) {
                total += precio * cantidad;
            }
        });
        $("tfoot td:last").text("S/. " + total.toFixed(2));
    }

    function actualizarBotonPreorden() {
        const productosEnCarrito = $("#table-body2 tr").filter(function () {
            const celdas = $(this).find("td");
            return celdas.length === 5 && celdas.eq(0).text().trim() !== " ";
        }).length;

        if (productosEnCarrito > 0) {
            generarPreordenBtn.removeClass("style-button-disabled").addClass("style-button");
            generarPreordenBtn.prop("disabled", false);
        } else {
            generarPreordenBtn.removeClass("style-button").addClass("style-button-disabled");
            generarPreordenBtn.prop("disabled", true);
        }
    }

    actualizarBotonPreorden();
});
