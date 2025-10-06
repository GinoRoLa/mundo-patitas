-- ==========================================================
-- Procedimientos Preorden CUS01
-- ==========================================================
Use mundo_patitas3;
DROP PROCEDURE IF EXISTS sp_registrarPreorden;
DELIMITER $$
CREATE PROCEDURE sp_registrarPreorden(
    IN p_idCliente INT,
    IN p_productosJson JSON
)
BEGIN
    DECLARE v_preordenId INT;
    DECLARE v_productoId INT;
    DECLARE v_cantidad INT;
    DECLARE v_precio DECIMAL(12,2);
    DECLARE v_index INT DEFAULT 0;
    DECLARE v_totalProductos INT;
    DECLARE v_totalPreorden DECIMAL(12,2) DEFAULT 0.00;

    -- Insertar la preorden con total 0 temporalmente
    INSERT INTO t01preordenpedido (t20Cliente_Id_Cliente, Estado, Total)
    VALUES (p_idCliente, 'Emitido', 0.00);

    SET v_preordenId = LAST_INSERT_ID();

    -- Contar cuántos productos vienen en el JSON
    SET v_totalProductos = JSON_LENGTH(p_productosJson);

    WHILE v_index < v_totalProductos DO
        SET v_productoId = JSON_UNQUOTE(JSON_EXTRACT(p_productosJson, CONCAT('$[', v_index, '].codigo')));
        SET v_cantidad  = JSON_UNQUOTE(JSON_EXTRACT(p_productosJson, CONCAT('$[', v_index, '].cantidad')));
        SET v_precio    = JSON_UNQUOTE(JSON_EXTRACT(p_productosJson, CONCAT('$[', v_index, '].precio')));

        -- Insertar detalle
        INSERT INTO t61detapreorden (t18CatalogoProducto_Id_Producto, t01PreOrdenPedido_Id_PreOrdenPedido, Cantidad)
        VALUES (v_productoId, v_preordenId, v_cantidad);

        -- Actualizar stock del producto
        UPDATE t18CatalogoProducto
        SET StockActual = StockActual - v_cantidad
        WHERE Id_Producto = v_productoId;

        -- Acumular el total de la preorden usando el precio del JSON
        SET v_totalPreorden = v_totalPreorden + (v_precio * v_cantidad);

        SET v_index = v_index + 1;
    END WHILE;

    -- Actualizar el total de la preorden
    UPDATE t01preordenpedido
    SET Total = v_totalPreorden
    WHERE Id_PreOrdenPedido = v_preordenId;
    SELECT v_preordenId AS idPreorden;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_registrarPreorden;
DELIMITER $$
CREATE PROCEDURE sp_registrarPreorden(
    IN p_idCliente INT,
    IN p_productosJson JSON
)
BEGIN
    DECLARE v_preordenId INT;
    DECLARE v_productoId INT;
    DECLARE v_cantidad INT;
    DECLARE v_precio DECIMAL(12,2);
    DECLARE v_index INT DEFAULT 0;
    DECLARE v_totalProductos INT;
    DECLARE v_totalPreorden DECIMAL(12,2) DEFAULT 0.00;

    -- Insertar la preorden con total 0 temporalmente
    INSERT INTO t01preordenpedido (t20Cliente_Id_Cliente, Estado, Total)
    VALUES (p_idCliente, 'Emitido', 0.00);

    SET v_preordenId = LAST_INSERT_ID();

    -- Contar cuántos productos vienen en el JSON
    SET v_totalProductos = JSON_LENGTH(p_productosJson);

    WHILE v_index < v_totalProductos DO
        SET v_productoId = JSON_UNQUOTE(JSON_EXTRACT(p_productosJson, CONCAT('$[', v_index, '].codigo')));
        SET v_cantidad  = JSON_UNQUOTE(JSON_EXTRACT(p_productosJson, CONCAT('$[', v_index, '].cantidad')));
        SET v_precio    = JSON_UNQUOTE(JSON_EXTRACT(p_productosJson, CONCAT('$[', v_index, '].precio')));

        -- Insertar detalle
        INSERT INTO t61detapreorden (t18CatalogoProducto_Id_Producto, t01PreOrdenPedido_Id_PreOrdenPedido, Cantidad)
        VALUES (v_productoId, v_preordenId, v_cantidad);

        -- Actualizar stock del producto
        UPDATE t18CatalogoProducto
        SET StockActual = StockActual - v_cantidad
        WHERE Id_Producto = v_productoId;

        -- Acumular el total de la preorden usando el precio del JSON
        SET v_totalPreorden = v_totalPreorden + (v_precio * v_cantidad);

        SET v_index = v_index + 1;
    END WHILE;

    -- Actualizar el total de la preorden
    UPDATE t01preordenpedido
    SET Total = v_totalPreorden
    WHERE Id_PreOrdenPedido = v_preordenId;

