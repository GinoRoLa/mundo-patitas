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
    // === BOTÓN EVALUAR ===
    btnEvaluar.addEventListener('click', async () => {
        if (!seleccionRequerimiento) {
            alert('Seleccione primero una solicitud.');
            return;
        }

        if (!financiamientoActual || financiamientoActual.FinanciamientoTotal <= 0) {
            alert('No hay financiamiento disponible para evaluar.');
            return;
        }

        const criterioSeleccionado = document.querySelector('input[name="criterio"]:checked')?.value || 'Precio';

        if (!confirm(`¿Desea evaluar la solicitud #${seleccionRequerimiento} con el criterio "${criterioSeleccionado}"?`)) {
            return;
        }

        btnEvaluar.disabled = true;
        btnEvaluar.textContent = 'Evaluando...';

        try {
            const res = await fetch('../../Vista/Ajax/CUS13/evaluarSimulacion.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    idRequerimiento: seleccionRequerimiento,
                    idPartida: financiamientoActual?.Id_PartidaPeriodo || 1001,
                    criterio: criterioSeleccionado
                })
            });

            const json = await res.json();
            console.log("📊 Resultado de simulación:", json);

            if (!json.success) throw new Error(json.error || 'Error en simulación.');

            // ✅ Mostrar tabla simulada
            mostrarResultado(json);

            // ✅ Guardar temporalmente el resultado en memoria
            window.resultadoSimulado = json;

            // ✅ Mostrar botón "Registrar Evaluación"
            document.getElementById('accionesEvaluacion').style.display = 'block';

            alert('✅ Evaluación simulada correctamente.\nRevise los resultados antes de registrar.');

        } catch (err) {
            console.error('Error durante la simulación:', err);
            alert('Error: ' + err.message);
        } finally {
            btnEvaluar.disabled = false;
            btnEvaluar.textContent = 'Evaluar';
        }
    });


    // === BOTÓN REGISTRAR ===
    document.getElementById('btnRegistrar').addEventListener('click', async () => {
        if (!window.resultadoSimulado) {
            alert('⚠️ Primero debe evaluar la solicitud antes de registrar.');
            return;
        }

        const criterioSeleccionado = document.querySelector('input[name="criterio"]:checked')?.value || 'Precio';

        if (!confirm(`La evaluación se completó con el criterio "${criterioSeleccionado}".\n¿Desea aprobar y registrar oficialmente la evaluación?`)) {
            return;
        }

        const btnRegistrar = document.getElementById('btnRegistrar');
        btnRegistrar.disabled = true;
        btnRegistrar.textContent = 'Registrando...';

        try {
            // 🔹 Usamos ruta RELATIVA CORRECTA
            const res2 = await fetch('../../Vista/Ajax/CUS13/registrarEvaluacion.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    idRequerimiento: seleccionRequerimiento,
                    idPartida: financiamientoActual?.Id_PartidaPeriodo || 1001,
                    criterio: criterioSeleccionado,
                    resultado: window.resultadoSimulado
                })
            });

            // 🧩 Leemos la respuesta en texto para depurar si hay HTML o error PHP
            const rawText = await res2.text();
            console.log("📤 Respuesta cruda del servidor registrarEvaluacion.php:\n", rawText);

            let finalJson;
            try {
                finalJson = JSON.parse(rawText);
            } catch (e) {
                console.error("❌ No es JSON válido. Respuesta literal del servidor:", rawText);
                alert("⚠️ Error interno del servidor (ver consola para detalles del error PHP).");
                return;
            }

            console.log("📋 Resultado final (registro):", finalJson);

            if (finalJson.success) {
                alert('✅ Evaluación aprobada y registrada correctamente.');
                mostrarResultado(finalJson);
                await cargarFinanciamiento();
                await cargarSolicitudes();

                // Limpiar después de registrar
                seleccionRequerimiento = null;
                window.resultadoSimulado = null;
                document.getElementById('accionesEvaluacion').style.display = 'none';
                document.getElementById('detalleVacio').style.display = 'block';
                document.getElementById('detalleContenido').style.display = 'none';
            } else {
                alert('❌ Error al registrar evaluación: ' + (finalJson.error || 'Error desconocido.'));
            }

        } catch (err) {
            console.error('Error al registrar:', err);
            alert('Error: ' + err.message);
        } finally {
            btnRegistrar.disabled = false;
            btnRegistrar.textContent = 'Registrar Evaluación';
        }
    });



    // === FUNCIONES GLOBALES ===
        function mostrarResultado(json) {
        document.getElementById('resultadoVacio').style.display = 'none';
        document.getElementById('resultadoContenido').style.display = 'block';
        
        const montoSolicitado = Number(json.MontoSolicitado || 0);
        const montoAprobado = Number(json.MontoAprobado || 0);
        const saldoDespues = Number(json.SaldoDespues || 0);
        const estado = json.Estado || 'Sin estado';

        let estadoClass = estado === 'Aprobado' ? 'estado-ok' : 
                        estado === 'Parcialmente Aprobado' ? 'estado-parcial' : 'estado-no';

        resultadoResumen.innerHTML = `
            <div class="resumen ${estadoClass}">
                <div class="estado-titulo">
                    ${json.idEvaluacion && json.idEvaluacion !== '-' 
                        ? (json.Estado || '') 
                        : 'En evaluación'}
                </div>
                <div class="info-grid">
                    <div><strong>ID Evaluación:</strong> ${json.idEvaluacion || '-'}</div>
                    <div><strong>Monto Solicitado:</strong> S/ ${montoSolicitado.toFixed(2)}</div>
                    <div><strong>Monto Aprobado:</strong> S/ ${montoAprobado.toFixed(2)}</div>
                    <div><strong>Saldo Después:</strong> S/ ${saldoDespues.toFixed(2)}</div>
                </div>
            </div>
        `;
        
        // Si no hay detalle, salimos
        if (!json.detalle || !Array.isArray(json.detalle)) {
            console.warn("⚠️ No se recibió detalle en el JSON final:", json);
            return;
        }

        tablaResultado.innerHTML = '';
        json.detalle.forEach(d => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${d.Id_Producto || '-'}</td>
                <td>${d.CantidadSolicitada || 0}</td>
                <td class="${d.CantidadAprobada > 0 ? 'ok' : 'no'}">${d.CantidadAprobada || 0}</td>
                <td>S/ ${(d.Precio ?? 0).toFixed(2)}</td>
                <td>S/ ${(d.MontoAsignado ?? 0).toFixed(2)}</td>
                <td><span class="badge-${(d.EstadoProducto || 'desconocido').toLowerCase()}">${d.EstadoProducto || '-'}</span></td>
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
