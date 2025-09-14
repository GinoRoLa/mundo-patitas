// /Vista/Script/CUS02/utils.js
(function () {
  const DEBUG = true;
  const log   = (...a) => DEBUG && console.log("[CUS02]", ...a);
  const warn  = (...a) => DEBUG && console.warn("[CUS02]", ...a);
  const error = (...a) => DEBUG && console.error("[CUS02]", ...a);

  const $  = (s) => document.querySelector(s);
  const $$ = (s) => document.querySelectorAll(s);

  // ================== Validaciones básicas ==================
  function validarDni(v) {
    const raw = (v || "").trim();
    if (raw === "")        return { ok: false, msg: "Ingrese DNI (8 dígitos numéricos)." };
    if (/[^0-9]/.test(raw))return { ok: false, msg: "Ingrese dato numérico (DNI de 8 dígitos)." };
    if (raw.length !== 8)  return { ok: false, msg: "DNI incompleto: deben ser 8 dígitos." };
    return { ok: true };
  }

  function to2(n) {
    const x = Number(n);
    return Number.isFinite(x) ? x.toFixed(2) : "0.00";
  }
  function setNum(el, val) { if (el) el.value = to2(val); }

  // ================== Mensaje global (legacy) ==================
  function msg(texto = "", isError = false) {
    const m = $("#msg");
    if (!m) return;
    m.textContent = texto;
    m.className = "msg" + (texto ? (isError ? " error" : " ok") : "");
    if (texto) m.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  // ================== Mensajes por sección (con autocierre) ==================
  const _timers = new WeakMap();

// ====== helpers de mensajes suaves, sin layout shift ======
function _applyMsg(el, text, kind){
  // No hacemos scroll para evitar saltos en la página
  el.textContent = text || "";
  // base: "msg", sumamos "ok" | "error" si aplica
  el.className = "msg" + (kind ? " " + kind : "");
  if (text) {
    el.classList.remove("hiding");
    el.classList.add("show");
  } else {
    // si te llaman con "" usamos softHide para animar salida
    softHide(el);
  }
}

// oculta con animación (fade + micro desplazamiento)
function softHide(el){
  if (!el.classList.contains("show")) return; // ya está oculto
  el.classList.add("hiding");                 // activa transición de salida

  const onEnd = (ev) => {
    // aseguramos que sea el final de opacidad del propio elemento
    if (ev.target !== el || ev.propertyName !== "opacity") return;
    el.removeEventListener("transitionend", onEnd);
    el.classList.remove("show", "hiding");    // queda invisible y sin estilos visibles
    el.textContent = "";                      // limpiamos texto al final de la animación
  };
  // por si ya había un listener previo
  el.removeEventListener("transitionend", onEnd);
  el.addEventListener("transitionend", onEnd, { once: true });
}

function makeMsg(selector, defaults = { ok:2500, error:6500, info:3000 }){
  const getEl = () => (typeof selector === "string" ? document.querySelector(selector) : selector);
  const _timers = new WeakMap();

  function show(text = "", opts = {}){
    const el = getEl(); if (!el) return;
    const kind    = opts.kind || (opts.isError ? "error" : "ok"); // ok | error | info
    const autoclr = opts.persist ? 0 : (opts.autoclear ?? (kind === "error" ? defaults.error : defaults.ok));

    // limpia timer anterior
    const prev = _timers.get(el);
    if (prev){ clearTimeout(prev); _timers.delete(el); }

    if (!text) {
      // si es limpiar, anima salida
      softHide(el);
      return;
    }

    // mostrar (sin mover layout)
    _applyMsg(el, text, kind);

    if (autoclr > 0){
      const t = setTimeout(() => softHide(el), autoclr);
      _timers.set(el, t);

      // pausa el autocierre con hover
      el.onmouseenter = () => { const tt = _timers.get(el); if (tt) { clearTimeout(tt); _timers.delete(el); } };
      el.onmouseleave = () => {
        if (!el.classList.contains("show")) return;
        const t2 = setTimeout(() => softHide(el), 1200);
        _timers.set(el, t2);
      };
    }
  }

  show.ok    = (t, o={}) => show(t, { ...o, kind:"ok" });
  show.error = (t, o={}) => show(t, { ...o, kind:"error" });
  show.info  = (t, o={}) => show(t, { ...o, kind:"info" });
  show.clear = ()        => show("");

  return show;
}


  const Messages = {
    global:   makeMsg("#msg"),
    cliente:  makeMsg("#msgCliente"),
    preorden: makeMsg("#msgPreorden"),
  };

  // ================== Dirty flag global ==================
  let _dirty = false;
  function setDirty(v = true) { _dirty = !!v; }
  function isDirty() { return _dirty; }

  // ================== Export ==================
  window.Utils = {
    $, $$,
    log, warn, error,
    validarDni,
    to2, setNum,
    msg,                // global (compatibilidad)
    setDirty, isDirty,
    Messages           // 👈 helpers con autocierre
  };
  window.Messages = Messages;
})();
