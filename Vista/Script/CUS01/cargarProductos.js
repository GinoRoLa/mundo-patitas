let productosOriginales = window.productosOriginales || [];  
let stockDisponible = window.stockDisponible || {};

productosOriginales.forEach(p => {
  stockDisponible[p.Id_Producto] = parseInt(p.StockActual);
});

window.renderTabla = function (lista) {
  const tbody = $("#table-body");
  tbody.empty();

  if (lista.length > 0) {
    lista.forEach(function (p) {
      let stock = stockDisponible[p.Id_Producto];
      let row = `
        <tr>
          <td>${String(p.Id_Producto).padStart(3,"0")}</td>
          <td>${p.NombreProducto}</td>
          <td>${parseFloat(p.PrecioUnitario).toFixed(2)}</td>
          <td>${stock}</td>
          <td>
            <label class="checkbox-container">
              <input type="radio" name="productoSeleccionado"
                codigo-producto="${String(p.Id_Producto).padStart(3,"0")}"
                nombre-producto="${p.NombreProducto}"
                precio-producto="${parseFloat(p.PrecioUnitario).toFixed(2)}"
                data-stock="${stock}"
                ${stock <= 0 ? "disabled" : ""}>
              <span class="checkmark"></span>
            </label>
          </td>
        </tr>
      `;
      tbody.append(row);
    });
  } else {
    tbody.append(`
      <tr>
        <td colspan="5">No se encontraron productos.</td>
      </tr>
    `);
  }

  const minRows = 5;
  const currentRows = tbody.find("tr").length;
  if (currentRows < minRows) {
    for (let i = currentRows; i < minRows; i++) {
      tbody.append(`<tr><td colspan="5">&nbsp;</td></tr>`);
    }
  }
};
