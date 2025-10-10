$(function(){
    $("#cboDistrito").on("change", function(){
        const idDistrito = $(this).val();
        if(!idDistrito) return;
        $.ajax({
            url: "../Ajax/CUS26/buscarPedidosPorDistrito.php",
            type: "POST",
            data: { idDistrito },
            dataType: "json",
            success: function(res){
                const tbody = $("#tablaPedidos tbody");
                tbody.empty();
                if(res.data.length === 0){
                    tbody.append("<tr><td colspan='5'>No hay pedidos para este distrito.</td></tr>");
                    return;
                }
                res.data.forEach(p => {
                    tbody.append(`
                        <tr data-id="${p.IDPedido}" data-cliente="${p.Cliente}" data-dir="${p.Direccion}">
                            <td>${p.IDPedido}</td>
                            <td>${p.Cliente}</td>
                            <td>${p.Telefono}</td>
                            <td>${p.Direccion}</td>
                            <td>${p.Estado}</td>
                        </tr>
                    `);
                });
            },
            error: () => alert("Error al cargar pedidos.")
        });
    });

    $(document).on("click", "#tablaPedidos tr[data-id]", function(){
        $("#IDPedido").val($(this).data("id"));
        $("#Cliente").val($(this).data("cliente"));
        $("#Direccion").val($(this).data("dir"));
    });
});
