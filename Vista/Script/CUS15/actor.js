// =======================================================
// Actor (Responsable de Compras) · CUS15
// =======================================================
(function () {
  async function cargarActor() {
    const { fetchJSON, url } = window.API15;
    const r = await fetchJSON(url.actor, { method: "GET" });
    if (!r || !r.ok) {
      console.warn(r?.error || "No se pudo obtener actor");
      return;
    }

    const actor = r.actor;

    // 👇 CORRECCIÓN: los selectores necesitan #
    const lblNombre = document.getElementById("lblActor");
    const lblRol    = document.getElementById("lblRol");

    if (lblNombre) lblNombre.textContent = actor?.nombre ?? "—";
    if (lblRol) lblRol.textContent = actor?.cargo ?? "—";
  }

  document.addEventListener("DOMContentLoaded", cargarActor);
})();
