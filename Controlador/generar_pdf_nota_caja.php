<?php
require_once(__DIR__ . '/../vendor/autoload.php');

use Dompdf\Dompdf;
use Dompdf\Options;

function generarPDFNotaCaja($cn, $idNotaCaja) {
    try {
        // Escapar ID
        $idNotaCaja = mysqli_real_escape_string($cn, $idNotaCaja);
        
        // Obtener datos de la nota de caja
        $sqlCabecera = "SELECT 
                nc.IDNotaCaja,
                nc.IDResponsableCaja,
                nc.IDRepartidor,
                nc.IDAsignacionReparto,
                nc.TotalContraEntrega,
                nc.VueltoTotal,
                DATE_FORMAT(nc.FechaEmision, '%d/%m/%Y %H:%i') AS FechaEmision,
                
                resp.des_nombreTrabajador AS NombreResponsable,
                resp.des_apepatTrabajador AS ApellidoResponsable,
                resp.DNITrabajador AS DNIResponsable,
                
                rep.des_nombreTrabajador AS NombreRepartidor,
                rep.des_apepatTrabajador AS ApellidoRepartidor,
                rep.DNITrabajador AS DNIRepartidor
            FROM 
                t28Nota_caja nc
            JOIN 
                t16catalogotrabajadores resp 
                ON nc.IDResponsableCaja = resp.id_Trabajador
            JOIN 
                t16catalogotrabajadores rep 
                ON nc.IDRepartidor = rep.id_Trabajador
            WHERE 
                nc.IDNotaCaja = '$idNotaCaja'";
        
        $resultado = mysqli_query($cn, $sqlCabecera);
        $cabecera = mysqli_fetch_assoc($resultado);
        
        if (!$cabecera) {
            return ['success' => false, 'message' => 'Nota de caja no encontrada'];
        }
        
        // Obtener detalle de contra entregas
        $sqlDetalle = "SELECT 
                t501.IdDet,
                t501.IdOrdenPedido,
                t501.Total,
                t501.EfectivoCliente,
                t501.Vuelto
            FROM t28Nota_caja nc
            JOIN t40ordenasignacionreparto t40
                ON nc.IDAsignacionReparto = t40.Id_OrdenAsignacion
            JOIN t401detalleasignacionreparto t401
                ON t40.Id_OrdenAsignacion = t401.Id_OrdenAsignacion
            JOIN t59ordenservicioentrega t59
                ON t401.Id_OSE = t59.Id_OSE
            JOIN t501detalleopce t501
                ON t59.Id_OrdenPedido = t501.IdOrdenPedido
            WHERE 
                nc.IDNotaCaja = '$idNotaCaja'";
        
        $resultadoDetalle = mysqli_query($cn, $sqlDetalle);
        $detalles = [];
        
        if ($resultadoDetalle) {
            while ($fila = mysqli_fetch_assoc($resultadoDetalle)) {
                $detalles[] = $fila;
            }
        }
        
        // Convertir logo a base64
        $logoPath = __DIR__ . '/../src/Imagen/Logo-MP.png';
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }
        
        // Crear HTML para el PDF (mismo contenido que antes)
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page { margin: 20mm; }
                body { 
                    font-family: Arial, Helvetica, sans-serif; 
                    font-size: 11px;
                    color: #333;
                    line-height: 1.4;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 30px;
                    border-bottom: 3px solid #28a745;
                    padding-bottom: 15px;
                }
                .logo-container {
                    margin-bottom: 15px;
                }
                .logo-container img {
                    max-width: 150px;
                    height: auto;
                }
                .header h1 { 
                    color: #28a745; 
                    font-size: 24px; 
                    margin: 10px 0 5px 0;
                    font-weight: bold;
                }
                .header .subtitle {
                    color: #666;
                    font-size: 12px;
                }
                .id-nota {
                    background-color: #28a745;
                    color: white;
                    padding: 8px 15px;
                    display: inline-block;
                    font-size: 14px;
                    font-weight: bold;
                    margin-top: 10px;
                }
                .section { 
                    margin-bottom: 20px;
                    page-break-inside: avoid;
                }
                .section-title { 
                    background-color: #28a745;
                    color: white;
                    padding: 10px 15px; 
                    font-weight: bold; 
                    font-size: 13px;
                    margin-bottom: 12px;
                }
                .info-grid {
                    width: 100%;
                    border: 1px solid #ddd;
                    background-color: #f9f9f9;
                    padding: 10px;
                    margin-bottom: 10px;
                }
                .info-row { 
                    margin-bottom: 8px;
                }
                .label { 
                    font-weight: bold;
                    color: #555;
                    display: inline-block;
                    width: 180px;
                }
                .value {
                    color: #333;
                    display: inline;
                }
                .two-columns {
                    display: table;
                    width: 100%;
                    margin-bottom: 15px;
                }
                .column {
                    display: table-cell;
                    width: 48%;
                    vertical-align: top;
                }
                .column:first-child {
                    padding-right: 2%;
                }
                .column:last-child {
                    padding-left: 2%;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-top: 10px;
                }
                thead tr {
                    background-color: #343a40;
                }
                th { 
                    background-color: #343a40;
                    color: white; 
                    padding: 12px 8px; 
                    text-align: center;
                    font-weight: bold;
                    font-size: 11px;
                    text-transform: uppercase;
                    border: 1px solid #23272b;
                }
                td { 
                    border: 1px solid #ddd; 
                    padding: 10px 8px; 
                    text-align: center;
                    background-color: white;
                }
                tbody tr:nth-child(even) td { 
                    background-color: #f8f9fa; 
                }
                .totales-box {
                    background-color: #fff3cd;
                    border: 2px solid #ffc107;
                    padding: 15px;
                    margin-top: 15px;
                }
                .totales-row {
                    margin-bottom: 10px;
                    font-size: 13px;
                }
                .totales-label {
                    font-weight: bold;
                    color: #856404;
                    display: inline-block;
                    width: 200px;
                }
                .totales-value {
                    font-size: 16px;
                    font-weight: bold;
                    color: #dc3545;
                }
                .footer { 
                    margin-top: 30px; 
                    padding: 15px;
                    background-color: #e9ecef;
                    border-left: 4px solid #6c757d;
                    font-size: 10px; 
                    color: #495057;
                }
                .badge {
                    display: inline-block;
                    padding: 4px 8px;
                    background-color: #17a2b8;
                    color: white;
                    font-size: 10px;
                    font-weight: bold;
                }
                .td-right {
                    text-align: right !important;
                    padding-right: 15px !important;
                }
            </style>
        </head>
        <body>
            <div class="header">';
        
        if ($logoBase64) {
            $html .= '
                <div class="logo-container">
                    <img src="' . $logoBase64 . '" alt="Mundo Patitas">
                </div>';
        }
        
        $html .= '
                <h1>NOTA DE CAJA PARA DELIVERY</h1>
                <div class="subtitle">Sistema de Gestion de Caja - Mundo Patitas</div>
                <div class="id-nota">ID NOTA DE CAJA: ' . htmlspecialchars($cabecera['IDNotaCaja']) . '</div>
            </div>
            
            <div class="section">
                <div class="info-grid">
                    <div class="info-row">
                        <span class="label">Fecha de Emisión:</span>
                        <span class="value"><strong>' . htmlspecialchars($cabecera['FechaEmision']) . '</strong></span>
                    </div>
                    <div class="info-row">
                        <span class="label">ID Asignación de Reparto:</span>
                        <span class="value"><span class="badge">' . htmlspecialchars($cabecera['IDAsignacionReparto']) . '</span></span>
                    </div>
                </div>
            </div>
            
            <div class="two-columns">
                <div class="column">
                    <div class="section-title">RESPONSABLE DE CAJA</div>
                    <div class="info-grid">
                        <div class="info-row">
                            <span class="label">ID:</span>
                            <span class="value">' . htmlspecialchars($cabecera['IDResponsableCaja']) . '</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Nombre:</span>
                            <span class="value">' . htmlspecialchars($cabecera['NombreResponsable'] . ' ' . $cabecera['ApellidoResponsable']) . '</span>
                        </div>
                        <div class="info-row">
                            <span class="label">DNI:</span>
                            <span class="value">' . htmlspecialchars($cabecera['DNIResponsable']) . '</span>
                        </div>
                    </div>
                </div>
                
                <div class="column">
                    <div class="section-title">REPARTIDOR</div>
                    <div class="info-grid">
                        <div class="info-row">
                            <span class="label">ID:</span>
                            <span class="value">' . htmlspecialchars($cabecera['IDRepartidor']) . '</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Nombre:</span>
                            <span class="value">' . htmlspecialchars($cabecera['NombreRepartidor'] . ' ' . $cabecera['ApellidoRepartidor']) . '</span>
                        </div>
                        <div class="info-row">
                            <span class="label">DNI:</span>
                            <span class="value">' . htmlspecialchars($cabecera['DNIRepartidor']) . '</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">DETALLE DE CONTRA ENTREGAS</div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 15%;">ID DETALLE</th>
                            <th style="width: 20%;">ID ORDEN PEDIDO</th>
                            <th style="width: 20%;">TOTAL</th>
                            <th style="width: 20%;">EFECTIVO CLIENTE</th>
                            <th style="width: 25%;">VUELTO</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        if (empty($detalles)) {
            $html .= '
                        <tr>
                            <td colspan="5" style="text-align: center; color: #666; padding: 20px;">
                                No hay contra entregas registradas
                            </td>
                        </tr>';
        } else {
            foreach ($detalles as $detalle) {
                $html .= '
                        <tr>
                            <td><strong>' . htmlspecialchars($detalle['IdDet']) . '</strong></td>
                            <td>' . htmlspecialchars($detalle['IdOrdenPedido']) . '</td>
                            <td class="td-right">S/ ' . number_format($detalle['Total'], 2) . '</td>
                            <td class="td-right">S/ ' . number_format($detalle['EfectivoCliente'], 2) . '</td>
                            <td class="td-right">S/ ' . number_format($detalle['Vuelto'], 2) . '</td>
                        </tr>';
            }
        }
        
        $html .= '
                    </tbody>
                </table>
            </div>
            
            <div class="totales-box">
                <div class="totales-row">
                    <span class="totales-label">Total Contra Entregas:</span>
                    <span class="totales-value">' . htmlspecialchars($cabecera['TotalContraEntrega']) . ' entregas</span>
                </div>
                <div class="totales-row">
                    <span class="totales-label">Total Vuelto por Conciliar:</span>
                    <span class="totales-value">S/ ' . number_format($cabecera['VueltoTotal'], 2) . '</span>
                </div>
            </div>
            
            <div class="footer">
                <strong>NOTA IMPORTANTE:</strong><br>
                Este documento certifica la recepción de efectivo del repartidor correspondiente a las contra entregas realizadas.<br>
                El repartidor debe conciliar el vuelto total indicado en esta nota de caja.<br>
                Generado automáticamente por el Sistema de Gestión de Caja - Mundo Patitas
            </div>
        </body>
        </html>';
        
        // Configurar DomPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        $options->set('isPhpEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Guardar PDF
        $nombreArchivo = "NotaCaja_" . $cabecera['IDRepartidor'] . "_" . $cabecera['IDNotaCaja'] . ".pdf";
        $carpetaPDFs = __DIR__ . "/../src/Documentos/pdf_nota_caja/";
        
        if (!file_exists($carpetaPDFs)) {
            mkdir($carpetaPDFs, 0777, true);
        }
        
        $rutaCompleta = $carpetaPDFs . $nombreArchivo;
        file_put_contents($rutaCompleta, $dompdf->output());
        
        // Ruta RELATIVA para BD
        $rutaRelativa = "src/Documentos/pdf_nota_caja/" . $nombreArchivo;
        
        // Actualizar BD con mysqli
        $rutaRelativaEscaped = mysqli_real_escape_string($cn, $rutaRelativa);
        $idNotaCajaEscaped = mysqli_real_escape_string($cn, $cabecera['IDNotaCaja']);
        
        $sqlUpdate = "UPDATE t28Nota_caja 
                      SET RutaPDF = '$rutaRelativaEscaped' 
                      WHERE IDNotaCaja = '$idNotaCajaEscaped'";
        mysqli_query($cn, $sqlUpdate);
        
        return [
            'success' => true,
            'message' => 'PDF de nota de caja generado correctamente',
            'rutaArchivo' => $rutaCompleta,
            'rutaRelativa' => $rutaRelativa,
            'nombreArchivo' => $nombreArchivo,
            'idNotaCaja' => $cabecera['IDNotaCaja']
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al generar PDF: ' . $e->getMessage()
        ];
    }
}
?>
