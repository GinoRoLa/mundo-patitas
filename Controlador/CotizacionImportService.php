<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Conexion.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsDate;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

final class CotizacionImportService
{
  /** Carpeta donde se ubican los Excel: REC{idReq}_{ruc}.xlsx */
  public const IMPORT_DIR = __DIR__ . '/../src/Cotizaciones';

  private mysqli $cn;

  public function __construct()
  {
    $this->cn = (new Conexion())->conecta();
  }

  /**
   * Escanea archivos con patrón REC{idReq}_{ruc}.xlsx
   * @param string $idReq     Id del requerimiento
   * @param bool   $withDebug Si true, agrega información de diagnóstico
   */
  public function scanArchivos(string $idReq, bool $withDebug = false): array
  {
    $dirReal = realpath(self::IMPORT_DIR) ?: self::IMPORT_DIR;
    $pattern = rtrim($dirReal, '/\\') . DIRECTORY_SEPARATOR . "REC{$idReq}_*.xlsx";

    $list = [];
    $globRes = glob($pattern);
    if ($globRes) {
      foreach ($globRes as $path) {
        $base = basename($path);
        $meta = self::parseFilename($base);
        if (!$meta) continue;

        $hash = @hash_file('sha256', $path) ?: null;
        $list[] = [
          'file'    => $base,
          'absPath' => $path,
          'idReq'   => $meta['idReq'],
          'ruc'     => $meta['ruc'],
          'size'    => @filesize($path) ?: 0,
          'mtime'   => @date('Y-m-d H:i:s', @filemtime($path) ?: time()),
          'hash'    => $hash,
        ];
      }
    }

    // Obtener hashes ya importados
    $importados = $this->getImportadosByReq((int)$idReq);
    $hashesImportados = array_column($importados, 'hash'); // ← Extrae solo hashes

    // ✅ NUEVO: Filtrar archivos nuevos (no importados)
    $nuevos = array_filter($list, function ($archivo) use ($hashesImportados) {
      return $archivo['hash'] && !in_array($archivo['hash'], $hashesImportados, true);
    });

    $out = [
      'archivos'   => $list,           // Todos los archivos encontrados
      'importados' => $importados,     // Metadata de importados
      'nuevos'     => array_values($nuevos) // ← Solo archivos pendientes de importar
    ];

    if ($withDebug) {
      $out['debug'] = [
        'IMPORT_DIR'     => self::IMPORT_DIR,
        'dirReal'        => $dirReal,
        'dirExists'      => is_dir($dirReal),
        'isReadable'     => is_readable($dirReal),
        'pattern'        => $pattern,
        'globCount'      => $globRes ? count($globRes) : 0,
        'parsedCount'    => count($list),
        'importedCount'  => count($importados),
        'nuevosCount'    => count($nuevos), // ← Debug count
      ];
    }
    return $out;
  }


  private function getImportadosByReq(int $idReq): array
  {
    $q = "SELECT FileHash AS hash, FileName AS name FROM t88ArchivoCotizacion
        WHERE Id_ReqEvaluacion = ? AND ImportStatus IN ('imported','ignored')";
    $st = mysqli_prepare($this->cn, $q);
    mysqli_stmt_bind_param($st, "i", $idReq);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $out = [];
    while ($r = mysqli_fetch_assoc($rs)) $out[] = ['hash' => $r['hash'], 'name' => $r['name']];
    mysqli_stmt_close($st);
    return $out;
  }


  /** Importa por nombre de archivo (dentro de IMPORT_DIR) */
  public function importarPorNombre(string $fileName, bool $overwrite = false): array
  {
    $meta = self::parseFilename($fileName);
    if (!$meta) {
      throw new Exception("Nombre inválido. Formato esperado: REC{idReq}_{ruc}.xlsx");
    }

    $dirReal = realpath(self::IMPORT_DIR) ?: self::IMPORT_DIR;
    $path    = rtrim($dirReal, '/\\') . DIRECTORY_SEPARATOR . $fileName;
    if (!is_file($path)) {
      throw new Exception("Archivo no encontrado: $fileName");
    }

    return $this->importarDesdePath($path, $meta['idReq'], $meta['ruc'], $overwrite);
  }

