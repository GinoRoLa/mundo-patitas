$(function () {

    $('#register-orden').on('submit', function (e) {
        e.preventDefault();
        // recolectar los ids de la tabla 2
        const ids = [];
        $("#table-body2 tr[data-id]").each(function () {
            ids.push($(this).data("id"));
        });

        if (ids.length === 0) {
            alert("Debe seleccionar al menos una orden.");
            return;
        }
        $.ajax({
            type: "POST",
            url: "../Ajax/CUS04/registarSalidaAlmacen.php",
            data: {listaOrdenes: JSON.stringify(ids)},
            dataType: "json",
            success: function (res) {
                if (res.success) {
                    alert("Salida de almacén registrada correctamente.");
                    $('#register-orden')[0].reset();
                    $(".generar-preorden-button")
                            .prop("disabled", true)
                            .removeClass("style-button")
                            .addClass("style-button-disabled");
                    ordenesSeleccionadas = [];
                    location.reload();
                } else {
                    alert("Error: " + res.message);
                }
            },
            error: function (xhr, status, error) {
                console.log("AJAX error:", status, error);
                console.log(xhr.responseText);
                alert("Error al registrar la salida de almacén.");
            }
        });
    });
});
