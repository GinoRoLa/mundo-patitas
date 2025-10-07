let oseOriginales = window.oseOriginales || [];
let oseDisponibles = [...oseOriginales]; // Copia inicial
let oseSeleccionadas = [];

// Función para renderizar tabla de OSE disponibles
window.renderOSE = function (lista) {
  const tbody = $("#table-body");
  tbody.empty();

  if (lista.length > 0) {
    lista.forEach(function (o) {
      let row = `
        <tr>
          <td>${String(o.Codigo_OSE).padStart(5, "0")}</td>
          <td>${String(o.Codigo_OP).padStart(5, "0")}</td>
          <td>${o.Distrito}</td>
          <td>${o.Zona}</td>
          <td>${parseFloat(o.Peso_Kg).toFixed(2)}</td>
          <td>${parseFloat(o.Volumen_m3).toFixed(2)}</td>
          <td>${o.Dias_Restantes}</td>
          <td>
            <label class="checkbox-container">
              <input type="checkbox" 
                class="chk-ose"
                value="${o.Codigo_OSE}"
                data-codigo-op="${o.Codigo_OP}">
              <span class="checkmark"></span>
            </label>
          </td>
        </tr>
      `;
      tbody.append(row);
    });
  } else {
    tbody.append(`
      <tr><td colspan="8">No se encontraron órdenes de servicio.</td></tr>
    `);
  }

  // Mantener al menos 5 filas visibles
  const minRows = 5;
  const currentRows = tbody.find("tr").length;
  if (currentRows < minRows) {
    for (let i = currentRows; i < minRows; i++) {
      tbody.append(`<tr><td colspan="8">&nbsp;</td></tr>`);
    }
  }
};

// Manejo de selección/deselección
$(document).on("change", ".chk-ose", function () {
  const id = parseInt($(this).val());
  const seleccionado = $(this).is(":checked");

  if (seleccionado) {
    // Mover de disponibles a seleccionadas
    const item = oseDisponibles.find(o => o.Codigo_OSE == id);
    if (item) {
      oseSeleccionadas.push(item);
      oseDisponibles = oseDisponibles.filter(o => o.Codigo_OSE != id);
    }
  } else {
    // Devolver a disponibles
    const item = oseSeleccionadas.find(o => o.Codigo_OSE == id);
    if (item) {
      oseDisponibles.push(item);
      oseSeleccionadas = oseSeleccionadas.filter(o => o.Codigo_OSE != id);
    }
  }

  // Re-renderizar la tabla principal
  renderOSE(oseDisponibles);

  // Si quieres, también podrías renderizar otra tabla con oseSeleccionadas
  if (typeof renderOSESeleccionadas === "function") {
    renderOSESeleccionadas(oseSeleccionadas);
  }
});

// Inicializa al cargar
$(document).ready(function () {
  renderOSE(oseDisponibles);
});
