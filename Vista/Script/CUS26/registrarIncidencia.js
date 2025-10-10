$(function(){
  $('#formIncidencia').on('submit',function(e){
    e.preventDefault();
    const form=$(this);
    $.post('../Ajax/CUS26/registrarIncidencia.php',form.serialize(),function(res){
      if(res.success){
        alert(res.message);
        const id=$('#txtIDPedido').val();
        $(`#tablaNoEntregado tr[data-id="${id}"]`).remove();
        form[0].reset();
      }else alert('Error: '+res.message);
    },'json');
  });
});
