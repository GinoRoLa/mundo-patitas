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

    // 🔹 DESHABILITAR TODO MENOS LOS PRÓXIMOS 5 DÍAS
    dayCellDidMount: function (info) {
      const hoy = new Date();
      hoy.setHours(0, 0, 0, 0);

      const fin = new Date();
      fin.setDate(hoy.getDate() + 3); // hoy + 4 = total 5 días
      fin.setHours(0, 0, 0, 0);

      const fechaCelda = new Date(info.date);
      fechaCelda.setHours(0, 0, 0, 0);

      // ❌ Si la celda no está dentro del rango permitido, se deshabilita
      if (fechaCelda < hoy || fechaCelda > fin) {
        info.el.classList.add("fc-day-disabled");
      }
    },

    // 🔹 Solo permitir selección dentro del rango válido
    selectAllow: function (info) {
      const hoy = new Date();
      hoy.setHours(0, 0, 0, 0);

      const fin = new Date();
      fin.setDate(hoy.getDate() + 3);
      fin.setHours(0, 0, 0, 0);

      const fecha = new Date(info.start);
      fecha.setHours(0, 0, 0, 0);

      return fecha >= hoy && fecha <= fin;
    }
  });

  window.calendar.render();
});