END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS registrarMovimiento;
DELIMITER $$

CREATE PROCEDURE registrarMovimiento (
    IN p_ListaOrdenes JSON,
    IN p_Estado VARCHAR(15)
)
BEGIN
    DECLARE v_Id_OrdenPedido INT;
    DECLARE v_Id_OrdenSalida INT;
    DECLARE done INT DEFAULT FALSE;

    DECLARE cur CURSOR FOR 
        SELECT CAST(jt.id AS UNSIGNED)
        FROM JSON_TABLE(p_ListaOrdenes, "$[*]" COLUMNS (id INT PATH "$")) jt;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_Id_OrdenPedido;
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- 1. Registrar la Orden de Salida
        INSERT INTO t11ordensalida (t02OrdenPedido_Id_OrdenPedido, Tipo_Movimiento)
        VALUES (v_Id_OrdenPedido, 'Venta');

        SET v_Id_OrdenSalida = LAST_INSERT_ID();

        -- 2. Insertar productos en el Kardex
        INSERT INTO t10kardex (
            Fec_Transaccion,
            id_Producto,
            Cantidad,
            Estado,
            t11OrdenSalida_Id_ordenSalida
        )
        SELECT 
            CURDATE(),
            d.t18CatalogoProducto_Id_Producto,
            d.Cantidad,
            p_Estado,
            v_Id_OrdenSalida
        FROM t61detapreorden d
        INNER JOIN t01preordenpedido pre 
            ON d.t01PreOrdenPedido_Id_PreOrdenPedido = pre.Id_PreOrdenPedido
        INNER JOIN t02ordenpedido o
            ON pre.t02OrdenPedido_Id_OrdenPedido = o.Id_OrdenPedido
        WHERE o.Id_OrdenPedido = v_Id_OrdenPedido;
    
    UPDATE t02ordenpedido
        SET Estado = 'Entregado'
        WHERE Id_OrdenPedido = v_Id_OrdenPedido;
    END LOOP;
    CLOSE cur;
END$$

DELIMITER ;


-- ==========================================================
-- Procedimientos Orden CUS02
-- ==========================================================

USE mundo_patitas3;

-- Limpieza defensiva
DROP PROCEDURE IF EXISTS sp_preorden_vigentes_por_dni;
DROP PROCEDURE IF EXISTS sp_preorden_filtrar_vigentes_del_cliente;
DROP PROCEDURE IF EXISTS sp_preorden_consolidar_productos;

DELIMITER $$
-- 1) Listar preórdenes vigentes (<24h) por DNI, con total calculado
CREATE PROCEDURE sp_preorden_vigentes_por_dni(IN p_dni VARCHAR(8))
BEGIN
  SELECT
      p.Id_PreOrdenPedido,
      c.Id_Cliente,
      c.DniCli,
      TRIM(p.Estado) AS Estado,
      DATE_FORMAT(p.Fec_Emision, '%Y-%m-%d %H:%i:%s') AS Fec_Emision,
      COALESCE(SUM(d.Cantidad * COALESCE(pr.PrecioUnitario,0)), 0) AS Total
  FROM t01preordenpedido p
  JOIN t20cliente c
    ON c.Id_Cliente = p.t20Cliente_Id_Cliente
  LEFT JOIN t61detapreorden d
    ON d.t01PreOrdenPedido_Id_PreOrdenPedido = p.Id_PreOrdenPedido
  LEFT JOIN t18catalogoproducto pr
    ON pr.Id_Producto = d.t18CatalogoProducto_Id_Producto
  WHERE c.DniCli = p_dni
    AND TRIM(p.Estado) = 'Emitido'
    AND p.Fec_Emision >= (NOW() - INTERVAL 24 HOUR)
  GROUP BY p.Id_PreOrdenPedido, c.Id_Cliente, c.DniCli, p.Estado, p.Fec_Emision
  ORDER BY p.Fec_Emision DESC;
