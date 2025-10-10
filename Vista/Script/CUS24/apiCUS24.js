// /Vista/Script/CUS24/apiCUS24.js
(function () {
  window.SERVICIOURL = "http://localhost:8080";
  //window.SERVICIOURL = "http://localhost";
  const BASE = `${window.SERVICIOURL}/mundo-patitas/Controlador/ControladorCUS24.php`;

  async function fetchJSON(url, opts = {}) {
    const finalOpts = Object.assign({ headers: { "X-Requested-With": "fetch" } }, opts);
    let res, text;
    try { res = await fetch(url, finalOpts); text = await res.text(); }
    catch (e) { return { ok: false, error: String(e), network: true }; }
    let data; try { data = JSON.parse(text); } catch { data = { ok: false, raw: text }; }
    if (!res.ok) return Object.assign({ ok: false, httpStatus: res.status }, data);
    return data;
  }

  window.API24 = {
    base: BASE,
    url: {
      actor: `${BASE}?accion=actor`,
      buscarAsignacion: (id) => `${BASE}?accion=buscar-asignacion&id=${encodeURIComponent(id)}`,
      itemsPorOrden: (idOP) => `${BASE}?accion=items-por-orden&idOP=${encodeURIComponent(idOP)}`,
      generarSalida: `${BASE}?accion=generar-salida`,               // (legacy, por si lo usas en otro lado)
      generarSalidaLote: `${BASE}?accion=generar-salida-lote`,      // ← NUEVO
    },
    fetchJSON,
  };
})();
