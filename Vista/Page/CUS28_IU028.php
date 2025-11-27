<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Nota de Caja para Repartidor</title>
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
            background-color: var(--primary-50);
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
            border-bottom: 3px solid var(--primary-600);
            padding-bottom: 20px;
            background: linear-gradient(to bottom, var(--primary-50), white);
            border-radius: 8px 8px 0 0;
            padding: 20px;
        }

        .header h1 {
            color: var(--gray-900);
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
            background-color: var(--gray-50);
        }

        .section h2 {
            color: var(--gray-900);
            margin-bottom: 15px;
            font-size: 1.3em;
            border-bottom: 3px solid var(--primary-600);
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
            color: var(--gray-700);
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

        .form-group input:read-only {
            background-color: #e9ecef;
            cursor: not-allowed;
        }

        .form-row {
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }

        .form-row .form-group {
            flex: 1;
        }

        .form-row .form-group.col-auto {
            flex: 0 0 auto;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            position: relative;
            max-height: 300px;
            overflow-y: auto;
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .table-container thead {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 10;
            box-shadow: 0 2px 2px rgba(0,0,0,0.1);
        }

        .table-container th {
            height: 45px;
            padding: 12px;
            border-bottom: 2px solid var(--primary-600);
            background-color: var(--primary-200);
            font-weight: bold;
            color: var(--blue-900);
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
            text-align: left;
        }

        .table-container td {
            height: 48px;
            padding: 12px;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }

        .table-container tbody tr:hover {
            background-color: var(--primary-50);
            transition: background-color 0.2s ease;
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
            background-color: var(--primary-600);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            background-color: #2563eb;
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

        .btn-small {
            padding: 6px 12px;
            font-size: 13px;
        }

        .totales-container {
            display: flex;
            justify-content: flex-end;
            gap: 20px;
            margin-top: 15px;
        }

        .total-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .total-item label {
            font-weight: bold;
            color: var(--gray-700);
            font-size: 14px;
        }

        .total-item input {
            width: 150px;
            padding: 8px 12px;
            border: 2px solid var(--primary-600);
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            text-align: right;
            background-color: #f8f9fa;
        }

        .btn-generar-container {
            display: flex;
            justify-content: flex-end;
            margin: 20px 0;
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

            .totales-container {
                flex-direction: column;
                align-items: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Responsable de Caja -->
        <div class="responsable-top">
            <div class="form-row">
                <div class="form-group">
                    <label for="responsableId">ID Responsable de Caja:</label>
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

        <!-- Header -->
        <div class="header">
            <h1>CUS28 – GENERAR NOTA DE CAJA PARA REPARTIDOR</h1>
            <div class="datetime">
                <span id="currentDate"></span> - <span id="currentTime"></span>
            </div>
        </div>

        <!-- Sección Repartidor -->
        <div class="section">
            <h2>Repartidor</h2>
            <div class="form-row">
                <div class="form-group">
                    <label for="idRepartidor">ID del Repartidor:</label>
                    <input type="text" id="idRepartidor">
                </div>
                <div class="form-group col-auto">
                    <button type="button" class="btn btn-primary btn-small" onclick="buscarRepartidor()">Buscar</button>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="dniRepartidor">DNI:</label>
                    <input type="text" id="dniRepartidor" readonly>
                </div>
                <div class="form-group">
                    <label for="nombreRepartidor">Nombre:</label>
                    <input type="text" id="nombreRepartidor" readonly>
                </div>
                <div class="form-group">
                    <label for="apellidoRepartidor">Ap. Paterno:</label>
                    <input type="text" id="apellidoRepartidor" readonly>
                </div>
            </div>
        </div>

        <!-- Sección Asignación de Reparto -->
        <div class="section">
            <h2>Asignación de Reparto</h2>
            <div class="form-row">
                <div class="form-group">
                    <label for="idOrdenAsignacion">ID Orden de Asignación de Reparto:</label>
                    <input type="text" id="idOrdenAsignacion" readonly>
                </div>
                <div class="form-group">
                    <label for="totalOrdenesPedido">Total de Ordenes de pedido:</label>
                    <input type="text" id="totalOrdenesPedido" readonly>
                </div>
            </div>
        </div>

        <!-- Sección Detalle de Contra Entregas -->
        <div class="section">
            <h2>Detalle de Contra Entregas</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID Detalle OPCE</th>
                            <th>ID Orden de Pedido</th>
                            <th>Total</th>
                            <th>Efectivo del Cliente</th>
                            <th>Vuelto</th>
                        </tr>
                    </thead>
                    <tbody id="detalleContraEntregasTable">
                        <tr>
                            <td colspan="5" style="text-align: center; color: #666;">
                                Busque un repartidor para cargar los datos
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Totales -->
            <div class="totales-container">
                <div class="total-item">
                    <label for="totalContraEntregas">Total Contra Entregas:</label>
                    <input type="text" id="totalContraEntregas" value="0" readonly>
                </div>
                <div class="total-item">
                    <label for="totalVueltoConciliar">Total Vuelto por Conciliar:</label>
                    <input type="text" id="totalVueltoConciliar" value="0.00" readonly>
                </div>
            </div>
        </div>

        <!-- Botón Generar Nota de Caja -->
        <div class="btn-generar-container">
            <button type="button" class="btn btn-primary" id="btnGenerarNotaCaja" onclick="generarNotaCaja()" disabled>
                Generar Nota de Caja
            </button>
        </div>

        <!-- Sección Notas de Caja Generadas -->
        <div class="section">
            <h2>Notas de Caja Generadas</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID Nota de Caja</th>
                            <th>ID Responsable de Caja</th>
                            <th>ID Repartidor</th>
                            <th>ID Asignación Reparto</th>
                            <th>Total Notas de Caja</th>
                            <th>Vuelto Total</th>
                            <th>Fecha Emisión</th>
                        </tr>
                    </thead>
                    <tbody id="notasCajaGeneradasTable">
                        <tr>
                            <td colspan="7" style="text-align: center; color: #666;">
                                No hay notas de caja generadas
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <button type="button" class="btn btn-secondary" onclick="salir()">Salir</button>
        </div>
    </div>

    <script>
        // Actualizar fecha y hora
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
        let datosRepartidor = null;
        let detalleContraEntregas = [];

        // Cargar datos al iniciar
        document.addEventListener('DOMContentLoaded', async function() {
            await cargarDatosResponsable();
            await cargarNotasCajaGeneradas();
        });

        // Cargar datos del responsable de caja
        async function cargarDatosResponsable() {
            try {
                const response = await fetch('../../Controlador/CUS28Negocio.php?action=obtener_responsable');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('responsableId').value = data.data.id_Trabajador || '';
                    document.getElementById('responsableNombre').value = data.data.des_nombreTrabajador || '';
                    document.getElementById('responsableApellido').value = data.data.des_apepatTrabajador || '';
                } else {
                    console.error('Error al cargar datos del responsable:', data.message);
                }
            } catch (error) {
                console.error('Error al cargar datos del responsable:', error);
            }
        }

        // Buscar repartidor
        async function buscarRepartidor() {
            const idRepartidor = document.getElementById('idRepartidor').value.trim();
            
            if (!idRepartidor) {
                alert('Por favor ingrese el ID del repartidor');
                return;
            }

            try {
                const response = await fetch(`../../Controlador/CUS28Negocio.php?action=buscar_repartidor&idRepartidor=${encodeURIComponent(idRepartidor)}`);
                const data = await response.json();
                
                if (data.success) {
                    datosRepartidor = data.data;
                    
                    // Llenar datos del repartidor
                    document.getElementById('dniRepartidor').value = data.data.DNI || '';
                    document.getElementById('nombreRepartidor').value = data.data.Nombre || '';
                    document.getElementById('apellidoRepartidor').value = data.data.ApellidoPaterno || '';
                    
                    // Llenar asignación de reparto
                    document.getElementById('idOrdenAsignacion').value = data.data.IdOrdenAsignacion || '';
                    document.getElementById('totalOrdenesPedido').value = data.data.TotalOrdenes || '0';
                    
                    // ✅ AUTOMÁTICAMENTE cargar detalle de contra entregas usando el ID de Orden de Asignación
                    const idOrdenAsignacion = data.data.IdOrdenAsignacion;
                    if (idOrdenAsignacion) {
                        await cargarDetalleContraEntregas(idOrdenAsignacion);
                    }
                    
                } else {
                    alert(data.message || 'Repartidor no encontrado');
                    limpiarFormulario();
                }
            } catch (error) {
                console.error('Error al buscar repartidor:', error);
                alert('Error de conexión al buscar repartidor');
            }
        }

        // Cargar detalle de contra entregas usando ID Orden de Asignación
        async function cargarDetalleContraEntregas(idOrdenAsignacion) {
            try {
                const response = await fetch(`../../Controlador/CUS28Negocio.php?action=obtener_detalle_contra_entregas&idOrdenAsignacion=${encodeURIComponent(idOrdenAsignacion)}`);
                const data = await response.json();
                
                if (data.success) {
                    detalleContraEntregas = data.data || [];
                    renderDetalleContraEntregas(detalleContraEntregas);
                    calcularTotales();
                    actualizarEstadoBotonGenerar();
                } else {
                    detalleContraEntregas = [];
                    renderDetalleContraEntregas([]);
                    calcularTotales();
                }
            } catch (error) {
                console.error('Error al cargar detalle:', error);
                detalleContraEntregas = [];
                renderDetalleContraEntregas([]);
            }
        }

        // Renderizar detalle de contra entregas
        function renderDetalleContraEntregas(datos) {
            const tbody = document.getElementById('detalleContraEntregasTable');
            tbody.innerHTML = '';
            
            if (!datos || datos.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align: center; color: #666;">
                            No hay contra entregas para este repartidor
                        </td>
                    </tr>
                `;
                return;
            }
            
            datos.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.IdDet || ''}</td>
                    <td>${item.IdOrdenPedido || ''}</td>
                    <td>${parseFloat(item.Total || 0).toFixed(2)}</td>
                    <td>${parseFloat(item.EfectivoCliente || 0).toFixed(2)}</td>
                    <td>${parseFloat(item.Vuelto || 0).toFixed(2)}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Calcular totales
        function calcularTotales() {
            // Total Contra Entregas = CANTIDAD DE FILAS (no suma)
            let totalContraEntregas = detalleContraEntregas.length;
            let totalVuelto = 0;
            
            detalleContraEntregas.forEach(item => {
                totalVuelto += parseFloat(item.Vuelto || 0);
            });
            
            document.getElementById('totalContraEntregas').value = totalContraEntregas; // Cantidad de filas
            document.getElementById('totalVueltoConciliar').value = totalVuelto.toFixed(2);
        }

        // Actualizar estado del botón generar
        function actualizarEstadoBotonGenerar() {
            const btnGenerar = document.getElementById('btnGenerarNotaCaja');
            const tieneRepartidor = datosRepartidor !== null;
            const tieneDetalles = detalleContraEntregas.length > 0;
            
            btnGenerar.disabled = !(tieneRepartidor && tieneDetalles);
        }

        // Generar nota de caja
        async function generarNotaCaja() {
            if (!datosRepartidor || detalleContraEntregas.length === 0) {
                alert('No hay datos suficientes para generar la nota de caja');
                return;
            }

            if (!confirm('¿Está seguro de generar la nota de caja?')) {
                return;
            }

            const btnGenerar = document.getElementById('btnGenerarNotaCaja');
            const textoOriginal = btnGenerar.textContent;

            try {
                btnGenerar.disabled = true;
                btnGenerar.textContent = '⏳ Generando...';

                const payload = {
                    idResponsable: document.getElementById('responsableId').value,
                    idRepartidor: document.getElementById('idRepartidor').value,
                    idOrdenAsignacion: document.getElementById('idOrdenAsignacion').value,
                    totalContraEntregas: document.getElementById('totalContraEntregas').value,
                    totalVuelto: document.getElementById('totalVueltoConciliar').value
                };

                const response = await fetch('../../Controlador/CUS28Negocio.php?action=generar_nota_caja', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ Nota de caja generada exitosamente\n\nID: ' + data.idNotaCaja);
                    
                    // Recargar tabla de notas generadas
                    await cargarNotasCajaGeneradas();
                    
                    // Limpiar formulario
                    limpiarFormulario();
                } else {
                    alert('❌ Error al generar nota de caja:\n' + data.message);
                }

                btnGenerar.textContent = textoOriginal;
                
            } catch (error) {
                console.error('Error al generar nota de caja:', error);
                alert('❌ Error de conexión al generar nota de caja');
                btnGenerar.textContent = textoOriginal;
                btnGenerar.disabled = false;
            }
        }

        // Cargar notas de caja generadas
        async function cargarNotasCajaGeneradas() {
            try {
                const response = await fetch('../../Controlador/CUS28Negocio.php?action=obtener_notas_caja_generadas');
                const data = await response.json();
                
                if (data.success) {
                    renderNotasCajaGeneradas(data.data || []);
                } else {
                    renderNotasCajaGeneradas([]);
                }
            } catch (error) {
                console.error('Error al cargar notas de caja:', error);
                renderNotasCajaGeneradas([]);
            }
        }

        // Renderizar notas de caja generadas
        function renderNotasCajaGeneradas(datos) {
            const tbody = document.getElementById('notasCajaGeneradasTable');
            tbody.innerHTML = '';
            
            if (!datos || datos.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align: center; color: #666;">
                            No hay notas de caja generadas
                        </td>
                    </tr>
                `;
                return;
            }
            
            datos.forEach(item => {
                // Formatear fecha si existe
                let fechaFormateada = '';
                if (item.FechaEmision) {
                    const fecha = new Date(item.FechaEmision);
                    fechaFormateada = fecha.toLocaleString('es-ES', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.IDNotaCaja || ''}</td>
                    <td>${item.IDResponsableCaja || ''}</td>
                    <td>${item.IDRepartidor || ''}</td>
                    <td>${item.IDAsignacionReparto || ''}</td>
                    <td>${parseFloat(item.TotalContraEntrega || 0).toFixed(0)}</td>
                    <td>${parseFloat(item.VueltoTotal || 0).toFixed(2)}</td>
                    <td>${fechaFormateada}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Limpiar formulario
        function limpiarFormulario() {
            document.getElementById('idRepartidor').value = '';
            document.getElementById('dniRepartidor').value = '';
            document.getElementById('nombreRepartidor').value = '';
            document.getElementById('apellidoRepartidor').value = '';
            document.getElementById('idOrdenAsignacion').value = '';
            document.getElementById('totalOrdenesPedido').value = '';
            document.getElementById('totalContraEntregas').value = '0';
            document.getElementById('totalVueltoConciliar').value = '0.00';
            
            datosRepartidor = null;
            detalleContraEntregas = [];
            
            renderDetalleContraEntregas([]);
            actualizarEstadoBotonGenerar();
        }

        // Salir
        function salir() {
            if (confirm('¿Está seguro que desea salir?')) {
                window.location.href = '../../index.php';
            }
        }
    </script>
</body>
</html>
