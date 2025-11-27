use mundo_patitas3;

-- -------------------------------------------------------------------------------------------------

DROP TABLE IF EXISTS t99_proveedores_productos;
DROP TABLE IF EXISTS t101detalle_solicitud_cotizacion_proveedor;
DROP TABLE IF EXISTS t100Solicitud_Cotizacion_Proveedor;

-- -------------------------------------------------------------------------------------------------

CREATE TABLE t99_proveedores_productos (
    Id_ProvProd INT AUTO_INCREMENT PRIMARY KEY,
    Id_NumRuc VARCHAR(11) NOT NULL,
    Id_Producto INT NOT NULL,
    CONSTRAINT fk_99_prov FOREIGN KEY (Id_NumRuc)
        REFERENCES t17catalogoproveedor(Id_NumRuc)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_99_prod FOREIGN KEY (Id_Producto)
        REFERENCES t18catalogoproducto(Id_Producto)
        ON UPDATE CASCADE ON DELETE CASCADE
);

-- -------------------------------------------------------------------------------------------------

CREATE TABLE t100Solicitud_Cotizacion_Proveedor (
  IDsolicitud INT NOT NULL AUTO_INCREMENT,
  Id_ReqEvaluacion INT NOT NULL,
  RUC VARCHAR(11) NOT NULL,
  Empresa VARCHAR(50) NOT NULL,
  Correo VARCHAR(100) NOT NULL,
  RutaPDF VARCHAR(255) NULL,
  FechaEnvio DATETIME NOT NULL DEFAULT NOW(),
  Estado VARCHAR(20) NOT NULL DEFAULT 'Pendiente',
  PRIMARY KEY (IDsolicitud),
  KEY fk_t100_t407 (Id_ReqEvaluacion),
  CONSTRAINT fk_t100_t407 FOREIGN KEY (Id_ReqEvaluacion)
    REFERENCES t407RequerimientoEvaluado (Id_ReqEvaluacion)
    ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB 
AUTO_INCREMENT=1000 
DEFAULT CHARSET=utf8mb4 
COLLATE=utf8mb4_0900_ai_ci;


-- -------------------------------------------------------------------------------------------------

CREATE TABLE t101Detalle_Solicitud_Cotizacion_Proveedor (
  IDdetalle_solicitud INT NOT NULL AUTO_INCREMENT,
  IDsolicitud INT NOT NULL,
  Id_Producto INT NOT NULL,
  Producto VARCHAR(100) NOT NULL,
  Cantidad INT NOT NULL,
  PRIMARY KEY (IDdetalle_solicitud),
  KEY fk_t101_solicitud (IDsolicitud),
  KEY fk_t101_producto (Id_Producto),
  CONSTRAINT fk_t101_solicitud FOREIGN KEY (IDsolicitud)
    REFERENCES t100Solicitud_Cotizacion_Proveedor (IDsolicitud)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_t101_producto FOREIGN KEY (Id_Producto)
    REFERENCES t18CatalogoProducto (Id_Producto)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT chk_cantidad_detalle CHECK (Cantidad > 0)
) ENGINE=InnoDB AUTO_INCREMENT=1000 
DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- -------------------------------------------------------------------------------------------------

-- -------------------------------------------------------------------------------------------------

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_GenerarSolicitudCotizacionPorProveedor$$

CREATE PROCEDURE sp_GenerarSolicitudCotizacionPorProveedor(
    IN p_Id_ReqEvaluacion INT,
    IN p_RUC VARCHAR(11),
    IN p_Empresa VARCHAR(50),
    IN p_Correo VARCHAR(100)
)
BEGIN
    DECLARE v_IDsolicitud INT;
    DECLARE v_productos_insertados INT;

    -- Paso 1: Crear la cabecera de la solicitud
    INSERT INTO t100Solicitud_Cotizacion_Proveedor (
        Id_ReqEvaluacion, 
        RUC, 
        Empresa, 
        Correo,
        Estado
    )
    VALUES (
        p_Id_ReqEvaluacion, 
        p_RUC, 
        p_Empresa, 
        p_Correo,
        'Pendiente'
    );

    SET v_IDsolicitud = LAST_INSERT_ID();

    -- Paso 2: Insertar solo los productos que el proveedor vende
    INSERT INTO t101Detalle_Solicitud_Cotizacion_Proveedor (
        IDsolicitud, 
        Id_Producto, 
        Producto, 
        Cantidad
    )
    SELECT 
        v_IDsolicitud,
        det.Id_Producto,
        prod.NombreProducto,
        det.Cantidad
    FROM t408DetalleReqEvaluado det
    INNER JOIN t18CatalogoProducto prod 
        ON det.Id_Producto = prod.Id_Producto
    INNER JOIN t99_proveedores_productos pp
        ON pp.Id_Producto = det.Id_Producto
        AND pp.Id_NumRuc = p_RUC
    WHERE det.Id_ReqEvaluacion = p_Id_ReqEvaluacion;

    -- Paso 3: Verificar si se insertaron productos
    SET v_productos_insertados = ROW_COUNT();

    -- Paso 4: Si no se insertó ningún producto, eliminar la solicitud
    IF v_productos_insertados = 0 THEN
        DELETE FROM t100Solicitud_Cotizacion_Proveedor 
        WHERE IDsolicitud = v_IDsolicitud;
        
        SELECT 0 AS IDsolicitud_Generado, 
               0 AS Productos_Insertados,
               'Este proveedor no vende ninguno de los productos solicitados' AS Mensaje;
    ELSE
        SELECT v_IDsolicitud AS IDsolicitud_Generado, 
               v_productos_insertados AS Productos_Insertados,
               'Solicitud generada exitosamente' AS Mensaje;
    END IF;
END$$

DELIMITER ;


-- -------------------------------------------------------------------------------------------------











