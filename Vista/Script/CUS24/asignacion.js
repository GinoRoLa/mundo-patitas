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
    // (opcional) limpiar panel de ítems
    window.ItemsProductos?.limpiar?.();
    
    const txtDest = document.querySelector("#txtDireccionActiva");
  if (txtDest) txtDest.value = ""; // o "Sin dirección activa"
  if (window.Asignacion) window.Asignacion.destinoActivo = null;
  }

  function pintarEncabezado(data) {
  const r = data.repartidor || {};
  const v = data.vehiculo   || {};
  const asig = data.asignacion || {};

  // Datos del repartidor
  $("#repNombre") && ($("#repNombre").value = r.nombre   || "");
  $("#repApePat") && ($("#repApePat").value = r.apePat   || "");
  $("#repApeMat") && ($("#repApeMat").value = r.apeMat   || "");
  $("#repTel")    && ($("#repTel").value    = r.telefono || "");
  $("#repEmail")  && ($("#repEmail").value  = r.email    || "");

  const licCompat = r.licenciaInfo?.numero ?? r.licencia ?? r.licenciaConducir ?? r.dni ?? "";
  $("#repLic") && ($("#repLic").value = licCompat);

  // Vehículo
  $("#vehMarca")  && ($("#vehMarca").value  = v.marca  || "");
  $("#vehPlaca")  && ($("#vehPlaca").value  = v.placa  || "");
  $("#vehModelo") && ($("#vehModelo").value = v.modelo || "");

  // Campos guía (conductor)
  const fullName = [r.nombre, r.apePat, r.apeMat].filter(Boolean).join(" ").trim();
  const guiaDni  = r.dni || "";
  const guiaLic  = licCompat;

  $("#guiaDni")       && ($("#guiaDni").value       = guiaDni);
  $("#guiaLic")       && ($("#guiaLic").value       = guiaLic);
  $("#guiaConductor") && ($("#guiaConductor").value = fullName);

 window.Asignacion = window.Asignacion || {};
  window.Asignacion.id             = asig.id || null;              // t40
  window.Asignacion.idAsignacionRV = asig.idAsignacionRV || null;

  // Snapshot para la guía
  window.GUIA = {
    transportista: {
      dni: guiaDni,
      licencia: guiaLic,
      conductor: fullName,
      estadoLicencia: r.licenciaInfo?.estado ?? null
    },
    vehiculo: { marca: v.marca || "", placa: v.placa || "", modelo: v.modelo || "" }
  };
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
        <td data-label="Acción">
          <button type="button" class="btnAdd" data-op="${p.op ?? ""}">Agregar</button>
          </td>
      `;
      tb.appendChild(tr);
    });
  }

  /* ============ Buscar ============ */
  async function buscarAsignacion() {
    const input = $("#txtAsignacion");
    const msgEl = $("#msgAsignacion");

    const v = window.Utils24.validateNumericInput(input, msgEl, { required: true });
    if (!v.ok) { input?.focus(); return; }

    const btn = $("#btnBuscar");
    btn && (btn.disabled = true);
    input && (input.disabled = true);
    window.Utils24.showMsg(msgEl, "info", "Buscando asignación…", { autoclear: 0 });

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

    pintarEncabezado(res);
    // en asignacion.js tras pintarEncabezado(res):
window.Asignacion = window.Asignacion || {};
window.Asignacion.id = res.asignacion?.id || null;
window.Asignacion.idAsignacionRV = res.asignacion?.idAsignacionRV || null;


    const pedidos = Array.isArray(res.pedidos) ? res.pedidos : [];
    if (pedidos.length > 0) {
      if (window.Pedidos?.pintarLista) window.Pedidos.pintarLista(pedidos);
      else pintarPedidosFallback(pedidos);
      window.Utils24.showMsg(msgEl, "ok", `Se encontraron ${pedidos.length} pedido(s) para retirar.`, { autoclear: 2500 });
    } else {
      if (window.Pedidos?.pintarLista) window.Pedidos.pintarLista([]);
      window.Utils24.showMsg(msgEl, "ok", "No hay pedidos pendientes en esta asignación.", { autoclear: 3000 });
    }
  }

  /* ============ Delegación en la tabla ============ */
  // asignacion.js
function onTablaClick(e) {
  const btn = e.target.closest(".btnAdd"); // <-- antes .btn-ver-items
  if (!btn) return;
  const idOP = parseInt(btn.dataset.op || "0", 10);
  if (!idOP) { /* mostrar error */ return; }
  window.ItemsProductos?.cargarPorOP?.(idOP);
}


  /* ============ Init ============ */
  function init() {
    window.Utils24.bindNumericValidation('#txtAsignacion', '#msgAsignacion', {
      required: true,
      btn: '#btnBuscar',
      validateOn: 'input',
      onValid: buscarAsignacion
    });

    $("#btnBuscar")?.addEventListener("click", buscarAsignacion);

    // 🡲 Delegación una sola vez: sirve para filas dinámicas
    $("#tblPedidos tbody")?.addEventListener("click", onTablaClick);
  }

  document.addEventListener("DOMContentLoaded", init);

  window.Asignacion = { buscar: buscarAsignacion, pintarEncabezado, limpiarUI };
})();
