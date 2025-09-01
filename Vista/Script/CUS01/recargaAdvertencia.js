window.addEventListener("beforeunload", function (e) {
    if (clienteBuscado) {
        e.preventDefault();
        e.returnValue = "Si recargas la página, se perderá la información del cliente.";
    }
});