// /Vista/Script/CUS02/main.js
(function () {
  const { $, log, error, isDirty } = window.Utils;

  window.addEventListener("DOMContentLoaded", () => {
    log("BOOT CUS02");
    window.Orden.cargarMetodosEntrega();

    $("#btnBuscar")?.addEventListener("click", window.Cliente.buscarCliente);
    $("#btnAgregar")?.addEventListener("click", window.Preorden.consolidar);
    $("#btnRegistrar")?.addEventListener("click", window.Orden.registrarOrden);
    $("#cboEntrega")?.addEventListener("change", window.Orden.onMetodoEntregaChange);
    $("#txtDni")?.addEventListener("keydown", (e) => {
      if (e.key === "Enter") { e.preventDefault(); window.Cliente.buscarCliente(); }
    });

    // Interceptar "Salir" y advertir si hay cambios pendientes
    const btnSalir = $("#btnSalir");
    if (btnSalir) {
      // anula cualquier onclick inline del HTML
      btnSalir.onclick = null;
      btnSalir.addEventListener("click", (e) => {
        if (isDirty()) {
          // Usa confirm para dar opción de cancelar
          const seguir = window.confirm("Es posible que no se guarden los cambios realizados.\n¿Desea salir igualmente?");
          if (!seguir) return; // se queda en la página
        }
        // navegar (ajusta la ruta si quieres otra)
        window.location.href = "/";
      });
    }

    const btnAgregar = $("#btnAgregar");
    if (btnAgregar) btnAgregar.disabled = true;

    window.addEventListener("error", (ev) => error("window.error:", ev.message, ev.filename, ev.lineno));
    window.addEventListener("unhandledrejection", (ev) => error("unhandledrejection:", ev.reason));
  });
})();
