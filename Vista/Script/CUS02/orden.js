// /Vista/Script/CUS02/orden.js
(function () {
  const { $, log, setNum, to2, msg, setDirty } = window.Utils;
  const { fetchJSON, url } = window.API;

  async function cargarMetodosEntrega() {
    const r = await fetchJSON(url.metodosEntrega);
    if (!r.ok) { msg("No se pudieron cargar los métodos de entrega.", true); return; }

    const cbo = $("#cboEntrega");
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
    setNum($("#txtCostoEnt"), costo);
    log("Métodos cargados y selección inicial aplicada.");
  }

 function vaciarConsolidado() {
    const tb = $("#tblItems tbody");
    if (tb) tb.innerHTML = "";

    $("#txtCantProd").value = 0;
    window.Utils.setNum($("#txtDesc"), 0);
    window.Utils.setNum($("#txtSubTotal"), 0);

    const costo = Number($("#cboEntrega").selectedOptions[0]?.dataset.costo || 0);
    setNum($("#txtCostoEnt"), costo);
    setNum($("#txtTotal"), 0);

    const btn = $("#btnRegistrar");
    if (btn) btn.disabled = true;
  }

  function pintarItemsConsolidados(r) {
    const tb = $("#tblItems tbody");
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

    $("#txtCantProd").value = r.cantidadProductos || 0;
    setNum($("#txtDesc"), r.descuento);
    setNum($("#txtSubTotal"), r.subtotal);

    const costo = Number($("#cboEntrega").selectedOptions[0]?.dataset.costo || 0);
    setNum($("#txtCostoEnt"), costo);
    setNum($("#txtTotal"), Math.max(0, (r.subtotal || 0) - (r.descuento || 0) + costo));

    $("#btnRegistrar").disabled = !(r.items || []).length;
  }

  function onMetodoEntregaChange(e) {
    const costo = Number(e.target?.selectedOptions?.[0]?.dataset?.costo || 0);
    setNum($("#txtCostoEnt"), costo);

    const subt = Number($("#txtSubTotal").value || 0);
    const desc = Number($("#txtDesc").value || 0);
    if (subt > 0) {
      const total = Math.max(0, subt - desc + costo);
      setNum($("#txtTotal"), total);
      setDirty(true); // ← cambió algo relevante con items presentes
    }
    log("Método de entrega cambiado.");
  }

  async function registrarOrden() {
    const dni = ($("#txtDni").value || "").trim();
    const val = window.Utils.validarDni(dni);
    if (!val.ok) { msg(val.msg, true); $("#txtDni").focus(); return; }

    const sel = window.Preorden.idsSeleccionadas();
    if (!sel.length) { msg("Debe seleccionar al menos una preorden.", true); return; }

    const metodoEntregaId = Number($("#cboEntrega").value);
    const descuento = Number($("#txtDesc").value || 0);

    const payload = new URLSearchParams();
    payload.append("dni", dni);
    payload.append("metodoEntregaId", String(metodoEntregaId));
    payload.append("descuento", String(descuento));
    sel.forEach((v, i) => payload.append(`idsPreorden[${i}]`, String(v)));

    const r = await fetchJSON(url.registrar, { method: "POST", body: payload });
    if (!r.ok) { msg(r.error || "No se pudo registrar", true); return; }

    msg(`Orden #${r.ordenId} registrada.`);
    $("#btnRegistrar").disabled = true;
    setDirty(false); // ← ya no hay cambios pendientes
    window.Cliente.limpiarCliente();
    
  }

  window.Orden = {
    cargarMetodosEntrega,
    vaciarConsolidado,
    pintarItemsConsolidados,
    onMetodoEntregaChange,
    registrarOrden
  };
})();
