$(function() {
  $('#btnBuscar').on('click', function() {
    const idDistrito = $('#txtCodigoDistrito').val().trim();
    if (!idDistrito) {
      alert("Ingrese un código de distrito.");
      return;
    }

    $.post('../Ajax/CUS26/buscarPedidosPorDistrito.php', { idDistrito }, function(res) {
      if (!res.success) return alert(res.message);

      const reparto = $('#tablaReparto tbody').empty();
      const noEntregado = $('#tablaNoEntregado tbody').empty();

      res.pedidos.forEach(p => {
        const fila = `
          <tr data-id="${p.IDPedido}" data-cliente="${p.Cliente}" data-dir="${p.Direccion}">
            <td>${p.IDPedido}</td><td>${p.Cliente}</td><td>${p.Direccion}</td>
            <td>${p.Telefono}</td><td>${p.Fecha}</td><td>${p.Estado}</td>
            ${p.Estado === 'No entregado'
              ? '<td><input type="radio" name="pedidoSel"></td>' : ''}
          </tr>`;
        if (p.Estado === 'En reparto') reparto.append(fila);
        else noEntregado.append(fila);
      });
    }, 'json');
  });

  $(document).on('change', 'input[name="pedidoSel"]', function() {
    const fila = $(this).closest('tr');
    $('#txtIDPedido').val(fila.data('id'));
    $('#txtCliente').val(fila.data('cliente'));
    $('#txtDireccion').val(fila.data('dir'));
  });
});
