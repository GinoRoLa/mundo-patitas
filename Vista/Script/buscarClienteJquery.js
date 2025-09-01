let clienteBuscado = false;
$(function () {
    $('#buscarCliente').on('submit', function (e) {
        e.preventDefault();
        const dni = $("input[name='dniCliente']").val().trim();
        if (!/^\d+$/.test(dni)) {
            alert("El DNI solo debe contener números.");
            return;
        }
        if (dni.length !== 8) {
            alert("El DNI debe tener exactamente 8 dígitos.");
            return;
        }
        $.ajax({
            type: 'POST',
            url: '../Ajax/buscarClienteAjax.php',
            data: $(this).serialize(),
            success: function (res) {
                if (res.success) {
                    const c = res.cliente;
                    $('#nombreCliente').val(c.des_nombreCliente);
                    $('#telefonoCliente').val(c.num_telefonoCliente);
                    $('#apepatCliente').val(c.des_apepatCliente);
                    $('#apematCliente').val(c.des_apematCliente);
                    $('#emailCliente').val(c.email_cliente);
                    $('#direccionCliente').val(c.direccionCliente);
                    const formPreorden = $("#register-preorden");
                    if (formPreorden.find("input[name='idCliente']").length === 0) {
                        formPreorden.append(`<input type="hidden" name="idCliente" value="${c.Id_Cliente}">`);
                    } else {
                        formPreorden.find("input[name='idCliente']").val(c.Id_Cliente);
                    }
                    const btn = $(".button-add-product");
                    btn.prop("disabled", false);
                    btn.removeClass("style-button-disabled").addClass("style-button");
                    clienteBuscado = true;
                } else {
                    alert(res.message);
                    $('#nombreCliente, #telefonoCliente, #apepatCliente, #apematCliente, #emailCliente, #direccionCliente').val('');
                    const btn = $(".button-add-product");
                    btn.prop("disabled", true);
                    btn.removeClass("style-button").addClass("style-button-disabled");
                    clienteBuscado = false;
                }
            },
            error: function (xhr, status, error) {
                console.log("AJAX error:", status, error);
                console.log(xhr.responseText);
                alert('Error en la solicitud AJAX.');
                const btn = $(".button-add-product");
                btn.prop("disabled", true);
                btn.removeClass("style-button").addClass("style-button-disabled");
            }
        });
    });
});