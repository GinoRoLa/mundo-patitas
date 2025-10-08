// Lista inicial desde PHP
let vrOriginales = window.vrOriginales || [];
let vrDisponibles = [...vrOriginales]; // Copia inicial
let vrSeleccionados = [];

// Función para renderizar la tabla de Repartidores-Vehículos disponibles
window.renderRV = function (lista) {
  const tbody = $("#table-body-rv");
  tbody.empty();

  if (lista.length > 0) {
    lista.forEach(function (r) {
      let row = `
        <tr>
          <td>${String(r.CodigoRepartidor).padStart(5, "0")}</td>
          <td>${r.Placa}</td>
          <td>${r.Marca}</td>
          <td>${r.Modelo}</td>
          <td>${parseFloat(r.CargaUtilKg).toFixed(2)}</td>
          <td>${parseFloat(r.CapacidadM3).toFixed(2)}</td>
          <td>
            <button class="style-button btn-disponibilidad" data-id="${r.CodigoAsignacion}">Ver</button>
          </td>
        </tr>
      `;
      tbody.append(row);
    });
  } else {
    tbody.append(`
      <tr><td colspan="7">No se encontraron repartidores disponibles.</td></tr>
    `);
  }

  // Mantener mínimo 5 filas visibles
  const minRows = 5;
  const currentRows = tbody.find("tr").length;
  if (currentRows < minRows) {
    for (let i = currentRows; i < minRows; i++) {
      tbody.append(`<tr><td colspan="7">&nbsp;</td></tr>`);
    }
  };
};

// Manejo de selección/deselección de repartidores
$(document).on("change", ".chk-rv", function () {
  const id = parseInt($(this).val());
  const seleccionado = $(this).is(":checked");

  if (seleccionado) {
    // Mover de disponibles a seleccionados
    const item = vrDisponibles.find(r => r.CodigoRepartidor == id);
    if (item) {
      vrSeleccionados.push(item);
      vrDisponibles = vrDisponibles.filter(r => r.CodigoRepartidor != id);
    }
  } else {
    // Devolver a disponibles
    const item = vrSeleccionados.find(r => r.CodigoRepartidor == id);
    if (item) {
      vrDisponibles.push(item);
      vrSeleccionados = vrSeleccionados.filter(r => r.CodigoRepartidor != id);
    }
  }

  // Re-renderizar la tabla principal
  renderRV(vrDisponibles);

  // Si existe función para renderizar los seleccionados, la llamamos
  if (typeof renderRVSeleccionados === "function") {
    renderRVSeleccionados(vrSeleccionados);
  }
});

// Inicialización al cargar la página
$(document).ready(function () {
  renderRV(vrDisponibles);
});
