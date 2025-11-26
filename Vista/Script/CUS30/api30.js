// /vista/Script/CUS30/api30.js
(function () {
  // Ajusta si usas otro puerto / host
  window.SERVICIOURL = "http://localhost:8080";

  // ⚠️ CORRECCIÓN: Usar el nombre correcto del controlador
  const BASE = `${window.SERVICIOURL}/mundo-patitas/Controlador/ControladorCUS30.php`;

  async function fetchJSON(url, opts = {}) {
    const finalOpts = Object.assign(
      { headers: { "X-Requested-With": "fetch" } },
      opts
    );

    let res, text;
    try {
      res = await fetch(url, finalOpts);
      text = await res.text();
    } catch (e) {
      return { ok: false, error: String(e), network: true };
    }

    let data;
    try {
      data = JSON.parse(text);
    } catch {
      data = { ok: false, raw: text };
    }

    if (!res.ok) {
      return Object.assign({ ok: false, httpStatus: res.status }, data);
    }
    return data;
  }

  window.API30 = {
    base: BASE,
    url: {
      // GET ?accion=actor&dni=XXXXXXXX (opcional, usa default 22222222)
      actor: (dni = '22222222') => `${BASE}?accion=actor&dni=${encodeURIComponent(dni)}`,
      
      // ⚠️ CORRECCIÓN: Usar parámetro 'dniRepartidor' como espera el PHP
      asignacionesPendientes: (dni) =>
        `${BASE}?accion=asignaciones-pendientes&dniRepartidor=${encodeURIComponent(dni)}`,
      
      // ⚠️ CORRECCIÓN: Usar parámetro 'idOrdenAsignacion' como espera el PHP
      recaudacionDetalle: (idAsignacion) =>
        `${BASE}?accion=recaudacion-detalle&idOrdenAsignacion=${encodeURIComponent(idAsignacion)}`,
      
      // POST ?accion=cerrar-recaudacion
      cerrarRecaudacion: `${BASE}?accion=cerrar-recaudacion`,
    },
    fetchJSON,
  };
})();