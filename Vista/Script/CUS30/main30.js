// /vista/Script/CUS30/main30.js
(function () {
  const showToast = window.Utils30?.showToast || ((m) => alert(m));

  function getEl(id) {
    return document.getElementById(id);
  }

  function toNumber(val) {
    const n = Number(val);
    return Number.isFinite(n) ? n : 0;
  }

  /* ===========================================
     LIMPIAR TODO EL CUS30 (después de cerrar)
  ============================================ */
  function limpiarCUS30() {
    // 1) Estado global
    window.CUS30 = window.CUS30 || {};
    window.CUS30.state = {
      cabecera: {},
      pedidos: [],
    };

    // 2) Limpiar campo DNI
    const txtDni = getEl("txtDniRepartidor");
    if (txtDni) txtDni.value = "";

    // 3) Tabla de asignaciones
    const tblAsig = document.querySelector("#tblAsignaciones tbody");
    if (tblAsig) tblAsig.innerHTML = "";
    const msgAsig = getEl("msgAsignaciones");
    if (msgAsig) {
      msgAsig.textContent = "";
      msgAsig.classList.remove("ok", "error");
    }

    // 3) Cabecera
    const panelCab = getEl("panelCabecera");
    if (panelCab) panelCab.hidden = true;

    [
      "txtCabIdAsignacion",
      "txtCabIdNotaCaja",
      "txtCabFondo",
      "txtCabRepartidor",
      "txtCabEstadoNota",
      "txtCabEstadoRuta",
    ].forEach((id) => {
      const el = getEl(id);
      if (el) el.value = "";
    });

    // 4) Tabla de pedidos
    const tblPed = document.querySelector("#tblPedidos tbody");
    if (tblPed) tblPed.innerHTML = "";
    const msgPed = getEl("msgPedidos");
    if (msgPed) {
      msgPed.textContent = "";
      msgPed.classList.remove("ok", "error");
    }

    // 5) Resumen
    [
      "txtResVentas",
      "txtResVuelto",
      "txtResEsperado",
      "txtResEfectivo",
      "txtResDiferencia",
    ].forEach((id) => {
      const el = getEl(id);
      if (el) el.value = "";
    });

    // Diferencia: reset color
    const txtDif = getEl("txtResDiferencia");
    if (txtDif) txtDif.style.color = "";

    // 6) Limpiar mensaje de cierre
    const msgCierre = getEl("msgCierre");
    if (msgCierre) {
      msgCierre.textContent = "";
      msgCierre.classList.remove("ok", "error");
    }

    // 7) Deshabilitar efectivo contado y botón cierre
    const txtEfectivo = getEl("txtResEfectivo");
    if (txtEfectivo) {
      txtEfectivo.disabled = true;
      txtEfectivo.value = "";
    }

    const btnCerrar = getEl("btnCerrarRecaudacion");
    if (btnCerrar) btnCerrar.disabled = true;

    // 8) Cerrar modales si quedaron abiertos
    const dlg = getEl("dlgPedido");
    if (dlg && dlg.open) dlg.close();

    const dlgMsg = getEl("dlgMsg");
    if (dlgMsg && dlgMsg.open) dlgMsg.close();

    const dlgConf = getEl("dlgConfirmRecaudacion");
    if (dlgConf && dlgConf.open) dlgConf.close();
  }

  /* ===========================================
     CERRAR RECAUDACIÓN (envío al backend)
  ============================================ */
  async function cerrarRecaudacionHandler() {
    const btn = getEl("btnCerrarRecaudacion");
    const msg = getEl("msgCierre");
    if (!btn) return;

    window.CUS30 = window.CUS30 || {};
    const st = window.CUS30.state || {};
    const cab = st.cabecera || {};
    const pedidos = st.pedidos || [];

    const txtEfectivo = getEl("txtResEfectivo");
    const efectivoContado = txtEfectivo ? toNumber(txtEfectivo.value) : 0;
    const txtVentas = getEl("txtResVentas");
    const txtVuelto = getEl("txtResVuelto");
    const txtEsperado = getEl("txtResEsperado");
    const txtDif = getEl("txtResDiferencia");

    // Fondo calculado
    const fondo =
      st.fondo ??
      cab.FondoRetirado ??
      cab.MontoFondo ??
      cab.MontoFondoRetirado ??
      0;

    // Feedback visual de procesamiento
    if (window.Processing30) {
      window.Processing30.show(
        "Cerrando recaudación",
        "Por favor, espera mientras se registra la recaudación…"
      );
    }

    btn.disabled = true;
    if (msg) {
      msg.textContent = "Procesando cierre de recaudación...";
      msg.classList.remove("ok", "error");
    }

    // ---- FormData con nombres EXACTOS que espera el controlador ----
    const fd = new FormData();
    fd.append("idOrdenAsignacion", cab.Id_OrdenAsignacion || "");
    fd.append(
      "idTrabajadorRepartidor",
      cab.Id_Trabajador || st.Id_TrabajadorRepartidor || ""
    );
    fd.append("idNotaCajaDelivery", cab.Id_NotaCajaDelivery || "");

    fd.append("montoFondoRetirado", Number(fondo).toFixed(2));
    fd.append("montoVentasEsperado", txtVentas ? txtVentas.value || "0" : "0");
    fd.append("montoVueltoEsperado", txtVuelto ? txtVuelto.value || "0" : "0");
    fd.append(
      "montoEfectivoEntregado",
      efectivoContado.toFixed(2)
    );
    fd.append(
      "montoRetornoEsperado",
      txtEsperado ? txtEsperado.value || "0" : "0"
    );
    fd.append("diferencia", txtDif ? txtDif.value || "0" : "0");

    // Mapear pedidos al formato que espera el backend (camelCase)
    const detalleNormalizado = pedidos.map((p) => ({
      idOrdenPedido: p.Id_OrdenPedido,
      montoPedido: toNumber(p.MontoPedido),
      montoCobrado: toNumber(p.MontoCobrado),
      montoVueltoEntregado: toNumber(p.MontoVueltoEntregado),
      estadoPedido: p.EstadoPedido || "Entregado",
    }));

    fd.append("detalleJson", JSON.stringify(detalleNormalizado));

    const res = await API30.fetchJSON(API30.url.cerrarRecaudacion, {
      method: "POST",
      body: fd,
    });

    if (window.Processing30) {
      window.Processing30.hide();
    }

    btn.disabled = false;

    if (!res.ok) {
      console.error("Error cerrar-recaudacion:", res);
      const errorMsg = res.error || "No se pudo cerrar la recaudación.";
      if (msg) {
        msg.textContent = errorMsg;
        msg.classList.remove("ok");
        msg.classList.add("error");
      }
      showToast(errorMsg, "error");
      return;
    }

    // Éxito
    const successMsg = res.msg || "Recaudación cerrada correctamente.";
    if (msg) {
      msg.textContent = successMsg;
      msg.classList.remove("error");
      msg.classList.add("ok");
    }

    showToast(
      successMsg,
      res.estadoFinal === "Cuadrado" ? "ok" : "info"
    );

    // Limpiar totalmente el CUS
    limpiarCUS30();

    // Volver a cargar las asignaciones del último DNI digitado (si aún está)
    const txtDni = getEl("txtDniRepartidor");
    if (txtDni && txtDni.value.trim() && window.Asignaciones30) {
      window.Asignaciones30.buscarAsignacionesPorDni();
    }
  }

  /* ===========================================
     MODAL DE CONFIRMACIÓN ANTES DE CERRAR
  ============================================ */
  function wireConfirmacionCerrar() {
    const btn = getEl("btnCerrarRecaudacion");
    if (!btn) return;

    btn.addEventListener("click", (ev) => {
      ev.preventDefault();

      window.CUS30 = window.CUS30 || {};
      const st = window.CUS30.state || {};
      const cab = st.cabecera || {};
      const pedidos = st.pedidos || [];

      const txtEfectivo = getEl("txtResEfectivo");
      const rawEf = txtEfectivo ? txtEfectivo.value.trim() : "";
      const efectivo = rawEf === "" ? NaN : Number(rawEf);

      if (!cab.Id_OrdenAsignacion || !pedidos.length) {
        showToast("Debe seleccionar una asignación con pedidos antes de cerrar.", "error");
        return;
      }

      if (!Number.isFinite(efectivo) || efectivo < 0) {
        showToast("Ingrese un monto válido en Efectivo Contado.", "error");
        txtEfectivo && txtEfectivo.focus();
        return;
      }

      const fondo =
        st.fondo ??
        cab.FondoRetirado ??
        cab.MontoFondo ??
        cab.MontoFondoRetirado ??
        0;

      const totalPedido = Number(getEl("txtResVentas")?.value || "0");
      const totalVuelto = Number(getEl("txtResVuelto")?.value || "0");
      const esperado = Number(getEl("txtResEsperado")?.value || "0");
      const diferencia = efectivo - esperado;

      const dlg = getEl("dlgConfirmRecaudacion");
      if (!dlg || !dlg.showModal) {
        // Fallback a confirm clásico del navegador
        const msg =
          `Fondo: S/ ${fondo.toFixed(2)}\n` +
          `Total pedido entregado: S/ ${totalPedido.toFixed(2)}\n` +
          `Total vuelto: S/ ${totalVuelto.toFixed(2)}\n` +
          `Monto esperado de retorno: S/ ${esperado.toFixed(2)}\n` +
          `Efectivo contado: S/ ${efectivo.toFixed(2)}\n` +
          `Diferencia: S/ ${diferencia.toFixed(2)}\n\n` +
          `¿Desea registrar la recaudación?`;
        if (confirm(msg)) {
          cerrarRecaudacionHandler();
        }
        return;
      }

      // Si existe diálogo <dialog>, rellenar campos y mostrar
      const body = getEl("dlgConfBody");
      if (body) {
        body.textContent =
          `Fondo retirado: S/ ${fondo.toFixed(2)}\n` +
          `Total pedido (entregado): S/ ${totalPedido.toFixed(2)}\n` +
          `Total vuelto de caja: S/ ${totalVuelto.toFixed(2)}\n` +
          `Monto esperado de retorno: S/ ${esperado.toFixed(2)}\n` +
          `Efectivo contado: S/ ${efectivo.toFixed(2)}\n` +
          `Diferencia: S/ ${diferencia.toFixed(2)}`;
      }

      const btnConfirm = getEl("btnConfirmRecaudacionOk");
      const btnCancel = getEl("btnConfirmRecaudacionCancel");

      // Para evitar múltiples listeners, limpiamos antes
      if (btnConfirm) {
        btnConfirm.onclick = null;
        btnConfirm.onclick = (e) => {
          e.preventDefault();
          dlg.close("ok");
          cerrarRecaudacionHandler();
        };
      }
      if (btnCancel) {
        btnCancel.onclick = null;
        btnCancel.onclick = (e) => {
          e.preventDefault();
          dlg.close("cancel");
        };
      }

      dlg.showModal();
    });
  }

  /* ===========================================
     Botón Salir
  ============================================ */
  function wireBtnSalir() {
    const btn = getEl("btnSalir");
    if (!btn) return;

    btn.addEventListener("click", () => {
      if (confirm("¿Desea salir? Se perderán los cambios no guardados.")) {
        window.location.href = "http://localhost:8080/mundo-patitas/";
      }
    });
  }

  /* ===========================================
     INIT GENERAL DEL CUS30
  ============================================ */
  function initCUS30() {
    window.CUS30 = window.CUS30 || {};
    window.CUS30.state = window.CUS30.state || {};

    // Estado inicial: efectivo y cierre deshabilitados
    const txtEfectivo = getEl("txtResEfectivo");
    if (txtEfectivo) {
      txtEfectivo.disabled = true;
      txtEfectivo.value = "";
    }
    const btnCerrar = getEl("btnCerrarRecaudacion");
    if (btnCerrar) {
      btnCerrar.disabled = true;
    }

    // Inicializar módulos
    window.Actor30 && window.Actor30.cargarActor30();
    window.Asignaciones30 && window.Asignaciones30.init();
    window.Pedidos30 && window.Pedidos30.init();
    window.Resumen30 && window.Resumen30.init();

    // Eventos de cierre y salir
    wireConfirmacionCerrar();
    wireBtnSalir();
  }

  document.addEventListener("DOMContentLoaded", initCUS30);
})();