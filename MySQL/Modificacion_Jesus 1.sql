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