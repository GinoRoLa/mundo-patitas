-- ==========================================================
-- Procedimientos Orden CUS24
-- ==========================================================
DROP PROCEDURE IF EXISTS sp_cus24_get_asignacion_encabezado;
DELIMITER $$
CREATE PROCEDURE sp_cus24_get_asignacion_encabezado(IN pIdAsignacion INT)
SQL SECURITY INVOKER
BEGIN
  SELECT 
    t40.Id_OrdenAsignacion                           AS id,
    t79.Id_AsignacionRepartidorVehiculo              AS idAsignacionRV,
    t40.FechaProgramada                              AS fechaProgramada,
    t40.FecCreacion                                  AS fecCreacion,
    t40.Estado                                       AS estado,
    t79.Id_Trabajador                                AS idTrabajador,
    t16.DNITrabajador                                AS dni,
    t16.des_nombreTrabajador                         AS nombre,
    t16.des_apepatTrabajador                         AS apePat,
    t16.des_apematTrabajador                         AS apeMat,
    t16.num_telefono                                 AS telefono,
    t16.email                                        AS email,
    t16.cargo                                        AS cargo,
    t41.Num_Licencia                                 AS numLicencia,
    t41.Estado                                       AS licenciaEstado,
    t79.Id_Vehiculo                                  AS idVehiculo,
    t78.Marca                                        AS vehMarca,
    t78.Placa                                        AS vehPlaca,
    t78.Modelo                                       AS vehModelo
  FROM t40OrdenAsignacionReparto t40
  JOIN t79AsignacionRepartidorVehiculo t79
       ON t79.Id_AsignacionRepartidorVehiculo = t40.Id_AsignacionRepartidorVehiculo
  JOIN t16CatalogoTrabajadores t16 ON t16.id_Trabajador = t79.Id_Trabajador
  JOIN t78Vehiculo t78             ON t78.Id_Vehiculo   = t79.Id_Vehiculo
  LEFT JOIN t41LicenciaConductor t41
       ON t41.id_Trabajador = t16.id_Trabajador AND t41.Estado = 'Vigente'
  WHERE t40.Id_OrdenAsignacion = pIdAsignacion
  LIMIT 1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_cus24_get_asignacion_pedidos;
DELIMITER $$
CREATE PROCEDURE sp_cus24_get_asignacion_pedidos(IN pIdAsignacion INT)
SQL SECURITY INVOKER
BEGIN
  SELECT
    t02.Id_OrdenPedido                                 AS idOrdenPedido,
    t59.Id_OSE                                         AS idOSE,
    t02.Estado                                         AS estadoOP,
    CONCAT(c.des_nombreCliente,' ',c.des_apepatCliente,' ',c.des_apematCliente) AS cliente,
    t71.ReceptorDniSnap                                AS receptorDni,
    t71.NombreContactoSnap                             AS receptorNombre,
    t71.DireccionSnap                                  AS direccion,
    t71.Id_Distrito                                    AS distritoId,
    d.DescNombre                                       AS distritoNombre
  FROM t401DetalleAsignacionReparto d401
  JOIN t59OrdenServicioEntrega t59
       ON t59.Id_OSE = d401.Id_OSE
  JOIN t02OrdenPedido t02
       ON t02.Id_OrdenPedido = t59.Id_OrdenPedido
  LEFT JOIN t71OrdenDirecEnvio t71
       ON t71.Id_OrdenPedido = t02.Id_OrdenPedido
  LEFT JOIN t77DistritoEnvio d
       ON d.Id_Distrito = t71.Id_Distrito
  LEFT JOIN t20Cliente c
       ON c.Id_Cliente = t02.Id_Cliente
  WHERE d401.Id_OrdenAsignacion = pIdAsignacion
    AND t02.Estado = 'Pagado'
  ORDER BY d.DescNombre, t71.DireccionSnap, t02.Id_OrdenPedido;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_cus24_get_items_por_orden;
