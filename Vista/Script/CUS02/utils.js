// /Vista/Script/CUS02/utils.js
(function () {
  const DEBUG = true;
  const log = (...a) => DEBUG && console.log("[CUS02]", ...a);
  const warn = (...a) => DEBUG && console.warn("[CUS02]", ...a);
  const error = (...a) => DEBUG && console.error("[CUS02]", ...a);

  const $  = (s) => document.querySelector(s);
  const $$ = (s) => document.querySelectorAll(s);

  function validarDni(v) {
    const raw = (v || "").trim();
    if (raw === "") return { ok: false, msg: "Ingrese DNI (8 dígitos numéricos)." };
    if (/[^0-9]/.test(raw)) return { ok: false, msg: "Ingrese dato numérico (DNI de 8 dígitos)." };
    if (raw.length !== 8) return { ok: false, msg: "DNI incompleto: deben ser 8 dígitos." };
    return { ok: true };
  }

  function to2(n) {
    const x = Number(n);
    return Number.isFinite(x) ? x.toFixed(2) : "0.00";
  }
  function setNum(el, val) { if (el) el.value = to2(val); }

  function msg(texto = "", isError = false) {
    const m = $("#msg");
    if (!m) return;
    m.textContent = texto;
    m.className = "msg" + (texto ? (isError ? " error" : " ok") : "");
    if (texto) m.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  // === Dirty flag global (hubo acciones/cambios no guardados) ===
  let _dirty = false;
  function setDirty(v = true) { _dirty = !!v; }
  function isDirty() { return _dirty; }

  window.Utils = { $, $$, log, warn, error, validarDni, to2, setNum, msg, setDirty, isDirty };
})();
