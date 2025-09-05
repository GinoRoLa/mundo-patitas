// /Vista/Script/CUS02/preorden.js
(function () {
  const { $, $$, log, msg, to2, setDirty } = window.Utils;
  const { fetchJSON, url } = window.API;

  function pintarPreordenes(rows) {
    const tb = $("#tblPreorden tbody");
    tb.innerHTML = "";
    (rows || []).forEach((p) => {
      const dni = p.DniCli ?? p.dni ?? "";
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${p.Id_PreOrdenPedido}</td>
        <td>${p.Fec_Emision}</td>
        <td>${dni}</td>
        <td>${to2(p.Total)}</td>
        <td>${p.Estado}</td>
        <td><input type="checkbox" class="chk-pre" value="${p.Id_PreOrdenPedido}"></td>
      `;
      tb.appendChild(tr);
    });
    const hayFilas = rows && rows.length > 0;
    const btn = $("#btnAgregar");
    if (btn) btn.disabled = !hayFilas;

    // Marcar dirty si el usuario selecciona/deselecciona preórdenes
    tb.addEventListener("change", (e) => {
      if (e.target && e.target.classList.contains("chk-pre")) setDirty(true);
    }, { once: true });
  }

  function idsSeleccionadas() {
    return Array.from($$(".chk-pre:checked"))
      .map((c) => parseInt(c.value, 10))
      .filter(Number.isInteger);
  }

  async function consolidar() {
    const dni = ($("#txtDni").value || "").trim();
    const val = window.Utils.validarDni(dni);
    if (!val.ok) { msg(val.msg, true); $("#txtDni").focus(); return; }

    const ids = idsSeleccionadas();
    if (!ids.length) { msg("Debe seleccionar al menos una preorden para generar la orden.", true); return; }

    const body = new URLSearchParams({ dni });
    ids.forEach((v, i) => body.append(`ids[${i}]`, String(v)));

    const r = await fetchJSON(url.consolidar, { method: "POST", body });
    if (!r.ok && r.error) { msg(r.error, true); return; }

    window.Orden.pintarItemsConsolidados(r);
    setDirty(true); // ← hubo acción relevante
    msg("Preórdenes consolidadas correctamente.");
    log("Consolidación ←", r);
  }

  window.Preorden = { pintarPreordenes, idsSeleccionadas, consolidar };
})();
