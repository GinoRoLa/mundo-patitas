$(function () {

    $('#register-orden').on('submit', function (e) {
        e.preventDefault();

        const idOrden = $("input[name='codigoOrdenHidden']").val();

        if (!idOrden) {
            alert("No se encontró un código de orden válido.");
            return;
        }

        $.ajax({
            type: "POST",
            url: "../Ajax/CUS04/registarSalidaAlmacen.php",
            data: { idOrden: idOrden },
            dataType: "json",
            success: function (res) {
                if (res.success) {
                    alert("Salida de almacén registrada correctamente.");
                    $('#register-orden')[0].reset();
                    $(".generar-preorden-button")
                        .prop("disabled", true)
                        .removeClass("style-button")
                        .addClass("style-button-disabled");
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
