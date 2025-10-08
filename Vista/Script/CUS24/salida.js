// /Vista/Script/CUS24/salida.js
(function () {
  // --- helpers modal en salida.js ---
  function showModal({
    title = "Mensaje",
    message = "",
    okText = "Aceptar",
    onOk = null,
  } = {}) {
    const dlg = document.getElementById("appDialog");
    const h3 = document.getElementById("appDialogTitle");
    const p = document.getElementById("appDialogMsg");
    const ok = document.getElementById("appDialogOk");
    const cancel = document.getElementById("appDialogCancel");
    if (!dlg) return;

    if (h3) h3.textContent = title;
    if (p) p.textContent = message;
    if (cancel) cancel.style.display = "none";
    if (ok) {
      ok.textContent = okText || "Aceptar";
      ok.addEventListener("click", function handle() {
        dlg.close("ok");
        ok.removeEventListener("click", handle);
        if (typeof onOk === "function") onOk();
      });
    }
    if (dlg.showModal) dlg.showModal();
    else dlg.setAttribute("open", "open");
  }

  async function onGenerarSalida() {
    const msg = document.getElementById("msg");

    // OPs incluidas
    const ops = (window.ItemsProductos?.opsIncluidas ?? []).slice();
    if (!ops.length) {
      window.Utils24?.showMsg?.(msg, "error", "No hay pedidos seleccionados.", {
        autoclear: 3000,
      });
      return;
    }

    const asigRV = window.Asignacion?.idAsignacionRV ?? null;
  if (!asigRV) {
    window.Utils24?.showMsg?.(msg, "error", "Falta la asignación (vehículo/repartidor). Vuelve a buscar la asignación.", {autoclear: 4000});
    return;
  }

    // Anchor (dni + dirección + distrito)
    const anch = window.AnchorCUS24?.get?.();
    const destTxt = document.getElementById("txtDireccionActiva")?.value || "";
    const distrito = destTxt.split("-").pop()?.trim() || ""; // si lo pintaste como "dir - distrito"

    // Origen (almacén)
    const origen = window.ORIGEN || {}; // {id, nombre, direccion, idDistrito}
    if (!origen.id) {
      window.Utils24?.showMsg?.(msg, "error", "Falta origen (almacén).", {
        autoclear: 3000,
      });
      return;
    }

    // Transporte (ya los pintaste en la guía)
    const vehiculo = {
      marca: document.getElementById("vehMarca")?.value || "",
      placa: document.getElementById("vehPlaca")?.value || "",
      modelo: document.getElementById("vehModelo")?.value || "",
    };
    const transportista = {
      conductor: document.getElementById("guiaConductor")?.value || "",
      licencia: document.getElementById("guiaLic")?.value || "",
    };

    // Destinatario (si quieres mostrar nombre del cliente en la guía)
    const destinatarioNombre =
      document.getElementById("NombreRecep")?.value || "";

    const payload = {
    ops,
    anchor: {
      dni: anch?.dni || document.getElementById('DniRecep')?.value || '',
      direccion: anch?.direccionRaw || (document.getElementById('txtDireccionActiva')?.value.split('-')[0] || '').trim(),
      distrito
    },
    origen: { id: origen.id },
    asignacionId: window.Asignacion?.id ?? null,         // opcional meta
    asignacionRV: asigRV,                                 // <-- ENVIAMOS OBLIGATORIO
    vehiculo: {
      marca:  document.getElementById('vehMarca')?.value || '',
      placa:  document.getElementById('vehPlaca')?.value || '',
      modelo: document.getElementById('vehModelo')?.value || ''
    },
    transportista: {
      conductor: document.getElementById('guiaConductor')?.value || '',
      licencia:  document.getElementById('guiaLic')?.value || ''
    },
    destinatarioNombre: document.getElementById('NombreRecep')?.value || ''
  };

    const { fetchJSON, url } = window.API24 || {};
    if (!fetchJSON || !url?.generarSalida) {
      window.Utils24?.showMsg?.(msg, "error", "API no disponible.", {
        autoclear: 3000,
      });
      return;
    }

    document.getElementById("btnGenerar")?.setAttribute("disabled", "disabled");
    window.Utils24?.showMsg?.(msg, "info", "Generando salida…", {
      autoclear: 0,
    });

    let r;
    try {
      r = await fetchJSON(url.generarSalida, {
        method: "POST",
        credentials: "include",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
    } catch (e) {
      window.Utils24?.showMsg?.(
        msg,
        "error",
        "No se pudo conectar al servidor.",
        { autoclear: 3500 }
      );
      document.getElementById("btnGenerar")?.removeAttribute("disabled");
      return;
    }

    if (!r || !r.ok) {
      window.Utils24?.showMsg?.(
        msg,
        "error",
        r?.error || "No se pudo generar la salida.",
        { autoclear: 4000 }
      );
      document.getElementById("btnGenerar")?.removeAttribute("disabled");
      return;
    }

    const nro = r.guiaNumeroStr || r.guiaNumero || "(sin nro)";
    window.Utils24?.showMsg?.(msg, "ok", `Salida registrada. Guía Nº ${nro}.`, {
      autoclear: 5000,
    });

    // Modal bonito si lo deseas…
    window.ModalCUS24?.show?.({
      title: "Guía generada",
      body: `Se emitió la Guía Nº <b>${nro}</b>.`,
      okText: "Aceptar",
    });

    // Reset
    window.ItemsProductos?.limpiar?.();
    document.getElementById("btnGenerar")?.setAttribute("disabled", "disabled");

    showModal({
      title: "Guía de Remisión generada",
      message: `Se generó la Guía Nº ${r.guiaNumero}.`,
      okText: "Entendido",
      onOk: () => {
        window.ItemsProductos?.limpiar?.();
        document
          .getElementById("btnGenerar")
          ?.setAttribute("disabled", "disabled");
      },
    });

    // Si quieres resetear UI:
    window.ItemsProductos?.limpiar?.();
    document.getElementById("btnGenerar")?.setAttribute("disabled", "disabled");
  }

  function init() {
    document
      .getElementById("btnGenerar")
      ?.addEventListener("click", onGenerarSalida);
  }
  document.addEventListener("DOMContentLoaded", init);
})();
