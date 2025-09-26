// /Vista/Script/CUS02/main.js
(function () {
  const { $, log, error, setDirty, Messages } = window.Utils;

  document.addEventListener("DOMContentLoaded", () => {
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
    // limpiar mensajes por sección al tipear DNI
    $("#txtDni")?.addEventListener("input", () => {
      window.Utils.Messages.cliente.clear();
      window.Utils.Messages.preorden.clear();
    });

    // 5) Radios de modo de envío (guardada/otra)
    document.querySelectorAll('input[name="envioModo"]').forEach((r) => {
      r.addEventListener("change", (e) => {
        const modo = e.target.value; // guardada | otra
        window.Orden.setEnvioModo(modo);

        // Habilita/deshabilita el campo distrito según modo
        const dist = $("#envioDistrito");
        if (dist) {
          dist.disabled = (modo === "guardada");
        }

        window.Orden.validarReadyParaRegistrar();
        setDirty(true);
        Messages.preorden.clear();
      });
    });

    // 6) Inputs que afectan la validación de envío (cuando es delivery)
    const revalida = () => window.Orden.validarReadyParaRegistrar();
    [
      "#cboDireccionGuardada",
      "#envioNombre",
      "#envioTelefono",
      "#envioDireccion",
      "#envioReceptorDni",
      "#envioDistrito",
      "#chkGuardarDireccion",
    ].forEach((sel) => $(sel)?.addEventListener("input", revalida));

    // 6.1) Cuando cambie la dirección guardada, copia DNI y Distrito desde data-*
    $("#cboDireccionGuardada")?.addEventListener("change", (e) => {
      const opt = e.target.selectedOptions?.[0];
      if (!opt) return;
      const dni   = opt.dataset.dni  || "";
      const dist  = opt.dataset.dist || "";

      const dniRec = $("#envioReceptorDni");
      const distInp = $("#envioDistrito");

      if (dniRec && /^\d{8}$/.test(dni)) dniRec.value = dni;
      if (distInp) { distInp.value = dist; }

      // si estás en modo guardada, el distrito queda deshabilitado
      const modo = document.querySelector('input[name="envioModo"]:checked')?.value || "otra";
      if (distInp) distInp.disabled = (modo === "guardada");

      revalida();
    });

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
        const modo = document.querySelector('input[name="envioModo"]:checked')?.value || "otra";
        if (window.Orden.isDeliverySelected() && modo === "otra") {
          const ok = /^\d{9}$/.test(e.target.value);
          if (e.target.value !== "" && !ok) {
            Messages.preorden.error("El teléfono debe tener exactamente 9 dígitos.", { persist: true });
          }
        }
      });
    }

    // 7.1) Normalización de DNI de receptor: 8 dígitos
    const dniRec = $("#envioReceptorDni");
    if (dniRec) {
      dniRec.setAttribute("maxlength", "8");
      dniRec.setAttribute("inputmode", "numeric");
      dniRec.setAttribute("pattern", "\\d{8}");
      dniRec.addEventListener("input", (e) => {
        const v = (e.target.value || "").replace(/\D/g, "").slice(0, 8);
        if (e.target.value !== v) e.target.value = v;
        window.Orden.validarReadyParaRegistrar();
      });
    }

    // 8) Estado inicial de botones
    const btnAgregar = $("#btnAgregar");
    if (btnAgregar) btnAgregar.disabled = true;

    // 9) Confirmación nativa al pulsar "Salir"
    const btnSalir = document.getElementById("btnSalir");
    if (btnSalir) {
      const DESTINO = `${window.SERVICIOURL}/mundo-patitas/`;
      btnSalir.removeAttribute("onclick");
      btnSalir.onclick = null;
      btnSalir.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopImmediatePropagation();
        if (window.Utils.isDirty()) {
          const ok = window.confirm("¿Quieres salir de este sitio? Es posible que los cambios no se guarden.");
          if (!ok) return;
        }
        window.Utils.setDirty(false);
        window.location.assign(DESTINO);
      }, { capture: true });
    }

    // 10) Manejo global de errores
    window.addEventListener("error", (ev) =>
      error("window.error:", ev.message, ev.filename, ev.lineno)
    );
    window.addEventListener("unhandledrejection", (ev) =>
      error("unhandledrejection:", ev.reason)
    );
  });
})();
