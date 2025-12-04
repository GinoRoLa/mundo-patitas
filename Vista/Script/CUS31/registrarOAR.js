// ===================================================
// BOTÓN "GENERAR ORDEN" (ADAPTADO)
// ===================================================
$(document).on("click", "#btnGenerarOrden", function (e) {
    e.preventDefault();

    // ✅ VALIDACIONES BÁSICAS
    if (!window.opSeleccionadas || !window.opSeleccionadas.length) {
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
        showToast("No se ha generado la ruta.", "warning");
        return;
    }

    // ✅ VALIDACIÓN OCUPACIÓN MÍNIMA 60%
    const pesoTotal = window.opSeleccionadas.reduce((sum, o) => sum + parseFloat(o.Peso || 0), 0);
    const volumenTotal = window.opSeleccionadas.reduce((sum, o) => sum + parseFloat(o.Volumen || 0), 0);

    const porcentajePeso = (pesoTotal / 1100) * 100;
    const porcentajeVolumen = (volumenTotal / CAPACIDAD_VOLUMEN) * 100;
    const porcentajeOcupado = Math.max(porcentajePeso, porcentajeVolumen);

    if (porcentajeOcupado < 60) {
        showToast("Debe ocupar al menos el 60% de la capacidad del vehículo.", "warning");
        return;
    }

    // ✅ CONSTRUIR PAYLOAD
    const payload = {
        ordenes: window.opSeleccionadas,           // ✅ Órdenes seleccionadas
        repartidor: window.vrSeleccionado[0],      // ✅ Repartidor seleccionado
        fecha: window.fechaSeleccionGlobal[0],     // ✅ Fecha seleccionada
        ruta: window.rutaGenerada,                 // ✅ Ruta generada
        pesoTotal: pesoTotal.toFixed(2),
        volumenTotal: volumenTotal.toFixed(2),
        porcentajeOcupacion: porcentajeOcupado.toFixed(1)
    };

    // ✅ MOSTRAR EN CONSOLA para depuración
    console.log("🚀 === DATOS A ENVIAR ===");
    console.log("📦 Órdenes:", window.opSeleccionadas);
    console.log("🚚 Repartidor:", window.vrSeleccionado[0]);
    console.log("📅 Fecha:", window.fechaSeleccionGlobal[0]);
    console.log("🗺️ Ruta:", window.rutaGenerada);
    console.log("⚖️ Peso/Volumen:", `${pesoTotal.toFixed(2)}kg / ${volumenTotal.toFixed(2)}m³ (${porcentajeOcupado.toFixed(1)}%)`);
    console.log("📤 Payload completo:", payload);

    // ✅ ENVIAR AL SERVIDOR
    $.ajax({
        url: "../Ajax/CUS31/generarOrdenAsignacionProxy.php",
        method: "POST",
        data: { data: JSON.stringify(payload) },
        dataType: "json",
        beforeSend: function () {
            showToast("Generando orden de asignación...", "info");
            $(this).prop("disabled", true).addClass("style-button-disabled");
        },
        success: function (response) {
            console.log("✅ Respuesta servidor:", response);

            if (response.success) {
                showToast(`✅ Orden generada: ${response.codigo_orden || response.id || 'OK'}`, "success");
                
                // ✅ OPCIONAL: Resetear todo después de éxito
                // resetearTodo();
                // ✅ RECARGA AUTOMÁTICA después de 2 segundos
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                showToast(`❌ Error: ${response.message || "Fallo al generar orden"}`, "error");
            }
        },
        error: function (xhr, status, error) {
            console.error("❌ Error AJAX:", xhr.responseText || error);
            showToast("❌ Error de conexión con servidor.", "error");
        },
        complete: function() {
            // Rehabilitar botón
            $("#btnGenerarOrden").prop("disabled", false).removeClass("style-button-disabled").addClass("style-button");
        }
    });
});
