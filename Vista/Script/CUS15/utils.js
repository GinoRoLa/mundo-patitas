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
  
  // 🐛 DEBUG temporal
  console.log("[showMsg]", { type, text: text?.substring(0, 30) });
  
  const old = _msgTimers.get(msgEl);
  if (old) clearTimeout(old);
  
  msgEl.textContent = text || "";
  
  // ✅ Remover solo las clases de estado
  const classesToRemove = ["ok", "error", "show"].filter(c => c); // filtrar vacíos
  classesToRemove.forEach(c => msgEl.classList.remove(c));
  
  // ✅ Asegurar clase base
  if (!msgEl.classList.contains("msg")) {
    msgEl.classList.add("msg");
  }

  if (text) {
    const normalizedType = String(type || "info").trim().toLowerCase();
    
    let cls = null;
    if (normalizedType === "error") cls = "error";
    else if (normalizedType === "ok") cls = "ok";

    if (cls && cls.length > 0 && !msgEl.classList.contains(cls)) {
      msgEl.classList.add(cls);
    }
    
    msgEl.classList.add("show");

    if (autoclear > 0) {
      const t = setTimeout(() => {
        ["show", "ok", "error"].forEach(c => msgEl.classList.remove(c));
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
