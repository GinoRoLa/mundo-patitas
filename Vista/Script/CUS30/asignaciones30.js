// /vista/Script/CUS30/asignaciones30.js
(function () {
  const dniRegex = /^\d{8}$/;
  const showToast = window.Utils30?.showToast || ((m) => alert(m));

  function getEl(id) {
    return document.getElementById(id);
  }

  function mostrarMsgAsignaciones(msg, tipo = "") {
    const div = getEl("msgAsignaciones");
    if (!div) return;

    div.textContent = msg || "";
    div.classList.remove("ok", "error");

    if (tipo) {
      div.classList.add(tipo);
    }
  }

  function limpiarTablaAsignaciones() {
    const tbody = getEl("tblAsignaciones")?.querySelector("tbody");
    if (tbody) tbody.innerHTML = "";
  }

  function renderAsignaciones(asignaciones) {
    const tbody = getEl("tblAsignaciones")?.querySelector("tbody");
    if (!tbody) return;

    tbody.innerHTML = "";

    if (!asignaciones || asignaciones.length === 0) {
      //mostrarMsgAsignaciones("No hay asignaciones pendientes para ese repartidor.", "ok");
      showToast("No hay asignaciones pendientes para ese repartidor.", "info");
      return;
    }

    // Guardamos en un namespace global por si otros módulos necesitan la data
    window.CUS30 = window.CUS30 || {};
    window.CUS30.asignaciones = asignaciones;

    for (const a of asignaciones) {
      const tr = document.createElement("tr");

      // Id asignación
      const tdId = document.createElement("td");
      tdId.textContent = a.Id_OrdenAsignacion ?? "";
      tr.appendChild(tdId);

      // Fecha programada
      const tdFec = document.createElement("td");
      tdFec.textContent = a.FechaProgramada ?? "";
      tr.appendChild(tdFec);

      // Nombre repartidor
      const tdRep = document.createElement("td");
      tdRep.textContent = a.RepartidorNombre ?? "";
      tr.appendChild(tdRep);

      // Fondo (S/)
      const tdFondo = document.createElement("td");
      tdFondo.textContent = (Number(a.MontoFondo) || 0).toFixed(2);
      tdFondo.style.textAlign = "right";
      tr.appendChild(tdFondo);

      // Estado de nota de caja
      const tdEstNota = document.createElement("td");
      tdEstNota.textContent = a.EstadoNota ?? "Sin Nota";
      tr.appendChild(tdEstNota);

      // Estado de la ruta
      const tdEstRuta = document.createElement("td");
      tdEstRuta.textContent = a.EstadoRuta ?? "";
      tr.appendChild(tdEstRuta);

      // Botón Ver
      const tdVer = document.createElement("td");
      const btn = document.createElement("button");
      btn.type = "button";
      btn.textContent = "Ver";
      btn.addEventListener("click", () => {
        cargarRecaudacionDetalle(a.Id_OrdenAsignacion);
      });
      tdVer.appendChild(btn);
      tr.appendChild(tdVer);

      tbody.appendChild(tr);
    }

    /* mostrarMsgAsignaciones(
      `Se encontraron ${asignaciones.length} asignación(es).`,
      "ok"
    ); */
    showToast(`Se encontraron ${asignaciones.length} asignación(es).`, "ok");
  }

  async function buscarAsignacionesPorDni() {
    const txtDni = getEl("txtDniRepartidor");
    if (!txtDni) return;

    const dni = (txtDni.value || "").trim();
    if (!dniRegex.test(dni)) {
      //mostrarMsgAsignaciones("DNI inválido. Deben ser 8 dígitos.", "error");
      showToast("DNI inválido. Deben ser 8 dígitos.", "error");
      limpiarTablaAsignaciones();
      return;
    }

    //mostrarMsgAsignaciones("Buscando asignaciones...", "");
    limpiarTablaAsignaciones();

    const url = API30.url.asignacionesPendientes(dni);
    const res = await API30.fetchJSON(url);

    if (!res.ok) {
      console.error("Error asignaciones-pendientes:", res);
      const errorMsg = res.error || "No se pudo obtener asignaciones.";
      //mostrarMsgAsignaciones(errorMsg, "error");
      showToast(errorMsg, "error");
      return;
    }

    const asignaciones = res.rutas || res.asignaciones || [];
    renderAsignaciones(asignaciones);
  }

  async function cargarRecaudacionDetalle(idAsignacion) {
    if (!idAsignacion) return;

    const msgPedidos = document.getElementById("msgPedidos");
    if (msgPedidos) {
      msgPedidos.textContent = "Cargando detalle de recaudación...";
      msgPedidos.classList.remove("ok", "error");
    }

    const url = API30.url.recaudacionDetalle(idAsignacion);
    const res = await API30.fetchJSON(url);

    if (!res.ok) {
      console.error("Error recaudacion-detalle:", res);
      const errorMsg = res.error || "No se pudo obtener el detalle de recaudación.";
      if (msgPedidos) {
        msgPedidos.textContent = errorMsg;
        msgPedidos.classList.add("error");
      }
      showToast(errorMsg, "error");
      return;
    }

    // Pasamos la respuesta al módulo de pedidos/resumen
    window.Pedidos30.setDetalleRecaudacion(res);
  }

  function initAsignaciones30() {
    const btnBuscar = getEl("btnBuscarAsignaciones");
    const txtDni = getEl("txtDniRepartidor");

    if (btnBuscar) {
      btnBuscar.addEventListener("click", buscarAsignacionesPorDni);
    }
    if (txtDni) {
      txtDni.addEventListener("keydown", (ev) => {
        if (ev.key === "Enter") {
          ev.preventDefault();
          buscarAsignacionesPorDni();
        }
      });
    }
  }

  window.Asignaciones30 = {
    init: initAsignaciones30,
    buscarAsignacionesPorDni,
  };
})();