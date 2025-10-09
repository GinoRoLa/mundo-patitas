// Lista global de OSE
let oseOriginales = window.oseOriginales || [];
let oseDisponibles = [...oseOriginales];
let oseSeleccionadas = [];
let zonaSeleccionada = 0;

// Array global para rutas sin duplicados
window.waypointsConDistrito = []; // { direccion, distrito }

// ===================================================
// 🔹 Array global accesible desde otros JS
// ===================================================
window.ordenesSeleccionadasGlobal = [];

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
// 🔹 Actualizar contador dinámico
// ===================================================
function actualizarContador() {
    const pesoTotal = oseSeleccionadas.reduce((sum, o) => sum + parseFloat(o.Peso_Kg), 0);
    const volumenTotal = oseSeleccionadas.reduce((sum, o) => sum + parseFloat(o.Volumen_m3), 0);
    const cantidad = oseSeleccionadas.length;

    const porcentajePeso = (pesoTotal / 1100) * 100;
    const porcentajeVolumen = (volumenTotal / 8) * 100;
    const porcentajeUsado = Math.max(porcentajePeso, porcentajeVolumen);

    let color = "#28a745";
    if (porcentajeUsado >= 60 && porcentajeUsado < 90) color = "#f0ad4e";
    else if (porcentajeUsado >= 90) color = "#d9534f";

    const contador = `
        <span><strong>Órdenes seleccionadas:</strong> ${cantidad}</span> |
        <span><strong>Peso total:</strong> ${pesoTotal.toFixed(2)} kg / 1100 kg</span> |
        <span><strong>Volumen total:</strong> ${volumenTotal.toFixed(2)} m³ / 8 m³</span>
    `;

    $("#resumenSeleccion").html(contador).css("color", color);
}

// ===================================================
// 🔹 Render tablas
// ===================================================
window.renderOSE = function (lista) {
    const tbody = $("#table-body");
    tbody.empty();

    if (lista.length > 0) {
        lista.forEach(o => {
            tbody.append(`
                <tr>
                  <td>${String(o.Codigo_OSE).padStart(5, "0")}</td>
                  <td>${String(o.Codigo_OP).padStart(5, "0")}</td>
                  <td>${o.Distrito}</td>
                  <td>${o.Zona}</td>
                  <td>${parseFloat(o.Peso_Kg).toFixed(2)}</td>
                  <td>${parseFloat(o.Volumen_m3).toFixed(2)}</td>
                  <td>${o.Dias_Restantes}</td>
                  <td><span class="icon-add-ose" data-id="${o.Codigo_OSE}" title="Agregar orden">&#10133;</span></td>
                </tr>
            `);
        });
    } else {
        tbody.append(`<tr><td colspan="8">No se encontraron órdenes de servicio.</td></tr>`);
    }

    while (tbody.find("tr").length < 5) {
        tbody.append(`<tr><td colspan="8">&nbsp;</td></tr>`);
    }
};

window.renderOSESeleccionadas = function (lista) {
    const tbody = $("#table-body-rv-selectd");
    tbody.empty();

    if (lista.length > 0) {
        lista.forEach(o => {
            tbody.append(`
                <tr>
                  <td>${String(o.Codigo_OSE).padStart(5, "0")}</td>
                  <td>${String(o.Codigo_OP).padStart(5, "0")}</td>
                  <td>${o.Distrito}</td>
                  <td>${o.Zona}</td>
                  <td>${parseFloat(o.Peso_Kg).toFixed(2)}</td>
                  <td>${parseFloat(o.Volumen_m3).toFixed(2)}</td>
                  <td>${o.Dias_Restantes}</td>
                  <td><span class="icon-remove-ose" data-id="${o.Codigo_OSE}" title="Quitar orden">&#10060;</span></td>
                </tr>
            `);
        });
    }

    while (tbody.find("tr").length < 5) {
        tbody.append(`<tr><td colspan="8">&nbsp;</td></tr>`);
    }

    actualizarContador();
};

// ===================================================
// 🔹 Trazar ruta y generar array para BD
// ===================================================
function trazarRuta() {
    const origen = window.direcAlmacen.DireccionOrigen;

    if (oseSeleccionadas.length === 0) {
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

            console.log("Array listo para BD:", rutaArray);
        },
        error: function(err) { console.error("Error al llamar al proxy:", err); }
    });
}

