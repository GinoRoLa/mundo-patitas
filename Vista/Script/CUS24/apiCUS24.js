// /Vista/Script/CUS24/apiCUS24.js
(function () {
  // Ajusta si usas otro host/puerto
  window.SERVICIOURL = "http://localhost:8080";
  // window.SERVICIOURL = "http://localhost";

  const BASE = `${window.SERVICIOURL}/mundo-patitas/Controlador/ControladorCUS24.php`;

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
    if (!res.ok)
      return Object.assign({ ok: false, httpStatus: res.status }, data);
    return data;
  }

  window.API24 = {
    base: BASE,
    url: {
      // Actor + almacén (para cabecera y origen)
      actor: `${BASE}?accion=actor`,

      // Buscar la orden de asignación y su detalle de pedidos (t40 + t401 + t59 + t71)
      buscarAsignacion: (id) => `${BASE}?accion=buscar-asignacion&id=${encodeURIComponent(id)}`,

      // (Opcional) Ítems por Orden de Pedido (cuando pintes el detalle al seleccionar un OP)
      itemsPorOrden: (idOP) => `${BASE}?accion=items-por-orden&idOP=${encodeURIComponent(idOP)}`,

      // (Opcional) Generar Orden de Salida + Guía (cuando confirmes la salida)
      generarSalida: `${BASE}?accion=generar-salida`,
    },
    fetchJSON,
  };
})();
