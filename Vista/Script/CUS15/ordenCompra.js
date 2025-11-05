// Version 2=======================================================
// CUS15.ordencompras.js
// - Evalúa requerimiento (greedy), habilita "Generar" si es válido
// - Genera OCs y (opcional) envía por correo en lote
// - Muestra resumen (si existe #modalOrdenes)
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

  // ===== Valida que la evaluación sea UTILIZABLE para generar OCs =====
  // Requisitos:
  //  1) Debe haber productos.
  //  2) Para cada producto:
  //     - Si viene p.faltante: debe ser 0.
  //     - Si NO viene p.faltante: suma(asignacion.cantidad) >= CantidadAprobada.
  //  3) Cada producto debe tener al menos una asignación válida
  //     (cantidad > 0 y precio/costo > 0).
  function evalTieneAdjudicacionValida(resEval) {
    const productos = Array.isArray(resEval?.productos)
      ? resEval.productos
      : [];

    if (!productos.length) {
      console.warn("[OC15] No hay productos en la evaluación");
      return false;
    }

    let hayAlMenosUnaAsignacionValidaGlobal = false;

    for (const p of productos) {
      const asigs = p.asignacion || p.Asignacion || [];

      // 1) Cobertura: primero intentamos usar p.faltante si viene del backend
      if ("faltante" in p || "Faltante" in p) {
        const falt = Number(p.faltante ?? p.Faltante ?? 0);
        if (falt > 0.0001) {
          console.warn(
            `[OC15] Producto ${p.Id_Producto} tiene faltante: ${falt}`
          );
          return false;
        }
      } else {
        // Si no viene 'faltante', recalculamos cobertura usando CantidadAprobada vs sum(asignacion)
        const aprob = Number(p.CantidadAprobada ?? p.cantidadAprobada ?? 0);
        const sumAsig = asigs.reduce(
          (acc, a) => acc + Number(a.cantidad ?? a.Cantidad ?? 0),
          0
        );

        if (aprob > 0 && sumAsig + 0.0001 < aprob) {
          console.warn(
            `[OC15] Producto ${p.Id_Producto}: cobertura incompleta. ` +
              `Aprobada=${aprob}, Asignada=${sumAsig}`
          );
          return false;
        }
      }

      // 2) Asignaciones válidas por producto
      let tieneAsignValidaProd = false;
      for (const a of asigs) {
        const cant = Number(a.cantidad ?? a.Cantidad ?? 0);
        const prec = Number(a.precio ?? a.Precio ?? a.PrecioUnitario ?? 0);
        const costo = Number(a.costo ?? a.Costo ?? 0);

        if (cant > 0 && (prec > 0 || costo > 0)) {
          tieneAsignValidaProd = true;
          hayAlMenosUnaAsignacionValidaGlobal = true;
          break; // basta una válida en este producto
        }
      }

      if (!tieneAsignValidaProd) {
        console.warn(
          `[OC15] Producto ${p.Id_Producto} ` +
            "no tiene ninguna asignación con cantidad > 0 y precio/costo > 0"
        );
        return false;
      }
    }

    if (!hayAlMenosUnaAsignacionValidaGlobal) {
      console.warn(
        "[OC15] Evaluación sin asignaciones válidas en ningún producto"
      );
      return false;
    }

    return true;
  }

  // ===== Modal de resultado (simple, opcional si tu vista lo tiene) =====
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

  // ===== Acciones =====
  async function evaluar() {
    const { fetchJSON, url } = window.API15 || {};
    const idReq = window.ReqCUS15?.getSelected?.();

    if (!idReq) return toast("Seleccione un requerimiento.", "warning");
    if (!fetchJSON || !url?.evaluar)
      return toast("API15 incompleta (evaluar).", "error");

    const btnEval = $("#btnEvaluar");
    const btnGen = $("#btnGenerarOC");

    try {
      btnEval && (btnEval.disabled = true);
      btnGen && (btnGen.disabled = true); // Deshabilitar mientras evalúa

      window.Processing?.show?.(
        "Evaluando requerimiento…",
        "Calculando adjudicación óptima (precio/stock)."
      );

      const resEval = await fetchJSON(url.evaluar, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ idReq }),
      });

      if (!resEval?.ok) {
        throw new Error(resEval?.error || "No se pudo evaluar.");
      }

      // Validar si se puede generar OCs
      const esValido = evalTieneAdjudicacionValida(resEval);

      console.log("[OC15] Evaluación completa:", {
        productos: resEval.productos?.length || 0,
        esValido,
        resumen: resEval.resumen,
      });

      if (btnGen) btnGen.disabled = !esValido;

      toast(
        esValido
          ? "✅ Evaluación OK: puedes generar OCs."
          : "⚠️ Evaluación incompleta: faltan cantidades o hay faltantes.",
        esValido ? "success" : "warning"
      );
    } catch (e) {
      console.error("[OC15] Error en evaluación:", e);
      toast(e.message || "Error al evaluar", "error");
      if (btnGen) btnGen.disabled = true;
    } finally {
      window.Processing?.hide?.();
      btnEval && (btnEval.disabled = false);
    }
  }

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

      // 2) Enviar lote (opcional, si endpoint existe)
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

      // 3) Mostrar resumen (si tienes el modal en la vista)
      abrirModalResultado({ idReq, genResp, sendResp });

      // 4) Refrescar listado
      window.ReqCUS15?.reload?.();
      window.ReqCUS15?.clear?.();

      // 5) Mantener botón deshabilitado (debe re-evaluar para generar de nuevo)
      if (btnGen) btnGen.disabled = true;
    } catch (e) {
      console.error("[OC15] Error generando/enviando OCs:", e);
      toast(e.message || "Error generando/enviando OCs", "error");

      // NO re-habilitar el botón si falló, debe re-evaluar
      if (btnGen) btnGen.disabled = true;
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

    // Si cambias de requerimiento, bloquear "Generar" y limpiar
    const selReq = $("#selRequerimiento") || $("#tablaRequerimientos");
    if (selReq) {
      selReq.addEventListener("change", () => {
        const btn = $("#btnGenerarOC");
        if (btn) btn.disabled = true;
      });
    }
  }

  document.addEventListener("DOMContentLoaded", wire);

  // Exponer API pública
  // Exponer API pública
  window.OC15 = {
    evaluar,
    generar,
    validarEvaluacion: evalTieneAdjudicacionValida, // ✅ Agregar esto
  };
})();
