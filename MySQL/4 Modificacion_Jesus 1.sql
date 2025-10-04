use mundo_patitas2;

select * from t20cliente;

create table t63TipoMetodoPago(
    idTipoMetodoPago INT AUTO_INCREMENT PRIMARY KEY,
    descripcionMetodoPago VARCHAR(20) UNIQUE NOT NULL
);

select * from  t63TipoMetodoPago; 
insert into t63TipoMetodoPago (descripcionMetodoPago) values 
('Efectivo'),
('Tarjeta')
;

create table t64TipoComprobantePago(
    idTipoCP INT AUTO_INCREMENT PRIMARY KEY,
    descripcionComprobantePago VARCHAR(20) UNIQUE NOT NULL
);

insert into t64TipoComprobantePago(descripcionComprobantePago) values 
('boleta'),
('factura')
;


/*describe t30tipobanco */
/*describe t28_metodopago */
/*describe t03comprobantepago; */


SHOW CREATE TABLE t03comprobantepago;

ALTER TABLE t03comprobantepago
  DROP FOREIGN KEY fk_cmp_banco,
  DROP FOREIGN KEY fk_cmp_metodo;

drop table t28_metodopago;
drop table t30tipobanco;

SHOW INDEX FROM t03comprobantepago;
ALTER TABLE t03comprobantepago
  DROP INDEX fk_cmp_banco,
  DROP INDEX fk_cmp_metodo;


ALTER TABLE t03comprobantepago
DROP COLUMN Id_MetodoPago,
DROP COLUMN Id_TipoBanco,
DROP COLUMN tipo;

ALTER TABLE t03comprobantepago
CHANGE COLUMN Nro_ComproPago idCP INT not null auto_increment;

ALTER TABLE t03comprobantepago
ADD COLUMN numeroCP VARCHAR(50) UNIQUE AFTER idCP,
ADD COLUMN tipoComprobante INT AFTER numeroCP,
ADD COLUMN fechaEmision DATE AFTER tipoComprobante,
ADD COLUMN idCliente INT AFTER fechaEmision,
ADD COLUMN idTrabajador INT AFTER idCliente,
ADD COLUMN montoTotal DECIMAL(12,2) AFTER idTrabajador,
ADD COLUMN estado VARCHAR(20) AFTER montoTotal,
ADD COLUMN idMetodoPago INT AFTER estado,

ADD CONSTRAINT fk_tipo_comprobante FOREIGN KEY (tipoComprobante) REFERENCES t64TipoComprobantePago(idTipoCP),
ADD CONSTRAINT fk_cliente FOREIGN KEY (idCliente) REFERENCES t20cliente(Id_Cliente),
ADD CONSTRAINT fk_trabajador FOREIGN KEY (idTrabajador) REFERENCES t16catalogotrabajadores(id_Trabajador),
ADD CONSTRAINT fk_metodo_pago FOREIGN KEY (idMetodoPago) REFERENCES t63TipoMetodoPago(idTipoMetodoPago);
;

describe t03comprobantepago;

CREATE TABLE t65DetComprobantePago (
    idCP INT NOT NULL,         
    idOrdenPedido INT NOT NULL,       
    idNotaCredito INT,               
    totalOrdenPedido DECIMAL(12,2) NOT NULL,
    igv DECIMAL(12,2) NOT NULL,
    totalNeto DECIMAL(12,2) NOT NULL,

    -- Clave compuesta
    PRIMARY KEY (idCP, idOrdenPedido),

    -- Relaciones
    FOREIGN KEY (idCP) REFERENCES t03comprobantepago(idCP),
    FOREIGN KEY (idOrdenPedido) REFERENCES t02ordenpedido(Id_OrdenPedido),
    FOREIGN KEY (idNotaCredito) REFERENCES t21notacredito(Id_NotaCredito)
);


use mundo_patitas2;
/*use mundo_patitas2;
*/
DESC t03comprobantepago;

ALTER TABLE t03comprobantepago
	ADD COLUMN idNotaCredito INT  UNIQUE AFTER idMetodoPago,
	ADD CONSTRAINT fk_t03_t21_idNotaCredito
    FOREIGN KEY (idNotaCredito)
    REFERENCES t21notacredito (Id_NotaCredito)
    ON UPDATE CASCADE
    ON DELETE SET NULL;

DESC t65detcomprobantepago;
SHOW CREATE TABLE t65detcomprobantepago;

ALTER TABLE t65detcomprobantepago
	DROP FOREIGN KEY t65detcomprobantepago_ibfk_3;

ALTER TABLE t65detcomprobantepago
	DROP COLUMN idNotaCredito;


select * from t02ordenpedido;
/*
pk = Id_OrdenPedido
*/

select * from t03comprobantepago;
describe t03comprobantepago;

select * from t16catalogotrabajadores where cargo = "Cajero";
/*
El id del cliente debe de venir directamente de la orden de pedido

EL monto total se obtiene de la ordenPedido
*/

select * from t02ordenpedido;

select * from t03comprobantepago;


INSERT INTO t03comprobantepago
(numeroCP, tipoComprobante, fechaEmision, idCliente, idTrabajador, montoTotal, estado, idMetodoPago)
VALUES
('BO259565001', 1, CURDATE(), 60001, 50001, 129.90, 'Generado', 1);


select * from t65detcomprobantepago;
describe t65detcomprobantepago;
INSERT INTO t65detcomprobantepago
values  (103001, 10001,  null, 100.42,18.08, 118.5);

DROP PROCEDURE IF EXISTS createComprobantePago;

