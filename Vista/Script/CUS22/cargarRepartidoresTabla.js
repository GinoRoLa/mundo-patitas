// ===================================================
// 🔹 Lista inicial desde PHP (mantener local para init, pero sincronizar con global)
// ===================================================
let vrOriginalesLocal = window.vrOriginales || [];  // AJUSTE: Local para inicialización
let vrDisponiblesLocal = [...vrOriginalesLocal];    // AJUSTE: Copia local inicial para evitar vacío
let vrSeleccionadosLocal = window.vrSeleccionados || [];  // AJUSTE: Local para init

// AJUSTE: Función helper para sincronizar locales con globales (llamar cuando sea necesario)
function sincronizarConGlobales() {
    if (window.vrDisponibles && window.vrDisponibles.length > 0) {
        vrDisponiblesLocal = [...window.vrDisponibles];
    }
    if (window.vrSeleccionados && window.vrSeleccionados.length > 0) {
        vrSeleccionadosLocal = [...window.vrSeleccionados];
    }
}

// ===================================================
// 🔹 Renderizar tabla de Repartidores-Vehículos
// ===================================================
window.renderRV = function (lista) {
  const tbody = $("#table-body-rv");
  tbody.empty();

  if (lista.length > 0) {
    lista.forEach(function (r) {
      let row = `
        <tr>
          <td>${String(r.CodigoRepartidor).padStart(5, "0")}</td>
          <td>${r.Placa}</td>
          <td>${r.Marca}</td>
          <td>${r.Modelo}</td>
          <td>${parseFloat(r.CargaUtilKg).toFixed(2)}</td>
          <td>${parseFloat(r.CapacidadM3).toFixed(2)}</td>
          <td>
            <button class="style-button btn-disponibilidad-disabled" data-id="${r.CodigoAsignacion}" disabled>Ver</button>
          </td>
        </tr>
      `;
      tbody.append(row);
    });
  } else {
    tbody.append(`
      <tr><td colspan="7">No se encontraron repartidores disponibles.</td></tr>
    `);
  }

  // Mantener mínimo 5 filas visibles
  const minRows = 5;
  const currentRows = tbody.find("tr").length;
  if (currentRows < minRows) {
    for (let i = currentRows; i < minRows; i++) {
      tbody.append(`<tr><td colspan="7">&nbsp;</td></tr>`);
    }
  }
  
  if (typeof window.actualizarEstadoBotones === "function") {
    window.actualizarEstadoBotones();
  }
};

// ===================================================
// 🔹 Selección / Deselección de repartidores (priorizar globales post-filtro)
// ===================================================
$(document).on("change", ".chk-rv", function () {
  const id = parseInt($(this).val());
  const seleccionado = $(this).is(":checked");

  // AJUSTE: Usar globales siempre para selecciones, ya que respetan el filtro de días
  if (seleccionado) {
    // Mover de disponibles a seleccionados
    const item = window.vrDisponibles.find(r => r.CodigoRepartidor == id);
    if (item) {
      window.vrSeleccionados.push(item);
      window.vrDisponibles = window.vrDisponibles.filter(r => r.CodigoRepartidor != id);
      // Sincronizar local solo para fallback
      vrSeleccionadosLocal = [...window.vrSeleccionados];
    }
  } else {
    // Devolver a disponibles
    const item = window.vrSeleccionados.find(r => r.CodigoRepartidor == id);
    if (item) {
      window.vrDisponibles.push(item);
      window.vrSeleccionados = window.vrSeleccionados.filter(r => r.CodigoRepartidor != id);
      // Sincronizar local solo para fallback
      vrDisponiblesLocal = [...window.vrDisponibles];
    }
  }

  // AJUSTE: Renderizar con la global actualizada
  window.renderRV(window.vrDisponibles);

  if (typeof renderRVSeleccionados === "function") {
    renderRVSeleccionados(window.vrSeleccionados);
  }

  // AJUSTE: Actualizar botones globales después de selección
  if (typeof window.actualizarEstadoBotones === "function") {
    window.actualizarEstadoBotones();
  }
});

// ===================================================
// 🔹 Filtro de repartidores (Buscar / Ver todo) - filtrar de global post-init
// ===================================================
$(document).on("submit", ".verDisponibilidad", function (e) {
  e.preventDefault();

  const botonPresionado = e.originalEvent.submitter?.textContent?.trim();
  const codigo = $(this).find("input").val().trim();

  // 👉 Ver todo
  if (botonPresionado === "Ver todo") {
    $(this).find("input").val("");
    // AJUSTE: Usar global (que ya tiene el filtro de días si aplica)
    window.renderRV(window.vrDisponibles);
    showToast("Mostrando todos los repartidores disponibles.", "info");
    return;
  }

  // 👉 Buscar
  if (codigo === "") {
    showToast("Ingrese un código para buscar.", "warning");
    return;
  }
   
  // ✅ Validar que solo contenga números
  if (!/^\d+$/.test(codigo)) {
    showToast("El código debe contener solo números.", "error");
    return;
  }

  // AJUSTE: Filtrar siempre de window.vrDisponibles (respeta filtro de días; usa local como fallback si global vacío)
  let listaBase = window.vrDisponibles && window.vrDisponibles.length > 0 ? window.vrDisponibles : vrDisponiblesLocal;
  const filtrados = listaBase.filter(r =>
    String(r.CodigoRepartidor).includes(codigo)
  );

  if (filtrados.length > 0) {
    window.renderRV(filtrados);
    showToast(`${filtrados.length} repartidor(es) encontrados.`, "success");
  } else {
    window.renderRV([]);
    showToast("No se encontró ningún repartidor con ese código.", "error");
  }
});

// ===================================================
// 🔹 Inicialización (usar local al inicio, sincronizar si global ya filtrado)
// ===================================================
$(document).ready(function () {
  // AJUSTE: Inicializar global con local si no existe (evita vacío al cargar)
  if (!window.vrDisponibles || window.vrDisponibles.length === 0) {
    window.vrDisponibles = [...vrDisponiblesLocal];
  }
  if (!window.vrSeleccionados || window.vrSeleccionados.length === 0) {
    window.vrSeleccionados = [...vrSeleccionadosLocal];
  }

  // AJUSTE: Sincronizar locales con globales por si hay estado previo
  sincronizarConGlobales();

  // AJUSTE: Renderizar con global (ahora poblado)
  window.renderRV(window.vrDisponibles);
});
