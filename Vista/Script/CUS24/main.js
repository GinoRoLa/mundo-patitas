// /Vista/Script/CUS24/main.js
document.addEventListener('DOMContentLoaded', () => {
  // Valida que txtAsignacion sea numérico
  window.Utils24.bindNumericValidation('#txtAsignacion', '#msgAsignacion', { required: true });

  // Si quieres bloquear el botón “Buscar” cuando hay error:
  const btn = document.getElementById('btnBuscar');
  const input = document.getElementById('txtAsignacion');
  const msg = document.getElementById('msgAsignacion');

  if (btn && input) {
    const updateBtnState = () => {
      const { ok } = window.Utils24.validateNumericInput(input, msg, { required: true });
      btn.disabled = !ok;
    };
    input.addEventListener('input', updateBtnState);
    input.addEventListener('blur', updateBtnState);
    updateBtnState(); // estado inicial
  }
});
