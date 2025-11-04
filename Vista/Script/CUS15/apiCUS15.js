// =======================================================
// API CUS15 · Evaluar Cotización de Proveedor
// =======================================================
(function () {
  window.SERVICIOURL = "http://localhost:8080";
  const BASE = `${window.SERVICIOURL}/mundo-patitas/Controlador/ControladorCUS15.php`;

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

  // =======================================================
  // Definición de endpoints
  // =======================================================
  window.API15 = {
    base: BASE,
    url: {
      actor: `${BASE}?accion=actor`,

      // 🔹 lista de requerimientos aprobados o en evaluación
      requerimientos: `${BASE}?accion=req-list`,

      // 🔹 detalle del requerimiento (encabezado + productos)
      detalleReq: (id) =>`${BASE}?accion=req-detalle&id=${encodeURIComponent(id)}`,
      scanExcel: (id) =>`${BASE}?accion=scan-excel&id=${encodeURIComponent(id)}`,
      importExcel: `${BASE}?accion=importar-excel`,
      importExcelReq: `${BASE}?accion=importar-excel-req`,

      // 🔹 cotizaciones generadas por requerimiento
      cotsGeneradas: (id) =>`${BASE}?accion=cots-generadas&id=${encodeURIComponent(id)}`,

      // 🔹 cotizaciones recibidas por requerimiento
      cotsRecibidas: (id) =>`${BASE}?accion=cots-recibidas&id=${encodeURIComponent(id)}`,

      // 🔹 evaluación automática (greedy o regla de negocio)
      evaluar: `${BASE}?accion=evaluar`,

      // 🔹 generar órdenes de compra
      generarOCs: `${BASE}?accion=generar-ocs`,

      // 🔹 vista previa OC
      ocPreview: (id) =>
        `${BASE}?accion=oc-preview&id=${encodeURIComponent(id)}`,
    },
    fetchJSON,
  };
})();
