// /Vista/Script/CUS02_OrdenPedido.js

/* ===================== DEBUG ===================== */
const DEBUG = true;
const log   = (...a) => { if (DEBUG) console.log('[CUS02]', ...a); };
const warn  = (...a) => { if (DEBUG) console.warn('[CUS02]', ...a); };
const error = (...a) => { if (DEBUG) console.error('[CUs02]', ...a); };

/* ===================== Helpers ===================== */
const API = window.CUS_BASE || window.API_CUS02 || '/Controlador/ControladorCUS02.php';
const $  = (s) => document.querySelector(s);
const $$ = (s) => document.querySelectorAll(s);

function msg(texto = '', isError = false) {
  const m = $("#msg");
  if (!m) return;
  m.textContent = texto;
  m.className = "msg" + (texto ? (isError ? " error" : " ok") : "");
  if (texto) m.scrollIntoView({ behavior: "smooth", block: "nearest" });
}

function validarDni(v){
  const raw = (v || '').trim();
  if (raw === '') return { ok:false, msg:'Ingrese DNI (8 dígitos numéricos).' };
  if (/[^0-9]/.test(raw)) return { ok:false, msg:'Ingrese dato numérico (DNI de 8 dígitos).' };
  if (raw.length !== 8)   return { ok:false, msg:'DNI incompleto: deben ser 8 dígitos.' };
  return { ok:true };
}

function to2(n) { const x = Number(n); return Number.isFinite(x) ? x.toFixed(2) : "0.00"; }
function setNum(el, val) { if (el) el.value = to2(val); }

/* fetchJSON */
async function fetchJSON(url, opts = {}) {
  const finalOpts = Object.assign({ headers: { "X-Requested-With": "fetch" } }, opts);
  log('FETCH →', url, finalOpts);
  let res, text;
  try {
    res = await fetch(url, finalOpts);
    text = await res.text();
  } catch (e) {
    error('FETCH ERROR (network):', e);
    return { ok: false, error: String(e), network: true };
  }
  let data;
  try {
    data = JSON.parse(text);
  } catch {
    data = { ok: false, error: `HTTP ${res.status}`, raw: text };
  }
  if (!res.ok) {
    warn('FETCH ← HTTP', res.status, url, data);
    return Object.assign({ ok: false, httpStatus: res.status }, data);
  }
  log('FETCH ← OK', data);
  return data;
}

/* ===================== Métodos de entrega ===================== */
async function cargarMetodosEntrega() {
  const r = await fetchJSON(`${API}?accion=metodos-entrega`);
  if (!r.ok) {
    msg('No se pudieron cargar los métodos de entrega.', true);
    return;
  }
  const cbo = $("#cboEntrega");
  cbo.innerHTML = "";
  (r.metodos || []).forEach(m => {
    const opt = document.createElement("option");
    opt.value = m.Id_MetodoEntrega;
    opt.textContent = m.Descripcion;
    opt.dataset.costo = m.Costo;
    cbo.appendChild(opt);
  });

  // Default: "tienda" si existe
  const idx = Array.from(cbo.options).findIndex(o => /tienda/i.test(o.textContent));
  cbo.selectedIndex = idx >= 0 ? idx : 0;

  const costo = Number(cbo.selectedOptions[0]?.dataset.costo || 0);
  setNum($("#txtCostoEnt"), costo);
  log('Métodos cargados:', r.metodos);
  log('Seleccion inicial → id:', cbo.value, 'costo:', costo);
}

