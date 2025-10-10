$(function(){
  $('#cboDistrito').on('change', function(){
    const idDistrito = $(this).val();
    const t1 = $('#tablaReparto tbody');
    const t2 = $('#tablaNoEntregado tbody');
    t1.empty(); t2.empty();

    if(!idDistrito){
      t1.append('<tr><td colspan="6">Seleccione un distrito</td></tr>');
      t2.append('<tr><td colspan="6">Seleccione un distrito</td></tr>');
      return;
    }

    $.post('../Ajax/CUS26/buscarPedidosPorDistrito.php',{idDistrito},function(res){
      if(!res.success){ alert(res.message); return; }

      res.enReparto.forEach(p=>{
        t1.append(`<tr>
          <td>${p.IDPedido}</td><td>${p.Cliente}</td><td>${p.Direccion}</td>
          <td>${p.Telefono}</td><td>${p.FechaPedido}</td><td>${p.Estado}</td>
        </tr>`);
      });
      if(res.enReparto.length===0)
        t1.append('<tr><td colspan="6">Sin pedidos en reparto</td></tr>');

      res.noEntregado.forEach(p=>{
        t2.append(`<tr data-id="${p.IDPedido}" data-cliente="${p.Cliente}" data-dir="${p.Direccion}">
          <td>${p.IDPedido}</td><td>${p.Cliente}</td><td>${p.Direccion}</td>
          <td>${p.Telefono}</td><td>${p.FechaPedido}</td><td>${p.Estado}</td>
          <td><input type="radio" name="pedidoSeleccionado" value="${p.IDPedido}"></td>
        </tr>`);
      });
      if(res.noEntregado.length===0)
        t2.append('<tr><td colspan="7">Sin pedidos no entregados</td></tr>');
    },'json');
  });

  $(document).on('change','input[name="pedidoSeleccionado"]',function(){
    const fila=$(this).closest('tr');
    $('#txtIDPedido').val(fila.data('id'));
    $('#txtCliente').val(fila.data('cliente'));
    $('#txtDireccion').val(fila.data('dir'));
  });
});
