// /Vista/Script/CUS02/cliente.js
(function () {
  const { $, log, msg, setNum, setDirty } = window.Utils;
  const { fetchJSON, url } = window.API;

function poblarDireccionesGuardadas(dirs) {
    const wrap = $("#envioGuardada");
    const cbo  = $("#cboDireccionGuardada");
    const radioGuardada = document.querySelector('input[name="envioModo"][value="guardada"]');
    const radioOtra     = document.querySelector('input[name="envioModo"][value="otra"]');

    if (!wrap || !cbo) return;

    cbo.innerHTML = "";
    if (dirs && dirs.length) {
      dirs.forEach(d => {
        const opt = document.createElement("option");
        opt.value = d.Id_DireccionEnvio;
        opt.textContent = `${d.NombreContacto} — ${d.Direccion} (${d.TelefonoContacto})`;
        cbo.appendChild(opt);
      });
      if (radioGuardada) radioGuardada.disabled = false;
      // respeta el radio actual
      const current = document.querySelector('input[name="envioModo"]:checked')?.value || 'otra';
      window.Orden.setEnvioModo(current);
    } else {
      if (radioGuardada) { radioGuardada.disabled = true; radioGuardada.checked = false; }
      if (radioOtra) radioOtra.checked = true;
      window.Orden.setEnvioModo('otra');
    }
    window.Orden.validarReadyParaRegistrar();
  }

  function limpiarCliente() {
    // 1) Campos del cliente
    ["#txtDni","#txtNombre","#txtApePat","#txtApeMat","#txtTel","#txtDir","#txtEmail"]
      .forEach(sel => { const el = document.querySelector(sel); if (el) el.value = ""; });

    // 2) Tablas
    const tbPre = document.querySelector("#tblPreorden tbody"); if (tbPre) tbPre.innerHTML = "";
    const tbIt  = document.querySelector("#tblItems tbody");   if (tbIt)  tbIt.innerHTML = "";
    document.querySelectorAll(".chk-pre").forEach(c => c.checked = false);

    // 3) Totales
    $("#txtCantProd").value = 0;
    setNum($("#txtDesc"), 0);
    setNum($("#txtSubTotal"), 0);

    // 4) Método de entrega → vuelve a “tienda” (o la primera opción) y recalcula costo
    const cbo = $("#cboEntrega");
    // ahora: queda marcado por defecto
    $("#chkGuardarDireccion") && ($("#chkGuardarDireccion").checked = true);
    if (cbo && cbo.options.length) {
      const idx = Array.from(cbo.options).findIndex(o => /tienda/i.test(o.textContent));
      cbo.selectedIndex = idx >= 0 ? idx : 0;
      const costo = Number(cbo.selectedOptions[0]?.dataset.costo || 0);
      setNum($("#txtCostoEnt"), costo);
    } else {
      setNum($("#txtCostoEnt"), 0);
    }
    setNum($("#txtTotal"), 0);

    // 5) Panel de envío → limpia y oculta (queda en “otra” por defecto)
    poblarDireccionesGuardadas([]);            // deshabilita "guardada"
    const radioOtra = document.querySelector('input[name="envioModo"][value="otra"]');
    if (radioOtra) radioOtra.checked = true;
    window.Orden.setEnvioModo('otra');
    window.Orden.updateEnvioPanelVisibility(); // lo oculta si quedó "tienda"

    // 6) Botones / flags / mensajes
    $("#btnRegistrar").disabled = true;
    const btnAgregar = $("#btnAgregar"); if (btnAgregar) btnAgregar.disabled = true;
    window.Preorden?.resetStale?.();           // si implementaste la bandera "stale"
    setDirty(false);
    //msg("");                                   // borra cualquier mensaje
    $("#txtDni")?.focus();
  }

  async function buscarCliente() {
    const dni = ($("#txtDni").value || "").trim();
    const v = window.Utils.validarDni(dni);
    if (!v.ok) { msg(v.msg, true); $("#txtDni").focus(); return; }

    const r = await fetchJSON(url.buscarCliente, { method: "POST", body: new URLSearchParams({ dni }) });
    if (!r.ok && r.error) { msg(r.error, true); return; }

    if (!r.found) { msg("Cliente no encontrado."); limpiarCliente(); return; }

    $("#txtNombre").value = r.cliente.des_nombreCliente || "";
    $("#txtApePat").value = r.cliente.des_apepatCliente || "";
    $("#txtApeMat").value = r.cliente.des_apematCliente || "";
    $("#txtTel").value    = r.cliente.num_telefonoCliente || "";
    $("#txtEmail").value  = r.cliente.email_cliente || "";
    $("#txtDir").value    = r.cliente.direccionCliente || "";

    // Si el backend devuelve direcciones, llénalas:
    poblarDireccionesGuardadas(r.direcciones || []);

    window.Preorden.pintarPreordenes(r.preordenes || []);
    if (!(r.preordenes || []).length) msg("El cliente no tiene preórdenes válidas en las últimas 24 horas.");
    else msg("");
    log("Cliente y preórdenes pintados.");
  }

  window.Cliente = { limpiarCliente, buscarCliente, poblarDireccionesGuardadas };
})();