  /**
   * Importa leyendo Excel y guardando en BD
   * @throws Exception si hay inconsistencias en el Excel
   */
  public function importarDesdePath(string $path, string $idReq, string $ruc, bool $overwrite = false): array
  {
    // ===== 0) Preparación: detectar si existe la tabla índice t88 y calcular hash =====
    $hasT88 = false;
    $chkT88 = @mysqli_query($this->cn, "SHOW TABLES LIKE 't88ArchivoCotizacion'");
    if ($chkT88 && mysqli_num_rows($chkT88) > 0) $hasT88 = true;
    @mysqli_free_result($chkT88);

    $hash  = @hash_file('sha256', $path) ?: null;
    $size  = @filesize($path) ?: 0;
    $mtime = @date('Y-m-d H:i:s', @filemtime($path) ?: time());
    $fileName = basename($path);

    $idReqInt = (int)$idReq;
    

    $fechaCierre = $this->getFechaCierreSolicitud($idReqInt, $ruc);
    if ($fechaCierre !== null && strcmp($mtime, $fechaCierre) > 0) {
      // Llegó DESPUÉS de la fecha de cierre → se ignora

      if ($hasT88 && $hash) {
        try {
          $msg = "Archivo recibido fuera de plazo. Llegó {$mtime}, cierre {$fechaCierre}.";
          $q88 = "INSERT INTO t88ArchivoCotizacion
                  (Id_ReqEvaluacion, RUC_Proveedor, FileName, FileSize, FileHash, LastModified,
                   ImportStatus, ErrorMsg)
                  VALUES (?,?,?,?,?,?,'ignored',?)";
          $s88 = mysqli_prepare($this->cn, $q88);
          mysqli_stmt_bind_param(
            $s88,
            "issssss",
            $idReqInt,
            $ruc,
            $fileName,
            $size,
            $hash,
            $mtime,
            $msg
          );
          mysqli_stmt_execute($s88);
          mysqli_stmt_close($s88);
        } catch (\Throwable $_) {
          // best-effort: no romper si falla el log
        }
      }

      return [
        'ok'      => true,
        'skipped' => true,
        'code'    => 'AFTER_DEADLINE',
        'message' => "El archivo {$fileName} llegó fuera de plazo y no se considerará en la evaluación.",
        'idReq'   => $idReqInt,
        'ruc'     => $ruc,
        'file'    => $fileName,
      ];
    }

    // ===== 1) Leer Excel =====
    $ss = IOFactory::load($path);
    /** @var Worksheet $ws */
    $ws = $ss->getSheetByName('COTIZACION') ?? $ss->getActiveSheet();

    $meta         = $this->leerMetadatos($ws);
    $fecEmision   = isset($meta['FechaEmision']) ? $this->toDateYmd($meta['FechaEmision']) : date('Y-m-d');
    $fecEntrega   = isset($meta['FechaEntrega']) ? $this->toDateYmd($meta['FechaEntrega']) : date('Y-m-d');
    $obs          = $meta['Observaciones'] ?? null;

    // Encabezado de detalle
    [$startRow, $colMap] = $this->buscarHeader($ws, ['Id_Producto', 'Descripcion', 'CantidadOfertada', 'PrecioUnitario']);
    if ($startRow === null) {
      throw new Exception("No se encontró el encabezado del detalle en la hoja.");
    }

    // Lee filas de detalle
    $detalle = [];
    $r = $startRow + 1;
    while (true) {
      $idProd = trim((string)$this->valBy($ws, $r, $colMap['Id_Producto']));
      $desc   = trim((string)$this->valBy($ws, $r, $colMap['Descripcion']));
      $cant   = $this->num($this->valBy($ws, $r, $colMap['CantidadOfertada']));
      $pu     = $this->num($this->valBy($ws, $r, $colMap['PrecioUnitario']));

      // fin al encontrar fila totalmente vacía
      if ($idProd === '' && $desc === '' && $cant === null && $pu === null) break;

      if ($idProd === '' || $cant === null || $pu === null) {
        throw new Exception("Fila $r: faltan Id_Producto, CantidadOfertada o PrecioUnitario.");
      }

      $detalle[] = [
        'Id_Producto'      => (int)$idProd,
        'Descripcion'      => $desc,
        'CantidadOfertada' => (int)$cant,
        'PrecioUnitario'   => (float)$pu,
      ];

      $r++;
      if ($r > 50000) throw new Exception("Demasiadas filas sin fin claro.");
    }

    if (count($detalle) === 0) {
      throw new Exception("No hay filas de detalle para importar.");
    }

    // Validar existencia de productos
    $ids       = array_map(fn($d) => (int)$d['Id_Producto'], $detalle);
    $presentes = $this->existenProductos($ids);
    foreach ($detalle as $i => $d) {
      if (empty($presentes[(int)$d['Id_Producto']])) {
        $filaExcel = $startRow + 1 + $i;
        throw new Exception("Producto inexistente en BD (Id_Producto={$d['Id_Producto']}) en fila $filaExcel.");
      }
    }

    // ===== 2) Persistir =====
    mysqli_begin_transaction($this->cn);
    try {
      $this->verificarCompatibilidadFK();

      // Forzar int
      //$idReqInt = (int)$idReq;
      $nroCotProv = $meta['NroCotizacion'] ?? null;

      // Idempotencia por (Req,RUC) cuando no hay t88 o no hay hash
      if (!$overwrite) {
        if ($this->existeCotizacionReqRuc($idReqInt, $ruc)) {
          mysqli_commit($this->cn); // no cambios
          return [
            'ok'      => true,
            'skipped' => true,
            'code'    => 'ALREADY_EXISTS',
            'message' => "Ya existe una cotización previa para REQ {$idReqInt} y proveedor {$ruc}. No se importó.",
            'idReq'   => $idReqInt,
            'ruc'     => $ruc,
            'file'    => $fileName,
          ];
        }



        // Opcional: distinguir por FechaEmision
        $idPrev = $this->buscarCotizacion($idReqInt, $ruc, $fecEmision);
        if ($idPrev) {
          mysqli_commit($this->cn);
          return [
            'ok'      => true,
            'skipped' => true,
            'code'    => 'ALREADY_EXISTS_SAME_DATE',
            'message' => "Ya existe cotización para REQ {$idReqInt}, RUC {$ruc} y FechaEmision {$fecEmision}. No se importó.",
            'idReq'   => $idReqInt,
            'ruc'     => $ruc,
            'file'    => $fileName,
          ];
        }
      } else {
        // overwrite=true → si existe por fecha, borrarlo
        $idPrev = $this->buscarCotizacion($idReqInt, $ruc, $fecEmision);
        if ($idPrev) {
          $this->borrarCotizacion($idPrev);
        }
      }

      // Cabecera
      $sqlCab = "INSERT INTO t86Cotizacion (Id_ReqEvaluacion, RUC_Proveedor, NroCotizacionProv,
                   FechaEmision, FechaEntrega, Observaciones,
                   SubTotal, IGV, Total, Estado)
                 VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0, 'Recibida')";
      $sc = mysqli_prepare($this->cn, $sqlCab);
      mysqli_stmt_bind_param($sc, "isssss", $idReqInt, $ruc, $nroCotProv, $fecEmision, $fecEntrega, $obs);
      mysqli_stmt_execute($sc);
      $idCot = (int)mysqli_insert_id($this->cn);
      mysqli_stmt_close($sc);

