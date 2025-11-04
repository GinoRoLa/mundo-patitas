// ===================================================
// 🔹 Toast
// ===================================================
function showToast(message, type = "info") {
const toast = document.createElement("div");
toast.className = `custom-toast ${type}`;
toast.textContent = message;
document.body.appendChild(toast);
setTimeout(() => toast.classList.add("show"), 100);
setTimeout(() => {
toast.classList.remove("show");
setTimeout(() => toast.remove(), 300);
}, 3000);
}

// ===================================================
// 🔹 Carga segura del reporte de inventario (sin PHP)
// ===================================================
(function initReporteInventario() {
try {
if (typeof window.reporteInventario === "undefined" || !Array.isArray(window.reporteInventario)) {
throw new Error("No se pudo cargar el inventario o los datos son inválidos.");
}
} catch (error) {
console.error("Error al cargar reporteInventario:", error);
window.reporteInventario = [];
showToast("Error al cargar el inventario. Verifique la conexión a la base de datos.", "error");
}
})();

// ===================================================
// 🔹 Renderizado de tabla y cálculos
// ===================================================
window.renderTablaInventario = function (lista) {
const tbody = $("#table-body");
tbody.empty();

let totalRequerimiento = 0;
let sumaPreciosPromedio = 0;
let contadorPrecios = 0;

if (lista && lista.length > 0) {
lista.forEach(function (item) {
const precio = parseFloat(item.PrecioPromedio || 0);
const cantidad = parseInt(item.CantidadSolicitar || 0);
const subtotal = precio * cantidad;


  totalRequerimiento += subtotal;
  if (precio > 0) {
    sumaPreciosPromedio += precio;
    contadorPrecios++;
  }

  const row = `
    <tr>
      <td>${String(item.Id_Producto).padStart(3, "0")}</td>
      <td>${item.Descripcion || "-"}</td>
      <td>${item.Marca || "-"}</td>
      <td>${item.Categoria || "-"}</td>
      <td>${parseInt(item.StockActual || 0)}</td>
      <td>${precio.toFixed(2)}</td>
      <td>${cantidad}</td>
    </tr>
  `;
  tbody.append(row);
});

showToast("Productos cargados correctamente.", "success");


} else {
tbody.append(`       <tr>         <td colspan="7" class="no-data">No se encontraron productos.</td>       </tr>
    `);
showToast("No se encontraron productos en el inventario.", "warning");
}

// Mantener mínimo 10 filas visuales
const minRows = 10;
const currentRows = tbody.find("tr").length;
if (currentRows < minRows) {
for (let i = currentRows; i < minRows; i++) {
tbody.append(`<tr><td colspan="7">&nbsp;</td></tr>`);
}
}

// Calcular resultados finales
const promedioRequerimiento =
contadorPrecios > 0 ? sumaPreciosPromedio / contadorPrecios : 0;

// Mostrar en los campos de detalle
$("#total").val(`S/. ${totalRequerimiento.toFixed(2)}`);
$("#precioPromedio").val(`S/. ${promedioRequerimiento.toFixed(2)}`);
};

// ===================================================
// 🔹 Render automático al cargar
// ===================================================
$(document).ready(function () {
try {
window.renderTablaInventario(window.reporteInventario || []);
} catch (error) {
console.error("Error al renderizar la tabla:", error);
showToast("Ocurrió un error al renderizar la tabla de inventario.", "error");
}
});
