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

