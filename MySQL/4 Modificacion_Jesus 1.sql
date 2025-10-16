use mundo_patitas3;

-- ------------------------------
-- Catálogos
-- ------------------------------
CREATE TABLE IF NOT EXISTS t63TipoMetodoPago (
  idTipoMetodoPago INT AUTO_INCREMENT PRIMARY KEY,
  descripcionMetodoPago VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO t63TipoMetodoPago (descripcionMetodoPago)
VALUES ('Efectivo'), ('Tarjeta');

CREATE TABLE IF NOT EXISTS t64TipoComprobantePago (
  idTipoCP INT AUTO_INCREMENT PRIMARY KEY,
  descripcionComprobantePago VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO t64TipoComprobantePago (descripcionComprobantePago)
VALUES ('boleta'), ('factura');


ALTER TABLE t03comprobantepago
  DROP FOREIGN KEY fk_cmp_banco,
  DROP FOREIGN KEY fk_cmp_metodo;

-- Tablas legadas (si existían)
DROP TABLE IF EXISTS t28_metodopago;
DROP TABLE IF EXISTS t30tipobanco;

-- Elimina índices legados
ALTER TABLE t03comprobantepago
  DROP INDEX fk_cmp_banco,
  DROP INDEX fk_cmp_metodo;

-- Elimina columnas legadas
ALTER TABLE t03comprobantepago
  DROP COLUMN Id_MetodoPago,
  DROP COLUMN Id_TipoBanco,
  DROP COLUMN tipo;

-- Renombra PK correlativa
ALTER TABLE t03comprobantepago
  CHANGE COLUMN Nro_ComproPago idCP INT NOT NULL AUTO_INCREMENT;

-- Estructura final
ALTER TABLE t03comprobantepago
  ADD COLUMN numeroCP VARCHAR(50) UNIQUE AFTER idCP,
  ADD COLUMN tipoComprobante INT NOT NULL AFTER numeroCP,
  ADD COLUMN fechaEmision DATE NOT NULL AFTER tipoComprobante,
  ADD COLUMN idCliente INT NOT NULL AFTER fechaEmision,
  ADD COLUMN idTrabajador INT NOT NULL AFTER idCliente,
  ADD COLUMN montoTotal DECIMAL(12,2) NULL AFTER idTrabajador,
  ADD COLUMN estado VARCHAR(20) NOT NULL DEFAULT 'Generado' AFTER montoTotal,
  ADD COLUMN idMetodoPago INT NOT NULL AFTER estado,
  ADD CONSTRAINT fk_tipo_comprobante
      FOREIGN KEY (tipoComprobante) REFERENCES t64TipoComprobantePago(idTipoCP),
  ADD CONSTRAINT fk_cliente
      FOREIGN KEY (idCliente) REFERENCES t20cliente (Id_Cliente),
  ADD CONSTRAINT fk_trabajador
      FOREIGN KEY (idTrabajador) REFERENCES t16catalogotrabajadores (id_Trabajador),
  ADD CONSTRAINT fk_metodo_pago
      FOREIGN KEY (idMetodoPago) REFERENCES t63TipoMetodoPago (idTipoMetodoPago);

-- Vincular (opcional) Nota de Crédito a la cabecera
ALTER TABLE t03comprobantepago
  ADD COLUMN idNotaCredito INT NULL UNIQUE AFTER idMetodoPago,
  ADD CONSTRAINT fk_t03_t21_idNotaCredito
      FOREIGN KEY (idNotaCredito) REFERENCES t21notacredito (Id_NotaCredito)
      ON UPDATE CASCADE
      ON DELETE SET NULL;

-- ------------------------------
-- Detalle del Comprobante
-- ------------------------------
CREATE TABLE IF NOT EXISTS t65DetComprobantePago (
  idCP INT NOT NULL,
  idOrdenPedido INT NOT NULL,
  totalOrdenPedido DECIMAL(12,2) NOT NULL,
  igv DECIMAL(12,2) NOT NULL,
  totalNeto DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (idCP, idOrdenPedido),
  CONSTRAINT fk_t65_t03 FOREIGN KEY (idCP) REFERENCES t03comprobantepago(idCP),
  CONSTRAINT fk_t65_t02 FOREIGN KEY (idOrdenPedido) REFERENCES t02ordenpedido(Id_OrdenPedido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------
-- Stored Procedure: createComprobantePago
-- ------------------------------
DROP PROCEDURE IF EXISTS createComprobantePago;
DELIMITER $$
CREATE PROCEDURE createComprobantePago (
  IN p_Id_OrdenPedido_Json JSON,  -- Ej: [10001,10002]
  IN p_tipoComprobante    INT,    -- 1=boleta, 2=factura
  IN p_idTrabajador       INT,
  IN p_idMetodoPago       INT,    -- 1=efectivo, 2=tarjeta
  IN p_idNotaCredito      INT     -- puede ser NULL
)
BEGIN
  DECLARE v_numeroCP         VARCHAR(32);
  DECLARE v_idCP             INT;
  DECLARE v_idClientePrimero INT;
  DECLARE v_clientesDist     INT;
  DECLARE v_jsonLen          INT;
  DECLARE v_rowsFound        INT;
  DECLARE v_sumaNeto         DECIMAL(12,2);
  DECLARE v_ncMonto          DECIMAL(12,2);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  -- 1) Validar JSON
  SET v_jsonLen = JSON_LENGTH(p_Id_OrdenPedido_Json);
  IF v_jsonLen IS NULL OR v_jsonLen = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La lista JSON de órdenes está vacía.';
  END IF;

  -- 2) Verificar órdenes y cliente único
  SELECT 
    COUNT(*)                               AS rows_join,
    COUNT(DISTINCT o.Id_Cliente)           AS clientes_distintos,
    MIN(o.Id_Cliente)                      AS cliente_unico
  INTO
    v_rowsFound, v_clientesDist, v_idClientePrimero
  FROM JSON_TABLE(p_Id_OrdenPedido_Json, '$[*]' COLUMNS (idOrdenPedido INT PATH '$')) jt
  JOIN t02ordenpedido o ON o.Id_OrdenPedido = jt.idOrdenPedido;

  IF v_rowsFound <> v_jsonLen THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Alguna orden no existe.';
  END IF;

  IF v_clientesDist > 1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Todas las órdenes deben pertenecer al mismo cliente.';
  END IF;

  -- 3) Cabecera
  SET v_numeroCP = CONCAT(
    CASE p_tipoComprobante WHEN 1 THEN 'B' WHEN 2 THEN 'F' ELSE 'X' END,
    YEAR(CURDATE()),
    LPAD(FLOOR(RAND()*10000000), 7, '0')
  );

  INSERT INTO t03comprobantepago (
    numeroCP, tipoComprobante, fechaEmision,
    idCliente, idTrabajador, montoTotal,
    estado, idMetodoPago, idNotaCredito
  ) VALUES (
    v_numeroCP, p_tipoComprobante, CURDATE(),
    v_idClientePrimero, p_idTrabajador, NULL,
    'Generado', p_idMetodoPago, p_idNotaCredito
  );

  SET v_idCP = LAST_INSERT_ID();

  -- 4) Detalle por orden
  INSERT INTO t65DetComprobantePago (idCP, idOrdenPedido, totalOrdenPedido, igv, totalNeto)
  SELECT
    v_idCP,
    o.Id_OrdenPedido,
    ROUND(o.Total / 1.18, 2)                                   AS totalBase,
    ROUND(o.Total - ROUND(o.Total / 1.18, 2), 2)               AS igv,
    o.Total                                                     AS totalNeto
  FROM JSON_TABLE(p_Id_OrdenPedido_Json, '$[*]' COLUMNS (idOrdenPedido INT PATH '$')) jt
  JOIN t02ordenpedido o ON o.Id_OrdenPedido = jt.idOrdenPedido;

  -- 5) Total neto
  SELECT SUM(o.Total) INTO v_sumaNeto
  FROM JSON_TABLE(p_Id_OrdenPedido_Json, '$[*]' COLUMNS (idOrdenPedido INT PATH '$')) jt
  JOIN t02ordenpedido o ON o.Id_OrdenPedido = jt.idOrdenPedido;

  -- 6) Nota de crédito (0 si no aplica)
  SET v_ncMonto = COALESCE(
    (SELECT n.Total FROM t21notacredito n WHERE n.Id_NotaCredito = p_idNotaCredito),
    0
  );

  -- 7) Actualizar cabecera y marcar órdenes como pagadas
  UPDATE t03comprobantepago
    SET montoTotal = v_sumaNeto - v_ncMonto
  WHERE idCP = v_idCP;

  UPDATE t02ordenpedido o
  JOIN JSON_TABLE(p_Id_OrdenPedido_Json, '$[*]' COLUMNS (idOrdenPedido INT PATH '$')) jt
    ON o.Id_OrdenPedido = jt.idOrdenPedido
  SET o.Estado = 'Pagado';

  COMMIT;
END $$
DELIMITER ;