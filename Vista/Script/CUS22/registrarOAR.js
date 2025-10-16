// ===========================================================
// 🔹 Enviar datos al proxy PHP para generar la orden de asignación
// ===========================================================
$(document).on("click", ".botonesCUS .style-button:first-child", function (e) {
  e.preventDefault();

  // Validaciones básicas
  if (!window.oseSeleccionadas || !window.oseSeleccionadas.length) {
    showToast("No hay órdenes seleccionadas.", "warning");
    return;
}

if (!window.vrSeleccionado || !window.vrSeleccionado.length) {
    showToast("No hay repartidor seleccionado.", "warning");
    return;
}

if (!window.fechaSeleccionGlobal || !window.fechaSeleccionGlobal.length) {
    showToast("No se ha seleccionado una fecha de entrega.", "warning");
    return;
}

if (!window.rutaGenerada || !window.rutaGenerada.length) {
    showToast("No se ha seleccionado una ruta de entrega.", "warning");
    return;
}

// ======================================================
  // 🔹 NUEVA VALIDACIÓN: verificar ocupación mínima de 60%
  // ======================================================
  const pesoTotal = window.oseSeleccionadas.reduce((sum, o) => sum + parseFloat(o.Peso_Kg), 0);
  const volumenTotal = window.oseSeleccionadas.reduce((sum, o) => sum + parseFloat(o.Volumen_m3), 0);

  const porcentajePeso = (pesoTotal / 1100) * 100;
  const porcentajeVolumen = (volumenTotal / 15) * 100; // o usa CAPACIDAD_VOLUMEN si ya está global
  const porcentajeOcupado = Math.max(porcentajePeso, porcentajeVolumen);

  if (porcentajeOcupado < 60) {
    showToast("Debe ocupar al menos el 60% de la capacidad del vehículo antes de generar la orden.", "warning");
    return;
  }
  // Construir el payload (JSON)
  const payload = {
    ose: window.oseSeleccionadas,
    repartidor: window.vrSeleccionado,
    fechas: window.fechaSeleccionGlobal,
    ruta: window.rutaGenerada
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
