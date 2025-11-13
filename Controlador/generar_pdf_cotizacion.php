<?php
require_once(__DIR__ . '/../vendor/autoload.php');

use Dompdf\Dompdf;
use Dompdf\Options;

function generarPDFCotizacion($pdo, $idSolicitud) {
    try {
        // Obtener datos de la solicitud y proveedor
        $sqlCabecera = "SELECT 
                s.IDsolicitud,
                p.des_RazonSocial AS Proveedor,
                s.RUC,
                p.DireccionProv AS Direccion,
                s.Correo,
                s.Id_ReqEvaluacion AS Requerimiento_ID,
                DATE_FORMAT(s.FechaEnvio, '%d/%m/%Y') AS Fecha_Envio
            FROM 
                t100Solicitud_Cotizacion_Proveedor s
            JOIN 
                t17CatalogoProveedor p 
                ON s.RUC = p.Id_NumRuc
            WHERE 
                s.IDsolicitud = :idSolicitud";
        
        $stmt = $pdo->prepare($sqlCabecera);
        $stmt->bindParam(':idSolicitud', $idSolicitud, PDO::PARAM_INT);
        $stmt->execute();
        $cabecera = $stmt->fetch();
        
        if (!$cabecera) {
            return ['success' => false, 'message' => 'Solicitud no encontrada'];
        }
        
        // Obtener productos de la solicitud
        $sqlDetalle = "SELECT 
                d.Id_Producto AS ID,
                p.NombreProducto AS Producto,
                d.Cantidad
            FROM 
                t101Detalle_Solicitud_Cotizacion_Proveedor d
            JOIN 
                t18CatalogoProducto p 
                ON d.Id_Producto = p.Id_Producto
            WHERE 
                d.IDsolicitud = :idSolicitud";
        
        $stmt = $pdo->prepare($sqlDetalle);
        $stmt->bindParam(':idSolicitud', $idSolicitud, PDO::PARAM_INT);
        $stmt->execute();
        $productos = $stmt->fetchAll();
        
        if (empty($productos)) {
            return ['success' => false, 'message' => 'No hay productos en esta solicitud'];
        }
        
        // Convertir logo a base64
        $logoPath = __DIR__ . '/../src/Imagen/Logo-MP.png';
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }
        
        // Crear HTML para el PDF (mismo código HTML que ya tienes)
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
                    border-bottom: 3px solid #007bff;
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
                    color: #007bff; 
                    font-size: 24px; 
                    margin: 10px 0 5px 0;
                    font-weight: bold;
                }
                .header .subtitle {
                    color: #666;
                    font-size: 12px;
                }
                .section { 
                    margin-bottom: 20px;
                    page-break-inside: avoid;
                }
                .section-productos {
                    page-break-before: always;
                    margin-bottom: 20px;
                }
                .section-title { 
                    background-color: #007bff;
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
                    width: 140px;
                }
                .value {
                    color: #333;
                    display: inline;
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
                .footer { 
                    margin-top: 30px; 
                    padding: 15px;
                    background-color: #fff3cd;
                    border-left: 4px solid #ffc107;
                    font-size: 10px; 
                    color: #856404;
                }
                .footer strong {
                    color: #856404;
                    font-size: 11px;
                }
                .highlight { 
                    color: #dc3545; 
                    font-weight: bold;
                    background-color: #ffe6e6;
                    padding: 2px 6px;
                }
                .badge {
                    display: inline-block;
                    padding: 4px 8px;
                    background-color: #17a2b8;
                    color: white;
                    font-size: 10px;
                    font-weight: bold;
                }
                .plazo-box {
                    background-color: #fff3cd;
                    border: 2px solid #ffc107;
                    padding: 12px;
                    margin-top: 10px;
                    text-align: center;
                }
                .plazo-box .plazo-title {
                    font-size: 13px;
                    font-weight: bold;
                    color: #856404;
                    margin-bottom: 5px;
                }
                .plazo-box .plazo-dias {
                    font-size: 20px;
                    font-weight: bold;
                    color: #dc3545;
                }
                .td-left {
                    text-align: left !important;
                    padding-left: 15px !important;
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
                <h1>SOLICITUD DE COTIZACION</h1>
                <div class="subtitle">Sistema de Gestion de Compras - Mundo Patitas</div>
            </div>
            
            <div class="section">
                <div class="section-title">DATOS DEL PROVEEDOR</div>
                <div class="info-grid">
                    <div class="info-row">
                        <span class="label">Razon Social:</span>
                        <span class="value">' . htmlspecialchars($cabecera['Proveedor']) . '</span>
                    </div>
                    <div class="info-row">
                        <span class="label">RUC:</span>
                        <span class="value"><span class="badge">' . htmlspecialchars($cabecera['RUC']) . '</span></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Correo Electronico:</span>
                        <span class="value">' . htmlspecialchars($cabecera['Correo']) . '</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Direccion:</span>
                        <span class="value">' . htmlspecialchars($cabecera['Direccion'] ?: 'No especificada') . '</span>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">INFORMACION DEL REQUERIMIENTO</div>
                <div class="info-grid">
                    <div class="info-row">
                        <span class="label">ID Requerimiento:</span>
                        <span class="value"><strong>' . htmlspecialchars($cabecera['Requerimiento_ID']) . '</strong></span>
                    </div>
                    <div class="info-row">
                        <span class="label">ID Solicitud:</span>
                        <span class="value"><strong>' . htmlspecialchars($cabecera['IDsolicitud']) . '</strong></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Fecha de Envio:</span>
                        <span class="value">' . htmlspecialchars($cabecera['Fecha_Envio']) . '</span>
                    </div>
                </div>
                
                <div class="plazo-box">
                    <div class="plazo-title">PLAZO PARA PRESENTAR COTIZACION</div>
                    <div class="plazo-dias">10 DIAS HABILES</div>
                    <div style="font-size: 10px; color: #856404; margin-top: 5px;">
                        (Contados desde la fecha de envio de esta solicitud)
                    </div>
                </div>
            </div>
            
            <div class="section-productos">
                <div class="section-title">PRODUCTOS A COTIZAR</div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 15%;">CODIGO</th>
                            <th style="width: 60%;">DESCRIPCION DEL PRODUCTO</th>
                            <th style="width: 25%;">CANTIDAD SOLICITADA</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($productos as $producto) {
            $html .= '
                        <tr>
                            <td><strong>' . htmlspecialchars($producto['ID']) . '</strong></td>
                            <td class="td-left">' . htmlspecialchars($producto['Producto']) . '</td>
                            <td><strong>' . htmlspecialchars($producto['Cantidad']) . '</strong></td>
                        </tr>';
        }
        
        $html .= '
                    </tbody>
                </table>
            </div>
            
            <div class="footer">
                <strong>INSTRUCCIONES IMPORTANTES:</strong><br>
                - Por favor, envie su cotizacion dentro del plazo de 10 dias habiles indicado arriba.<br>
                - Incluya precios unitarios y totales para cada producto.<br>
                - Especifique tiempos de entrega y condiciones de pago.<br>
                - Cualquier consulta, contactenos a: <strong>mundopatitas.venta@gmail.com</strong>
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
        
        // 🎯 CAMBIO: Guardar en carpeta PERMANENTE
        $nombreArchivo = "Cotizacion_" . $cabecera['RUC'] . "_" . $idSolicitud . ".pdf";
        $carpetaPDFs = __DIR__ . "/../src/Documentos/pdf_cotizaciones/"; // Carpeta permanente
        
        // Crear directorio si no existe
        if (!file_exists($carpetaPDFs)) {
            mkdir($carpetaPDFs, 0777, true);
        }
        
        $rutaCompleta = $carpetaPDFs . $nombreArchivo;
        file_put_contents($rutaCompleta, $dompdf->output());
        
        // 🎯 Ruta RELATIVA para guardar en BD
        $rutaRelativa = "src/Documentos/pdf_cotizaciones/" . $nombreArchivo;
        
        // 🎯 NUEVO: Actualizar la BD con la ruta del PDF
        $sqlUpdate = "UPDATE t100Solicitud_Cotizacion_Proveedor 
                      SET RutaPDF = :rutaPDF 
                      WHERE IDsolicitud = :idSolicitud";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindParam(':rutaPDF', $rutaRelativa);
        $stmtUpdate->bindParam(':idSolicitud', $idSolicitud, PDO::PARAM_INT);
        $stmtUpdate->execute();
        
        return [
            'success' => true,
            'message' => 'PDF generado y guardado correctamente',
            'rutaArchivo' => $rutaCompleta, // Ruta completa para enviar email
            'rutaRelativa' => $rutaRelativa, // Ruta relativa guardada en BD
            'nombreArchivo' => $nombreArchivo,
            'correoProveedor' => $cabecera['Correo'],
            'razonSocial' => $cabecera['Proveedor']
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al generar PDF: ' . $e->getMessage()
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error general: ' . $e->getMessage()
        ];
    }
}
?>