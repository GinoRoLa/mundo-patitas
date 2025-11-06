// =======================================================
// solicitudCotizacion.js
// Gestión exclusiva de solicitudes de cotización generadas
// Responsabilidad: Tabla "Solicitud de cotización generadas" (tbodyCotsGeneradas)
// =======================================================
(function () {
  const tbody = document.getElementById("tbodyCotsGeneradas");
  const seccion = document.getElementById("secCotsGeneradas");

  if (!tbody || !seccion) {
    console.warn("⚠️ Elementos de solicitudes no encontrados");
    return;
  }

  /**
   * Cargar y mostrar solicitudes generadas desde API
   */
  async function cargarSolicitudes(idReq) {
    if (!idReq) {
      limpiarTabla();
      return;
    }

    try {
      const { fetchJSON, url } = window.API15;
      const res = await fetchJSON(url.solsGeneradas(idReq), { method: "GET" });

      if (!res || !res.ok) {
        console.error("❌ Error al cargar solicitudes:", res?.error || "Sin respuesta");
        mostrarError("No se pudieron cargar las solicitudes generadas");
        return;
      }

      const { solicitudes = [], conteo = {} } = res;

      if (solicitudes.length === 0) {
        mostrarVacio();
        return;
      }

      renderizarTabla(solicitudes);
      actualizarEstadisticas(conteo);
    } catch (err) {
      console.error("❌ Error inesperado:", err);
      mostrarError("Error de conexión al cargar solicitudes");
    }
  }

  /**
   * Renderizar filas en la tabla
   */
  function renderizarTabla(solicitudes) {
    tbody.innerHTML = "";

    solicitudes.forEach((sol) => {
      const tr = document.createElement("tr");

      // Formatear fechas
      const fechaEmision = formatearFecha(sol.FechaEmision);

      // Badge de estado
      const badgeEstado = crearBadgeEstado(sol.Estado);

      tr.innerHTML = `
        <td>${sol.IDsolicitud || "—"}</td>
        <td>${sol.RUC || "—"}</td>
        <td>${escapeHtml(sol.Empresa || "—")}</td>
        <td title="${escapeHtml(sol.Correo || "")}">${escapeHtml(truncar(sol.Correo, 30)) || "—"}</td>
        <td>${fechaEmision}</td>
        <td>${badgeEstado}</td>
      `;

      tbody.appendChild(tr);
    });
  }

  /**
   * Crear badge visual según estado
   */
  function crearBadgeEstado(estado) {
    const badges = {
      Pendiente: '<span class="badge badge--warning">⏳ Pendiente</span>',
      Enviada: '<span class="badge badge--info">📤 Enviada</span>',
      Respondida: '<span class="badge badge--success">✅ Respondida</span>',
      Vencida: '<span class="badge badge--danger">❌ Vencida</span>',
    };
    return badges[estado] || `<span class="badge">${escapeHtml(estado)}</span>`;
  }

  /**
   * Actualizar estadísticas en consola
   */
  function actualizarEstadisticas(conteo) {
    const total = Object.values(conteo).reduce((a, b) => a + b, 0);
    console.log(`📊 Total solicitudes: ${total}`, conteo);
  }

  /**
   * Mostrar mensaje de tabla vacía
   */
  function mostrarVacio() {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="empty-msg">
          📭 No hay solicitudes de cotización generadas para este requerimiento
        </td>
      </tr>
    `;
  }

  /**
   * Mostrar mensaje de error
   */
  function mostrarError(mensaje) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="error-msg">
          ⚠️ ${escapeHtml(mensaje)}
        </td>
      </tr>
    `;
  }

  /**
   * Limpiar tabla
   */
  function limpiarTabla() {
    tbody.innerHTML = "";
  }

  // =======================================================
  // Utilidades
  // =======================================================

  function formatearFecha(fecha) {
    if (!fecha) return "—";
    const d = new Date(fecha);
    if (isNaN(d)) return "—";
    const dia = String(d.getDate()).padStart(2, "0");
    const mes = String(d.getMonth() + 1).padStart(2, "0");
    const año = d.getFullYear();
    return `${dia}/${mes}/${año}`;
  }

  function escapeHtml(texto) {
    if (!texto) return "";
    const div = document.createElement("div");
    div.textContent = texto;
    return div.innerHTML;
  }

  function truncar(texto, max) {
    if (!texto || texto.length <= max) return texto;
    return texto.substring(0, max) + "...";
  }

  // =======================================================
  // Exponer API pública
  // =======================================================
  window.SolicitudCotizacion = {
    cargar: cargarSolicitudes,
    limpiar: limpiarTabla,
  };
})();