// ======== t77 en front (sin endpoint) ========

// Normaliza texto (quita tildes, minúsculas, trim)
function _norm(s) {
  return (s || "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .trim();
}

// Estructuras en memoria
let DIST_LIST = Array.isArray(window.T77) ? window.T77 : [];
let DIST_MAP  = {};

// Hidrata mapa y arma el datalist
function hydrateDistritos() {
  DIST_LIST = Array.isArray(window.T77) ? window.T77 : [];
  DIST_MAP = {};
  DIST_LIST.forEach(d => {
    // t77: DescNombre, MontoCosto, Estado
    if ((d.Estado || "").toLowerCase() === "activo") {
      DIST_MAP[_norm(d.DescNombre)] = d;
    }
  });
  _buildDistritoDatalist();
}

function _buildDistritoDatalist() {
  const dl = document.getElementById("dlDistritos");
  if (!dl) return;
  dl.innerHTML = "";
  DIST_LIST
    .filter(d => (d.Estado || "").toLowerCase() === "activo")
    .forEach(d => {
      const opt = document.createElement("option");
      opt.value = d.DescNombre; // lo que se coloca en el input
      opt.label = `${d.DescNombre} — S/ ${Number(d.MontoCosto).toFixed(2)}`;
      opt.dataset.monto = d.MontoCosto; // opcional
      dl.appendChild(opt);
    });
}

// Lookup local por nombre de distrito
function costoPorNombreLocal(nombre) {
  const rec = DIST_MAP[_norm(nombre)];
  return rec
    ? { MontoCosto: Number(rec.MontoCosto), DescNombre: rec.DescNombre }
    : null;
}

// Setea costo en UI y recalcula total
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

// Activa typeahead para "otra dirección"
function setupDistritoTypeahead() {
  const inp = document.getElementById("envioDistrito");
  if (!inp) return;
  inp.setAttribute("list", "dlDistritos"); // conecta al datalist

  if (inp._bound) return;  // evita listeners duplicados
  inp._bound = true;

  const hint = document.getElementById("distritoHint");

  const apply = () => {
    const m = costoPorNombreLocal(inp.value);
    if (m) {
      setCostoEnvio(m.MontoCosto);
      if (hint) hint.textContent = `Costo por distrito: S/ ${m.MontoCosto.toFixed(2)}`;
    } else {
      if (hint) hint.textContent = "Distrito no encontrado en la lista.";
      // Si quieres, puedes resetear costo a 0:
      // setCostoEnvio(0);
    }
    window.Orden?.validarReadyParaRegistrar?.();
  };

  inp.addEventListener("input", apply);
  inp.addEventListener("change", apply);
}

// Llamar una vez en el arranque (p.ej. DOMContentLoaded)
document.addEventListener("DOMContentLoaded", hydrateDistritos);

// Exponer helpers si otros módulos los usan
window.costoPorNombreLocal = costoPorNombreLocal;
window.setupDistritoTypeahead = setupDistritoTypeahead;
