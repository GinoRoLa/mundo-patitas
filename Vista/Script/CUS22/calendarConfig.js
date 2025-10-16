// ===========================================================
// VARIABLES GLOBALES
// ===========================================================
let calendar;
let filaActiva = null;
let fechaSeleccionada = null;
let repartidorActivo = null;

if (typeof window.vrOriginales === "undefined") window.vrOriginales = [];
if (typeof window.vrDisponibles === "undefined") window.vrDisponibles = [];
if (typeof window.vrSeleccionado === "undefined") window.vrSeleccionado = [];

// 🔹 NUEVO: Array global para guardar la fecha seleccionada y otros datos
if (typeof window.fechaSeleccionGlobal === "undefined") window.fechaSeleccionGlobal = []; 
// Estructura esperada: [{ fecha: "2025-10-10", idRepartidor: 101, idAsignacion: 12 }]

// ===========================================================
// FUNCIÓN AUXILIAR
// ===========================================================
function formatLocalDate(d) {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
}

// ===========================================================
// FULL CALENDAR INIT
// ===========================================================
document.addEventListener("DOMContentLoaded", function () {
  const calendarEl = document.getElementById("calendar");

  window.calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: "dayGridMonth",
    locale: "es",
    height: 320,
    selectable: false,
    editable: false,
    headerToolbar: false,
    events: [],
    validRange: {
      start: new Date(new Date().getFullYear(), 0, 1),
      end: new Date(new Date().getFullYear(), 11, 31),
    },
    dayCellDidMount: function (info) {
      const hoy = new Date();
      const fin = new Date(hoy);
      fin.setDate(hoy.getDate() + 4);
      const fechaCeldaStr = formatLocalDate(info.date);
      const hoyStr = formatLocalDate(hoy);
      const finStr = formatLocalDate(fin);

      if (fechaCeldaStr < hoyStr || fechaCeldaStr > finStr) {
        info.el.classList.add("fc-day-disabled");
      }
    },
  });

  window.calendar.render();

  // ----------------------
  // BOTÓN "VER DISPONIBILIDAD"
  // ----------------------
  $(document).on("click", ".btn-disponibilidad", function () {
    if (window.vrSeleccionado.length > 0) {
      if (typeof showToast === "function")
        showToast("Ya hay un repartidor seleccionado. Primero cambia el repartidor.", "warning");
      return;
    }

    const codAsignacion = $(this).data("id");
    if (!codAsignacion) return;

    if (filaActiva) $(filaActiva).removeClass("fila-seleccionada");
    filaActiva = $(this).closest("tr");
    filaActiva.addClass("fila-seleccionada");

    const all = [...window.vrOriginales];
    repartidorActivo = all.find(r => String(r.CodigoAsignacion) === String(codAsignacion));
    fechaSeleccionada = null;

    cargarDisponibilidad(codAsignacion);
  });

  // ----------------------
  // BOTÓN "CAMBIAR REPARTIDOR"
  // ----------------------
  $(document).on("click", ".button-change", function () {
    if (!repartidorActivo) {
      if (typeof showToast === "function") showToast("No hay repartidor seleccionado.", "info");
      return;
    }

    window.calendar.removeAllEvents();
    window.calendar.gotoDate(new Date());
    $("#nombreCliente, #telefonoCliente, #apepatCliente, #apematCliente, #emailCliente").val("");
    if (filaActiva) $(filaActiva).removeClass("fila-seleccionada");

    const idx = window.vrSeleccionado.findIndex(x => x.CodigoAsignacion === repartidorActivo.CodigoAsignacion);
    if (idx >= 0) window.vrSeleccionado.splice(idx, 1);

    if (!window.vrDisponibles.some(x => x.CodigoAsignacion === repartidorActivo.CodigoAsignacion)) {
      window.vrDisponibles.push(repartidorActivo);
    }

    window.vrDisponibles.sort((a, b) => a.CodigoAsignacion - b.CodigoAsignacion);

    if (typeof renderRV === "function") renderRV(window.vrDisponibles);

    $(".btn-disponibilidad").prop("disabled", false);

    // 🔹 NUEVO: eliminar del array global si existía una fecha registrada
    window.fechaSeleccionGlobal = window.fechaSeleccionGlobal.filter(
      f => f.idAsignacion !== repartidorActivo.CodigoAsignacion
    );

    repartidorActivo = null;
    fechaSeleccionada = null;

    if (typeof showToast === "function") showToast("Repartidor liberado y tabla actualizada.", "info");
  });
});

