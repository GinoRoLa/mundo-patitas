let calendar;

document.addEventListener('DOMContentLoaded', function () {
  const calendarEl = document.getElementById('calendar');

  calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    locale: 'es',
    height: 320,
    selectable: false,
    editable: false,
    headerToolbar: false,
    events: [],
    eventClick: () => false,
    dateClick: () => false,
    validRange: {
      start: new Date(new Date().getFullYear(), 0, 1),
      end: new Date(new Date().getFullYear(), 11, 31),
    },
    dayCellDidMount: function (info) {
      // 🔹 Calcula rango permitido (hoy + 4 días)
      const hoy = new Date();
      const fin = new Date();
      fin.setDate(hoy.getDate() + 4);

      const fechaCelda = info.date;

      // 🔹 Si la fecha está fuera del rango → añade clase de "deshabilitado"
      if (fechaCelda < hoy.setHours(0,0,0,0) || fechaCelda > fin.setHours(23,59,59,999)) {
        info.el.classList.add('fc-day-disabled');
      }
    }
  });

  calendar.render();

  // Botón "Ver disponibilidad"
  $(document).on('click', '.btn-disponibilidad', function () {
    const codAsignacion = $(this).data('id');
    if (codAsignacion) {
      cargarDisponibilidad(codAsignacion);
    }
  });
});


// ===========================================================
// 🔄 FUNCIÓN AJAX PARA CARGAR DISPONIBILIDAD
// ===========================================================
function cargarDisponibilidad(codAsignacion) {
    $.ajax({
        url: '../../Vista/Ajax/CUS22/verDisponibilidadRV.php',
        method: 'GET',
        data: { CodigoAsignacion: codAsignacion }, // 
        dataType: 'json',
        success: function (data) {
            calendar.removeAllEvents();

            // 🔹 Calculamos el rango dinámico (hoy + 4 días)
            const hoy = new Date();
            const rango = [];
            for (let i = 0; i < 5; i++) {
                const fecha = new Date(hoy);
                fecha.setDate(hoy.getDate() + i);
                rango.push(fecha.toISOString().split('T')[0]);
            }

            // 🔹 Generamos los eventos según la disponibilidad
            const eventos = rango.map(f => {
                // Verifica si la fecha f está en los días ocupados
                const ocupado = data.some(d => d.fecha === f);
                return {
                    title: ocupado ? 'Ocupado' : 'Disponible',
                    start: f,
                    backgroundColor: ocupado ? '#ff8a80' : '#b9f6ca',
                    borderColor: ocupado ? '#ff5252' : '#69f0ae',
                    display: 'background',
                };
            });

            // 🔹 Carga los eventos en el calendario
            calendar.addEventSource(eventos);

            // 🔹 Ir al primer día del rango (hoy)
            calendar.gotoDate(hoy);
        },
        error: function (xhr) {
            console.error(xhr.responseText);
            alert('Error al cargar disponibilidad.');
        }
    });
}
