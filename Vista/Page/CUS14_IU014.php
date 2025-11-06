<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Cotizaciones al Proveedor</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }

        .responsable-top {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .responsable-top .form-row {
            margin-bottom: 0;
        }

        .responsable-top .form-group {
            margin-bottom: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 20px;
        }

        .header h1 {
            color: #333;
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .datetime {
            color: #666;
            font-size: 1.1em;
        }

        .section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background-color: #fafafa;
        }

        .section h2 {
            color: #444;
            margin-bottom: 15px;
            font-size: 1.3em;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 10px;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .table-container thead {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 10;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #555;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .checkbox {
            transform: scale(1.2);
            cursor: pointer;
        }

        .sections-row {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .section-left {
            flex: 1;
            margin-bottom: 0;
        }

        .section-right {
            flex: 1;
            margin-bottom: 0;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-primary:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
        }

        .btn-generar {
            margin: 20px 0;
            display: flex;
            justify-content: flex-end;
        }

        .footer {
            margin-top: 30px;
            text-align: right;
            border-top: 2px solid #e0e0e0;
            padding-top: 20px;
        }

        .footer .btn {
            margin-left: 10px;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }

            .sections-row {
                flex-direction: column;
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Campos del responsable en la parte superior -->
        <div class="responsable-top">
            <div class="form-row">
                <div class="form-group">
                    <label for="responsableId">ID Responsable de Compra:</label>
                    <input type="text" id="responsableId" value="" readonly>
                </div>
                <div class="form-group">
                    <label for="responsableNombre">Nombre:</label>
                    <input type="text" id="responsableNombre" value="" readonly>
                </div>
                <div class="form-group">
                    <label for="responsableApellido">Ap. Paterno:</label>
                    <input type="text" id="responsableApellido" value="" readonly>
                </div>
            </div>
        </div>

        <div class="header">
            <h1>CUS 14 - SOLICITUD DE COTIZACIONES AL PROVEEDOR</h1>
            <div class="datetime">
                <span id="currentDate"></span> - <span id="currentTime"></span>
            </div>
        </div>

        <div class="section">
            <h2>Solicitudes de Requerimiento Evaluadas</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID Requerimiento Evaluado</th>
                            <th>Seleccionar</th>
                        </tr>
                    </thead>
                    <tbody id="requerimientosTable">
                        <tr>
                            <td colspan="2" style="text-align: center; color: #666;">
                                Cargando requerimientos...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sections-row">
            <div class="section section-left">
                <h2>Solicitud de Requerimiento</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Producto</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Seleccionar</th>
                            </tr>
                        </thead>
                        <tbody id="productosTable">
                            <tr>
                                <td colspan="4" style="text-align: center; color: #666;">
                                    Seleccione un requerimiento evaluado
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="section section-right">
                <h2>Proveedores</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>RUC</th>
                                <th>Empresa</th>
                                <th>Correo</th>
                            </tr>
                        </thead>
                        <tbody id="proveedoresTable">
                            <tr>
                                <td colspan="3" style="text-align: center; color: #666;">
                                    Cargando proveedores...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="btn-generar">
            <button type="button" class="btn btn-primary" id="btnGenerarSolicitud" onclick="generarSolicitud()" disabled>Generar Solicitud</button>
        </div>

        <div class="section">
            <h2>Solicitud Cotización</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID Solicitud</th>
                            <th>RUC</th>
                            <th>Empresa</th>
                            <th>Correo</th>
                            <th>ID Producto</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Fecha Emision</th>
                            <th>Fecha Cierre</th>
                        </tr>
                    </thead>
                    <tbody id="solicitudCotizacionTable">
                        <tr>
                            <td colspan="9" style="text-align: center; color: #666;">
                                No hay solicitudes generadas
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="footer">
            <button type="button" class="btn btn-primary" id="btnEnviar" onclick="enviarSolicitud()" disabled>Enviar</button>
            <button type="button" class="btn btn-secondary" onclick="salir()">Salir</button>
        </div>
    </div>

    <script>
        function updateDateTime() {
            const now = new Date();
            const date = now.toLocaleDateString('es-ES');
            const time = now.toLocaleTimeString('es-ES');
            document.getElementById('currentDate').textContent = date;
            document.getElementById('currentTime').textContent = time;
        }

        setInterval(updateDateTime, 1000);
        updateDateTime();

        // Variables globales
        let requerimientosSeleccionados = [];
        let productosSeleccionados = [];
        let productoSeleccionado = null; // Producto seleccionado (solo uno)
        let proveedores = [];
        let solicitudCotizacion = [];

        // Persistencia de bloqueos entre recargas
        const LS_REQ_LOCK_KEY = 'CUS14_reqLock';
        const LS_PROD_DISABLED_PREFIX = 'CUS14_disabledProducts_';

        function getReqLock() { return localStorage.getItem(LS_REQ_LOCK_KEY) || null; }
        function setReqLock(idReq) { if (idReq) localStorage.setItem(LS_REQ_LOCK_KEY, idReq); }
        function clearReqLock() { localStorage.removeItem(LS_REQ_LOCK_KEY); }
        function getDisabledProducts(reqId) {
            try {
                const raw = localStorage.getItem(LS_PROD_DISABLED_PREFIX + reqId);
                if (!raw) return new Set();
                const arr = JSON.parse(raw);
                return new Set(Array.isArray(arr) ? arr : []);
            } catch { return new Set(); }
        }
        function addDisabledProduct(reqId, idProducto) {
            const set = getDisabledProducts(reqId);
            set.add(String(idProducto));
            localStorage.setItem(LS_PROD_DISABLED_PREFIX + reqId, JSON.stringify(Array.from(set)));
        }
        function clearDisabledProducts(reqId) { localStorage.removeItem(LS_PROD_DISABLED_PREFIX + reqId); }

        // Cargar datos al iniciar la página
        document.addEventListener('DOMContentLoaded', async function() {
            cargarDatosResponsable();
            await cargarRequerimientosEvaluados();
            // No cargar proveedores al inicio, se cargarán cuando se seleccione un producto
            limpiarProveedores();
            // Cargar solicitudes pendientes en la tabla inferior
            cargarSolicitudesPendientes();

            // Los event listeners para checkboxes se agregan en renderRequerimientos
        });

        // Función para cargar datos del responsable
        async function cargarDatosResponsable() {
            try {
                const response = await fetch('../../Controlador/CUS14Negocio.php?action=obtener_responsable');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('responsableId').value = data.data.id_Trabajador || '';
                    document.getElementById('responsableNombre').value = data.data.des_nombreTrabajador || '';
                    document.getElementById('responsableApellido').value = data.data.des_apepatTrabajador || '';
                    
                    console.log('Datos del responsable cargados:', data.data);
                } else {
                    console.error('Error al cargar datos del responsable:', data.message);
                }
            } catch (error) {
                console.error('Error al cargar datos del responsable:', error);
            }
        }

        // Función para cargar requerimientos evaluados
        async function cargarRequerimientosEvaluados() {
            try {
                const response = await fetch('../../Controlador/CUS14Negocio.php?action=obtener_requerimientos_evaluados');
                const data = await response.json();
                
                if (data.success) {
                    renderRequerimientos(data.data);
                    // Aplicar bloqueo persistido si existe
                    const lockedReq = getReqLock();
                    if (lockedReq) {
                        const tbodyReq = document.getElementById('requerimientosTable');
                        const checks = tbodyReq ? tbodyReq.querySelectorAll('.checkbox-requerimiento') : [];
                        checks.forEach(cb => {
                            if (cb.value === String(lockedReq)) cb.checked = true;
                            cb.disabled = true;
                        });
                        actualizarCheckboxes();
                        await cargarProductosSeleccionados();
                    }
                } else {
                    console.error('Error al cargar requerimientos:', data.message);
                    mostrarErrorRequerimientos('Error al cargar los requerimientos');
                }
            } catch (error) {
                console.error('Error al cargar requerimientos:', error);
                mostrarErrorRequerimientos('Error de conexión');
            }
        }

        // Función para renderizar requerimientos
        function renderRequerimientos(requerimientos) {
            const tbody = document.getElementById('requerimientosTable');
            tbody.innerHTML = '';
            
            if (!requerimientos || requerimientos.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="2" style="text-align: center; color: #666;">
                            No hay requerimientos evaluados disponibles
                        </td>
                    </tr>
                `;
                return;
            }
            
            requerimientos.forEach(item => {
                const tr = document.createElement('tr');
                const idRequerimiento = item.Id_ReqEvaluacion || item.id_ReqEvaluacion || item.Id_RequerimientoEvaluado || item.id_RequerimientoEvaluado;
                tr.innerHTML = `
                    <td>${idRequerimiento}</td>
                    <td>
                        <input type="checkbox" class="checkbox checkbox-requerimiento" 
                               value="${idRequerimiento}" 
                               data-id="${idRequerimiento}">
                    </td>
                `;
                tbody.appendChild(tr);
            });
            
            // Agregar event listeners para selección única
            tbody.querySelectorAll('.checkbox-requerimiento').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        // Desmarcar todos los demás checkboxes
                        tbody.querySelectorAll('.checkbox-requerimiento').forEach(cb => {
                            if (cb !== this) {
                                cb.checked = false;
                            }
                        });
                    }
                    actualizarCheckboxes();
                    cargarProductosSeleccionados();
                    // Limpiar selección de producto cuando cambia el requerimiento
                    productoSeleccionado = null;
                    limpiarProveedores();
                    actualizarEstadoBotonGenerar();
                });
            });
        }

        function mostrarErrorRequerimientos(mensaje) {
            const tbody = document.getElementById('requerimientosTable');
            tbody.innerHTML = `
                <tr>
                    <td colspan="2" style="text-align: center; color: red;">
                        ${mensaje}
                    </td>
                </tr>
            `;
        }

        // Función para actualizar checkboxes seleccionados (solo uno puede estar marcado)
        function actualizarCheckboxes() {
            requerimientosSeleccionados = [];
            const checkboxMarcado = document.querySelector('.checkbox-requerimiento:checked');
            if (checkboxMarcado) {
                requerimientosSeleccionados.push(checkboxMarcado.value);
            }
        }

        // Función para cargar productos del requerimiento seleccionado
        async function cargarProductosSeleccionados() {
            // Limpiar selección de producto cuando se cargan nuevos productos
            productoSeleccionado = null;
            limpiarProveedores();
            
            if (requerimientosSeleccionados.length === 0) {
                const tbody = document.getElementById('productosTable');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" style="text-align: center; color: #666;">
                            Seleccione un requerimiento evaluado
                        </td>
                    </tr>
                `;
                productosSeleccionados = [];
                actualizarEstadoBotonGenerar();
                return;
            }

            try {
                // Solo hay un requerimiento seleccionado (selección única)
                const idRequerimiento = requerimientosSeleccionados[0];
                
                const response = await fetch(`../../Controlador/CUS14Negocio.php?action=obtener_productos_requerimiento&idRequerimiento=${encodeURIComponent(idRequerimiento)}`);
                const resultado = await response.json();
                
                if (resultado.success && resultado.data) {
                    productosSeleccionados = resultado.data.map(producto => ({
                        Id_Producto: producto.Id_Producto,
                        NombreProducto: producto.NombreProducto,
                        Cantidad: parseInt(producto.Cantidad || 0)
                    }));
                    renderProductos(productosSeleccionados);
                    actualizarEstadoBotonGenerar();
                } else {
                    productosSeleccionados = [];
                    const tbody = document.getElementById('productosTable');
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4" style="text-align: center; color: #666;">
                                No hay productos para este requerimiento
                            </td>
                        </tr>
                    `;
                }
            } catch (error) {
                console.error('Error al cargar productos:', error);
                const tbody = document.getElementById('productosTable');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" style="text-align: center; color: red;">
                            Error al cargar productos
                        </td>
                    </tr>
                `;
            }
        }

        // Función para renderizar productos
        function renderProductos(productos) {
            const tbody = document.getElementById('productosTable');
            tbody.innerHTML = '';
            
            if (!productos || productos.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" style="text-align: center; color: #666;">
                            No hay productos para los requerimientos seleccionados
                        </td>
                    </tr>
                `;
                return;
            }
            
            productos.forEach(producto => {
                const tr = document.createElement('tr');
                const productoKey = producto.NombreProducto || '';
                tr.innerHTML = `
                    <td>${producto.Id_Producto || ''}</td>
                    <td>${producto.NombreProducto}</td>
                    <td>${producto.Cantidad}</td>
                    <td>
                        <input type="checkbox" class="checkbox checkbox-producto" 
                               value="${productoKey}" 
                               data-producto="${productoKey}"
                               data-idproducto="${producto.Id_Producto || ''}"
                               data-cantidad="${producto.Cantidad}">
                    </td>
                `;
                tbody.appendChild(tr);
            });
            
            // Aplicar bloqueos persistidos si existe requerimiento bloqueado
            const _lockedReq = getReqLock();
            if (_lockedReq) {
                const disabledSet = getDisabledProducts(_lockedReq);
                tbody.querySelectorAll('.checkbox-producto').forEach(cb => {
                    const idp = cb.getAttribute('data-idproducto') || '';
                    if (disabledSet.has(String(idp))) {
                        cb.disabled = true;
                        cb.checked = false;
                    }
                });
                // Bloquear checkboxes de requerimientos en la parte superior
                document.querySelectorAll('.checkbox-requerimiento').forEach(cb => cb.disabled = true);
            }
            
            // Agregar event listeners para selección única de productos
            tbody.querySelectorAll('.checkbox-producto').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        // Desmarcar todos los demás checkboxes de productos
                        tbody.querySelectorAll('.checkbox-producto').forEach(cb => {
                            if (cb !== this) {
                                cb.checked = false;
                            }
                        });
                        
                        // Obtener datos del producto de la fila seleccionada
                        // Estructura: cells[0]=Id_Producto, cells[1]=NombreProducto, cells[2]=Cantidad
                        const fila = this.closest('tr');
                        const idProducto = fila.cells[0].textContent.trim();
                        const nombreProducto = fila.cells[1].textContent.trim();
                        const cantidad = fila.cells[2].textContent.trim();
                        
                        // Guardar el producto seleccionado
                        productoSeleccionado = {
                            Id_Producto: idProducto,
                            NombreProducto: nombreProducto,
                            Cantidad: parseInt(cantidad) || 0
                        };
                        
                        // Cargar proveedores filtrados por el producto seleccionado
                        cargarProveedoresPorProducto(nombreProducto);
                    } else {
                        // Si se desmarca, limpiar la selección y la tabla de proveedores
                        productoSeleccionado = null;
                        limpiarProveedores();
                    }
                    
                    // Actualizar estado del botón
                    actualizarEstadoBotonGenerar();
                });
            });
        }

        // Función para cargar proveedores filtrados por producto
        async function cargarProveedoresPorProducto(nombreProducto) {
            if (!nombreProducto) {
                limpiarProveedores();
                return;
            }

            try {
                const response = await fetch(`../../Controlador/CUS14Negocio.php?action=obtener_proveedores&nombreProducto=${encodeURIComponent(nombreProducto)}`);
                const data = await response.json();
                
                if (data.success) {
                    proveedores = data.data || [];
                    renderProveedores(proveedores);
                } else {
                    console.error('Error al cargar proveedores:', data.message);
                    mostrarErrorProveedores('Error al cargar los proveedores');
                    proveedores = [];
                }
            } catch (error) {
                console.error('Error al cargar proveedores:', error);
                mostrarErrorProveedores('Error de conexión');
                proveedores = [];
            }
        }

        // Función para limpiar la tabla de proveedores
        function limpiarProveedores() {
            const tbody = document.getElementById('proveedoresTable');
            tbody.innerHTML = `
                <tr>
                    <td colspan="3" style="text-align: center; color: #666;">
                        Seleccione un producto para ver los proveedores
                    </td>
                </tr>
            `;
            proveedores = [];
        }

        // Función para renderizar proveedores
        function renderProveedores(proveedoresData) {
            const tbody = document.getElementById('proveedoresTable');
            tbody.innerHTML = '';
            
            if (!proveedoresData || proveedoresData.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="3" style="text-align: center; color: #666;">
                            No hay proveedores disponibles
                        </td>
                    </tr>
                `;
                return;
            }
            
            proveedoresData.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.RUC || ''}</td>
                    <td>${item.Empresa || item.NombreEmpresa || ''}</td>
                    <td>${item.Correo || item.Email || ''}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        function mostrarErrorProveedores(mensaje) {
            const tbody = document.getElementById('proveedoresTable');
            tbody.innerHTML = `
                <tr>
                    <td colspan="3" style="text-align: center; color: red;">
                        ${mensaje}
                    </td>
                </tr>
            `;
        }

        // Función para actualizar estado del botón Generar Solicitud
        function actualizarEstadoBotonGenerar() {
            const btnGenerar = document.getElementById('btnGenerarSolicitud');
            const tieneRequerimientoSeleccionado = requerimientosSeleccionados.length > 0;
            const tieneProductoSeleccionado = productoSeleccionado !== null;
            
            // El botón se habilita solo si hay un requerimiento Y un producto seleccionados
            btnGenerar.disabled = !(tieneRequerimientoSeleccionado && tieneProductoSeleccionado);
        }

        // Función para generar solicitud (ejecuta SP por cada proveedor)
        async function generarSolicitud() {
            if (requerimientosSeleccionados.length === 0) {
                alert('Seleccione un requerimiento evaluado');
                return;
            }
            if (!productoSeleccionado) {
                alert('Seleccione un producto');
                return;
            }
            if (!proveedores || proveedores.length === 0) {
                alert('No hay proveedores disponibles');
                return;
            }

            try {
                const btnGenerar = document.getElementById('btnGenerarSolicitud');
                btnGenerar.disabled = true;
                btnGenerar.textContent = 'Generando...';

                const payload = {
                    idReqEvaluacion: requerimientosSeleccionados[0],
                    idProducto: productoSeleccionado.Id_Producto,
                    producto: productoSeleccionado.NombreProducto,
                    cantidad: productoSeleccionado.Cantidad,
                    proveedores: proveedores
                };

                const response = await fetch('../../Controlador/CUS14Negocio.php?action=generar_solicitud_bd', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                if (data.success) {
                    alert(`Solicitud generada. Proveedores procesados: ${data.procesados || 0}`);
                    // Recargar desde BD la tabla de Solicitud Cotización
                    await cargarSolicitudesPendientes();

                    // Persistir bloqueos tras SP
                    const currentReq = requerimientosSeleccionados[0];
                    if (currentReq) {
                        setReqLock(String(currentReq));
                        if (productoSeleccionado && productoSeleccionado.Id_Producto) {
                            addDisabledProduct(String(currentReq), String(productoSeleccionado.Id_Producto));
                        }
                    }

                    // Manejo de bloqueos tras ejecutar SP
                    manejarBloqueosTrasGeneracion();
                } else {
                    alert('Error al generar la solicitud: ' + (data.message || ''));
                }

                btnGenerar.disabled = false;
                btnGenerar.textContent = 'Generar Solicitud';
            } catch (error) {
                console.error('Error al generar solicitud:', error);
                alert('Error al generar la solicitud');
                const btnGenerar = document.getElementById('btnGenerarSolicitud');
                btnGenerar.disabled = false;
                btnGenerar.textContent = 'Generar Solicitud';
            }
        }

        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        // Bloquea el producto seleccionado y mantiene bloqueado el requerimiento hasta terminar todos los productos
        async function manejarBloqueosTrasGeneracion() {
            try {
                // 1) Bloquear el checkbox del producto usado
                const prodChecked = document.querySelector('.checkbox-producto:checked');
                if (prodChecked) {
                    prodChecked.disabled = true;
                    prodChecked.checked = false;
                }

                // Limpiar selección de producto y proveedores
                productoSeleccionado = null;
                limpiarProveedores();

                // 2) Mantener bloqueados los checkboxes de requerimiento
                const reqChecks = Array.from(document.querySelectorAll('.checkbox-requerimiento'));
                const reqChecked = reqChecks.find(cb => cb.checked);

                // Contar productos pendientes (no deshabilitados)
                const productosPendientes = document.querySelectorAll('.checkbox-producto:not(:disabled)');

                if (productosPendientes.length > 0) {
                    // AÚN HAY PRODUCTOS PENDIENTES
                    // Bloquear todos los checkboxes de requerimiento
                    reqChecks.forEach(cb => cb.disabled = true);
                    if (reqChecked) {
                        reqChecked.checked = true;
                    }
                } else {
                    // TODOS LOS PRODUCTOS FUERON PROCESADOS
                    // Ejecutar UPDATE para cambiar estado a 'Solicitado'
                    const idReqEvaluacion = requerimientosSeleccionados[0];
                    
                    if (idReqEvaluacion) {
                        await ejecutarUpdateRequerimientoSolicitado(idReqEvaluacion);
                    }
                    
                    // Liberar requerimientos y limpiar tablas
                    reqChecks.forEach(cb => {
                        cb.disabled = false;
                        cb.checked = false;
                    });

                    // Limpiar tabla de productos
                    const tbody = document.getElementById('productosTable');
                    if (tbody) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="4" style="text-align: center; color: #666;">
                                    Seleccione un requerimiento evaluado
                                </td>
                            </tr>
                        `;
                    }

                    requerimientosSeleccionados = [];

                    // Limpiar persistencia de bloqueos
                    const lockedReq = getReqLock();
                    if (lockedReq) {
                        clearDisabledProducts(String(lockedReq));
                        clearReqLock();
                    }
                }

                // Recalcular estado del botón Generar
                actualizarEstadoBotonGenerar();
            } catch (e) {
                console.error('Error al manejar bloqueos tras generación:', e);
            }
        }

        // AGREGAR esta nueva función después de manejarBloqueosTrasGeneracion()
        async function ejecutarUpdateRequerimientoSolicitado(idReqEvaluacion) {
            try {
                const response = await fetch('../../Controlador/CUS14Negocio.php?action=actualizar_estado_solicitado', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ idReqEvaluacion })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Mostrar mensaje de éxito
                    alert('Requerimiento completado. Todos los productos han sido solicitados.');
                    
                    // Limpiar localStorage
                    clearDisabledProducts(idReqEvaluacion);
                    clearReqLock();
                    
                    // Recargar tabla de requerimientos evaluados
                    await cargarRequerimientosEvaluados();
                    
                    console.log('Estado actualizado a Solicitado para requerimiento:', idReqEvaluacion);
                } else {
                    console.error('Error al actualizar estado:', data.message);
                    alert('Error al actualizar el estado del requerimiento: ' + data.message);
                }
            } catch (error) {
                console.error('Error al ejecutar UPDATE:', error);
                alert('Error de conexión al actualizar el estado del requerimiento');
            }
        }
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        // Función para renderizar solicitud de cotización
        function renderSolicitudCotizacion(solicitudData) {
            const tbody = document.getElementById('solicitudCotizacionTable');
            tbody.innerHTML = '';
            
            if (!solicitudData || solicitudData.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" style="text-align: center; color: #666;">
                            No hay solicitudes generadas
                        </td>
                    </tr>
                `;
                return;
            }
            
            solicitudData.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.Id_Solicitud || item.IDsolicitud || '-'}</td>
                    <td>${item.RUC || ''}</td>
                    <td>${item.Empresa || ''}</td>
                    <td>${item.Correo || ''}</td>
                    <td>${item.Id_Producto || ''}</td>
                    <td>${item.Producto || item.NombreProducto || ''}</td>
                    <td>${item.Cantidad || ''}</td>
                    <td>${item.FechaEmision || ''}</td>
                    <td>${item.FechaCierre || ''}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Cargar solicitudes pendientes desde BD
        async function cargarSolicitudesPendientes() {
            try {
                const resp = await fetch('../../Controlador/CUS14Negocio.php?action=obtener_solicitudes_pendientes');
                const data = await resp.json();
                if (data.success) {
                    renderSolicitudCotizacion(data.data || []);
                } else {
                    renderSolicitudCotizacion([]);
                }
            } catch (e) {
                console.error('Error al cargar solicitudes pendientes:', e);
                renderSolicitudCotizacion([]);
            }
        }

        // Función para enviar solicitud
        async function enviarSolicitud() {
            if (solicitudCotizacion.length === 0) {
                alert('No hay solicitudes para enviar');
                return;
            }

            if (!confirm('¿Está seguro que desea enviar la solicitud de cotización?')) {
                return;
            }

            try {
                const btnEnviar = document.getElementById('btnEnviar');
                btnEnviar.disabled = true;
                btnEnviar.textContent = 'Enviando...';

                const response = await fetch('../../Controlador/CUS14Negocio.php?action=enviar_solicitud', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        requerimientos: requerimientosSeleccionados,
                        solicitud: solicitudCotizacion
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Solicitud enviada exitosamente');
                    // Limpiar formulario
                    limpiarFormulario();
                } else {
                    alert('Error al enviar la solicitud: ' + data.message);
                }

                btnEnviar.disabled = true;
                btnEnviar.textContent = 'Enviar';
            } catch (error) {
                console.error('Error al enviar solicitud:', error);
                alert('Error al enviar la solicitud');
                const btnEnviar = document.getElementById('btnEnviar');
                btnEnviar.disabled = false;
                btnEnviar.textContent = 'Enviar';
            }
        }

        // Función para limpiar formulario
        function limpiarFormulario() {
            // Desmarcar checkboxes de requerimientos
            document.querySelectorAll('.checkbox-requerimiento').forEach(cb => cb.checked = false);
            requerimientosSeleccionados = [];
            
            // Desmarcar checkboxes de productos
            document.querySelectorAll('.checkbox-producto').forEach(cb => cb.checked = false);
            productoSeleccionado = null;
            
            // Limpiar tablas
            const tbodyProductos = document.getElementById('productosTable');
            tbodyProductos.innerHTML = `
                <tr>
                    <td colspan="4" style="text-align: center; color: #666;">
                        Seleccione un requerimiento evaluado
                    </td>
                </tr>
            `;
            
            // Limpiar tabla de proveedores
            limpiarProveedores();
            
            const tbodySolicitud = document.getElementById('solicitudCotizacionTable');
            tbodySolicitud.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; color: #666;">
                        No hay solicitudes generadas
                    </td>
                </tr>
            `;
            
            productosSeleccionados = [];
            solicitudCotizacion = [];
            
            // Deshabilitar botones
            actualizarEstadoBotonGenerar();
            document.getElementById('btnEnviar').disabled = true;
        }

        function salir() {
            if (confirm('¿Está seguro que desea salir?')) {
                window.location.href = '../../index.php';
            }
        }
    </script>
</body>
</html>

