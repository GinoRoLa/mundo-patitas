// /Vista/Script/CUS02/api.js
(function () {
  window.SERVICIOURL = "http://localhost:8080";

  //window.SERVICIOURL = "http://localhost:3000";

  const BASE = `${window.SERVICIOURL}/Controlador/ControladorCUS02.php`;

  //const BASE = window.CUS_BASE || "/Controlador/ControladorCUS02.php";

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

  window.API = {
    base: BASE,
    url: {
      metodosEntrega: `${BASE}?accion=metodos-entrega`,
      buscarCliente: `${BASE}?accion=buscar-cliente`,
      consolidar: `${BASE}?accion=consolidar`,
      registrar: `${BASE}?accion=registrar`,
    },
    fetchJSON,
  };
})();