DELIMITER $$
CREATE PROCEDURE sp_cus24_get_items_por_orden(IN pIdOP INT)
SQL SECURITY INVOKER
BEGIN
  SELECT 
    op.Id_OrdenPedido                           AS idOP,
    d.Id_DetOrdenPedido                         AS idDet,
    p.Id_Producto                               AS idProducto,
    p.NombreProducto                            AS nombreProducto,
    p.Descripcion                               AS descripcion,
    p.Marca                                     AS marca,
    p.PrecioUnitario                            AS precio,
    d.Cantidad                                  AS cantidad,
    um.Descripcion                              AS unidad,
    t71.ReceptorDniSnap                         AS receptorDni,
    t71.NombreContactoSnap                      AS receptorNombre,
    t71.DireccionSnap                           AS direccionSnap,
    t71.Id_Distrito                             AS idDistrito
  FROM t60DetOrdenPedido d
  JOIN t02OrdenPedido       op  ON op.Id_OrdenPedido = d.t02OrdenPedido_Id_OrdenPedido
  JOIN t18CatalogoProducto  p   ON p.Id_Producto     = d.t18CatalogoProducto_Id_Producto
  LEFT JOIN t34UnidadMedida um  ON um.Id_UnidadMedida = p.t34UnidadMedida_Id_UnidadMedida
  LEFT JOIN t71OrdenDirecEnvio t71 ON t71.Id_OrdenPedido = op.Id_OrdenPedido
  WHERE op.Id_OrdenPedido = pIdOP;
END$$
DELIMITER ;


DROP PROCEDURE IF EXISTS sp_cus24_registrar_salida;
DELIMITER $$

CREATE PROCEDURE sp_cus24_registrar_salida (
    IN p_op_ids JSON
)
BEGIN
    DECLARE v_op INT;
    DECLARE v_id_t11 INT;
    DECLARE v_error_msg VARCHAR(255);
    DECLARE done INT DEFAULT FALSE;

    DECLARE cur CURSOR FOR
        SELECT CAST(jt.id AS UNSIGNED)
        FROM JSON_TABLE(p_op_ids, '$[*]' COLUMNS (id INT PATH '$')) jt;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    START TRANSACTION;

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_op;
        IF done THEN
            LEAVE read_loop;
        END IF;

        /* --- Verificación de stock --- */
        IF EXISTS (
            SELECT 1
            FROM (
                SELECT d.t18CatalogoProducto_Id_Producto AS idProd,
                       SUM(d.Cantidad) AS qty
                FROM t60DetOrdenPedido d
                WHERE d.t02OrdenPedido_Id_OrdenPedido = v_op
                GROUP BY d.t18CatalogoProducto_Id_Producto
            ) x
            JOIN t18CatalogoProducto p ON p.Id_Producto = x.idProd
            WHERE (p.StockActual - x.qty) < 0
        ) THEN
            SET v_error_msg = CONCAT('Stock insuficiente para OP ', v_op);
            ROLLBACK;
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg;
        END IF;

        /* --- t11: Orden de Salida --- */
        INSERT INTO t11OrdenSalida (t02OrdenPedido_Id_OrdenPedido, Tipo_Movimiento)
        VALUES (v_op, 'Venta');
        SET v_id_t11 = LAST_INSERT_ID();

        /* --- t10: Kardex --- */
        INSERT INTO t10Kardex (
            Fec_Transaccion,
            id_Producto,
            Cantidad,
            Estado,
            t11OrdenSalida_Id_ordenSalida
        )
        SELECT
            NOW(),
            d.t18CatalogoProducto_Id_Producto,
            d.Cantidad,
            'Salida',
            v_id_t11
        FROM t60DetOrdenPedido d
        WHERE d.t02OrdenPedido_Id_OrdenPedido = v_op;

        /* --- Descuento de stock --- */
        UPDATE t18CatalogoProducto p
        JOIN (
            SELECT d.t18CatalogoProducto_Id_Producto AS idProd,
                   SUM(d.Cantidad) AS qty
            FROM t60DetOrdenPedido d
            WHERE d.t02OrdenPedido_Id_OrdenPedido = v_op
            GROUP BY d.t18CatalogoProducto_Id_Producto
        ) x ON x.idProd = p.Id_Producto
        SET p.StockActual = p.StockActual - x.qty;

        /* --- Estado de la OP --- */
        UPDATE t02OrdenPedido
        SET Estado = 'En Reparto'
        WHERE Id_OrdenPedido = v_op;

        /* --- Estado de la OSE (t59) --- */
        UPDATE t59OrdenServicioEntrega
        SET Estado = 'En Reparto'
        WHERE Id_OrdenPedido = v_op;

        /* 
           Si quieres asegurar transiciones válidas, puedes usar:
           WHERE Id_OrdenPedido = v_op AND Estado IN ('Pagado','Pendiente');
        */
    END LOOP;
    CLOSE cur;

    COMMIT;
END$$
DELIMITER ;