END$$

-- 2) De un set de IDs (JSON array), devuelve solo las preórdenes vigentes del mismo cliente (por DNI)
CREATE PROCEDURE sp_preorden_filtrar_vigentes_del_cliente(IN p_dni VARCHAR(8), IN p_ids_json JSON)
BEGIN
  -- Validación mínima de JSON
  -- IF JSON_VALID(p_ids_json) = 0 THEN
    -- SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'p_ids_json no es JSON válido';
  -- END IF;

  WITH ids AS (
    SELECT CAST(j.id AS SIGNED) AS Id_PreOrdenPedido
    FROM JSON_TABLE(p_ids_json, '$[*]' COLUMNS (id INT PATH '$')) AS j
  )
  SELECT p.Id_PreOrdenPedido
  FROM t01preordenpedido p
  JOIN t20cliente c
    ON c.Id_Cliente = p.t20Cliente_Id_Cliente
  JOIN ids
    ON ids.Id_PreOrdenPedido = p.Id_PreOrdenPedido
  WHERE c.DniCli = p_dni
    AND TRIM(p.Estado) = 'Emitido'
    AND p.Fec_Emision >= (NOW() - INTERVAL 24 HOUR);
END$$

-- 3) Consolidación de productos (sumando cantidades) a partir de un JSON array de IDs de preorden
DROP PROCEDURE IF EXISTS sp_preorden_consolidar_productos;
DELIMITER $$
CREATE PROCEDURE sp_preorden_consolidar_productos(IN p_ids_json JSON)
BEGIN
  WITH ids AS (
    SELECT CAST(j.id AS SIGNED) AS Id_PreOrdenPedido
    FROM JSON_TABLE(p_ids_json, '$[*]' COLUMNS (id INT PATH '$')) AS j
  )
  SELECT
      d.t18CatalogoProducto_Id_Producto AS IdProducto,
      COALESCE(MAX(pr.NombreProducto), CONCAT('Prod ', d.t18CatalogoProducto_Id_Producto)) AS NombreProducto,
      COALESCE(MAX(pr.PrecioUnitario), 0) AS PrecioUnitario,
      COALESCE(MAX(pr.Peso), 0)          AS PesoUnitario,
      COALESCE(MAX(pr.Volumen), 0)       AS VolumenUnitario,
      SUM(d.Cantidad)                    AS Cantidad,
      SUM(d.Cantidad) * COALESCE(MAX(pr.PrecioUnitario), 0) AS Subtotal,
      SUM(d.Cantidad) * COALESCE(MAX(pr.Peso), 0)           AS PesoTotal,
      SUM(d.Cantidad) * COALESCE(MAX(pr.Volumen), 0)        AS VolumenTotal
  FROM t61detapreorden d
  JOIN ids
    ON d.t01PreOrdenPedido_Id_PreOrdenPedido = ids.Id_PreOrdenPedido
  LEFT JOIN t18catalogoproducto pr
    ON pr.Id_Producto = d.t18CatalogoProducto_Id_Producto
  GROUP BY d.t18CatalogoProducto_Id_Producto
  HAVING SUM(d.Cantidad) > 0
  ORDER BY NombreProducto;
END$$
DELIMITER ;


