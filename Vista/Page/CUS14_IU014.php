<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Cotizaciones al Proveedor</title>
    <style>

        :root {
            /* Primarios */
            --primary: #93c5fd;
            --primary-600: #3b82f6;
            --primary-50: #eff6ff;
            --primary-100: #dbeafe;
            --primary-200: #bfdbfe;
            
            /* Secundarios */
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-900: #111827;
            
            /* Azul oscuro para texto en headers */
            --blue-900: #1e3a8a;
            --blue-800: #1e40af;
        }



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
            background-color: var(--primary-50); /* 🎨 Azul muy claro */
            border: 2px solid var(--primary-200);
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
            border-bottom: 3px solid var(--primary-600); /* 🎨 Línea azul */
            padding-bottom: 20px;
            background: linear-gradient(to bottom, var(--primary-50), white); /* Degradado sutil */
            border-radius: 8px 8px 0 0;
            padding: 20px;
        }

        .header h1 {
            color: var(--gray-900); /* 🎨 Gris oscuro */
            font-size: 2.5em;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .datetime {
            color: #666;
            font-size: 1.1em;
        }

        .section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            background-color: var(--gray-50); /* 🎨 Gris muy claro */
        }

        .section h2 {
            color: var(--gray-900); /* 🎨 Gris muy oscuro */
            margin-bottom: 15px;
            font-size: 1.3em;
            border-bottom: 3px solid var(--primary-600); /* 🎨 Línea azul */
            padding-bottom: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: var(--gray-700); /* 🎨 Gris oscuro */
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
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
            border: 1px solid #ddd;
            border-radius: 4px;
            position: relative;
            height: 285px;
            overflow-y: auto;
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            table-layout: auto;
        }

        .table-container thead {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 10;
            box-shadow: 0 2px 2px rgba(0,0,0,0.1);
        }

        /* Estilos generales de th SIN text-align por defecto */
        .table-container th {
            height: 45px;
            padding: 12px;
            border-bottom: 2px solid var(--primary-600);
            background-color: var(--primary-200); /* 🎨 Azul claro */
            font-weight: bold;
            color: var(--blue-900); /* 🎨 Azul oscuro */
            text-transform: uppercase; /* Opcional: mayúsculas */
            font-size: 13px;
            letter-spacing: 0.5px;
        }

        /* Estilos generales de td SIN text-align por defecto */
        .table-container td {
            height: 48px;
            padding: 12px;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }

        .table-container tbody tr:hover {
            background-color: var(--primary-50); /* 🎨 Azul muy claro al hover */
            transition: background-color 0.2s ease;
        }

        .table-container tbody tr.empty-row {
            height: 48px;
        }

        .table-container tbody tr.empty-row td {
            text-align: center;
            color: #666;
            font-style: italic;
        }

        /* ========================================
        📋 TABLA DE REQUERIMIENTOS - CENTRADA
        ======================================== */
        #requerimientosTable {
            table-layout: fixed;
        }

        #requerimientosTable th,
        #requerimientosTable td {
            text-align: center !important;
            vertical-align: middle !important;
        }

        #requerimientosTable th:nth-child(1),
        #requerimientosTable td:nth-child(1) {
            width: 70%;
        }

        #requerimientosTable th:nth-child(2),
        #requerimientosTable td:nth-child(2) {
            width: 30%;
        }

        #requerimientosTable .checkbox {
            transform: scale(1.3);
            cursor: pointer;
        }

        /* ========================================
        📧 TABLA DE PROVEEDORES - ADAPTATIVA
        ======================================== */
        #proveedoresTable th:nth-child(1),
        #proveedoresTable td:nth-child(1) {
            width: 120px;
            min-width: 120px;
            white-space: nowrap;
        }

        #proveedoresTable th:nth-child(2),
        #proveedoresTable td:nth-child(2) {
            width: 30%;
            min-width: 150px;
        }

        #proveedoresTable th:nth-child(3),
        #proveedoresTable td:nth-child(3) {
            width: auto;
            min-width: 250px;
            white-space: normal;
            word-break: break-word;
        }

        /* ========================================
        📦 TABLA DE PRODUCTOS
        ======================================== */
        #productosTable th:nth-child(1),
        #productosTable td:nth-child(1) {
            width: 100px;
            min-width: 100px;
        }

        #productosTable th:nth-child(2),
        #productosTable td:nth-child(2) {
            width: auto;
            min-width: 200px;
        }

        #productosTable th:nth-child(3),
        #productosTable td:nth-child(3) {
            width: 100px;
            min-width: 100px;
            text-align: center;
        }

        /* ========================================
        📊 TABLA DE HISTORIAL
        ======================================== */
        #solicitudCotizacionTable th:nth-child(1),
        #solicitudCotizacionTable td:nth-child(1) {
            width: 100px;
        }

        #solicitudCotizacionTable th:nth-child(2),
        #solicitudCotizacionTable td:nth-child(2) {
            width: 110px;
        }

        #solicitudCotizacionTable th:nth-child(3),
        #solicitudCotizacionTable td:nth-child(3) {
            width: 120px;
        }

        #solicitudCotizacionTable th:nth-child(4),
        #solicitudCotizacionTable td:nth-child(4) {
            width: auto;
            min-width: 150px;
        }

        #solicitudCotizacionTable th:nth-child(5),
        #solicitudCotizacionTable td:nth-child(5) {
            width: auto;
            min-width: 200px;
            white-space: normal;
            word-break: break-word;
        }

        #solicitudCotizacionTable th:nth-child(6),
        #solicitudCotizacionTable td:nth-child(6) {
            width: 80px;
            text-align: center;
        }

        /* ========================================
        OTRAS TABLAS - ALINEACIÓN IZQUIERDA
        ======================================== */
        #productosTable th,
        #productosTable td,
        #proveedoresTable th,
        #proveedoresTable td,
        #solicitudCotizacionTable th,
        #solicitudCotizacionTable td {
            text-align: left;
        }

        /* Excepciones: columnas específicas centradas */
        #productosTable th:nth-child(3),
        #productosTable td:nth-child(3),
        #solicitudCotizacionTable th:nth-child(6),
        #solicitudCotizacionTable td:nth-child(6) {
            text-align: center;
        }

        /* Filas seleccionadas */
        .fila-seleccionada {
            background-color: var(--primary-100) !important; /* 🎨 Azul claro */
            border-left: 4px solid var(--primary-600); /* 🎨 Borde azul */
            font-weight: bold;
        }

        .fila-seleccionada:hover {
            background-color: var(--primary-200) !important; /* 🎨 Azul más intenso */
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
            background-color: var(--primary-600); /* 🎨 Azul principal */
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            background-color: #2563eb; /* Azul más oscuro */
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
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

        /* 🔍 Ícono de lupa para ver PDF */
        .btn-ver-pdf {
            background-color: var(--primary-600);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-ver-pdf:hover {
            background-color: #2563eb;
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
        }

        .btn-ver-pdf:disabled {
            background-color: var(--gray-200);
            color: var(--gray-600);
            cursor: not-allowed;
            opacity: 0.5;
        }

        /* Ajustar ancho de columna PDF */
        #solicitudCotizacionTable th:nth-child(7),
        #solicitudCotizacionTable td:nth-child(7) {
            width: 60px;
            text-align: center;
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
                                <!-- ❌ ELIMINAR: <th>Seleccionar</th> -->
                            </tr>
                        </thead>
                        <tbody id="productosTable">
                            <tr>
                                <td colspan="3" style="text-align: center; color: #666;">
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
                                <th>Razón Social</th>
                                <th>Correo</th>
                            </tr>
                        </thead>
                        <tbody id="proveedoresTable">
                            <tr>
                                <td colspan="3" style="text-align: center; color: #666;">
                                    Seleccione un requerimiento evaluado
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="btn-generar">
            <button type="button" class="btn btn-primary" id="btnGenerarYEnviar" onclick="generarYEnviarSolicitud()" disabled>Generar y Enviar Solicitud</button>
        </div>

        <div class="section">
            <h2>Historial de Solicitudes Enviadas</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID Solicitud</th>
                            <th>ID Requerimiento</th>
                            <th>RUC</th>
                            <th>Empresa</th>
                            <th>Correo</th>
                            <th>Productos Solicitados</th>
                            <th>PDF</th> <!-- 🎯 NUEVA COLUMNA -->
                        </tr>
                    </thead>
                    <tbody id="solicitudCotizacionTable">
                        <tr>
                            <td colspan="7" style="text-align: center; color: #666;">
                                No hay solicitudes generadas
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="footer">
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
        let productosDelRequerimiento = []; // ✅ RENOMBRAR de productosSeleccionados
        let proveedores = [];
        let solicitudCotizacion = [];
        let proveedoresOriginales = []; // ✅ NUEVA: guardar proveedores completos
        let productoSeleccionado = null; // ✅ NUEVA: guardar producto seleccionado

        // Cargar datos al iniciar la página
        document.addEventListener('DOMContentLoaded', async function() {
            cargarDatosResponsable();
            await cargarRequerimientosEvaluados();
            limpiarProveedores();
            cargarSolicitudesEnviadas();
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
            
            // Event listeners para selección única
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
                    cargarDatosRequerimiento(); // ✅ NUEVA FUNCIÓN
                });
            });
        }

        async function cargarDatosRequerimiento() {
            if (requerimientosSeleccionados.length === 0) {
                limpiarTablasProductosYProveedores();
                actualizarEstadoBotonGenerar();
                return;
            }

            const idRequerimiento = requerimientosSeleccionados[0];
            
            // Cargar productos y proveedores en paralelo
            await Promise.all([
                cargarProductosRequerimiento(idRequerimiento),
                cargarProveedoresRequerimiento(idRequerimiento)
            ]);
            
            actualizarEstadoBotonGenerar();
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
        async function cargarProductosRequerimiento(idRequerimiento) {
            try {
                const response = await fetch(`../../Controlador/CUS14Negocio.php?action=obtener_productos_requerimiento&idRequerimiento=${encodeURIComponent(idRequerimiento)}`);
                const resultado = await response.json();
                
                if (resultado.success && resultado.data) {
                    productosDelRequerimiento = resultado.data.map(producto => ({
                        Id_Producto: producto.Id_Producto,
                        NombreProducto: producto.NombreProducto,
                        Cantidad: parseInt(producto.Cantidad || 0)
                    }));
                    renderProductos(productosDelRequerimiento);
                } else {
                    productosDelRequerimiento = [];
                    const tbody = document.getElementById('productosTable');
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="3" style="text-align: center; color: #666;">
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
                        <td colspan="3" style="text-align: center; color: red;">
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
                        <td colspan="3" style="text-align: center; color: #666;">
                            No hay productos para este requerimiento
                        </td>
                    </tr>
                `;
                return;
            }
            
            productos.forEach(producto => {
                const tr = document.createElement('tr');
                tr.style.cursor = 'pointer'; // ✅ Cambiar cursor
                tr.dataset.idProducto = producto.Id_Producto; // ✅ Guardar ID en el elemento
                
                tr.innerHTML = `
                    <td>${producto.Id_Producto || ''}</td>
                    <td>${producto.NombreProducto}</td>
                    <td>${producto.Cantidad}</td>
                `;
                
                // ✅ EVENTO CLICK EN LA FILA
                tr.addEventListener('click', function() {
                    toggleSeleccionProducto(this, producto);
                });
                
                tbody.appendChild(tr);
            });
        }

        // Función para seleccionar/deseleccionar producto
        async function toggleSeleccionProducto(filaElement, producto) {
            const tbody = document.getElementById('productosTable');
            const todasLasFilas = tbody.querySelectorAll('tr');
            
            // Si esta fila ya está seleccionada, deseleccionar
            if (filaElement.classList.contains('fila-seleccionada')) {
                filaElement.classList.remove('fila-seleccionada');
                productoSeleccionado = null;
                
                // Restaurar todos los proveedores del requerimiento
                renderProveedores(proveedoresOriginales);
            } else {
                // Deseleccionar todas las filas primero
                todasLasFilas.forEach(fila => fila.classList.remove('fila-seleccionada'));
                
                // Seleccionar esta fila
                filaElement.classList.add('fila-seleccionada');
                productoSeleccionado = producto;
                
                // Cargar proveedores que venden este producto específico
                await cargarProveedoresPorProducto(producto.Id_Producto);
            }
        }

        // Función para cargar proveedores de un producto específico
        async function cargarProveedoresPorProducto(idProducto) {
            try {
                const response = await fetch(`../../Controlador/CUS14Negocio.php?action=obtener_proveedores_por_producto&idProducto=${encodeURIComponent(idProducto)}`);
                const data = await response.json();
                
                if (data.success) {
                    const proveedoresFiltrados = data.data || [];
                    
                    if (proveedoresFiltrados.length === 0) {
                        const tbody = document.getElementById('proveedoresTable');
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="3" style="text-align: center; color: #ff6b6b;">
                                    ⚠️ No hay proveedores que vendan este producto
                                </td>
                            </tr>
                        `;
                    } else {
                        renderProveedores(proveedoresFiltrados);
                    }
                } else {
                    console.error('Error al cargar proveedores del producto:', data.message);
                    mostrarErrorProveedores('Error al filtrar proveedores');
                }
            } catch (error) {
                console.error('Error al cargar proveedores del producto:', error);
                mostrarErrorProveedores('Error de conexión');
            }
        }
        
        async function cargarProveedoresRequerimiento(idRequerimiento) {
            if (!idRequerimiento) {
                limpiarProveedores();
                return;
            }

            try {
                const response = await fetch(`../../Controlador/CUS14Negocio.php?action=obtener_proveedores&idRequerimiento=${encodeURIComponent(idRequerimiento)}`);
                const data = await response.json();
                
                if (data.success) {
                    proveedores = data.data || [];
                    proveedoresOriginales = [...proveedores]; // ✅ GUARDAR COPIA
                    renderProveedores(proveedores);
                } else {
                    console.error('Error al cargar proveedores:', data.message);
                    mostrarErrorProveedores('Error al cargar los proveedores');
                    proveedores = [];
                    proveedoresOriginales = []; // ✅ LIMPIAR COPIA
                }
            } catch (error) {
                console.error('Error al cargar proveedores:', error);
                mostrarErrorProveedores('Error de conexión');
                proveedores = [];
                proveedoresOriginales = []; // ✅ LIMPIAR COPIA
            }
        }
        
        // Función para limpiar la tabla de proveedores
        function limpiarProveedores() {
            const tbody = document.getElementById('proveedoresTable');
            tbody.innerHTML = `
                <tr>
                    <td colspan="3" style="text-align: center; color: #666;">
                        Seleccione un requerimiento evaluado
                    </td>
                </tr>
            `;
            proveedores = [];
        }

        function limpiarTablasProductosYProveedores() {
            const tbodyProductos = document.getElementById('productosTable');
            tbodyProductos.innerHTML = `
                <tr>
                    <td colspan="3" style="text-align: center; color: #666;">
                        Seleccione un requerimiento evaluado
                    </td>
                </tr>
            `;
            limpiarProveedores();
            productosDelRequerimiento = [];
            proveedores = [];
            proveedoresOriginales = []; // ✅ LIMPIAR COPIA
            productoSeleccionado = null; // ✅ LIMPIAR SELECCIÓN
        }

        // Función para renderizar proveedores
        function renderProveedores(proveedoresData) {
            const tbody = document.getElementById('proveedoresTable');
            tbody.innerHTML = '';
            
            if (!proveedoresData || proveedoresData.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="3" style="text-align: center; color: #666;">
                            No hay proveedores disponibles para este requerimiento
                        </td>
                    </tr>
                `;
                return;
            }
            
            proveedoresData.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.Id_NumRuc || ''}</td>
                    <td>${item.des_RazonSocial || ''}</td>
                    <td>${item.Correo || ''}</td>
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

        // Función para actualizar estado del botón Generar Y Enviar Solicitud
        function actualizarEstadoBotonGenerar() {
            const btnGenerar = document.getElementById('btnGenerarYEnviar');
            const tieneRequerimiento = requerimientosSeleccionados.length > 0;
            const tieneProductos = productosDelRequerimiento.length > 0;
            const tieneProveedores = proveedores.length > 0;
            
            btnGenerar.disabled = !(tieneRequerimiento && tieneProductos && tieneProveedores);
        }


        // ✅ REEMPLAZAR FUNCIÓN COMPLETA
        async function generarYEnviarSolicitud() {
            // ============================================
            // 1. VALIDACIONES INICIALES
            // ============================================
            if (requerimientosSeleccionados.length === 0) {
                alert('Seleccione un requerimiento evaluado');
                return;
            }
            
            if (productosDelRequerimiento.length === 0) {
                alert('No hay productos en este requerimiento');
                return;
            }
            
            if (!proveedores || proveedores.length === 0) {
                alert('No hay proveedores disponibles');
                return;
            }

            if (!confirm(`¿Generar y enviar solicitudes a ${proveedores.length} proveedores?\n\nSe crearán las solicitudes, generarán PDFs y enviarán correos automáticamente.`)) {
                return;
            }

            const btnGenerar = document.getElementById('btnGenerarYEnviar');
            const textoOriginal = btnGenerar.textContent;

            try {
                btnGenerar.disabled = true;
                btnGenerar.textContent = '⏳ Generando solicitudes...';

                const idRequerimiento = requerimientosSeleccionados[0];
                let solicitudesGeneradas = 0;
                let proveedoresSinProductos = 0;
                let detalleGeneracion = [];

                // ============================================
                // PASO 1: GENERAR SOLICITUDES EN BD (Estado: Pendiente)
                // ============================================
                for (const proveedor of proveedores) {
                    const payload = {
                        idReqEvaluacion: idRequerimiento,
                        ruc: proveedor.Id_NumRuc,
                        empresa: proveedor.des_RazonSocial,
                        correo: proveedor.Correo
                    };

                    const response = await fetch('../../Controlador/CUS14Negocio.php?action=generar_solicitud_por_proveedor', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        if (data.productosInsertados > 0) {
                            solicitudesGeneradas++;
                            detalleGeneracion.push(`✅ ${proveedor.des_RazonSocial}: ${data.productosInsertados} producto(s)`);
                        } else {
                            proveedoresSinProductos++;
                            detalleGeneracion.push(`⚠️ ${proveedor.des_RazonSocial}: No vende estos productos`);
                        }
                    } else {
                        console.error(`Error con proveedor ${proveedor.des_RazonSocial}:`, data.message);
                        detalleGeneracion.push(`❌ ${proveedor.des_RazonSocial}: Error al generar`);
                    }
                }

                // Si no se generó ninguna solicitud, detenemos
                if (solicitudesGeneradas === 0) {
                    alert('⚠️ No se generaron solicitudes.\n\nLos proveedores seleccionados no venden estos productos.');
                    btnGenerar.disabled = false;
                    btnGenerar.textContent = textoOriginal;
                    return;
                }

                // ============================================
                // PASO 2: GENERAR PDFs Y ENVIAR CORREOS
                // ============================================
                btnGenerar.textContent = '📧 Enviando correos...';
                
                const responseEnvio = await fetch('../../Controlador/CUS14Negocio.php?action=enviar_correos_cotizacion', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });

                const dataEnvio = await responseEnvio.json();

                if (!dataEnvio.success) {
                    alert('⚠️ Solicitudes creadas pero hubo un error al enviar correos:\n' + dataEnvio.message);
                    btnGenerar.disabled = false;
                    btnGenerar.textContent = textoOriginal;
                    return;
                }

                // ============================================
                // PASO 3: ACTUALIZAR ESTADOS A "ENVIADO" ⭐ NUEVO
                // ============================================
                if (dataEnvio.exitosos > 0) {
                    btnGenerar.textContent = '🔄 Actualizando estados...';
                    
                    const responseUpdate = await fetch('../../Controlador/CUS14Negocio.php?action=actualizar_estado_solicitudes_enviadas', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });

                    const dataUpdate = await responseUpdate.json();
                    console.log('✅ Estados actualizados:', dataUpdate);
                }

                // ============================================
                // PASO 4: ACTUALIZAR ESTADO DEL REQUERIMIENTO
                // ============================================
                btnGenerar.textContent = '📝 Finalizando...';
                
                await fetch('../../Controlador/CUS14Negocio.php?action=actualizar_estado_solicitado', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ idReqEvaluacion: idRequerimiento })
                });

                // ============================================
                // PASO 5: PREPARAR MENSAJE DE RESULTADO
                // ============================================
                let mensaje = `🎉 PROCESO COMPLETADO\n\n`;
                mensaje += `📊 RESUMEN:\n`;
                mensaje += `📝 Solicitudes creadas: ${solicitudesGeneradas}\n`;
                mensaje += `📧 Correos enviados: ${dataEnvio.exitosos}\n`;
                
                if (proveedoresSinProductos > 0) {
                    mensaje += `⚠️ Proveedores sin productos: ${proveedoresSinProductos}\n`;
                }
                
                if (dataEnvio.fallidos > 0) {
                    mensaje += `❌ Fallos en envío: ${dataEnvio.fallidos}\n`;
                }
                
                mensaje += `\n📋 DETALLE DE GENERACIÓN:\n${detalleGeneracion.join('\n')}`;
                
                if (dataEnvio.detalles && dataEnvio.detalles.length > 0) {
                    mensaje += `\n\n📧 DETALLE DE ENVÍOS:\n`;
                    dataEnvio.detalles.forEach(detalle => {
                        if (detalle.estado === 'exitoso') {
                            mensaje += `✅ ${detalle.proveedor} - Enviado\n`;
                        } else {
                            mensaje += `❌ Solicitud ${detalle.idSolicitud}: ${detalle.mensaje}\n`;
                        }
                    });
                }

                // ============================================
                // PASO 6: MOSTRAR MENSAJE Y ESPERAR CONFIRMACIÓN
                // ============================================
                btnGenerar.disabled = false;
                btnGenerar.textContent = textoOriginal;
                
                alert(mensaje);
                
                // ============================================
                // PASO 7: DESPUÉS DE ACEPTAR, RECARGAR TABLAS ⭐ MEJORADO
                // ============================================
                btnGenerar.disabled = true;
                btnGenerar.textContent = '🔄 Actualizando tablas...';

                // ⏱️ Esperar 1 segundo para que la BD termine de actualizar
                await new Promise(resolve => setTimeout(resolve, 1000));

                // Recargar las tablas en paralelo
                await Promise.all([
                    cargarRequerimientosEvaluados(),
                    cargarSolicitudesEnviadas()
                ]);

                // Limpiar formulario
                limpiarFormulario();

                // Restaurar botón
                btnGenerar.textContent = textoOriginal;

            } catch (error) {
                console.error('Error completo:', error);
                alert('❌ Error en el proceso:\n' + error.message);
                
                // Limpiar formulario (esto deshabilita el botón)
                limpiarFormulario();

                // Restaurar solo el texto
                btnGenerar.textContent = textoOriginal;
                
                // Intentar recargar la tabla de todos modos
                try {
                    await cargarSolicitudesEnviadas();
                } catch (e) {
                    console.error('Error al recargar tabla:', e);
                }
            }
        }
        
        // Función para renderizar solicitud de cotización
        function renderSolicitudCotizacion(solicitudData) {
            const tbody = document.getElementById('solicitudCotizacionTable');
            tbody.innerHTML = '';
            
            if (!solicitudData || solicitudData.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align: center; color: #666;">
                            No hay solicitudes generadas
                        </td>
                    </tr>
                `;
                return;
            }
            
            solicitudData.forEach(item => {
                const tr = document.createElement('tr');
                
                // 🎯 Botón de lupa para ver PDF
                let btnPDF = '';
                if (item.RutaPDF) {
                    btnPDF = `<button class="btn-ver-pdf" onclick="verPDF('${item.RutaPDF}', ${item.IDsolicitud})" title="Ver PDF">
                                🔍
                            </button>`;
                } else {
                    btnPDF = `<button class="btn-ver-pdf" disabled title="PDF no disponible">❌</button>`;
                }
                
                tr.innerHTML = `
                    <td>${item.IDsolicitud || '-'}</td>
                    <td>${item.Id_ReqEvaluacion || '-'}</td>
                    <td>${item.RUC || ''}</td>
                    <td>${item.Empresa || ''}</td>
                    <td>${item.Correo || ''}</td>
                    <td style="text-align: center;">${item.Productos || 0}</td>
                    <td style="text-align: center;">${btnPDF}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        // 🎯 NUEVA FUNCIÓN: Ver PDF en nueva pestaña
        function verPDF(rutaPDF, idSolicitud) {
            if (!rutaPDF) {
                alert('❌ PDF no disponible para esta solicitud');
                return;
            }
            
            // Construir URL relativa desde la raíz del proyecto
            const urlPDF = '../../' + rutaPDF; // Ajusta según tu estructura
            
            // Abrir en nueva pestaña
            window.open(urlPDF, '_blank');
        }

        // Cargar solicitudes enviadas desde BD
        async function cargarSolicitudesEnviadas() {  // ← Cambiar nombre
            try {
                const resp = await fetch('../../Controlador/CUS14Negocio.php?action=obtener_solicitudes_enviadas');  // ← Cambiar action
                const data = await resp.json();
                if (data.success) {
                    renderSolicitudCotizacion(data.data || []);
                } else {
                    renderSolicitudCotizacion([]);
                }
            } catch (e) {
                console.error('Error al cargar solicitudes enviadas:', e);
                renderSolicitudCotizacion([]);
            }
        }
        
        // Función para limpiar formulario
        function limpiarFormulario() {
            // Desmarcar checkboxes de requerimientos
            document.querySelectorAll('.checkbox-requerimiento').forEach(cb => cb.checked = false);
            requerimientosSeleccionados = [];
            
            // Limpiar tablas
            const tbodyProductos = document.getElementById('productosTable');
            tbodyProductos.innerHTML = `
                <tr>
                    <td colspan="3" style="text-align: center; color: #666;">
                        Seleccione un requerimiento evaluado
                    </td>
                </tr>
            `;
            
            // Limpiar tabla de proveedores
            limpiarProveedores();
            
            productosDelRequerimiento = [];
            proveedores = [];
            solicitudCotizacion = [];
            
            // Deshabilitar botones
            actualizarEstadoBotonGenerar();
        }

        function salir() {
            if (confirm('¿Está seguro que desea salir?')) {
                window.location.href = '../../index.php';
            }
        }
    </script>
</body>
</html>

