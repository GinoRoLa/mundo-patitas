"use strict";
let fechaActual = document.getElementById("hoy");
window.SERVICIOURL = "http://localhost/ServicioWebTaller/";

const colocarFechaActual = () => {
    const hoy = new Date().toISOString().split("T")[0];
    fechaActual.value = hoy;
};

/*
 Paso 1 Buscar al cliente por DNI
 */

let dniClienteGlobal = null;

const dniCliente = document.getElementById("dniCliente");
const btnBuscar = document.getElementById("btnBuscar");

const inpNombre = document.getElementById("nombre");
const inpApPaterno = document.getElementById("apPaterno");
const inpApMaterno = document.getElementById("apMaterno");
const inpTelefono = document.getElementById("telefono");
const inpEmail = document.getElementById("email");
const inpDireccion = document.getElementById("direccion");


const state = {nombreCliente: ""};

const global = new Proxy(state, {
    set(obj, prop, value) {
        obj[prop] = value; // se asigna normalmente
        if (prop === "nombreCliente") {
            console.log("Se actualizó el nombre:", value);
            actualizarResumenCliente(); // aquí llamas tu función
        }
        return true;
    }
});


const buscarCliente = async (dni) => {

    const dniValue = String(dni).trim();

    if (!/^\d{8}$/.test(dniValue)) {
        Swal.fire({
            icon: 'warning',
            title: 'DNI inválido',
            text: 'El DNI debe tener exactamente 8 dígitos.'
        });
        return null;
    }

    try {
        const response = await
                fetch(`${window.SERVICIOURL}/CUS03/obtenerDatoDni.php?dniCliente=${encodeURIComponent(dniValue)}`);

        if (!response.ok)
            throw new Error(`Error en la respuesta del servidor ${response.status}`);

        const data = await response.json();
        const cliente = Array.isArray(data) ? data[0] : data;

        if (cliente) {
            inpNombre.value = cliente.des_nombreCliente ?? "";
            inpApPaterno.value = cliente.des_apepatCliente ?? "";
            inpApMaterno.value = cliente.des_apematCliente ?? "";
            inpTelefono.value = cliente.num_telefonoCliente ?? "";
            inpEmail.value = cliente.email_cliente ?? "";
            inpDireccion.value = cliente.direccionCliente ?? "";


            global.nombreCliente = `${cliente.des_nombreCliente ?? ""} ${cliente.des_apepatCliente ?? ""} ${cliente.des_apematCliente ?? ""}`.trim();

            return cliente;
        } else {
            // Limpiar campos si no hay resultados
            inpNombre.value = inpApPaterno.value = inpApMaterno.value = "";
            inpTelefono.value = inpEmail.value = inpDireccion.value = "";
            global.nombreCliente = "";

            Swal.fire({
                icon: 'error',
                title: 'No encontrado',
                text: 'No se encontró ningún cliente con ese DNI.'
            });

            return null;
        }
    } catch (error) {
        console.error("Error al buscar cliente:", error);

        inpNombre.value = inpApPaterno.value = inpApMaterno.value = "";
        inpTelefono.value = inpEmail.value = inpDireccion.value = "";

        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrio un error al buscar al cliente.'
        });
        return null;
    }
}

btnBuscar.addEventListener("click", async () => {
    dniClienteGlobal = dniCliente.value.trim();
    try {
        const cliente = await buscarCliente(dniClienteGlobal);
        if (cliente) {
            listaOrdenesPedido();
        }
    } catch (err) {
        console.log("Error en la búsqueda:", err.message);
    }
});


/*
 Paso 2 Listar las órdenes de pedido del cliente
 */

const tableListaOrdenesPedidoCliente = document.getElementById("tbody-ordenes");
const alertaOrdenVacio = document.getElementById("alerta-orden-pedido-vacio");

const formatMoney = (n) => `S/ ${Number(n ?? 0).toFixed(2)}`;