DELIMITER $$
CREATE PROCEDURE sp_orden_crear_con_detalle(
  IN p_id_cliente   INT,
  IN p_metodo_id    INT,
  IN p_costo        DECIMAL(10,2),
  IN p_descuento    DECIMAL(10,2),
  IN p_total        DECIMAL(10,2),
  IN p_items        JSON      -- [{ "IdProducto": 1, "Cantidad": 2 }, ...]
)
BEGIN
  DECLARE v_orden_id INT;
  DECLARE v_items_cnt INT;

  -- Validaciones básicas
  SET v_items_cnt = JSON_LENGTH(p_items);
  IF v_items_cnt IS NULL OR v_items_cnt = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Items vacíos o inválidos';
  END IF;

  START TRANSACTION;

  INSERT INTO t02OrdenPedido
    (Fecha, Id_Cliente, Id_MetodoEntrega, CostoEntrega, Descuento, Total, Estado)
  VALUES
    (NOW(), p_id_cliente, p_metodo_id, p_costo, p_descuento, p_total, 'Generada');

  SET v_orden_id = LAST_INSERT_ID();

  -- Inserta el detalle en bloque desde el JSON
  INSERT INTO t60DetOrdenPedido
    (t18CatalogoProducto_Id_Producto, t02OrdenPedido_Id_OrdenPedido, Id_Cliente, Cantidad)
  SELECT
    jt.IdProducto, v_orden_id, p_id_cliente, jt.Cantidad
  FROM JSON_TABLE(p_items, '$[*]' COLUMNS (
    IdProducto INT PATH '$.IdProducto',
    Cantidad   INT PATH '$.Cantidad'
  )) AS jt
  WHERE jt.IdProducto > 0 AND jt.Cantidad > 0;

  IF ROW_COUNT() = 0 THEN
    ROLLBACK;
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se insertó detalle';
  END IF;

  COMMIT;

  SELECT v_orden_id AS ordenId;
END$$
DELIMITER ;

USE mundo_patitas3;

DROP PROCEDURE IF EXISTS sp_orden_crear_con_detalle;
DELIMITER $$
CREATE PROCEDURE sp_orden_crear_con_detalle(
  IN p_id_cliente   INT,
  IN p_metodo_id    INT,
  IN p_costo        DECIMAL(10,2),
  IN p_descuento    DECIMAL(10,2),
  IN p_total        DECIMAL(10,2),
  IN p_items        JSON      -- [{ "IdProducto": 1, "Cantidad": 2 }, ...]
)
BEGIN
  DECLARE v_orden_id    INT;
  DECLARE v_items_cnt   INT;
  DECLARE v_peso_total  DECIMAL(12,2) DEFAULT 0.00;
  DECLARE v_vol_total   DECIMAL(12,2) DEFAULT 0.00;

  SET v_items_cnt = JSON_LENGTH(p_items);
  IF v_items_cnt IS NULL OR v_items_cnt = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Items vacíos o inválidos';
  END IF;

  -- Calcula peso_total y volumen_total en servidor (fuente de verdad)
  SELECT
    COALESCE(SUM(j.Cantidad * COALESCE(p.Peso, 0)), 0),
    COALESCE(SUM(j.Cantidad * COALESCE(p.Volumen, 0)), 0)
  INTO v_peso_total, v_vol_total
  FROM JSON_TABLE(p_items, '$[*]' COLUMNS (
         IdProducto INT PATH '$.IdProducto',
         Cantidad   INT PATH '$.Cantidad'
       )) AS j
  JOIN t18catalogoproducto p
    ON p.Id_Producto = j.IdProducto;

  START TRANSACTION;

  INSERT INTO t02OrdenPedido
    (Fecha, Id_Cliente, Id_MetodoEntrega, CostoEntrega, Descuento, Total,
     peso_total, Volumen_total, Estado)
  VALUES
    (NOW(), p_id_cliente, p_metodo_id, p_costo, p_descuento, p_total,
     v_peso_total, v_vol_total, 'Generada');

  SET v_orden_id = LAST_INSERT_ID();

  -- Inserta el detalle desde el JSON (ajusta a tu tabla real de detalle)
  INSERT INTO t60DetOrdenPedido
    (t18CatalogoProducto_Id_Producto, t02OrdenPedido_Id_OrdenPedido, Id_Cliente, Cantidad)
  SELECT
    jt.IdProducto, v_orden_id, p_id_cliente, jt.Cantidad
  FROM JSON_TABLE(p_items, '$[*]' COLUMNS (
    IdProducto INT PATH '$.IdProducto',
    Cantidad   INT PATH '$.Cantidad'
  )) AS jt
  WHERE jt.IdProducto > 0 AND jt.Cantidad > 0;

  IF ROW_COUNT() = 0 THEN
    ROLLBACK;
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se insertó detalle';
  END IF;

  COMMIT;

  SELECT v_orden_id AS ordenId, v_peso_total AS peso_total, v_vol_total AS volumen_total;
END$$
DELIMITER ;


