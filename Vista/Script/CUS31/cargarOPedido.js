let opOriginales = window.listaOriginales || [];
const CAPACIDAD_VOLUMEN = 8;
window.opSeleccionadas = [];
window.zonaSeleccionada = null;
window.opFiltradas = []; 
window.zonaSeleccionadaId = null;
window.rutaGenerada = [];
// Array global para rutas sin duplicados
window.waypointsConDistrito = []; // { direccion, distrito }
window.vrOriginales = window.vrOriginales || [];
window.vrDisponibles = [];


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

window.renderOP = function (lista) {
    const tbody = $("#table-body");
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
                  <td><span class="icon-add-ose" data-id="${o.Codigo}" title="Agregar orden">&#10133;</span></td>
                </tr>
            `);
        });
    } else {
        tbody.append(`<tr><td colspan="8">No se encontraron órdenes de pedido.</td></tr>`);
    }

    while (tbody.find("tr").length < 5) {
        tbody.append(`<tr><td colspan="8">&nbsp;</td></tr>`);
    }
};

// ===================================================
// 🔹 Actualizar contador dinámico
// ===================================================
function actualizarContador() {
    const pesoTotal = window.opSeleccionadas.reduce((sum, o) => sum + parseFloat(o.Peso), 0);
    const volumenTotal = window.opSeleccionadas.reduce((sum, o) => sum + parseFloat(o.Volumen), 0);
    const cantidad = window.opSeleccionadas.length;

    const porcentajePeso = (pesoTotal / 1100) * 100;
    const porcentajeVolumen = (volumenTotal / CAPACIDAD_VOLUMEN) * 100; /*CAMBIAR DE 15 A 8*/
    const porcentajeUsado = Math.max(porcentajePeso, porcentajeVolumen);

    let color = "#28a745";
    if (porcentajeUsado >= 60 && porcentajeUsado < 90) color = "#f0ad4e";
    else if (porcentajeUsado >= 90) color = "#d9534f";

    const contador = `
        <span><strong>Órdenes seleccionadas:</strong> ${cantidad}</span> |
        <span><strong>Peso total:</strong> ${pesoTotal.toFixed(2)} kg / 1100 kg</span> |
        <span><strong>Volumen total:</strong> ${volumenTotal.toFixed(2)} m³ / ${CAPACIDAD_VOLUMEN} m³</span>
    `; /*CAMBIAR DE 15 A 8*/

    $("#resumenSeleccion").html(contador).css("color", color);
}

function recalcularRepartidoresDisponibles() {

    // 1. Obtener TODOS los repartidores ya asignados a las OP seleccionadas
    const repartidoresOcupados = window.opSeleccionadas
        .map(o => Number(o.IdRepartidor))
        .filter(id => !isNaN(id)); // limpia null, undefined, 0

    console.log("Repartidores ocupados:", repartidoresOcupados);

    // 2. Filtrar desde el ORIGINAL SIEMPRE
    window.vrDisponibles = window.vrOriginales.filter(r =>
        !repartidoresOcupados.includes(Number(r.IdRepartidor))
    );

    console.log("Repartidores disponibles:", window.vrDisponibles);

    // 3. Renderizar
    renderRV(window.vrDisponibles);
}

function actualizarMinDiasRestantes() {
    // Si no hay órdenes seleccionadas → restaurar estado original
    if (!window.opSeleccionadas || window.opSeleccionadas.length === 0) {
        window.minDiasRestantesSeleccionados = null;

        console.log("🔹 Mínimo de días restantes: null");
        console.log("♻️ Restaurando repartidores originales...");

        // ✅ Restaurar las listas globales completas
        if (Array.isArray(window.vrOriginalesBackup)) {
            window.vrOriginales = [...window.vrOriginalesBackup];
            window.vrDisponibles = [...window.vrOriginalesBackup];
        } else {
            // Si aún no se ha guardado el respaldo, lo crea ahora
            window.vrOriginalesBackup = [...window.vrOriginales];
            window.vrDisponibles = [...window.vrOriginales];
        }

        // Renderizar todo el listado
        window.renderRV(window.vrDisponibles);
        console.log("✅ Tabla de repartidores actualizada con el filtro de null día(s).");
        return;
    }

    // Si hay órdenes seleccionadas → filtrar por el mínimo de días
    const dias = window.opSeleccionadas.map(o => o.DiasRestantes);
    window.minDiasRestantesSeleccionados = Math.min(...dias);


    console.log("🔹 Mínimo de días restantes:", window.minDiasRestantesSeleccionados);

    // Llamada AJAX para filtrar repartidores
    $.ajax({
        url: "../Ajax/CUS22/filtrarRepartidoresProxy.php",
        method: "POST",
        data: { dias_limite: window.minDiasRestantesSeleccionados },
        dataType: "json",
        success: function (response) {
            if (response.success) {
                // ✅ Hacer respaldo de los repartidores originales (solo la primera vez)
                if (!Array.isArray(window.vrOriginalesBackup)) {
                    window.vrOriginalesBackup = [...window.vrOriginales];
                }

                // Actualizar los arrays visibles
                window.vrOriginales = response.data;
                window.vrDisponibles = [...response.data];

                window.renderRV(window.vrDisponibles);
                console.log(`✅ Tabla de repartidores actualizada con el filtro de ${window.minDiasRestantesSeleccionados} día(s).`);
            } else {
                console.warn("⚠️ No se pudo actualizar la tabla de repartidores (sin éxito en respuesta).");
                window.renderRV([]);
            }
        },
        error: function (xhr, status, error) {
            console.error("❌ Error al filtrar repartidores:", xhr.responseText || error);
        }
    });
}

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
                  <td><span class="icon-remove-ose" data-id="${o.Codigo}" title="Quitar orden">&#10060;</span></td>
                </tr>
            `);
        });
    } else {
        tbody.append(`<tr><td colspan="8">No se han seleccionado órdenes de pedido.</td></tr>`);
    }

    while (tbody.find("tr").length < 5) {
        tbody.append(`<tr><td colspan="8">&nbsp;</td></tr>`);
    }

    actualizarContador();
};

