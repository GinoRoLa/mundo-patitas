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
        // 🔹 Si el pedido está EN REPARTO → va en la tabla de reparto
        if (p.Estado === 'En reparto') {
          const filaReparto = `
            <tr>
              <td>${p.IDPedido}</td>
              <td>${p.Cliente}</td>
              <td>${p.Direccion}</td>
              <td>${p.Telefono}</td>
              <td>${p.Fecha}</td>
              <td>${p.DiasRestantes} día(s)</td>
              <td>${p.Estado}</td>
            </tr>`;
          reparto.append(filaReparto);
        }

        // 🔹 Si el pedido está NO ENTREGADO → va en la tabla de no entregado
        else if (p.Estado === 'No entregado') {
          const filaNoEntregado = `
            <tr data-id="${p.IDPedido}" data-cliente="${p.Cliente}" data-dir="${p.Direccion}">
              <td>${p.IDPedido}</td>
              <td>${p.Cliente}</td>
              <td>${p.Direccion}</td>
              <td>${p.Telefono}</td>
              <td>${p.Fecha}</td>
              <td><input type="radio" name="pedidoSel"></td>
            </tr>`;
          noEntregado.append(filaNoEntregado);
        }
      });

      // 🔸 Si no hay resultados en alguna tabla
      if (reparto.children().length === 0)
        reparto.append('<tr><td colspan="7">Sin pedidos en reparto</td></tr>');

      if (noEntregado.children().length === 0)
        noEntregado.append('<tr><td colspan="6">Sin pedidos no entregados</td></tr>');
    }, 'json');
  });

  // 🔹 Al seleccionar un pedido no entregado
  $(document).on('change', 'input[name="pedidoSel"]', function() {
    const fila = $(this).closest('tr');
    $('#txtIDPedido').val(fila.data('id'));
    $('#txtCliente').val(fila.data('cliente'));
    $('#txtDireccion').val(fila.data('dir'));
  });
});
