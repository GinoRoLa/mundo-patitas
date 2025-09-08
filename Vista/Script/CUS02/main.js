// /Vista/Script/CUS02/main.js
(function () {
  const { $, log, error } = window.Utils;

  window.addEventListener("DOMContentLoaded", () => {
    log("BOOT CUS02");
    window.Orden.cargarMetodosEntrega();

    $("#btnBuscar")?.addEventListener("click", window.Cliente.buscarCliente);
    $("#btnAgregar")?.addEventListener("click", window.Preorden.consolidar);
    $("#btnRegistrar")?.addEventListener("click", window.Orden.registrarOrden);
    $("#cboEntrega")?.addEventListener(
      "change",
      window.Orden.onMetodoEntregaChange
    );
    $("#txtDni")?.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        window.Cliente.buscarCliente();
      }
    });

    // Envío: cambios que requieren revalidar el botón Registrar
    const revalida = () => window.Orden.validarReadyParaRegistrar();

    document.querySelectorAll('input[name="envioModo"]').forEach((r) =>
      r.addEventListener("change", (e) => {
        window.Orden.setEnvioModo(e.target.value);
        window.Orden.validarReadyParaRegistrar();
        window.Utils.setDirty(true);
      })
    );

    [
      "#cboDireccionGuardada",
      "#envioNombre",
      "#envioTelefono",
      "#envioDireccion",
      "#chkGuardarDireccion",
    ].forEach((sel) => $(sel)?.addEventListener("input", revalida));

    const btnAgregar = $("#btnAgregar");
    if (btnAgregar) btnAgregar.disabled = true;

    window.addEventListener("error", (ev) =>
      error("window.error:", ev.message, ev.filename, ev.lineno)
    );
    window.addEventListener("unhandledrejection", (ev) =>
      error("unhandledrejection:", ev.reason)
    );

    // Teléfono: solo números + tope 9, y revalida
const tel = $("#envioTelefono");
if (tel) {
  tel.setAttribute("maxlength","9");
  tel.setAttribute("inputmode","numeric");
  tel.setAttribute("pattern","\\d{9}");   // ayuda a validadores del navegador

  tel.addEventListener("input", (e) => {
    const v = e.target.value;
    const n = v.replace(/\D/g, "").slice(0, 9);   // quita no-dígitos y recorta a 9
    if (v !== n) e.target.value = n;
    window.Orden.validarReadyParaRegistrar();
  });

  tel.addEventListener("blur", (e) => {
    // solo avisa si aplica: Delivery + "otra"
    const modo = document.querySelector('input[name="envioModo"]:checked')?.value || "otra";
    if (window.Orden.isDeliverySelected() && modo === "otra") {
      const ok = /^\d{9}$/.test(e.target.value);
      if (e.target.value !== "" && !ok) {
        window.Utils.msg("El teléfono debe tener exactamente 9 dígitos.", true);
      }
    }
  });
}

  });
})();
