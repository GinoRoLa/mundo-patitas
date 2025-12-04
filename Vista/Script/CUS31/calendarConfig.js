document.addEventListener("DOMContentLoaded", function () {
  const calendarEl = document.getElementById("calendar");

  window.calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: "dayGridMonth",
    locale: "es",
    height: 320,
    selectable: true,
    editable: false,
    headerToolbar: false,
    events: [],
    validRange: {
      start: new Date(new Date().getFullYear(), 0, 1),
      end: new Date(new Date().getFullYear(), 11, 31),
    },

    // 🔹 RANGO DINÁMICO
    dayCellDidMount: function (info) {
      const hoy = new Date();
      hoy.setHours(0, 0, 0, 0);
      
      // ✅ USAR minDiasRestantesSeleccionados o 3 por defecto
      const diasMax = window.minDiasRestantesSeleccionados !== null ? 
                      window.minDiasRestantesSeleccionados : 3;
      
      const fin = new Date(hoy);
      fin.setDate(hoy.getDate() + diasMax);  // ✅ CORREGIDO: + diasMax
      fin.setHours(0, 0, 0, 0);

      const fechaCelda = new Date(info.date);
      fechaCelda.setHours(0, 0, 0, 0);

      if (fechaCelda < hoy || fechaCelda > fin) {
        info.el.classList.add("fc-day-disabled");
      }
    },

    // 🔹 Selección dinámica
    selectAllow: function (info) {
      const hoy = new Date();
      hoy.setHours(0, 0, 0, 0);
      
      const diasMax = window.minDiasRestantesSeleccionados !== null ? 
                      window.minDiasRestantesSeleccionados : 3;
      
      const fin = new Date(hoy);
      fin.setDate(hoy.getDate() + diasMax);  // ✅ CORREGIDO: + diasMax
      fin.setHours(0, 0, 0, 0);

      const fecha = new Date(info.start);
      fecha.setHours(0, 0, 0, 0);

      return fecha >= hoy && fecha <= fin;
    }
  });

  window.calendar.render();
  
  // ✅ FUNCIÓN GLOBAL para actualizar rango
  window.actualizarRangoCalendario = function() {
    window.calendar.render();  // ✅ Solo render() es suficiente
  };
});
