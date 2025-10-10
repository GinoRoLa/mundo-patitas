$(function() {
  $('#formIncidencia').on('submit', function(e) {
    e.preventDefault();

    const datos = new FormData(this);

    $.ajax({
      url: '../Ajax/CUS26/registrarIncidencia.php',
      type: 'POST',
      data: datos,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function(res) {
        alert(res.message);
        if (res.success) {
          $('#formIncidencia')[0].reset();
          $('#txtIDPedido,#txtCliente,#txtDireccion').val('');
          $('#btnBuscar').trigger('click'); // refresca la tabla
        }
      }
    });
  });
});
