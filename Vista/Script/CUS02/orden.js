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

  /** Recalcula el costo de entrega:
   * - Si NO es delivery: usa el costo base del método (normalmente 0)
   * - Si es delivery: intenta costo por distrito (guardada u “otra”) usando window.costoPorNombreLocal
   * y actualiza txtCostoEnt + total.
   */
  function recomputeCostoEntrega() {
    const cboEntrega = document.getElementById("cboEntrega");
    const base = Number(cboEntrega?.selectedOptions?.[0]?.dataset?.costo || 0);
    let costo = base;

    if (isDeliverySelected()) {
      const modo = document.querySelector('input[name="envioModo"]:checked')?.value || "otra";
      let distritoTxt = "";

      if (modo === "guardada") {
        const opt = document.getElementById("cboDireccionGuardada")?.selectedOptions?.[0];
        distritoTxt = opt?.dataset?.distrito || "";
      } else {
        distritoTxt = (document.getElementById("envioDistrito")?.value || "").trim();
      }

      if (distritoTxt && typeof window.costoPorNombreLocal === "function") {
        const det = window.costoPorNombreLocal(distritoTxt);
        if (det && typeof det.MontoCosto !== "undefined") {
          costo = Number(det.MontoCosto);
        }
      }
    }

    setNum(document.getElementById("txtCostoEnt"), costo);

    // Recalcula total si ya hay subtotal/desc
    const subt = Number(document.getElementById("txtSubTotal")?.value || 0);
    const desc = Number(document.getElementById("txtDesc")?.value || 0);
    if (subt || desc) {
      setNum(document.getElementById("txtTotal"), Math.max(0, subt - desc + costo));
    }
  }

  // === Prefill desde dirección guardada + LOG ===
  function _prefillDesdeGuardada(debug = true) {
    const opt = document.getElementById("cboDireccionGuardada")?.selectedOptions?.[0];
    if (!opt) return;

    const dni      = opt.dataset?.dni || "";
    const distrito = opt.dataset?.distrito || "";
    const dir      = opt.dataset?.dir || "";
    const nombre   = opt.dataset?.nombre || "";
    const tel      = opt.dataset?.telefono || "";

    if (debug) {
      console.log("[ENVIO:GUARDADA] id=%s", opt.value);
      console.log("  DNI receptor :", dni);
      console.log("  Distrito     :", distrito);
      console.log("  Dirección    :", dir);
      console.log("  Contacto/Tel :", nombre, "/", tel);
      console.log("  dataset full :", opt.dataset);
      if (!dni || !distrito) {
        console.warn("⚠ Falta data-dni o data-distrito en el <option>. Revisa cliente.js / backend.");
      }
    }

    const dniRec        = document.getElementById("envioReceptorDni");
    const distritoInput = document.getElementById("envioDistrito");
    if (dniRec)        { dniRec.value = dni; dniRec.readOnly = true; }
    if (distritoInput) { distritoInput.value = distrito; distritoInput.readOnly = true; }
  }

  function _bindGuardadaChangeOnce() {
    const selGuard = document.getElementById("cboDireccionGuardada");
    if (!selGuard || selGuard._boundGuardChange) return;
    selGuard._boundGuardChange = true;

    selGuard.addEventListener("change", () => {
      // Prefill de DNI/Distrito
      _prefillDesdeGuardada(true);
      // Recalcula costo SOLO si estamos en delivery
      if (isDeliverySelected()) recomputeCostoEntrega();
      // Revalida botón
      window.Orden?.validarReadyParaRegistrar?.();
    });
  }

  function setEnvioModo(modo) {
    const guardada = modo === "guardada";

    const wrapGuard = document.getElementById("envioGuardada");
    const selGuard  = document.getElementById("cboDireccionGuardada");

    const wrapOtra  = document.getElementById("envioOtra");
    const inpNom    = document.getElementById("envioNombre");
    const inpTel    = document.getElementById("envioTelefono");
    const inpDir    = document.getElementById("envioDireccion");

    // Mostrar/ocultar secciones
    if (wrapGuard) wrapGuard.hidden = !guardada;
    if (wrapOtra)  wrapOtra.style.display = guardada ? "none" : "block";

    // Combo de guardadas
    if (selGuard) {
      selGuard.disabled = !guardada;
      selGuard.required = guardada;

      if (
        guardada &&
        (selGuard.options.length === 0 ||
         (selGuard.options.length === 1 && selGuard.options[0].value === ""))
      ) {
        if (selGuard.options.length === 0) {
          const opt = document.createElement("option");
          opt.value = ""; opt.disabled = true; opt.selected = true;
          opt.textContent = "— Sin direcciones de envío —";
          selGuard.appendChild(opt);
        } else {
          selGuard.options[0].textContent = "— Sin direcciones de envío —";
          selGuard.options[0].disabled = true;
          selGuard.options[0].selected = true;
        }
        selGuard.disabled = true;
      }

      // DNI/Distrito según modo
      const dniRec   = document.getElementById("envioReceptorDni");
      const distrito = document.getElementById("envioDistrito");
      if (guardada) {
        // Prefill inmediato
        _prefillDesdeGuardada(true);

        if (dniRec)   dniRec.readOnly = true;
        if (distrito) distrito.readOnly = true;

        _bindGuardadaChangeOnce(); // escucha cambios en el combo una sola vez
      } else {
        if (dniRec)   { dniRec.readOnly = false; dniRec.value = ""; }
        if (distrito) { distrito.readOnly = false; distrito.value = ""; }

        // Activa el typeahead local para “otra” (definido en distritos.js)
        if (typeof window.setupDistritoTypeahead === "function") {
          window.setupDistritoTypeahead();
        }
      }
    }

    // Inputs de “otra” dirección
    [inpNom, inpTel, inpDir].forEach((i) => {
      if (i) { i.disabled = guardada; i.required = !guardada; }
    });

    // Recalcula costo sólo si estamos en delivery
    if (isDeliverySelected()) recomputeCostoEntrega();
    // Revalida
    validarReadyParaRegistrar();
  }

  function updateEnvioPanelVisibility() {
    const panel = document.getElementById("envioPanel");
    if (!panel) return;

    const esDel = isDeliverySelected();
    panel.style.display = esDel ? "block" : "none";

    const dniRec   = document.getElementById("envioReceptorDni");
    const distrito = document.getElementById("envioDistrito");

    if (!esDel) {
      ["envioNombre","envioTelefono","envioDireccion","envioReceptorDni","envioDistrito"]
        .forEach((id) => { const el = document.getElementById(id); if (el) el.value = ""; });

      if (dniRec)   dniRec.required = false;
      if (distrito) distrito.required = false;

      const chk = document.getElementById("chkGuardarDireccion");
      if (chk) chk.checked = true;
    } else {
      // Selección automática
      const cbo = document.getElementById("cboDireccionGuardada");
      const hayGuardadas = cbo && !cbo.disabled && cbo.options.length > 0;
      const defaultModo  = hayGuardadas ? "guardada" : "otra";
      const radio = document.querySelector(`input[name="envioModo"][value="${defaultModo}"]`);
      if (radio) radio.checked = true;
      setEnvioModo(defaultModo);

      // Atributos de validación en delivery
      if (dniRec) {
        dniRec.required   = true;
        dniRec.maxLength  = 8;
        dniRec.pattern    = "\\d{8}";
        dniRec.setAttribute("inputmode", "numeric");
      }
      if (distrito) {
        distrito.required = true;
        distrito.maxLength = 120;
      }

      // Listener del combo para prefill + validar (si no estaba)
      const cboGuard = document.getElementById("cboDireccionGuardada");
      if (hayGuardadas && cboGuard && !cboGuard._listenerApplied) {
        cboGuard.addEventListener("change", () => {
          if (document.querySelector('input[name="envioModo"]:checked')?.value === "guardada") {
            _prefillDesdeGuardada(true);
            if (isDeliverySelected()) recomputeCostoEntrega();
          }
          validarReadyParaRegistrar();
        });
        cboGuard._listenerApplied = true;
      }
    }

    // Centraliza el recálculo del costo y validación
    recomputeCostoEntrega();
    validarReadyParaRegistrar();
  }

  function validarReadyParaRegistrar() {
    const cant = Number(document.getElementById("txtCantProd")?.value || 0);
    let ok = cant > 0;

    if (ok && isDeliverySelected()) {
      const modo = document.querySelector('input[name="envioModo"]:checked')?.value || "otra";

      if (modo === "guardada") {
        const opt    = document.getElementById("cboDireccionGuardada")?.selectedOptions?.[0];
        const idSel  = opt?.value;
        const dni    = opt?.dataset?.dni || "";
        const dist   = opt?.dataset?.distrito || "";
        ok = !!idSel && /^\d{8}$/.test(dni) && dist !== "";

        // Refleja en inputs (por si estaban vacíos)
        const dniRec   = document.getElementById("envioReceptorDni");
        const distrito = document.getElementById("envioDistrito");
        if (dniRec)   { dniRec.value = dni; dniRec.readOnly = true; }
        if (distrito) { distrito.value = dist; distrito.readOnly = true; }
      } else {
        const dniRec   = (document.getElementById("envioReceptorDni")?.value || "").trim();
        const distrito = (document.getElementById("envioDistrito")?.value || "").trim();
        const nom      = (document.getElementById("envioNombre")?.value || "").trim();
        const tel      = (document.getElementById("envioTelefono")?.value || "").trim();
        const dir      = (document.getElementById("envioDireccion")?.value || "").trim();
        const telOk    = /^\d{9}$/.test(tel);
        ok = /^\d{8}$/.test(dniRec) && distrito !== "" && nom !== "" && dir !== "" && telOk;
      }
    }

    const btn = document.getElementById("btnRegistrar");
    if (btn) btn.disabled = !ok;
    return ok;
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

    // No seteamos txtCostoEnt directo; centralizamos:
    recomputeCostoEntrega();
    updateEnvioPanelVisibility();
    Messages.global.clear();
  }

  function vaciarConsolidado() {
    const tb = document.querySelector("#tblItems tbody");
    if (tb) tb.innerHTML = "";
    document.getElementById("txtCantProd").value = 0;
    setNum(document.getElementById("txtDesc"), 0);
    setNum(document.getElementById("txtSubTotal"), 0);
    // Recalcula costo según método actual (si delivery, por distrito; sino base)
    recomputeCostoEntrega();
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

    // Centraliza costo/total
    recomputeCostoEntrega();

    validarReadyParaRegistrar();
    const n = Number(document.getElementById("txtCantProd").value || 0);
    if (n > 0) Messages.preorden.ok("Productos consolidados en la orden.", { autoclear: 1500 });
  }

  function onMetodoEntregaChange() {
    // Recalcula costo (si es delivery, por distrito; sino base) y luego actualiza panel
    recomputeCostoEntrega();
    updateEnvioPanelVisibility();
    Messages.preorden.clear();
    setDirty(true);
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
    const descuento       = Number(document.getElementById("txtDesc").value || 0);

    if (!validarReadyParaRegistrar()) return;

    const payload = new URLSearchParams();
    payload.append("dni", dni);
    payload.append("metodoEntregaId", String(metodoEntregaId));
    payload.append("descuento", String(descuento));
    sel.forEach((v, i) => payload.append(`idsPreorden[${i}]`, String(v)));

    if (isDeliverySelected()) {
      const modo = document.querySelector('input[name="envioModo"]:checked')?.value || "otra";
      if (modo === "guardada") {
        const opt   = document.getElementById("cboDireccionGuardada")?.selectedOptions?.[0];
        const idSel = opt?.value;
        if (!idSel) {
          Messages.preorden.error("Selecciona una dirección guardada o elige 'otra'.", { persist: true });
          return;
        }
        payload.append("direccionEnvioId", String(idSel));
        payload.append("envioReceptorDni", opt?.dataset?.dni || "");
        payload.append("envioDistrito",   opt?.dataset?.distrito || "");
      } else {
        const nom   = (document.getElementById("envioNombre")?.value || "").trim();
        const tel   = (document.getElementById("envioTelefono")?.value || "").trim();
        const dir   = (document.getElementById("envioDireccion")?.value || "").trim();
        const dniRec= (document.getElementById("envioReceptorDni")?.value || "").trim();
        const dis   = (document.getElementById("envioDistrito")?.value || "").trim();

        payload.append("envioNombre", nom);
        payload.append("envioTelefono", tel);
        payload.append("envioDireccion", dir);
        payload.append("envioReceptorDni", dniRec);
        payload.append("envioDistrito", dis);
        if (document.getElementById("chkGuardarDireccion")?.checked) {
          payload.append("guardarDireccionCliente", "1");
        }
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

  // Export
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