const renderDetalleOrden = (ordenId, items, extra) => {
    const filas = (items || []).map(it => {
        const total = Number(it.cantidad) * Number(it.PrecioUnitario);
        return `
      <tr>
        <td>${it.NombreProducto}</td>
        <td class="text-end">${it.cantidad}</td>
        <td class="text-end">${formatMoney(it.PrecioUnitario)}</td>
        <td class="text-end">${formatMoney(total)}</td>
      </tr>
    `;
    }).join("");

    return `
    <div class="p-3 detalle-pedido">
      <h6 class="fw-semibold mb-3">Detalles del pedido #${ordenId}</h6>

      <div class="table-responsive mb-3">
        <table class="table table-sm table-striped align-middle mb-0">
          <thead>
            <tr>
              <th>Producto</th>
              <th class="text-end">Cant.</th>
              <th class="text-end">Precio</th>
              <th class="text-end">Total</th>
            </tr>
          </thead>
          <tbody>
            ${filas || `<tr><td colspan="4" class="text-muted">Sin detalle.</td></tr>`}
          </tbody>
        </table>
      </div>

      <div class="row g-2">
        <div class="col-12 col-md-4">
          <div class="p-2 bg-light border rounded">
            <span class="text-muted">Método:</span>
            <strong>${extra?.MetodoEntrega ?? '-'}</strong>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="p-2 bg-light border rounded">
            <span class="text-muted">Envío:</span>
            <strong>${formatMoney(extra?.CostoEnvio)}</strong>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="p-2 bg-light border rounded">
            <span class="text-muted">Descuento:</span>
            <strong>- ${formatMoney(extra?.DescuentoTotal)}</strong>
          </div>
        </div>
      </div>
    </div>
  `;
};

/*Recibe la Orden de pedido y el contenedor. Hace dos peticiones para obtener los detalles del pedido y los procesa*/
const cargarDetalleOrdenPedido = async (ordenId, contenedor) => {
    if (contenedor.dataset.loaded === "1")
        return;

    const urlProd = `${window.SERVICIOURL}CUS03/obtenerProductosOrdenPedido.php?ordenPedido=${ordenId}`;
    const urlExtra = `${window.SERVICIOURL}CUS03/obtenerDatosExtraOrdenPedido.php?ordenPedido=${ordenId}`;

    contenedor.innerHTML = `<div class="p-3 text-muted">Cargando detalle...</div>`;

    try {
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 10000);

        const [itemsRes, extrasRes] = await Promise.allSettled([
            fetch(urlProd, {signal: controller.signal})
                    .then(r => {
                        if (!r.ok)
                            throw new Error(`HTTP ${r.status}`);
                        return r.json();
                    }),
            fetch(urlExtra, {signal: controller.signal})
                    .then(r => {
                        if (!r.ok)
                            throw new Error(`HTTP ${r.status}`);
                        return r.json();
                    })
        ]);

        clearTimeout(timer);

        const items = itemsRes.status === "fulfilled" ? (Array.isArray(itemsRes.value) ? itemsRes.value : []) : [];
        const extras = extrasRes.status === "fulfilled" ? extrasRes.value : null;
        const extra = Array.isArray(extras) ? extras[0] : extras;

        if (itemsRes.status !== "fulfilled") {
            console.warn("[detalle items] no llegó:", itemsRes.reason);
        }
        if (extrasRes.status !== "fulfilled") {
            console.warn("[detalle extra] no llegó:", extrasRes.reason);
        }

        contenedor.innerHTML = renderDetalleOrden(ordenId, items, extra);
        contenedor.dataset.loaded = "1";

    } catch (err) {
        console.error("cargarDetalleOrdenPedido error:", err);
        contenedor.innerHTML = `<div class="p-3 text-danger">No se pudo cargar el detalle.</div>`;
    }
};

