$(function () {
    $("#register-preorden").on("submit", function (e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: "../../Vista/Ajax/CUS01/registrarPreorden.php",
            data: {
                productos: JSON.stringify(productosSeleccionados),
                idCliente: $("#register-preorden input[name='idCliente']").val()
            },
            dataType: "json",
            success: function (respuesta) {
                if (respuesta.success) {
                    alert(respuesta.message);
                    clienteBuscado = false;
                    location.reload();
                } else {
                    alert("Error: " + respuesta.message);
                }
            },
            error: function () {
                alert("Error al crear la PreOrden");
            }
        });
    });
});
