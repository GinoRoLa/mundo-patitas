$(function() {
  $('#btnVerIncidencias').on('click', function() {
    $('#tablaIncidencias').toggle();
    const tbody = $('#tbodyIncidencias');
    tbody.empty();

    $.ajax({
      type: 'GET',
      url: '../Ajax/CUS26/listarIncidencias.php',
      dataType: 'json',
      success: function(res) {
        if (res.success && res.data.length > 0) {
          res.data.forEach(i => {
            tbody.append(`
              <tr>
                <td>${i.IDIncidenciaEntrega}</td>
                <td>${i.IDPedido}</td>
                <td>${i.Cliente}</td>
                <td>${i.Motivo}</td>
                <td>${i.Estado}</td>
                <td>${i.FechaIncidencia}</td>
              </tr>
            `);
          });
        } else {
          tbody.append(`<tr><td colspan="6">${res.message || 'No hay incidencias registradas.'}</td></tr>`);
        }
      },
      error: function(xhr, status, error) {
        console.error(xhr.responseText);
        alert('Error al cargar incidencias.');
      }
    });
  });
});