const crearFilaOrdenPedido = (ordenPedido) => {
    const fila = document.createElement("div");

    fila.className = "row border-bottom py-2 align-items-center hover bg-white";
    fila.setAttribute("data-bs-toggle", "collapse");
    fila.setAttribute("data-bs-target", `#detalle-${ordenPedido.Id_OrdenPedido}`);
    fila.setAttribute("role", "button");
    fila.setAttribute("aria-expanded", "false");

    fila.innerHTML = `
                        <div class="col-2 text-center">${ordenPedido.Id_OrdenPedido}</div>
                        <div class="col-4 text-center">${ordenPedido.Fecha}</div>
                        <div class="col-2 text-center">${Number(ordenPedido.Total).toFixed(2)}</div>
                        <div class="col-3 text-center">${ordenPedido.estado}</div>
                        <div class="col-1 text-center">
                            <button class="btn btn-success btn-sm btn-agregar" 
                                  data-id="${ordenPedido.Id_OrdenPedido}"
                                  data-total="${Number(ordenPedido.Total) || 0}" 
                                  title="Agregar">
                                  Agregar
                            </button>
                        </div>

                        <!-- Contenedor del collapse para el detalle -->
                        <div class="col-12 p-0">
                            <div id="detalle-${ordenPedido.Id_OrdenPedido}" class="collapse border-top" data-bs-parent="#tbody-ordenes">
                                <div class="p-3 text-muted">Cargando detalle...</div>
                            </div>
                        </div>
                `;

    const btn = fila.querySelector(".btn-agregar");

    const id = Number(ordenPedido.Id_OrdenPedido);
    if (seleccion.has(id)) {
        setBtnEliminar(btn); // rojo, "Eliminar"
    } else {
        setBtnAgregar(btn);  // verde, "Agregar"
    }
    return fila;
}

const pintarOrdenes = (lista) => {
    tableListaOrdenesPedidoCliente.innerHTML = "";

    if (!Array.isArray(lista) || lista.length === 0) {
        alertaOrdenVacio.textContent = "El cliente no tiene órdenes activas.";
        alertaOrdenVacio.classList.remove("d-none");

        return;
    }
    alertaOrdenVacio.classList.add("d-none");

    const frag = document.createDocumentFragment();
    lista.forEach((op) => frag.appendChild(crearFilaOrdenPedido(op)));
    tableListaOrdenesPedidoCliente.appendChild(frag);

}

const listaOrdenesPedido = () => {
    if (!dniClienteGlobal) {
        console.log("No hay cliente seleccionado en la orden de pedido.");
        pintarOrdenes([]);
        return;
    }
    fetch(`${window.SERVICIOURL}/CUS03/obtenerOrdenPedidoCliente.php?clienteElegido=${dniClienteGlobal}`)
            .then(r => {
                if (!r.ok)
                    throw new Error("Error en la respuesta del servidor");
                return r.json();
            })
            .then(data => {
                console.log("Órdenes de pedido recibidas:", data);
                pintarOrdenes(data);
            })
            .catch(err => {
                console.error("Error al obtener preórdenes:", err);
                alertaOrdenVacio.textContent = "No se pudieron cargar las órdenes. Intenta nuevamente.";
                alertaOrdenVacio.classList.remove("d-none");
            });
};

/*
 Paso 3 Mostrar detalle de la orden de pedido
 */
const seleccion = new Map();

const getSeleccionIds = () => Array.from(seleccion.keys());

const getSeleccionJSON = () => Array.from(seleccion.values());

const limpiarSeleccion = () => {
    seleccion.clear();
    actualizarTotalGeneral();
};

const $total = document.getElementById('total-pedido');

const $montoBruto = document.getElementById('montoBruto');

const actualizarTotalGeneral = () => {
    let suma = 0;
    for (const {total} of seleccion.values()) {
        suma += Number(total) || 0;
    }
    $total.textContent = suma.toFixed(2);

    if (typeof window.setMontoBruto === "function") {
        window.setMontoBruto(suma);
    } else {
        const montoBruto = document.getElementById('montoBruto');
        if (montoBruto) {
            montoBruto.textContent = suma.toFixed(2);
        }
    }

    aplicarVisibilidad();
};

const setBtnAgregar = (btn) => {
    btn.classList.remove("btn-danger");
    btn.classList.add("btn-success");
    btn.textContent = "Agregar";
    btn.title = "Agregar";
    btn.dataset.selected = "0";
};

