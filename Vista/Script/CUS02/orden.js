// /Vista/Script/CUS02/orden.js
(function () {
  const { $, log, setNum, to2, setDirty, Messages } = window.Utils;
  const { fetchJSON, url } = window.API;

  function isDeliverySelected() {
    const opt = document.getElementById("cboEntrega")?.selectedOptions?.[0];
    if (!opt) return false;
    if (opt.dataset.esDelivery !== undefined) {
      return opt.dataset.esDelivery === "1" || opt.dataset.esDelivery === "true";
    }
    return /delivery/i.test(opt.textContent || "");
  }

  function setEnvioModo(modo) {
    const guardada = modo === "guardada";

    const wrapGuard = document.getElementById("envioGuardada");
    const selGuard  = document.getElementById("cboDireccionGuardada");

    const wrapOtra  = document.getElementById("envioOtra");
    const inpNom    = document.getElementById("envioNombre");
    const inpTel    = document.getElementById("envioTelefono");
    const inpDir    = document.getElementById("envioDireccion");

    if (wrapGuard) wrapGuard.hidden = !guardada;
    if (wrapOtra)  wrapOtra.style.display = guardada ? "none" : "block";

    if (selGuard) {
      selGuard.disabled = !guardada;
      selGuard.required = guardada;

      if (guardada && (selGuard.options.length === 0 || (selGuard.options.length === 1 && selGuard.options[0].value === ""))) {
        if (selGuard.options.length === 0) {
          const opt = document.createElement("option");
          opt.value = ""; opt.disabled = true; opt.selected = true;
          opt.textContent = "— Sin direcciones de envio —";
          selGuard.appendChild(opt);
        } else {
          selGuard.options[0].textContent = "— Sin direcciones de envio —";
          selGuard.options[0].disabled = true;
          selGuard.options[0].selected = true;
        }
        selGuard.disabled = true;
      }
    }

    [inpNom, inpTel, inpDir].forEach((i) => { if (i) { i.disabled = guardada; i.required = !guardada; } });
  }

  function updateEnvioPanelVisibility() {
    const panel = document.getElementById("envioPanel");
    if (!panel) return;

    const esDel = isDeliverySelected();
    panel.style.display = esDel ? "block" : "none";

    if (!esDel) {
      ["envioNombre", "envioTelefono", "envioDireccion"].forEach((id) => { const el = document.getElementById(id); if (el) el.value = ""; });
      const chk = document.getElementById("chkGuardarDireccion"); if (chk) chk.checked = true;
    } else {
      const cbo = document.getElementById("cboDireccionGuardada");
      const hayGuardadas = cbo && !cbo.disabled && cbo.options.length > 0;
      const defaultModo = hayGuardadas ? "guardada" : "otra";
      const radio = document.querySelector(`input[name="envioModo"][value="${defaultModo}"]`);
      if (radio) radio.checked = true;
      window.Orden.setEnvioModo(defaultModo);
    }

    validarReadyParaRegistrar();
  }

  function validarReadyParaRegistrar() {
    const cant = Number(document.getElementById("txtCantProd")?.value || 0);
    let ok = cant > 0;

    if (ok && isDeliverySelected()) {
      const modo = document.querySelector('input[name="envioModo"]:checked')?.value || "otra";
      if (modo === "guardada") {
        ok = !!document.getElementById("cboDireccionGuardada")?.value;
      } else {
        const nom = (document.getElementById("envioNombre")?.value || "").trim();
        const tel = (document.getElementById("envioTelefono")?.value || "").trim();
        const dir = (document.getElementById("envioDireccion")?.value || "").trim();
        const telOk = /^\d{9}$/.test(tel);
        ok = nom !== "" && dir !== "" && telOk;
      }
    }
    const btn = document.getElementById("btnRegistrar");
    if (btn) btn.disabled = !ok;
  }

  async function cargarMetodosEntrega() {
    const r = await fetchJSON(url.metodosEntrega);
    if (!r.ok) { Messages.global.error("No se pudieron cargar los métodos de entrega.", { autoclear: 6000 }); return; }

    const cbo = document.getElementById("cboEntrega");
    cbo.innerHTML = "";
    (r.metodos || []).forEach((m) => {
      const opt = document.createElement("option");
      opt.value = m.Id_MetodoEntrega;
      opt.textContent = m.Descripcion;
      opt.dataset.costo = m.Costo;
      // opt.dataset.esDelivery = m.EsDelivery ? "1" : "0";
      cbo.appendChild(opt);
    });

    const idx = Array.from(cbo.options).findIndex((o) => /tienda/i.test(o.textContent));
    cbo.selectedIndex = idx >= 0 ? idx : 0;

    const costo = Number(cbo.selectedOptions[0]?.dataset.costo || 0);
    setNum(document.getElementById("txtCostoEnt"), costo);
    updateEnvioPanelVisibility();
    Messages.global.clear();
  }

  function vaciarConsolidado() {
    const tb = document.querySelector("#tblItems tbody");
    if (tb) tb.innerHTML = "";
    document.getElementById("txtCantProd").value = 0;
    setNum(document.getElementById("txtDesc"), 0);
    setNum(document.getElementById("txtSubTotal"), 0);
    const costo = Number(document.getElementById("cboEntrega").selectedOptions[0]?.dataset.costo || 0);
    setNum(document.getElementById("txtCostoEnt"), costo);
    setNum(document.getElementById("txtTotal"), 0);
    const btn = document.getElementById("btnRegistrar");
    if (btn) btn.disabled = true;
    Messages.preorden.clear();
  }

  function pintarItemsConsolidados(r) {
    const tb = document.querySelector("#tblItems tbody");
    tb.innerHTML = "";
    (r.items || []).forEach((it) => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${it.IdProducto}</td>
        <td>${it.NombreProducto}</td>
        <td>${to2(it.PrecioUnitario)}</td>
        <td>${it.Cantidad}</td>
        <td>${to2(it.Subtotal)}</td>
      `;
      tb.appendChild(tr);
    });

    document.getElementById("txtCantProd").value = r.cantidadProductos || 0;
    setNum(document.getElementById("txtDesc"), r.descuento);
    setNum(document.getElementById("txtSubTotal"), r.subtotal);

    const costo = Number(document.getElementById("cboEntrega").selectedOptions[0]?.dataset.costo || 0);
    setNum(document.getElementById("txtCostoEnt"), costo);
    setNum(document.getElementById("txtTotal"), Math.max(0, (r.subtotal || 0) - (r.descuento || 0) + costo));

    validarReadyParaRegistrar();
    const n = Number(document.getElementById("txtCantProd").value || 0);
    if (n > 0) Messages.preorden.ok("Productos consolidados en la orden.", { autoclear: 1500 });
  }

  function onMetodoEntregaChange(e) {
    const costo = Number(e.target?.selectedOptions?.[0]?.dataset?.costo || 0);
    setNum(document.getElementById("txtCostoEnt"), costo);

    const subt = Number(document.getElementById("txtSubTotal").value || 0);
    const desc = Number(document.getElementById("txtDesc").value || 0);
    if (subt > 0) {
      const total = Math.max(0, subt - desc + costo);
      setNum(document.getElementById("txtTotal"), total);
      setDirty(true);
    }
    updateEnvioPanelVisibility();
    Messages.preorden.clear();
  }

  async function registrarOrden() {
    const dni = (document.getElementById("txtDni").value || "").trim();
    const v = window.Utils.validarDni(dni);
    if (!v.ok) { Messages.cliente.error(v.msg, { persist: true }); document.getElementById("txtDni").focus(); return; }

    const sel = window.Preorden.idsSeleccionadas();
    if (!sel.length) { Messages.preorden.error("Debes seleccionar al menos una preorden.", { persist: true }); return; }
    if (window.Preorden.isStale && window.Preorden.isStale()) {
      Messages.preorden.error('La selección cambió. Vuelve a presionar "Agregar a la orden".', { persist: true });
      return;
    }

    const metodoEntregaId = Number(document.getElementById("cboEntrega").value);
    const descuento = Number(document.getElementById("txtDesc").value || 0);

    const payload = new URLSearchParams();
    payload.append("dni", dni);
    payload.append("metodoEntregaId", String(metodoEntregaId));
    payload.append("descuento", String(descuento));
    sel.forEach((v, i) => payload.append(`idsPreorden[${i}]`, String(v)));

    if (isDeliverySelected()) {
      const modo = document.querySelector('input[name="envioModo"]:checked')?.value || "otra";
      if (modo === "guardada") {
        const idSel = document.getElementById("cboDireccionGuardada")?.value;
        if (!idSel) { Messages.preorden.error("Selecciona una dirección guardada o elige 'otra'.", { persist: true }); return; }
        payload.append("direccionEnvioId", String(idSel));
      } else {
        const nom = (document.getElementById("envioNombre")?.value || "").trim();
        const tel = (document.getElementById("envioTelefono")?.value || "").trim();
        const dir = (document.getElementById("envioDireccion")?.value || "").trim();
        const telOk = /^\d{9}$/.test(tel);
        if (!nom || !telOk || !dir) { Messages.preorden.error("Completa nombre, teléfono (9 dígitos) y dirección de envío.", { persist: true }); return; }
        payload.append("envioNombre", nom);
        payload.append("envioTelefono", tel);
        payload.append("envioDireccion", dir);
        if (document.getElementById("chkGuardarDireccion")?.checked) payload.append("guardarDireccionCliente", "1");
      }
    }

    const r = await fetchJSON(url.registrar, { method: "POST", body: payload });
    if (!r.ok) { Messages.preorden.error(r.error || "No se pudo registrar la orden.", { autoclear: 6500 }); return; }

    AppDialog.alert(`Orden #${r.ordenId} generada.`, {
      title: "Orden generada",
      onClose: () => {
        document.getElementById("btnRegistrar").disabled = true;
        setDirty(false);
        window.Cliente.limpiarCliente();
        Messages.global.ok(`Orden #${r.ordenId} generada.`, { autoclear: 1500 });
        updateEnvioPanelVisibility();
      },
    });
  }

  // Export sólo funciones (los listeners están en main.js)
  window.Orden = {
    cargarMetodosEntrega,
    vaciarConsolidado,
    pintarItemsConsolidados,
    onMetodoEntregaChange,
    registrarOrden,
    isDeliverySelected,
    validarReadyParaRegistrar,
    updateEnvioPanelVisibility,
    setEnvioModo,
  };
})();
