// /Vista/Script/CUS02/cliente.js
(function () {
  const { $, log, msg, setNum, setDirty } = window.Utils;
  const { fetchJSON, url } = window.API;

  function limpiarCliente() {
    ["#txtDni","#txtNombre","#txtApePat","#txtApeMat","#txtTel","#txtDir","#txtEmail"]
      .forEach((s) => { const el = document.querySelector(s); if (el) el.value = ""; });

    document.querySelector("#tblPreorden tbody").innerHTML = "";
    document.querySelector("#tblItems tbody").innerHTML   = "";

    $("#txtCantProd").value = 0;
    setNum($("#txtDesc"), 0);
    setNum($("#txtSubTotal"), 0);
    setNum($("#txtCostoEnt"), 0);
    setNum($("#txtTotal"), 0);

    $("#btnRegistrar").disabled = true;
    const btnAgregar = $("#btnAgregar"); if (btnAgregar) btnAgregar.disabled = true;

    setDirty(false); // ← pantalla limpia, sin cambios pendientes
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

    window.Preorden.pintarPreordenes(r.preordenes || []);
    if (!(r.preordenes || []).length) msg("El cliente no tiene preórdenes válidas en las últimas 24 horas.");
    else msg("");
    log("Cliente y preórdenes pintados.");
  }

  window.Cliente = { limpiarCliente, buscarCliente };
})();
