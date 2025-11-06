document.addEventListener('DOMContentLoaded', () => {
    const tablaSolicitudes = document.querySelector('#tablaSolicitudes tbody');
    const tablaDetalle = document.querySelector('#tablaDetalle tbody');
    const btnEvaluar = document.getElementById('btnEvaluar');
    const tablaResultado = document.querySelector('#tablaResultado tbody');
    const resultadoResumen = document.getElementById('resultadoResumen');

    let seleccionRequerimiento = null;
    let detalleActual = [];
    let financiamientoActual = null;

    // Cargar financiamiento del periodo
    async function cargarFinanciamiento(){
        try {
            const mes = new Date().toISOString().slice(0,7); // YYYY-MM
            const res = await fetch(`../../Vista/Ajax/CUS13/obtenerFinanciamiento.php?mes=${mes}`);
            const json = await res.json();
            
            if(json.success){
                financiamientoActual = json.data;
                mostrarFinanciamiento(json.data);
            } else {
                throw new Error(json.error || 'Error al cargar financiamiento');
            }
        } catch(error){
            console.error('Error:', error);
            document.getElementById('financiamientoInfo').innerHTML = `
                <div class="error-box">❌ ${error.message}</div>
            `;
        }
    }

    function mostrarFinanciamiento(f){
        console.log("Financiamiento recibido:", f);

        const monto = Number(f.MontoPeriodo) || 0;
        const saldoAnterior = Number(f.SaldoAnterior) || 0;
        const total = Number(f.FinanciamientoTotal) || (monto + saldoAnterior);

        // Asegurar que todos los elementos existan antes de escribir
        const descEl = document.getElementById('partidaDesc');
        const montoEl = document.getElementById('partidaMonto');
        const saldoAntEl = document.getElementById('saldoAnteriorValor');
        const totalEl = document.getElementById('partidaSaldo');

        if (!descEl || !montoEl || !saldoAntEl || !totalEl) {
            console.error("❌ Faltan elementos HTML en Partida periodo.");
            return;
        }

        descEl.textContent = f.Descripcion || '-';
        montoEl.textContent = `S/ ${monto.toFixed(2)}`;
        saldoAntEl.textContent = `S/ ${saldoAnterior.toFixed(2)}`;
        totalEl.textContent = `S/ ${total.toFixed(2)}`;
        }




    // Cargar solicitudes
    async function cargarSolicitudes(){
        try {
            const res = await fetch('../../Vista/Ajax/CUS13/listarSolicitudes.php');
            const datos = await res.json();
            tablaSolicitudes.innerHTML = '';
            
            if(datos.length === 0){
                tablaSolicitudes.innerHTML = '<tr><td colspan="6" class="empty">No hay solicitudes pendientes</td></tr>';
                return;
            }
            
            datos.forEach(r => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${r.Id_Requerimiento}</td>
                    <td>${r.FechaRequerimiento}</td>
                    <td>S/ ${parseFloat(r.Total).toFixed(2)}</td>
                    <td>S/ ${parseFloat(r.PrecioPromedio).toFixed(2)}</td>
                    <td><span class="badge">${r.Estado || 'Pendiente'}</span></td>
                    <td><button class="btnSelect" data-id="${r.Id_Requerimiento}">Seleccionar</button></td>
                `;
                tablaSolicitudes.appendChild(tr);
            });

            document.querySelectorAll('.btnSelect').forEach(b => {
                b.addEventListener('click', async (ev) => {
                    const id = ev.currentTarget.dataset.id;
                    console.log("🟢 ID que se está enviando desde interfaz:", id);
                    seleccionRequerimiento = parseInt(id);
                    await cargarDetalle(id);
                    
                    // Marcar seleccionado
                    //document.querySelectorAll('#tablaSolicitudes tbody tr').forEach(tr => tr.classList.remove('selected'));
                    //const tr = ev.currentTarget.closest('tr');
                    //if (tr) {
                    //    tr.classList.add('selected');
                    //}

                });
            });
        } catch(error){
            console.error('Error:', error);
            tablaSolicitudes.innerHTML = '<tr><td colspan="6" class="error">Error al cargar</td></tr>';
        }
    }

    // Cargar detalle
    async function cargarDetalle(idRequerimiento){
        try {
            const response = await fetch(`../../Vista/Ajax/CUS13/listarProductos.php?id=${idRequerimiento}`);
            const result = await response.json();
            console.log("✅ Productos recibidos (JSON real):", result);
            console.log("📦 Tipo de data:", Array.isArray(result.data) ? "array" : typeof result.data, result.data);


            // Mostrar el área de detalle
            document.getElementById('detalleVacio').style.display = 'none';
            document.getElementById('detalleContenido').style.display = 'block';

            const tabla = document.getElementById("tablaDetalleSolicitud");
            const tbody = tabla.querySelector("tbody");
            tbody.innerHTML = "";


            if (!result.success) {
                tbody.innerHTML = `<tr><td colspan="4" class="text-danger text-center">Error: ${result.error}</td></tr>`;
                return;
            }

            const data = result.data;
            console.log("📦 Tipo de data:", typeof data, data);
            
            if (!data || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-muted text-center">No hay productos para esta solicitud.</td></tr>`;
            return;
            }

            let totalSolicitado = 0;

            data.forEach((item) => {
                const row = document.createElement("tr");
                const total = Number(item.Total) || 0;
                totalSolicitado += total;

                row.innerHTML = `
                    <td>${item.Id_Producto}</td>
                    <td>${item.Cantidad}</td>
                    <td>S/ ${Number(item.PrecioPromedio).toFixed(2)}</td>
                    <td>S/ ${total.toFixed(2)}</td>
                `;
                tbody.appendChild(row);
            });

            // Actualizar el total en el pie de tabla
            const totalEl = document.getElementById("totalSolicitado");
            if (totalEl) {
                totalEl.textContent = `S/ ${totalSolicitado.toFixed(2)}`;
            }
            
        } catch (error) {
            console.error("Error al cargar detalle:", error);
            const tbody = document.querySelector("#tablaDetalleSolicitud tbody");
            tbody.innerHTML = `<tr><td colspan="4" class="text-danger text-center">Error al cargar productos.</td></tr>`;
        }
    }


    // Evaluar solicitud
    btnEvaluar.addEventListener('click', async () => {
        if (!seleccionRequerimiento) {
            alert('Seleccione primero una solicitud.');
            return;
        }

        if (!financiamientoActual || financiamientoActual.FinanciamientoTotal <= 0) {
            alert('No hay financiamiento disponible para evaluar.');
            return;
        }

        // Mostrar confirmación antes de evaluar
        if (!confirm(`¿Evaluar la solicitud #${seleccionRequerimiento} con los 3 criterios (Precio, Rotación, Proporcionalidad)?`)) {
            return;
        }

        btnEvaluar.disabled = true;
        btnEvaluar.textContent = 'Evaluando...';

        try {
            const res = await fetch('../../Vista/Ajax/CUS13/aplicarEvaluacion.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    idRequerimiento: seleccionRequerimiento,
                    idPartida: financiamientoActual.Id_PartidaPeriodo
                })
            });

            const json = await res.json();
            console.log("📊 Resultado de evaluación:", json);

            if (json.success) {
                mostrarResultado(json);
                await cargarFinanciamiento();
                await cargarSolicitudes();
                seleccionRequerimiento = null;
                document.getElementById('detalleVacio').style.display = 'block';
                document.getElementById('detalleContenido').style.display = 'none';
            } else {
                alert('Error: ' + (json.error || 'No se pudo evaluar.'));
            }
        } catch (err) {
            console.error('Error al evaluar:', err);
            alert('Error de conexión con el servidor.');
        } finally {
            btnEvaluar.disabled = false;
            btnEvaluar.textContent = 'Evaluar y Aprobar (3 criterios)';
        }
    });


    function mostrarResultado(json){
        document.getElementById('resultadoVacio').style.display = 'none';
        document.getElementById('resultadoContenido').style.display = 'block';
        
        let estadoClass = json.Estado === 'Aprobado' ? 'estado-ok' : 
                        json.Estado === 'Parcialmente Aprobado' ? 'estado-parcial' : 'estado-no';
        
        resultadoResumen.innerHTML = `
            <div class="resumen ${estadoClass}">
                <div class="estado-titulo">${json.Estado}</div>
                <div class="info-grid">
                    <div><strong>ID Evaluación:</strong> ${json.idEvaluacion}</div>
                    <div><strong>Monto Solicitado:</strong> S/ ${json.MontoSolicitado.toFixed(2)}</div>
                    <div><strong>Monto Aprobado:</strong> S/ ${json.MontoAprobado.toFixed(2)}</div>
                    <div><strong>Saldo Después:</strong> S/ ${json.SaldoDespues.toFixed(2)}</div>
                </div>
            </div>
        `;
        
        tablaResultado.innerHTML = '';
        json.detalle.forEach(d => {
            const prod = detalleActual.find(x => x.Id_Producto == d.Id_Producto) || {};
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${prod.NombreProducto || d.Id_Producto}</td>
                <td>${d.CantidadSolicitada || ''}</td>
                <td class="${d.CantidadAprobada > 0 ? 'ok' : 'no'}">${d.CantidadAprobada}</td>
                <td>S/ ${parseFloat(d.Precio).toFixed(2)}</td>
                <td>S/ ${parseFloat(d.MontoAsignado).toFixed(2)}</td>
                <td><span class="badge-${d.EstadoProducto.toLowerCase()}">${d.EstadoProducto}</span></td>
            `;
            tablaResultado.appendChild(tr);
        });
    }

    // Inicializar
    cargarFinanciamiento();
    cargarSolicitudes();

    // Actualizar hora
    setInterval(() => {
        document.getElementById('horaTexto').textContent = new Date().toLocaleTimeString('es-PE');
    }, 1000);
});