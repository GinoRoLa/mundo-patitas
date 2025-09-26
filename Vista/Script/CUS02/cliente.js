// /Vista/Script/CUS02/cliente.js
(function () {
  const { $, log, setNum, setDirty, Messages } = window.Utils;
  const { fetchJSON, url } = window.API;

  const NO_ADDR = "— Sin direcciones de envío —";

  /** Pinta un placeholder en el combo de direcciones guardadas y lo deshabilita. */
  function setGuardadaPlaceholder(text = NO_ADDR) {
    const wrap = $("#envioGuardada");
    const cbo  = $("#cboDireccionGuardada");
    if (!wrap || !cbo) return;

    cbo.innerHTML = "";
    const opt = document.createElement("option");
    opt.value = "";
    opt.disabled = true;
    opt.selected = true;
    opt.textContent = text;
    cbo.appendChild(opt);

    cbo.disabled = true;
    wrap.hidden  = false;
  }

  /** Llena el combo de direcciones guardadas (t70) y deja data-* para prefill/validación. */
  function poblarDireccionesGuardadas(dirs) {
    const wrap   = $("#envioGuardada");
    const cbo    = $("#cboDireccionGuardada");
    const rGuard = document.querySelector('input[name="envioModo"][value="guardada"]');
    const rOtra  = document.querySelector('input[name="envioModo"][value="otra"]');
    if (!wrap || !cbo) return;

    // Limpiar siempre
    cbo.innerHTML = "";

    if (Array.isArray(dirs) && dirs.length > 0) {
      dirs.forEach((d) => {
        const opt = document.createElement("option");
        opt.value = d.Id_DireccionEnvio;

        const nombre    = d.NombreContacto    ?? "";
        const telefono  = d.TelefonoContacto  ?? "";
        const direccion = d.Direccion         ?? "";
        const distrito  = d.Distrito          ?? "";
        const dniRec    = d.DniReceptor       ?? "";

        // Texto visible
        opt.textContent = `${direccion} — ${distrito} (${nombre} / ${telefono})`;

        // Data para snapshot / validaciones
        opt.dataset.nombre   = nombre;
        opt.dataset.telefono = telefono;
        opt.dataset.dir      = direccion;
        opt.dataset.distrito = distrito;
        opt.dataset.dni      = dniRec; // siempre set, aunque esté vacío

        cbo.appendChild(opt);
      });

      // (debug opcional) imprime el primero para verificar
      if (cbo.options.length > 0) {
        const o0 = cbo.options[0];
        console.log("[t70] option#0 dataset:", {dni:o0.dataset.dni, distrito:o0.dataset.distrito, dir:o0.dataset.dir});
      }

      cbo.disabled = false;
      wrap.hidden  = false;

      // Modo por defecto: guardada
      if (rGuard) { rGuard.disabled = false; rGuard.checked = true; }
      if (rOtra)  { rOtra.checked = false; }
      window.Orden.setEnvioModo("guardada");
    } else {
      // Sin direcciones: placeholder y modo "otra"
      setGuardadaPlaceholder(NO_ADDR);
      if (rGuard) rGuard.checked = false;
      if (rOtra)  { rOtra.checked = true; rOtra.disabled = false; }
      window.Orden.setEnvioModo("otra");
    }

    window.Orden.validarReadyParaRegistrar();
  }

  /** Limpia la ficha del cliente, tablas y totales. Resetea estado y mensajes. */
  function limpiarCliente() {
    ["#txtDni","#txtNombre","#txtApePat","#txtApeMat","#txtTel","#txtDir","#txtEmail"]
      .forEach((sel) => { const el = document.querySelector(sel); if (el) el.value = ""; });

    const tbPre = document.querySelector("#tblPreorden tbody"); if (tbPre) tbPre.innerHTML = "";
    const tbIt  = document.querySelector("#tblItems tbody");   if (tbIt)  tbIt.innerHTML = "";
    document.querySelectorAll(".chk-pre").forEach((c) => (c.checked = false));

    $("#txtCantProd").value = 0;
    setNum($("#txtDesc"), 0);
    setNum($("#txtSubTotal"), 0);

    const cbo = $("#cboEntrega");
    $("#chkGuardarDireccion") && ($("#chkGuardarDireccion").checked = true);
    if (cbo && cbo.options.length) {
      const idx = Array.from(cbo.options).findIndex((o) => /tienda/i.test(o.textContent));
      cbo.selectedIndex = idx >= 0 ? idx : 0;
      const costo = Number(cbo.selectedOptions[0]?.dataset.costo || 0);
      setNum($("#txtCostoEnt"), costo);
    } else {
      setNum($("#txtCostoEnt"), 0);
    }
    setNum($("#txtTotal"), 0);

    poblarDireccionesGuardadas([]); // placeholder
    const rOtra = document.querySelector('input[name="envioModo"][value="otra"]');
    if (rOtra) rOtra.checked = true;
    window.Orden.setEnvioModo("otra");
    window.Orden.updateEnvioPanelVisibility();

    $("#btnRegistrar").disabled = true;
    const btnAgregar = $("#btnAgregar"); if (btnAgregar) btnAgregar.disabled = true;

    window.Preorden?.resetStale?.();
    setDirty(false);
    $("#txtDni")?.focus();

    Messages.cliente.clear();
    Messages.preorden.clear();
  }

  /** Busca cliente por DNI, pinta datos, preórdenes y direcciones. */
  async function buscarCliente() {
    Messages.cliente.clear();
    Messages.preorden.clear();

    const dni = ($("#txtDni").value || "").trim();
    const v = window.Utils.validarDni(dni);
    if (!v.ok) {
      Messages.cliente.error(v.msg, { persist: true });
      $("#txtDni").focus();
      return;
    }

    let r;
    try {
      r = await fetchJSON(url.buscarCliente, {
        method: "POST",
        body: new URLSearchParams({ dni }),
      });
    } catch {
      Messages.cliente.error("No se pudo conectar. Inténtalo nuevamente.", { autoclear: 6000 });
      return;
    }

    if (!r || !r.ok) {
      Messages.cliente.error(r?.error || "No se pudo obtener el cliente.", { autoclear: 6000 });
      return;
    }
    if (!r.found) {
      limpiarCliente();
      Messages.cliente.error("Cliente no encontrado o inactivo.", { persist: true });
      return;
    }

    $("#txtNombre").value = r.cliente.des_nombreCliente || "";
    $("#txtApePat").value = r.cliente.des_apepatCliente || "";
    $("#txtApeMat").value = r.cliente.des_apematCliente || "";
    $("#txtTel").value    = r.cliente.num_telefonoCliente || "";
    $("#txtEmail").value  = r.cliente.email_cliente || "";
    $("#txtDir").value    = r.cliente.direccionCliente || "";

    Messages.cliente.ok("Cliente encontrado.", { autoclear: 1300 });

    // Direcciones guardadas (t70) con distrito y DNI receptor en data-*
    poblarDireccionesGuardadas(r.direcciones || []);

    // Preórdenes
    const preos = r.preordenes || [];
    window.Preorden.pintarPreordenes(preos);

    if (!preos.length) {
      Messages.preorden.error("El cliente no tiene preórdenes válidas en las últimas 24 horas.", { persist: true });
    }

    log("Cliente y preórdenes pintados.");
  }

  // Export API pública
  window.Cliente = {
    limpiarCliente,
    buscarCliente,
    poblarDireccionesGuardadas,
    setGuardadaPlaceholder,
  };
})();