// ===================================================
// Evento: Agregar Orden de Pedido
// ===================================================
$(document).on("click", ".icon-add-ose", function () {
    const id = $(this).data("id");

    const seleccionada = opOriginales.find(o => o.Codigo == id);
    if (!seleccionada) return;

    // Evitar duplicados
    if (window.opSeleccionadas.some(o => o.Codigo == id)) return;
    
    // == VALIDACIÓN DE ZONA ==
    if (window.opSeleccionadas.length === 0) {
        // Primera orden → define la zona permitida
        window.zonaSeleccionada = seleccionada.Zona;
        window.zonaSeleccionadaId = seleccionada.idZona;
        
    } else {
        if (seleccionada.Zona !== window.zonaSeleccionada) {
            showToast(`Solo se permiten seleccionar órdenes de la zona "${window.zonaSeleccionada}".`,'success');
            return;
        }
    }
    
    // == VALIDACIÓN DE PESO Y VOLUMEN ==
    const nuevoPeso = parseFloat(seleccionada.Peso);
    const nuevoVolumen = parseFloat(seleccionada.Volumen);

    const pesoActual = window.opSeleccionadas.reduce((sum, o) => sum + parseFloat(o.Peso), 0);
    const volumenActual = window.opSeleccionadas.reduce((sum, o) => sum + parseFloat(o.Volumen), 0);

    const pesoResultante = pesoActual + nuevoPeso;
    const volumenResultante = volumenActual + nuevoVolumen;

    const LIMITE_PESO = 1100;
    const LIMITE_VOLUMEN = CAPACIDAD_VOLUMEN;

    if (pesoResultante > LIMITE_PESO) {
        showToast(`La suma de peso excede el máximo permitido (${LIMITE_PESO} kg).`,'warning');
        return;
    }

    if (volumenResultante > LIMITE_VOLUMEN) {
        showToast(`La suma de volumen excede el máximo permitido (${LIMITE_VOLUMEN} m³).`,'warning');
        return;
    }
    
    // Agregar a seleccionadas
    window.opSeleccionadas.push(seleccionada);
    // Solo agregar dirección si no existe
    if (!window.waypointsConDistrito.some(w => w.direccion === seleccionada.Direccion)) {
        window.waypointsConDistrito.push({ direccion: seleccionada.Direccion, distrito: seleccionada.idDistrito});
    }
    renderOPSeleccionadas(window.opSeleccionadas);
    trazarRuta();
    recalcularRepartidoresDisponibles();
    //Mensaje 
    showToast("La orden fue seleccionada correctamente.",'success');
    // Quitar de la tabla principal
    opOriginales = opOriginales.filter(o => o.Codigo != id);
    renderOP(opOriginales);

    // Habilitar botones
    $("#btnGenerarOrden")
        .removeAttr("disabled")
        .removeClass("style-button-disabled")
        .addClass("style-button");

    $(".btn-disponibilidad-disabled")
        .removeAttr("disabled")
        .removeClass("btn-disponibilidad-disabled")
        .addClass("btn-disponibilidad");
});

