-- ==========================================================
-- Procedimientos Preorden CUS01
-- ==========================================================

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



-- ==========================================================
-- Procedimientos Orden CUS02
-- ==========================================================
USE mundo_patitas2;

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
CREATE PROCEDURE sp_preorden_consolidar_productos(IN p_ids_json JSON)
BEGIN
  -- IF JSON_VALID(p_ids_json) = 0 THEN
    -- SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'p_ids_json no es JSON válido';
  -- END IF;

  WITH ids AS (
    SELECT CAST(j.id AS SIGNED) AS Id_PreOrdenPedido
    FROM JSON_TABLE(p_ids_json, '$[*]' COLUMNS (id INT PATH '$')) AS j
  )
  SELECT 
      d.t18CatalogoProducto_Id_Producto AS IdProducto,
      COALESCE(MAX(pr.NombreProducto), CONCAT('Prod ', d.t18CatalogoProducto_Id_Producto)) AS NombreProducto,
      COALESCE(MAX(pr.PrecioUnitario), 0) AS PrecioUnitario,
      SUM(d.Cantidad) AS Cantidad,
      SUM(d.Cantidad) * COALESCE(MAX(pr.PrecioUnitario), 0) AS Subtotal
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

CREATE OR REPLACE VIEW ordenesSSE AS
SELECT
  op.Id_OrdenPedido,
  op.Fecha,
  c.DniCli,
  op.Id_MetodoEntrega,
  op.CostoEntrega,
  op.Descuento,
  op.Total,
  op.Estado
FROM t02ordenpedido op
JOIN t20cliente c 
  ON op.Id_Cliente = c.Id_Cliente
WHERE op.Id_MetodoEntrega = 9001;

CREATE OR REPLACE VIEW ordenesCSE AS
SELECT
  op.Id_OrdenPedido,
  op.Fecha,
  c.DniCli,
  op.Id_MetodoEntrega,
  op.CostoEntrega,
  op.Descuento,
  op.Total,
  op.Estado,
  nd.Id_NotaDistribucion
FROM t02ordenpedido op
JOIN t20cliente c 
  ON op.Id_Cliente = c.Id_Cliente
LEFT JOIN t62notadistribucion nd
  ON op.Id_OrdenPedido = nd.Id_OrdenPedido
WHERE op.Id_MetodoEntrega <> 9001;

DELIMITER $$

CREATE PROCEDURE registrarMovimiento (
    IN p_Id_OrdenPedido INT,
    IN p_Estado VARCHAR(15)
)
BEGIN
    DECLARE v_Id_OrdenSalida INT;

    -- 1. Registrar la Orden de Salida
    INSERT INTO t11ordensalida (t02OrdenPedido_Id_OrdenPedido)
    VALUES (p_Id_OrdenPedido);

    SET v_Id_OrdenSalida = LAST_INSERT_ID();

    -- 2. Insertar productos en el Kardex con el estado dinámico
    INSERT INTO t10kardex (
        Fec_Transaccion,
        id_Producto,
        Cantidad,
        Estado,
        t11OrdenSalida_Id_ordenSalida
    )
    SELECT 
        CURDATE() AS Fec_Transaccion,
        d.t18CatalogoProducto_Id_Producto,
        d.Cantidad,
        p_Estado AS Estado,
        v_Id_OrdenSalida
    FROM t61detapreorden d
    INNER JOIN t01preordenpedido pre 
        ON d.t01PreOrdenPedido_Id_PreOrdenPedido = pre.Id_PreOrdenPedido
    INNER JOIN t02ordenpedido o
        ON pre.t02OrdenPedido_Id_OrdenPedido = o.Id_OrdenPedido
    WHERE o.Id_OrdenPedido = p_Id_OrdenPedido;
END$$

DELIMITER ;
