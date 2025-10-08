// /Vista/Script/CUS24/itemsProductos.js
(function () {
  const $ = (sel, ctx = document) => ctx.querySelector(sel);

  /* ==================== Estado interno ==================== */
  let _reqSeq = 0;
  let _opActiva = null;

  // OPs incluidas (para marcar "Seleccionado")
  const _opsIncluidas = new Set();

  // Acumulador por producto (key = codigo/idProducto)
  // value = { codigo, desc, cantidad, ops: Set<number> }
  const _itemsAcumMap = new Map();

  /* ==================== Helpers UI ==================== */
  const getTB        = () => $("#tblItems tbody");
  const getBtnLimpiar= () => document.getElementById("btnLimpiar");
  const getMsgBox    = () => document.getElementById("msgAsignacion");

  const setBtnLimpiar = (enabled) => {
    const b = getBtnLimpiar();
    if (!b) return;
    b.disabled = !enabled;
    b.classList.toggle("is-loading", !enabled);
  };

  function setDestinoActivo(op, direccion, distrito) {
    const txt = document.getElementById("txtDireccionActiva");
    if (txt) {
      const dir = (direccion || "").trim();
      const dis = (distrito || "").trim();
      txt.value = dir && dis ? `${dir} - ${dis}` : (dir || dis || "");
    }
    window.Asignacion = window.Asignacion || {};
    window.Asignacion.destinoActivo = { op, direccion, distrito };
  }

  function setRecepcionista(dni, nombre) {
    const dniEl = document.getElementById("DniRecep");
    const nomEl = document.getElementById("NombreRecep");
    if (dniEl) dniEl.value = dni || "";
    if (nomEl) nomEl.value = nombre || "";
    window.Asignacion = window.Asignacion || {};
    window.Asignacion.recepcionista = { dni: dni || "", nombre: nombre || "" };
  }

  function clearDestino() {
  const txtDest = document.getElementById("txtDireccionActiva");
  if (txtDest) txtDest.value = ""; // ← corregido: usar txtDest

  const dniEl = document.getElementById("DniRecep");
  const nomEl = document.getElementById("NombreRecep");
  if (dniEl) dniEl.value = "";
  if (nomEl) nomEl.value = "";

  if (window.Asignacion) {
    window.Asignacion.destinoActivo = null;
    window.Asignacion.recepcionista = null;
  }
}


  /* ==================== Limpieza / Pintado ==================== */
  function emptyState(text = "Sin ítems para mostrar.") {
    const tb = getTB();
    if (!tb) return;
    tb.innerHTML = "";
    const tr = document.createElement("tr");
    tr.innerHTML = `<td colspan="4" style="text-align:center; opacity:.7;">${text}</td>`;
    tb.appendChild(tr);
    setBtnLimpiar(false);
  }

  function renderAll() {
    const tb = getTB();
    if (!tb) return;
    tb.innerHTML = "";

    if (_itemsAcumMap.size === 0) {
      emptyState();
      return;
    }

    // Orden opcional estable por código para lectura
    const rows = Array.from(_itemsAcumMap.values())
      .sort((a, b) => String(a.codigo).localeCompare(String(b.codigo)));

    for (const it of rows) {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td data-label="OP">${
          it.ops && it.ops.size === 1
            ? Array.from(it.ops)[0]
            : (it.ops ? `${it.ops.size} OPs` : "")
        }</td>
        <td data-label="Código Producto">${it.codigo}</td>
        <td data-label="Descripción">${it.desc}</td>
        <td data-label="Cantidad">${it.cantidad}</td>
      `;
      tb.appendChild(tr);
    }
    setBtnLimpiar(true);
  }

  function limpiar() {
    const tb = getTB();
    if (tb) tb.innerHTML = "";
    setBtnLimpiar(false);

    // Quitar selección visual de TODAS las filas
    document.querySelectorAll("#tblPedidos tbody tr")
      .forEach(tr => tr.classList.remove("selected"));

    clearDestino();

    // Reset ancla y estados
    window.AnchorCUS24?.clear?.();
    _opActiva = null;
    _opsIncluidas.clear();
    _itemsAcumMap.clear();

    // Refresca TODOS los botones a "Agregar" o "off" según corresponda
    window.Pedidos?.refreshCompatHighlights?.();

    // Botón Generar OFF + recálculo
    const gen = document.getElementById("btnGenerar");
    if (gen) gen.disabled = true;
    window.SalidaCUS24?.updateGenerarHabilitado?.();
  }

  /* ==================== Normalización y Merge ==================== */
  function pickCodigo(row) {
    return (
      row.codigo ??
      row.idProducto ??
      row.codProducto ??
      row.Id_Producto ??
      row.t18CatalogoProducto_Id_Producto ??
      ""
    );
  }
  function pickDescripcion(row) {
    return (
      row.descripcion ??
      row.desc ??
      row.producto ??
      row.nombre ??
      row.NombreProducto ??
      ""
    );
  }
  function pickCantidad(row) {
    const n = Number(row.cantidad ?? row.cant ?? row.Cantidad ?? 0);
    return Number.isFinite(n) ? n : 0;
  }

  function mergeItems(op, items = []) {
    for (const r of items) {
      const codigo = pickCodigo(r);
      if (codigo === "" || codigo === null || typeof codigo === "undefined") continue;

      const desc = pickDescripcion(r);
      const qty  = pickCantidad(r);

      if (!_itemsAcumMap.has(codigo)) {
        _itemsAcumMap.set(codigo, {
          codigo,
          desc,
          cantidad: qty,
          ops: new Set([op]),
        });
      } else {
        const node = _itemsAcumMap.get(codigo);
        node.cantidad += qty;
        node.ops.add(op);
      }
    }
  }

  /* ==================== API: cargar Pedido (con anclaje) ==================== */
  async function cargarPedido(meta) {
    // meta = { op, receptorDni, direccion, distrito?, clienteNombre? }
    if (!meta || !Number.isFinite(Number(meta.op))) return;
    const op = Number(meta.op);

    // 1) Validar/Fijar ancla
    if (!window.AnchorCUS24?.isSet?.()) {
      window.AnchorCUS24?.setFromPedido?.({
        receptorDni: (meta.receptorDni || "").trim(),
        direccion: (meta.direccion || "").trim()
      });
      // aplicar resaltado inicial
      window.Pedidos?.refreshCompatHighlights?.();
    } else if (!window.AnchorCUS24?.isCompatible?.({
      receptorDni: (meta.receptorDni || "").trim(),
      direccion: (meta.direccion || "").trim()
    })) {
      window.Utils24?.showMsg?.(getMsgBox(), "error",
        "E1: Pertenece a otra dirección. Finaliza o cambia de dirección.", { autoclear: 3500 });
      return;
    }

    // 2) Si ya agregada → marcar UI y salir
    if (_opsIncluidas.has(op)) {
      window.Pedidos?.refreshSingle?.(op);
      return;
    }

    // 3) Pintar destino + recepcionista
    setDestinoActivo(op, meta.direccion || "", meta.distrito || "");
    setRecepcionista(meta.receptorDni || "", meta.clienteNombre || "");

    // 4) Cargar ítems (ACUMULATIVO) y merge por producto
    _opActiva = op;
    const mySeq = ++_reqSeq;

    // placeholder de carga
    const tb = getTB();
    if (tb) {
      const tr = document.createElement("tr");
      tr.className = "row-loading";
      tr.innerHTML = `<td colspan="4" style="text-align:center;">Cargando ítems de la OP ${op}…</td>`;
      tb.appendChild(tr);
    }

    if (!window.API24?.url?.itemsPorOrden || !window.API24?.fetchJSON) {
      if (mySeq !== _reqSeq) return;
      window.Utils24?.showMsg?.(getMsgBox(), "error", "API de ítems no disponible.", { autoclear: 3000 });
      tb?.querySelector(".row-loading")?.remove();
      return;
    }

    let res;
    try {
      res = await window.API24.fetchJSON(window.API24.url.itemsPorOrden(op), {
        method: "GET",
        credentials: "include",
        headers: { "X-Requested-With": "fetch" },
      });
    } catch {
      if (mySeq !== _reqSeq) return;
      window.Utils24?.showMsg?.(getMsgBox(), "error", "No se pudo conectar al servidor.", { autoclear: 3500 });
      tb?.querySelector(".row-loading")?.remove();
      return;
    }

    if (mySeq !== _reqSeq) return;
    tb?.querySelector(".row-loading")?.remove();

    if (!res || !res.ok) {
      window.Utils24?.showMsg?.(getMsgBox(), "error", res?.error || "No se pudo obtener los ítems.", { autoclear: 3500 });
      return;
    }

    // 5) Merge por producto
    const items = Array.isArray(res.items) ? res.items : [];
    mergeItems(op, items);
    _opsIncluidas.add(op);

    // 6) Render y habilitar generar
    renderAll();
    window.SalidaCUS24?.updateGenerarHabilitado?.();

    // 7) Actualiza estado de botones/filas
    window.Pedidos?.refreshSingle?.(op);
    window.Pedidos?.refreshCompatHighlights?.();
  }

  /* ==================== Exports ==================== */
  Object.defineProperty(window, "ItemsProductos", {
    value: {
      cargarPedido,       // ← agregar con validación de anclaje
      hasOp: (op) => _opsIncluidas.has(Number(op)),
      get opsIncluidas() { return Array.from(_opsIncluidas); },
      get items() {
        // devuelve array plano de items (por si necesitas mandar a backend)
        return Array.from(_itemsAcumMap.values()).map(x => ({
          codigo: x.codigo,
          desc: x.desc,
          cantidad: x.cantidad,
          ops: Array.from(x.ops || []),
        }));
      },
      limpiar,
      get opActiva() { return _opActiva; },
    },
    writable: false,
  });

  // Wire botón Limpiar
  const btnL = getBtnLimpiar();
  if (btnL && !btnL._wired) {
    btnL.addEventListener("click", () => limpiar());
    btnL._wired = true;
  }
})();
