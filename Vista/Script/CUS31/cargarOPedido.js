// === CARGA INICIAL / GLOBALES ===
let opOriginales = window.listaOriginales || [];
const CAPACIDAD_VOLUMEN = 8;

window.opSeleccionadas = [];
window.zonaSeleccionada = null;
window.opFiltradas = [];
window.zonaSeleccionadaId = null;
window.rutaGenerada = [];
window.waypointsConDistrito = []; // { direccion, distrito }
window.vrOriginales = window.vrOriginales || [];
window.vrOriginalesBackup = Array.isArray(window.vrOriginalesBackup) ? window.vrOriginalesBackup : [...window.vrOriginales];
window.vrDisponibles = [];
window.minDiasRestantesSeleccionados = null;

window.vrSeleccionado = [];           // Repartidores seleccionados
window.fechaSeleccionGlobal = [];     // Fechas por repartidor
let repartidorActivo = null;          // Repartidor actual (temporal)
let fechaSeleccionada = null;         // Fecha temporal
let filaActiva = null;                // Fila destacada

// ===================================================
// UTIL: Formatear fecha local
// ===================================================
function formatLocalDate(date) {
    return date.toISOString().split('T')[0]; // YYYY-MM-DD
}

// === UTIL ===
function showToast(message, type) {
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

// === HELPERS DE FILTRADO DE ÓRDENES (CORREGIDO) ===
function obtenerOrdenesParaMostrar() {
    // Prioridad 1: Si hay zona bloqueada por selección → filtrar por ella
    if (window.zonaSeleccionadaId !== null) {
        return opOriginales.filter(o =>
            String(o.idZona) === String(window.zonaSeleccionadaId) &&
                    !window.opSeleccionadas.some(s => s.Codigo == o.Codigo)
        );
    }

    // Prioridad 2: Filtro explícito activo
    if (Array.isArray(window.opFiltradas) && window.opFiltradas.length > 0) {
        return window.opFiltradas.filter(o =>
            !window.opSeleccionadas.some(s => s.Codigo == o.Codigo)
        );
    }

    // Default: todas menos seleccionadas
    return opOriginales.filter(o =>
        !window.opSeleccionadas.some(s => s.Codigo == o.Codigo)
    );
}

// === RENDER ÓRDENES PRINCIPAL ===
window.renderOP = function (lista) {
    const tbody = $("#table-body");
    tbody.empty();

    const listaParaMostrar = lista || obtenerOrdenesParaMostrar();

    if (listaParaMostrar && listaParaMostrar.length > 0) {
        listaParaMostrar.forEach(o => {
            tbody.append(`
                <tr>
                  <td>${String(o.Codigo)}</td>
                  <td>${o.Distrito}</td>
                  <td>${o.Zona}</td>
                  <td>${parseFloat(o.Peso).toFixed(2)}</td>
                  <td>${parseFloat(o.Volumen).toFixed(2)}</td>
                  <td>${o.DiasRestantes}</td>
                  <td>${o.Numero}</td>
                  <td><span class="icon-add-ose" data-id="${o.Codigo}" title="Agregar orden">➕</span></td>
                </tr>
            `);
        });
    } else {
        tbody.append(`<tr><td colspan="8">No se encontraron órdenes de pedido.</td></tr>`);
    }

    // Mantener mínimo 5 filas visibles
    const minRows = 5;
    const currentRows = tbody.find("tr").length;
    if (currentRows < minRows) {
        for (let i = currentRows; i < minRows; i++) {
            tbody.append(`<tr><td colspan="8">&nbsp;</td></tr>`);
        }
    }
};

// === CONTADOR ===
function actualizarContador() {
    const pesoTotal = window.opSeleccionadas.reduce((sum, o) => sum + parseFloat(o.Peso || 0), 0);
    const volumenTotal = window.opSeleccionadas.reduce((sum, o) => sum + parseFloat(o.Volumen || 0), 0);
    const cantidad = window.opSeleccionadas.length;

    const porcentajePeso = (pesoTotal / 1100) * 100;
    const porcentajeVolumen = (volumenTotal / CAPACIDAD_VOLUMEN) * 100;
    const porcentajeUsado = Math.max(porcentajePeso, porcentajeVolumen);

    let color = "#28a745";
    if (porcentajeUsado >= 60 && porcentajeUsado < 90)
        color = "#f0ad4e";
    else if (porcentajeUsado >= 90)
        color = "#d9534f";

    const contador = `
        <span><strong>Órdenes seleccionadas:</strong> ${cantidad}</span> |
        <span><strong>Peso total:</strong> ${pesoTotal.toFixed(2)} kg / 1100 kg</span> |
        <span><strong>Volumen total:</strong> ${volumenTotal.toFixed(2)} m³ / ${CAPACIDAD_VOLUMEN} m³</span>
    `;

    $("#resumenSeleccion").html(contador).css("color", color);
}

// === REPARTIDORES: FILTRADO (AJAX por días) + EXCLUSIÓN OCUPADOS ===
function actualizarMinDiasRestantes() {
    if (!window.opSeleccionadas || window.opSeleccionadas.length === 0) {
        window.minDiasRestantesSeleccionados = null;
        // ✅ Reset calendario a 3 días
        if (window.calendar) {
            window.calendar.refetchEvents();
        }
        return;
    }
    const dias = window.opSeleccionadas.map(o => parseInt(o.DiasRestantes, 10) || 0);
    window.minDiasRestantesSeleccionados = Math.min(...dias);

    // ✅ ACTUALIZAR CALENDARIO con nuevo rango
    if (window.calendar && typeof window.actualizarRangoCalendario === "function") {
        window.actualizarRangoCalendario();
    }
}

function actualizarRepartidoresDisponibles() {
    const repartidoresOcupados = window.opSeleccionadas
            .map(o => Number(o.IdRepartidor))
            .filter(id => !isNaN(id));

    // si no hay seleccionadas -> restaurar backup
    if (!window.opSeleccionadas || window.opSeleccionadas.length === 0) {
        window.vrOriginales = [...window.vrOriginalesBackup];
        window.vrDisponibles = [...window.vrOriginalesBackup];
        if (typeof renderRV === "function")
            renderRV(window.vrDisponibles);
        return;
    }

    // AJAX para obtener repartidores disponibles según dias
    $.ajax({
        url: "../Ajax/CUS22/filtrarRepartidoresProxy.php",
        method: "POST",
        data: {dias_limite: window.minDiasRestantesSeleccionados},
        dataType: "json",
        success: function (response) {
            if (!response.success) {
                window.vrDisponibles = [];
                if (typeof renderRV === "function")
                    renderRV([]);
                return;
            }
            // filtrados por dias desde el backend
            const filtradosPorDias = response.data || [];

            // excluir los repartidores ocupados por las OP seleccionadas
            window.vrDisponibles = filtradosPorDias.filter(r =>
                !repartidoresOcupados.includes(Number(r.IdRepartidor))
            );

            // actualizar vrOriginales para reflejar el conjunto filtrado por días
            window.vrOriginales = [...filtradosPorDias];

            if (typeof renderRV === "function")
                renderRV(window.vrDisponibles);

            // ✅ ACTUALIZAR CALENDARIO después de repartidores
            if (typeof window.actualizarRangoCalendario === "function") {
                window.actualizarRangoCalendario();
            }
        },
        error: function (xhr, status, error) {
            console.error("❌ Error al filtrar repartidores:", xhr.responseText || error);
        }
    });
}

// === RENDER SELECCIONADAS ===
window.renderOPSeleccionadas = function (lista) {
    const tbody = $("#table-body-rv-selectd");
    tbody.empty();

    if (lista.length > 0) {
        lista.forEach(o => {
            tbody.append(`
                <tr>
                  <td>${String(o.Codigo)}</td>
                  <td>${o.Distrito}</td>
                  <td>${o.Zona}</td>
                  <td>${parseFloat(o.Peso).toFixed(2)}</td>
                  <td>${parseFloat(o.Volumen).toFixed(2)}</td>
                  <td>${o.DiasRestantes}</td>
                  <td>${o.Numero}</td>
                  <td><span class="icon-remove-ose" data-id="${o.Codigo}" title="Quitar orden">❌</span></td>
                </tr>
            `);
        });
    } else {
        tbody.append(`<tr><td colspan="8">No se han seleccionado órdenes de pedido.</td></tr>`);
    }

    // Mantener mínimo 5 filas visibles
    const minRows = 5;
    const currentRows = tbody.find("tr").length;
    if (currentRows < minRows) {
        for (let i = currentRows; i < minRows; i++) {
            tbody.append(`<tr><td colspan="8">&nbsp;</td></tr>`);
        }
    }

    actualizarContador();
};

// ===================================================
// Evento: Agregar Orden de Pedido
// ===================================================
$(document).on("click", ".icon-add-ose", function () {
    const id = $(this).data("id");
    const seleccionada = opOriginales.find(o => o.Codigo == id);
    if (!seleccionada)
        return;

    // Evitar duplicados
    if (window.opSeleccionadas.some(o => o.Codigo == id))
        return;

    // == VALIDACIÓN DE ZONA ==
    if (window.opSeleccionadas.length === 0) {
        // Primera orden → define la zona permitida
        window.zonaSeleccionada = seleccionada.Zona;
        window.zonaSeleccionadaId = seleccionada.idZona;
        //window.opFiltradas = [];
    } else if (window.zonaSeleccionada !== null) {  // ← ¡CLAVE!
        if (seleccionada.Zona !== window.zonaSeleccionada) {
            showToast(`Solo se permiten seleccionar órdenes de la zona "${window.zonaSeleccionada}".`, 'warning');
            return;
        }
    }

    // == VALIDACIÓN DE PESO Y VOLUMEN ==
    const nuevoPeso = parseFloat(seleccionada.Peso || 0);
    const nuevoVolumen = parseFloat(seleccionada.Volumen || 0);

    const pesoActual = window.opSeleccionadas.reduce((sum, o) => sum + parseFloat(o.Peso || 0), 0);
    const volumenActual = window.opSeleccionadas.reduce((sum, o) => sum + parseFloat(o.Volumen || 0), 0);

    const pesoResultante = pesoActual + nuevoPeso;
    const volumenResultante = volumenActual + nuevoVolumen;

    const LIMITE_PESO = 1100;
    const LIMITE_VOLUMEN = CAPACIDAD_VOLUMEN;

    if (pesoResultante > LIMITE_PESO) {
        showToast(`La suma de peso excede el máximo permitido (${LIMITE_PESO} kg).`, 'warning');
        return;
    }

    if (volumenResultante > LIMITE_VOLUMEN) {
        showToast(`La suma de volumen excede el máximo permitido (${LIMITE_VOLUMEN} m³).`, 'warning');
        return;
    }

    // Agregar a seleccionadas
    window.opSeleccionadas.push(seleccionada);

    // actualizar días mínimos y repartidores
    actualizarMinDiasRestantes();
    actualizarRepartidoresDisponibles();

    // Solo agregar dirección si no existe (evita duplicados)
    if (!window.waypointsConDistrito.some(w => w.direccion === seleccionada.Direccion)) {
        window.waypointsConDistrito.push({
            direccion: seleccionada.Direccion,
            distrito: seleccionada.idDistrito
        });
    }

    // Quitar de la tabla principal
    const index = opOriginales.findIndex(o => o.Codigo == id);
    if (index !== -1) {
        opOriginales.splice(index, 1);

        // 🔑 RECREAR filtro si está activo
        /*if (window.opFiltradas.length > 0 && window.zonaSeleccionadaId !== null) {
         window.opFiltradas = opOriginales.filter(o =>
         String(o.idZona) === String(window.zonaSeleccionadaId) &&
         !window.opSeleccionadas.some(sel => sel.Codigo == o.Codigo)
         );
         }*/
    }

    // Renderizar (usa obtenerOrdenesParaMostrar() que aplica todos los filtros)
    renderOP();
    renderOPSeleccionadas(window.opSeleccionadas);

    trazarRuta();

    // Habilitar botones
    $("#btnGenerarOrden").removeAttr("disabled").removeClass("style-button-disabled").addClass("style-button");
    //$(".btn-disponibilidad-disabled").removeAttr("disabled").removeClass("btn-disponibilidad-disabled").addClass("btn-disponibilidad");
    //$("[data-id][class*='btn-disponibilidad']").removeAttr("disabled").removeClass("btn-disponibilidad-disabled").addClass("btn-disponibilidad");

    showToast("La orden fue seleccionada correctamente.", 'success');
});

// ===================================================
// Evento: Quitar Orden de Pedido
// ===================================================
$(document).on("click", ".icon-remove-ose", function () {
    const id = $(this).data("id");

    // Buscar la orden en seleccionadas
    const orden = window.opSeleccionadas.find(o => o.Codigo == id);
    if (!orden)
        return;

    // 1. Devolver al listado original
    opOriginales.push(orden);

    // 🔑 RECREAR filtro si está activo (SIMÉTRICO a agregar)
    if (window.opFiltradas.length > 0 && window.zonaSeleccionadaId !== null) {
        window.opFiltradas = opOriginales.filter(o =>
            String(o.idZona) === String(window.zonaSeleccionadaId) &&
                    !window.opSeleccionadas.some(sel => sel.Codigo == o.Codigo)
        );
    }

    // 2. Quitar de seleccionadas
    window.opSeleccionadas = window.opSeleccionadas.filter(o => o.Codigo != id);

    // recalcular días mínimos y repartidores
    actualizarMinDiasRestantes();
    actualizarRepartidoresDisponibles();

    // 3. Si no quedan seleccionadas → Resetear zona
    if (window.opSeleccionadas.length === 0) {
        window.zonaSeleccionada = null;
        window.zonaSeleccionadaId = null;
        window.opFiltradas = [];
        $("#zonasReparto").val("0");

        // Deshabilitar botones
        $("#btnGenerarOrden")
                .attr("disabled", true)
                .addClass("style-button-disabled")
                .removeClass("style-button");
        
        if (window.vrSeleccionado.length > 0 || window.fechaSeleccionGlobal.length > 0) {
            limpiarSeleccionRepartidor();
        }
        
        // ✅ CAMBIA ESTA LÍNEA:
        //$(".btn-disponibilidad-disabled").attr("disabled", true).addClass("btn-disponibilidad-disabled").removeClass("btn-disponibilidad");
        //$("[data-id][class*='btn-disponibilidad']").attr("disabled", true).addClass("btn-disponibilidad-disabled").removeClass("btn-disponibilidad");

    }

    // Quitar del array de waypoints (solo si no quedan más con esa dirección)
    window.waypointsConDistrito = window.waypointsConDistrito.filter(w => {
        const quedanOtras = window.opSeleccionadas.some(op => op.Direccion === w.direccion);
        return quedanOtras || w.direccion !== orden.Direccion;
    });

    // Renderizar tablas
    renderOP();
    renderOPSeleccionadas(window.opSeleccionadas);

    actualizarContador();
    trazarRuta();

    showToast("La orden fue retirada correctamente.", 'success');
});

function limpiarSeleccionRepartidor() {
    window.vrSeleccionado = [];
    window.fechaSeleccionGlobal = [];

    $("#nombreCliente").val("");
    $("#telefonoCliente").val("");
    $("#apepatCliente").val("");
    $("#apematCliente").val("");
    $("#emailCliente").val("");

    if (filaActiva) {
        filaActiva.removeClass("fila-seleccionada");
        filaActiva = null;
    }

    // ✅ CLAVE: NO llamar actualizarRepartidoresDisponibles()
    // Solo restaurar vrDisponibles con filtros actuales
    const repartidoresOcupados = window.opSeleccionadas
        .map(o => Number(o.IdRepartidor))
        .filter(id => !isNaN(id));
    
    // ✅ MANTENER vrOriginales actual (con filtro días)
    window.vrDisponibles = window.vrOriginales.filter(r =>
        !repartidoresOcupados.includes(Number(r.IdRepartidor))
    );
    
    $(".btn-disponibilidad")
        .prop("disabled", false)
        .removeClass("btn-disponibilidad-disabled")
        .addClass("btn-disponibilidad");

    repartidorActivo = null;
    fechaSeleccionada = null;

    if (window.calendar) {
        window.calendar.removeAllEvents();
        window.calendar.gotoDate(new Date());
    }

    if (typeof renderRV === "function") {
        renderRV(window.vrDisponibles);
    }
    
    if (window.calendar && typeof window.actualizarRangoCalendario === "function") {
        window.actualizarRangoCalendario();
    }

    showToast("✅ Repartidor limpiado porque no hay órdenes seleccionadas.", "info");
}

// ===================================================
// FILTRAR POR ZONA (CORREGIDO)
// ===================================================
$(document).on("click", "#btnFiltrar", function (e) {
    e.preventDefault();

    const zonaSeleccionadaFiltro = $("#zonasReparto").val();

    if (zonaSeleccionadaFiltro === "0") {
        showToast("Seleccione una zona para filtrar.", 'info');
        return;
    }

    // ✅ RESTRICCIÓN CORREGIDA: Solo bloquear si hay OP seleccionadas Y zona diferente
    if (window.opSeleccionadas.length > 0 && window.zonaSeleccionadaId !== null) {
        if (String(zonaSeleccionadaFiltro) !== String(window.zonaSeleccionadaId)) {
            showToast(`Solo puedes filtrar por la zona "${window.zonaSeleccionada}" porque ya hay órdenes seleccionadas.`, 'error');
            return;
        }
    }

    // Aplicar filtro explícito
    window.opFiltradas = opOriginales.filter(o =>
        String(o.idZona) === String(zonaSeleccionadaFiltro) &&
                !window.opSeleccionadas.some(sel => sel.Codigo == o.Codigo)
    );

    // ✅ NUEVA VALIDACIÓN
    if (window.opFiltradas.length === 0) {
        renderOP([]);  // Mostrar mensaje "No se encontraron"
        showToast(`No hay más órdenes disponibles en esta zona.`, 'info');
        return;
    }

    // Actualizar contexto de zona para futuras selecciones
    window.zonaSeleccionadaId = zonaSeleccionadaFiltro;
    const primeraOrden = window.opFiltradas[0];
    if (primeraOrden) {
        window.zonaSeleccionada = primeraOrden.Zona;
    }

    renderOP();
    showToast(`Filtro aplicado a zona "${window.zonaSeleccionada}".`, 'success');
});

$(document).on("click", "#btnVerTodo", function (e) {
    e.preventDefault();

    window.opFiltradas = [];  // ← Siempre limpiar filtro visual

    // ✅ SOLO resetear zona si NO hay órdenes seleccionadas
    if (window.opSeleccionadas.length === 0) {
        window.zonaSeleccionada = null;
        window.zonaSeleccionadaId = null;
    }

    $("#zonasReparto").val("0");
    renderOP();
    showToast(`Filtro eliminado.`, 'success');
});

// ===================================================
// BOTÓN "VER DISPONIBILIDAD" (CORREGIDO)
// ===================================================
$(document).on("click", ".btn-disponibilidad", function () {
    if (window.opSeleccionadas.length === 0) {
        showToast("Primero selecciona órdenes de pedido.", "warning");
        return;
    }

    if (window.vrSeleccionado.length > 0) {
        showToast("Ya hay un repartidor seleccionado. Primero quita el repartidor.", "warning");
        return;
    }

    const codAsignacion = $(this).data("id");
    if (!codAsignacion)
        return;

    // Destacar fila
    if (filaActiva)
        filaActiva.removeClass("fila-seleccionada");
    filaActiva = $(this).closest("tr");
    filaActiva.addClass("fila-seleccionada");

    repartidorActivo = window.vrDisponibles.find(r => String(r.CodigoAsignacion) === String(codAsignacion));
    fechaSeleccionada = null;

    if (!repartidorActivo) {
        showToast("Repartidor no encontrado.", "error");
        return;
    }

    cargarDisponibilidad(codAsignacion);
});

// ===================================================
// CARGAR DISPONIBILIDAD (SIN MODAL)
// ===================================================
function cargarDisponibilidad(codAsignacion) {
    $.ajax({
        url: "../Ajax/CUS22/verDisponibilidadRV.php",
        method: "GET",
        data: {CodigoAsignacion: codAsignacion},
        dataType: "json",
        success: function (data) {
            // ✅ LIMPIAR CALENDARIO
            window.calendar.removeAllEvents();

            // ✅ RANGO DINÁMICO según órdenes
            const hoy = new Date();
            const diasMax = window.minDiasRestantesSeleccionados !== null ? 
                           window.minDiasRestantesSeleccionados : 3;
            const rango = [];
            
            for (let i = 0; i <= diasMax; i++) {  // ✅ <= para incluir día 0
                const fecha = new Date(hoy);
                fecha.setDate(hoy.getDate() + i);
                rango.push(formatLocalDate(fecha));
            }

            // Crear eventos
            const eventos = rango.map(f => {
                const ocupado = Array.isArray(data) && data.some(d => String(d.fecha) === String(f));
                return {
                    title: ocupado ? "Ocupado" : "Disponible",
                    start: f,
                    allDay: true,
                    display: "background",
                    backgroundColor: ocupado ? "#ff8a80" : "#b9f6ca",
                    borderColor: ocupado ? "#ff5252" : "#69f0ae",
                };
            });

            window.calendar.addEventSource(eventos);
            window.calendar.gotoDate(hoy);

            // ✅ MOSTRAR CALENDARIO (SIN MODAL - directo)
            $("#calendar").show();  // Mostrar calendario directamente

            // ✅ CONFIGURAR dateClick (CORREGIDO)
            window.calendar.setOption("dateClick", function (info) {
                const fechaClick = formatLocalDate(info.date);
                const evs = window.calendar.getEvents().filter(ev => formatLocalDate(ev.start) === fechaClick);
                const disponible = evs.length > 0 && evs[0].title.toLowerCase() === "disponible";

                if (!disponible) {
                    showToast("Fecha no disponible", "warning");
                    return;
                }

                if (fechaSeleccionada && fechaSeleccionada !== fechaClick) {
                    const confirmar = confirm(`Ya seleccionaste ${fechaSeleccionada}. ¿Cambiar por ${fechaClick}?`);
                    if (!confirmar)
                        return;
                }

                fechaSeleccionada = fechaClick;

                // ✅ LLENAR CAMPOS
                $("#nombreCliente").val(String(repartidorActivo.IdRepartidor || repartidorActivo.CodigoRepartidor).padStart(5, "0"));
                $("#telefonoCliente").val(repartidorActivo.Placa || "");
                $("#apepatCliente").val(repartidorActivo.Marca || "");
                $("#apematCliente").val(repartidorActivo.Modelo || "");
                $("#emailCliente").val(fechaClick);

                // ✅ SELECCIONAR REPARTIDOR
                if (!window.vrSeleccionado.some(x => x.CodigoAsignacion === repartidorActivo.CodigoAsignacion)) {
                    window.vrSeleccionado.push(repartidorActivo);
                }

                // ✅ ACTUALIZAR LISTA DISPONIBLES
                /*window.vrDisponibles = window.vrOriginales.filter(
                        x => !window.vrSeleccionado.some(s => s.CodigoAsignacion === x.CodigoAsignacion)
                );*/
        
                window.vrDisponibles = window.vrDisponibles.filter(  // ← CAMBIO: vrDisponibles
                        x => !window.vrSeleccionado.some(s => s.CodigoAsignacion === x.CodigoAsignacion)
                );

                if (typeof renderRV === "function") {
                    renderRV(window.vrDisponibles);
                }

                // ✅ DESHABILITAR BOTONES
                $(".btn-disponibilidad").prop("disabled", true).addClass("btn-disponibilidad-disabled").removeClass("btn-disponibilidad");

                // ✅ GUARDAR FECHA
                const existente = window.fechaSeleccionGlobal.find(f => f.idAsignacion === repartidorActivo.CodigoAsignacion);
                if (existente) {
                    existente.fecha = fechaClick;
                } else {
                    window.fechaSeleccionGlobal.push({
                        fecha: fechaClick,
                        idRepartidor: repartidorActivo.IdRepartidor || repartidorActivo.CodigoRepartidor,
                        idAsignacion: repartidorActivo.CodigoAsignacion
                    });
                }

                showToast(`✅ ${fechaClick} seleccionada para ${repartidorActivo.IdRepartidor || repartidorActivo.CodigoRepartidor}`, "success");
            });
        },
        error: function (xhr) {
            console.error("❌ Error AJAX:", xhr.responseText);
            showToast("Error al cargar disponibilidad", "error");
        }
    });
}

// ===================================================
// BOTÓN "CAMBIAR REPARTIDOR"
// ===================================================
$(document).on("click", "#btnCambiarRepartidor", function () {
    if (window.vrSeleccionado.length === 0) {
        showToast("No hay repartidor seleccionado para cambiar.", "warning");
        return;
    }

    const confirmar = confirm(`¿Deseas cambiar el repartidor ${window.vrSeleccionado[0].IdRepartidor || window.vrSeleccionado[0].CodigoRepartidor}? Se limpiarán todos los datos.`);
    if (!confirmar) return;

    // Limpiar repartidor seleccionado y fechas globales
    window.vrSeleccionado = [];
    window.fechaSeleccionGlobal = [];

    // Limpiar campos de formulario
    $("#nombreCliente").val("");
    $("#telefonoCliente").val("");
    $("#apepatCliente").val("");
    $("#apematCliente").val("");
    $("#emailCliente").val("");

    // Quitar clase seleccionada de fila
    if (filaActiva) {
        filaActiva.removeClass("fila-seleccionada");
        filaActiva = null;
    }

    // Restaurar lista de repartidores disponibles
    //window.vrDisponibles = [...window.vrOriginales];
    // ✅ CORREGIDO: Mantiene el filtro por días del backend
    //window.vrDisponibles = [...window.vrOriginalesBackup];  // ← Backup mantiene el filtro original

    // ✅ AMBOS FILTROS: DÍAS + REPARTIDORES OCUPADOS
    const repartidoresOcupados = window.opSeleccionadas
        .map(o => Number(o.IdRepartidor))
        .filter(id => !isNaN(id));
    
    window.vrDisponibles = window.vrOriginales.filter(r =>
        !repartidoresOcupados.includes(Number(r.IdRepartidor))
    );
    
    // Rehabilitar botones de disponibilidad
    $(".btn-disponibilidad")
        .prop("disabled", false)
        .removeClass("btn-disponibilidad-disabled")
        .addClass("btn-disponibilidad");

    // Resetear variables temporales
    repartidorActivo = null;
    fechaSeleccionada = null;

    // Limpiar eventos y estado del calendario para estado inicial sin selección
    if (window.calendar) {
        window.calendar.removeAllEvents();
        window.calendar.gotoDate(new Date());
    }

    // Re-renderizar tabla repartidores
    if (typeof renderRV === "function") {
        renderRV(window.vrDisponibles);
    }
    
    // Re-renderizar tabla repartidores
    if (typeof renderRV === "function") {
        renderRV(window.vrDisponibles);
    }
    
    // Actualizar calendario a rango por defecto (3 días)
    if (window.calendar && typeof window.actualizarRangoCalendario === "function") {
        window.actualizarRangoCalendario();
    }

    showToast("✅ Repartidor cambiado. Puedes seleccionar uno nuevo.", "success");
});

// ===================================================
// TRAZAR RUTA
// ===================================================
function trazarRuta() {
    const origen = window.direcAlmacen.DireccionOrigen;

    if (window.opSeleccionadas.length === 0) {
        $("#ruta").val(`Origen: ${origen}\nDestino: ${origen}`);
        return;
    }

    const destinos = window.waypointsConDistrito.map(w => w.direccion).join("|");

    $.ajax({
        url: "../Ajax/CUS22/directionsProxy.php",
        data: {origin: origen, destination: origen, waypoints: destinos},
        dataType: "json",
        success: function (data) {
            if (data.status !== "OK")
                return console.error("Error en Directions API:", data);

            const route = data.routes[0];
            const waypointOrder = route.waypoint_order || [];
            const polyline = route.overview_polyline?.points || "No disponible";

            const rutaArray = [];

            // Origen
            rutaArray.push({
                Id_Distrito: window.direcAlmacen.Id_Distrito,
                DireccionSnap: origen,
                Orden: 1,
                RutaPolyline: polyline
            });

            // Waypoints optimizados
            waypointOrder.forEach((idx, i) => {
                const w = window.waypointsConDistrito[idx];
                rutaArray.push({
                    Id_Distrito: w.distrito,
                    DireccionSnap: w.direccion,
                    Orden: i + 2,
                    RutaPolyline: polyline
                });
            });

            // Regreso al almacén
            rutaArray.push({
                Id_Distrito: window.direcAlmacen.Id_Distrito,
                DireccionSnap: origen,
                Orden: rutaArray.length + 1,
                RutaPolyline: polyline
            });

            // Mostrar en textarea
            let rutaTexto = `Origen: ${origen}\n`;
            waypointOrder.forEach(idx => {
                rutaTexto += `${window.waypointsConDistrito[idx].direccion}\n`;
            });
            rutaTexto += `Destino: ${origen}`;
            $("#ruta").val(rutaTexto);
            window.rutaGenerada = rutaArray;
        },
        error: function (err) {
            console.error("Error al llamar al proxy:", err);
        }
    });
}

// ===================================================
// INIT
// ===================================================
$(document).ready(() => {
    // deshabilitar botones al cargar
    $("#btnGenerarOrden").prop("disabled", true).addClass("style-button-disabled").removeClass("style-button");
    //$(".btn-disponibilidad").prop("disabled", true).addClass("btn-disponibilidad-disabled").removeClass("btn-disponibilidad");
    //$("[data-id][class*='btn-disponibilidad']").prop("disabled", true).addClass("btn-disponibilidad-disabled").removeClass("btn-disponibilidad");

    // render inicial
    renderOP();
    renderOPSeleccionadas(window.opSeleccionadas);

    // inicializar repartidores
    window.vrDisponibles = [...window.vrOriginalesBackup];
    if (typeof renderRV === "function")
        renderRV(window.vrDisponibles);

    // contador inicial
    actualizarContador();
});
