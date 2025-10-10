$(function() {
  $('#btnVerIncidencias').on('click', function() {
    $('#tablaIncidencias').toggle();
    const tbody = $('#tbodyIncidencias').empty();

    $.get('../Ajax/CUS26/listarIncidencias.php', res => {
      if (res.success && res.data.length) {
        res.data.forEach(r => {
          tbody.append(`
            <tr>
              <td>${r.IDIncidenciaEntrega}</td>
              <td>${r.IDPedido}</td>
              <td>${r.Cliente}</td>
              <td>${r.Motivo}</td>
              <td>${r.Estado}</td>
              <td>${r.FechaIncidencia}</td>
            </tr>`);
        });
      } else {
        tbody.append('<tr><td colspan="6">No hay incidencias registradas</td></tr>');
      }
    }, 'json');
  });
});
