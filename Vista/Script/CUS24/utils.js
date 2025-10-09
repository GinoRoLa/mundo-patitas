// /Vista/Script/CUS24/utils.js
(function () {
  const $ = (sel, ctx = document) => ctx.querySelector(sel);

  /* ================== Validadores ================== */
 function validarSoloDigitos(value, {
  required = true,
  maxLen = 9,             // ajusta: 5 para 80000, 9 para hasta 999,999,999
  min = 1,
  max = 2147483647        // INT (signed). Si usas UNSIGNED: 4294967295.
} = {}) {
  const raw = String(value ?? "").trim();

  if (!raw) {
    return required ? { ok: false, msg: "Ingrese el numero de asignacion" } : { ok: true };
  }

  // Solo dígitos (sin e, +, -, ., espacios, etc.)
  if (!/^\d+$/.test(raw)) {
    return { ok: false, msg: "Solo números (sin símbolos ni espacios)." };
  }

  if (maxLen && raw.length > maxLen) {
    return { ok: false, msg: `Máximo ${maxLen} dígitos.` };
  }

  // Convertir con seguridad
  const n = Number(raw);
  if (!Number.isSafeInteger(n)) {
    return { ok: false, msg: "Número fuera de rango." };
  }

  if (n < min) return { ok: false, msg: `Debe ser ≥ ${min}.` };
  if (n > max) return { ok: false, msg: `Debe ser ≤ ${max}.` };

  return { ok: true, value: n };
}


  /* ================== Mensajes con autocierre ================== */
  // Mantenemos un timer por mensaje (clave: selector o nodo)
  const _msgTimers = new WeakMap();

  function showMsg(msgEl, type = "info", text = "", { autoclear = 0 } = {}) {
    if (!msgEl) return;
    // cancela timer anterior si lo hubiera
    const oldTimer = _msgTimers.get(msgEl);
    if (oldTimer) clearTimeout(oldTimer);

    msgEl.textContent = text || "";
    msgEl.classList.remove("ok", "error", "show", "hiding");
    msgEl.classList.add("msg"); // por si no la tuviera

    if (text) {
      if (type === "ok")    msgEl.classList.add("ok");
      if (type === "error") msgEl.classList.add("error");
      msgEl.classList.add("show");

      if (autoclear > 0) {
        const t = setTimeout(() => {
          // salida suave si tienes .msg.hiding { opacity:0; transform:translateY(-2px); ... }
          msgEl.classList.add("hiding");
          setTimeout(() => {
            msgEl.classList.remove("show", "ok", "error", "hiding");
            msgEl.textContent = "";
          }, 180); // ≈ duración de tu transición en CSS
          _msgTimers.delete(msgEl);
        }, autoclear);
        _msgTimers.set(msgEl, t);
      }
    } else {
      // limpiar si no hay texto
      msgEl.classList.remove("ok", "error", "show");
    }
  }

  function clearMsg(msgEl) {
    if (!msgEl) return;
    const oldTimer = _msgTimers.get(msgEl);
    if (oldTimer) clearTimeout(oldTimer);
    msgEl.classList.remove("show", "ok", "error", "hiding");
    msgEl.textContent = "";
  }

  /* ================== Errores de campo (borde rojo + mensaje) ================== */
  function setInvalid(inputEl, msgEl, message, { autoclear = 0 } = {}) {
    inputEl?.classList.add("input--invalid");
    showMsg(msgEl, "error", message || "Campo inválido.", { autoclear });
  }
  function clearInvalid(inputEl, msgEl) {
    inputEl?.classList.remove("input--invalid");
    clearMsg(msgEl);
  }

  /* ================== Validación de un input numérico ================== */
  function validateNumericInput(inputEl, msgEl, { required = true, autoclear = 0 } = {}) {
    const { ok, msg } = validarSoloDigitos(inputEl?.value || "", { required });
    if (!ok) {
      setInvalid(inputEl, msgEl, msg, { autoclear });
      return { ok: false, msg };
    }
    clearInvalid(inputEl, msgEl);
    return { ok: true };
  }

  /* ================== Binder reutilizable ================== */
  // - Valida al tipear/blur
  // - Habilita/deshabilita botón si se pasa selector en opts.btn
  // - Llama onValid (si existe) cuando el usuario presione Enter y sea válido
  // Reemplaza tu bindNumericValidation por esta versión
function bindNumericValidation(inputSel, msgSel, opts = {}) {
  const {
    btn: btnSel,
    validateOn = "input",
    onValid,
    // NUEVO: control fino
    requiredOnInput = false,
    requiredOnBlur  = false,
  } = opts;

  const input = $(inputSel);
  const msgEl = $(msgSel);
  const btn   = btnSel ? $(btnSel) : null;
  if (!input) return;

  const run = (req = false) => {
    // pasa todas las opciones para respetar maxLen/min/max si las agregas
    const r = validateNumericInput(input, msgEl, { ...opts, required: req, autoclear: 0 });
    if (btn) btn.disabled = !r.ok;
    return r.ok;
  };

  // Validación “suave” mientras se tipea/blur (no obligatorio)
  if (validateOn === "input" || validateOn === "both") {
    input.addEventListener("input", () => run(requiredOnInput));
  }
  if (validateOn === "blur" || validateOn === "both") {
    input.addEventListener("blur", () => run(requiredOnBlur));
  }
  if (validateOn !== "input" && validateOn !== "both") {
    input.addEventListener("input", () => run(requiredOnInput));
  }

  // Enter = validación “fuerte” (obligatorio)
  input.addEventListener("keydown", (ev) => {
    if (ev.key === "Enter") {
      ev.preventDefault();
      if (run(true) && typeof onValid === "function") onValid();
    }
  });

  // Estado inicial “relajado”
  run(false);

  return { validateNow: () => run(false), destroy: () => {} };
}


  // ===================================================
// 🔹 Toast
// ===================================================
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

  // Exponer API pública
  window.Utils24 = {
    $,
    validarSoloDigitos,
    showMsg,
    clearMsg,
    setInvalid,
    clearInvalid,
    validateNumericInput,
    bindNumericValidation,
    showToast
  };
})();
