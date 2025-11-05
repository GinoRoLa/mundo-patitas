// =======================================================
// CUS15.requerimiento.js
// Lista + selección + detalle + cots + import Excel + evaluación + comparador
// Requiere: window.API15 (CUS15.api.js) y opcional window.Utils24.showToast
// Expone: window.ReqCUS15 { reload(), seleccionar(id), getSelected() }
// =======================================================
(function () {
  const $ = (sel, ctx = document) => (ctx || document).querySelector(sel);

  // --------- State ---------
  const State = {
    selectedId: null,
    loading: false,
    evalByProd: new Map(),
    evalResumen: null,
  };

  // --------- DOM refs (ids de tu vista) ---------
  const DOM = {
    tbodyReq: $("#tbodyRequerimientos"),
    msgDetalle: $("#msgDetalle"),
    tbodyDetalle: $("#tbodyDetalleReq"),
    tbodyGen: $("#tbodyCotsGeneradas"),
    tbodyRec: $("#tbodyCotsRecibidas"),
    tbodyEval: $("#tbodyEvaluacion"),
    resumenEvalBox: $("#resumenEvaluacion"),
    btnGenOC: $("#btnGenerarOC"),
    modalCmp: $("#modalComparador"),
    cmpProd: $("#cmpProd"),
    cmpCant: $("#cmpCant"),
    tblCmp: $("#tblComparador"),
  };

  const Utils = window.Utils15 || {
    showToast: (m, t = "info") => {
      try {
        window.Utils24?.showToast?.(m, t);
      } catch {}
      console.log("[TOAST]", t, m);
    },
    showMsg: (el, type, txt) => {
      if (el) {
        el.textContent = txt || "";
        el.className = `msg ${type || ""}`;
      }
    },
  };

  // =======================================================
  // Render: lista de requerimientos
  // =======================================================
  function renderRequerimientos(list) {
    const tb = DOM.tbodyReq;
    if (!tb) return;
    tb.innerHTML = "";

    if (!Array.isArray(list) || list.length === 0) {
      tb.innerHTML = `<tr><td colspan="6" style="text-align:center; color:#64748b;">No hay requerimientos</td></tr>`;
      return;
    }

    for (const r of list) {
      const listas = Number(r?.cotizaciones?.listas || 0);
      const detect = Number(r?.cotizaciones?.detectadas || 0);
      const cotTxt =
        listas > 0 ? `🟢 ${listas}` : detect > 0 ? `🟡 ${detect}` : "⚪ —";

      const tr = document.createElement("tr");
      tr.dataset.id = r.id;
      tr.className = "row-req";
      tr.innerHTML = `
        <td class="mono">${r.id ?? "—"}</td>
        <td>${r.fecha ?? "—"}</td>
        <td style="text-align:right;">${Number(r.items || 0)}</td>
        <td><span class="status-badge ${statusClass(r.estado)}">${
        r.estado ?? "—"
      }</span></td>
        <td class="col-cots" style="text-align:center;">${cotTxt}</td>
        <td style="text-align:center;">
          <input type="checkbox" class="chk-evaluar" aria-label="Evaluar ${
            r.id ?? ""
          }">
        </td>
      `;
      tb.appendChild(tr);
    }
    lazyScanAll(list);
  }

  function statusClass(e) {
    const s = String(e || "").toLowerCase();
    if (s.includes("aprob")) return "ready";
    if (s.includes("pend") || s.includes("detect")) return "detected";
    if (!s || s.includes("sin")) return "none";
    return "ok";
  }

  function normalizeDetectadosFromScan(scan) {
    const a = scan?.archivos;
    if (Array.isArray(a)) return a.length;
    if (a && typeof a === "object") {
      if (Array.isArray(a.detectados?.todos)) return a.detectados.todos.length;
      if (Array.isArray(a.todos)) return a.todos.length;
      if (Array.isArray(a.nuevos)) return a.nuevos.length;
    }
    return 0;
  }

  function pintarSemaforo(idReq, { enBD = 0, detectados = 0 }) {
    const cel = DOM.tbodyReq?.querySelector(`tr[data-id="${idReq}"] .col-cots`);
    if (!cel) return;
    if (enBD > 0) {
      cel.textContent = `🟢 ${enBD}`;
      cel.title = `${enBD} cotización(es) en BD`;
    } else if (detectados > 0) {
      cel.textContent = `🟡 ${detectados}`;
      cel.title = `${detectados} archivo(s) Excel detectado(s) en carpeta`;
    } else {
      cel.textContent = "⚪ —";
      cel.title = "Sin cotizaciones";
    }
  }

  /** Pre-scan por fila: cuenta BD y carpeta */
  async function scanYBadge(idReq) {
    const { fetchJSON, url } = window.API15 || {};
    if (!fetchJSON || !url?.cotsRecibidas || !url?.scanExcel) return;

    try {
      const [rRec, rScan] = await Promise.all([
        fetchJSON(url.cotsRecibidas(idReq), { method: "GET" }),
        fetchJSON(url.scanExcel(idReq), { method: "GET" }),
      ]);
      const enBD = Array.isArray(rRec?.recibidas) ? rRec.recibidas.length : 0;
      const detectados = normalizeDetectadosFromScan(rScan);
      pintarSemaforo(idReq, { enBD, detectados });
    } catch (e) {
      pintarSemaforo(idReq, { enBD: 0, detectados: 0 });
      console.warn("[CUS15] scanYBadge error", idReq, e);
    }
  }

  /** Escaneo concurrente limitado */
  async function lazyScanAll(list, maxConcurrent = 3) {
    if (!Array.isArray(list) || list.length === 0) return;
    const queue = list.map((r) => String(r.id)).filter(Boolean);
    let running = 0;
    async function runNext() {
      if (queue.length === 0) return;
      const id = queue.shift();
      running++;
      try {
        await scanYBadge(id);
      } finally {
        running--;
        if (queue.length > 0) runNext();
      }
    }
    const starters = Math.min(maxConcurrent, queue.length);
    for (let i = 0; i < starters; i++) runNext();
  }

  // =======================================================
  // Delegación de eventos checkbox
  // =======================================================
  if (DOM.tbodyReq) {
    DOM.tbodyReq.addEventListener("click", async (ev) => {
      const chk = ev.target.closest(".chk-evaluar");
      if (!chk) return;
      const row = ev.target.closest("tr.row-req");
      if (!row) return;
      if (State.loading) {
        ev.preventDefault();
        return;
      }

      setTimeout(async () => {
        const isChecked = chk.checked;
        DOM.tbodyReq.querySelectorAll(".chk-evaluar").forEach((c) => {
          if (c !== chk) c.checked = false;
        });
        if (isChecked) await seleccionarRequerimiento(row.dataset.id, row);
        else if (State.selectedId === row.dataset.id) limpiarSeleccion();
      }, 0);
    });
  }

  function marcarFilaSeleccionada(rowEl) {
    if (!DOM.tbodyReq) return;
    DOM.tbodyReq
      .querySelectorAll("tr")
      .forEach((tr) => tr.classList.remove("selected"));
    if (rowEl) rowEl.classList.add("selected");
    const chkActual = rowEl?.querySelector(".chk-evaluar");
    DOM.tbodyReq
      .querySelectorAll(".chk-evaluar")
      .forEach((c) => (c.checked = c === chkActual));
  }

  function limpiarSeleccion() {
    State.selectedId = null;
    State.evalByProd.clear();
    State.evalResumen = null;

    DOM.tbodyReq
      ?.querySelectorAll("tr")
      .forEach((tr) => tr.classList.remove("selected"));
    DOM.tbodyReq
      ?.querySelectorAll(".chk-evaluar")
      .forEach((c) => (c.checked = false));

    if (DOM.tbodyDetalle) DOM.tbodyDetalle.innerHTML = "";
    if (DOM.tbodyGen) DOM.tbodyGen.innerHTML = "";
    if (DOM.tbodyRec) DOM.tbodyRec.innerHTML = "";
    if (DOM.tbodyEval) DOM.tbodyEval.innerHTML = "";
    if (DOM.resumenEvalBox) DOM.resumenEvalBox.textContent = "";

    Utils.showMsg(DOM.msgDetalle, "info", "Seleccione un requerimiento para ver los detalles");

    if (DOM.btnGenOC) DOM.btnGenOC.disabled = true;
  }

  // =======================================================
  // Selección + cargas + scan/import + evaluación
  // =======================================================
  async function seleccionarRequerimiento(id, rowEl) {
    if (!id || State.loading) return;

    marcarFilaSeleccionada(rowEl);
    State.selectedId = id;
    Utils.showMsg(DOM.msgDetalle, "ok", `REQ seleccionado: ${id}`);
    if (DOM.btnGenOC) DOM.btnGenOC.disabled = true;

    try {
      State.loading = true;
      const { fetchJSON, url } = window.API15 || {};
      if (!fetchJSON || !url) {
        Utils.showToast("API no disponible (API15)", "error");
        return;
      }

      // 1) Básicos
      const [rDet, rGen, rRec] = await Promise.all([
        fetchJSON(url.detalleReq(id), { method: "GET" }),
        fetchJSON(url.cotsGeneradas(id), { method: "GET" }),
        fetchJSON(url.cotsRecibidas(id), { method: "GET" }),
      ]);

      renderDetalle(rDet?.detalle || [], rDet?.req || null);
      renderCotsGeneradas(rGen?.generadas || []);
      renderCotsRecibidas(rRec?.recibidas || []);

      // 2) Import inteligente si BD vacía
      const cotsEnBD = (rRec?.recibidas || []).length;
      if (cotsEnBD === 0) await scanAndMaybeImport(id);
      else {
        const row = DOM.tbodyReq?.querySelector(`tr[data-id="${id}"]`);
        const cel = row?.querySelector(".col-cots");
        if (cel) cel.textContent = `🟢 ${cotsEnBD}`;
      }

      // 3) Evaluar siempre Y validar para habilitar botón
      await evaluarYMostrar(id);
    } catch (err) {
      console.error(err);
      Utils.showToast("Error cargando información del requerimiento", "error");
    } finally {
      State.loading = false;
    }
  }

  async function scanAndMaybeImport(idReq) {
    const { fetchJSON, url } = window.API15 || {};
    if (!url?.scanExcel) return;
    try {
      const scan = await fetchJSON(url.scanExcel(idReq), { method: "GET" });
      const cotsEnBD = (scan?.importados || []).length;
      const nuevos = scan?.nuevos || [];

      const row = DOM.tbodyReq?.querySelector(`tr[data-id="${idReq}"]`);
      const cel = row?.querySelector(".col-cots");
      if (cel) {
        const g = cotsEnBD,
          y = nuevos.length;
        cel.textContent =
          g > 0
            ? y > 0
              ? `🟢 ${g} · 🟡 ${y}`
              : `🟢 ${g}`
            : y > 0
            ? `🟡 ${y}`
            : "⚪ —";
      }

      if (nuevos.length > 0) {
        const ok = confirm(
          [
            `🟡 Se detectaron ${nuevos.length} archivo(s) Excel nuevo(s):`,
            "",
            ...nuevos.map(
              (n, i) => `${i + 1}. ${n.file} (${(n.size / 1024).toFixed(1)} KB)`
            ),
            "",
            "¿Deseas importarlos ahora?",
          ].join("\n")
        );
        if (ok)
          await ejecutarImportacionSoloNuevos(
            idReq,
            nuevos.map((n) => n.hash)
          );
      }
    } catch (e) {
      console.error(e);
      Utils.showToast("Error al escanear/importar Excel", "error");
    }
  }

  async function ejecutarImportacionSoloNuevos(idReq, hashesNuevos = []) {
    const { fetchJSON, url } = window.API15 || {};
    if (!url?.importExcelReq)
      return Utils.showToast(
        "No está configurado API15.url.importExcelReq",
        "error"
      );

    const r = await fetchJSON(url.importExcelReq, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ idReq, onlyNew: true, hashes: hashesNuevos }),
    });

    if (r?.ok) {
      const tipo = (r.errores?.length || 0) > 0 ? "warning" : "success";
      const msg =
        r.message ??
        `Importados: ${r.importados?.length || 0} · Omitidos: ${
          r.omitidos?.length || 0
        } · Errores: ${r.errores?.length || 0}`;
      Utils.showToast(msg, tipo);
      await cargarCotsRecibidas(idReq);
      await evaluarYMostrar(idReq);
    } else {
      Utils.showToast(r?.error || "No se pudo importar", "error");
    }
  }

  async function cargarCotsRecibidas(idReq) {
    const { fetchJSON, url } = window.API15 || {};
    if (!url?.cotsRecibidas) return 0;

    const r = await fetchJSON(url.cotsRecibidas(idReq), { method: "GET" });
    const rows = r?.recibidas || [];
    renderCotsRecibidas(rows);

    const rowEl = DOM.tbodyReq?.querySelector(`tr[data-id="${idReq}"]`);
    const cel = rowEl?.querySelector(".col-cots");
    if (cel && rows.length > 0) cel.textContent = `🟢 ${rows.length}`;
    return rows.length;
  }

  // =======================================================
  // Evaluación (preview) + render + VALIDACIÓN
  // =======================================================
  async function evaluarYMostrar(idReq) {
    const { fetchJSON, url } = window.API15 || {};
    if (!url?.evaluar) return console.warn("[CUS15] Falta API15.url.evaluar");

    try {
      const r = await fetchJSON(url.evaluar, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ idReq }),
      });

      if (!r?.ok) {
        Utils.showToast(r?.error || "No fue posible evaluar", "error");
        renderEvaluacion([], null);
        if (DOM.btnGenOC) DOM.btnGenOC.disabled = true;
        return;
      }

      renderEvaluacion(r.productos || [], r.resumen || null);

      // 🔥 NUEVO: Validar y habilitar botón si window.OC15 está disponible
      if (window.OC15?.validarEvaluacion) {
        const esValido = window.OC15.validarEvaluacion(r);
        if (DOM.btnGenOC) {
          DOM.btnGenOC.disabled = !esValido;
        }
        console.log("[CUS15] Auto-evaluación:", {
          productos: r.productos?.length || 0,
          esValido,
          botonHabilitado: !DOM.btnGenOC?.disabled,
        });
      } else {
        console.warn("[CUS15] window.OC15.validarEvaluacion no disponible");
      }
    } catch (e) {
      console.error("[CUS15] Error en evaluarYMostrar:", e);
      Utils.showToast("Error al evaluar cotizaciones", "error");
      renderEvaluacion([], null);
      if (DOM.btnGenOC) DOM.btnGenOC.disabled = true;
    }
  }

  function sumaAsignada(producto) {
    if (!producto || !Array.isArray(producto.asignacion)) return 0;
    return producto.asignacion.reduce(
      (sum, a) => sum + Number(a.cantidad || 0),
      0
    );
  }

  function renderEvaluacion(productos, resumen) {
    const tb = DOM.tbodyEval;
    if (!tb) return;
    State.evalByProd.clear();
    State.evalResumen = resumen || null;
    tb.innerHTML = "";

    if (!Array.isArray(productos) || productos.length === 0) {
      tb.innerHTML = `<tr><td colspan="6" style="text-align:center; color:#64748b;">Sin evaluación disponible</td></tr>`;
      if (DOM.resumenEvalBox)
        DOM.resumenEvalBox.innerHTML = `<b>Resumen:</b> Sin datos de evaluación`;
      return;
    }

    for (const p of productos) {
      State.evalByProd.set(Number(p.Id_Producto), p);

      const aprob = Number(p.CantidadAprobada || 0);
      const asigTotal = sumaAsignada(p);
      const falt = Math.max(0, aprob - asigTotal);
      const asignText = formatAsignaciones(p.asignacion || []);

      let estadoBadge = "";
      if (falt > 0)
        estadoBadge = `<span class="status-badge detected">Falta ${fmtCant(
          falt
        )}</span>`;
      else if ((p.asignacion || []).length > 0)
        estadoBadge = `<span class="status-badge ready">Completo</span>`;
      else estadoBadge = `<span class="status-badge none">Sin ofertas</span>`;

      const tr = document.createElement("tr");
      tr.dataset.idProd = p.Id_Producto;
      tr.innerHTML = `
        <td class="mono">${p.Id_Producto}</td>
        <td>${p.Nombre ?? "—"}</td>
        <td style="text-align:right;">${fmtCant(aprob)}</td>
        <td class="mono">${asignText}</td>
        <td style="text-align:right;">S/ ${fmtMoney(p.costoTotal)}</td>
        <td>
          ${estadoBadge}
          <button class="btn btn-ghost btn-sm btn-detalle" data-id-prod="${
            p.Id_Producto
          }" title="Ver comparación detallada">🔍 Detalle</button>
        </td>
      `;
      tb.appendChild(tr);
    }

    tb.querySelectorAll(".btn-detalle").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.stopPropagation();
        const idProd = Number(btn.dataset.idProd);
        openComparadorForProduct(idProd);
      });
    });

    if (DOM.resumenEvalBox) {
      const provs = resumen?.proveedores ?? countDistinctProviders(productos);
      const prods = resumen?.productosEvaluados ?? (productos?.length || 0);
      const total = resumen?.costoTotal ?? sumCostoTotal(productos);
      DOM.resumenEvalBox.innerHTML = `
        <b>Resumen:</b> ${prods} producto${prods !== 1 ? "s" : ""} evaluado${
        prods !== 1 ? "s" : ""
      } · 
        ${provs} proveedor${provs !== 1 ? "es" : ""} · 
        Costo total: <b>S/ ${fmtMoney(total)}</b>
      `;
    }
  }

  function fmtCant(n) {
    const x = Number(n || 0);
    return (Math.round(x * 100) / 100).toString().replace(/\.00$/, "");
  }
  function fmtMoney(n) {
    const x = Number(n || 0);
    return x.toFixed(2);
  }

  function formatAsignaciones(asignacion = []) {
    if (!Array.isArray(asignacion) || asignacion.length === 0) return "—";
    const lines = asignacion.map((a) => {
      const cant = fmtCant(a?.cantidad);
      const ruc = a?.ruc || a?.proveedor || "—";
      let pu = Number(a?.precio);
      if (!(pu > 0)) {
        const costo = Number(a?.costo);
        const c = Number(a?.cantidad);
        pu = c > 0 ? costo / c : 0;
      }
      return `${cant}u – RUC ${ruc} @ S/ ${fmtMoney(pu)}`;
    });
    return lines.join("<br>");
  }

  function countDistinctProviders(productos) {
    const s = new Set();
    for (const p of productos || []) {
      for (const a of p.asignacion || [])
        s.add(a.ruc || a.proveedor || JSON.stringify(a));
    }
    return s.size;
  }

  function sumCostoTotal(productos) {
    return (productos || []).reduce(
      (acc, p) => acc + (Number(p.costoTotal) || 0),
      0
    );
  }

  // =======================================================
  // Comparador
  // =======================================================
  function openComparadorForProduct(idProd) {
    const p = State.evalByProd.get(Number(idProd));
    if (!p)
      return Utils.showToast(
        "No hay datos de evaluación para este producto.",
        "error"
      );

    if (DOM.cmpProd)
      DOM.cmpProd.textContent = `${p.Nombre ?? `Producto ${idProd}`}`;
    if (DOM.cmpCant) {
      const aprob = Number(p.CantidadAprobada || 0);
      DOM.cmpCant.textContent = `Cantidad aprobada: ${fmtCant(aprob)} ${
        p.UnidadMedida ?? ""
      }`;
    }

    const provs = normalizeProviders(p);
    if (provs.length === 0)
      return Utils.showToast("No hay ofertas para comparar.", "warning");

    provs.sort(
      (a, b) =>
        a.precio - b.precio ||
        b.stock - a.stock ||
        String(a.ruc).localeCompare(String(b.ruc))
    );
    buildDynamicComparisonTable(provs, p);

    if (DOM.modalCmp?.showModal) DOM.modalCmp.showModal();
    else if (DOM.modalCmp) DOM.modalCmp.style.display = "flex";

    DOM.modalCmp?.querySelector("[data-close]")?.addEventListener(
      "click",
      () => {
        if (DOM.modalCmp.close) DOM.modalCmp.close();
        else DOM.modalCmp.style.display = "none";
      },
      { once: true }
    );

    function normalizeProviders(prod) {
      const asignMap = {};
      for (const a of prod.asignacion || [])
        asignMap[a.ruc] = {
          cantidad: Number(a.cantidad ?? 0),
          costo: Number(a.costo ?? 0),
        };
      return (prod.rankingPrecio || []).map((x) => {
        const a = asignMap[x.ruc] ?? { cantidad: 0, costo: 0 };
        return {
          ruc: String(x.ruc),
          nombre: x.proveedor ?? x.nombre ?? null,
          precio: Number(x.precio ?? 0),
          stock: Number(x.stock ?? 0),
          asignado: Number(a.cantidad ?? 0),
          costo: Number(a.costo ?? 0),
        };
      });
    }

    function buildDynamicComparisonTable(provs, prod) {
      const table = DOM.tblCmp;
      if (!table) return;
      const thead = table.querySelector("thead");
      const tbody =
        table.querySelector("tbody#tbodyComparador") ||
        table.querySelector("tbody");
      if (!thead || !tbody) return;

      const thProvCols = provs
        .map(
          (pv) => `<th>${pv.nombre ? `${pv.nombre} (${pv.ruc})` : pv.ruc}</th>`
        )
        .join("");
      thead.innerHTML = `<tr><th>Criterio / Proveedor</th>${thProvCols}<th>Mejor</th></tr>`;

      const bestPrecio = provs[0];
      const bestStock = provs.slice().sort((a, b) => b.stock - a.stock)[0];

      const rows = [];
      rows.push(
        renderRow(
          "Precio unitario",
          provs.map((pv) => `S/ ${fmtMoney(pv.precio)}`),
          (bestPrecio?.nombre
            ? `${bestPrecio.nombre} (${bestPrecio.ruc})`
            : bestPrecio?.ruc) || "—"
        )
      );
      rows.push(
        renderRow(
          "Stock disponible",
          provs.map((pv) => fmtCant(pv.stock)),
          (bestStock?.nombre
            ? `${bestStock.nombre} (${bestStock.ruc})`
            : bestStock?.ruc) || "—"
        )
      );
      rows.push(
        renderRow(
          "Orden por precio",
          provs.map((_, i) => `${i + 1}°`),
          "—"
        )
      );
      rows.push(
        renderRow(
          "Cantidad asignada",
          provs.map((pv) => fmtCant(pv.asignado)),
          "—"
        )
      );
      rows.push(
        renderRow(
          "Costo parcial",
          provs.map((pv) => `S/ ${fmtMoney(pv.costo)}`),
          "—"
        )
      );
      rows.push(
        `<tr><td><b>Costo total del producto</b></td>${provs
          .map(() => `<td>—</td>`)
          .join("")}<td><b>S/ ${fmtMoney(
          Number(prod.costoTotal || 0)
        )}</b></td></tr>`
      );
      tbody.innerHTML = rows.join("");

      function renderRow(label, valuesPerProv, bestLabel) {
        const tds = valuesPerProv.map((v) => `<td>${v ?? "—"}</td>`).join("");
        return `<tr><td><b>${label}</b></td>${tds}<td>${
          bestLabel ?? "—"
        }</td></tr>`;
      }
    }
  }

  // =======================================================
  // Renders simples
  // =======================================================
  function renderDetalle(items, reqMeta) {
    const tb = DOM.tbodyDetalle;
    if (!tb) return;
    tb.innerHTML = "";

    if (!Array.isArray(items) || items.length === 0) {
      tb.innerHTML = `<tr><td colspan="4" style="text-align:center; color:#64748b;">Sin detalle</td></tr>`;
    } else {
      for (const it of items) {
        const cant = Number(it.CantidadAprobada || 0);
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td class="mono">${it.Id_Producto ?? "—"}</td>
          <td>${it.Nombre ?? "—"}</td>
          <td style="text-align:right;">${fmtCant(cant)}</td>
          <td>${it.UnidadMedida ?? "UND"}</td>
        `;
        tb.appendChild(tr);
      }
    }

    const id =
      (reqMeta && (reqMeta.id || reqMeta.Id_ReqEvaluacion)) ||
      State.selectedId ||
      "—";
    Utils.showMsg(DOM.msgDetalle, "ok", `REQ seleccionado: ${id}`);
  }

  function renderCotsGeneradas(rows) {
    const tb = DOM.tbodyGen;
    if (!tb) return;
    tb.innerHTML = "";
    if (!Array.isArray(rows) || rows.length === 0) {
      tb.innerHTML = `<tr><td colspan="5" style="text-align:center; color:#64748b;">Sin solicitudes de cotización generadas</td></tr>`;
      return;
    }
    for (const r of rows) {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td class="mono">${r.codigo ?? "—"}</td>
        <td>${r.ruc ?? "—"}</td>
        <td>${r.razon ?? "—"}</td>
        <td>${r.direccion ?? "—"}</td>
        <td>${fmtFecha(r.fecEmision)}</td>
      `;
      tb.appendChild(tr);
    }
  }

  function renderCotsRecibidas(rows) {
    const tb = DOM.tbodyRec;
    if (!tb) return;
    tb.innerHTML = "";
    if (!Array.isArray(rows) || rows.length === 0) {
      tb.innerHTML = `<tr><td colspan="6" style="text-align:center; color:#64748b;">No existen cotizaciones recibidas para este requerimiento de compra</td></tr>`;
      return;
    }
    for (const r of rows) {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td class="mono">${r.codigo ?? r.Id_Cotizacion ?? "—"}</td>
        <td>${r.ruc ?? r.RUC_Proveedor ?? "—"}</td>
        <td>${r.razon ?? r.RazonSocial ?? "—"}</td>
        <td>${r.direccion ?? r.Direccion ?? r.DireccionProv ?? "—"}</td>
        <td>${fmtFecha(r.fecEmision ?? r.FechaEmision)}</td>
        <td>${fmtFechaHora(r.fecRecepcion ?? r.FechaRecepcion)}</td>
      `;
      tb.appendChild(tr);
    }
  }

  function fmtFecha(d) {
    if (!d) return "—";
    try {
      const s = String(d);
      if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s;
      const dt = new Date(s);
      if (!isNaN(dt)) {
        const y = dt.getFullYear();
        const m = String(dt.getMonth() + 1).padStart(2, "0");
        const day = String(dt.getDate()).padStart(2, "0");
        return `${y}-${m}-${day}`;
      }
    } catch {}
    return String(d);
  }

  function fmtFechaHora(d) {
    if (!d) return "—";
    try {
      const dt = new Date(d);
      if (!isNaN(dt)) {
        const Y = dt.getFullYear();
        const M = String(dt.getMonth() + 1).padStart(2, "0");
        const D = String(dt.getDate()).padStart(2, "0");
        const h = String(dt.getHours()).padStart(2, "0");
        const m = String(dt.getMinutes()).padStart(2, "0");
        return `${Y}-${M}-${D} ${h}:${m}`;
      }
    } catch {}
    return String(d);
  }

  async function cargarListaRequerimientos() {
    const { fetchJSON, url } = window.API15 || {};
    if (!fetchJSON || !url || !url.requerimientos) {
      Utils.showToast("API no disponible (requerimientos)", "error");
      renderRequerimientos([]);
      return;
    }
    try {
      State.loading = true;
      const r = await fetchJSON(url.requerimientos, { method: "GET" });
      if (!r || !r.ok) {
        Utils.showToast(
          r?.error || "No se pudo obtener requerimientos",
          "error"
        );
        renderRequerimientos([]);
        return;
      }
      renderRequerimientos(r.requerimientos || []);
    } catch (e) {
      console.error(e);
      Utils.showToast("Error al cargar requerimientos", "error");
      renderRequerimientos([]);
    } finally {
      State.loading = false;
    }
  }

  function init() {
    cargarListaRequerimientos();
  }
  document.addEventListener("DOMContentLoaded", init);

  // API pública del módulo
  window.ReqCUS15 = {
    reload: cargarListaRequerimientos,
    seleccionar: seleccionarRequerimiento,
    getSelected: () => State.selectedId,
    clear: limpiarSeleccion,
  };
})();
