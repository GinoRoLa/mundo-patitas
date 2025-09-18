// ====== Inicialización (normaliza tipos) ======
let ordenesPedido = (window.ordenesPedido || []).map(p => ({
    ...p,
    Id_OrdenPedido: String(p.Id_OrdenPedido),
    DniCli: p.DniCli ?? "",
    Fecha: p.Fecha ?? "",
    Estado: p.Estado ?? "",
    Total: Number(p.Total) || 0
}));

// almacenará objetos: { id: "1001", DniCli, Fecha, Total }
let ordenesSeleccionadas = [];

// ====== Helpers ======
function rellenarFilas(tbodyId, minRows = 5) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    const table = tbody.closest("table");
    const colCount = table ? table.querySelectorAll("thead tr th").length : 1;

    // elimina placeholders viejos
    $(tbody).find("tr.placeholder").remove();

    const currentRows = tbody.querySelectorAll("tr").length;
    for (let i = currentRows; i < minRows; i++) {
        const tr = document.createElement("tr");
        tr.classList.add("placeholder");
        tr.innerHTML = `<td colspan="${colCount}">&nbsp;</td>`;
        tbody.appendChild(tr);
    }
}

function hayFiltroActivo() {
    const filtro = $("input[name='filtroOrden']:checked").val();
    const valor = $("input[name='filtroOrdenPedido']").val();
    return (filtro && String(valor).trim() !== "");
}

// devuelve { lista, isFiltered }
function getFilteredList() {
    const filtro = $("input[name='filtroOrden']:checked").val();
    const valorRaw = $("input[name='filtroOrdenPedido']").val();
    const valor = String(valorRaw ?? "").trim();

    let lista = ordenesPedido.slice(); // copia

    // aplica filtro si hay
    if (filtro && valor !== "") {
        if (filtro === "codigoOrdenPedido") {
            lista = lista.filter(p => p.Id_OrdenPedido == valor);
        } else if (filtro === "dniCliente") {
            lista = lista.filter(p => String(p.DniCli) == valor);
        }
    }

    // excluye seleccionados (compara como strings)
    lista = lista.filter(p => !ordenesSeleccionadas.some(s => s.id === String(p.Id_OrdenPedido)));

    return { lista, isFiltered: (filtro && valor !== "") };
}

// ====== Render tabla principal (tabla 1 / #table-body3) ======
function renderTablaPrincipal(obj) {
    const tbody = $("#table-body3");
    tbody.empty();

    const lista = obj.lista;
    const isFiltered = obj.isFiltered;

    if (lista.length > 0) {
        lista.forEach(p => {
            const row = `
        <tr data-id="${p.Id_OrdenPedido}">
          <td>${String(p.Id_OrdenPedido).padStart(3, "0")}</td>
          <td>${p.DniCli}</td>
          <td>${p.Fecha}</td>
          <td>${p.Estado}</td>
          <td>${p.Total.toFixed(2)}</td>
          <td>
            <label class="checkbox-container">
              <input type="checkbox"
                     class="checkbox-add-order"
                     data-ordenpedido="${p.Id_OrdenPedido}"
                     data-dnicli="${p.DniCli}"
                     data-fecha="${p.Fecha}"
                     data-total="${p.Total}">
              <span class="checkmark"></span>
            </label>
          </td>
        </tr>
      `;
            tbody.append(row);
        });
    } else {
        // Mostrar "No se encontraron..." solo si es resultado de un filtro activo
        if (isFiltered) {
            tbody.append(`
        <tr>
          <td colspan="6">No se encontraron pedidos.</td>
        </tr>
      `);
        }
    }

    rellenarFilas("table-body3");
}

