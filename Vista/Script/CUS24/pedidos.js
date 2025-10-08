// /Vista/Script/CUS24/pedidos.js
(function () {
  const $ = (sel, ctx = document) => ctx.querySelector(sel);

  /* ========== Helpers de botón/filas ========== */
  function setBtnState(btn, { incluida, compat }) {
    btn.classList.remove("btnAdd","ok","off","btnSelected");
    btn.disabled = false;

    if (incluida) {
      btn.classList.add("btnSelected");
      btn.textContent = "Seleccionado";
      btn.disabled = true; // no clickeable
    } else if (compat) {
      btn.classList.add("btnAdd","ok"); // verde
      btn.textContent = "Agregar";
      btn.disabled = false;
    } else {
      btn.classList.add("btnAdd","off"); // gris
      btn.textContent = "Agregar";
      btn.disabled = true;
    }
  }

  function filaMetaDesdeTR(tr) {
    if (!tr) return null;
    const btn = tr.querySelector("button");
    const op  = Number(btn?.dataset.op || tr.dataset.op || 0);
    const dni = (btn?.dataset.dni || tr.dataset.dni || "").trim();
    const dir = (btn?.dataset.dir || tr.dataset.dir || "").trim();
    const distrito = tr.querySelector('td[data-label="Distrito"]')?.textContent.trim() || "";
    const cliente  = tr.querySelector('td[data-label="Cliente"]')?.textContent.trim() || "";
    if (!op || !dni || !dir) return null;
    return { op, receptorDni: dni, direccion: dir, distrito, clienteNombre: cliente };
  }

  /* ========== Render de filas ========== */
  function rowHtml(p) {
    const op         = Number(p.idOrdenPedido);
    const incluida   = !!window.ItemsProductos?.hasOp?.(op);
    const compatible = incluida ? true : (window.AnchorCUS24?.isCompatible?.(p) ?? true);

    let btnClass = "btnAdd ok";
    let btnText  = "Agregar";
    let disabled = "";

    if (incluida) {
      btnClass = "btnSelected";
      btnText  = "Seleccionado";
      disabled = "disabled";
    } else if (!compatible) {
      btnClass = "btnAdd off";
      btnText  = "Agregar";
      disabled = "disabled";
    }

    return `
      <tr data-op="${op}"
          data-dni="${p.receptorDni || ''}"
          data-dir="${String(p.direccion || '').replace(/"/g,'&quot;')}">
        <td data-label="Código Orden Pedido">${op}</td>
        <td data-label="Cliente">${p.cliente || ''}</td>
        <td data-label="Dirección">${p.direccion || ''}</td>
        <td data-label="Distrito">${p.distritoNombre || ''}</td>
        <td data-label="OSE">${p.idOSE || ''}</td>
        <td data-label="Estado">${p.estadoOP || ''}</td>
        <td data-label="Acción">
          <button type="button"
                  class="${btnClass}"
                  ${disabled}
                  data-op="${op}"
                  data-dni="${p.receptorDni || ''}"
                  data-dir="${String(p.direccion || '').replace(/"/g,'&quot;')}">
            ${btnText}
          </button>
        </td>
      </tr>
    `;
  }

  function pintarLista(pedidos = []) {
    const tb = $("#tblPedidos tbody");
    if (!tb) return;
    tb.innerHTML = "";
    pedidos.forEach(p => tb.insertAdjacentHTML("beforeend", rowHtml(p)));
  }

  // Refresca SOLO una fila (por OP)
  function refreshSingle(op) {
    const tr  = document.querySelector(`#tblPedidos tbody tr[data-op="${Number(op)}"]`);
    const btn = tr?.querySelector("button");
    if (!tr || !btn) return;

    const incluida = !!window.ItemsProductos?.hasOp?.(op);
    const compat = incluida
      ? true
      : (window.AnchorCUS24?.isCompatible?.({
          idOrdenPedido: Number(op),
          receptorDni: btn.dataset.dni || "",
          direccion: btn.dataset.dir || ""
        }) ?? true);

    setBtnState(btn, { incluida, compat });

    if (incluida) tr.classList.add("selected");
    else tr.classList.remove("selected");
  }

  // Recalcula TODOS los botones/filas según ancla + inclusión
  function refreshCompatHighlights() {
    const tb = $("#tblPedidos tbody");
    if (!tb) return;

    tb.querySelectorAll("tr").forEach(tr => {
      const btn = tr.querySelector("button");
      if (!btn) return;

      const op = Number(btn.dataset.op || "0");
      const incluida = !!window.ItemsProductos?.hasOp?.(op);
      const compat = incluida
        ? true
        : (window.AnchorCUS24?.isCompatible?.({
            idOrdenPedido: op,
            receptorDni: btn.dataset.dni || "",
            direccion  : btn.dataset.dir || ""
          }) ?? true);

      setBtnState(btn, { incluida, compat });

      if (incluida) tr.classList.add("selected");
      else tr.classList.remove("selected");
    });
  }

  /* ========== Delegación de clicks ========== */
  function wireDelegation() {
    const tbody = document.querySelector("#tblPedidos tbody");
    if (!tbody || tbody._wired) return;

    tbody.addEventListener("click", async (e) => {
      const btn = e.target.closest("button");
      if (!btn) return;

      // Si ya está seleccionada o disabled → no hacer nada
      if (btn.classList.contains("btnSelected") || btn.disabled) return;

      const tr = btn.closest("tr");
      const meta = filaMetaDesdeTR(tr);
      if (!meta) return;

      // Delega TODA la validación/merge a ItemsProductos
      await window.ItemsProductos?.cargarPedido?.(meta);

      // refrescos visuales
      refreshSingle(meta.op);
      refreshCompatHighlights();
    });

    tbody._wired = true;
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", wireDelegation);
  } else {
    wireDelegation();
  }

  // Exponer API pública
  window.Pedidos = { pintarLista, refreshCompatHighlights, refreshSingle };
})();
