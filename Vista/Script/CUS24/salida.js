// /Vista/Script/CUS24/salida.js
(function () {

function showLoading(text = "Generando guías…") {
  const dlg = document.getElementById("appLoading");
  const t   = document.getElementById("appLoadingText");
  if (!dlg) return;
  if (t) t.textContent = text;

  // evitar cerrar con ESC
  dlg.addEventListener("cancel", (e) => e.preventDefault(), { once: true });

  if (typeof dlg.showModal === "function") {
    if (!dlg.open) dlg.showModal();
  } else {
    dlg.setAttribute("open", "open");
  }
}

function hideLoading() {
  const dlg = document.getElementById("appLoading");
  if (!dlg) return;
  try {
    if (typeof dlg.close === "function" && dlg.open) dlg.close();
  } catch (_) {}
  // fallback por si no hay <dialog>.close() o quedó atributo
  dlg.removeAttribute("open");
}

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

/* function limpiarCampos() {
  const root = document.querySelector("main.container") || document;

  // 1) Vaciar inputs y textareas
  root.querySelectorAll("input, textarea").forEach(el => {
    const t = (el.type || "").toLowerCase();
    if (t === "button" || t === "submit" || t === "reset" || t === "file") return;
    if (t === "checkbox" || t === "radio") { el.checked = false; return; }
    el.value = "";
  });

  // 2) Reiniciar selects (si los hubiera)
  root.querySelectorAll("select").forEach(sel => sel.selectedIndex = 0);

  // 3) Limpiar todas las tablas (solo cuerpos)
  root.querySelectorAll("table tbody").forEach(tb => tb.innerHTML = "");

  // 4) Limpiar contenedores/msgs usados en la vista
  const ids = ["gruposResumen","gruposLista","msg","msgAsignacion","msgPedidos","modalGuiasList","modalMailInfo","repEmailView","txtOrigen"];
  ids.forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    if (id === "gruposLista" || id === "modalGuiasList") el.innerHTML = "";
    else if (id === "repEmailView") el.textContent = "—";
    else el.textContent = "";
  });

  // 5) Recalcular el estado del botón (opcional)
  if (typeof canGenerate === "function") canGenerate();
} */

function limpiarCampos() {
  const root = document.querySelector("main.container") || document;

  // 1) Vaciar inputs y textareas
  root.querySelectorAll("input, textarea").forEach(el => {
    const t = (el.type || "").toLowerCase();
    if (t === "button" || t === "submit" || t === "reset" || t === "file") return;
    if (t === "checkbox" || t === "radio") { el.checked = false; return; }
    el.value = "";
  });

  // 2) Reiniciar selects
  root.querySelectorAll("select").forEach(sel => sel.selectedIndex = 0);

  // 3) Limpiar todas las tablas (solo cuerpos)
  root.querySelectorAll("table tbody").forEach(tb => tb.innerHTML = "");

  // 4) Limpiar contenedores/msgs
  const ids = ["gruposResumen","gruposLista","msg","msgAsignacion","msgPedidos","modalGuiasList","modalMailInfo","repEmailView","txtOrigen"];
  ids.forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    if (id === "gruposLista" || id === "modalGuiasList") el.innerHTML = "";
    else if (id === "repEmailView") el.textContent = "—";
    else el.textContent = "";
  });

  // 5) Estado global mínimo
  if (window.Asignacion) {
    window.Asignacion.grupos = [];
    window.Asignacion.id = null;
    window.Asignacion.idAsignacionRV = null;
  }
  window.ORIGEN = {};

  // 6) Estado inicial de botones/controles
  document.getElementById("btnGenerar")?.setAttribute("disabled", "disabled");
  document.getElementById("btnBuscar")?.removeAttribute("disabled");
  document.getElementById("txtAsignacion")?.removeAttribute("disabled");

  // 7) Recalcular habilitación
  if (typeof canGenerate === "function") canGenerate();
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
  showLoading("Generando guías…");

  let r;
  try {
    r = await fetchJSON(url.generarSalidaLote, {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    if (!r?.ok) {
      window.Utils24?.showMsg?.(msg, "error", r?.error || "No se pudo generar el lote.", { autoclear: 4000 });
      return;
    }

    const total = (r.guias || []).length;
    const bloqueos = (r.bloqueos || []).length;
    const estadoAsig = r.asignacion?.estado || "";

    // preparar contenido del modal de resultado
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

    // IMPORTANTE: cerrar loader ANTES de mostrar el modal final
    hideLoading();

    showModal({
      title: "Guías generadas",
      message: `Se generaron ${total} guía(s). ${bloqueos ? bloqueos + " grupo(s) bloqueado(s). " : ""}Asignación: ${estadoAsig}.`,
      okText: "Entendido",
      onOk: () => {
        // Limpiar todos los campos
        limpiarCampos();
        
        // Refrescar la asignación si es necesario
        if (window.Asignacion?.buscar) {
          const txt = document.getElementById("txtAsignacion");
          if (txt && txt.value.trim()) window.Asignacion.buscar();
        }
      },
    });

  } catch (e) {
    window.Utils24?.showMsg?.(msg, "error", "No se pudo conectar al servidor.", { autoclear: 3500 });
  } finally {
    hideLoading();
    btn?.removeAttribute("disabled");
  }
}

function init() {
  document.getElementById("btnGenerar")?.addEventListener("click", onGenerarSalida);
  document.addEventListener("DOMContentLoaded", canGenerate);
}
document.addEventListener("DOMContentLoaded", init);

// API expuesta
window.SalidaCUS24 = Object.assign(window.SalidaCUS24 || {}, {
  canGenerate,
  limpiarCampos
});
})();