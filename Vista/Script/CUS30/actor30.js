// /vista/Script/CUS30/actor30.js
(function () {
  async function cargarActor30(dni = '22222222') {
    if (!window.API30) {
      console.error('API30 no está disponible');
      return;
    }

    const lblActor = document.getElementById("lblActor");
    const lblRol = document.getElementById("lblRol");

    // ⚠️ CORRECCIÓN: Llamar a actor con DNI como función
    const res = await API30.fetchJSON(API30.url.actor(dni));
    
    if (!res.ok) {
      if (lblActor) lblActor.textContent = "(error al cargar)";
      if (lblRol) lblRol.textContent = "";
      console.error("Error al cargar actor CUS30:", res);
      return;
    }

    // ⚠️ CORRECCIÓN: El PHP devuelve 'found', 'nombre' y 'rol' directamente
    if (!res.found) {
      if (lblActor) lblActor.textContent = "(no encontrado)";
      if (lblRol) lblRol.textContent = "";
      console.warn("Actor no encontrado con DNI:", dni);
      return;
    }

    // ⚠️ CORRECCIÓN: Usar las propiedades correctas de la respuesta
    if (lblActor) lblActor.textContent = res.nombre || "(sin nombre)";
    if (lblRol) lblRol.textContent = res.rol || "(sin rol)";

    // Guardar información completa del actor
    window.CUS30 = window.CUS30 || {};
    window.CUS30.actor = {
      trabajador: res.trabajador,
      nombre: res.nombre,
      rol: res.rol,
      dni: dni
    };

    console.log("Actor cargado:", window.CUS30.actor);
  }

  window.Actor30 = { cargarActor30 };
})();