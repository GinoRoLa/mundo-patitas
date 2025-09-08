// /Vista/Script/CUS02/orden.js
(function () {
  const { $, log, setNum, to2, msg, setDirty } = window.Utils;
  const { fetchJSON, url } = window.API;

  function isDeliverySelected() {
    const opt = $("#cboEntrega")?.selectedOptions?.[0];
    if (!opt) return false;
    if (opt.dataset.esDelivery !== undefined) {
      return opt.dataset.esDelivery === "1" || opt.dataset.esDelivery === "true";
    }
    return /delivery/i.test(opt.textContent || "");
  }

  //SIN optional chaining en el lado izquierdo de asignaciones
  function setEnvioModo(modo) {
    const guardada = (modo === 'guardada');

    const wrapGuard = document.getElementById('envioGuardada');
    const selGuard  = document.getElementById('cboDireccionGuardada');

    const wrapOtra  = document.getElementById('envioOtra');
    const inpNom    = document.getElementById('envioNombre');
    const inpTel    = document.getElementById('envioTelefono');
    const inpDir    = document.getElementById('envioDireccion');

    if (wrapGuard) wrapGuard.style.display = guardada ? 'block' : 'none';
    if (wrapOtra)  wrapOtra.style.display  = guardada ? 'none'  : 'block';

    if (selGuard) { selGuard.disabled = !guardada; selGuard.required = guardada; }
    [inpNom, inpTel, inpDir].forEach(i => { if (i) { i.disabled = guardada; i.required = !guardada; } });
  }

  function updateEnvioPanelVisibility() {
    const panel = document.getElementById('envioPanel');
    if (!panel) return;

    const esDel = isDeliverySelected();
    panel.style.display = esDel ? 'block' : 'none';

    if (!esDel) {
      const nom = document.getElementById('envioNombre');
      const tel = document.getElementById('envioTelefono');
      const dir = document.getElementById('envioDireccion');
      const chk = document.getElementById('chkGuardarDireccion');
      if (nom) nom.value = '';
      if (tel) tel.value = '';
      if (dir) dir.value = '';
      if (chk) chk.checked = false;
    } else {
      const modo = document.querySelector('input[name="envioModo"]:checked')?.value || 'otra';
      setEnvioModo(modo);
    }
    validarReadyParaRegistrar();
  }

  function validarReadyParaRegistrar() {
    const cant = Number($("#txtCantProd")?.value || 0);
    let ok = cant > 0;

    if (ok && isDeliverySelected()) {
      const modo = (document.querySelector('input[name="envioModo"]:checked')?.value) || "otra";
      if (modo === "guardada") {
        ok = !!document.getElementById('cboDireccionGuardada')?.value;
      } else {
        const nom = (document.getElementById('envioNombre')?.value || "").trim();
        const tel = (document.getElementById('envioTelefono')?.value || "").trim();
        const dir = (document.getElementById('envioDireccion')?.value || "").trim();
        const telOk = /^\d{9}$/.test(tel); 
        ok = nom !== "" && dir !== "" && telOk; 
      }
    }
    const btn = document.getElementById('btnRegistrar');
    if (btn) btn.disabled = !ok;
  }

  async function cargarMetodosEntrega() {
    const r = await fetchJSON(url.metodosEntrega);
    if (!r.ok) { msg("No se pudieron cargar los métodos de entrega.", true); return; }

    const cbo = document.getElementById('cboEntrega');
    cbo.innerHTML = "";
    (r.metodos || []).forEach((m) => {
      const opt = document.createElement("option");
      opt.value = m.Id_MetodoEntrega;
      opt.textContent = m.Descripcion;
      opt.dataset.costo = m.Costo;
      cbo.appendChild(opt);
    });

    const idx = Array.from(cbo.options).findIndex((o) => /tienda/i.test(o.textContent));
    cbo.selectedIndex = idx >= 0 ? idx : 0;

    const costo = Number(cbo.selectedOptions[0]?.dataset.costo || 0);
    setNum(document.getElementById('txtCostoEnt'), costo);
    updateEnvioPanelVisibility();
  }

  function vaciarConsolidado() {
    const tb = document.querySelector("#tblItems tbody");
    if (tb) tb.innerHTML = "";
    document.getElementById('txtCantProd').value = 0;
    setNum(document.getElementById('txtDesc'), 0);
    setNum(document.getElementById('txtSubTotal'), 0);
    const costo = Number(document.getElementById('cboEntrega').selectedOptions[0]?.dataset.costo || 0);
    setNum(document.getElementById('txtCostoEnt'), costo);
    setNum(document.getElementById('txtTotal'), 0);
    const btn = document.getElementById('btnRegistrar');
    if (btn) btn.disabled = true;
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

    document.getElementById('txtCantProd').value = r.cantidadProductos || 0;
    setNum(document.getElementById('txtDesc'), r.descuento);
    setNum(document.getElementById('txtSubTotal'), r.subtotal);

    const costo = Number(document.getElementById('cboEntrega').selectedOptions[0]?.dataset.costo || 0);
    setNum(document.getElementById('txtCostoEnt'), costo);
    setNum(document.getElementById('txtTotal'), Math.max(0, (r.subtotal || 0) - (r.descuento || 0) + costo));

    validarReadyParaRegistrar();
  }

  function onMetodoEntregaChange(e) {
    const costo = Number(e.target?.selectedOptions?.[0]?.dataset?.costo || 0);
    setNum(document.getElementById('txtCostoEnt'), costo);

    const subt = Number(document.getElementById('txtSubTotal').value || 0);
    const desc = Number(document.getElementById('txtDesc').value || 0);
    if (subt > 0) {
      const total = Math.max(0, subt - desc + costo);
      setNum(document.getElementById('txtTotal'), total);
      setDirty(true);
    }
    updateEnvioPanelVisibility();
  }

  async function registrarOrden() {
    const dni = (document.getElementById('txtDni').value || "").trim();
    const v = window.Utils.validarDni(dni);
    if (!v.ok) { msg(v.msg, true); document.getElementById('txtDni').focus(); return; }

    const sel = window.Preorden.idsSeleccionadas();
    if (!sel.length) { msg("Debe seleccionar al menos una preorden.", true); return; }
    if (window.Preorden.isStale && window.Preorden.isStale()) {
      msg('La selección cambió. Vuelve a presionar "Agregar a la orden".', true);
      return;
    }

    const metodoEntregaId = Number(document.getElementById('cboEntrega').value);
    const descuento = Number(document.getElementById('txtDesc').value || 0);

    const payload = new URLSearchParams();
    payload.append("dni", dni);
    payload.append("metodoEntregaId", String(metodoEntregaId));
    payload.append("descuento", String(descuento));
    sel.forEach((v, i) => payload.append(`idsPreorden[${i}]`, String(v)));

    if (isDeliverySelected()) {
      const modo = (document.querySelector('input[name="envioModo"]:checked')?.value) || "otra";
      if (modo === "guardada") {
        const idSel = document.getElementById('cboDireccionGuardada')?.value;
        if (!idSel) { msg("Seleccione una dirección guardada o elija 'otra'.", true); return; }
        payload.append("direccionEnvioId", String(idSel));
      } else {
        const nom = (document.getElementById('envioNombre')?.value || "").trim();
        const tel = (document.getElementById('envioTelefono')?.value || "").trim();
        const dir = (document.getElementById('envioDireccion')?.value || "").trim();
        if (!nom || !tel || !dir) { msg("Complete nombre, teléfono y dirección de envío.", true); return; }
        payload.append("envioNombre", nom);
        payload.append("envioTelefono", tel);
        payload.append("envioDireccion", dir);
        if (document.getElementById('chkGuardarDireccion')?.checked) payload.append("guardarDireccionCliente", "1");
      }
    }

    const r = await fetchJSON(url.registrar, { method: "POST", body: payload });
    if (!r.ok) { msg(r.error || "No se pudo registrar", true); return; }

    msg(`Orden #${r.ordenId} registrada.`);
    document.getElementById('btnRegistrar').disabled = true;
    setDirty(false);
    window.Cliente.limpiarCliente();
    updateEnvioPanelVisibility();
  }

  // eventos de radios (si no lo haces en main)
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[name="envioModo"]').forEach(r => {
      r.addEventListener('change', (e) => {
        setEnvioModo(e.target.value);
        validarReadyParaRegistrar();
        setDirty(true);
      });
    });
  });

  // ✅ export
  window.Orden = {
    cargarMetodosEntrega,
    vaciarConsolidado,
    pintarItemsConsolidados,
    onMetodoEntregaChange,
    registrarOrden,
    isDeliverySelected,
    validarReadyParaRegistrar,
    updateEnvioPanelVisibility,
    setEnvioModo
  };
})();
