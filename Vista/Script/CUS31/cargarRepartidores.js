
let vrOriginalesLocal = window.vrOriginales || []; 

window.renderRV = function (lista) {
  const tbody = $("#table-body-rv");
  tbody.empty();

  if (lista.length > 0) {
    lista.forEach(function (r) {
      let row = `
        <tr>
          <td>${String(r.IdRepartidor).padStart(5, "0")}</td>
          <td>${r.Placa}</td>
          <td>${r.Marca}</td>
          <td>${r.Modelo}</td>
          <td>${parseFloat(r.CargaUtilKg).toFixed(2)}</td>
          <td>${parseFloat(r.CapacidadM3).toFixed(2)}</td>
          <td>
            <button class="style-button btn-disponibilidad-disabled" data-id="${r.CodigoAsignacion}" disabled>Ver</button>
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
  }
  
  if (typeof window.actualizarEstadoBotones === "function") {
    window.actualizarEstadoBotones();
  }
};

$(document).ready(() => {
    window.vrDisponibles = [...window.vrOriginales];
    renderRV(window.vrDisponibles);
});
