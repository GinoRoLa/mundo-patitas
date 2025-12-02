// /vista/Script/CUS30/resumen30.js
(function () {
  const showToast = window.Utils30?.showToast || ((m) => alert(m));

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
    const txtEfectivo = getEl("txtResEfectivo");
    const chkEditar = getEl("chkEditarEfectivo");

    if (txtVentas) txtVentas.value = totalPedido.toFixed(2);
    if (txtVuelto) txtVuelto.value = totalVuelto.toFixed(2);
    if (txtEsperado) txtEsperado.value = totalEsperado.toFixed(2);

    let efectivo = 0;

    if (txtEfectivo) {
      // Si NO está marcado "Editar efectivo", el valor siempre es el esperado
      if (!chkEditar || !chkEditar.checked) {
        efectivo = totalEsperado;
        txtEfectivo.value = totalEsperado.toFixed(2);
      } else {
        // Editable: respetar lo que escribe el usuario, pero sin pasar el esperado
        efectivo = toNumber(txtEfectivo.value);

        if (efectivo > totalEsperado) {
          efectivo = totalEsperado;
          txtEfectivo.value = totalEsperado.toFixed(2);
          showToast("El efectivo no puede ser mayor al monto esperado.", "error");
        }

        // si el usuario borra todo, dejar 0 pero sin romper
        if (txtEfectivo.value.trim() === "") {
          efectivo = 0;
        }
      }
    }

    const diferencia = efectivo - totalEsperado;
    pintarDiferencia(diferencia);

    // habilitar o no el botón de cierre
    const btnCerrar = getEl("btnCerrarRecaudacion");
    if (btnCerrar) {
      // Habilitar si hay pedidos Y el efectivo es válido (>= 0)
      // Cuando se carga por primera vez, efectivo será igual a totalEsperado (modo automático)
      const hayPedidos = pedidos.length > 0;
      const montoValido = Number.isFinite(efectivo) && efectivo >= 0;
      const totalEsperadoValido = totalEsperado > 0;
      
      btnCerrar.disabled = !(hayPedidos && montoValido && totalEsperadoValido);
    }
  }

  function onCambioEfectivo() {
    recalcular();
  }

  function onToggleEditarEfectivo() {
    const txtEfectivo = getEl("txtResEfectivo");
    const chkEditar = getEl("chkEditarEfectivo");

    if (!txtEfectivo || !chkEditar) return;

    if (chkEditar.checked) {
      // Habilitar edición, pero manteniendo el valor actual (que será el esperado)
      txtEfectivo.disabled = false;
      txtEfectivo.focus();
      txtEfectivo.select();
    } else {
      // Volver a modo automático
      txtEfectivo.disabled = true;
      txtEfectivo.value = ""; // lo recalculamos abajo como esperado
    }

    recalcular();
  }

  function initDesdeCabecera() {
    const txtEfectivo = getEl("txtResEfectivo");
    const chkEditar = getEl("chkEditarEfectivo");

    if (txtEfectivo) {
      txtEfectivo.disabled = true; // siempre inicia bloqueado
      txtEfectivo.value = "";
    }
    if (chkEditar) {
      chkEditar.checked = false; // siempre inicia desmarcado
    }

    // Recalcular determinará si habilitar o no el botón según los datos cargados
    recalcular();
  }

  function initResumen30() {
    const txtEfectivo = getEl("txtResEfectivo");
    const chkEditar = getEl("chkEditarEfectivo");

    if (txtEfectivo) {
      txtEfectivo.addEventListener("input", onCambioEfectivo);
    }
    if (chkEditar) {
      chkEditar.addEventListener("change", onToggleEditarEfectivo);
    }
  }

  window.Resumen30 = {
    init: initResumen30,
    initDesdeCabecera,
    recalcular,
  };
})();