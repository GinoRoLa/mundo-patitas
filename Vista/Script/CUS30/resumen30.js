// /vista/Script/CUS30/resumen30.js
(function () {
  function getEl(id) {
    return document.getElementById(id);
  }
  function toNumber(v) {
    const n = Number(v);
    return Number.isFinite(n) ? n : 0;
  }

  function pintarDiferencia(valor) {
    const txt = getEl("txtResDiferencia");
    if (!txt) return;

    txt.value = valor.toFixed(2);
    txt.style.color = "";
    if (valor > 0.009) {
      // sobrante
      txt.style.color = "#16a34a";
    } else if (valor < -0.009) {
      // faltante
      txt.style.color = "#dc2626";
    }
  }

  function recalcular() {
    window.CUS30 = window.CUS30 || {};
    const state = window.CUS30.state || {};
    const pedidos = state.pedidos || [];

    let totalPedido = 0;
    let totalVuelto = 0;
    let totalEsperado = 0;

    for (const p of pedidos) {
      const montoPedido = toNumber(p.MontoPedido);
      const vueltoCaja =
        toNumber(p.MontoVueltoProgramado) ||
        toNumber(p.VueltoProgramado) ||
        0;

      totalPedido += montoPedido;
      totalVuelto += vueltoCaja;

      // si desde pedidos30 ya viene p.EsperadoRetorno, úsalo directo
      let esperado = toNumber(p.EsperadoRetorno);
      if (!Number.isFinite(esperado) || esperado === 0) {
        if ((p.EstadoPedido || "").toLowerCase() === "entregado") {
          esperado = montoPedido + vueltoCaja;
        } else {
          esperado = vueltoCaja;
        }
      }
      totalEsperado += esperado;
    }

    const txtVentas = getEl("txtResVentas");
    const txtVuelto = getEl("txtResVuelto");
    const txtEsperado = getEl("txtResEsperado");

    if (txtVentas) txtVentas.value = totalPedido.toFixed(2);
    if (txtVuelto) txtVuelto.value = totalVuelto.toFixed(2);
    if (txtEsperado) txtEsperado.value = totalEsperado.toFixed(2);

    const efectivo = toNumber(getEl("txtResEfectivo")?.value);
    const diferencia = efectivo - totalEsperado;
    pintarDiferencia(diferencia);

    // habilitar o no el botón de cierre
    const btnCerrar = getEl("btnCerrarRecaudacion");
    if (btnCerrar) {
      btnCerrar.disabled = !(efectivo >= 0 && pedidos.length > 0);
    }
  }

  function onCambioEfectivo() {
    recalcular();
  }

  function initDesdeCabecera() {
    const txtEfectivo = getEl("txtResEfectivo");
    if (txtEfectivo) {
      txtEfectivo.disabled = false;
      txtEfectivo.value = "";
    }

    recalcular();

    const btnCerrar = getEl("btnCerrarRecaudacion");
    if (btnCerrar) btnCerrar.disabled = true;
  }

  function initResumen30() {
    const txtEfectivo = getEl("txtResEfectivo");
    if (txtEfectivo) {
      txtEfectivo.addEventListener("input", onCambioEfectivo);
    }
  }

  window.Resumen30 = {
    init: initResumen30,
    initDesdeCabecera,
    recalcular,
  };
})();
