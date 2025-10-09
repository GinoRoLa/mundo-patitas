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

  function updateGenerarHabilitado() {
    const tb = document.querySelector("#tblPedidos tbody");
    const gen = document.getElementById("btnGenerar");
    const anch = window.AnchorCUS24?.get?.();
    if (!tb || !gen) return;

    // sin ancla => no generar
    if (!anch) {
      gen.disabled = true;
      const msg = document.getElementById("msgPedidos");
      if (msg) {
        msg.classList.add("hint");
        msg.textContent = "Selecciona un pedido.";
      }
      return;
    }

    const rows = [...tb.querySelectorAll("tr")];
    const compatibles = rows.filter((tr) => {
      const btn = tr.querySelector("button");
      if (!btn) return false;
      const estado =
        tr.querySelector('td[data-label="Estado"]')?.textContent.trim() || "";
      const p = {
        idOrdenPedido: +btn.dataset.op,
        receptorDni: btn.dataset.dni,
        direccion: btn.dataset.dir,
      };
      return (
        estado === "Pagado" && (window.AnchorCUS24?.isCompatible?.(p) ?? true)
      );
    });

    const totCompat = compatibles.length;
    const sel = (window.ItemsProductos?.opsIncluidas ?? []).length;
    const falta = Math.max(totCompat - sel, 0);

    gen.disabled = falta > 0 || sel === 0;

    const msg = document.getElementById("msgPedidos");
    if (msg) {
      msg.classList.add("hint");
      msg.textContent =
        falta > 0
          ? `Faltan ${falta} pedido(s) de esta dirección. Agréguelos para poder generar la guía.`
          : `Listo: todos los pedidos de esta dirección están seleccionados.`;
    }
  }

  async function onGenerarSalida() {
    const msg = document.getElementById("msg");

    // OPs incluidas (¡capturar antes de limpiar!)
    const ops = (window.ItemsProductos?.opsIncluidas ?? []).slice();
    if (!ops.length) {
      window.Utils24?.showMsg?.(msg, "error", "No hay pedidos seleccionados.", {
        autoclear: 3000,
      });
      return;
    }

    const asigRV = window.Asignacion?.idAsignacionRV ?? null;
    if (!asigRV) {
      window.Utils24?.showMsg?.(
        msg,
        "error",
        "Falta la asignación (vehículo/repartidor). Vuelve a buscar la asignación.",
        { autoclear: 4000 }
      );
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

    // Destinatario
    const destinatarioNombre =
      document.getElementById("NombreRecep")?.value || "";

    const payload = {
      ops,
      anchor: {
        dni: anch?.dni || document.getElementById("DniRecep")?.value || "",
        direccion:
          anch?.direccionRaw ||
          (
            document
              .getElementById("txtDireccionActiva")
              ?.value.split("-")[0] || ""
          ).trim(),
        distrito,
      },
      origen: { id: origen.id },
      asignacionId: window.Asignacion?.id ?? null,
      asignacionRV: asigRV,
      vehiculo: { ...vehiculo },
      transportista: { ...transportista },
      destinatarioNombre,
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



    // Modal informativo
    window.ModalCUS24?.show?.({
      title: "Guía generada",
      body: `Se emitió la Guía Nº <b>${nro}</b>.`,
      okText: "Aceptar",
    });


    // Reset UI de ítems
    window.ItemsProductos?.limpiar?.();

    // === Fallback sin API de recarga ===
    // Remueve de la grilla las OP usadas (para mostrar disponibles)
    window.Pedidos?.removeRowsByOps?.(ops);

    // Deshabilita Generar hasta nueva selección
    document.getElementById("btnGenerar")?.setAttribute("disabled", "disabled");

    // Modal adicional (opcional)
    showModal({
      title: "Guía de Remisión generada",
      message: `Se generó la Guía Nº ${r.guiaNumero || nro}.`,
      okText: "Entendido",
      onOk: () => {
        window.ItemsProductos?.limpiar?.();
        document.getElementById("btnGenerar")?.setAttribute("disabled", "disabled");
      },
    });

    // Abre guía HTML directamente (con autoprint)
    const gid = r.guiaId;
    if (gid) {
      const urlHTML = `../../Controlador/ControladorGuiaHTML.php?id=${encodeURIComponent(
        gid
      )}&autoprint=1`;
      window.open(urlHTML, "_blank");
    }

    // Recalcular estados por si quedó algo compatible pendiente
    window.SalidaCUS24?.updateGenerarHabilitado?.();
  }

  function init() {
    document
      .getElementById("btnGenerar")
      ?.addEventListener("click", onGenerarSalida);
  }
  document.addEventListener("DOMContentLoaded", init);

  // Exponer util para otros módulos
  window.SalidaCUS24 = Object.assign(window.SalidaCUS24 || {}, {
    updateGenerarHabilitado,
  });
})();
