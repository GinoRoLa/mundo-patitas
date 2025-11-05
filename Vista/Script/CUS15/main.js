// =======================================================
// Main · CUS15
// =======================================================
(function () {
  const $ = (sel, ctx = document) => ctx.querySelector(sel);

  /* ========= Botón Cancelar ========= */
  function wireBtnCancelar() {
    const btnCancelar = $("#btnCancelar");
    if (!btnCancelar) return;
    btnCancelar.addEventListener("click", () => {
      if (confirm("¿Está seguro que desea cancelar? Se perderán los cambios no guardados.")) {
        window.location.href = "../"; // o donde corresponda
      }
    });
  }

  /* ========= Botón Ver Comparador ========= */
  function wireBtnComparador() {
    const btn = $("#btnVerComparador");
    const modal = $("#modalComparador");
    if (!btn || !modal) return;
    btn.addEventListener("click", () => modal.showModal());
  }

  /* ========= Cierre de modales ========= */
  function wireCerrarModales() {
    document.querySelectorAll("[data-close]").forEach((btn) => {
      btn.addEventListener("click", (e) => e.target.closest("dialog")?.close());
    });
  }

  /* ========= Inicialización ========= */
  function init() {
    wireBtnCancelar();
    wireBtnComparador();
    // wireBtnGenerarOC();     // ← COMENTAR O ELIMINAR
    // wireBtnConfirmarOC();   // ← COMENTAR O ELIMINAR
    wireCerrarModales();
  }

  document.addEventListener("DOMContentLoaded", init);
})();