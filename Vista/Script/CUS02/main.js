// /Vista/Script/CUS02/main.js
(function () {
  const { $, log, error, setDirty, msg } = window.Utils;

  window.addEventListener("DOMContentLoaded", () => {
    log("BOOT CUS02");

    // 1) Cargar métodos de entrega y preparar estado inicial
    window.Orden.cargarMetodosEntrega();

    // 2) Acciones principales
    $("#btnBuscar")?.addEventListener("click", window.Cliente.buscarCliente);
    $("#btnAgregar")?.addEventListener("click", window.Preorden.consolidar);
    $("#btnRegistrar")?.addEventListener("click", window.Orden.registrarOrden);

    // 3) Cambio de método de entrega (recalcula costos + muestra/oculta panel)
    $("#cboEntrega")?.addEventListener("change", window.Orden.onMetodoEntregaChange);

    // 4) Enter en DNI dispara búsqueda
    $("#txtDni")?.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        window.Cliente.buscarCliente();
      }
    });

    // 5) Radios de modo de envío (guardada/otra)
    document.querySelectorAll('input[name="envioModo"]').forEach((r) => {
      r.addEventListener("change", (e) => {
        window.Orden.setEnvioModo(e.target.value);
        window.Orden.validarReadyParaRegistrar();
        setDirty(true);
      });
    });

    // 6) Inputs que afectan la validación de envío (cuando es delivery)
    const revalida = () => window.Orden.validarReadyParaRegistrar();
    [
      "#cboDireccionGuardada",
      "#envioNombre",
      "#envioTelefono",
      "#envioDireccion",
      "#chkGuardarDireccion",
    ].forEach((sel) => $(sel)?.addEventListener("input", revalida));

    // 7) Normalización de teléfono: sólo dígitos y máximo 9
    const tel = $("#envioTelefono");
    if (tel) {
      tel.setAttribute("maxlength", "9");
      tel.setAttribute("inputmode", "numeric");
      tel.setAttribute("pattern", "\\d{9}");
      tel.addEventListener("input", (e) => {
        const v = e.target.value;
        const n = v.replace(/\D/g, "").slice(0, 9);
        if (v !== n) e.target.value = n;
        revalida();
      });
      tel.addEventListener("blur", (e) => {
        const modo =
          document.querySelector('input[name="envioModo"]:checked')?.value ||
          "otra";
        if (window.Orden.isDeliverySelected() && modo === "otra") {
          const ok = /^\d{9}$/.test(e.target.value);
          if (e.target.value !== "" && !ok) {
            msg("El teléfono debe tener exactamente 9 dígitos.", true);
          }
        }
      });
    }

    // 8) Estado inicial de botones
    const btnAgregar = $("#btnAgregar");
    if (btnAgregar) btnAgregar.disabled = true;

    // 9) Manejo global de errores
    window.addEventListener("error", (ev) =>
      error("window.error:", ev.message, ev.filename, ev.lineno)
    );
    window.addEventListener("unhandledrejection", (ev) =>
      error("unhandledrejection:", ev.reason)
    );
  });
})();
