// /Vista/Script/CUS24/main.js
(function () {
  const $ = (sel, ctx = document) => ctx.querySelector(sel);

  /* ========= Dirty state (fallback si no existe en Utils/Utils24) ========= */
  const Dirty = (() => {
    const api =
      (window.Utils && window.Utils.isDirty && window.Utils.setDirty && window.Utils) ||
      (window.Utils24 && window.Utils24.isDirty && window.Utils24.setDirty && window.Utils24) ||
      null;

    if (api) return { isDirty: () => !!api.isDirty(), setDirty: (v) => api.setDirty(!!v) };
    let _dirty = false;
    return { isDirty: () => _dirty, setDirty: (v) => (_dirty = !!v) };
  })();

  /* ========= URL de salida ========= */
  function getDestinoBase() {
    const base = window.SERVICIOURL || ""; // ya lo usas en otros módulos
    return base ? `${base}/mundo-patitas/` : "../../";
  }

  /* ========= Botón Salir ========= */
  function wireBtnSalir() {
    const btnSalir = $("#btnSalir");
    if (!btnSalir) return;

    const DESTINO = getDestinoBase();
    btnSalir.removeAttribute("onclick");
    btnSalir.onclick = null;

    btnSalir.addEventListener(
      "click",
      (e) => {
        e.preventDefault();
        e.stopImmediatePropagation();
        if (Dirty.isDirty()) {
          const ok = window.confirm("¿Quieres salir de este sitio? Es posible que los cambios no se guarden.");
          if (!ok) return;
        }
        Dirty.setDirty(false);
        window.location.assign(DESTINO);
      },
      { capture: true }
    );
  }

  /* ========= Advertir al intentar cerrar/recargar (opcional) ========= */
  function wireBeforeUnload() {
    window.addEventListener("beforeunload", (ev) => {
      if (!Dirty.isDirty()) return;
      ev.preventDefault();
      ev.returnValue = "";
    });
  }

  /* ========= Errores globales ========= */
  function wireGlobalErrorHandlers() {
    const logErr = (...args) => console.error("[CUS24]", ...args);
    window.addEventListener("error", (ev) => logErr("window.error:", ev.message, ev.filename, ev.lineno, ev.colno));
    window.addEventListener("unhandledrejection", (ev) => logErr("unhandledrejection:", ev.reason));
  }

  /* ========= Validación de #txtAsignacion + botón Buscar ========= */
  function wireAsignacionValidation() {
    const input = $("#txtAsignacion");
    const msgEl = $("#msgAsignacion");
    const btn = $("#btnBuscar");
    if (!input || !window.Utils24) return;

    // vincula reglas (pinta borde rojo y mensaje)
    window.Utils24.bindNumericValidation("#txtAsignacion", "#msgAsignacion", { required: true });

    // habilita/deshabilita botón según validación
    const updateBtnState = () => {
      const { ok } = window.Utils24.validateNumericInput(input, msgEl, { required: true });
      if (btn) btn.disabled = !ok;
    };
    input.addEventListener("input", updateBtnState);
    input.addEventListener("blur", updateBtnState);
    updateBtnState(); // estado inicial
  }

  /* ========= Init ========= */
  function init() {
    wireBtnSalir();
    wireBeforeUnload();           // quítalo si no quieres aviso al recargar/cerrar
    wireGlobalErrorHandlers();
    wireAsignacionValidation();
  }

  document.addEventListener("DOMContentLoaded", init);

  // Export opcional
  window.MainCUS24 = { setDirty: Dirty.setDirty, isDirty: Dirty.isDirty };
})();