const setBtnEliminar = (btn) => {
    btn.classList.remove("btn-success");
    btn.classList.add("btn-danger");
    btn.textContent = "Eliminar";
    btn.title = "Eliminar";
    btn.dataset.selected = "1";
};

const toggleOrdenSeleccion = (id, total, btn) => {
    if (seleccion.has(id)) {
        seleccion.delete(id);
        setBtnAgregar(btn);   // botón verde "Agregar"
    } else {
        seleccion.set(id, {id, total});
        setBtnEliminar(btn);  // botón rojo "Eliminar"
    }
    actualizarTotalGeneral();
    console.log("Selección:", getSeleccionJSON());

};

const tbodyOrdenes = document.getElementById("tbody-ordenes");

tbodyOrdenes.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-agregar");
    if (!btn)
        return;
    e.stopPropagation();

    const id = Number(btn.dataset.id);
    const total = Number(btn.dataset.total || 0);

    if (Number.isNaN(id))
        return;

    toggleOrdenSeleccion(id, total, btn);
});

tbodyOrdenes.addEventListener("shown.bs.collapse", (e) => {
    const contenedor = e.target;
    const id = contenedor.id?.replace(/^detalle-/, "");
    if (!id)
        return;

    if (contenedor.dataset.loaded === "1")
        return;

    cargarDetalleOrdenPedido(id, contenedor);
});

/*Validacion antes de mostrar Nota de credito / Efectuar pago */
const sectionNotaCredito = document.getElementById("nota-credito-section");
const sectionPago = document.getElementById("pago-section");
const THRESHOLD = 1;

const obtenerTotal = () => {
    return parseFloat($total.textContent) || 0;
};

const aplicarVisibilidad = () => {
    const total = obtenerTotal();
    if (total > THRESHOLD) {
        sectionNotaCredito.classList.remove("d-none");
        sectionPago.classList.remove("d-none");
    } else {
        sectionNotaCredito.classList.add("d-none");
        sectionPago.classList.add("d-none");
    }
}

/*Array que obtiene los id del mapa */
let claves = [];

const obtenerIdsSeleccionados = () => {
    return claves = [...seleccion.keys()];
};

/*Obtener el método de pago */
let metodoPagoGlobal = null;

const obtenerMetodoPago = () => {
    const activeBtn = document.querySelector('#pagoNav .nav-link.active');

    const val = activeBtn?.dataset?.metodoPago
            ?? activeBtn?.dataset?.metodopago
            ?? activeBtn?.getAttribute('data-metodo-pago')
            ?? activeBtn?.getAttribute('data-metodoPago')
            ?? "1";
    return Number(val);
}

/*Obtener el comprobante de pago */
let modoComprobantePagoGlobal = null;

const obtenerModoComprobantePago = () => {
    const checkedBtn = document.querySelector('input[name="tipoComprobante"]:checked');
    if (!checkedBtn)
        return null;

    const label = document.querySelector(`label[for="${checkedBtn.id}"]`);

    modoComprobantePagoGlobal = label.dataset.metodoComprobantepago;
    return modoComprobantePagoGlobal;
}

document.querySelectorAll('input[name="tipoComprobante"]').forEach(radio => {
    radio.addEventListener('change', () => {

        obtenerModoComprobantePago();
        console.log("Método comprobante de pago:", modoComprobantePagoGlobal);
    });
});


document.addEventListener('DOMContentLoaded', () => {
    console.log('Método de pago inicial:', obtenerMetodoPago());

    console.log("Tipo de comprobante inicial:", obtenerModoComprobantePago());

    const nav = document.getElementById('pagoNav');
    nav.addEventListener('shown.bs.tab', (e) => {
        const nuevo = e.target.dataset.metodoPago
                ?? e.target.dataset.metodopago
                ?? e.target.getAttribute('data-metodo-pago')
                ?? e.target.getAttribute('data-metodoPago');

        metodoPagoGlobal = Number(nuevo) || 1;
        console.log('Método de pago cambiado:', metodoPagoGlobal);
    });
});


