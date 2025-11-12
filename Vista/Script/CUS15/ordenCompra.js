// =======================================================
// CUS15.ordencompras.js - CORREGIDO CON DIAGNÓSTICO
// Solución: Cerrar overlay ANTES de mostrar modal + Re-evaluar estado
// =======================================================
(function () {
  const $ = (sel, ctx = document) => (ctx || document).querySelector(sel);
  const toast = (m, t = "info") => {
    try {
      (window.Utils15?.showToast || window.Utils24?.showToast)?.(m, t);
    } catch {
      console.log("[TOAST]", t, m);
    }
  };

  // === Estado local para OC15 ===
  const StateOC15 = {
    lastEval: null,
    esParcial: false,
  };

  // ===== Endpoints =====
  (function ensureEndpoints() {
    if (!window.API15 || !window.API15.base) return;
    const BASE = window.API15.base;
    window.API15.url = Object.assign({}, window.API15.url, {
      evaluar: window.API15?.url?.evaluar ?? `${BASE}?accion=evaluar`,
      generarOCs: window.API15?.url?.generarOCs ?? `${BASE}?accion=generar-ocs`,
      ocEnviarLote:
        window.API15?.url?.ocEnviarLote ?? `${BASE}?accion=oc-enviar-lote`,
    });
  })();

  // ---- AppDialog.confirm (robusto) ----
  const AppDialog = {
    async confirm({
      title = "Confirmación",
      message = "",
      okText = "Continuar",
      cancelText = "Cancelar",
    } = {}) {
      const dlg = document.getElementById("appDialog");
      // Sin nodo => confirm nativo
      if (!dlg) {
        console.warn("[AppDialog] #appDialog no existe, usando window.confirm");
        return window.confirm(`${title}\n\n${message}`);
      }

      const h3 = dlg.querySelector("#appDialogTitle") || dlg.querySelector("h3");
      const p  = dlg.querySelector("#appDialogMsg")   || dlg.querySelector("p");
      const ok = dlg.querySelector("#appDialogOk")    || dlg.querySelector("[data-ok]");
      const cancel = dlg.querySelector("#appDialogCancel") || dlg.querySelector("[data-cancel]");

      if (h3) h3.textContent = title;
      if (p)  p.textContent  = message;
      if (ok) ok.textContent = okText;
      if (cancel) cancel.textContent = cancelText;

      return await new Promise((resolve) => {
        const cleanup = () => {
          ok?.removeEventListener("click", onOk);
          cancel?.removeEventListener("click", onCancel);
          try { dlg.close(); } catch {}
        };
        const onOk = () => { cleanup(); resolve(true);  };
        const onCancel = () => { cleanup(); resolve(false); };

        ok?.addEventListener("click", onOk, { once: true });
        cancel?.addEventListener("click", onCancel, { once: true });

        // Intento 1: showModal real
        try {
          // quita overlays que bloqueen el click
          window.Processing?.hide?.();
          dlg.showModal();
          return;
        } catch (e) {
          console.warn("[AppDialog] showModal() falló:", e?.message);
        }

        // Intento 2: fallback a atributo [open] + CSS
        try {
          dlg.setAttribute("open", "");
          return;
        } catch (e2) {
          console.warn("[AppDialog] setAttribute('open') falló:", e2?.message);
        }

        // Último recurso: confirm nativo
        const ans = window.confirm(`${title}\n\n${message}`);
        resolve(ans);
      });
    },
  };
  window.AppDialog = AppDialog;

  // ===== Validación =====
  function evalTieneAdjudicacionValida(resEval) {
    const productos = Array.isArray(resEval?.productos)
      ? resEval.productos
      : [];
    if (!productos.length) return false;

    for (const p of productos) {
      const asigs = p.asignacion || p.Asignacion || [];
      for (const a of asigs) {
        const cant = Number(a.cantidad ?? a.Cantidad ?? 0);
        const prec = Number(a.precio ?? a.Precio ?? a.PrecioUnitario ?? 0);
        const costo = Number(a.costo ?? a.Costo ?? 0);
        if (cant > 0 && (prec > 0 || costo > 0)) return true;
      }
    }
    return false;
  }

  function evalEsParcial(resEval) {
    const productos = Array.isArray(resEval?.productos)
      ? resEval.productos
      : [];
    if (!productos.length) return false;

    let hayAsignacionValida = false;
    let hayFaltantes = false;

    for (const p of productos) {
      const aprob = Number(p.CantidadAprobada ?? p.cantidadAprobada ?? 0);
      const asigs = p.asignacion || p.Asignacion || [];
      const sumAsig = asigs.reduce(
        (acc, a) => acc + Number(a.cantidad ?? a.Cantidad ?? 0),
        0
      );
      
      for (const a of asigs) {
        const cant = Number(a.cantidad ?? a.Cantidad ?? 0);
        const prec = Number(a.precio ?? a.Precio ?? a.PrecioUnitario ?? 0);
        const costo = Number(a.costo ?? a.Costo ?? 0);
        if (cant > 0 && (prec > 0 || costo > 0)) {
          hayAsignacionValida = true;
          break;
        }
      }
      
      const falt =
        "faltante" in p || "Faltante" in p
          ? Number(p.faltante ?? p.Faltante ?? 0)
          : Math.max(0, aprob - sumAsig);

      if (falt > 0.0001) hayFaltantes = true;
    }
    
    const resultado = hayAsignacionValida && hayFaltantes;
    console.log("[evalEsParcial]", {
      hayAsignacionValida,
      hayFaltantes,
      resultado,
      productos: productos.length
    });
    
    return resultado;
  }

  // ===== Modal de resultado =====
  function abrirModalResultado({ idReq, genResp, sendResp }) {
    const modal = $("#modalOrdenes");
    if (!modal) return;

    const titulo = modal.querySelector(".modal__title");
    const msg = modal.querySelector(".modal__msg");
    const lista = $("#listaOC");
    const banner = modal.querySelector(".mail-banner-info");
    const btnOk = $("#btnConfirmarOC");
    const btnClose = modal.querySelector("[data-close]");

    if (titulo) titulo.textContent = "Resultado: Órdenes de Compra";
    if (msg) msg.textContent = "Resumen del proceso:";
    if (banner)
      banner.textContent = "Se muestran los resultados de generación y envío.";
    if (btnOk) {
      btnOk.style.display = "none";
      btnOk.replaceWith(btnOk.cloneNode(true));
    }
    if (btnClose) btnClose.textContent = "Cerrar";

    const ordenes = Array.isArray(genResp?.ordenes) ? genResp.ordenes : [];
    const totalGen = ordenes.length;

    const total = Number(sendResp?.total ?? totalGen ?? 0);
    const enviados = Number(sendResp?.enviados ?? 0);
    const omitidos = Number(sendResp?.omitidos ?? 0);
    const errores = Array.isArray(sendResp?.errores) ? sendResp.errores : [];
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
        <b>Omitidas:</b> ${omitidos} ${
      errores.length ? `· <b>Errores:</b> ${errores.length}` : ""
    }
      </div>
    `;

    const itemsHTML =
      ordenes.length === 0
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
            ${ordenes
              .map((oc) => {
                const idOC = Number(oc.idOC || oc.Id_OrdenCompra || 0);
                const det = mapDet.get(idOC);
                const err = mapErr.get(idOC);
                const status = err ? "error" : det?.status || "ok";
                const badge =
                  status === "sent" || status === "ok"
                    ? '<span class="status-badge ready">Enviado</span>'
                    : status === "error"
                    ? '<span class="status-badge detected">Error</span>'
                    : '<span class="status-badge">—</span>';
                const correo = det?.email || "—";
                const msg = err?.error ? String(err.error).slice(0, 180) : "—";
                const ruc = oc.ruc || "—";
                const razon = oc.razon || "—";
                const total = (oc.total ?? 0).toFixed
                  ? oc.total.toFixed(2)
                  : Number(oc.total || 0).toFixed(2);

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
              })
              .join("")}
          </tbody>
        </table>
      </div>`;

    if (lista) lista.innerHTML = resumenHTML + itemsHTML;

    if (modal.showModal) modal.showModal();
    else modal.style.display = "flex";
  }

  // ===== EVALUAR =====
  async function evaluar() {
    const { fetchJSON, url } = window.API15 || {};
    const idReq = window.ReqCUS15?.getSelected?.();

    if (!idReq) return toast("Seleccione un requerimiento.", "warning");
    if (!fetchJSON || !url?.evaluar)
      return toast("API15 incompleta (evaluar).", "error");

    const btnEval = document.querySelector("#btnEvaluar");
    const btnGen = document.querySelector("#btnGenerarOC");

    try {
      if (btnEval) btnEval.disabled = true;
      if (btnGen) btnGen.disabled = true;

      window.Processing?.show?.(
        "Evaluando requerimiento…",
        "Calculando adjudicación óptima (precio/stock)."
      );

      const resEval = await fetchJSON(url.evaluar, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ idReq }),
      });

      if (!resEval?.ok)
        throw new Error(resEval?.error || "No se pudo evaluar.");

      // 🔥 GUARDAR EN ESTADO
      StateOC15.lastEval = resEval;

      const esValido = evalTieneAdjudicacionValida(resEval);
      const esParcial = evalEsParcial(resEval);
      
      // 🔥 GUARDAR FLAG PARCIAL
      StateOC15.esParcial = esParcial;

      console.log("[OC15] Evaluación completada:", {
        productos: resEval.productos?.length || 0,
        esValido,
        esParcial,
        StateOC15: { ...StateOC15, lastEval: "existe" }
      });

      if (btnGen) btnGen.disabled = !esValido;

      // 🔥 CRÍTICO: Cerrar overlay ANTES de mostrar modal
      window.Processing?.hide?.();

      if (esValido && esParcial) {
        toast("⚠️ Evaluación parcial detectada.", "warning");

        // Pequeña pausa para asegurar que el overlay se cierre
        await new Promise(r => setTimeout(r, 150));

        const ok = await AppDialog.confirm({
          title: "⚠️ Evaluación parcial",
          message:
            "Se generarán Órdenes de Compra solo para los ítems cubiertos.\n" +
            "Los productos sin cobertura quedarán pendientes.\n\n" +
            "¿Deseas continuar con la generación parcial?",
          okText: "Entendido",
          cancelText: "Cerrar",
        });

        if (!ok) {
          toast(
            "Aviso cancelado. Puedes revisar la evaluación antes de generar.",
            "info"
          );
        }
      } else if (esValido) {
        toast("✅ Evaluación completa: puedes generar las OCs.", "success");
      } else {
        toast("⚠️ No hay asignaciones válidas para generar OCs.", "warning");
      }
    } catch (e) {
      console.error("[OC15] Error en evaluación:", e);
      toast(e.message || "Error al evaluar", "error");
      if (btnGen) btnGen.disabled = true;
    } finally {
      window.Processing?.hide?.();
      if (btnEval) btnEval.disabled = false;
    }
  }

  // ===== GENERAR OCS =====
  async function generar() {
    const { fetchJSON, url } = window.API15 || {};
    const idReq = window.ReqCUS15?.getSelected?.();

    if (!idReq) return toast("Seleccione un requerimiento.", "warning");
    if (!fetchJSON || !url?.generarOCs)
      return toast("API15 incompleta (generar-ocs).", "error");

    const btnGen = $("#btnGenerarOC");
    try {
      if (btnGen?.disabled) {
        toast("Debe evaluar primero antes de generar OCs.", "warning");
        return;
      }

      btnGen && (btnGen.disabled = true);

      // 🔍 DIAGNÓSTICO: Verificar estado actual
      console.log("[OC15] Estado antes de generar:", {
        esParcial: StateOC15.esParcial,
        lastEval: StateOC15.lastEval ? "existe" : "null",
      });

      // Re-evaluar si es parcial (por si el estado se perdió)
      const esParcial = StateOC15.lastEval 
        ? evalEsParcial(StateOC15.lastEval)
        : StateOC15.esParcial;

      console.log("[OC15] Es parcial (re-evaluado):", esParcial);

      // 🔥 CRÍTICO: SIEMPRE mostrar confirmación antes de generar
      let confirmMessage = "";
      let confirmTitle = "";

      if (esParcial) {
        confirmTitle = "⚠️ Generación parcial de OC";
        confirmMessage =
          "Se generarán OCs solo con los ítems cubiertos en la evaluación.\n" +
          "El requerimiento quedará como 'Parcialmente Atendido'.\n\n" +
          "¿Deseas continuar?";
      } else {
        confirmTitle = "Confirmar generación de OC";
        confirmMessage =
          "Se generarán las Órdenes de Compra y se enviarán automáticamente por correo a cada proveedor.\n\n" +
          "¿Deseas continuar?";
      }

      const ok = await AppDialog.confirm({
        title: confirmTitle,
        message: confirmMessage,
        okText: "Sí, continuar",
        cancelText: "No, revisar",
      });
      
      if (!ok) {
        toast("Generación cancelada por el usuario.", "info");
        btnGen.disabled = false;
        return;
      }

      // ✅ AHORA SÍ mostramos el overlay de procesamiento
      window.Processing?.show?.(
        "Generando órdenes de compra…",
        "Creando OCs por proveedor."
      );

      // 1) Generar OCs
      const genResp = await fetchJSON(url.generarOCs, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ idReq }),
      });

      if (!genResp?.ok) {
        throw new Error(genResp?.error || "No se pudieron generar las OCs.");
      }

      const n = Array.isArray(genResp.ordenes) ? genResp.ordenes.length : 0;
      toast(`✅ OC(s) generadas: ${n}`, n ? "success" : "info");

      // 2) Enviar lote
      let sendResp = null;
      if (url?.ocEnviarLote) {
        window.Processing?.show?.(
          "Enviando órdenes por correo…",
          "Adjuntando PDFs para cada proveedor."
        );

        sendResp = await fetchJSON(url.ocEnviarLote, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ idReq }),
        });

        if (!sendResp?.ok) {
          throw new Error(
            sendResp?.error || "Fallo al enviar lote de correos."
          );
        }

        const {
          total = 0,
          enviados = 0,
          omitidos = 0,
          errores = [],
        } = sendResp;

        toast(
          `📧 Envío: ${enviados}/${total} enviados · omitidos: ${omitidos}`,
          errores.length ? "warning" : "success"
        );
      }

      // 3) Mostrar resumen
      abrirModalResultado({ idReq, genResp, sendResp });

      // 4) Refrescar listado
      window.ReqCUS15?.reload?.();
      window.ReqCUS15?.clear?.();

      // 5) Deshabilitar botón
      if (btnGen) btnGen.disabled = true;
    } catch (e) {
      console.error("[OC15] Error generando/enviando OCs:", e);
      toast(e.message || "Error generando/enviando OCs", "error");
      if (btnGen) btnGen.disabled = true;
    } finally {
      window.Processing?.hide?.();
    }
  }

  // ===== Wire UI =====
  function wire() {
    $("#btnEvaluar")?.addEventListener("click", evaluar);

    const btnGen = $("#btnGenerarOC");
    if (btnGen) {
      btnGen.disabled = true;
      btnGen.addEventListener("click", generar);
    }

    const selReq = $("#selRequerimiento") || $("#tablaRequerimientos");
    if (selReq) {
      selReq.addEventListener("change", () => {
        const btn = $("#btnGenerarOC");
        if (btn) btn.disabled = true;
        // 🔥 Limpiar estado al cambiar de requerimiento
        StateOC15.lastEval = null;
        StateOC15.esParcial = false;
      });
    }
  }

  document.addEventListener("DOMContentLoaded", wire);

  // ===== API Pública =====
  window.OC15 = {
    evaluar,
    generar,
    validarEvaluacion: (resEval, opts) =>
      evalTieneAdjudicacionValida(resEval, opts),
    policy: { requireFull: false },
    // 🔍 Exponer estado para diagnóstico
    StateOC15: StateOC15,
    getState: () => ({ ...StateOC15, lastEval: StateOC15.lastEval ? "existe" : null }),
  };
})();