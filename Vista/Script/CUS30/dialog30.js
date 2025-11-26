// /vista/Script/CUS30/dialog30.js
(function () {
  function showMessageDialog(title, body) {
    const dlg = document.getElementById("dlgMsg");
    if (!dlg) {
      alert(title + "\n\n" + body);
      return;
    }
    const t = document.getElementById("dlgMsgTitle");
    const b = document.getElementById("dlgMsgBody");
    if (t) t.textContent = title || "Mensaje";
    if (b) b.textContent = body || "";
    dlg.showModal();
  }

  window.Dialog30 = {
    showMessage: showMessageDialog,
  };
})();