DROP PROCEDURE IF EXISTS sp_cus24_crear_guia_sin_numerador;
DELIMITER $$
CREATE PROCEDURE sp_cus24_crear_guia_sin_numerador(
  IN  p_Serie                VARCHAR(3),
  IN  p_RemitenteRUC         VARCHAR(11),
  IN  p_RemitenteRazonSocial VARCHAR(120),
  IN  p_DestinatarioNombre   VARCHAR(120),
  IN  p_DniReceptor          VARCHAR(8),
  IN  p_DireccionDestino     VARCHAR(100),
  IN  p_DistritoDestino      VARCHAR(120),
  IN  p_Id_DireccionAlmacen  INT,
  IN  p_Id_AsignacionRV      INT,
  IN  p_Marca                VARCHAR(10),
  IN  p_Placa                VARCHAR(10),
  IN  p_Conductor            VARCHAR(30),
  IN  p_Licencia             VARCHAR(20),
  IN  p_Motivo               VARCHAR(30),
  OUT p_Id_Guia              INT,
  OUT p_Numero               INT,
  OUT p_NumeroStr            VARCHAR(20)
)
BEGIN
  DECLARE v_ok_lock INT DEFAULT 0;

  START TRANSACTION;

  /* 1) Lock nominal por serie para serializar la numeración */
  SELECT GET_LOCK(CONCAT('guia_num_', p_Serie), 5) INTO v_ok_lock;
  IF v_ok_lock <> 1 THEN
    ROLLBACK;
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='No se pudo obtener lock de numeración';
  END IF;

  /* 2) Siguiente número en la serie */
  SELECT COALESCE(MAX(Numero),0) + 1
    INTO p_Numero
  FROM t72GuiaRemision
  WHERE Serie = p_Serie
  FOR UPDATE;
  SET p_NumeroStr = CONCAT(p_Serie, '-', LPAD(p_Numero, 6, '0'));
  INSERT INTO t72GuiaRemision(
    Serie, Numero,
    Fec_Emision, Estado,
    RemitenteRUC, RemitenteRazonSocial,
    DestinatarioNombre, DniReceptor,
    DireccionDestino, DistritoDestino,
    Id_DireccionAlmacen, Id_AsignacionRepartidorVehiculo,
    ModalidadTransporte, Marca, Placa, Conductor, Licencia,
    Motivo, FechaInicioTraslado
  ) VALUES (
    p_Serie, p_Numero,
    NOW(), 'Emitida',
    p_RemitenteRUC, p_RemitenteRazonSocial,
    p_DestinatarioNombre, p_DniReceptor,
    p_DireccionDestino, p_DistritoDestino,
    p_Id_DireccionAlmacen, p_Id_AsignacionRV,
    'PROPIO', p_Marca, p_Placa, p_Conductor, p_Licencia,
    IFNULL(p_Motivo,'Venta'), NOW()
  );

  SET p_Id_Guia = LAST_INSERT_ID();
  DO RELEASE_LOCK(CONCAT('guia_num_', p_Serie));
  COMMIT;
END$$
DELIMITER ;


DROP PROCEDURE IF EXISTS sp_cus24_insertar_detalle_guia_from_ops;
DELIMITER $$
CREATE PROCEDURE sp_cus24_insertar_detalle_guia_from_ops(
  IN p_Id_Guia INT,
  IN p_ops_json JSON
)
BEGIN
  /* 1) Insertar vínculos guía–OP (t93) de forma masiva e idempotente */
  INSERT IGNORE INTO t93Guia_OrdenPedido (Id_Guia, Id_OrdenPedido)
  SELECT
    p_Id_Guia,
    jt.op
  FROM JSON_TABLE(p_ops_json, '$[*]' COLUMNS(op INT PATH '$')) jt;

  /* 2) Insertar detalle agregado por producto (t74) */
  INSERT INTO t74DetalleGuia (Id_Guia, Id_Producto, Descripcion, Unidad, Cantidad)
  SELECT
    p_Id_Guia                          AS Id_Guia,
    p.Id_Producto                      AS Id_Producto,
    p.Descripcion                      AS Descripcion,
    um.Descripcion                     AS Unidad,
    SUM(d.Cantidad)                    AS Cantidad
  FROM JSON_TABLE(p_ops_json, '$[*]' COLUMNS(op INT PATH '$')) j
  JOIN t60DetOrdenPedido d
       ON d.t02OrdenPedido_Id_OrdenPedido = j.op
  JOIN t18CatalogoProducto p
       ON p.Id_Producto = d.t18CatalogoProducto_Id_Producto
  JOIN t34UnidadMedida um
       ON um.Id_UnidadMedida = p.t34UnidadMedida_Id_UnidadMedida
  GROUP BY p.Id_Producto, p.Descripcion, um.Descripcion;
END$$
DELIMITER ;

