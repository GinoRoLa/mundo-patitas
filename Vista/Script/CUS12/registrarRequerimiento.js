// ===================================================
// 🔹 Toast (ya existente, se reutiliza)
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
// 🔹 Función auxiliar: limpiar formato "S/. 123,456.78"
// ===================================================
function parseCurrencyToNumber(str) {
if (!str && str !== 0) return 0;
str = String(str).trim();

// Reemplazar comas por nada (si se usan como separadores de miles)
str = str.replace(/,/g, "");

// Quitar todo lo que no sea dígito o punto
const cleaned = str.replace(/[^0-9.]/g, "");

if (cleaned === "") return 0;

// Si solo hay un punto, parseFloat directo
const parts = cleaned.split(".");
if (parts.length === 1) return parseFloat(cleaned);

// Si hay varios puntos, tomar el último como separador decimal
const decimals = parts.pop();
const integerPart = parts.join("");
const normalized = integerPart + "." + decimals;

return parseFloat(normalized);
}

// ===================================================
// 🔹 Función para enviar el requerimiento al servidor
// ===================================================
function enviarRequerimiento() {
try {
// Obtener datos desde las variables y los inputs
const listaProductos = window.reporteInventario || [];

// Usar la función de parseo correcta
const total = parseCurrencyToNumber($("#total").val());
const precioPromedio = parseCurrencyToNumber($("#precioPromedio").val());

// Validaciones básicas
if (!Array.isArray(listaProductos) || listaProductos.length === 0) {
  showToast("No hay productos para registrar el requerimiento.", "warning");
  return;
}

if (total <= 0 || precioPromedio <= 0 || isNaN(total) || isNaN(precioPromedio)) {
  showToast("Los valores de total o precio promedio no son válidos.", "warning");
  return;
}

// Mostrar estado de envío
showToast("Generando requerimiento, por favor espere...", "info");

// Enviar datos al servidor
$.ajax({
  url: "../Ajax/CUS12/registrarRC.php",
  type: "POST",
  dataType: "json",
  contentType: "application/json",
  data: JSON.stringify({
    json: listaProductos,
    total: total,
    preciopromedio: precioPromedio
  }),
  success: function (response) {
    if (response.success) {
      showToast(`Requerimiento generado correctamente (ID: ${response.id})`, "success");
      setTimeout(() => { location.reload(); }, 2500);
      // Opcional: limpiar inputs o refrescar tabla
      // $("#total").val("");
      // $("#precioPromedio").val("");
    } else {
      showToast(response.message || "No se pudo generar el requerimiento.", "error");
    }
  },
  error: function (xhr, status, error) {
    console.error("Error AJAX:", error, xhr.responseText);
    showToast("Error de conexión al registrar el requerimiento.", "error");
  }
});

} catch (error) {
console.error("Error en enviarRequerimiento:", error);
showToast("Error inesperado al enviar el requerimiento.", "error");
}
}

// ===================================================
// 🔹 Asociar evento al botón al cargar el DOM
// ===================================================
$(document).ready(function () {
$("#btnGenerarRequerimiento").on("click", function () {
enviarRequerimiento();
});
});
