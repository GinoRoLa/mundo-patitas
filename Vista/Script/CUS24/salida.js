// /Vista/Script/CUS24/salida.js
(function () {
  function showModal({ title="Mensaje", message="", okText="Aceptar", onOk=null } = {}) {
    const dlg = document.getElementById("appDialog");
    const h3  = document.getElementById("appDialogTitle");
    const p   = document.getElementById("appDialogMsg");
    const ok  = document.getElementById("appDialogOk");
    const cancel = document.getElementById("appDialogCancel");
    if (!dlg) return;

    if (h3) h3.textContent = title;
    if (p)  p.textContent  = message;
    if (cancel) cancel.style.display = "none";
    if (ok) {
      ok.textContent = okText || "Aceptar";
      ok.addEventListener("click", function handle() {
        dlg.close("ok");
        ok.removeEventListener("click", handle);
        if (typeof onOk === "function") onOk();
      });
    }
    if (dlg.showModal) dlg.showModal(); else dlg.setAttribute("open","open");
  }

  function canGenerate() {
    const grupos = (window.Asignacion?.grupos || []);
    const email  = document.getElementById("repEmail")?.value || "";
    const btn    = document.getElementById("btnGenerar");
    const ok = grupos.length > 0 && !!email;
    if (btn) btn.disabled = !ok;
    return ok;
  }

  async function onGenerarSalida() {
    const msg = document.getElementById("msg");
    const grupos = (window.Asignacion?.grupos || []).slice();
    if (!grupos.length) {
      window.Utils24?.showMsg?.(msg, "error", "No hay grupos para generar.", { autoclear: 3000 });
      return;
    }

    const asigId = window.Asignacion?.id ?? null;
    const asigRV = window.Asignacion?.idAsignacionRV ?? null;
    if (!asigId || !asigRV) {
      window.Utils24?.showMsg?.(msg, "error", "Falta la asignación o Id_AsignacionRV.", { autoclear: 3500 });
      return;
    }

    const origen = window.ORIGEN || {}; // { id, ... }
    if (!origen.id) {
      window.Utils24?.showMsg?.(msg, "error", "Falta origen (almacén).", { autoclear: 3000 });
      return;
    }

    // Normalizamos payload grupos
    const gruposPayload = grupos.map(g => ({
      key: g.key,
      dni: g.dni,
      nombre: g.nombre || "",
      direccion: g.dir || g.direccion || "",
      distritoNombre: g.distritoNombre || "",
      ops: Array.isArray(g.ops) ? g.ops : [],
    })).filter(g => g.ops.length > 0);

    if (!gruposPayload.length) {
      window.Utils24?.showMsg?.(msg, "error", "No hay OP elegibles en los grupos.", { autoclear: 3000 });
      return;
    }

    const vehiculo = {
      marca: document.getElementById("vehMarca")?.value || "",
      placa: document.getElementById("vehPlaca")?.value || "",
      modelo: document.getElementById("vehModelo")?.value || "",
    };
    const transportista = {
      conductor: document.getElementById("guiaConductor")?.value || "",
      licencia: document.getElementById("guiaLic")?.value || "",
    };

    const payload = {
      asignacionId: asigId,
      asignacionRV: asigRV,
      origen: { id: origen.id },
      vehiculo,
      transportista,
      // opcional: serie/remitente configurables
      // serie: "001", remitenteRuc: "...", remitenteRazon: "...",
      grupos: gruposPayload
    };

    const { fetchJSON, url } = window.API24 || {};
    if (!fetchJSON || !url?.generarSalidaLote) {
      window.Utils24?.showMsg?.(msg, "error", "API no disponible.", { autoclear: 3000 });
      return;
    }

    const btn = document.getElementById("btnGenerar");
    btn?.setAttribute("disabled", "disabled");
    window.Utils24?.showMsg?.(msg, "info", "Generando guías…", { autoclear: 0 });

    let r;
    try {
      r = await fetchJSON(url.generarSalidaLote, {
        method: "POST",
        credentials: "include",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
    } catch (e) {
      window.Utils24?.showMsg?.(msg, "error", "No se pudo conectar al servidor.", { autoclear: 3500 });
      btn?.removeAttribute("disabled");
      return;
    }

    if (!r?.ok) {
      window.Utils24?.showMsg?.(msg, "error", r?.error || "No se pudo generar el lote.", { autoclear: 4000 });
      btn?.removeAttribute("disabled");
      return;
    }

    // Construir feedback
    const total = (r.guias || []).length;
    const bloqueos = (r.bloqueos || []).length;
    const estadoAsig = r.asignacion?.estado || "";
    window.Utils24?.showMsg?.(msg, "ok",
      `Se generaron ${total} guía(s). ${bloqueos ? bloqueos + " grupo(s) bloqueado(s). " : ""}Estado t40: ${estadoAsig}.`,
      { autoclear: 6000 }
    );

    // Modal con la lista de guías
    try {
      const ul = document.getElementById("modalGuiasList");
      if (ul) {
        ul.innerHTML = "";
        (r.guias || []).forEach(g => {
          const li = document.createElement("li");
          li.innerHTML = `<b>${g.numeroStr || g.numero}</b> — ${g.destino?.direccion || ""} (${g.destino?.distrito || ""})`;
          ul.appendChild(li);
        });
      }
      const mailInfo = document.getElementById("modalMailInfo");
      if (mailInfo) {
        mailInfo.textContent = `Se enviará correo al repartidor: ${document.getElementById("repEmail")?.value || "—"}.`;
      }
    } catch {}

    showModal({
      title: "Guías generadas",
      message: total ? `Se generaron ${total} guía(s).` : "No se generaron guías.",
      okText: "Entendido",
      onOk: () => {
        // Refrescar la asignación para mostrar pedidos disponibles (los 'Pagado' deberían disminuir)
        if (window.Asignacion?.buscar) {
          const txt = document.getElementById("txtAsignacion");
          if (txt && txt.value.trim()) window.Asignacion.buscar();
        }
      },
    });

    // (Opcional) abrir la primera guía en HTML con autoprint DESPUÉS del OK:
    // Lo haríamos en onOk si quieres. Si prefieres abrir ya mismo, descomenta:
    // const first = (r.guias || [])[0];
    // if (first?.id) window.open(`../../Controlador/ControladorGuiaHTML.php?id=${encodeURIComponent(first.id)}&autoprint=1`, "_blank");

    btn?.removeAttribute("disabled");
  }

  function init() {
    document.getElementById("btnGenerar")?.addEventListener("click", onGenerarSalida);
    // habilitar/deshabilitar por estado actual (grupos + email)
    document.addEventListener("DOMContentLoaded", canGenerate);
  }
  document.addEventListener("DOMContentLoaded", init);

  // API expuesta (por si otro módulo quiere recalcular habilitado)
  window.SalidaCUS24 = Object.assign(window.SalidaCUS24 || {}, {
    canGenerate
  });
})();