DROP PROCEDURE IF EXISTS sp_vincular_preordenes_a_orden;
DELIMITER $$
CREATE PROCEDURE sp_vincular_preordenes_a_orden(
  IN p_orden_id INT,
  IN p_ids_json JSON
)
BEGIN
  DECLARE v_ins INT DEFAULT 0;
  DECLARE v_upd INT DEFAULT 0;
  DECLARE v_cnt INT;

  SET v_cnt = JSON_LENGTH(p_ids_json);
  IF v_cnt IS NULL OR v_cnt = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Lista de preórdenes vacía o inválida';
  END IF;

  START TRANSACTION;

  INSERT IGNORE INTO t90PreOrden_OrdenPedido (Id_OrdenPedido, Id_PreOrdenPedido, Fec_Vinculo)
  SELECT p_orden_id, jt.id, NOW()
  FROM JSON_TABLE(p_ids_json, '$[*]' COLUMNS (id INT PATH '$')) jt;
  SET v_ins = ROW_COUNT();

  UPDATE t01PreOrdenPedido p
  JOIN (
    SELECT CAST(j.id AS SIGNED) AS Id_PreOrdenPedido
    FROM JSON_TABLE(p_ids_json, '$[*]' COLUMNS (id INT PATH '$')) j
  ) i ON i.Id_PreOrdenPedido = p.Id_PreOrdenPedido
  SET p.Estado = 'Procesado'
  WHERE TRIM(p.Estado) = 'Emitido';
  SET v_upd = ROW_COUNT();

  COMMIT;

  SELECT v_ins AS preordenes_vinculadas, v_upd AS preordenes_marcadas;
END$$
DELIMITER ;

-- ==========================================================
-- Procedimientos Orden CUS24
-- ==========================================================
DROP PROCEDURE IF EXISTS sp_cus24_get_asignacion_encabezado;
DELIMITER $$
CREATE PROCEDURE sp_cus24_get_asignacion_encabezado(IN pIdAsignacion INT)
SQL SECURITY INVOKER
BEGIN
  SELECT 
    t40.Id_OrdenAsignacion           AS id,
    t40.FechaProgramada              AS fechaProgramada,
    t40.FecCreacion                  AS fecCreacion,
    t40.Estado                       AS estado,

    t79.Id_Trabajador                AS idTrabajador,
    t16.DNITrabajador                AS dni,
    t16.des_nombreTrabajador         AS nombre,
    t16.des_apepatTrabajador         AS apePat,
    t16.des_apematTrabajador         AS apeMat,
    t16.num_telefono                 AS telefono,
    t16.email                        AS email,
    t16.cargo                        AS cargo,

    t79.Id_Vehiculo                  AS idVehiculo,
    t78.Marca                        AS vehMarca,
    t78.Placa                        AS vehPlaca,
    t78.Modelo                       AS vehModelo
  FROM t40OrdenAsignacionReparto t40
  JOIN t79AsignacionRepartidorVehiculo t79
       ON t79.Id_AsignacionRepartidorVehiculo = t40.Id_AsignacionRepartidorVehiculo
  JOIN t16CatalogoTrabajadores t16
       ON t16.id_Trabajador = t79.Id_Trabajador
  JOIN t78Vehiculo t78
       ON t78.Id_Vehiculo = t79.Id_Vehiculo
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
  ORDER BY d.DescNombre, t71.DireccionSnap, t02.Id_OrdenPedido;
END$$
DELIMITER ;


DROP PROCEDURE IF EXISTS sp_cus24_get_items_op;
DELIMITER $$
CREATE PROCEDURE sp_cus24_get_items_op(IN pIdOrden INT)
SQL SECURITY INVOKER
BEGIN
  SELECT 
    d.Id_DetOrdenPedido   AS idDet,
    d.t18CatalogoProducto_Id_Producto AS idProducto,
    p.NombreProducto      AS descripcion,
    d.Cantidad            AS cantidad
  FROM t60DetOrdenPedido d
  JOIN t18CatalogoProducto p ON p.Id_Producto = d.t18CatalogoProducto_Id_Producto
  WHERE d.t02OrdenPedido_Id_OrdenPedido = pIdOrden
  ORDER BY d.Id_DetOrdenPedido;
END$$
DELIMITER ;

