// =======================================================
// Utilidades generales · CUS15
// =======================================================
(function () {
  const $ = (sel, ctx = document) => ctx.querySelector(sel);

  // --- Toast simple ---
  function showToast(message, type = "info") {
    const toast = document.createElement("div");
    toast.className = `custom-toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add("show"), 100);
    setTimeout(() => {
      toast.classList.remove("show");
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  // --- Mensajes con autocierre ---
  const _msgTimers = new WeakMap();
  function showMsg(msgEl, type = "info", text = "", { autoclear = 0 } = {}) {
    if (!msgEl) return;
    const old = _msgTimers.get(msgEl);
    if (old) clearTimeout(old);
    msgEl.textContent = text || "";
    msgEl.classList.remove("ok", "error", "show");
    msgEl.classList.add("msg");
    if (text) {
      msgEl.classList.add(type === "error" ? "error" : type === "ok" ? "ok" : "");
      msgEl.classList.add("show");
      if (autoclear > 0) {
        const t = setTimeout(() => {
          msgEl.classList.remove("show", "ok", "error");
          msgEl.textContent = "";
        }, autoclear);
        _msgTimers.set(msgEl, t);
      }
    }
  }

  // ===== Modal "Procesando" (simple) =====
const Processing = (() => {
  const dlg = () => document.getElementById("dlgProcessing");
  return {
    show(title = "Procesando…", msg = "Por favor, espera un momento.") {
      const d = dlg(); if (!d) return;
      const t = document.getElementById("procTitle");
      const p = document.getElementById("procMsg");
      if (t) t.textContent = title;
      if (p) p.textContent = msg;
      if (!d.open && d.showModal) d.showModal();
    },
    hide() { const d = dlg(); if (d?.open) d.close(); }
  };
})();


  // --- Validación simple de número ---
  function validarSoloDigitos(value, { required = true } = {}) {
    const raw = String(value ?? "").trim();
    if (!raw) return required ? { ok: false, msg: "Campo obligatorio" } : { ok: true };
    if (!/^\d+$/.test(raw)) return { ok: false, msg: "Solo números" };
    return { ok: true, value: Number(raw) };
  }

  window.Processing = Processing;
  window.Utils15 = {
    $,
    showToast,
    showMsg,
    validarSoloDigitos
  };
})();