      // Detalle
      $sub = 0.0;
      $sqlDet = "INSERT INTO t87DetalleCotizacion (Id_Cotizacion, Descripcion, Id_Producto, CantidadOfertada, PrecioUnitario)
               VALUES (?, ?, ?, ?, ?)";
      $sd = mysqli_prepare($this->cn, $sqlDet);
      foreach ($detalle as $d) {
        mysqli_stmt_bind_param(
          $sd,
          "isidd",
          $idCot,
          $d['Descripcion'],
          $d['Id_Producto'],
          $d['CantidadOfertada'],
          $d['PrecioUnitario']
        );
        mysqli_stmt_execute($sd);
        $sub += round($d['CantidadOfertada'] * $d['PrecioUnitario'], 2);
      }
      mysqli_stmt_close($sd);

      // Totales
      $igv = round($sub * 0.18, 2);
      $tot = round($sub + $igv, 2);
      $up  = mysqli_prepare($this->cn, "UPDATE t86Cotizacion SET SubTotal=?, IGV=?, Total=? WHERE Id_Cotizacion=?");
      mysqli_stmt_bind_param($up, "dddi", $sub, $igv, $tot, $idCot);
      mysqli_stmt_execute($up);
      mysqli_stmt_close($up);

      // Registrar en t88 si existe
      if ($hasT88 && $hash) {
        // Si ya había una fila por mismo hash y overwrite=true, la dejamos como está (única hash).
        // Si no existía, se inserta como importado.
        $st = mysqli_prepare($this->cn, "SELECT Id_Archivo FROM t88ArchivoCotizacion WHERE FileHash=? LIMIT 1");
        mysqli_stmt_bind_param($st, "s", $hash);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        $row = $rs ? mysqli_fetch_assoc($rs) : null;
        mysqli_stmt_close($st);

        if (!$row) {
          $q88 = "INSERT INTO t88ArchivoCotizacion
                (Id_ReqEvaluacion,RUC_Proveedor,FileName,FileSize,FileHash,LastModified,ImportStatus,Id_Cotizacion)
                VALUES (?,?,?,?,? ,?,'imported',?)";
          $s88 = mysqli_prepare($this->cn, $q88);
          mysqli_stmt_bind_param(
            $s88,
            "isssssi",
            $idReqInt,
            $ruc,
            $fileName,
            $size,
            $hash,
            $mtime,
            $idCot
          );
          mysqli_stmt_execute($s88);
          mysqli_stmt_close($s88);
        } else {
          // ya existe fila por hash: nos aseguramos de marcar que está importado y linkear a Id_Cotizacion si no lo tiene
          $q88u = "UPDATE t88ArchivoCotizacion
                 SET ImportStatus='imported', Id_Cotizacion=COALESCE(Id_Cotizacion, ?)
                 WHERE Id_Archivo=?";
          $s88u = mysqli_prepare($this->cn, $q88u);
          $idArchivo = (int)$row['Id_Archivo'];
          mysqli_stmt_bind_param($s88u, "ii", $idCot, $idArchivo);
          mysqli_stmt_execute($s88u);
          mysqli_stmt_close($s88u);
        }
      }