/* ===================== Cliente & Preórdenes ===================== */
function pintarPreordenes(rows) {
  const tb = $("#tblPreorden tbody");
  tb.innerHTML = "";
  (rows || []).forEach(p => {
    const dni = p.DniCli ?? p.dni ?? ""; // por si el backend lo aliasa distinto
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
}


function limpiarCliente() {
  ["#txtDni", "#txtNombre", "#txtApePat", "#txtApeMat", "#txtTel", "#txtDir", "#txtEmail"]
    .forEach(s => { const el = $(s); if (el) el.value = ""; });

  $("#tblPreorden tbody").innerHTML = "";
  $("#tblItems tbody").innerHTML = "";

  $("#txtCantProd").value = 0;
  setNum($("#txtDesc"), 0);
  setNum($("#txtSubTotal"), 0);
  setNum($("#txtCostoEnt"), 0);
  setNum($("#txtTotal"), 0);

  $("#btnRegistrar").disabled = true;
}


async function buscarCliente() {
  const dni = ($("#txtDni").value || "").trim();
  const v = validarDni(dni);
  if (!v.ok) {
    msg(v.msg, true);
    $("#txtDni").focus();
    return;
  }
  log('Buscar cliente → DNI', dni);

  const r = await fetchJSON(`${API}?accion=buscar-cliente`, {
    method: "POST",
    body: new URLSearchParams({ dni })
  });

  if (!r.ok && r.error) {
    msg(r.error, true);
    return;
  }

  if (!r.found) {
    msg("Cliente no encontrado.");
    limpiarCliente();
    return;
  }

  // Pintar cliente
  $("#txtNombre").value = r.cliente.des_nombreCliente || '';
  $("#txtApePat").value = r.cliente.des_apepatCliente || '';
  $("#txtApeMat").value = r.cliente.des_apematCliente || '';
  $("#txtTel").value    = r.cliente.num_telefonoCliente || '';
  $("#txtEmail").value  = r.cliente.email_cliente || '';
  $("#txtDir").value    = r.cliente.direccionCliente || '';

  // Pintar preórdenes
  pintarPreordenes(r.preordenes || []);
  log('Preórdenes recibidas:', (r.preordenes || []).length, r.preordenes);

  if (!(r.preordenes || []).length) {
    msg("El cliente no tiene preórdenes válidas en las últimas 24 horas.");
  } else {
    msg("");
  }
}


/* ===================== Consolidación ===================== */
function idsSeleccionadas() {
  return Array.from($$(".chk-pre:checked")).map(c => parseInt(c.value, 10)).filter(Number.isInteger);
}

function pintarItemsConsolidados(r) {
  const tb = $("#tblItems tbody");
  tb.innerHTML = "";
  (r.items || []).forEach(it => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${it.IdProducto}</td>
      <td>${it.NombreProducto}</td>
      <td>${to2(it.PrecioUnitario)}</td>
      <td>${it.Cantidad}</td>
      <td>${to2(it.Subtotal)}</td>
    `;
    tb.appendChild(tr);
  });

  $("#txtCantProd").value = r.cantidadProductos || 0;
  setNum($("#txtDesc"), r.descuento);
  setNum($("#txtSubTotal"), r.subtotal);

  const costo = Number($("#cboEntrega").selectedOptions[0]?.dataset.costo || 0);
  setNum($("#txtCostoEnt"), costo);
  setNum($("#txtTotal"), Math.max(0, (r.subtotal || 0) - (r.descuento || 0) + costo));

  $("#btnRegistrar").disabled = !((r.items || []).length);
}

async function consolidar() {
  const dni = ($("#txtDni").value || "").trim();
  const v = validarDni(dni);
  if (!v.ok) { msg(v.msg, true); $("#txtDni").focus(); return; }

  const sel = idsSeleccionadas();
  if (!sel.length) { msg("Debe seleccionar al menos una preorden para generar la orden.", true); return; }

  log('Consolidar → DNI', dni, 'IDs', sel);

  const body = new URLSearchParams({ dni });
  sel.forEach((v, i) => body.append(`ids[${i}]`, String(v)));

  const r = await fetchJSON(`${API}?accion=consolidar`, { method: 'POST', body });
  if (!r.ok && r.error) {
    msg(r.error, true);
    return;
  }

  pintarItemsConsolidados(r);
  msg("Preórdenes consolidadas correctamente.");
  log('Consolidación ←', r);
}


/* ===================== Registrar ===================== */
async function registrarOrden() {
  const dni = ($("#txtDni").value || "").trim();
  const v = validarDni(dni);
  if (!v.ok) { msg(v.msg, true); $("#txtDni").focus(); return; }

  const sel = idsSeleccionadas();
  if (!sel.length) { msg("Debe seleccionar al menos una preorden.", true); return; }

  const metodoEntregaId = Number($("#cboEntrega").value);
  const descuento = Number($("#txtDesc").value || 0);

  const payload = new URLSearchParams();
  payload.append('dni', dni);
  payload.append('metodoEntregaId', String(metodoEntregaId));
  payload.append('descuento', String(descuento));
  sel.forEach((v, i) => payload.append(`idsPreorden[${i}]`, String(v)));

  log('Registrar → payload', Object.fromEntries(payload));

  const r = await fetchJSON(`${API}?accion=registrar`, { method: 'POST', body: payload });
  if (!r.ok) {
    msg(r.error || "No se pudo registrar", true);
    error('Registrar ← error', r);
    return;
  }

  msg(`Orden #${r.ordenId} registrada.`);
  $("#btnRegistrar").disabled = true;
  log('Registrar ← OK', r);
  limpiarCliente();
}


/* ===================== Eventos y boot ===================== */
function onMetodoEntregaChange(e) {
  const opt = e.target?.selectedOptions?.[0];
  const costo = Number(opt?.dataset?.costo || 0);
  setNum($("#txtCostoEnt"), costo);

  // Recalcula total si ya hay items consolidados
  const subt = Number($("#txtSubTotal").value || 0);
  const desc = Number($("#txtDesc").value || 0);
  if (subt > 0) {
    const total = Math.max(0, subt - desc + costo);
    setNum($("#txtTotal"), total);
  }
  log('Método entrega cambiado → id:', e.target.value, 'costo:', costo);
}

window.addEventListener("DOMContentLoaded", () => {
  log('BOOT con API =', API);
  cargarMetodosEntrega();

  $("#btnBuscar")?.addEventListener("click", buscarCliente);
  $("#btnAgregar")?.addEventListener("click", consolidar);
  $("#btnRegistrar")?.addEventListener("click", registrarOrden);
  $("#cboEntrega")?.addEventListener("change", onMetodoEntregaChange);

  // Trazas globales por si algo explota
  window.addEventListener('error', (ev) => error('window.error:', ev.message, ev.filename, ev.lineno));
  window.addEventListener('unhandledrejection', (ev) => error('unhandledrejection:', ev.reason));
});