// ===================================================
// 🔹 Agregar orden
// ===================================================
$(document).on("click", ".icon-add-ose", function () {
    const id = parseInt($(this).data("id"));
    const item = oseDisponibles.find(o => o.Codigo_OSE == id);
    if (!item) return;

    if (oseSeleccionadas.length > 0 && item.Zona !== oseSeleccionadas[0].Zona) {
        showToast("Solo puedes seleccionar órdenes de la misma zona.", "warning");
        return;
    }

    const pesoTotal = oseSeleccionadas.reduce((sum, o) => sum + parseFloat(o.Peso_Kg), 0) + parseFloat(item.Peso_Kg);
    const volumenTotal = oseSeleccionadas.reduce((sum, o) => sum + parseFloat(o.Volumen_m3), 0) + parseFloat(item.Volumen_m3);
    if (pesoTotal > 1100 || volumenTotal > 8) {
        showToast("No puedes exceder 8 m³ o 1100 kg en total.", "error");
        return;
    }

    oseSeleccionadas.push(item);
    oseDisponibles = oseDisponibles.filter(o => o.Codigo_OSE != id);
    showToast("Orden agregada correctamente.", "success");

    // Solo agregar dirección si no existe
    if (!window.waypointsConDistrito.some(w => w.direccion === item.Direccion)) {
        window.waypointsConDistrito.push({ direccion: item.Direccion, distrito: item.idDistrito});
    }
    
    // 👇 Agregar también al array global
    if (!window.ordenesSeleccionadasGlobal.some(o => o.Codigo_OSE == item.Codigo_OSE)) {
        window.ordenesSeleccionadasGlobal.push(item);
    }
    console.log("🟢 Global actualizado (agregar):", window.ordenesSeleccionadasGlobal);
    aplicarFiltroActual();
    renderOSESeleccionadas(oseSeleccionadas);
    trazarRuta();
});

// ===================================================
// 🔹 Quitar orden (ajustado para orden correcto)
// ===================================================
$(document).on("click", ".icon-remove-ose", function () {
    const id = parseInt($(this).data("id"));
    const item = oseSeleccionadas.find(o => o.Codigo_OSE == id);
    if (!item) return;

    // Quitar de seleccionadas
    oseSeleccionadas = oseSeleccionadas.filter(o => o.Codigo_OSE != id);

    // Devolver al final del array y luego reordenar
    oseDisponibles.push(item);
    oseDisponibles.sort((a, b) => a.Codigo_OSE - b.Codigo_OSE);

    showToast("Orden devuelta a la lista disponible.", "info");

    // Quitar del array de waypoints
    window.waypointsConDistrito = window.waypointsConDistrito.filter(w => w.direccion !== item.Direccion);
    
    // 👇 Quitar también del array global
    window.ordenesSeleccionadasGlobal = window.ordenesSeleccionadasGlobal.filter(o => o.Codigo_OSE != id);
    console.log("🔴 Global actualizado (quitar):", window.ordenesSeleccionadasGlobal);
    
    aplicarFiltroActual();
    renderOSESeleccionadas(oseSeleccionadas);
    trazarRuta();
});


// ===================================================
// 🔹 Filtro por zona (ajustado)
// ===================================================
function aplicarFiltroActual() {
    // Asegurar que el filtro compare correctamente
    const zona = parseInt(zonaSeleccionada);

    let listaFiltrada = oseDisponibles.filter(o => 
        !oseSeleccionadas.some(s => s.Codigo_OSE == o.Codigo_OSE)
    );

    if (zona && zona !== 0) {
        listaFiltrada = listaFiltrada.filter(o => parseInt(o.idZona) === zona);
    }

    // Ordenar por Código OSE para mantener orden lógico
    listaFiltrada.sort((a, b) => a.Codigo_OSE - b.Codigo_OSE);

    renderOSE(listaFiltrada);
}

// ✅ Manejo del formulario de filtro
$(document).on("submit", ".filtroOSE", e => {
    e.preventDefault();
    zonaSeleccionada = parseInt($("#zonasReparto").val());
    aplicarFiltroActual();
});

// ✅ Botón "Ver todo"
$(document).on("click", ".botonesFiltro .style-button:nth-child(2)", e => {
    e.preventDefault();
    zonaSeleccionada = 0;
    $("#zonasReparto").val("0");
    aplicarFiltroActual();
});

// ===================================================
// 🔹 Inicialización
// ===================================================
$(document).ready(() => {
    renderOSE(oseDisponibles);
    renderOSESeleccionadas(oseSeleccionadas);
});
