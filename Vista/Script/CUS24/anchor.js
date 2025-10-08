// /Vista/Script/CUS24/anchor.js
(function () {
  function normDir(s) {
    return String(s || "")
      .toLowerCase()
      .normalize('NFKC')
      .replace(/\s+/g, ' ')
      .trim();
  }

  let _anchor = null; // { dni, direccionNorm, direccionRaw }

  function setFromPedido(p) {
    _anchor = {
      dni: String(p.receptorDni || '').trim(),
      direccionNorm: normDir(p.direccion),
      direccionRaw: String(p.direccion || '').trim()
    };
    return _anchor;
  }

  function clear() { _anchor = null; }

  function isSet() { return !!_anchor; }

  function isCompatible(p) {
    if (!_anchor) return true;
    const dni = String(p.receptorDni || '').trim();
    const dir = normDir(p.direccion);
    return (_anchor.dni && dni && _anchor.dni === dni) &&
           (_anchor.direccionNorm && dir && _anchor.direccionNorm === dir);
  }

  function get() { return _anchor; }

  window.AnchorCUS24 = { setFromPedido, isCompatible, isSet, clear, get, _normDir: normDir };
})();
