// /Vista/Script/CUS02/preorden.js
(function () {
  const { $, $$, to2, setDirty, Messages } = window.Utils;
  const { fetchJSON, url } = window.API;

  /** Devuelve IDs de preórdenes seleccionadas (números). */
  function idsSeleccionadas() {
    return Array.from($$(".chk-pre:checked"))
      .map((c) => parseInt(c.value, 10))
      .filter(Number.isInteger);
  }

  /** Envía IDs para consolidar y pinta el consolidado en la orden. */
  async function consolidar() {
    const dni = ($("#txtDni").value || "").trim();
    const val = window.Utils.validarDni(dni);
    if (!val.ok) { Messages.cliente.error(val.msg, { persist: true }); $("#txtDni").focus(); return; }

    const ids = idsSeleccionadas();
    if (!ids.length) {
      window.Orden.vaciarConsolidado();
      setDirty(false);
      Messages.preorden.error("Debes seleccionar al menos una preorden para generar la orden.", { persist: true });
      return;
    }

    const body = new URLSearchParams({ dni });
    ids.forEach((v, i) => body.append(`ids[${i}]`, String(v)));

    let r;
    try {
      r = await fetchJSON(url.consolidar, { method: "POST", body });
    } catch {
      Messages.preorden.error("No se pudo consolidar. Verifica tu conexión e inténtalo otra vez.", { autoclear: 6000 });
      return;
    }
    if (!r.ok && r.error) { Messages.preorden.error(r.error, { autoclear: 6500 }); return; }

    window.Orden.pintarItemsConsolidados(r);
    setDirty(true);
    Messages.preorden.ok("Preórdenes consolidadas correctamente.", { autoclear: 1300 });
  }

  /** Pinta la tabla de preórdenes y habilita/deshabilita el botón “Agregar”. */
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

    if (!hayFilas) {
      Messages.preorden.error("No hay preórdenes vigentes en las últimas 24 horas.", { persist: true });
    } else {
      // Limpia mensaje previo si había uno de error
      Messages.preorden.clear();
    }

    // Marcar “dirty” y limpiar mensajes al cambiar selecciones (una sola vez)
    tb.addEventListener("change", (e) => {
      if (e.target && e.target.classList.contains("chk-pre")) {
        setDirty(true);
        Messages.preorden.clear();
      }
    }, { once: true });
  }

  // Export
  window.Preorden = { pintarPreordenes, idsSeleccionadas, consolidar };
})();
