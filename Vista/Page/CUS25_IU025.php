<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consolidación de Entrega</title>
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
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }

        .repartidor-top {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .repartidor-top .form-row {
            margin-bottom: 0;
        }

        .repartidor-top .form-group {
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
        }

        /* Estilos específicos para la tabla de Pedidos Asignados */
        .pedidos-asignados-container {
            max-height: 240px; /* Altura máxima para mostrar aproximadamente 6 filas */
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .pedidos-asignados-container table {
            margin-bottom: 0;
        }

        .pedidos-asignados-container thead {
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

        .selected-row {
            background-color: #e3f2fd !important;
        }

        .image-upload {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .sections-row {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .destinatario-section {
            flex: 2;
            margin-bottom: 0;
        }

        .foto-direccion-section {
            flex: 1;
            margin-bottom: 0;
        }

        .photos-container {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .photo-section {
            flex: 1;
            margin-bottom: 0;
        }

        .photo-section .section {
            margin-bottom: 0;
        }

        .image-placeholder {
            width: 200px;
            height: 200px;
            border: 2px dashed #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f9f9f9;
            color: #999;
            font-size: 14px;
        }

        .upload-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
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

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn:disabled {
            background-color: #6c757d;
            color: #fff;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .btn:disabled:hover {
            background-color: #6c757d;
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

        .checkbox {
            transform: scale(1.2);
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .image-upload {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .upload-buttons {
                flex-direction: row;
            }

            .sections-row {
                flex-direction: column;
                gap: 20px;
            }

            .photos-container {
                flex-direction: column;
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <form id="entregaForm" enctype="multipart/form-data">
    <div class="container">
        <!-- Campos del repartidor en la parte superior -->
        <div class="repartidor-top">
            <div class="form-row">
                <div class="form-group">
                    <label for="repartidorId">ID Repartidor:</label>
                    <input type="text" id="repartidorId" value="" readonly>
                </div>
                <div class="form-group">
                    <label for="repartidorNombre">Nombre:</label>
                    <input type="text" id="repartidorNombre" value="" readonly>
                </div>
                <div class="form-group">
                    <label for="repartidorApellido">Ap. Paterno:</label>
                    <input type="text" id="repartidorApellido" value="" readonly>
                </div>
            </div>
        </div>

        <div class="header">
            <h1>CUS25 - CONSOLIDACION DE ENTREGA</h1>
            <div class="datetime">
                <span id="currentDate"></span> - <span id="currentTime"></span>
            </div>
        </div>

        <div class="section">
            <h2>Orden Asignada: <span id="ordenAsignadaLabel" style="font-weight:600; color:#333;">-</span></h2>
            <div class="table-container pedidos-asignados-container">
                <table>
                    <thead>
                        <tr>
                            <th>Orden de Pedido</th>
                            <th>Guía de Remisión</th>
                            <th>
                                Distrito
                                <button type="button" id="btnOrdenDistrito" class="btn btn-secondary" style="padding:4px 8px; margin-left:6px; font-size:12px;"
                                    onclick="toggleOrdenDistrito()">A-Z</button>
                            </th>
                            <th>Dirección</th>
                        </tr>
                    </thead>
                    <tbody id="direccionesTable">
                        <tr>
                        <td colspan="4" style="text-align: center; color: #666;">
                                Cargando direcciones...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section">
            <h2>Lista de Pedido</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Productos</th>
                            <th>Cantidad</th>
                        </tr>
                    </thead>
                    <tbody id="pedidosTable">
                        <tr>
                            <td colspan="2" style="text-align: center; color: #666;">
                                Seleccione una dirección para ver los pedidos
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sections-row">
            <div class="section destinatario-section">
                <h2>Destinatario</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="clienteDni">DNI:</label>
                        <input type="text" id="clienteDni" value="" readonly>
                    </div>
                    <div class="form-group">
                        <label for="clienteNombre">Nombre Completo:</label>
                        <input type="text" id="clienteNombre" value="" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="clienteCelular">Celular:</label>
                        <input type="text" id="clienteCelular" value="" readonly>
                    </div>
                    <div class="form-group">
                        <label for="clienteDireccion">Dirección:</label>
                        <input type="text" id="clienteDireccion" value="" readonly>
                    </div>
                </div>
            </div>

            <div class="section foto-direccion-section">
                <h2>Foto de la Dirección de Entrega</h2>
                <div class="image-upload">
                    <div class="image-placeholder" id="direccion-placeholder">
                        <span>&#128247;</span>
                    </div>
                    <div class="upload-buttons">
                        <button type="button" class="btn btn-primary" onclick="uploadImage('direccion')">Subir</button>
                        <button type="button" class="btn btn-danger" onclick="deleteImage('direccion')">Eliminar</button>
                    </div>
                </div>
                <input type="file" id="fotoDireccion" name="fotoDireccion" accept=".jpg,.jpeg,.png" style="display: none;">
            </div>
        </div>

        <div class="section">
            <h2>Estado de la entrega</h2>
            <div class="form-group">
                <label for="estadoEntrega">Estado:</label>
                <select id="estadoEntrega">
                    <option value="" selected disabled hidden>Seleccione estado...</option>
                    <option value="entregado">Entregado</option>
                    <option value="no-entregado">No entregado</option>
                </select>
            </div>
        </div>

        <div class="photos-container">
            <div class="photo-section">
                <div class="section">
                    <h2>DNI del destinatario o receptor</h2>
                    <div class="image-upload">
                        <div class="image-placeholder" id="dni-placeholder">
                            <span>&#128247;</span>
                        </div>
                        <div class="upload-buttons">
                            <button type="button" class="btn btn-primary" onclick="uploadImage('dni')">Subir</button>
                            <button type="button" class="btn btn-danger" onclick="deleteImage('dni')">Eliminar</button>
                        </div>
                    </div>
                    <input type="file" id="fotoDni" name="fotoDni" accept=".jpg,.jpeg,.png" style="display: none;">
                </div>
            </div>
            
            <div class="photo-section">
                <div class="section">
                    <h2>Foto de Entrega</h2>
                    <div class="image-upload">
                        <div class="image-placeholder" id="entrega-placeholder">
                            <span>&#128247;</span>
                        </div>
                        <div class="upload-buttons">
                            <button type="button" class="btn btn-primary" onclick="uploadImage('entrega')">Subir</button>
                            <button type="button" class="btn btn-danger" onclick="deleteImage('entrega')">Eliminar</button>
                        </div>
                    </div>
                    <input type="file" id="fotoEntrega" name="fotoEntrega" accept=".jpg,.jpeg,.png" style="display: none;">
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Observaciones</h2>
            <div class="form-group">
                <label for="observaciones">Observaciones:</label>
                <select id="observaciones">
                    <option value="" selected disabled hidden>Seleccione observación...</option>
                </select>
            </div>
        </div>

        <div class="footer">
            <button type="submit" class="btn btn-primary" disabled>Registrar</button>
            <button type="button" class="btn btn-secondary" onclick="salir()">Salir</button>
        </div>
    </div>
    </form>

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

        // Variables globales para almacenar datos
        let todasLasDirecciones = [];
        let direccionesOriginal = [];
        let ordenDistritoActivo = false;
        let direccionesFiltradas = [];

        // Cargar datos al iniciar la página
        document.addEventListener('DOMContentLoaded', function() {
            // Limpiar campos del destinatario al cargar la página
            limpiarCamposDestinatario();
            
            // Configurar opciones iniciales del combobox de observaciones
            actualizarOpcionesObservaciones();
            
            // Agregar event listener al combobox de estado de entrega
            const estadoEntregaSelect = document.getElementById('estadoEntrega');
            if (estadoEntregaSelect) {
                estadoEntregaSelect.addEventListener('change', actualizarOpcionesObservaciones);
            }
            
            // Agregar event listener al combobox de observaciones
            const observacionesSelect = document.getElementById('observaciones');
            if (observacionesSelect) {
                observacionesSelect.addEventListener('change', updateRegistrarState);
            }
            
            cargarDatosRepartidor();
            // Si cambia el ID del repartidor, recargar direcciones
            const repartidorIdInput = document.getElementById('repartidorId');
            if (repartidorIdInput) {
                repartidorIdInput.addEventListener('input', recargarDireccionesPorRepartidor);
                repartidorIdInput.addEventListener('change', recargarDireccionesPorRepartidor);
            }

            // Cargar Orden Asignada ante cambios manuales del ID
            if (repartidorIdInput) {
                repartidorIdInput.addEventListener('input', cargarOrdenAsignada);
                repartidorIdInput.addEventListener('change', cargarOrdenAsignada);
            }
        });

        // Función para cargar todas las direcciones al inicio
        async function cargarDatosIniciales() {
            try {
                // Cargar datos del repartidor
                await cargarDatosRepartidor();
                
                // Cargar todas las direcciones del repartidor actual
                const idTrabajador = document.getElementById('repartidorId').value;
                const responseDirecciones = await fetch(`../Controlador/CUS25Negocio.php?action=obtener_direcciones&idTrabajador=${encodeURIComponent(idTrabajador)}`);
                const dataDirecciones = await responseDirecciones.json();
                
                if (dataDirecciones.success) {
                    todasLasDirecciones = Array.isArray(dataDirecciones.data) ? dataDirecciones.data : [];
                    direccionesOriginal = [...todasLasDirecciones];
                    ordenDistritoActivo = false;
                    renderDirecciones(todasLasDirecciones);
                } else {
                    console.error('Error al cargar direcciones:', dataDirecciones.message);
                    mostrarError('Error al cargar las direcciones');
                }
                
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de conexión');
            }
        }

        // Recargar todas las direcciones cuando cambie el repartidor
        async function recargarDireccionesPorRepartidor() {
            try {
                const idTrabajador = document.getElementById('repartidorId').value;
                const responseDirecciones = await fetch(`../Controlador/CUS25Negocio.php?action=obtener_direcciones&idTrabajador=${encodeURIComponent(idTrabajador)}`);
                const dataDirecciones = await responseDirecciones.json();
                if (dataDirecciones.success) {
                    todasLasDirecciones = Array.isArray(dataDirecciones.data) ? dataDirecciones.data : [];
                    direccionesOriginal = [...todasLasDirecciones];
                    ordenDistritoActivo = false;
                    renderDirecciones(todasLasDirecciones);
                } else {
                    mostrarError('Error al recargar direcciones');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de conexión');
            }
        }

        // Función para cargar datos del repartidor
        async function cargarDatosRepartidor() {
            try {
                const response = await fetch('../../Controlador/CUS25Negocio.php?action=obtener_repartidor');
                const data = await response.json();
                
                if (data.success) {
                    // Llenar los campos del repartidor con datos de la base de datos
                    document.getElementById('repartidorId').value = data.data.id_Trabajador;
                    document.getElementById('repartidorNombre').value = data.data.des_nombreTrabajador;
                    document.getElementById('repartidorApellido').value = data.data.des_apepatTrabajador;
                    
                    console.log('Datos del repartidor cargados:', data.data);
                    
                    // Cargar direcciones después de cargar los datos del repartidor
                    await cargarDirecciones(data.data.id_Trabajador);

                    // Cargar Orden Asignada ahora que tenemos el ID del repartidor
                    cargarOrdenAsignada();
                } else {
                    console.error('Error al cargar datos del repartidor:', data.message);
                    // Mantener valores por defecto si hay error
                }
            } catch (error) {
                console.error('Error al cargar datos del repartidor:', error);
                // Mantener valores por defecto si hay error
            }
        }

        // Función para cargar direcciones
        async function cargarDirecciones(idTrabajador) {
            try {
                const response = await fetch(`../../Controlador/CUS25Negocio.php?action=obtener_direcciones&idTrabajador=${encodeURIComponent(idTrabajador)}`);
                const data = await response.json();
                
                if (data.success) {
                    renderDirecciones(data.data);
                    console.log('Direcciones cargadas:', data.data);
                } else {
                    console.error('Error al cargar direcciones:', data.message);
                    mostrarError('Error al cargar las direcciones');
                }
            } catch (error) {
                console.error('Error al cargar direcciones:', error);
                mostrarError('Error de conexión');
            }
        }

        // Habilitar/Deshabilitar botón Registrar según selección, foto de dirección y estado
        function updateRegistrarState() {
            try {
                const submitBtn = document.querySelector('button[type="submit"]');
                
                // Condiciones básicas (siempre requeridas)
                const hasSelectedRow = !!document.querySelector('.pedidos-asignados-container tbody tr.selected-row');
                const fotoDireccionInput = document.getElementById('fotoDireccion');
                const hasFotoDireccion = fotoDireccionInput && fotoDireccionInput.files && fotoDireccionInput.files.length > 0;
                const estadoEntrega = document.getElementById('estadoEntrega').value;
                
                // Si no se cumplen las condiciones básicas, deshabilitar
                if (!hasSelectedRow || !hasFotoDireccion || !estadoEntrega) {
                    submitBtn.disabled = true;
                    return;
                }
                
                // Condiciones específicas según el estado
                let condicionesEspecificas = false;
                
                if (estadoEntrega === 'entregado') {
                    // Para "Entregado": requiere foto DNI + foto Entrega + observación
                    const fotoDniInput = document.getElementById('fotoDni');
                    const fotoEntregaInput = document.getElementById('fotoEntrega');
                    const observaciones = document.getElementById('observaciones').value;
                    
                    const hasFotoDni = fotoDniInput && fotoDniInput.files && fotoDniInput.files.length > 0;
                    const hasFotoEntrega = fotoEntregaInput && fotoEntregaInput.files && fotoEntregaInput.files.length > 0;
                    const hasObservacion = observaciones && observaciones !== '';
                    
                    condicionesEspecificas = hasFotoDni && hasFotoEntrega && hasObservacion;
                    
                } else if (estadoEntrega === 'no-entregado') {
                    // Para "No entregado": solo requiere observación
                    const observaciones = document.getElementById('observaciones').value;
                    condicionesEspecificas = observaciones && observaciones !== '';
                }
                
                // Habilitar solo si se cumplen todas las condiciones
                submitBtn.disabled = !condicionesEspecificas;
                
            } catch (e) {
                console.error('Error actualizando estado del botón Registrar:', e);
            }
        }

        // Finalizar orden de asignación cuando no hay filas en la tabla
        async function finalizarOrdenAsignacionSiEsNecesario() {
            try {
                const tbody = document.getElementById('direccionesTable');
                const filas = tbody.querySelectorAll('tr');
                const tieneFilasConDatos = Array.from(filas).some(fila => {
                    const celdas = fila.querySelectorAll('td');
                    return celdas.length > 0 && !celdas[0].textContent.includes('No hay direcciones') && !celdas[0].textContent.includes('Cargando');
                });

                if (!tieneFilasConDatos) {
                    const ordenAsignadaLabel = document.getElementById('ordenAsignadaLabel');
                    const idOrdenAsignacion = ordenAsignadaLabel ? ordenAsignadaLabel.textContent.trim() : null;
                    
                    if (idOrdenAsignacion && idOrdenAsignacion !== '-') {
                        console.log('No hay filas en la tabla, finalizando orden de asignación:', idOrdenAsignacion);
                        
                        const response = await fetch(`../../Controlador/CUS25Negocio.php?action=finalizar_orden_asignacion&idOrdenAsignacion=${encodeURIComponent(idOrdenAsignacion)}`);
                        const data = await response.json();
                        
                        if (data.success) {
                            console.log('Orden de asignación finalizada exitosamente');
                        } else {
                            console.error('Error al finalizar orden de asignación:', data.message);
                        }
                    }
                }
            } catch (error) {
                console.error('Error al verificar si finalizar orden de asignación:', error);
            }
        }

        // Cargar Id_OrdenAsignacion en el label
        async function cargarOrdenAsignada() {
            try {
                const idTrabajador = document.getElementById('repartidorId').value;
                const label = document.getElementById('ordenAsignadaLabel');
                if (!idTrabajador) {
                    label.textContent = '-';
                    return;
                }
                const resp = await fetch(`../../Controlador/CUS25Negocio.php?action=obtener_orden_asignacion&idTrabajador=${encodeURIComponent(idTrabajador)}`);
                const data = await resp.json();
                label.textContent = data.success && data.data ? data.data : '-';
            } catch (err) {
                console.error('Error al cargar Orden Asignada:', err);
                const label = document.getElementById('ordenAsignadaLabel');
                if (label) label.textContent = '-';
            }
        }

        // Renderizar direcciones en la tabla
        function renderDirecciones(direccionesData) {
            const tbody = document.getElementById('direccionesTable');
            tbody.innerHTML = '';
            
            if (!direccionesData || direccionesData.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" style="text-align: center; color: #666;">
                            No hay direcciones disponibles
                        </td>
                    </tr>
                `;
                
                // Verificar si debe finalizar la orden de asignación
                setTimeout(() => finalizarOrdenAsignacionSiEsNecesario(), 100);
                return;
            }
            
            direccionesData.forEach(item => {
                const tr = document.createElement('tr');
                tr.onclick = () => selectAddress(tr);
                tr.innerHTML = `
                    <td>${item.OrdenPedido}</td>
                    <td>${item.GuiaRemision || ''}</td>
                    <td>${item.Distrito}</td>
                    <td>${item.Direccion}</td>
                `;
                tbody.appendChild(tr);
            });
            
            // Guardar datos para uso posterior
            todasLasDirecciones = Array.isArray(direccionesData) ? direccionesData : [];
            if (!ordenDistritoActivo) {
                direccionesOriginal = [...todasLasDirecciones];
            }
        }
        // Alternar orden por Distrito A-Z y volver a original
        function toggleOrdenDistrito() {
            const btn = document.getElementById('btnOrdenDistrito');
            if (!ordenDistritoActivo) {
                const ordenadas = [...todasLasDirecciones].sort((a, b) => {
                    const ad = (a.Distrito || '').toString().toLowerCase();
                    const bd = (b.Distrito || '').toString().toLowerCase();
                    if (ad < bd) return -1; if (ad > bd) return 1; return 0;
                });
                ordenDistritoActivo = true;
                btn.textContent = 'Original';
                renderDirecciones(ordenadas);
            } else {
                ordenDistritoActivo = false;
                btn.textContent = 'A-Z';
                renderDirecciones(direccionesOriginal);
            }
        }



        // Mostrar error
        function mostrarError(mensaje) {
            const tbody = document.getElementById('direccionesTable');
            tbody.innerHTML = `
                <tr>
                    <td colspan="3" style="text-align: center; color: red;">
                        ${mensaje}
                    </td>
                </tr>
            `;
        }

        async function selectAddress(row) {
            document.querySelectorAll('tbody tr').forEach(r => r.classList.remove('selected-row'));
            row.classList.add('selected-row');

            // Limpiar campos del destinatario antes de cargar nuevos datos
            limpiarCamposDestinatario();

            // Obtener el ID de la orden de pedido de la tercera columna
            const idOrdenPedido = row.cells[0] ? row.cells[0].textContent.trim() : '';
            if (!idOrdenPedido) return;

            const pedidosBody = document.getElementById('pedidosTable');
            if (!pedidosBody) return;
            
            // Mostrar mensaje de carga
            pedidosBody.innerHTML = `
                <tr>
                    <td colspan="2" style="text-align: center; color: #666;">Cargando productos...</td>
                </tr>
            `;

            try {
                // Cargar productos y destinatario en paralelo
                const [productosResponse, destinatarioResponse] = await Promise.all([
                    fetch(`../../Controlador/CUS25Negocio.php?action=obtener_productos&idOrdenPedido=${encodeURIComponent(idOrdenPedido)}`),
                    fetch(`../../Controlador/CUS25Negocio.php?action=obtener_destinatario&idOrdenPedido=${encodeURIComponent(idOrdenPedido)}`)
                ]);

                const productosData = await productosResponse.json();
                const destinatarioData = await destinatarioResponse.json();

                // Procesar productos
                if (productosData.success) {
                    renderProductosPorOrden(productosData.data);
                } else {
                    pedidosBody.innerHTML = `
                        <tr>
                            <td colspan="2" style="text-align: center; color: red;">${productosData.message || 'Error al cargar productos'}</td>
                        </tr>
                    `;
                }

                // Procesar destinatario
                if (destinatarioData.success) {
                    llenarCamposDestinatario(destinatarioData.data);
                } else {
                    console.error('Error al cargar destinatario:', destinatarioData.message);
                }

            } catch (error) {
                console.error(error);
                pedidosBody.innerHTML = `
                    <tr>
                        <td colspan="2" style="text-align: center; color: red;">Error de conexión</td>
                    </tr>
                `;
            }
            updateRegistrarState();
        }

        function renderProductosPorOrden(productos) {
            const pedidosBody = document.getElementById('pedidosTable');
            pedidosBody.innerHTML = '';
            if (!productos || productos.length === 0) {
                pedidosBody.innerHTML = `
                    <tr>
                        <td colspan="2" style="text-align: center; color: #666;">No hay productos para esta orden</td>
                    </tr>
                `;
                return;
            }
            productos.forEach(producto => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${producto.NombreProducto}</td>
                    <td>${producto.Cantidad}</td>
                `;
                pedidosBody.appendChild(tr);
            });
        }

        // Función para limpiar los campos del destinatario
        function limpiarCamposDestinatario() {
            try {
                document.getElementById('clienteDni').value = '';
                document.getElementById('clienteNombre').value = '';
                document.getElementById('clienteCelular').value = '';
                document.getElementById('clienteDireccion').value = '';
                
                console.log('Campos del destinatario limpiados');
            } catch (error) {
                console.error('Error al limpiar campos del destinatario:', error);
            }
        }

        // Función para manejar el estado de los botones de imágenes según el estado de entrega
        function manejarBotonesImagenes(estadoEntrega) {
            try {
                // Botones de DNI del cliente
                const btnSubirDni = document.querySelector('button[onclick="uploadImage(\'dni\')"]');
                const btnEliminarDni = document.querySelector('button[onclick="deleteImage(\'dni\')"]');
                
                // Botones de Foto de entrega
                const btnSubirEntrega = document.querySelector('button[onclick="uploadImage(\'entrega\')"]');
                const btnEliminarEntrega = document.querySelector('button[onclick="deleteImage(\'entrega\')"]');
                
                if (estadoEntrega === 'no-entregado') {
                    // Deshabilitar solo botones de DNI y entrega cuando no se entregó
                    if (btnSubirDni) btnSubirDni.disabled = true;
                    if (btnEliminarDni) btnEliminarDni.disabled = true;
                    if (btnSubirEntrega) btnSubirEntrega.disabled = true;
                    if (btnEliminarEntrega) btnEliminarEntrega.disabled = true;
                    
                    // Limpiar solo imágenes de DNI y entrega
                    limpiarImagenesDNIYEntrega();
                    
                    console.log('Botones de DNI y entrega deshabilitados y sus imágenes limpiadas');
                    
                } else if (estadoEntrega === 'entregado') {
                    // Habilitar botones de DNI y entrega cuando se entregó
                    if (btnSubirDni) btnSubirDni.disabled = false;
                    if (btnEliminarDni) btnEliminarDni.disabled = false;
                    if (btnSubirEntrega) btnSubirEntrega.disabled = false;
                    if (btnEliminarEntrega) btnEliminarEntrega.disabled = false;
                    
                    console.log('Botones de DNI y entrega habilitados');
                }
                
                // Los botones de foto de dirección siempre permanecen habilitados
                console.log('Botones de foto de dirección siempre habilitados');
            } catch (error) {
                console.error('Error al manejar botones de imágenes:', error);
            }
        }

        // Función para limpiar solo las imágenes de DNI y entrega (no toca la foto de dirección)
        function limpiarImagenesDNIYEntrega() {
            try {
                // Limpiar imagen del DNI
                const dniPlaceholder = document.getElementById('dni-placeholder');
                if (dniPlaceholder) {
                    dniPlaceholder.innerHTML = '<span>&#128247;</span>';
                }
                
                // Limpiar imagen de entrega
                const entregaPlaceholder = document.getElementById('entrega-placeholder');
                if (entregaPlaceholder) {
                    entregaPlaceholder.innerHTML = '<span>&#128247;</span>';
                }
                
                // Limpiar inputs de archivo de DNI y entrega
                const fotoDni = document.getElementById('fotoDni');
                const fotoEntrega = document.getElementById('fotoEntrega');
                if (fotoDni) fotoDni.value = '';
                if (fotoEntrega) fotoEntrega.value = '';
                
                console.log('Imágenes de DNI y entrega limpiadas');
            } catch (error) {
                console.error('Error al limpiar imágenes de DNI y entrega:', error);
            }
        }

        // Función para limpiar todas las imágenes (usada al limpiar formulario completo)
        function limpiarImagenes() {
            try {
                // Limpiar imagen de dirección
                const direccionPlaceholder = document.getElementById('direccion-placeholder');
                if (direccionPlaceholder) {
                    direccionPlaceholder.innerHTML = '<span>&#128247;</span>';
                }
                
                // Limpiar imagen del DNI
                const dniPlaceholder = document.getElementById('dni-placeholder');
                if (dniPlaceholder) {
                    dniPlaceholder.innerHTML = '<span>&#128247;</span>';
                }
                
                // Limpiar imagen de entrega
                const entregaPlaceholder = document.getElementById('entrega-placeholder');
                if (entregaPlaceholder) {
                    entregaPlaceholder.innerHTML = '<span>&#128247;</span>';
                }
                
                // Limpiar inputs de archivo
                const fotoDireccion = document.getElementById('fotoDireccion');
                const fotoDni = document.getElementById('fotoDni');
                const fotoEntrega = document.getElementById('fotoEntrega');
                if (fotoDireccion) fotoDireccion.value = '';
                if (fotoDni) fotoDni.value = '';
                if (fotoEntrega) fotoEntrega.value = '';
                
                console.log('Todas las imágenes limpiadas');
            } catch (error) {
                console.error('Error al limpiar todas las imágenes:', error);
            }
        }

        // Función para actualizar las opciones del combobox de observaciones según el estado de entrega
        function actualizarOpcionesObservaciones() {
            try {
                const estadoEntrega = document.getElementById('estadoEntrega').value;
                const observacionesSelect = document.getElementById('observaciones');
                
                // Limpiar opciones actuales y agregar placeholder primero
                observacionesSelect.innerHTML = '';
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = 'Seleccione observación...';
                placeholder.disabled = true;
                placeholder.hidden = true;
                placeholder.selected = true;
                observacionesSelect.appendChild(placeholder);
                
                if (estadoEntrega === 'entregado') {
                    // Opciones para "Entregado"
                    const opcionesEntregado = [
                        { value: 'entregado-destinatario', text: 'Se entregó al destinatario' },
                        { value: 'entregado-familiar', text: 'Se entregó a algún conocido o familiar del destinatario' }
                    ];
                    
                    opcionesEntregado.forEach(opcion => {
                        const option = document.createElement('option');
                        option.value = opcion.value;
                        option.textContent = opcion.text;
                        observacionesSelect.appendChild(option);
                    });
                    
                    // Mantener placeholder, no seleccionar automáticamente
                    observacionesSelect.selectedIndex = 0;
                    
                } else if (estadoEntrega === 'no-entregado') {
                    // Opciones para "No entregado"
                    const opcionesNoEntregado = [
                        { value: 'destinatario-no-encontrado', text: 'El destinatario no se encuentra' },
                        { value: 'rechazo-pedido', text: 'Rechazo del pedido' },
                        { value: 'direccion-no-existe', text: 'La dirección no existe' },
                        { value: 'lugar-inaccesible', text: 'Lugar inaccesible' },
                        { value: 'problemas-vehiculares', text: 'Problemas vehiculares' },
                        { value: 'otros', text: 'Otros' }
                    ];
                    
                    opcionesNoEntregado.forEach(opcion => {
                        const option = document.createElement('option');
                        option.value = opcion.value;
                        option.textContent = opcion.text;
                        observacionesSelect.appendChild(option);
                    });
                    
                    // Mantener placeholder, no seleccionar automáticamente
                    observacionesSelect.selectedIndex = 0;
                }
                
                // Manejar botones de imágenes según el estado
                manejarBotonesImagenes(estadoEntrega);
                
                // Actualizar estado del botón Registrar
                updateRegistrarState();
                
                console.log('Opciones de observaciones actualizadas para:', estadoEntrega);
            } catch (error) {
                console.error('Error al actualizar opciones de observaciones:', error);
            }
        }

        // Función para llenar los campos del destinatario
        function llenarCamposDestinatario(destinatario) {
            try {
                // Llenar los campos del destinatario con datos de la base de datos
                document.getElementById('clienteDni').value = destinatario.ReceptorDniSnap || '';
                document.getElementById('clienteNombre').value = destinatario.NombreContactoSnap || '';
                document.getElementById('clienteCelular').value = destinatario.TelefonoSnap || '';
                document.getElementById('clienteDireccion').value = destinatario.DireccionSnap || '';
                
                console.log('Datos del destinatario cargados:', destinatario);
            } catch (error) {
                console.error('Error al llenar campos del destinatario:', error);
            }
        }

        function uploadImage(type) {
            let inputId;
            switch(type) {
                case 'direccion':
                    inputId = 'fotoDireccion';
                    break;
                case 'dni':
                    inputId = 'fotoDni';
                    break;
                case 'entrega':
                    inputId = 'fotoEntrega';
                    break;
                default:
                    return;
            }
            const input = document.getElementById(inputId);
            input.click();
        }

        // Manejar cambio de archivo
        document.getElementById('fotoDireccion').addEventListener('change', function(e) {
            handleFileSelect(e, 'direccion');
        });

        document.getElementById('fotoDni').addEventListener('change', function(e) {
            handleFileSelect(e, 'dni');
        });

        document.getElementById('fotoEntrega').addEventListener('change', function(e) {
            handleFileSelect(e, 'entrega');
        });

        function handleFileSelect(e, type) {
            const file = e.target.files[0];
            if (file) {
                // Validar formato de archivo
                const allowedExtensions = ['jpg', 'jpeg', 'png'];
                const fileExtension = file.name.split('.').pop().toLowerCase();
                
                if (!allowedExtensions.includes(fileExtension)) {
                    alert('Solo se permiten archivos en formato .jpg, .jpeg y .png');
                    // Limpiar el input
                    e.target.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const placeholderId = type + '-placeholder';
                    const placeholder = document.getElementById(placeholderId);
                    placeholder.innerHTML = '<img src="' + e.target.result + '" style="width: 100%; height: 100%; object-fit: cover;">';
                };
                reader.readAsDataURL(file);
            }
            // Actualizar estado del botón para cualquier tipo de foto
            updateRegistrarState();
        }

        function deleteImage(type) {
            const placeholderId = type + '-placeholder';
            const placeholder = document.getElementById(placeholderId);
            placeholder.innerHTML = '<span>&#128247;</span>';
            
            // Limpiar el input de archivo
            let inputId;
            switch(type) {
                case 'direccion':
                    inputId = 'fotoDireccion';
                    break;
                case 'dni':
                    inputId = 'fotoDni';
                    break;
                case 'entrega':
                    inputId = 'fotoEntrega';
                    break;
                default:
                    return;
            }
            document.getElementById(inputId).value = '';
            // Actualizar estado del botón para cualquier tipo de foto
            updateRegistrarState();
        }

        // Manejar envío del formulario
        document.getElementById('entregaForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar que se haya seleccionado una dirección
            const selectedRow = document.querySelector('.selected-row');
            if (!selectedRow) {
                alert('Por favor seleccione una dirección de la tabla "Ordenes Asignadas"');
                return;
            }
            
            // Obtener el ID de la orden de pedido (ahora está en la primera columna)
            const idOrdenPedido = selectedRow.cells[0].textContent.trim();
            if (!idOrdenPedido) {
                alert('No se pudo obtener el ID de la orden de pedido');
                return;
            }
            
            // Validar estado de entrega y observaciones con placeholder
            const estadoEntrega = document.getElementById('estadoEntrega').value;
            const observaciones = document.getElementById('observaciones').value;
            if (!estadoEntrega) {
                alert('Seleccione un estado de entrega');
                return;
            }
            if (!observaciones) {
                alert('Seleccione una observación');
                return;
            }
            
            // Crear FormData para enviar archivos
            const formData = new FormData();
            formData.append('idOrdenPedido', idOrdenPedido);
            formData.append('estadoEntrega', estadoEntrega);
            formData.append('observaciones', observaciones);
            
            // Agregar archivos si existen
            const fotoDireccion = document.getElementById('fotoDireccion').files[0];
            const fotoDni = document.getElementById('fotoDni').files[0];
            const fotoEntrega = document.getElementById('fotoEntrega').files[0];
            
            if (fotoDireccion) formData.append('fotoDireccion', fotoDireccion);
            if (fotoDni) formData.append('fotoDni', fotoDni);
            if (fotoEntrega) formData.append('fotoEntrega', fotoEntrega);
            
            // Mostrar mensaje de carga
            const submitBtn = document.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Registrando...';
            submitBtn.disabled = true;
            
            // Enviar datos al procedimiento almacenado
            fetch('../../Controlador/CUS25Negocio.php?action=procesar_entrega', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('¡Consolidación registrada exitosamente!');
                    // Limpiar formulario
                    limpiarFormulario();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la consolidación');
            })
            .finally(() => {
                // Restaurar botón
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });
        
        // Función para limpiar el formulario después del registro exitoso
        function limpiarFormulario() {
            // Limpiar campos del destinatario
            limpiarCamposDestinatario();
            
            // Limpiar imágenes
            limpiarImagenes();
            
            // Limpiar tabla de pedidos
            const pedidosBody = document.getElementById('pedidosTable');
            pedidosBody.innerHTML = `
                <tr>
                    <td colspan="2" style="text-align: center; color: #666;">
                        Seleccione una dirección para ver los pedidos
                    </td>
                </tr>
            `;
            
            // Deseleccionar fila
            document.querySelectorAll('tbody tr').forEach(r => r.classList.remove('selected-row'));
            
            // Resetear comboboxes con placeholder
            const estado = document.getElementById('estadoEntrega');
            if (estado) estado.selectedIndex = 0;
            const observ = document.getElementById('observaciones');
            if (observ) {
                observ.innerHTML = '<option value="" selected disabled hidden>Seleccione observación...</option>';
            }
            updateRegistrarState();
            
            // Recargar direcciones para verificar si quedan más pedidos
            setTimeout(async () => {
                const idTrabajador = document.getElementById('repartidorId').value;
                if (idTrabajador) {
                    await cargarDirecciones(idTrabajador);
                }
            }, 500);
        }

        function salir() {
            if (confirm('¿Está seguro que desea salir?')) {
                window.location.href = '../../index.php';
            }
        }
    </script>
</body>
</html>
