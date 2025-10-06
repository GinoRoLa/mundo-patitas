// /Vista/Script/CUS24/utils.js
(function () {
  const $ = (sel, ctx = document) => ctx.querySelector(sel);

  /* ================== Validadores ================== */
  function validarSoloDigitos(value, { required = true } = {}) {
    const v = String(value ?? "").trim();
    /* if (!v && required) return { ok: false, msg: "Este campo es obligatorio." }; */
    if (v && !/^\d+$/.test(v)) {
      return { ok: false, msg: "No se permiten caracteres alfanuméricos." };
    }
    return { ok: true };
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
  function bindNumericValidation(inputSel, msgSel, opts = {}) {
    const { required = true, btn: btnSel, validateOn = "input", onValid } = opts;
    const input = $(inputSel);
    const msgEl = $(msgSel);
    const btn   = btnSel ? $(btnSel) : null;

    if (!input) return;

    const run = () => {
      const r = validateNumericInput(input, msgEl, { required, autoclear: 0 });
      if (btn) btn.disabled = !r.ok;
      return r.ok;
    };

    if (validateOn === "input" || validateOn === "both") {
      input.addEventListener("input", run);
    }
    if (validateOn === "blur" || validateOn === "both") {
      input.addEventListener("blur", run);
    } else if (validateOn !== "input" && validateOn !== "both") {
      // default mínimo
      input.addEventListener("input", run);
    }

    // Enter para ejecutar onValid
    input.addEventListener("keydown", (ev) => {
      if (ev.key === "Enter") {
        ev.preventDefault();
        if (run() && typeof onValid === "function") onValid();
      }
    });

    // estado inicial
    run();

    // devuelve una API mínima por si la necesitas
    return { validateNow: run, destroy: () => {} };
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
  };
})();
