// /vista/Script/CUS30/pedidos30.js
(function () {
  const showToast = window.Utils30?.showToast || ((m) => alert(m));

  function getEl(id) {
    return document.getElementById(id);
  }

  function toNumber(v) {
    const n = Number(v);
    return Number.isFinite(n) ? n : 0;
  }

  /* ============================================================
     1) Cargar cabecera
  ============================================================ */
  function setCabecera(cab) {
    const panel = getEl("panelCabecera");
    if (panel) panel.hidden = false;

    getEl("txtCabIdAsignacion").value = cab.Id_OrdenAsignacion ?? "";
    getEl("txtCabIdNotaCaja").value = cab.Id_NotaCajaDelivery ?? "";

    const fondo =
      cab.FondoRetirado ??
      cab.MontoFondo ??
      cab.MontoFondoRetirado ??
      0;

    getEl("txtCabFondo").value = Number(fondo).toFixed(2);
    getEl("txtCabRepartidor").value = cab.RepartidorNombre ?? "";
    getEl("txtCabEstadoNota").value = cab.EstadoNota ?? cab.EstadoNotaCaja ?? "";
    getEl("txtCabEstadoRuta").value = cab.EstadoRuta ?? "";
  }

  /* ============================================================
     2) Render tabla de pedidos (versión final)
  ============================================================ */
  function renderPedidosTabla() {
    const tbody = document.querySelector("#tblPedidos tbody");
    const msg = getEl("msgPedidos");
    if (!tbody) return;

    tbody.innerHTML = "";

    window.CUS30 = window.CUS30 || {};
    const pedidos = window.CUS30.state.pedidos || [];

    if (!pedidos.length) {
      msg.textContent = "No hay pedidos para esta asignación.";
      msg.classList.remove("ok", "error");
      showToast("No hay pedidos para esta asignación.", "info");
      return;
    }

    // Aviso tipo toast
    showToast(`Se cargaron ${pedidos.length} pedido(s).`, "ok");

    pedidos.forEach((p) => {
      const tr = document.createElement("tr");

      /* ================================
         ID Pedido
      ================================== */
      const tdId = document.createElement("td");
      tdId.textContent = p.Id_OrdenPedido;
      tr.appendChild(tdId);

      /* ================================
         Cliente
      ================================== */
      const tdCli = document.createElement("td");
      tdCli.textContent = p.Cliente ?? "";
      tr.appendChild(tdCli);

      /* ================================
         Monto del Pedido
      ================================== */
      const montoPedido = toNumber(p.MontoPedido);
      const tdPedido = document.createElement("td");
      tdPedido.textContent = montoPedido.toFixed(2);
      tdPedido.style.textAlign = "right";
      tr.appendChild(tdPedido);

      /* ================================
         Vuelto Caja Asignado
         viene de T501DetalleOPCE
      ================================== */
      const vueltoCaja =
        toNumber(p.VueltoProgramado) ||
        toNumber(p.MontoVueltoProgramado) ||
        0;

      const tdVuelto = document.createElement("td");
      tdVuelto.textContent = vueltoCaja.toFixed(2);
      tdVuelto.style.textAlign = "right";
      tr.appendChild(tdVuelto);

      /* ================================
         Esperado Retorno según Estado
         ENTREGADO  = pedido + vuelto
         NO ENTREGADO = solo vuelto
      ================================== */
      const estado = p.EstadoPedido;

      let esperadoRetorno = 0;
      if (estado === "Entregado") {
        esperadoRetorno = montoPedido + vueltoCaja;
      } else {
        esperadoRetorno = vueltoCaja;
      }

      p.EsperadoRetorno = esperadoRetorno;

      const tdEsp = document.createElement("td");
      tdEsp.textContent = esperadoRetorno.toFixed(2);
      tdEsp.style.textAlign = "right";
      tr.appendChild(tdEsp);

      /* ================================
         Estado Pedido (solo lectura)
      ================================== */
      const tdEst = document.createElement("td");
      tdEst.textContent = estado;
      tr.appendChild(tdEst);

      tbody.appendChild(tr);
    });

    // actualizar totales del resumen
    window.Resumen30.recalcular();
  }

  /* ============================================================
     3) Recibir datos desde API
  ============================================================ */
  function setDetalleRecaudacion(res) {
    window.CUS30 = window.CUS30 || {};
    window.CUS30.state = window.CUS30.state || {};

    const cab = res.cabecera || {};
    const pedidos = res.pedidos || [];

    window.CUS30.state.cabecera = cab;
    window.CUS30.state.pedidos = pedidos;

    setCabecera(cab);
    renderPedidosTabla();
    window.Resumen30.initDesdeCabecera();
  }

  function initPedidos30() {}

  window.Pedidos30 = {
    init: initPedidos30,
    setDetalleRecaudacion,
    renderPedidosTabla,
  };
})();
