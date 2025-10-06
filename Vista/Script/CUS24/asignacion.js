// /Vista/Script/CUS24/asignacion.js
(function () {
  const $ = (sel, ctx = document) => ctx.querySelector(sel);

  /* ============ Limpieza / Pintado ============ */
  function limpiarUI() {
    ["#repNombre","#repApePat","#repApeMat","#repTel","#repEmail","#repLic",
     "#vehMarca","#vehPlaca","#vehModelo"].forEach(sel => {
      const el = $(sel); if (el) el.value = "";
    });
    const tb = $("#tblPedidos tbody"); if (tb) tb.innerHTML = "";
  }

  function pintarEncabezado(data) {
    const r = data.repartidor || {};
    const v = data.vehiculo   || {};
    $("#repNombre") && ($("#repNombre").value = r.nombre   || "");
    $("#repApePat") && ($("#repApePat").value = r.apePat   || "");
    $("#repApeMat") && ($("#repApeMat").value = r.apeMat   || "");
    $("#repTel")    && ($("#repTel").value    = r.telefono || "");
    $("#repEmail")  && ($("#repEmail").value  = r.email    || "");
    const lic = (r.licencia ?? r.licenciaConducir ?? r.dni ?? "") || "";
    $("#repLic")    && ($("#repLic").value = lic);

    $("#vehMarca")  && ($("#vehMarca").value  = v.marca  || "");
    $("#vehPlaca")  && ($("#vehPlaca").value  = v.placa  || "");
    $("#vehModelo") && ($("#vehModelo").value = v.modelo || "");
  }

  function pintarPedidosFallback(pedidos = []) {
    const tb = $("#tblPedidos tbody");
    if (!tb) return;
    tb.innerHTML = "";
    pedidos.forEach(p => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td data-label="Código (OP)">${p.op ?? ""}</td>
        <td data-label="Cliente">${p.cliente ?? ""}</td>
        <td data-label="Dirección">${p.direccion ?? ""}</td>
        <td data-label="Distrito">${p.distrito ?? ""}</td>
        <td data-label="OSE">${p.ose ?? ""}</td>
        <td data-label="Estado">${p.estado ?? ""}</td>
        <td data-label="Acción"><button type="button" class="ghost" data-op="${p.op ?? ""}">Ver</button></td>
      `;
      tb.appendChild(tr);
    });
  }

  /* ============ Búsqueda ============ */
  async function buscarAsignacion() {
    const input = $("#txtAsignacion");
    const msgEl = $("#msgAsignacion");

    // Validar con Utils24
    const v = window.Utils24.validateNumericInput(input, msgEl, { required: true });
    if (!v.ok) { input?.focus(); return; }

    // Loading
    const btn = $("#btnBuscar");
    btn && (btn.disabled = true);
    input && (input.disabled = true);
    window.Utils24.showMsg(msgEl, "info", "Buscando asignación…", { autoclear: 0 });

    // Llamada
    if (!window.API24?.fetchJSON || !window.API24?.url?.buscarAsignacion) {
      btn && (btn.disabled = false);
      input && (input.disabled = false);
      window.Utils24.showMsg(msgEl, "error", "API no disponible.", { autoclear: 3500 });
      return;
    }

    limpiarUI();

    let res;
    try {
      res = await window.API24.fetchJSON(window.API24.url.buscarAsignacion(input.value.trim()), {
        method: "GET",
        credentials: "include"
      });
    } catch {
      btn && (btn.disabled = false);
      input && (input.disabled = false);
      window.Utils24.showMsg(msgEl, "error", "No se pudo conectar al servidor.", { autoclear: 4000 });
      return;
    }

    btn && (btn.disabled = false);
    input && (input.disabled = false);

    if (!res || !res.ok) {
      window.Utils24.showMsg(msgEl, "error", res?.error || "No se pudo obtener la asignación.", { autoclear: 4000 });
      return;
    }

    // Encabezado
    pintarEncabezado(res);

    // Pedidos
    const pedidos = Array.isArray(res.pedidos) ? res.pedidos : [];
    if (pedidos.length > 0) {
      if (window.Pedidos?.pintarLista) window.Pedidos.pintarLista(pedidos);
      else pintarPedidosFallback(pedidos);
      window.Utils24.showMsg(msgEl, "ok", `Se encontraron ${pedidos.length} pedido(s) para retirar.`, { autoclear: 2500 });
    } else {
      if (window.Pedidos?.pintarLista) window.Pedidos.pintarLista([]);
      window.Utils24.showMsg(msgEl, "info", "No hay pedidos pendientes en esta asignación.", { autoclear: 3000 });
    }
  }

  /* ============ Init ============ */
  function init() {
    // Validación “live” + Enter que llama a buscarAsignacion
    window.Utils24.bindNumericValidation('#txtAsignacion', '#msgAsignacion', {
      required: true,
      btn: '#btnBuscar',
      validateOn: 'input',
      onValid: buscarAsignacion
    });

    // Click en botón
    $("#btnBuscar")?.addEventListener("click", buscarAsignacion);
  }

  document.addEventListener("DOMContentLoaded", init);

  // Export opcional
  window.Asignacion = { buscar: buscarAsignacion, pintarEncabezado, limpiarUI };
})();
