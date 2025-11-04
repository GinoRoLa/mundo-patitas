// =======================================================
// CUS15.ordencompras.js
// - Evalúa requerimiento (greedy), habilita "Generar" si es válido
// - Genera OCs y (opcional) envía por correo en lote
// - Muestra resumen (si existe #modalOrdenes)
// =======================================================
(function () {
  const $ = (sel, ctx = document) => (ctx || document).querySelector(sel);
  const toast = (m, t="info") => {
    try { (window.Utils15?.showToast || window.Utils24?.showToast)?.(m, t); }
    catch { console.log("[TOAST]", t, m); }
  };

  // ===== Endpoints =====
  (function ensureEndpoints() {
    if (!window.API15 || !window.API15.base) return;
    const BASE = window.API15.base;
    window.API15.url = Object.assign({}, window.API15.url, {
      evaluar       : window.API15?.url?.evaluar        ?? `${BASE}?accion=evaluar`,
      generarOCs    : window.API15?.url?.generarOCs     ?? `${BASE}?accion=generar-ocs`,
      ocEnviarLote  : window.API15?.url?.ocEnviarLote   ?? `${BASE}?accion=oc-enviar-lote`,
    });
  })();

  // ===== Valida si la evaluación tiene adjudicación utilizable =====
  function evalTieneAdjudicacionValida(resEval) {
    const productos = resEval?.productos ?? [];
    for (const p of productos) {
      const asigs = p.Asignacion || p.asignacion || [];
      for (const a of asigs) {
        const cant  = Number(a.Cantidad ?? a.cantidad ?? 0);
        const prec  = Number(a.Precio   ?? a.precio   ?? a.PrecioUnitario ?? 0);
        const costo = Number(a.Costo    ?? a.costo    ?? 0);
        if (cant > 0 && (prec > 0 || costo > 0)) return true;
      }
    }
    return false;
  }

  // ===== Modal de resultado (simple, opcional si tu vista lo tiene) =====
  function abrirModalResultado({ idReq, genResp, sendResp }) {
    const modal = $("#modalOrdenes");
    if (!modal) return; // si no existe, omite sin error

    const titulo = modal.querySelector(".modal__title");
    const msg    = modal.querySelector(".modal__msg");
    const lista  = $("#listaOC");
    const banner = modal.querySelector(".mail-banner-info");
    const btnOk  = $("#btnConfirmarOC");
    const btnClose = modal.querySelector("[data-close]");

    if (titulo) titulo.textContent = "Resultado: Órdenes de Compra";
    if (msg) msg.textContent = "Resumen del proceso:";
    if (banner) banner.textContent = "Se muestran los resultados de generación y envío.";
    if (btnOk)  { btnOk.style.display = "none"; btnOk.replaceWith(btnOk.cloneNode(true)); }
    if (btnClose) btnClose.textContent = "Cerrar";

    const ordenes  = Array.isArray(genResp?.ordenes) ? genResp.ordenes : [];
    const totalGen = ordenes.length;

    const total    = Number(sendResp?.total ?? totalGen ?? 0);
    const enviados = Number(sendResp?.enviados ?? 0);
    const omitidos = Number(sendResp?.omitidos ?? 0);
    const errores  = Array.isArray(sendResp?.errores) ? sendResp.errores : [];
    const detalles = Array.isArray(sendResp?.detalles) ? sendResp.detalles : [];

    const mapDet = new Map();
    for (const d of detalles) mapDet.set(Number(d.idOC), d);
    const mapErr = new Map();
    for (const e of errores) mapErr.set(Number(e.idOC), e);

    const resumenHTML = `
      <div class="groups-summary" style="margin-bottom:10px">
        <b>Requerimiento:</b> ${idReq} · 
        <b>Generadas:</b> ${totalGen} · 
        <b>Enviadas:</b> ${enviados}/${total} · 
        <b>Omitidas:</b> ${omitidos} ${errores.length ? `· <b>Errores:</b> ${errores.length}` : ""}
      </div>
    `;

    const itemsHTML = ordenes.length === 0
      ? `<div class="hint">No se generaron órdenes para este requerimiento.</div>`
      : `
      <div class="table-scroll" style="max-height:none">
        <table class="table">
          <thead>
            <tr>
              <th>OC</th>
              <th>RUC</th>
              <th>Proveedor</th>
              <th style="text-align:right;">Total</th>
              <th>Correo</th>
              <th>Estado</th>
              <th>Mensaje</th>
            </tr>
          </thead>
          <tbody>
            ${
              ordenes.map(oc => {
                const idOC = Number(oc.idOC || oc.Id_OrdenCompra || 0);
                const det  = mapDet.get(idOC);
                const err  = mapErr.get(idOC);
                const status = err ? "error" : (det?.status || "ok");
                const badge =
                  status === "sent" || status === "ok" ? '<span class="status-badge ready">Enviado</span>' :
                  status === "error"                  ? '<span class="status-badge detected">Error</span>'  :
                                                         '<span class="status-badge">—</span>';
                const correo = det?.email || "—";
                const msg    = err?.error ? String(err.error).slice(0,180) : "—";
                const ruc    = oc.ruc || "—";
                const razon  = oc.razon || "—";
                const total  = (oc.total ?? 0).toFixed ? oc.total.toFixed(2) : Number(oc.total||0).toFixed(2);

                return `
                  <tr>
                    <td class="mono">#${idOC || "—"}</td>
                    <td>${ruc}</td>
                    <td>${razon}</td>
                    <td style="text-align:right;">S/ ${total}</td>
                    <td>${correo}</td>
                    <td>${badge}</td>
                    <td>${msg}</td>
                  </tr>
                `;
              }).join("")
            }
          </tbody>
        </table>
      </div>`;

    if (lista) lista.innerHTML = resumenHTML + itemsHTML;

    if (modal.showModal) modal.showModal();
    else modal.style.display = "flex";
  }

  // ===== Acciones =====
  async function evaluar() {
    const { fetchJSON, url } = window.API15 || {};
    const idReq = window.ReqCUS15?.getSelected?.();

    if (!idReq) return toast("Seleccione un requerimiento.", "warning");
    if (!fetchJSON || !url?.evaluar) return toast("API15 incompleta (evaluar).", "error");

    const btnEval = $("#btnEvaluar");
    const btnGen  = $("#btnGenerarOC");

    try {
      btnEval && (btnEval.disabled = true);
      window.Processing?.show?.("Evaluando requerimiento…", "Calculando adjudicación óptima (precio/stock).");

      const resEval = await fetchJSON(url.evaluar, {
        method: "POST",
        headers: { "Content-Type":"application/json" },
        body: JSON.stringify({ idReq })
      });

      if (!resEval?.ok) throw new Error(resEval?.error || "No se pudo evaluar.");
      const ok = evalTieneAdjudicacionValida(resEval);

      if (btnGen) btnGen.disabled = !ok;
      toast(ok ? "Evaluación OK: puedes generar OCs." : "Evaluación sin adjudicación utilizable.", ok ? "success" : "warning");

      // Aquí puedes pintar tu cuadro de evaluación si lo tienes (omito por simplicidad)

    } catch (e) {
      console.error(e);
      toast(e.message || "Error al evaluar", "error");
      // mantener Generar deshabilitado
      btnGen && (btnGen.disabled = true);
    } finally {
      window.Processing?.hide?.();
      btnEval && (btnEval.disabled = false);
    }
  }

  async function generar() {
    const { fetchJSON, url } = window.API15 || {};
    const idReq = window.ReqCUS15?.getSelected?.();

    if (!idReq) return toast("Seleccione un requerimiento.", "warning");
    if (!fetchJSON || !url?.generarOCs) return toast("API15 incompleta (generar-ocs).", "error");

    const btnGen = $("#btnGenerarOC");
    try {
      if (btnGen?.disabled) return; // evita sin evaluación o doble click
      btnGen && (btnGen.disabled = true);
      window.Processing?.show?.("Generando órdenes de compra…", "Creando OCs por proveedor.");

      // 1) Generar OCs
      const genResp = await fetchJSON(url.generarOCs, {
        method: "POST",
        headers: { "Content-Type":"application/json" },
        body: JSON.stringify({ idReq })
      });
      if (!genResp?.ok) throw new Error(genResp?.error || "No se pudieron generar las OCs.");
      const n = Array.isArray(genResp.ordenes) ? genResp.ordenes.length : 0;
      toast(`OC(s) generadas: ${n}`, n ? "success" : "info");

      // 2) Enviar lote (opcional, si endpoint existe)
      let sendResp = null;
      if (url?.ocEnviarLote) {
        window.Processing?.show?.("Enviando órdenes por correo…", "Adjuntando PDFs para cada proveedor.");
        sendResp = await fetchJSON(url.ocEnviarLote, {
          method: "POST",
          headers: { "Content-Type":"application/json" },
          body: JSON.stringify({ idReq })
        });
        if (!sendResp?.ok) throw new Error(sendResp?.error || "Fallo al enviar lote de correos.");
        const { total=0, enviados=0, omitidos=0, errores=[] } = sendResp;
        toast(`Envío: ${enviados}/${total} enviados · omitidos: ${omitidos}`, errores.length ? "warning" : "success");
      }

      // 3) Mostrar resumen (si tienes el modal en la vista)
      abrirModalResultado({ idReq, genResp, sendResp });

      // 4) Refrescar listado (si tu controlador lo expone)
      window.ReqCUS15?.reload?.();

    } catch (e) {
      console.error(e);
      toast(e.message || "Error generando/enviando OCs", "error");
      // si falló, re-habilita para reintentar
      btnGen && (btnGen.disabled = false);
    } finally {
      window.Processing?.hide?.();
    }
  }

  // ===== Wire UI =====
  function wire() {
    // Botón Evaluar
    $("#btnEvaluar")?.addEventListener("click", evaluar);

    // Botón Generar (queda deshabilitado al inicio hasta evaluar OK)
    const btnGen = $("#btnGenerarOC");
    if (btnGen) {
      btnGen.disabled = true;
      btnGen.addEventListener("click", generar);
    }

    // Si cambias de requerimiento en un select/lista, vuelve a bloquear "Generar"
    // y limpia resultados de evaluación (si aplica).
    const selReq = $("#selRequerimiento") || $("#tablaRequerimientos");
    selReq?.addEventListener("change", () => {
      const b = $("#btnGenerarOC");
      if (b) b.disabled = true;
    });
  }

  document.addEventListener("DOMContentLoaded", wire);

  // Exponer si necesitas invocar desde consola
  window.OC15 = { evaluar, generar };
})();