// ===================================================
// Evento: Quitar Orden de Pedido
// ===================================================
$(document).on("click", ".icon-remove-ose", function () {
    const id = $(this).data("id");

    // Buscar la orden en seleccionadas
    const orden = window.opSeleccionadas.find(o => o.Codigo == id);
    if (!orden) return;

    // 1. Devolver al listado original
    opOriginales.push(orden);

    // 2. Quitar de seleccionadas
    window.opSeleccionadas = window.opSeleccionadas.filter(o => o.Codigo != id);

    // 3. Si no quedan seleccionadas → Resetear zona
    if (window.opSeleccionadas.length === 0) {
        window.zonaSeleccionada = null;

        // 7. Deshabilitar botones
        $("#btnGenerarOrden")
            .attr("disabled", true)
            .addClass("style-button-disabled")
            .removeClass("style-button");

        $(".btn-disponibilidad")
            .attr("disabled", true)
            .addClass("btn-disponibilidad-disabled")
            .removeClass("btn-disponibilidad");
    }

    // 4 y 5. Renderizar ambas tablas
    renderOP(opOriginales);
    renderOPSeleccionadas(window.opSeleccionadas);
    // Quitar del array de waypoints
    window.waypointsConDistrito = window.waypointsConDistrito.filter(w => w.direccion !== orden.Direccion);
    // 6. Contador
    actualizarContador();
    recalcularRepartidoresDisponibles();
    trazarRuta();
    // 8. Toast
    showToast("La orden fue retirada correctamente.",'success');
});

// ===================================================
// FILTRAR POR ZONA (sin recargar página)
// ===================================================
$(document).on("click", "#btnFiltrar", function (e) {
    e.preventDefault(); // evitar submit

    const zonaSeleccionadaFiltro = $("#zonasReparto").val();

    if (zonaSeleccionadaFiltro === "0") {
        showToast("Seleccione una zona para filtrar.",'info');
        return;
    }
    console.log("Filtro:", zonaSeleccionadaFiltro);
    console.log("Seleccionada:", window.zonaSeleccionada);

    // RESTRICCIÓN: Si ya hay órdenes seleccionadas,
    // solo se puede filtrar por la misma zona
    // -------------------------------------------------------
    if (window.zonaSeleccionadaId !== null && zonaSeleccionadaFiltro != window.zonaSeleccionadaId) {
    showToast(`Solo puedes filtrar por la zona "${window.zonaSeleccionada}" porque ya hay órdenes seleccionadas.`,'error');
    return;
}


    // Filtrar solo las órdenes que:
    // 1. pertenezcan a la zona seleccionada
    // 2. NO estén seleccionadas actualmente
    window.opFiltradas = opOriginales.filter(o =>
        String(o.idZona) === zonaSeleccionadaFiltro &&
        !window.opSeleccionadas.some(sel => sel.Codigo == o.Codigo)
    );

    renderOP(window.opFiltradas);
    showToast(`Filtro aplicado.`,'success');
});

$(document).on("click", "#btnVerTodo", function (e) {
    e.preventDefault();

    // Mostrar nuevamente todas las órdenes NO seleccionadas
    const listaSinSeleccionadas = opOriginales.filter(o =>
        !window.opSeleccionadas.some(sel => sel.Codigo == o.Codigo)
    );

    window.opFiltradas = [];
    renderOP(listaSinSeleccionadas);
    showToast(`Filtro eliminado.`,'success');
});

function trazarRuta() {
    const origen = window.direcAlmacen.DireccionOrigen;

    if (window.opSeleccionadas.length === 0) {
        $("#ruta").val(`Origen: ${origen}\nDestino: ${origen}`);
        return;
    }

    // Solo las direcciones únicas para la API
    const destinos = window.waypointsConDistrito.map(w => w.direccion).join("|");

    $.ajax({
        url: "../Ajax/CUS22/directionsProxy.php",
        data: { origin: origen, destination: origen, waypoints: destinos },
        dataType: "json",
        success: function(data) {
            if (data.status !== "OK") return console.error("Error en Directions API:", data);

            const route = data.routes[0];
            const waypointOrder = route.waypoint_order || [];
            const polyline = route.overview_polyline?.points || "No disponible";

            // Array final para BD
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
            waypointOrder.forEach(idx => { rutaTexto += `${window.waypointsConDistrito[idx].direccion}\n`; });
            rutaTexto += `Destino: ${origen}`;
            $("#ruta").val(rutaTexto);
            window.rutaGenerada = rutaArray;
            console.log("Array listo para BD:", rutaArray);
        },
        error: function(err) { console.error("Error al llamar al proxy:", err); }
    });
}

$(document).ready(() => {
    renderOP(opOriginales);
    renderOPSeleccionadas(window.opSeleccionadas);
    recalcularRepartidoresDisponibles();
});