// ===================== Nota de Crédito =====================
const notaCreditoInput = document.getElementById("notaCredito");
const btnBuscarNotaCredito = document.getElementById("btnBuscarNotaCredito");
let notaCreditoData = null;

const validarNotaCredito = (valor) => /^\d{13}$/.test(String(valor).trim());


const toggleBtnLoading = (btn, loading = false) => {
    if (!btn)
        return;
    if (loading) {
        btn.disabled = true;
        btn.dataset._label = btn.innerHTML;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Buscando...`;
    } else {
        btn.disabled = false;
        if (btn.dataset._label)
            btn.innerHTML = btn.dataset._label;
}
};


const buscarNotaCredito = async (numero) => {
    const nc = String(numero).trim();

    if (!validarNotaCredito(nc)) {
        Swal.fire({
            icon: "warning",
            title: "Número inválido",
            text: "La Nota de crédito debe tener exactamente 13 dígitos."
        });
        return null;
    }
    const url = `${window.SERVICIOURL}CUS03/obtenerNotaCredito.php?notaCredito=${encodeURIComponent(nc)}`;

    try {
        const resp = await fetch(url, {headers: {"Accept": "application/json"}});
        if (!resp.ok)
            throw new Error(`HTTP ${resp.status}`);

        const data = await resp.json();
        const item = Array.isArray(data) && data.length > 0 ? data[0] : null;

        if (item) {
            notaCreditoData = item;
            console.log("Nota de crédito encontrada:", notaCreditoData);

            setDescuentoNotaCredito(item.Total);

            Swal.fire({
                icon: "success",
                title: "Nota encontrada",
                text: `Total: S/ ${item.Total}`
            });

            return item;
        } else {
            setDescuentoNotaCredito(0);
            Swal.fire({
                icon: "info",
                title: "No encontrada",
                text: "No se encontró ninguna nota de crédito con ese número."
            });
            return null;
        }
    } catch (err) {
        console.error("Error al consultar Nota de crédito:", err);
        notaCreditoData = null;
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Ocurrió un error al consultar la Nota de crédito."
        });
        return null;
    }


}

btnBuscarNotaCredito.addEventListener("click", async () => {
    toggleBtnLoading(btnBuscarNotaCredito, true);
    try {
        await buscarNotaCredito(notaCreditoInput.value);
    } finally {
        toggleBtnLoading(btnBuscarNotaCredito, false);
    }
});


/* Resumen del comprobante de pago */


const resumenClienteElem = document.getElementById("resumenCPCliente");

const actualizarResumenCliente = () => {
    resumenClienteElem.textContent = global.nombreCliente || "N/D";
};


//monto bruto ya definido arriba
const $montoDescuento = document.getElementById("montoDescuento");
const $montoTotal = document.getElementById("montoTotal");

// convierte a número seguro
const toNumber = (v) => {
    if (v == null)
        return 0;
    const num = parseFloat(String(v).replace(",", "."));
    return Number.isFinite(num) ? num : 0;
};
const toMoney = (n) => (Math.max(0, n)).toFixed(2);

// estado local del resumen
const resumenState = {
    bruto: toNumber($montoBruto?.textContent ?? 0),
    descuento: toNumber($montoDescuento?.textContent ?? 0)
};

const recalcularResumen = () => {
    if ($montoBruto)
        $montoBruto.textContent = toMoney(resumenState.bruto);
    if ($montoDescuento)
        $montoDescuento.textContent = toMoney(resumenState.descuento);
    if ($montoTotal)
        $montoTotal.textContent = toMoney(resumenState.bruto - resumenState.descuento);
};

// API global para ser llamada desde otras partes del código
window.setMontoBruto = (valor) => {
    resumenState.bruto = Math.max(0, toNumber(valor));
    // si el descuento actual excede el nuevo bruto -> no aplicarlo y avisar
    if (resumenState.descuento > resumenState.bruto) {
        resumenState.descuento = 0;
        Swal.fire({
            icon: "warning",
            title: "Nota de crédito excede el total",
            text: "Por favor, agregue más productos."
        });
    }
    recalcularResumen();
};

window.setDescuentoNotaCredito = (valor) => {
    const d = Math.max(0, toNumber(valor));
    if (d > resumenState.bruto) {
        // no aplicamos descuento mayor al bruto
        resumenState.descuento = 0;
        recalcularResumen();
        Swal.fire({
            icon: "warning",
            title: "Nota de crédito excede el total",
            text: "Por favor, agregue más productos."
        });
        return;
    }
    resumenState.descuento = d;
    recalcularResumen();
};

// helper para obtener total numérico cuando lo necesites
window.getTotalAPagar = () => Math.max(0, resumenState.bruto - resumenState.descuento);

// inicializamos la UI con valores actuales
recalcularResumen();



const ingresoEfectivoInput = document.getElementById("inpEfectivo");
const paneEfectivo = document.getElementById("pane-efectivo");

const getTotal = () => {
    if (typeof getTotalAPagar === "function")
        return Number(getTotalAPagar()) || 0;
    if (typeof obtenerTotal === "function")
        return Number(obtenerTotal()) || 0;
    const el = document.getElementById("montoTotal");
    return el ? Number(String(el.textContent || el.value || "0").replace(",", ".")) : 0;
};




/*Paso final: Llamdo al endpoint */
const rutaEndpoint = `${window.SERVICIOURL}CUS03/registrarComprobantePago.php`;



let idTrabajadorGlobal = 50001;
let idNotaCreditoGlobal = null;


const buildPayload = () => ({
        itemsOrdenPedido: obtenerIdsSeleccionados(),
        idMetodoComprobante: modoComprobantePagoGlobal,
        idTrabajador: idTrabajadorGlobal,
        idMetodoPago: metodoPagoGlobal,
        idNotaCredito: idNotaCreditoGlobal
    });

const postData = async (url, data) => {
    try {
        const response = await fetch(url, {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        return await response.json();
    } catch (err) {
        console.error("Error en la petición:", err);
        return {ok: false, error: err.message};
    }
};

const registrarComprobante = async (vuelto = null) => {
    const payload = buildPayload();
    console.log("Enviando payload:", payload);

    const confirm = await Swal.fire({
        title: "¿Seguro que quieres registrar el comprobante de pago?",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Sí, registrar",
        cancelButtonText: "Cancelar",
        reverseButtons: true
    });

    if (!confirm.isConfirmed)
        return;

    const result = await postData(rutaEndpoint, payload);

    if (result.ok) {
        let mensaje = result.mensaje || "Comprobante de pago registrado exitosamente.";
        if (vuelto !== null && vuelto > 0) {
            mensaje += `\n\nVuelto: S/ ${vuelto.toFixed(2)}`;
        }

        Swal.fire({
            title: "¡Comprobante registrado!",
            text: mensaje,
            icon: "success",
            confirmButtonText: "Aceptar"
        }).then((resultado) => {
            if (resultado.isConfirmed)
                location.reload();
        });
    } else {
        Swal.fire({
            title: "Error",
            text: result.error || result.mensaje || "Hubo un error al registrar",
            icon: "error",
            confirmButtonText: "Cerrar"
        });
}
};


const btnGenerarCP = document.getElementById("btnPagar");

btnGenerarCP.addEventListener("click", async (e) => {
    e.preventDefault();

    const efectivoActivo = paneEfectivo && paneEfectivo.classList.contains("active");
    let vuelto = null;

    if (efectivoActivo) {
        const total = getTotal();
        const recibido = toNumber(ingresoEfectivoInput?.value || "0");

        if (Number.isNaN(recibido) || recibido < total) {
            await Swal.fire({
                title: "Monto insuficiente",
                text: `Inserte un monto mayor o igual al total: S/ ${total.toFixed(2)}`,
                icon: "warning",
                confirmButtonText: "Entendido"
            });
            return;
        }
        vuelto = Math.max(0, recibido - total);
    }

    registrarComprobante(vuelto);
});

aplicarVisibilidad();
listaOrdenesPedido();
colocarFechaActual();