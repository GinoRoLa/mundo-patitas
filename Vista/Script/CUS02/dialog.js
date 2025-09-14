(function () {
  function alertModal(message, opts = {}) {
    const dlg  = document.getElementById('appDialog');
    const ok   = document.getElementById('appDialogOk');
    const msg  = document.getElementById('appDialogMsg');
    const ttl  = document.getElementById('appDialogTitle');

    // Fallback si no existe <dialog> o el navegador no lo soporta
    if (!dlg || !dlg.showModal) return window.alert(message);

    ttl.textContent = opts.title || 'Listo';
    msg.textContent = message;

    // Limpia listeners previos y configura cierre
    const onClose = () => {
      dlg.removeEventListener('close', onClose);
      if (typeof opts.onClose === 'function') opts.onClose(dlg.returnValue);
    };
    dlg.addEventListener('close', onClose, { once: true });

    dlg.showModal();
    // Enfocar botón aceptar
    queueMicrotask(() => ok && ok.focus());
  }

  window.AppDialog = { alert: alertModal };
})();