// ===========================================================
// FUNCIÓN: CARGAR DISPONIBILIDAD Y MANEJO DE FECHAS
// ===========================================================
function cargarDisponibilidad(codAsignacion) {
  $.ajax({
    url: "../../Vista/Ajax/CUS22/verDisponibilidadRV.php",
    method: "GET",
    data: { CodigoAsignacion: codAsignacion },
    dataType: "json",
    success: function (data) {
      window.calendar.removeAllEvents();
      const hoy = new Date();
      const rango = [];
      for (let i = 0; i < 5; i++) {
        const fecha = new Date(hoy);
        fecha.setDate(hoy.getDate() + i);
        rango.push(formatLocalDate(fecha));
      }

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

      // -------- CLICK EN FECHA DISPONIBLE --------
      window.calendar.setOption("dateClick", function (info) {
        const fechaClick = formatLocalDate(info.date);

        const evs = window.calendar.getEvents().filter(ev => formatLocalDate(ev.start) === fechaClick);
        const disponible = evs.some(ev => ev.title.toLowerCase() === "disponible");
        if (!disponible) {
          if (typeof showToast === "function") showToast("Fecha no disponible", "warning");
          return;
        }

        if (fechaSeleccionada && fechaSeleccionada !== fechaClick) {
          const confirmar = confirm(`Ya seleccionaste ${fechaSeleccionada}. ¿Deseas cambiar por ${fechaClick}?`);
          if (!confirmar) return;
        }

        fechaSeleccionada = fechaClick;

        $("#nombreCliente").val(String(repartidorActivo.CodigoRepartidor).padStart(5, "0"));
        $("#telefonoCliente").val(repartidorActivo.Placa || "");
        $("#apepatCliente").val(repartidorActivo.Marca || "");
        $("#apematCliente").val(repartidorActivo.Modelo || "");
        $("#emailCliente").val(fechaClick);

        if (!window.vrSeleccionado.some(x => x.CodigoAsignacion === repartidorActivo.CodigoAsignacion)) {
          window.vrSeleccionado.push(repartidorActivo);
        }

        window.vrDisponibles = window.vrOriginales.filter(
          x => !window.vrSeleccionado.some(s => s.CodigoAsignacion === x.CodigoAsignacion)
        );

        if (typeof renderRV === "function") renderRV(window.vrDisponibles);
        $(".btn-disponibilidad").prop("disabled", true);

        // 🔹 NUEVO: guardar o actualizar la fecha seleccionada en el array global
        const existente = window.fechaSeleccionGlobal.find(f => f.idAsignacion === repartidorActivo.CodigoAsignacion);
        if (existente) {
          existente.fecha = fechaClick;
        } else {
          window.fechaSeleccionGlobal.push({
            fecha: fechaClick,
            idRepartidor: repartidorActivo.CodigoRepartidor,
            idAsignacion: repartidorActivo.CodigoAsignacion
          });
        }
        
        console.log("📅 Array global de fechas seleccionadas:", window.fechaSeleccionGlobal);
        if (typeof showToast === "function")
          showToast(`Fecha ${fechaClick} seleccionada para repartidor ${repartidorActivo.CodigoRepartidor}`, "success");
      });
    },
    error: function (xhr) {
      console.error("Error AJAX verDisponibilidadRV:", xhr.responseText || xhr.statusText);
      alert("Error al cargar disponibilidad (ver consola).");
    }
  });
}
