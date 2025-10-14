// /Vista/Script/CUS24/asignacion.js
(function () {
  const $ = (sel, ctx = document) => ctx.querySelector(sel);

  /* ============ Helpers ============ */
  function normDir(s) {
    return String(s || "")
      .toLowerCase()
      .normalize("NFKC")
      .replace(/\s+/g, " ")
      .trim();
  }

  function setBtnGenerarEnabled(enabled) {
    const b = $("#btnGenerar");
    if (b) b.disabled = !enabled;
  }

  function setResumenGruposText(groups = []) {
    const box = $("#gruposResumen");
    if (!box) return;
    if (!groups.length) {
      box.textContent = "No se detectaron grupos de destino.";
      return;
    }
    box.textContent = `Se detectaron ${groups.length} grupo(s) de destino. Se generará una guía por cada grupo.`;
  }

  /* ============ Estado global mínimo ============ */
  // Nota: no guardamos ítems ni "dirección activa".
  window.Asignacion = window.Asignacion || {
    id: null, // t40
    idAsignacionRV: null, // t79
    grupos: [], // [{ key, dni, nombre, dir, distritoId, distritoNombre, ops: [idOP], clientes: Set }]
  };

  /* ============ Limpieza / Pintado base ============ */
  function limpiarUI() {
    // Campos repartidor/unidad
    [
      "#repNombre",
      "#repApePat",
      "#repApeMat",
      "#repTel",
      "#repEmail",
      "#guiaDni",
      "#guiaLic",
      "#guiaConductor",
      "#vehMarca",
      "#vehPlaca",
      "#vehModelo",
      "#msgPedidos",
    ].forEach((sel) => {
      const el = $(sel);
      if (el) el.value = "";
    });

    // Banner correo
    const mail = $("#repEmailView");
    if (mail) mail.textContent = "—";

    // Pedidos
    const tb = $("#tblPedidos tbody");
    if (tb) tb.innerHTML = "";

    // Previsualización de grupos
    const grupos = $("#gruposLista");
    if (grupos) grupos.innerHTML = "";
    setResumenGruposText([]);

    // Mensajería
    const msgAsig = $("#msgAsignacion");
    if (msgAsig) msgAsig.textContent = "";

    // Botones
    setBtnGenerarEnabled(false);

    // Estado global
    window.Asignacion.id = null;
    window.Asignacion.idAsignacionRV = null;
    window.Asignacion.grupos = [];
  }

  function pintarEncabezado(data) {
    const r = data.repartidor || {};
    const v = data.vehiculo || {};
    const asig = data.asignacion || {};

    // Datos del repartidor
    $("#repNombre") && ($("#repNombre").value = r.nombre || "");
    $("#repApePat") && ($("#repApePat").value = r.apePat || "");
    $("#repApeMat") && ($("#repApeMat").value = r.apeMat || "");
    $("#repTel") && ($("#repTel").value = r.telefono || "");
    $("#repEmail") && ($("#repEmail").value = r.email || "");

    // Banner correo
    const mailView = $("#repEmailView");
    if (mailView) mailView.textContent = r.email || "—";

    // Licencia / DNI / Conductor (snapshot)
    const licCompat =
      r.licenciaInfo?.numero ?? r.licencia ?? r.licenciaConducir ?? "";
    const fullName = [r.nombre, r.apePat, r.apeMat]
      .filter(Boolean)
      .join(" ")
      .trim();

    $("#guiaDni") && ($("#guiaDni").value = r.dni || "");
    $("#guiaLic") && ($("#guiaLic").value = licCompat);
    $("#guiaConductor") && ($("#guiaConductor").value = fullName);

    // Vehículo
    $("#vehMarca") && ($("#vehMarca").value = v.marca || "");
    $("#vehPlaca") && ($("#vehPlaca").value = v.placa || "");
    $("#vehModelo") && ($("#vehModelo").value = v.modelo || "");

    // Estado global
    window.Asignacion.id = asig.id || null;
    window.Asignacion.idAsignacionRV = asig.idAsignacionRV || null;

    // Snapshot opcional
    window.GUIA = {
      transportista: {
        dni: r.dni || "",
        licencia: licCompat || "",
        conductor: fullName || "",
        estadoLicencia: r.licenciaInfo?.estado ?? null,
      },
      vehiculo: {
        marca: v.marca || "",
        placa: v.placa || "",
        modelo: v.modelo || "",
      },
    };
  }

  /* ============ Pedidos (Tabla) ============ */
  function pintarPedidos(pedidos = []) {
    const tb = $("#tblPedidos tbody");
    if (!tb) return;
    tb.innerHTML = "";

    const frag = document.createDocumentFragment();

    pedidos.forEach((p) => {
      const op = Number(p.idOrdenPedido || p.op || 0);
      const estado = String(p.estadoOP || p.estado || "").trim();
      const cliente = p.cliente || p.clienteNombre || "";
      const dir = p.direccion || p.direccionSnap || "";
      const dist = p.distritoNombre || p.distrito || "";
      const ose = p.idOSE || p.ose || "";

      const elegible = estado === "Pagado";

      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td data-label="Código Orden Pedido">${op || ""}</td>
        <td data-label="Cliente">${cliente}</td>
        <td data-label="Dirección">${dir}</td>
        <td data-label="Distrito">${dist}</td>
        <td data-label="OSE">${ose}</td>
        <td data-label="Estado">${estado || ""}</td>
        <td class="incluida-cell" data-label="Incluida">${
          elegible
            ? `<input type="checkbox" class="chk-incluida" checked disabled>`
            : "—"
        }</td>
      `;
      frag.appendChild(tr);
    });

    tb.appendChild(frag);
  }

  /* ============ Agrupación por destino ============ */
  function agruparPorDestino(pedidos = []) {
    // Sólo OP elegibles (Pagado)
    const elegibles = pedidos.filter(
      (p) => String(p.estadoOP || p.estado || "").trim() === "Pagado"
    );

    const map = new Map();
    for (const p of elegibles) {
      const dni = String(p.receptorDni || "").trim();
      const dirRaw = String(p.direccion || p.direccionSnap || "").trim();
      const dirN = normDir(dirRaw);
      const distritoId = p.distritoId ?? null;
      const distritoNombre = p.distritoNombre || p.distrito || "";
      const op = Number(p.idOrdenPedido || p.op || 0);
      const cliente = p.cliente || p.clienteNombre || "";

      // clave del grupo
      const key = `${dni}||${dirN}||${distritoId ?? distritoNombre}`;

      if (!map.has(key)) {
        map.set(key, {
          key,
          dni,
          nombre: p.receptorNombre || "", // si no viene, quedará vacío
          dir: dirRaw,
          dirNorm: dirN,
          distritoId,
          distritoNombre,
          ops: [],
          clientes: new Set(),
        });
      }
      const g = map.get(key);
      if (op) g.ops.push(op);
      if (cliente) g.clientes.add(cliente);
      // Si llega vacío el nombre, no sobre-escribimos
      if (!g.nombre && p.receptorNombre) g.nombre = p.receptorNombre;
    }

    // Convertimos Set clientes a string breve
    const groups = Array.from(map.values()).map((g) => ({
      ...g,
      clientesTexto:
        g.clientes.size > 3
          ? Array.from(g.clientes).slice(0, 3).join(", ") + "…"
          : Array.from(g.clientes).join(", "),
    }));

    return groups;
  }

  // Helpers de mapeo (reutilizan tu lógica previa)
  function pickCodigo(row) {
    return (
      row.codigo ??
      row.idProducto ??
      row.codProducto ??
      row.Id_Producto ??
      row.t18CatalogoProducto_Id_Producto ??
      ""
    );
  }
  function pickDescripcion(row) {
    return (
      row.descripcion ??
      row.desc ??
      row.producto ??
      row.nombreProducto ??
      row.NombreProducto ??
      ""
    );
  }
  function pickUM(row) {
    // Si en items-por-orden no viene UM, puedes dejar vacío o "NIU"
    return row.unidad ?? row.um ?? row.Unidad ?? row.UnidadMedida ?? "";
  }
  function pickCantidad(row) {
    const n = Number(row.cantidad ?? row.cant ?? row.Cantidad ?? 0);
    return Number.isFinite(n) ? n : 0;
  }

  async function cargarDetalleGrupo(key, bodyEl) {
    // 1) localizar grupo por key
    const grp = (window.Asignacion?.grupos || []).find((g) => g.key === key);
    if (!grp) throw new Error("Grupo no encontrado");

    const loading = bodyEl.querySelector(".group-loading");
    const content = bodyEl.querySelector(".group-content");
    const itemsTbody = bodyEl.querySelector(".group-items");
    const opsValues = bodyEl.querySelector(".ops-values");
    const warnings = bodyEl.querySelector(".warnings");

    // 2) acumulador por producto
    const map = new Map(); // codigo -> { codigo, desc, um, qty }

    // 3) fetch items por cada OP del grupo (secuencial simple; si quieres, paraleliza con Promise.all)
    for (const op of grp.ops) {
      let res;
      try {
        res = await window.API24.fetchJSON(window.API24.url.itemsPorOrden(op), {
          method: "GET",
          credentials: "include",
        });
      } catch (e) {
        // Si falla una OP, avisamos y continuamos (o lanza para abortar todo, a tu criterio)
        window.Utils24?.showToast?.(
          `No se pudo cargar ítems de la OP ${op}.`,
          "warn"
        );
        continue;
      }
      if (!res?.ok) {
        window.Utils24?.showToast?.(
          `Error al obtener ítems de la OP ${op}.`,
          "warn"
        );
        continue;
      }

      const items = Array.isArray(res.items) ? res.items : [];
      for (const r of items) {
        const codigo = pickCodigo(r);
        if (!codigo && codigo !== 0) continue;
        const desc = pickDescripcion(r);
        const um = pickUM(r);
        const qty = pickCantidad(r);

        if (!map.has(codigo)) {
          map.set(codigo, { codigo, desc, um, qty });
        } else {
          const n = map.get(codigo);
          n.qty += qty;
          // si quieres, unifica um si viene vacía
          if (!n.um && um) n.um = um;
        }
      }
    }

    // 4) pintar tabla consolidada
    if (itemsTbody) {
      itemsTbody.innerHTML = "";
      const rows = Array.from(map.values()).sort((a, b) =>
        String(a.codigo).localeCompare(String(b.codigo))
      );

      if (rows.length === 0) {
        itemsTbody.innerHTML = `<tr><td colspan="4" style="text-align:center;opacity:.7;">Sin ítems</td></tr>`;
      } else {
        const frag = document.createDocumentFragment();
        for (const it of rows) {
          const tr = document.createElement("tr");
          tr.innerHTML = `
          <td>${it.codigo ?? ""}</td>
          <td>${it.desc ?? ""}</td>
          <td>${it.um ?? ""}</td>
          <td>${it.qty}</td>
        `;
          frag.appendChild(tr);
        }
        itemsTbody.appendChild(frag);
      }
    }

    // 5) lista de OP incluidas
    if (opsValues) opsValues.textContent = grp.ops.join(", ");

    // 6) advertencias (ejemplo: UM vacías)
    const umVacias = Array.from(map.values()).filter((x) => !x.um).length;
    warnings.innerHTML = umVacias
      ? `<div class="badge warn">Advertencia</div> ${umVacias} producto(s) sin unidad definida; se aplicará mapeo/truncado al generar.`
      : "";

    // 7) mostrar contenido y ocultar “cargando”
    if (loading) loading.setAttribute("hidden", "hidden");
    if (content) content.removeAttribute("hidden");
  }

  /* ============ Render de grupos (pre-visualización) ============ */
  function renderGrupos(groups = []) {
    const wrap = $("#gruposLista");
    if (!wrap) return;
    wrap.innerHTML = "";

    if (!groups.length) {
      setResumenGruposText([]);
      setBtnGenerarEnabled(false);
      return;
    }

    setResumenGruposText(groups);

    const frag = document.createDocumentFragment();

    for (const g of groups) {
      const card = document.createElement("article");
      card.className = "group-card";
      card.innerHTML = `
  <header class="group-head">
    <div class="destino">
      <div><b>DNI:</b> ${g.dni || "—"}</div>
      <div><b>Nombre:</b> ${g.nombre || "—"}</div>
      <div><b>Dirección:</b> ${g.dir || "—"}</div>
      <div><b>Distrito:</b> ${g.distritoNombre || "—"}</div>
    </div>
    <div class="resumen">
      <span>#OP: ${g.ops.length}</span>
    </div>
    <div class="estado"><span class="badge ok">Listo</span></div>
    <button class="btn btn-ghost btn-sm group-toggle" type="button" data-key="${
      g.key
    }">
      Ver detalle
    </button>
  </header>
  <section class="group-body" data-key="${g.key}" hidden>
    <div class="group-loading" style="font-size:13px;opacity:.75;">Cargando detalle…</div>
    <div class="group-content" hidden>
      <h4>Consolidado de productos</h4>
      <table class="table table-compact">
        <thead>
          <tr><th>Código</th><th>Descripción</th><th>UM</th><th>Cantidad</th></tr>
        </thead>
        <tbody class="group-items"></tbody>
      </table>
      <div class="ops-list">
        <b>Órdenes incluidas:</b> <span class="ops-values"></span>
      </div>
      <div class="warnings"></div>
    </div>
  </section>
`;
      frag.appendChild(card);
    }

    wrap.appendChild(frag);

    // Habilitado del botón: al menos 1 grupo + email de repartidor presente
    const emailRep = ($("#repEmail") && $("#repEmail").value) || "";
    const enable = groups.length > 0 && !!emailRep;
    setBtnGenerarEnabled(enable);
  }

  function todayLocalISO() {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${y}-${m}-${day}`; // YYYY-MM-DD
  }
  function onlyDate(s) {
    return String(s || "")
      .trim()
      .slice(0, 10); // toma solo 'YYYY-MM-DD'
  }

  /* ============ Buscar Asignación ============ */
  async function buscarAsignacion() {
    const { validateNumericInput, showMsg, showToast, $ } =
      window.Utils24 || {};
    const input = $("#txtAsignacion");
    const msgEl = $("#msgAsignacion");

    // Validación
    const v = validateNumericInput
      ? validateNumericInput(input, msgEl, { required: true })
      : { ok: !!(input && /^\d+$/.test(input.value.trim())) };

    if (!v.ok) {
      input?.focus();
      showToast?.("Ingrese un número de asignación válido.", "error");
      return;
    }

    const btn = $("#btnBuscar");
    if (btn) btn.disabled = true;
    if (input) input.disabled = true;

    if (!window.API24?.fetchJSON || !window.API24?.url?.buscarAsignacion) {
      if (btn) btn.disabled = false;
      if (input) input.disabled = false;
      showMsg?.(msgEl, "error", "API no disponible.", { autoclear: 3500 });
      showToast?.("API no disponible", "error");
      return;
    }

    limpiarUI();
    //showToast?.("Buscando asignación…", "info");

    let res;
    try {
      res = await window.API24.fetchJSON(
        window.API24.url.buscarAsignacion(input.value.trim()),
        { method: "GET", credentials: "include" }
      );
    } catch {
      if (btn) btn.disabled = false;
      if (input) input.disabled = false;
      showMsg?.(msgEl, "error", "No se pudo conectar al servidor.", {
        autoclear: 4000,
      });
      return;
    }

    if (btn) btn.disabled = false;
    if (input) input.disabled = false;

    if (!res || res.ok !== true) {
      const mensaje =
        typeof res?.error === "string" && res.error.trim()
          ? res.error
          : "Asignación no encontrada.";
      //showMsg?.(msgEl, "error", mensaje, { autoclear: 4000 });
      showToast?.(mensaje, "error");
      limpiarUI();
      return;
    }

    // 1) Encabezado (repartidor / unidad / ids)
    pintarEncabezado(res);

    const pedidos = Array.isArray(res.pedidos) ? res.pedidos : [];
    const tot = pedidos.length;

    // (A) Sin pedidos => mensaje y fin
    if (tot === 0) {
      // limpiar vista de pedidos/grupos por si acaso
      const tb = document.querySelector("#tblPedidos tbody");
      if (tb) tb.innerHTML = "";
      const gruposWrap = document.querySelector("#gruposLista");
      if (gruposWrap) gruposWrap.innerHTML = "";
      setResumenGruposText([]);
      window.Asignacion.grupos = [];

      const msg = document.querySelector("#msgPedidos");
      if (msg) {
        msg.classList.add("hint");
        msg.textContent = "No hay pedidos pendientes en esta asignación.";
      }
      window.Utils24?.showToast?.(
        "No hay pedidos pendientes en esta asignación.",
        "info"
      );
      setBtnGenerarEnabled(false);
      return;
    }

    // (B) Hay pedidos => verifica fecha programada
    const fp = onlyDate(res?.asignacion?.fechaProgramada);
    const hoy = todayLocalISO();
    if (fp && fp !== hoy) {
      // Deshabilitar y limpiar
      setBtnGenerarEnabled(false);
      const tb = document.querySelector("#tblPedidos tbody");
      if (tb) tb.innerHTML = "";
      const gruposWrap = document.querySelector("#gruposLista");
      if (gruposWrap) gruposWrap.innerHTML = "";
      setResumenGruposText([]);
      window.Asignacion.grupos = [];

      const msg = document.querySelector("#msgPedidos");
      if (msg) {
        msg.classList.add("hint");
        msg.textContent = `No se puede atender: la asignación está programada para ${fp}.`;
      }
      window.Utils24?.showToast?.(
        `No se puede atender: la asignación está programada para ${fp} (hoy ${hoy}).`,
        "error"
      );
      return;
    }

    // (C) OK: hay pedidos y la fecha es hoy -> continúa flujo normal
    pintarPedidos(pedidos);
    const grupos = agruparPorDestino(pedidos);
    window.Asignacion.grupos = grupos;
    renderGrupos(grupos);

    // Mensaje + toast de éxito
    const msg = document.querySelector("#msgPedidos");
    if (msg) {
      msg.classList.add("hint");
      msg.textContent = `Se encontraron ${tot} pedido(s). Se procesarán automáticamente.`;
    }
    window.Utils24?.showToast?.(
      `Asignación #${input?.value?.trim() || ""} cargada (${tot} pedido${
        tot === 1 ? "" : "s"
      }).`,
      "success" // usa "ok" para coincidir con tus variantes CSS
    );
  }

  /* ============ Init ============ */
  function init() {
    // Validación ligera + enter para buscar
    if (window.Utils24?.bindNumericValidation) {
      window.Utils24.bindNumericValidation("#txtAsignacion", "#msgAsignacion", {
        required: true,
        btn: "#btnBuscar",
        validateOn: "input",
        requiredOnInput: false,
        requiredOnBlur: false,
        onValid: buscarAsignacion,
      });
    } else {
      $("#txtAsignacion")?.addEventListener("keydown", (e) => {
        if (e.key === "Enter") buscarAsignacion();
      });
    }

    $("#btnBuscar")?.addEventListener("click", buscarAsignacion);

    // El botón Generar se habilita cuando hay grupos y correo
    setBtnGenerarEnabled(false);

    // Delegación en la lista de grupos
    $("#gruposLista")?.addEventListener("click", async (e) => {
      const btn = e.target.closest(".group-toggle");
      if (!btn) return;

      const key = btn.dataset.key;
      const body = document.querySelector(
        `.group-body[data-key="${CSS.escape(key)}"]`
      );
      if (!body) return;

      const isHidden = body.hasAttribute("hidden");
      if (isHidden) {
        // Primer expand: si no está cacheado, cargar
        if (!body.dataset.loaded) {
          try {
            await cargarDetalleGrupo(key, body);
            body.dataset.loaded = "1";
          } catch (err) {
            window.Utils24?.showToast?.(
              "No se pudo cargar el detalle del grupo.",
              "error"
            );
          }
        }
        body.removeAttribute("hidden");
        btn.textContent = "Ocultar detalle";
      } else {
        body.setAttribute("hidden", "hidden");
        btn.textContent = "Ver detalle";
      }
    });
  }

  document.addEventListener("DOMContentLoaded", init);

  // API pública
  window.Asignacion = Object.assign(window.Asignacion || {}, {
    buscar: buscarAsignacion,
    pintarEncabezado,
    limpiarUI,
  });
})();
