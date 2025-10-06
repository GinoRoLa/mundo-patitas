// ======== t77 en front ========

// Normaliza texto
function _norm(s) {
  return (s || "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .trim();
}

// Estructuras en memoria
let DIST_LIST = Array.isArray(window.T77) ? window.T77 : [];
let DIST_BY_NAME = {};
let DIST_BY_ID = {};

function hydrateDistritos() {
  DIST_LIST = Array.isArray(window.T77) ? window.T77 : [];
  DIST_BY_NAME = {};
  DIST_BY_ID = {};

  DIST_LIST.forEach(d => {
    if ((d.Estado || "").toLowerCase() === "activo") {
      const rec = {
        Id_Distrito: Number(d.Id_Distrito),
        DescNombre: d.DescNombre,
        MontoCosto: Number(d.MontoCosto),
        Estado: d.Estado
      };
      DIST_BY_NAME[_norm(d.DescNombre)] = rec;
      DIST_BY_ID[rec.Id_Distrito] = rec;
    }
  });
  _buildDistritoDatalist();
}

function _buildDistritoDatalist() {
  const dl = document.getElementById("dlDistritos");
  if (!dl) return;
  dl.innerHTML = "";
  Object.values(DIST_BY_ID).forEach(d => {
    const opt = document.createElement("option");
    opt.value = d.DescNombre;
    opt.label = `${d.DescNombre} — S/ ${d.MontoCosto.toFixed(2)}`;
    opt.dataset.monto = d.MontoCosto;
    dl.appendChild(opt);
  });
}

// Lookup por nombre (¡incluye Id_Distrito!)
function costoPorNombreLocal(nombre) {
  const rec = DIST_BY_NAME[_norm(nombre)];
  return rec
    ? { Id_Distrito: rec.Id_Distrito, MontoCosto: rec.MontoCosto, DescNombre: rec.DescNombre }
    : null;
}

// Lookup por ID
function costoPorIdLocal(id) {
  const rec = DIST_BY_ID[Number(id)];
  return rec
    ? { Id_Distrito: rec.Id_Distrito, MontoCosto: rec.MontoCosto, DescNombre: rec.DescNombre }
    : null;
}

// Setea costo y total
function setCostoEnvio(nuevoCosto) {
  const ent = document.getElementById("txtCostoEnt");
  if (!ent) return;
  const n = Number(nuevoCosto) || 0;
  ent.value = n.toFixed(2);

  const subt = Number(document.getElementById("txtSubTotal")?.value || 0);
  const desc = Number(document.getElementById("txtDesc")?.value || 0);
  const total = Math.max(0, subt - desc + n);
  const tot = document.getElementById("txtTotal");
  if (tot) tot.value = total.toFixed(2);
}

// Typeahead para “otra dirección”
function setupDistritoTypeahead() {
  const inp = document.getElementById("envioDistrito");
  if (!inp) return;
  inp.setAttribute("list", "dlDistritos");
  if (inp._bound) return;
  inp._bound = true;

  const hint = document.getElementById("distritoHint");
  const hid  = document.getElementById("envioDistritoId");

  const apply = () => {
    const m = costoPorNombreLocal(inp.value);
    if (m) {
      setCostoEnvio(m.MontoCosto);
      if (hid) hid.value = m.Id_Distrito;  // ← ahora sí se guarda el ID
      if (hint) hint.textContent = `Costo por distrito: S/ ${m.MontoCosto.toFixed(2)}`;
    } else {
      if (hid) hid.value = "";
      if (hint) hint.textContent = "Distrito no encontrado en la lista.";
    }
    window.Orden?.validarReadyParaRegistrar?.();
  };

  inp.addEventListener("input", apply);
  inp.addEventListener("change", apply);
}

document.addEventListener("DOMContentLoaded", hydrateDistritos);

// Exponer helpers
window.costoPorNombreLocal = costoPorNombreLocal;
window.costoPorIdLocal = costoPorIdLocal;
window.setupDistritoTypeahead = setupDistritoTypeahead;
