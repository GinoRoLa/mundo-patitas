// =======================================================
// Utilidades generales · CUS30 (UNIFICADO)
// =======================================================
(function () {
  let toastCounter = 0;
  const MAX_TOASTS = 5; // Máximo de toasts visibles simultáneamente

  const $ = (sel, ctx = document) => (ctx || document).querySelector(sel);

  /**
   * Escapa HTML para prevenir XSS
   * @param {string} text - Texto a escapar
   * @returns {string} Texto escapado
   */
  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  /**
   * Obtiene el ícono SVG según el tipo de toast
   * @param {string} type - Tipo de toast
   * @returns {string} SVG del ícono
   */
  function getIconForType(type) {
    const icons = {
      success: `<svg width="20" height="20" viewBox="0 0 20 20" fill="none">
        <circle cx="10" cy="10" r="9" fill="currentColor" opacity="0.2"/>
        <path d="M14.5 7L8.5 13L5.5 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>`,
      error: `<svg width="20" height="20" viewBox="0 0 20 20" fill="none">
        <circle cx="10" cy="10" r="9" fill="currentColor" opacity="0.2"/>
        <path d="M10 6V11M10 14H10.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>`,
      warning: `<svg width="20" height="20" viewBox="0 0 20 20" fill="none">
        <path d="M10 2L18 17H2L10 2Z" fill="currentColor" opacity="0.2"/>
        <path d="M10 8V12M10 15H10.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>`,
      info: `<svg width="20" height="20" viewBox="0 0 20 20" fill="none">
        <circle cx="10" cy="10" r="9" fill="currentColor" opacity="0.2"/>
        <path d="M10 10V14M10 6H10.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>`,
    };
    return icons[type] || icons.info;
  }

  /**
   * Remueve un toast con animación
   * @param {HTMLElement} toast - Elemento del toast
   */
  function removeToast(toast) {
    if (!toast || !toast.parentNode) return;

    toast.classList.remove("toast-show");
    toast.classList.add("toast-hide");

    // Esperar animación antes de remover del DOM
    setTimeout(() => {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }, 300);
  }

  /**
   * Muestra un toast apilable con animación
   * @param {string} message - Mensaje a mostrar
   * @param {string} type - Tipo: 'success'|'ok'|'error'|'warning'|'info'
   * @param {number} duration - Duración en ms (0 = no auto-cerrar)
   */
  function showToast(message, type = "info", duration = 5000) {
    // Normalizar tipo
    if (type === "ok") type = "success";

    // Crear contenedor si no existe
    let container = document.getElementById("toast-container");
    if (!container) {
      container = document.createElement("div");
      container.id = "toast-container";
      container.className = "toast-container";
      document.body.appendChild(container);
    }

    // Limitar cantidad de toasts (remover el más antiguo si excede)
    const existingToasts = container.querySelectorAll(".toast");
    if (existingToasts.length >= MAX_TOASTS) {
      const oldest = existingToasts[0];
      removeToast(oldest);
    }

    // Crear toast
    const toast = document.createElement("div");
    const toastId = `toast-${++toastCounter}`;
    toast.id = toastId;
    toast.className = `toast toast-${type}`;
    toast.setAttribute("role", "alert");
    toast.setAttribute("aria-live", "polite");

    // Estructura interna del toast
    const icon = getIconForType(type);
    toast.innerHTML = `
      <div class="toast-icon">${icon}</div>
      <div class="toast-content">
        <div class="toast-message">${escapeHtml(message)}</div>
      </div>
      <button class="toast-close" aria-label="Cerrar notificación">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
          <path d="M14 1.41L12.59 0L7 5.59L1.41 0L0 1.41L5.59 7L0 12.59L1.41 14L7 8.41L12.59 14L14 12.59L8.41 7L14 1.41Z" fill="currentColor"/>
        </svg>
      </button>
    `;

    // Agregar al contenedor (al final, los nuevos aparecen arriba por CSS)
    container.appendChild(toast);

    // Trigger animación de entrada
    setTimeout(() => {
      toast.classList.add("toast-show");
    }, 10);

    // Botón de cerrar
    const closeBtn = toast.querySelector(".toast-close");
    closeBtn.addEventListener("click", () => {
      removeToast(toast);
    });

    // Auto-cerrar si duration > 0
    if (duration > 0) {
      setTimeout(() => {
        removeToast(toast);
      }, duration);
    }

    return toastId;
  }

  /**
   * Limpia todos los toasts activos
   */
  function clearAllToasts() {
    const container = document.getElementById("toast-container");
    if (container) {
      const toasts = container.querySelectorAll(".toast");
      toasts.forEach((toast) => removeToast(toast));
    }
  }

  /**
   * Muestra un toast de confirmación con acciones
   * @param {string} message - Mensaje
   * @param {Function} onConfirm - Callback al confirmar
   * @param {Function} onCancel - Callback al cancelar
   */
  function showConfirmToast(message, onConfirm, onCancel) {
    const container = document.getElementById("toast-container") || (() => {
      const c = document.createElement("div");
      c.id = "toast-container";
      c.className = "toast-container";
      document.body.appendChild(c);
      return c;
    })();

    const toast = document.createElement("div");
    toast.className = "toast toast-confirm";
    toast.innerHTML = `
      <div class="toast-content">
        <div class="toast-message">${escapeHtml(message)}</div>
        <div class="toast-actions">
          <button class="toast-btn toast-btn-confirm">Confirmar</button>
          <button class="toast-btn toast-btn-cancel">Cancelar</button>
        </div>
      </div>
    `;

    container.appendChild(toast);
    setTimeout(() => toast.classList.add("toast-show"), 10);

    const btnConfirm = toast.querySelector(".toast-btn-confirm");
    const btnCancel = toast.querySelector(".toast-btn-cancel");

    btnConfirm.addEventListener("click", () => {
      removeToast(toast);
      if (onConfirm) onConfirm();
    });

    btnCancel.addEventListener("click", () => {
      removeToast(toast);
      if (onCancel) onCancel();
    });
  }

  // --- Mensajes con autocierre en <div class="msg"> ---
  const _msgTimers = new WeakMap();

  /**
   * Muestra mensaje en un <div class="msg">
   * @param {HTMLElement} msgEl
   * @param {string} type 'info'|'ok'|'error'
   * @param {string} text
   * @param {{autoclear?:number}} options
   */
  function showMsg(msgEl, type = "info", text = "", { autoclear = 0 } = {}) {
    if (!msgEl) return;

    const old = _msgTimers.get(msgEl);
    if (old) clearTimeout(old);

    msgEl.textContent = text || "";

    // limpiar clases de estado
    ["ok", "error", "show"].forEach((c) => msgEl.classList.remove(c));

    // asegurar clase base
    if (!msgEl.classList.contains("msg")) {
      msgEl.classList.add("msg");
    }

    if (text) {
      const normalizedType = String(type || "info").trim().toLowerCase();
      let cls = null;
      if (normalizedType === "error") cls = "error";
      else if (normalizedType === "ok" || normalizedType === "success") cls = "ok";

      if (cls && !msgEl.classList.contains(cls)) {
        msgEl.classList.add(cls);
      }
      msgEl.classList.add("show");

      if (autoclear > 0) {
        const t = setTimeout(() => {
          ["show", "ok", "error"].forEach((c) => msgEl.classList.remove(c));
          msgEl.textContent = "";
        }, autoclear);
        _msgTimers.set(msgEl, t);
      }
    }
  }

  // ===== Modal "Procesando" (reutilizable) =====
  const Processing = (() => {
    const dlg = () => document.getElementById("dlgProcessing");
    return {
      show(title = "Procesando…", msg = "Por favor, espera un momento.") {
        const d = dlg();
        if (!d) return;
        const t = document.getElementById("procTitle");
        const p = document.getElementById("procMsg");
        if (t) t.textContent = title;
        if (p) p.textContent = msg;
        if (!d.open && d.showModal) d.showModal();
      },
      hide() {
        const d = dlg();
        if (d?.open) d.close();
      },
    };
  })();

  // --- Validación simple de solo dígitos ---
  function validarSoloDigitos(value, { required = true } = {}) {
    const raw = String(value ?? "").trim();
    if (!raw) return required ? { ok: false, msg: "Campo obligatorio" } : { ok: true };
    if (!/^\d+$/.test(raw)) return { ok: false, msg: "Solo números" };
    return { ok: true, value: Number(raw) };
  }

  // Exponer en global
  window.Processing30 = Processing;

  window.Utils30 = window.Utils30 || {};
  Object.assign(window.Utils30, {
    $,
    showToast,
    clearAllToasts,
    showConfirmToast,
    showMsg,
    validarSoloDigitos,
  });
})();
