// Vista/Script/CUS29/CUS29_improved.js

document.addEventListener('DOMContentLoaded', () => {
    // Referencias a elementos del DOM - BÚSQUEDA
    const idOrdenAsignacionInput = document.getElementById('idOrdenAsignacion');
    const btnBuscarOrden = document.getElementById('btnBuscarOrden');
    const infoOrden = document.getElementById('infoOrden');
    const txtIdOrden = document.getElementById('txtIdOrden');
    const txtRepartidor = document.getElementById('txtRepartidor');
    const txtVehiculo = document.getElementById('txtVehiculo');
    const txtNotaCaja = document.getElementById('txtNotaCaja');
    const txtFechaProgramada = document.getElementById('txtFechaProgramada');
    const txtEstadoOrden = document.getElementById('txtEstadoOrden');
    
    // SECCIÓN PEDIDOS
    const seccionPedidos = document.getElementById('seccionPedidos');
    const tbodyPedidos = document.getElementById('tbodyPedidos');
    const progresoEntregas = document.getElementById('progresoEntregas');
    
    // FORMULARIO CONSOLIDACIÓN
    const formularioConsolidacion = document.getElementById('formularioConsolidacion');
    const txtPedidoActual = document.getElementById('txtPedidoActual');
    const txtDetalleAsignacion = document.getElementById('txtDetalleAsignacion');
    const txtMontoEsperado = document.getElementById('txtMontoEsperado');
    const btnCancelarPedido = document.getElementById('btnCancelarPedido');
    
    const infoVueltoDisponible = document.getElementById('infoVueltoDisponible');
    const montoRecibidoInput = document.getElementById('montoRecibido');
    const btnCalcularVuelto = document.getElementById('btnCalcularVuelto');
    const txtVuelto = document.getElementById('txtVuelto');
    
    const btnAddDenomRecibida = document.getElementById('btnAddDenomRecibida');
    const tbodyDenomRecibida = document.querySelector('#tablaDenomRecibida tbody');
    
    const btnAddDenomVuelto = document.getElementById('btnAddDenomVuelto');
    const tbodyDenomVuelto = document.querySelector('#tablaDenomVuelto tbody');
    
    const tipoIncidenciaSelect = document.getElementById('tipoIncidencia');
    const descIncidenciaText = document.getElementById('descIncidencia');
    const btnRegistrarIncidencia = document.getElementById('btnRegistrarIncidencia');
    const estadoIncidencia = document.getElementById('estadoIncidencia');
    
    const btnFinalizar = document.getElementById('btnFinalizar');
    
    // ESTADO GLOBAL
    let estadoOrden = {
        idOrdenAsignacion: null,
        idNotaCaja: null,
        idRepartidor: null,
        vueltoTotal: 0,
        vueltoDisponible: 0,
        pedidos: []
    };
    
    let pedidoActual = {
        idPedido: null,
        idDetalleAsignacion: null,
        montoEsperado: 0,
        idEntrega: null,
        idPago: null
    };
    
    let incidenciaRegistrada = null;
    
    // ============================================
    // FUNCIONES UTILITARIAS
    // ============================================
    
    function formatMoney(amount) {
        return Number(amount || 0).toFixed(2);
    }
    
    function mostrarNotificacion(mensaje, tipo = 'info') {
        let notif = document.getElementById('notificacion-toast');
        if (!notif) {
            notif = document.createElement('div');
            notif.id = 'notificacion-toast';
            notif.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 10px;
                color: white;
                font-weight: 600;
                z-index: 9999;
                min-width: 300px;
                box-shadow: 0 6px 20px rgba(0,0,0,0.3);
                transform: translateX(400px);
                transition: transform 0.3s ease;
            `;
            document.body.appendChild(notif);
        }
        
        const colores = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };
        
        notif.style.backgroundColor = colores[tipo] || colores.info;
        notif.textContent = mensaje;
        
        setTimeout(() => notif.style.transform = 'translateX(0)', 10);
        setTimeout(() => notif.style.transform = 'translateX(400px)', 3500);
    }
    
    // ============================================
    // BUSCAR ORDEN DE ASIGNACIÓN
    // ============================================
    
    btnBuscarOrden.addEventListener('click', async () => {
        const idOrden = idOrdenAsignacionInput.value.trim();
        
        if (!idOrden) {
            mostrarNotificacion('Ingrese un ID de orden de asignación', 'warning');
            return;
        }
        
        btnBuscarOrden.disabled = true;
        btnBuscarOrden.textContent = 'Buscando...';
        
        try {
            const res = await fetch(`../../Vista/Ajax/CUS29/obtenerPedidosOrden.php?id=${idOrden}`);
            const json = await res.json();
            
            if (!json.success) {
                throw new Error(json.error || 'Error al buscar orden');
            }
            
            // Actualizar estado global
            estadoOrden.idOrdenAsignacion = json.orden.idOrden;
            estadoOrden.idNotaCaja = json.orden.idNotaCaja;
            estadoOrden.idRepartidor = json.orden.idRepartidor;
            estadoOrden.vueltoTotal = json.orden.vueltoTotal;
            estadoOrden.pedidos = json.pedidos;
            
            // Mostrar información de la orden
            txtIdOrden.textContent = json.orden.idOrden;
            txtRepartidor.textContent = json.orden.nombreRepartidor || 'Sin asignar';
            txtVehiculo.textContent = json.orden.vehiculo || 'Sin vehículo';
            txtNotaCaja.textContent = json.orden.idNotaCaja || '-';
            txtFechaProgramada.textContent = json.orden.fechaProgramada || '-';
            txtEstadoOrden.textContent = json.orden.estado || '-';
            
            // Activar visualmente la sección de info
            infoOrden.classList.remove('info-vacia');
            
            // Mostrar lista de pedidos
            mostrarPedidos(json.pedidos);
            
            // Activar sección de pedidos
            seccionPedidos.classList.remove('seccion-deshabilitada');
            seccionPedidos.classList.add('seccion-activa');
            
            // Mantener formulario deshabilitado
            formularioConsolidacion.classList.add('seccion-deshabilitada');
            formularioConsolidacion.classList.remove('seccion-activa');
            
            // Obtener vuelto disponible
            if (json.orden.idNotaCaja) {
                await obtenerVueltoDisponible(json.orden.idNotaCaja);
            }
            
            mostrarNotificacion(`✓ Orden cargada: ${json.pedidos.length} pedido(s) encontrado(s)`, 'success');
            
        } catch (error) {
            console.error('Error:', error);
            mostrarNotificacion('Error: ' + error.message, 'error');
        } finally {
            btnBuscarOrden.disabled = false;
            btnBuscarOrden.textContent = 'Buscar Orden';
        }
    });
    
    // ============================================
    // MOSTRAR PEDIDOS EN TABLA
    // ============================================
    
    function mostrarPedidos(pedidos) {
        tbodyPedidos.innerHTML = '';
        
        if (pedidos.length === 0) {
            tbodyPedidos.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px; color:#999;">No hay pedidos en esta asignación</td></tr>';
            progresoEntregas.innerHTML = '<span class="progreso-texto">0/0 pedidos</span>';
            return;
        }
        
        // Calcular progreso
        const totalPedidos = pedidos.length;
        const pedidosEntregados = pedidos.filter(p => p.estado === 'Entregado').length;
        const porcentaje = Math.round((pedidosEntregados / totalPedidos) * 100);
        
        // Actualizar barra de progreso
        const colorProgreso = porcentaje === 100 ? '#10b981' : porcentaje > 0 ? '#f59e0b' : '#6b7280';
        progresoEntregas.innerHTML = `
            <div class="progreso-barra-container">
                <div class="progreso-barra" style="width: ${porcentaje}%; background: ${colorProgreso};"></div>
            </div>
            <span class="progreso-texto">${pedidosEntregados}/${totalPedidos} pedidos entregados (${porcentaje}%)</span>
        `;
        
        pedidos.forEach(pedido => {
            const tr = document.createElement('tr');
            
            const estadoClass = pedido.estado === 'Pendiente' ? 'estado-pendiente' : 'estado-entregado';
            const btnHtml = pedido.estado === 'Pendiente' 
                ? `<button class="btn-registrar" data-id="${pedido.idPedido}" data-detalle="${pedido.idDetalleAsignacion}" data-monto="${pedido.total}">✏️ Registrar</button>`
                : `<span class="badge-entregado">✓ Entregado</span>`;
            
            tr.innerHTML = `
                <td><strong>#${pedido.idPedido}</strong></td>
                <td>${pedido.cliente}</td>
                <td>${pedido.direccion}</td>
                <td><strong>S/ ${formatMoney(pedido.total)}</strong></td>
                <td><span class="${estadoClass}">${pedido.estado}</span></td>
                <td>${btnHtml}</td>
            `;
            
            tbodyPedidos.appendChild(tr);
        });
        
        // Agregar event listeners a los botones de registrar
        document.querySelectorAll('.btn-registrar').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const idPedido = parseInt(e.target.dataset.id);
                const idDetalle = parseInt(e.target.dataset.detalle);
                const monto = parseFloat(e.target.dataset.monto);
                seleccionarPedido(idPedido, idDetalle, monto);
            });
        });
    }
    
    // ============================================
    // SELECCIONAR PEDIDO PARA CONSOLIDACIÓN
    // ============================================
    
    async function seleccionarPedido(idPedido, idDetalleAsignacion, montoEsperado) {
        pedidoActual.idPedido = idPedido;
        pedidoActual.idDetalleAsignacion = idDetalleAsignacion;
        pedidoActual.montoEsperado = montoEsperado;
        
        // Actualizar UI
        txtPedidoActual.textContent = `#${idPedido}`;
        txtDetalleAsignacion.textContent = idDetalleAsignacion;
        txtMontoEsperado.textContent = formatMoney(montoEsperado);
        
        // Mostrar info del pedido actual y ocultar mensaje vacío
        document.getElementById('infoPedidoActual').style.display = 'grid';
        document.getElementById('btnCancelarPedido').style.display = 'inline-flex';
        document.querySelector('.pedido-actual-info .mensaje-vacio').style.display = 'none';
        document.querySelector('.pedido-actual-info').classList.remove('pedido-vacio');
        
        // Activar formulario de consolidación
        formularioConsolidacion.classList.remove('seccion-deshabilitada');
        formularioConsolidacion.classList.add('seccion-activa');
        
        // Scroll suave al formulario
        formularioConsolidacion.scrollIntoView({ behavior: 'smooth', block: 'start' });
        
        // Actualizar info de vuelto
        if (estadoOrden.idNotaCaja) {
            await mostrarInfoVuelto();
        }
        
        // Limpiar formulario
        limpiarFormularioConsolidacion();
        
        mostrarNotificacion(`Pedido #${idPedido} seleccionado`, 'info');
    }
    
    // ============================================
    // CANCELAR PEDIDO ACTUAL
    // ============================================
    
    btnCancelarPedido.addEventListener('click', () => {
        if (confirm('¿Desea cancelar el registro de este pedido?')) {
            // Ocultar info del pedido y mostrar mensaje vacío
            document.getElementById('infoPedidoActual').style.display = 'none';
            document.getElementById('btnCancelarPedido').style.display = 'none';
            document.querySelector('.pedido-actual-info .mensaje-vacio').style.display = 'block';
            document.querySelector('.pedido-actual-info').classList.add('pedido-vacio');
            
            // Deshabilitar formulario
            formularioConsolidacion.classList.add('seccion-deshabilitada');
            formularioConsolidacion.classList.remove('seccion-activa');
            
            limpiarFormularioConsolidacion();
            pedidoActual = {
                idPedido: null,
                idDetalleAsignacion: null,
                montoEsperado: 0,
                idEntrega: null,
                idPago: null
            };
            seccionPedidos.scrollIntoView({ behavior: 'smooth' });
            mostrarNotificacion('Registro cancelado', 'info');
        }
    });
    
    // ============================================
    // VUELTO DISPONIBLE
    // ============================================
    
    async function obtenerVueltoDisponible(idNotaCaja) {
        try {
            const res = await fetch(`../../Vista/Ajax/CUS29/validarVueltoDisponible.php?idNotaCaja=${idNotaCaja}`);
            const json = await res.json();
            
            if (json.success) {
                estadoOrden.vueltoDisponible = json.vueltoDisponible;
                estadoOrden.vueltoTotal = json.vueltoTotal;
            }
        } catch (error) {
            console.error('Error al obtener vuelto disponible:', error);
        }
    }
    
    async function mostrarInfoVuelto() {
        await obtenerVueltoDisponible(estadoOrden.idNotaCaja);
        
        infoVueltoDisponible.innerHTML = `
            <p><strong>Vuelto inicial de caja:</strong> S/ ${formatMoney(estadoOrden.vueltoTotal)}</p>
            <p><strong>Saldo actual del repartidor:</strong>
                <span style="color: ${estadoOrden.vueltoDisponible > 0 ? '#059669' : '#dc2626'}; font-size: 24px; font-weight: bold;">
                    S/ ${formatMoney(estadoOrden.vueltoDisponible)}
                </span>
            </p>
            <p style="font-size: 12px; color: #6b7280; margin-top: 8px;">
                <em>El saldo incluye el vuelto inicial + dinero cobrado - vueltos dados</em>
            </p>
        `;
    }
    
    // ============================================
    // CALCULAR VUELTO
    // ============================================
    
    btnCalcularVuelto.addEventListener('click', () => {
        const montoRecibido = parseFloat(montoRecibidoInput.value || 0);
        const vuelto = montoRecibido - pedidoActual.montoEsperado;
        
        txtVuelto.textContent = formatMoney(vuelto);
        
        // Validar que no exceda el vuelto disponible
        if (vuelto > estadoOrden.vueltoDisponible) {
            mostrarNotificacion(
                `⚠️ ADVERTENCIA: El vuelto (S/ ${formatMoney(vuelto)}) excede el disponible (S/ ${formatMoney(estadoOrden.vueltoDisponible)})`,
                'warning'
            );
        } else if (vuelto > 0) {
            mostrarNotificacion('✓ Vuelto calculado correctamente', 'success');
        }
    });
    
    // ============================================
    // DENOMINACIONES
    // ============================================
    
    function crearFilaDenominacion(tipo = 'Billete', denominacion = '', cantidad = 0) {
        const tr = document.createElement('tr');
        
        tr.innerHTML = `
            <td>
                <select class="select-tipo">
                    <option value="Billete" ${tipo === 'Billete' ? 'selected' : ''}>Billete</option>
                    <option value="Moneda" ${tipo === 'Moneda' ? 'selected' : ''}>Moneda</option>
                </select>
            </td>
            <td>
                <input type="number" class="input-denominacion" step="0.01" min="0" value="${denominacion}" placeholder="10.00">
            </td>
            <td>
                <input type="number" class="input-cantidad" step="1" min="0" value="${cantidad}" placeholder="5">
            </td>
            <td class="td-total">S/ 0.00</td>
            <td>
                <button class="btn-eliminar">🗑️ Eliminar</button>
            </td>
        `;
        
        const inputDenom = tr.querySelector('.input-denominacion');
        const inputCant = tr.querySelector('.input-cantidad');
        const tdTotal = tr.querySelector('.td-total');
        
        function actualizarTotal() {
            const d = parseFloat(inputDenom.value || 0);
            const c = parseInt(inputCant.value || 0);
            tdTotal.textContent = 'S/ ' + formatMoney(d * c);
        }
        
        inputDenom.addEventListener('input', actualizarTotal);
        inputCant.addEventListener('input', actualizarTotal);
        
        tr.querySelector('.btn-eliminar').addEventListener('click', () => {
            tr.remove();
        });
        
        actualizarTotal();
        return tr;
    }
    
    btnAddDenomRecibida.addEventListener('click', () => {
        tbodyDenomRecibida.appendChild(crearFilaDenominacion());
    });
    
    btnAddDenomVuelto.addEventListener('click', () => {
        tbodyDenomVuelto.appendChild(crearFilaDenominacion());
    });
    
    function leerDenominaciones(tbody) {
        const filas = Array.from(tbody.querySelectorAll('tr'));
        return filas.map(tr => {
            const tipo = tr.querySelector('.select-tipo').value;
            const denominacion = parseFloat(tr.querySelector('.input-denominacion').value || 0);
            const cantidad = parseInt(tr.querySelector('.input-cantidad').value || 0);
            return { tipo, denominacion, cantidad };
        }).filter(d => d.cantidad > 0 && d.denominacion > 0);
    }
    
    // ============================================
    // INCIDENCIAS
    // ============================================
    
    btnRegistrarIncidencia.addEventListener('click', () => {
        const tipoInc = tipoIncidenciaSelect.value;
        const descInc = descIncidenciaText.value.trim();
        
        if (!tipoInc) {
            mostrarNotificacion('Seleccione un tipo de incidencia', 'warning');
            return;
        }
        
        incidenciaRegistrada = {
            tipo: tipoInc,
            descripcion: descInc || 'Sin descripción'
        };
        
        estadoIncidencia.style.display = 'block';
        estadoIncidencia.innerHTML = `✓ Incidencia marcada: <strong>${tipoInc}</strong> (se guardará al finalizar)`;
        
        mostrarNotificacion('✓ Incidencia registrada', 'warning');
    });
    
    // ============================================
    // FINALIZAR CONSOLIDACIÓN
    // ============================================
    
    btnFinalizar.addEventListener('click', async () => {
        // Validaciones iniciales
        if (!pedidoActual.idPedido) {
            mostrarNotificacion('No hay pedido seleccionado', 'warning');
            return;
        }
        
        const montoRecibido = parseFloat(montoRecibidoInput.value || 0);
        const vuelto = montoRecibido - pedidoActual.montoEsperado;
        
        // VALIDACIÓN CRÍTICA: Vuelto no puede exceder el disponible
        if (vuelto > estadoOrden.vueltoDisponible) {
            mostrarNotificacion(
                `❌ ERROR: El vuelto (S/ ${formatMoney(vuelto)}) excede el disponible (S/ ${formatMoney(estadoOrden.vueltoDisponible)}). Debe registrar una incidencia.`,
                'error'
            );
            return;
        }
        
        if (montoRecibido < pedidoActual.montoEsperado && !incidenciaRegistrada) {
            mostrarNotificacion('El monto recibido es menor al esperado. Debe registrar una incidencia.', 'warning');
            return;
        }
        
        const confirmMsg = `¿Confirma finalizar la consolidación?\n\n` +
                          `Pedido: #${pedidoActual.idPedido}\n` +
                          `Monto esperado: S/ ${formatMoney(pedidoActual.montoEsperado)}\n` +
                          `Monto recibido: S/ ${formatMoney(montoRecibido)}\n` +
                          `Vuelto: S/ ${formatMoney(vuelto)}\n` +
                          (incidenciaRegistrada ? `⚠️ Con incidencia: ${incidenciaRegistrada.tipo}` : '');
        
        if (!confirm(confirmMsg)) {
            return;
        }
        
        btnFinalizar.disabled = true;
        btnFinalizar.innerHTML = '<span>⏳ Procesando...</span>';
        
        try {
            const estadoEntrega = incidenciaRegistrada ? 'No Entregado' : 'Entregado';
            
            // 1) Registrar entrega
            console.log('1. Registrando entrega...');
            const resEntrega = await fetch('../../Vista/Ajax/CUS29/registrarEntrega.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    idPedido: pedidoActual.idPedido,
                    idDetalleAsign: pedidoActual.idDetalleAsignacion,
                    idNotaCaja: estadoOrden.idNotaCaja,
                    idTrabajador: estadoOrden.idRepartidor,
                    estadoEntrega: estadoEntrega,
                    observaciones: incidenciaRegistrada ? incidenciaRegistrada.descripcion : ''
                })
            });
            
            const jsonEntrega = await resEntrega.json();
            if (!jsonEntrega.success) {
                throw new Error(jsonEntrega.error || 'Error al registrar entrega');
            }
            
            pedidoActual.idEntrega = jsonEntrega.idEntrega;
            console.log('✓ Entrega registrada ID:', pedidoActual.idEntrega);
            
            // 2) Registrar pago
            console.log('2. Registrando pago...');
            const resPago = await fetch('../../Vista/Ajax/CUS29/registrarPago.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    idEntrega: pedidoActual.idEntrega,
                    montoEsperado: pedidoActual.montoEsperado,
                    montoRecibido: montoRecibido,
                    montoVuelto: vuelto
                })
            });
            
            const jsonPago = await resPago.json();
            if (!jsonPago.success) {
                throw new Error(jsonPago.error || 'Error al registrar pago');
            }
            
            pedidoActual.idPago = jsonPago.idPago;
            console.log('✓ Pago registrado ID:', pedidoActual.idPago);
            
            // 3) Registrar denominaciones recibidas
            console.log('3. Registrando denominaciones recibidas...');
            const denomsRecibidas = leerDenominaciones(tbodyDenomRecibida);
            for (const denom of denomsRecibidas) {
                await fetch('../../Vista/Ajax/CUS29/registrarDenominacionPago.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        idPago: pedidoActual.idPago,
                        tipo: denom.tipo,
                        denominacion: denom.denominacion,
                        cantidad: denom.cantidad
                    })
                });
            }
            console.log(`✓ ${denomsRecibidas.length} denominaciones recibidas guardadas`);
            
            // 4) Registrar denominaciones de vuelto
            console.log('4. Registrando denominaciones de vuelto...');
            const denomsVuelto = leerDenominaciones(tbodyDenomVuelto);
            for (const denom of denomsVuelto) {
                await fetch('../../Vista/Ajax/CUS29/registrarDenominacionVuelto.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        idPago: pedidoActual.idPago,
                        tipo: denom.tipo,
                        denominacion: denom.denominacion,
                        cantidad: denom.cantidad
                    })
                });
            }
            console.log(`✓ ${denomsVuelto.length} denominaciones de vuelto guardadas`);
            
            // 5) Registrar movimientos de control de vuelto
            console.log('5. Registrando control de vuelto...');
            const resControlVuelto = await fetch('../../Vista/Ajax/CUS29/registrarControlVuelto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    idNotaCaja: estadoOrden.idNotaCaja,
                    idEntrega: pedidoActual.idEntrega,
                    montoRecibido: montoRecibido,
                    vueltoEntregado: vuelto
                })
            });
            
            const jsonControl = await resControlVuelto.json();
            if (!jsonControl.success) {
                console.warn('Advertencia al registrar control de vuelto:', jsonControl.error);
            } else {
                console.log('✓ Control de vuelto actualizado');
            }
            
            // 6) Registrar incidencia si existe
            if (incidenciaRegistrada) {
                console.log('6. Registrando incidencia...');
                await fetch('../../Vista/Ajax/CUS29/registrarIncidencia.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        idEntrega: pedidoActual.idEntrega,
                        tipoIncidencia: incidenciaRegistrada.tipo,
                        descripcion: incidenciaRegistrada.descripcion
                    })
                });
                console.log('✓ Incidencia registrada');
            }
            
            // 7) Actualizar estados en la base de datos
            console.log('7. Actualizando estados...');
            const resEstados = await fetch('../../Vista/Ajax/CUS29/actualizarEstados.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    idPedido: pedidoActual.idPedido,
                    idOrdenAsignacion: estadoOrden.idOrdenAsignacion,
                    estadoEntrega: estadoEntrega
                })
            });
            
            const jsonEstados = await resEstados.json();
            if (!jsonEstados.success) {
                console.warn('Advertencia al actualizar estados:', jsonEstados.error);
            } else {
                console.log('✓ Estados actualizados correctamente');
                console.log(`  - Estado del pedido: ${jsonEstados.estadoPedido}`);
                console.log(`  - Estado de la orden: ${jsonEstados.estadoOrden}`);
                console.log(`  - Pedidos entregados: ${jsonEstados.pedidosEntregados}/${jsonEstados.totalPedidos}`);
            }
            
            // Mostrar resultado exitoso
            let mensajeFinal = '✅ Consolidación guardada correctamente';
            
            if (jsonEstados.success && jsonEstados.todosEntregados) {
                mensajeFinal = '¡Todos los pedidos han sido entregados!\n\nLa orden de asignación se ha marcado como FINALIZADA';
            }
            
            alert(mensajeFinal);
            
            mostrarNotificacion(
                jsonEstados.todosEntregados 
                    ? '🎉 ¡Orden completada! Todos los pedidos entregados' 
                    : '✅ Consolidación completada', 
                'success'
            );
            
            // Ocultar info del pedido actual y deshabilitar formulario
            document.getElementById('infoPedidoActual').style.display = 'none';
            document.getElementById('btnCancelarPedido').style.display = 'none';
            document.querySelector('.pedido-actual-info .mensaje-vacio').style.display = 'block';
            document.querySelector('.pedido-actual-info').classList.add('pedido-vacio');
            
            formularioConsolidacion.classList.add('seccion-deshabilitada');
            formularioConsolidacion.classList.remove('seccion-activa');
            
            // Recargar la orden
            btnBuscarOrden.click();
            
        } catch (error) {
            console.error('Error:', error);
            alert('❌ Error en la consolidación:\n\n' + error.message);
            mostrarNotificacion('Error: ' + error.message, 'error');
        } finally {
            btnFinalizar.disabled = false;
            btnFinalizar.innerHTML = '<span>✅ Finalizar Consolidación de Este Pedido</span>';
        }
    });
    
    // ============================================
    // LIMPIAR FORMULARIO CONSOLIDACIÓN
    // ============================================
    
    function limpiarFormularioConsolidacion() {
        montoRecibidoInput.value = '';
        txtVuelto.textContent = '0.00';
        
        tbodyDenomRecibida.innerHTML = '';
        tbodyDenomVuelto.innerHTML = '';
        
        tipoIncidenciaSelect.value = '';
        descIncidenciaText.value = '';
        estadoIncidencia.style.display = 'none';
        incidenciaRegistrada = null;
    }
    
    // ============================================
    // ACTUALIZAR HORA EN TIEMPO REAL
    // ============================================
    
    setInterval(() => {
        const now = new Date();
        document.getElementById('horaTexto').textContent = now.toLocaleTimeString('es-PE');
    }, 1000);
});