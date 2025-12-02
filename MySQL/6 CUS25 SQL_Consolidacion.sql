use mundo_patitas3;

CREATE TABLE t170Consolidacion_Fotos (
  ID_Fotos INT NOT NULL AUTO_INCREMENT,
  Foto_Direccion LONGBLOB,
  Foto_DNI LONGBLOB,
  Foto_Entrega LONGBLOB,
  PRIMARY KEY (ID_Fotos)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE t171Consolidacion_Entrega (
  ID_Consolidacion INT NOT NULL AUTO_INCREMENT,
  Id_OrdenPedido INT NOT NULL,
  Fecha DATE,
  Hora TIME,
  ID_Fotos INT NOT NULL,
  Estado VARCHAR(50),
  Observaciones VARCHAR(255),
  PRIMARY KEY (ID_Consolidacion),
  FOREIGN KEY (Id_OrdenPedido) REFERENCES t02OrdenPedido(Id_OrdenPedido),
  FOREIGN KEY (ID_Fotos) REFERENCES t170Consolidacion_Fotos(ID_Fotos)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE t172GestionNoEntregados (
  Id_Gestion INT NOT NULL AUTO_INCREMENT,
  Id_Consolidacion INT NOT NULL,
  Id_OrdenPedido INT NOT NULL,
  Id_Cliente INT NOT NULL,
  Decision VARCHAR(50) NOT NULL,          -- “Devolución” o “Reprogramación”
  Observaciones VARCHAR(255),
  FechaGestion DATETIME DEFAULT CURRENT_TIMESTAMP,
  Estado VARCHAR(20) DEFAULT 'Registrado', -- “Registrado”, “Procesado”
  PRIMARY KEY (Id_Gestion),
  FOREIGN KEY (Id_Consolidacion) REFERENCES t171Consolidacion_Entrega(ID_Consolidacion),
  FOREIGN KEY (Id_OrdenPedido) REFERENCES t02OrdenPedido(Id_OrdenPedido),
  FOREIGN KEY (Id_Cliente) REFERENCES t20Cliente(Id_Cliente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE t172GestionNoEntregados
ADD COLUMN EsCasoEspecial BOOLEAN DEFAULT FALSE,
ADD COLUMN Fecha_Reprogramacion DATE NULL,
ADD COLUMN Motivo VARCHAR(100) NULL;


DELIMITER //

CREATE PROCEDURE sp_cus25_RegistrarConsolidacion(
  IN p_Id_OrdenPedido INT,
  IN p_Foto_Direccion LONGBLOB,
  IN p_Foto_DNI LONGBLOB,
  IN p_Foto_Entrega LONGBLOB,
  IN p_Estado VARCHAR(50),
  IN p_Observaciones VARCHAR(255)
)
BEGIN
  -- 1️⃣ Insertar las fotos
  INSERT INTO t170Consolidacion_Fotos (Foto_Direccion, Foto_DNI, Foto_Entrega)
  VALUES (p_Foto_Direccion, p_Foto_DNI, p_Foto_Entrega);

  -- 2️⃣ Guardar el ID autogenerado de la foto
  SET @nuevo_ID_Fotos = LAST_INSERT_ID();

  -- 3️⃣ Insertar la consolidación con fecha y hora actuales
  INSERT INTO t171Consolidacion_Entrega (Id_OrdenPedido, Fecha, Hora, ID_Fotos, Estado, Observaciones)
  VALUES (p_Id_OrdenPedido, CURDATE(), CURTIME(), @nuevo_ID_Fotos, p_Estado, p_Observaciones);

  -- 4️⃣ Actualizar el estado del pedido
  UPDATE t02OrdenPedido
  SET Estado = p_Estado
  WHERE Id_OrdenPedido = p_Id_OrdenPedido;
END //

DELIMITER ;

DELIMITER //

CREATE PROCEDURE RegistrarGestionNoEntregados(
  IN p_Id_Consolidacion INT,
  IN p_Id_OrdenPedido INT,
  IN p_Id_Cliente INT,
  IN p_Decision VARCHAR(50),
  IN p_Observaciones VARCHAR(255)
)
BEGIN
  DECLARE v_Total DECIMAL(12,2);
  DECLARE v_IdOrdenDevolucion INT;

  -- Obtener total del pedido
  SELECT Total INTO v_Total
  FROM t02OrdenPedido
  WHERE Id_OrdenPedido = p_Id_OrdenPedido;

  -- Registrar gestión
  INSERT INTO t172GestionNoEntregados (
    Id_Consolidacion, Id_OrdenPedido, Id_Cliente, Decision, Observaciones, Estado
  ) VALUES (
    p_Id_Consolidacion, p_Id_OrdenPedido, p_Id_Cliente, p_Decision, p_Observaciones, 'Procesado'
  );

  -- Actualizar estado en Consolidación
  UPDATE Consolidacion_Entrega
  SET Estado = p_Decision
  WHERE ID_Consolidacion = p_Id_Consolidacion;

  -- === DECISIÓN: DEVOLUCIÓN ===
  IF p_Decision = 'Devolución' THEN
    -- Generar Nota de Crédito
    INSERT INTO t21NotaCredito (
      Fec_emision, codigoNotaCredito, fecha_caducidad, Id_OrdenPedido,
      id_Trabajador, Nro_comprobantePago, Total, Estado, t20Cliente_Id_Cliente
    )
    VALUES (
      CURDATE(),
      FLOOR(RAND() * 90000 + 10000),
      DATE_ADD(CURDATE(), INTERVAL 6 MONTH),
      p_Id_OrdenPedido,
      1,
      10001,
      v_Total,
      'Emitido',
      p_Id_Cliente
    );

    -- Generar Orden de Devolución
    INSERT INTO t50OrdenDevolucion (FechaDevolucion, Id_OrdenPedido, Estado)
    VALUES (NOW(), p_Id_OrdenPedido, 'Pendiente');

    SET v_IdOrdenDevolucion = LAST_INSERT_ID();

    -- Generar Orden de Ingreso a Almacén
    INSERT INTO t09OrdenIngresoAlmacen (FechaIngreso, Id_OrdenDevolucion, Estado)
    VALUES (NOW(), v_IdOrdenDevolucion, 'Pendiente');

  END IF;

  -- === DECISIÓN: REPROGRAMACIÓN ===
  IF p_Decision = 'Reprogramación' THEN
    UPDATE t02OrdenPedido
    SET Estado = 'Reprogramado'
    WHERE Id_OrdenPedido = p_Id_OrdenPedido;
  END IF;
END //

DELIMITER ;