let vrOriginalesLocal = window.vrOriginales || []; 

window.renderRV = function (lista) {
  const tbody = $("#table-body-rv");
  tbody.empty();

  // ✅ CLAVE: Determinar estado de botones según órdenes seleccionadas
  const botonesHabilitados = window.opSeleccionadas && window.opSeleccionadas.length > 0;

  if (lista.length > 0) {
    lista.forEach(function (r) {
      const buttonClass = botonesHabilitados ? "btn-disponibilidad" : "btn-disponibilidad-disabled";
      const disabledAttr = botonesHabilitados ? "" : "disabled";
      
      let row = `
        <tr>
          <td>${String(r.IdRepartidor).padStart(5, "0")}</td>
          <td>${r.Placa}</td>
          <td>${r.Marca}</td>
          <td>${r.Modelo}</td>
          <td>${parseFloat(r.CargaUtilKg).toFixed(2)}</td>
          <td>${parseFloat(r.CapacidadM3).toFixed(2)}</td>
          <td>
            <button class="style-button ${buttonClass}" data-id="${r.CodigoAsignacion}" ${disabledAttr}>Ver</button>
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
  
  // ✅ ELIMINAR esta línea que causaba conflictos:
  // if (typeof window.actualizarEstadoBotones === "function") {
  //     window.actualizarEstadoBotones();
  // }
};

$(document).ready(() => {
    window.vrDisponibles = [...window.vrOriginales];
    renderRV(window.vrDisponibles);
});
