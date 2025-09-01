$(document).on("change", "input[name='productoSeleccionado']", function() {
    const cantidadInput = $("#cantidadProducto");
    const stock = $(this).data("stock");

    if (this.checked) {
        cantidadInput.val(1);
        cantidadInput.attr("max", stock); 
    }
});
