// /Vista/Script/CUS24/actor.js
(function () {
  async function cargarPerfilActor() {
    const { fetchJSON, url } = window.API24;
    const r = await fetchJSON(url.actor, { method: "GET", credentials: "include" });
    if (!r || !r.ok) { console.warn(r?.error || "No se pudo obtener actor"); return; }

    const actor = r.actor;
    const alm   = r.almacenPorDefecto;

    const lblActor = document.getElementById('lblActor');
    const lblRol   = document.getElementById('lblRol');
    if (lblActor) lblActor.textContent = actor?.nombre ?? '';
    if (lblRol)   lblRol.textContent   = actor?.cargo  ?? '';

    const txtOrigen = document.getElementById('txtOrigen');
    if (txtOrigen && alm) {
      txtOrigen.value = `${alm.nombre} — ${alm.direccion}${alm.distritoNombre ? ' — ' + alm.distritoNombre : ''}`;

      // Guarda por si luego la guía lo necesita
      window.ORIGEN = { id: alm.id, nombre: alm.nombre, direccion: alm.direccion, idDistrito: alm.idDistrito };
    }
  }

  document.addEventListener('DOMContentLoaded', cargarPerfilActor);
})();
