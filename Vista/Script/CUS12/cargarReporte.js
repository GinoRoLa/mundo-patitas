// Función para renderizar la tabla de inventario
window.renderTablaInventario = function(lista) {
  const tbody = $("#table-body");
  tbody.empty();

  if (lista && lista.length > 0) {
    lista.forEach(function (item) {
      const row = `
        <tr>
          <td>${String(item.Id_Producto).padStart(3, "0")}</td>
          <td>${item.Descripcion || "-"}</td>
          <td>${item.Marca || "-"}</td>
          <td>${item.Categoria || "-"}</td>
          <td>${parseInt(item.StockActual || 0)}</td>
          <td>${parseFloat(item.PrecioPromedio || 0).toFixed(2)}</td>
          <td>${parseInt(item.CantidadSolicitar || 0)}</td>
        </tr>
      `;
      tbody.append(row);
    });
  } else {
    tbody.append(`
      <tr>
        <td colspan="7" class="no-data">No se encontraron productos.</td>
      </tr>
    `);
  }

  // Mantener mínimo 5 filas visuales
  const minRows = 5;
  const currentRows = tbody.find("tr").length;
  if (currentRows < minRows) {
    for (let i = currentRows; i < minRows; i++) {
      tbody.append(`<tr><td colspan="7">&nbsp;</td></tr>`);
    }
  }
};

// Renderizar automáticamente al cargar
$(document).ready(function() {
  window.renderTablaInventario(window.reporteInventario || []);
});