DELIMITER $$
CREATE PROCEDURE createComprobantePago (
    IN p_Id_OrdenPedido_Json JSON,  -- Ej: [10001,10002,10003]
    IN p_tipoComprobante    INT,   -- 1 = boleta, 2 = factura
    IN p_idTrabajador       INT,
    IN p_idMetodoPago       INT,   -- 1 = efectivo, 2 = tarjeta
    IN p_idNotaCredito      INT    -- puede ser NULL
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

    DECLARE exit handler FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
		RESIGNAL;
    END;

    START TRANSACTION;

    /* 1) Validación Json Existente */
    SET v_jsonLen = JSON_LENGTH(p_Id_OrdenPedido_Json);
    IF v_jsonLen IS NULL OR v_jsonLen = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La lista JSON de órdenes está vacía.';
    END IF;

    /* 2) Verificar existencia de órdenes y obtener cliente único */
    SELECT 
        COUNT(*) AS rows_join,
        COUNT(DISTINCT t02ordenpedido.Id_Cliente)  	AS clientes_distintos,
        MIN(t02ordenpedido.Id_Cliente)            AS cliente_unico
    INTO
        v_rowsFound,
        v_clientesDist,
        v_idClientePrimero
    FROM JSON_TABLE(
            p_Id_OrdenPedido_Json,
            '$[*]' COLUMNS (idOrdenPedido INT PATH '$')
         ) jt
    JOIN t02ordenpedido
      ON t02ordenpedido.Id_OrdenPedido = jt.idOrdenPedido;

    IF v_rowsFound <> v_jsonLen THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Alguna orden no existe en 
        .';
    END IF;

    IF v_clientesDist > 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Todas las órdenes deben pertenecer al mismo cliente.';
    END IF;

    /* 3) Generar cabecera (idCliente del conjunto, montoTotal = NULL inicialmente) */
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

    /* 4) Insertar detalles por lote a partir del JSON (base, igv, neto) */
    INSERT INTO t65detcomprobantepago (
        idCP, idOrdenPedido, totalOrdenPedido, igv, totalNeto
    )
    SELECT
        v_idCP,
        t02ordenpedido.Id_OrdenPedido,
        ROUND(t02ordenpedido.Total / 1.18, 2)         				AS totalBase,
        ROUND(t02ordenpedido.Total - ROUND(t02ordenpedido.Total / 1.18, 2), 2)   	AS igv,
        t02ordenpedido.Total                             AS totalNeto
    FROM JSON_TABLE(
            p_Id_OrdenPedido_Json,
            '$[*]' COLUMNS (idOrdenPedido INT PATH '$')
         ) jt
    JOIN t02ordenpedido
      ON t02ordenpedido.Id_OrdenPedido = jt.idOrdenPedido;

    /* 5) Sumar el neto total de las órdenes seleccionadas */
    SELECT
        SUM(t02ordenpedido.Total)
    INTO v_sumaNeto
    FROM JSON_TABLE(
            p_Id_OrdenPedido_Json,
            '$[*]' COLUMNS (idOrdenPedido  INT PATH '$')
         ) jt
    JOIN t02ordenpedido
      ON t02ordenpedido.Id_OrdenPedido = jt.idOrdenPedido;

    /* 6) Traer nota de crédito (0 si no aplica o no existe) */
    SET v_ncMonto = COALESCE(
        (SELECT n.Total FROM t21notacredito n WHERE n.Id_NotaCredito = p_idNotaCredito),
        0
    );

    /* 7) Actualizar cabecera con total final */
    UPDATE t03comprobantepago
    SET montoTotal = v_sumaNeto - v_ncMonto
    WHERE idCP = v_idCP;
    
    UPDATE t02ordenpedido
	JOIN JSON_TABLE(
		p_Id_OrdenPedido_Json,
		'$[*]' COLUMNS (idOrdenPedido  INT PATH '$')
	) jt
	  ON t02ordenpedido.Id_OrdenPedido = jt.idOrdenPedido
	SET t02ordenpedido.Estado = 'Pagado';

    COMMIT;
END $$
DELIMITER ;

/*	Llamado al procedimiento, si no funciona me mato*/
CALL createComprobantePago(
  '[10001]',
  2,        -- factura
  50001,    -- idTrabajador
  2,        -- tarjeta
  null      -- idNotaCredito
);

select * from t02ordenpedido;


select * from t64tipocomprobantepago;

/*Detalles de comprobante de pago: */
select 
t18catalogoproducto.Id_Producto,
t18catalogoproducto.NombreProducto,
sum(t60detordenpedido.Cantidad) as 'cantidadTotalProductos',
t18catalogoproducto.PrecioUnitario

from t65detcomprobantepago 
join t02ordenpedido
	on t02ordenpedido.Id_OrdenPedido =t65detcomprobantepago.idOrdenPedido
join t60detordenpedido
	on t60detordenpedido.t02OrdenPedido_Id_OrdenPedido = t02ordenpedido.Id_OrdenPedido
join t18catalogoproducto
	on t18catalogoproducto.Id_Producto = t60detordenpedido.t18CatalogoProducto_Id_Producto
where idCP = 103008
Group by t18catalogoproducto.Id_Producto;


select * from t02ordenpedido;

select 
	t02ordenpedido.Id_OrdenPedido,
	t02ordenpedido.CostoEntrega
from t65detcomprobantepago 
join t02ordenpedido
	on t02ordenpedido.Id_OrdenPedido =t65detcomprobantepago.idOrdenPedido
where idCP = 103008
