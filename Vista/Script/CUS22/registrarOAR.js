// ===========================================================
// 🔹 Enviar datos al proxy PHP para generar la orden de asignación
// ===========================================================
$(document).on("click", ".botonesCUS .style-button:first-child", function (e) {
  e.preventDefault();

  // Validaciones básicas
  if (!window.oseSeleccionadas.length) {
    if (typeof showToast === "function") showToast("No hay órdenes seleccionadas.", "warning");
    return;
  }

  if (!window.vrSeleccionado.length) {
    if (typeof showToast === "function") showToast("No hay repartidor seleccionado.", "warning");
    return;
  }

  if (!window.fechaSeleccionGlobal.length) {
    if (typeof showToast === "function") showToast("No se ha seleccionado una fecha de entrega.", "warning");
    return;
  }

  // Construir el payload (JSON)
  const payload = {
    ose: window.oseSeleccionadas,
    repartidor: window.vrSeleccionado,
    fechas: window.fechaSeleccionGlobal
  };

  console.log("🚀 Enviando datos al proxy PHP:", payload);

  $.ajax({
    url: "../Ajax/CUS22/generarOrdenAsignacionProxy.php", // ruta al proxy PHP
    method: "POST",
    data: { data: JSON.stringify(payload) }, // se envía todo como un JSON
    dataType: "json",
    beforeSend: function () {
      if (typeof showToast === "function") showToast("Generando orden de asignación...", "info");
    },
    success: function (response) {
      console.log("✅ Respuesta del proxy:", response);

      if (response.success) {
        if (typeof showToast === "function") 
          showToast(`Orden de asignación generada con código: ${response.codigo_orden}`, "success");
      } else {
        if (typeof showToast === "function") 
          showToast(`Error: ${response.message || "No se pudo generar la orden"}`, "error");
      }
    },
    error: function (xhr, status, error) {
      console.error("❌ Error AJAX:", xhr.responseText || error);
      if (typeof showToast === "function") showToast("Error al comunicarse con el servidor.", "error");
    }
  });
});
