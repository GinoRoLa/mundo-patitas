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

  // ================== Mensajes con fade suave (sin layout shift) ==================
  // .msg.show => visible; .msg.hiding => transición de salida
  function softHide(el){
    if (!el.classList.contains("show")) return;
    el.classList.add("hiding");
    const onEnd = (ev) => {
      if (ev.target !== el || ev.propertyName !== "opacity") return;
      el.removeEventListener("transitionend", onEnd);
      el.classList.remove("show", "hiding");
      el.textContent = "";
    };
    el.removeEventListener("transitionend", onEnd);
    el.addEventListener("transitionend", onEnd, { once: true });
  }

  function _applyMsg(el, text, kind){
    el.textContent = text || "";
    el.className = "msg" + (kind ? " " + kind : "");
    if (text) {
      el.classList.remove("hiding");
      el.classList.add("show");
    } else {
      softHide(el);
    }
  }

  function makeMsg(selector, defaults = { ok:2500, error:6500, info:3000 }){
    const getEl = () => (typeof selector === "string" ? document.querySelector(selector) : selector);
    const timers = new WeakMap(); // timers POR instancia

    function show(text = "", opts = {}){
      const el = getEl(); if (!el) return;
      const kind    = opts.kind || (opts.isError ? "error" : "ok"); // ok|error|info
      const autoclr = opts.persist ? 0 : (opts.autoclear ?? (kind === "error" ? defaults.error : defaults.ok));

      // limpia timer previo
      const prev = timers.get(el);
      if (prev){ clearTimeout(prev); timers.delete(el); }

      if (!text) { softHide(el); return; }
      _applyMsg(el, text, kind);

      if (autoclr > 0){
        const t = setTimeout(() => softHide(el), autoclr);
        timers.set(el, t);

        // pausa con hover
        el.onmouseenter = () => { const tt = timers.get(el); if (tt){ clearTimeout(tt); timers.delete(el); } };
        el.onmouseleave = () => {
          if (!el.classList.contains("show")) return;
          const t2 = setTimeout(() => softHide(el), 1200);
          timers.set(el, t2);
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

  // ================== msg() legacy (compat) ==================
  // Redirige al contenedor global, sin scroll y con autocierre
  function msg(texto = "", isError = false) {
    if (!texto) { Messages.global.clear(); return; }
    if (isError) Messages.global.error(texto, { autoclear: 6500 });
    else         Messages.global.ok(texto,    { autoclear: 2500 });
  }

  // ================== Dirty flag + beforeunload ==================
  let _dirty = false;

  function onBeforeUnload(e){
    e.preventDefault();
    e.returnValue = ""; // fuerza el prompt nativo del navegador
  }

  function setDirty(v = true) {
    const next = !!v;
    if (next === _dirty) return;  // evita toggles innecesarios
    _dirty = next;
    if (next) window.addEventListener("beforeunload", onBeforeUnload);
    else      window.removeEventListener("beforeunload", onBeforeUnload);
  }

  function isDirty() { return _dirty; }

  // ================== Export ==================
  window.Utils = {
    $, $$,
    log, warn, error,
    validarDni,
    to2, setNum,
    msg,                // compat
    setDirty, isDirty,
    Messages            // nuevo sistema de mensajes
  };
  window.Messages = Messages;
})();
