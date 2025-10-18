document.addEventListener("DOMContentLoaded", () => { 
    const radios = document.querySelectorAll("input[type='radio']");
    const btnRegistrar = document.getElementById("btnRegistrarReprogramacion");

    //formulario principal
    const idConsolidacion = document.getElementById("idConsolidacionRep");
    const idPedido = document.getElementById("idPedidoRep");
    const idCliente = document.getElementById("idClienteRep");
    const nombreCliente = document.getElementById("nombreClienteRep");
    const observaciones = document.getElementById("observacionesRep");
    const fecha = document.getElementById("fechaConsolRep"); 
    fecha.value = "";
    const estado = document.getElementById("estadoRep");

    // Inputs detalle t02
    const detalleIdPedido = document.getElementById("detalleIdPedido");
    const detalleIdCliente = document.getElementById("detalleIdCliente");
    const detalleFecha = document.getElementById("detalleFecha");
    const detalleEstado = document.getElementById("detalleEstado");

    // Inputs detalle t59
    const detalleIdOSE = document.getElementById("detalleIdOSE");
    const detalleIdPedidoOSE = document.getElementById("detalleIdPedidoOSE");
    const detalleFecCreacion = document.getElementById("detalleFecCreacion");
    const detalleEstadoOSE = document.getElementById("detalleEstadoOSE");

    const obsDevolucion = ["Rechazo del pedido", "La dirección no existe", "Otros"];
    let filaSeleccionada = null;

    // Función para llenar los inputs de detalle
    function rellenarDetalle(fila) {
        // t02
        detalleIdPedido.value = fila.dataset.idpedido;
        detalleIdCliente.value = fila.dataset.idcliente;
        detalleFecha.value = fila.dataset.fecha || new Date().toISOString().split("T")[0];
        detalleEstado.value = fila.dataset.estado || "No Entregado";

        // t59
        detalleIdOSE.value = fila.dataset.idose;
        detalleIdPedidoOSE.value = fila.dataset.idpedido;
        detalleFecCreacion.value = fila.dataset.fecCreacion || new Date().toISOString().split("T")[0];
        detalleEstadoOSE.value = fila.dataset.estadoOSE || "No Entregado";
    }

    //Seleccion de fila y radio
    radios.forEach(radio => {
        radio.addEventListener("change", () => {
            filaSeleccionada = radio.closest("tr");
            if (!filaSeleccionada) return;

            // Bloquear radios de todas las demás filas
            document.querySelectorAll("tr").forEach(tr => {
                if (tr !== filaSeleccionada) {
                    const otrosRadios = tr.querySelectorAll("input[type='radio']");
                    otrosRadios.forEach(r => r.disabled = true);
                }
            });

            const radioReprog = filaSeleccionada.querySelector(".radio-reprogramar");
            const radioDevol = filaSeleccionada.querySelector(".radio-devolucion");
            const obs = filaSeleccionada.dataset.observaciones || "";

            const esDevolucionObligatoria = obsDevolucion.some(o => obs.toLowerCase() === o.toLowerCase());

            if (esDevolucionObligatoria) {
                // Solo permitir devolución
                radioReprog.disabled = true;
                radioDevol.disabled = false;
                radioDevol.checked = true;

                registrarDevolucion(filaSeleccionada);
            } else {
                // Solo permitir reprogramación
                radioDevol.disabled = true;
                radioReprog.disabled = false;
                radioReprog.checked = true;

                // Rellenar formulario
                idConsolidacion.value = filaSeleccionada.dataset.id;
                idPedido.value = filaSeleccionada.dataset.idpedido;
                idCliente.value = filaSeleccionada.dataset.idcliente;
                nombreCliente.value = filaSeleccionada.dataset.nombrecliente;
                observaciones.value = filaSeleccionada.dataset.observaciones;
                fecha.value = new Date().toISOString().split("T")[0];
                estado.value = "Reprogramado";
                btnRegistrar.disabled = false;

                // 🔹 Llenar detalle automáticamente
                rellenarDetalle(filaSeleccionada);
            }
        });
    });

    btnRegistrar.addEventListener("click", () => {
        if (!filaSeleccionada || !idPedido.value) {
            alert("Seleccione un pedido antes de registrar la reprogramación.");
            return;
        }

        const datos = {
            idConsolidacion: idConsolidacion.value,
            idPedido: idPedido.value,
            idCliente: idCliente.value,
            nombreCliente: nombreCliente.value,
            observaciones: observaciones.value,
            fechaReprogramacion: fecha.value,
            estado: estado.value
        };

        fetch("../../vista/ajax/CUS26/registrarReprogramacion.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(datos)
        })
        .then(res => res.text())
        .then(texto => {
            console.log("📡 Respuesta del servidor:", texto);
            if (texto.includes("ÉXITO")) {
                // Actualizar detalles con la fecha y estado modificados
                detalleFecha.value = fecha.value;
                detalleEstado.value = estado.value;
                detalleFecCreacion.value = fecha.value; // o si quieres otro campo de fecha
                detalleEstadoOSE.value = estado.value;

                alert("✅ Reprogramación registrada correctamente");
                location.reload();
            } else {
                alert("⚠️ Error al registrar reprogramación");
            }
        })
        .catch(err => {
            console.error("❌ Error en fetch:", err);
            alert("Error de conexión con el servidor.");
        });
    });

    function registrarDevolucion(fila) {
        const datos = {
            idConsolidacion: fila.dataset.id,
            idPedido: fila.dataset.idpedido,
            idCliente: fila.dataset.idcliente,
            observaciones: fila.dataset.observaciones || ""
        };

        fetch("../../vista/ajax/CUS26/registrarDevolucion.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(datos)
        })
        .then(res => res.text())
        .then(texto => {
            console.log("📡 Respuesta devolución:", texto);
            if (texto.includes("ÉXITO")) {
                window.location.href = `../CUS27/CUS27_IU027.php?idPedido=${datos.idPedido}`;
            } else {
                alert("⚠️ Error al registrar devolución");
            }
        })
        .catch(err => {
            console.error("❌ Error en devolución:", err);
        });
    }
});