      mysqli_commit($this->cn);

      $resp = [
        'ok'           => true,
        'idCotizacion' => $idCot,
        'resumen'      => ['SubTotal' => $sub, 'IGV' => $igv, 'Total' => $tot],
        'items'        => count($detalle),
        'idReq'        => $idReqInt,
        'ruc'          => $ruc,
        'file'         => $fileName,
      ];
      return $resp;
    } catch (\Throwable $e) {
      mysqli_rollback($this->cn);

      // Si existe t88 y el hash, registra error (best-effort; no interrumpe la excepción)
      if ($hasT88 && $hash) {
        try {
          $q88e = "INSERT INTO t88ArchivoCotizacion
                 (Id_ReqEvaluacion,RUC_Proveedor,FileName,FileSize,FileHash,LastModified,ImportStatus,ErrorMsg)
                 VALUES (?,?,?,?,? ,?,'error',?)";
          $s88e = mysqli_prepare($this->cn, $q88e);
          mysqli_stmt_bind_param(
            $s88e,
            "issssss",
            $idReq,
            $ruc,
            $fileName,
            $size,
            $hash,
            $mtime,
            $e->getMessage()
          );
          mysqli_stmt_execute($s88e);
          mysqli_stmt_close($s88e);
        } catch (\Throwable $_) {
        }
      }

      throw $e;
    }
  }

  // ===== BD helpers =====

  /** Asegura que la FK Id_ReqEvaluacion sea de tipo INT o VARCHAR (flexible en tu evolución de esquema) */
  private function verificarCompatibilidadFK(): void
  {
    $rs = mysqli_query($this->cn, "SHOW COLUMNS FROM t86Cotizacion LIKE 'Id_ReqEvaluacion'");
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    if (!$row) throw new Exception("Columna Id_ReqEvaluacion no existe en t86Cotizacion");
    $type = strtolower($row['Type'] ?? '');
    if (stripos($type, 'int') === false && stripos($type, 'varchar') === false) {
      throw new Exception("t86Cotizacion.Id_ReqEvaluacion debe ser INT o VARCHAR. Tipo actual: {$type}");
    }
  }

  /** Busca cotización específica por (Req, RUC, FechaEmision) y devuelve Id_Cotizacion si existe */
  private function buscarCotizacion(int $idReq, string $ruc, string $fecEmision): ?int
  {
    $sql = "SELECT Id_Cotizacion FROM t86Cotizacion
            WHERE Id_ReqEvaluacion=? AND RUC_Proveedor=? AND FechaEmision=?";
    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "iss", $idReq, $ruc, $fecEmision);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    mysqli_stmt_close($st);
    return $row ? (int)$row['Id_Cotizacion'] : null;
  }

  /** Borra una cotización por Id (se usa cuando overwrite=true) */
  private function borrarCotizacion(int $idCot): void
  {
    $st = mysqli_prepare($this->cn, "DELETE FROM t86Cotizacion WHERE Id_Cotizacion=?");
    mysqli_stmt_bind_param($st, "i", $idCot);
    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
  }

  /** Verifica si existen productos por Id en t18CatalogoProducto */
  private function existenProductos(array $ids): array
  {
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (empty($ids)) return [];
    $place = implode(',', array_fill(0, count($ids), '?'));
    $sql   = "SELECT Id_Producto FROM t18CatalogoProducto WHERE Id_Producto IN ($place)";
    $st    = mysqli_prepare($this->cn, $sql);
    $types = str_repeat('i', count($ids));
    mysqli_stmt_bind_param($st, $types, ...$ids);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $ok = [];
    while ($r = mysqli_fetch_assoc($rs)) {
      $ok[(int)$r['Id_Producto']] = true;
    }
    mysqli_stmt_close($st);
    return $ok;
  }

  /** Devuelve true si ya existe cualquier cotización para (Id_ReqEvaluacion, RUC_Proveedor) */
  private function existeCotizacionReqRuc(int $idReq, string $ruc): bool
  {
    $st = mysqli_prepare($this->cn, "SELECT 1 FROM t86Cotizacion WHERE Id_ReqEvaluacion=? AND RUC_Proveedor=? LIMIT 1");
    mysqli_stmt_bind_param($st, "is", $idReq, $ruc);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $ok = (bool)($rs && mysqli_fetch_row($rs));
    mysqli_stmt_close($st);
    return $ok;
  }

  // ===== Lectura Excel =====
  private function leerMetadatos(Worksheet $ws): array
  {
    $pairs = [
      'RUC'            => 'RUC',
      'FechaEmision'   => 'FechaEmision',
      'FechaEntrega'   => 'FechaEntrega',
      'Moneda'         => 'Moneda',
      'Observaciones'  => 'Observaciones',
    ];

    $meta = [];

    // 🔹 Buscar en las primeras 10 filas las claves estándar
    for ($r = 1; $r <= 80; $r++) {
      for ($c = 1; $c <= 10; $c++) {
        $label = trim((string)$this->valBy($ws, $r, $c));
        if (!$label) continue;
        if (isset($pairs[$label])) {
          $val = $this->valBy($ws, $r, $c + 1);
          $meta[$label] = $val;
        }

        // 🔹 Detección especial del formato “Cotizacion N°”
        if (preg_match('/^Cotizaci[oó]n\s*N/i', $label)) {
          $val = $this->valBy($ws, $r, $c + 1);
          $meta['NroCotizacion'] = trim((string)$val);
        }
      }
    }

    return $meta;
  }

  /**
   * Encuentra la fila encabezado e índices de columnas requeridas
   * @return array{0:int|null,1:array} [$row, $map]
   */
  private function buscarHeader(Worksheet $ws, array $expected): array
  {
    $expectedLower = array_map('mb_strtolower', $expected);
    for ($r = 1; $r <= 200; $r++) {
      $labels = [];
      for ($c = 1; $c <= 30; $c++) {
        $labels[$c] = mb_strtolower(trim((string)$this->valBy($ws, $r, $c)));
      }
      $map = [];
      foreach ($expectedLower as $idx => $name) {
        $colIdx = array_search($name, $labels, true);
        if ($colIdx === false) {
          $map = [];
          break;
        }
        $map[$expected[$idx]] = $colIdx;
      }
      if (!empty($map)) return [$r, $map];
    }
    return [null, []];
  }

    /**
   * Devuelve la última FechaCierre para (Id_ReqEvaluacion, RUC) o null si no hay solicitud.
   */
  /* private function getFechaCierreSolicitud(int $idReq, string $ruc): ?string
  {
    $sql = "SELECT MAX(FechaCierre) AS fc
            FROM t100Solicitud_Cotizacion_Proveedor
            WHERE Id_ReqEvaluacion = ? AND RUC = ?";
    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "is", $idReq, $ruc);
    mysqli_stmt_execute($st);
    $rs  = mysqli_stmt_get_result($st);
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    mysqli_stmt_close($st);

    $fc = $row['fc'] ?? null;
    return $fc ? substr((string)$fc, 0, 19) : null; // 'YYYY-mm-dd HH:ii:ss'
  } */

  private function getFechaCierreSolicitud(int $idReq, string $ruc): ?string{
    // Calculamos la fecha de cierre en MySQL: 10 días después
    $sql = "SELECT DATE_ADD(MAX(FechaEnvio), INTERVAL 10 DAY) AS fc
            FROM t100Solicitud_Cotizacion_Proveedor
            WHERE Id_ReqEvaluacion = ? AND RUC = ?";

    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "is", $idReq, $ruc);
    mysqli_stmt_execute($st);
    $rs  = mysqli_stmt_get_result($st);
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    mysqli_stmt_close($st);

    $fc = $row['fc'] ?? null;
    // Devuelve 'YYYY-mm-dd HH:ii:ss' o null si no hay solicitudes
    return $fc ? substr((string)$fc, 0, 19) : null;
  }



  /** Lee una celda usando columna numérica (A=1, B=2, …) */
  private function valBy(Worksheet $ws, int $row, int $col)
  {
    $colStr = Coordinate::stringFromColumnIndex($col);
    return $ws->getCell($colStr . $row)->getCalculatedValue();
  }

  /** Normaliza a número (float) */
  private function num($v): ?float
  {
    if ($v === null || $v === '') return null;
    if (is_numeric($v)) return (float)$v;
    $s = str_replace([',', ' '], ['', ''], (string)$v);
    $s = preg_replace('/[^\d\.\-]/', '', $s);
    return is_numeric($s) ? (float)$s : null;
  }

  /** Convierte Excel date o string a Y-m-d */
  private function toDateYmd($v): string
  {
    if ($v === null || $v === '') return date('Y-m-d');
    if (is_numeric($v)) {
      $ts = XlsDate::excelToTimestamp((float)$v);
      return date('Y-m-d', $ts);
    }
    return substr((string)$v, 0, 10);
  }

  /** Convierte Excel date o string a Y-m-d H:i:s */
  private function toDateYmdHis($v): string
  {
    if ($v === null || $v === '') return date('Y-m-d H:i:s');
    if (is_numeric($v)) {
      $ts = XlsDate::excelToTimestamp((float)$v);
      return date('Y-m-d H:i:s', $ts);
    }
    $s = preg_replace('/[TZ]/', ' ', (string)$v);
    return substr($s, 0, 19);
  }

  /** Parser de nombre: REC{idReq}_{ruc}.xlsx */
  public static function parseFilename(string $file): ?array
  {
    $base = pathinfo($file, PATHINFO_FILENAME);
    if (!preg_match('/^REC([A-Za-z0-9\-]+)_([0-9]{11})$/', $base, $m)) return null;
    return ['idReq' => $m[1], 'ruc' => $m[2]];
  }
}
