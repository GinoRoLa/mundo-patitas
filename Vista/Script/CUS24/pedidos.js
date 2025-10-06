// /Vista/Script/CUS24/pedidos.js
(function () {
  const $ = (sel, ctx = document) => ctx.querySelector(sel);

  function filaPedido(p) {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td data-label="Código (OP)">${p.idOrdenPedido ?? ""}</td>
      <td data-label="Cliente">${(p.cliente || "").replace(/\s+/g,' ').trim()}</td>
      <td data-label="Dirección">${p.direccion ?? ""}</td>
      <td data-label="Distrito">${p.distritoNombre ?? ""}</td>
      <td data-label="OSE">${p.idOSE ?? ""}</td>
      <td data-label="Estado">${p.estadoOP ?? ""}</td>
      <td data-label="Acción">
        <button type="button" class="btnAdd" data-op="${p.idOrdenPedido}">Agregar</button>
      </td>
    `;
    return tr;
  }

  function pintarLista(pedidos) {
    const tb = $("#tblPedidos tbody");
    if (!tb) return;

    tb.innerHTML = "";
    pedidos.forEach(p => tb.appendChild(filaPedido(p)));

    // Bind click “Agregar” (la lógica que necesites)
    tb.querySelectorAll(".btnAdd").forEach(btn => {
      btn.addEventListener("click", () => {
        const idOP = btn.getAttribute("data-op");
        // aquí disparas tu flujo para setear dirección activa y mostrar ítems
        // por ahora, solo marcamos visualmente la fila:
        btn.closest("tr")?.classList.add("selected");
        // TODO: llamar a tu endpoint de ítems (cuando lo implementes)
        // window.Items?.cargarPorOrden?.(idOP);
      });
    });
  }

  window.Pedidos = { pintarLista };
})();