// ====== Render tabla seleccionados (tabla 2 / #table-body2) ======
function renderTablaSeleccionados() {
    const tbody = $("#table-body2");
    tbody.empty();

    if (ordenesSeleccionadas.length > 0) {
        ordenesSeleccionadas.forEach(s => {
            const row = `
        <tr data-id="${s.id}">
          <td>${String(s.id).padStart(3, "0")}</td>
          <td>${s.DniCli}</td>
          <td>${s.Fecha}</td>
          <td>${Number(s.Total).toFixed(2)}</td>
          <td>
            <label class="checkbox-container">
              <input type="checkbox"
                     class="checkbox-remove-order"
                     data-id="${s.id}" checked>
              <span class="checkmark"></span>
            </label>
          </td>
        </tr>
      `;
            tbody.append(row);
        });
    }

    rellenarFilas("table-body2");

    // ====== Manejo del botón ======
    const btn = $("#generar-orden");
    if (ordenesSeleccionadas.length > 0) {
        btn.removeClass("style-button-disabled")
           .addClass("style-button")
           .prop("disabled", false);
    } else {
        btn.removeClass("style-button")
           .addClass("style-button-disabled")
           .prop("disabled", true);
    }
}


// ====== Filtrado launcher ======
function filtrarOrdenesYRender() {
    const obj = getFilteredList();
    renderTablaPrincipal(obj);
}

// ====== Eventos delegados ======

// Seleccionar desde tabla principal
$(document).on("change", ".checkbox-add-order", function () {
    const id = String($(this).data("ordenpedido"));
    const pedido = ordenesPedido.find(p => String(p.Id_OrdenPedido) === id);
    if (!pedido) return;

    // Validación: no permitir diferentes clientes
    if (ordenesSeleccionadas.length > 0) {
        const clienteActual = ordenesSeleccionadas[0].DniCli; // tomamos el primero
        if (pedido.DniCli !== clienteActual) {
            alert("No puede agregar órdenes de diferentes clientes.");
            $(this).prop("checked", false); // desmarcar el checkbox
            return;
        }
    }

    // Agregar si no existe ya
    if (!ordenesSeleccionadas.some(s => s.id === id)) {
        ordenesSeleccionadas.push({
            id,
            DniCli: pedido.DniCli,
            Fecha: pedido.Fecha,
            Total: pedido.Total
        });
    }

    renderTablaSeleccionados();
    filtrarOrdenesYRender();
});


// Deseleccionar (quitar) desde tabla seleccionados
$(document).on("change", ".checkbox-remove-order", function () {
    const id = String($(this).data("id"));
    ordenesSeleccionadas = ordenesSeleccionadas.filter(s => s.id !== id);

    renderTablaSeleccionados();
    filtrarOrdenesYRender();
});

// Botón filtrar
$(document).on("click", ".button-search-orden", function (e) {
    e.preventDefault();
    const filtro = $("input[name='filtroOrden']:checked").val();
    const valorRaw = $("input[name='filtroOrdenPedido']").val();
    const valor = String(valorRaw ?? "").trim();

    // Validación: filtro no seleccionado
    if (!filtro) {
        alert("Seleccione un filtro.");
        return;
    }

    // Validación: input vacío cuando hay filtro
    if (valor === "") {
        alert("Ingrese un valor para filtrar.");
        return;
    }

    // Validación: input debe ser numérico
    if (!/^\d+$/.test(valor)) {
        alert("Ingrese un valor numérico válido.");
        return;
    }

    // Si pasa las validaciones, renderizar normalmente
    filtrarOrdenesYRender();

});

// Botón limpiar filtro
$(document).on("click", ".button-clear-filters", function (e) {
    e.preventDefault();
    $("input[name='filtroOrdenPedido']").val("");   // limpiar input
    $("input[name='filtroOrden']").prop("checked", false); // desmarcar radios
    filtrarOrdenesYRender(); // render sin filtro
});

// Soporte: filtrar también si presionan Enter en el input
$(document).on("keypress", "input[name='filtroOrdenPedido']", function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        filtrarOrdenesYRender();
    }
});

// Render inicial al cargar la página
$(document).ready(function () {
    filtrarOrdenesYRender();
    renderTablaSeleccionados();
});

window.addEventListener("beforeunload", function (e) {
    if (ordenesSeleccionadas.length > 0) {
        e.preventDefault();
        e.returnValue = ""; 
        // ⚠️ Los navegadores ignoran el mensaje personalizado
        // y muestran su propio confirm genérico
    }
});