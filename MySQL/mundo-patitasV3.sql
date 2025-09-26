-- ==========================================================
-- Mundo Patitas - Script Definitivo (MySQL 8 / InnoDB / utf8mb4)
-- Con PK numéricas (id_Trabajador, Id_Cliente) y seeds diferenciadas
-- ==========================================================

DROP DATABASE IF EXISTS mundo_patitas2;
CREATE DATABASE mundo_patitas2
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_0900_ai_ci;
USE mundo_patitas2;

-- Endurecer chequeos
SET sql_mode = 'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- ==========================================================
-- 1) Catálogos / Maestras mínimas
-- ==========================================================

-- Clientes ahora con PK numérica y DNI único (de V2)
CREATE TABLE t20Cliente (
  Id_Cliente INT NOT NULL AUTO_INCREMENT,
  DniCli VARCHAR(8) NOT NULL,
  des_apepatCliente VARCHAR(20) NOT NULL,
  des_apematCliente VARCHAR(20) NOT NULL,
  des_nombreCliente VARCHAR(20) NOT NULL,
  num_telefonoCliente VARCHAR(9) NOT NULL,
  email_cliente VARCHAR(35) NOT NULL,
  direccionCliente VARCHAR(50) NOT NULL,
  estado VARCHAR(15) NOT NULL,
  CONSTRAINT t20Cliente_pk PRIMARY KEY (Id_Cliente),
  CONSTRAINT uq_t20_dni UNIQUE (DniCli)
) ENGINE=InnoDB AUTO_INCREMENT=60001;

-- Trabajadores con PK numérica y DNI único
CREATE TABLE t16CatalogoTrabajadores (
  id_Trabajador INT NOT NULL AUTO_INCREMENT,
  DNITrabajador VARCHAR(8) NOT NULL,
  des_apepatTrabajador VARCHAR(40) NOT NULL,
  des_apematTrabajador VARCHAR(40) NOT NULL,
  des_nombreTrabajador VARCHAR(60) NOT NULL,
  num_telefono VARCHAR(15) NOT NULL,
  direccion VARCHAR(120) NOT NULL,
  email VARCHAR(100) NOT NULL,
  cargo VARCHAR(40) NOT NULL,
  estado VARCHAR(15) NOT NULL,
  CONSTRAINT t16CatalogoTrabajadores_pk PRIMARY KEY (id_Trabajador),
  CONSTRAINT uq_t16_dni UNIQUE (DNITrabajador)
) ENGINE=InnoDB AUTO_INCREMENT=50001;

CREATE TABLE t31CategoriaProducto (
  Id_Categoria INT NOT NULL AUTO_INCREMENT,
  Descripcion VARCHAR(50) NOT NULL,
  CONSTRAINT t31CategoriaProducto_pk PRIMARY KEY (Id_Categoria)
) ENGINE=InnoDB AUTO_INCREMENT=21001;

CREATE TABLE t34UnidadMedida (
  Id_UnidadMedida INT NOT NULL AUTO_INCREMENT,
  Descripcion VARCHAR(30) NOT NULL,
  CONSTRAINT t34UnidadMedida_pk PRIMARY KEY (Id_UnidadMedida)
) ENGINE=InnoDB AUTO_INCREMENT=24001;

CREATE TABLE t37DetalleRequerimiento (
  Id_DetaRequerimiento INT NOT NULL AUTO_INCREMENT,
  Observacion VARCHAR(200),
  CONSTRAINT t37DetalleRequerimiento_pk PRIMARY KEY (Id_DetaRequerimiento)
) ENGINE=InnoDB AUTO_INCREMENT=37001;

-- ==========================================================
-- 2) Productos / Proveedores
-- ==========================================================

CREATE TABLE t18catalogoproducto (
  Id_Producto int NOT NULL AUTO_INCREMENT,
  NombreProducto varchar(100) NOT NULL,
  Descripcion varchar(200) NOT NULL,
  Marca varchar(30) NOT NULL,
  PrecioUnitario decimal(12,2) NOT NULL,
  StockActual int NOT NULL,
  StockMinimo int NOT NULL,
  StockMaximo int NOT NULL,
  Estado varchar(15) NOT NULL,
  t31CategoriaProducto_Id_Categoria int NOT NULL,
  t34UnidadMedida_Id_UnidadMedida int NOT NULL,
  PRIMARY KEY (Id_Producto),
  KEY fk_t18_categoria (t31CategoriaProducto_Id_Categoria),
  KEY fk_t18_unidad (t34UnidadMedida_Id_UnidadMedida),
  KEY idx_t18_nombre (NombreProducto),
  CONSTRAINT fk_t18_categoria FOREIGN KEY (t31CategoriaProducto_Id_Categoria)
    REFERENCES t31CategoriaProducto (Id_Categoria)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_t18_unidad FOREIGN KEY (t34UnidadMedida_Id_UnidadMedida)
    REFERENCES t34UnidadMedida (Id_UnidadMedida)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT t18catalogoproducto_chk_1 CHECK (PrecioUnitario >= 0),
  CONSTRAINT t18catalogoproducto_chk_2 CHECK (StockActual >= 0),
  CONSTRAINT t18catalogoproducto_chk_3 CHECK (StockMinimo >= 0),
  CONSTRAINT t18catalogoproducto_chk_4 CHECK (StockMaximo >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=1031;


-- (Opcional) catálogo legacy no referenciado
CREATE TABLE t58Producto (
  Id_Producto INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(60) NOT NULL,
  descripcion VARCHAR(200) NOT NULL,
  precio DECIMAL(12,2) NOT NULL CHECK (precio >= 0),
  CONSTRAINT t58Producto_pk PRIMARY KEY (Id_Producto)
) ENGINE=InnoDB AUTO_INCREMENT=58001;

CREATE TABLE t17CatalogoProveedor (
  Id_NumRuc VARCHAR(11) NOT NULL,
  des_RazonSocial VARCHAR(50) NOT NULL,
  DDireccionProv VARCHAR(50) NOT NULL,
  Telefono VARCHAR(15) NOT NULL,
  Correo VARCHAR(100) NOT NULL,
  estado VARCHAR(15) NOT NULL,
  t18CatalogoProducto_Id_Producto INT NOT NULL,
  CONSTRAINT t17CatalogoProveedor_pk PRIMARY KEY (Id_NumRuc),
  CONSTRAINT fk_t17_prod
    FOREIGN KEY (t18CatalogoProducto_Id_Producto)
    REFERENCES t18CatalogoProducto (Id_Producto)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ==========================================================
-- 3) Catálogos de Pago (antes de ventas, para FKs)
-- ==========================================================

CREATE TABLE t28_Metodopago (
  Id_MetodoPago INT NOT NULL AUTO_INCREMENT,
  Descripcion VARCHAR(40) NOT NULL,
  CONSTRAINT t28_Metodopago_pk PRIMARY KEY (Id_MetodoPago)
) ENGINE=InnoDB AUTO_INCREMENT=28001;

CREATE TABLE t30TipoBanco (
  Id_TipoBanco INT NOT NULL AUTO_INCREMENT,
  Descripcion VARCHAR(40) NOT NULL,
  CONSTRAINT t30TipoBanco_pk PRIMARY KEY (Id_TipoBanco)
) ENGINE=InnoDB AUTO_INCREMENT=30001;

-- ==========================================================
-- 4) Ventas: Orden / Preorden / Comprobante / Pagos / Entrega
-- ==========================================================

CREATE TABLE t27MetodoEntrega (
  Id_MetodoEntrega INT NOT NULL AUTO_INCREMENT,
  Descripcion VARCHAR(40) NOT NULL,
  Costo DECIMAL(12,2) NOT NULL CHECK (Costo >= 0),
  Estado VARCHAR(20) NOT NULL,
  CONSTRAINT t27MetodoEntrega_pk PRIMARY KEY (Id_MetodoEntrega)
) ENGINE=InnoDB AUTO_INCREMENT=9001;

CREATE TABLE t02OrdenPedido (
  Id_OrdenPedido INT NOT NULL AUTO_INCREMENT,
  Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Id_Cliente INT NOT NULL,
  Id_MetodoEntrega INT,
  CostoEntrega DECIMAL(10,2) NOT NULL DEFAULT 0 CHECK (CostoEntrega >= 0),
  Descuento    DECIMAL(10,2) NOT NULL DEFAULT 0 CHECK (Descuento >= 0),
  Total        DECIMAL(12,2) NOT NULL DEFAULT 0 CHECK (Total >= 0),
  Estado VARCHAR(15),
  CONSTRAINT t02OrdenPedido_pk PRIMARY KEY (Id_OrdenPedido),
  CONSTRAINT fk_t02_cliente FOREIGN KEY (Id_Cliente)
    REFERENCES t20Cliente (Id_Cliente)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t02_metodo FOREIGN KEY (Id_MetodoEntrega)
    REFERENCES t27MetodoEntrega (Id_MetodoEntrega)
    ON UPDATE RESTRICT ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10001;

-- Preorden (versión dump + ajustes de FKs y FK faltante a t02)
CREATE TABLE t01PreOrdenPedido (
  Id_PreOrdenPedido INT NOT NULL AUTO_INCREMENT,
  t20Cliente_Id_Cliente INT NULL,
  Fec_Emision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Estado VARCHAR(15) NOT NULL DEFAULT 'Emitido',
  Total DECIMAL(10,2) DEFAULT 0.00,
  PRIMARY KEY (Id_PreOrdenPedido),
  KEY idx_preorden_cliente_estado_fec (t20Cliente_Id_Cliente, Estado, Fec_Emision),
  CONSTRAINT fk_t01_cliente
    FOREIGN KEY (t20Cliente_Id_Cliente)
    REFERENCES t20Cliente (Id_Cliente)
    ON UPDATE RESTRICT ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10;

-- =======================
-- t90PreOrden_OrdenPedido
-- =======================
CREATE TABLE t90PreOrden_OrdenPedido (
  Id                INT NOT NULL AUTO_INCREMENT,
  Id_OrdenPedido    INT NOT NULL,
  Id_PreOrdenPedido INT NOT NULL,
  Fec_Vinculo       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (Id),
  UNIQUE KEY uq_t90_par (Id_OrdenPedido, Id_PreOrdenPedido),
  UNIQUE KEY uq_t90_preorden_unica (Id_PreOrdenPedido),
  CONSTRAINT fk_t90_orden
    FOREIGN KEY (Id_OrdenPedido)
    REFERENCES t02OrdenPedido (Id_OrdenPedido)
    ON UPDATE RESTRICT ON DELETE CASCADE,
  CONSTRAINT fk_t90_preorden
    FOREIGN KEY (Id_PreOrdenPedido)
    REFERENCES t01PreOrdenPedido (Id_PreOrdenPedido)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;



CREATE TABLE t03ComprobantePago (
  Nro_ComproPago INT NOT NULL AUTO_INCREMENT,
  Tipo VARCHAR(20),
  Id_MetodoPago INT NULL,
  Id_TipoBanco  INT NULL,
  CONSTRAINT t03ComprobantePago_pk PRIMARY KEY (Nro_ComproPago),
  CONSTRAINT fk_cmp_metodo FOREIGN KEY (Id_MetodoPago)
    REFERENCES t28_Metodopago (Id_MetodoPago)
    ON UPDATE RESTRICT ON DELETE SET NULL,
  CONSTRAINT fk_cmp_banco FOREIGN KEY (Id_TipoBanco)
    REFERENCES t30TipoBanco (Id_TipoBanco)
    ON UPDATE RESTRICT ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=103001;

CREATE TABLE t59OrdenServicioEntrega (
  Id_OSE           INT NOT NULL AUTO_INCREMENT,
  Id_OrdenPedido   INT NOT NULL,
  FechaProgramada  DATE NOT NULL,
  Estado           VARCHAR(20),
  FecCreacion      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FecModificacion  DATETIME NULL,

  CONSTRAINT pk_t59 PRIMARY KEY (Id_OSE),
  CONSTRAINT uq_t59_orden UNIQUE (Id_OrdenPedido),
  CONSTRAINT fk_t59_orden FOREIGN KEY (Id_OrdenPedido)
    REFERENCES t02OrdenPedido (Id_OrdenPedido)
    ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE t04Factura (
  Id_Factura INT NOT NULL AUTO_INCREMENT,
  Nro_ComproPago INT,
  CONSTRAINT t04Factura_pk PRIMARY KEY (Id_Factura),
  CONSTRAINT fk_t04_cmp FOREIGN KEY (Nro_ComproPago)
    REFERENCES t03ComprobantePago (Nro_ComproPago)
    ON UPDATE RESTRICT ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=104001;

CREATE TABLE t05Boleta (
  Id_Boleta INT NOT NULL AUTO_INCREMENT,
  Nro_ComproPago INT,
  CONSTRAINT t05Boleta_pk PRIMARY KEY (Id_Boleta),
  CONSTRAINT fk_t05_cmp FOREIGN KEY (Nro_ComproPago)
    REFERENCES t03ComprobantePago (Nro_ComproPago)
    ON UPDATE RESTRICT ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=105001;

-- ============================================
-- Catálogo de direcciones del cliente (t70)
-- ============================================
CREATE TABLE IF NOT EXISTS t70DireccionEnvioCliente (
  Id_DireccionEnvio INT AUTO_INCREMENT PRIMARY KEY,
  Id_Cliente        INT NOT NULL,
  NombreContacto    VARCHAR(120) NOT NULL,
  TelefonoContacto  VARCHAR(20)  NOT NULL,
  Direccion         VARCHAR(255) NOT NULL,
  Distrito          VARCHAR(120) NOT NULL,
  DniReceptor    VARCHAR(8)   NOT NULL,
  INDEX idx_t70_cliente  (Id_Cliente),
  INDEX idx_t70_distrito (Distrito),
  CONSTRAINT fk_t70_cliente
    FOREIGN KEY (Id_Cliente)
    REFERENCES t20Cliente (Id_Cliente)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Snapshot por orden (t71) 1:1 con la orden de pedido
-- =====================================================
CREATE TABLE IF NOT EXISTS t71OrdenDirecEnvio (
  Id_OrdenDirecEnvio INT AUTO_INCREMENT PRIMARY KEY,
  Id_OrdenPedido     INT NOT NULL,
  NombreContactoSnap VARCHAR(120) NOT NULL,
  TelefonoSnap       VARCHAR(20)  NOT NULL,
  DireccionSnap      VARCHAR(255) NOT NULL,
  DistritoSnap       VARCHAR(120) NOT NULL,
  ReceptorDniSnap    VARCHAR(8)   NOT NULL,
  UNIQUE KEY uq_t71_orden (Id_OrdenPedido),
  CONSTRAINT fk_t71_orden
    FOREIGN KEY (Id_OrdenPedido)
    REFERENCES t02OrdenPedido (Id_OrdenPedido)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



CREATE TABLE IF NOT EXISTS t92Ref_Snapshot_DirCatalogo (
  Id                  INT AUTO_INCREMENT PRIMARY KEY,
  Id_OrdenDirecEnvio  INT NOT NULL,
  Id_DireccionEnvio   INT NOT NULL,
  UNIQUE KEY uq_t92_snapshot (Id_OrdenDirecEnvio), -- a lo más 1 vínculo
  INDEX idx_t92_dir (Id_DireccionEnvio),
  CONSTRAINT fk_t92_t71
    FOREIGN KEY (Id_OrdenDirecEnvio) REFERENCES t71OrdenDirecEnvio (Id_OrdenDirecEnvio)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_t92_t70
    FOREIGN KEY (Id_DireccionEnvio)  REFERENCES t70DireccionEnvioCliente (Id_DireccionEnvio)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- ==========================================================
-- 5) Compras / Ingresos / Kardex / Salidas / Incidencias
-- ==========================================================

CREATE TABLE t06OrdenCompra (
  Id_OrdenCompra INT NOT NULL AUTO_INCREMENT,
  Fec_Emision DATE,
  des_RazonSocial VARCHAR(100),
  Fec_Atencion DATE,
  CONSTRAINT t06OrdenCompra_pk PRIMARY KEY (Id_OrdenCompra)
) ENGINE=InnoDB AUTO_INCREMENT=20001;

CREATE TABLE t07DetalleOrdenCompra (
  Id_Detalle INT NOT NULL AUTO_INCREMENT,
  Id_OrdenCompra INT NOT NULL,
  Id_Producto INT NOT NULL,
  Cantidad INT NOT NULL CHECK (Cantidad >= 0),
  PrecioUnitario DECIMAL(12,2) NOT NULL CHECK (PrecioUnitario >= 0),
  CONSTRAINT t07DetalleOrdenCompra_pk PRIMARY KEY (Id_Detalle),
  CONSTRAINT fk_t07_oc FOREIGN KEY (Id_OrdenCompra)
    REFERENCES t06OrdenCompra (Id_OrdenCompra)
    ON UPDATE RESTRICT ON DELETE CASCADE,
  CONSTRAINT fk_t07_prod FOREIGN KEY (Id_Producto)
    REFERENCES t18CatalogoProducto (Id_Producto)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=200001;

CREATE TABLE t08ReporteIncidencia (
  Id_ReporteIncidencia INT NOT NULL AUTO_INCREMENT,
  id_Trabajador INT NOT NULL,
  fec_Informe DATE NOT NULL,
  Lista_Productos LONGTEXT NOT NULL,
  DetaIncidencia LONGTEXT NOT NULL,
  Estado VARCHAR(15) NOT NULL,
  CONSTRAINT t08ReporteIncidencia_pk PRIMARY KEY (Id_ReporteIncidencia),
  CONSTRAINT fk_t08_trab FOREIGN KEY (id_Trabajador)
    REFERENCES t16CatalogoTrabajadores (id_Trabajador)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=8001;

CREATE TABLE t12InformeIncidencia (
  Id_InfomeIncidencia INT NOT NULL AUTO_INCREMENT,
  t08ReporteIncidencia_Id_ReporteIncidencia INT NOT NULL,
  id_Trabajador INT NOT NULL,
  fec_Informe DATE NOT NULL,
  Lista_Productos LONGTEXT NOT NULL,
  DetaIncidencia LONGTEXT NOT NULL,
  Estado VARCHAR(15) NOT NULL,
  CONSTRAINT t12InformeIncidencia_pk PRIMARY KEY (Id_InfomeIncidencia),
  CONSTRAINT fk_t12_t08 FOREIGN KEY (t08ReporteIncidencia_Id_ReporteIncidencia)
    REFERENCES t08ReporteIncidencia (Id_ReporteIncidencia)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t12_trab FOREIGN KEY (id_Trabajador)
    REFERENCES t16CatalogoTrabajadores (id_Trabajador)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=12001;

CREATE TABLE t11OrdenSalida (
  Id_ordenSalida INT NOT NULL AUTO_INCREMENT,
  t02OrdenPedido_Id_OrdenPedido INT NOT NULL,
  CONSTRAINT t11OrdenSalida_pk PRIMARY KEY (Id_ordenSalida),
  CONSTRAINT fk_t11_t02 FOREIGN KEY (t02OrdenPedido_Id_OrdenPedido)
    REFERENCES t02OrdenPedido (Id_OrdenPedido)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3001;

CREATE TABLE t10Kardex (
  Id_Kardex INT NOT NULL AUTO_INCREMENT,
  Fec_Transaccion DATE NOT NULL,
  id_Producto INT NOT NULL,
  Cantidad INT NOT NULL CHECK (Cantidad >= 0),
  Estado VARCHAR(15) NOT NULL,
  t11OrdenSalida_Id_ordenSalida INT NOT NULL,
  CONSTRAINT t10Kardex_pk PRIMARY KEY (Id_Kardex),
  CONSTRAINT fk_t10_t11 FOREIGN KEY (t11OrdenSalida_Id_ordenSalida)
    REFERENCES t11OrdenSalida (Id_ordenSalida)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t10_prod FOREIGN KEY (id_Producto)
    REFERENCES t18CatalogoProducto (Id_Producto)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=30001;

CREATE TABLE t19OrdenIngresoCompra (
  Id_OrdenIngresoCompra INT NOT NULL AUTO_INCREMENT,
  t06OrdenCompra_Id_OrdenCompra INT NOT NULL,
  id_Trabajador INT NOT NULL,
  Fec_Emision DATE NOT NULL,
  ListaProductos LONGTEXT NOT NULL,
  Estado VARCHAR(15) NOT NULL,
  CONSTRAINT t19OrdenIngresoCompra_pk PRIMARY KEY (Id_OrdenIngresoCompra),
  CONSTRAINT fk_t19_t06 FOREIGN KEY (t06OrdenCompra_Id_OrdenCompra)
    REFERENCES t06OrdenCompra (Id_OrdenCompra)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t19_trab FOREIGN KEY (id_Trabajador)
    REFERENCES t16CatalogoTrabajadores (id_Trabajador)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=20051;

CREATE TABLE t09OrdenIngresoAlmacen (
  Id_OrdenIngresoAlmacen INT NOT NULL AUTO_INCREMENT,
  t19OrdenIngresoCompra_Id_OrdenIngresoCompra INT NOT NULL,
  id_Trabajador INT NOT NULL,
  Lista_productos LONGTEXT NOT NULL,
  fec_ingreso DATE NOT NULL,
  Observacion LONGTEXT NOT NULL,
  Estado VARCHAR(15) NOT NULL,
  t10Kardex_Id_Kardex INT NOT NULL,
  CONSTRAINT t09OrdenIngresoAlmacen_pk PRIMARY KEY (Id_OrdenIngresoAlmacen),
  CONSTRAINT fk_t09_t19
    FOREIGN KEY (t19OrdenIngresoCompra_Id_OrdenIngresoCompra)
    REFERENCES t19OrdenIngresoCompra (Id_OrdenIngresoCompra)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t09_t10
    FOREIGN KEY (t10Kardex_Id_Kardex)
    REFERENCES t10Kardex (Id_Kardex)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t09_trab FOREIGN KEY (id_Trabajador)
    REFERENCES t16CatalogoTrabajadores (id_Trabajador)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=20081;

-- ==========================================================
-- 6) Cierre de caja / Notas / Cobertura
-- ==========================================================

CREATE TABLE t26ReporteCierreCaja (
  id_ReporteCierre INT NOT NULL AUTO_INCREMENT,
  Fec_emision DATE NOT NULL,
  id_Trabajador INT NOT NULL,
  des_apepatTrabajador VARCHAR(30) NOT NULL,
  des_apematTrabajador VARCHAR(30) NOT NULL,
  des_nombreTrabajador VARCHAR(40) NOT NULL,
  montoContado DECIMAL(12,2) NOT NULL CHECK (montoContado >= 0),
  montoRegistrado DECIMAL(12,2) NOT NULL CHECK (montoRegistrado >= 0),
  diferencia DECIMAL(12,2) NOT NULL,
  observaciones LONGTEXT NOT NULL,
  estado VARCHAR(15) NOT NULL,
  t02OrdenPedido_Id_OrdenPedido INT NULL,
  CONSTRAINT t26ReporteCierreCaja_pk PRIMARY KEY (id_ReporteCierre),
  CONSTRAINT fk_t26_t02 FOREIGN KEY (t02OrdenPedido_Id_OrdenPedido)
    REFERENCES t02OrdenPedido (Id_OrdenPedido)
    ON UPDATE RESTRICT ON DELETE SET NULL,
  CONSTRAINT fk_t26_trab FOREIGN KEY (id_Trabajador)
    REFERENCES t16CatalogoTrabajadores (id_Trabajador)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=40001;

CREATE TABLE t21NotaCredito (
  Id_NotaCredito INT NOT NULL AUTO_INCREMENT,
  Fec_emision DATE NOT NULL,
  codigoNotaCredito INT NOT NULL,
  fecha_caducidad DATE NOT NULL,
  Id_OrdenPedido INT NOT NULL,
  id_Trabajador INT NOT NULL,
  Nro_comprobantePago INT NOT NULL,
  Total DECIMAL(12,2) NOT NULL CHECK (Total >= 0),
  Estado VARCHAR(15) NOT NULL,
  t20Cliente_Id_Cliente INT NOT NULL,
  CONSTRAINT t21NotaCredito_pk PRIMARY KEY (Id_NotaCredito),
  CONSTRAINT fk_t21_cliente FOREIGN KEY (t20Cliente_Id_Cliente)
    REFERENCES t20Cliente (Id_Cliente)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t21_cmp FOREIGN KEY (Nro_comprobantePago)
    REFERENCES t03ComprobantePago (Nro_ComproPago)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t21_orden FOREIGN KEY (Id_OrdenPedido)
    REFERENCES t02OrdenPedido (Id_OrdenPedido)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t21_trab FOREIGN KEY (id_Trabajador)
    REFERENCES t16CatalogoTrabajadores (id_Trabajador)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=41001;

CREATE TABLE t23NotaFaltanteCaja (
  id_NotaFaltante INT NOT NULL AUTO_INCREMENT,
  t26ReporteCierreCaja_id_ReporteCierre INT NOT NULL,
  id_Trabajador INT NOT NULL,
  montoFaltante DECIMAL(12,2) NOT NULL CHECK (montoFaltante >= 0),
  fechaEmision DATE NOT NULL,
  CONSTRAINT t23NotaFaltanteCaja_pk PRIMARY KEY (id_NotaFaltante),
  CONSTRAINT fk_t23_t26 FOREIGN KEY (t26ReporteCierreCaja_id_ReporteCierre)
    REFERENCES t26ReporteCierreCaja (id_ReporteCierre)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t23_trab FOREIGN KEY (id_Trabajador)
    REFERENCES t16CatalogoTrabajadores (id_Trabajador)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=43001;

CREATE TABLE t24NotaSobranteCaja (
  id_NotaSobrante INT NOT NULL AUTO_INCREMENT,
  t26ReporteCierreCaja_id_ReporteCierre INT NOT NULL,
  id_Trabajador INT NOT NULL,
  Fec_emi DATE NOT NULL,
  montoSobrante DECIMAL(12,2) NOT NULL CHECK (montoSobrante >= 0),
  observaciones VARCHAR(200) NOT NULL,
  CONSTRAINT t24NotaSobranteCaja_pk PRIMARY KEY (id_NotaSobrante),
  CONSTRAINT fk_t24_t26 FOREIGN KEY (t26ReporteCierreCaja_id_ReporteCierre)
    REFERENCES t26ReporteCierreCaja (id_ReporteCierre)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t24_trab FOREIGN KEY (id_Trabajador)
    REFERENCES t16CatalogoTrabajadores (id_Trabajador)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=44001;

CREATE TABLE t22NotaDescargoCobertura (
  id_NotaDescargo INT NOT NULL AUTO_INCREMENT,
  Fec_emi DATE NOT NULL,
  montoCubierto DECIMAL(12,2) NOT NULL CHECK (montoCubierto >= 0),
  Estado VARCHAR(15) NOT NULL,
  t23NotaFaltanteCaja_id_NotaFaltante INT NOT NULL,
  CONSTRAINT t22NotaDescargoCobertura_pk PRIMARY KEY (id_NotaDescargo),
  CONSTRAINT fk_t22_t23 FOREIGN KEY (t23NotaFaltanteCaja_id_NotaFaltante)
    REFERENCES t23NotaFaltanteCaja (id_NotaFaltante)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=42001;

CREATE TABLE t25CoberturaRiesgo (
  id_Cobertura INT NOT NULL AUTO_INCREMENT,
  id_Trabajador INT NOT NULL,
  saldoDisponible DECIMAL(12,2) NOT NULL CHECK (saldoDisponible >= 0),
  fechaUltimaAct DATE NOT NULL,
  estado VARCHAR(15) NOT NULL,
  CONSTRAINT t25CoberturaRiesgo_pk PRIMARY KEY (id_Cobertura),
  CONSTRAINT fk_t25_trab FOREIGN KEY (id_Trabajador)
    REFERENCES t16CatalogoTrabajadores (id_Trabajador)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=45001;

-- ==========================================================
-- 7) Devoluciones (catálogos globales) y detalle orden
-- ==========================================================

CREATE TABLE t52MotivoDevolucion (
  Id_Motivo INT NOT NULL AUTO_INCREMENT,
  Descripcion VARCHAR(100) NOT NULL,
  CONSTRAINT t52MotivoDevolucion_pk PRIMARY KEY (Id_Motivo)
) ENGINE=InnoDB AUTO_INCREMENT=52001;

CREATE TABLE t53TipoDevolucion (
  Id_tipo INT NOT NULL AUTO_INCREMENT,
  Descripcion VARCHAR(50) NOT NULL,
  CONSTRAINT t53TipoDevolucion_pk PRIMARY KEY (Id_tipo)
) ENGINE=InnoDB AUTO_INCREMENT=53001;

CREATE TABLE t50OrdenDevolucion (
  Id_Devolucion INT NOT NULL AUTO_INCREMENT,
  fecha_emision DATE NOT NULL,
  Id_Tipo INT NULL,
  Id_Motivo INT NULL,
  motivo VARCHAR(50) NOT NULL,
  palabras_cliente VARCHAR(100) NOT NULL,
  total DECIMAL(12,2) NOT NULL CHECK (total >= 0),
  estado VARCHAR(20) NOT NULL,
  t20Cliente_Id_Cliente INT NOT NULL,
  t21NotaCredito_Id_NotaCredito INT NOT NULL,
  CONSTRAINT t50OrdenDevolucion_pk PRIMARY KEY (Id_Devolucion),
  CONSTRAINT fk_t50_cliente FOREIGN KEY (t20Cliente_Id_Cliente)
    REFERENCES t20Cliente (Id_Cliente)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t50_nc FOREIGN KEY (t21NotaCredito_Id_NotaCredito)
    REFERENCES t21NotaCredito (Id_NotaCredito)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t50_tipo FOREIGN KEY (Id_Tipo)
    REFERENCES t53TipoDevolucion (Id_tipo)
    ON UPDATE RESTRICT ON DELETE SET NULL,
  CONSTRAINT fk_t50_motivo FOREIGN KEY (Id_Motivo)
    REFERENCES t52MotivoDevolucion (Id_Motivo)
    ON UPDATE RESTRICT ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=70001;

CREATE TABLE t54EvaluacionDevolucion (
  Id_Evaluacion INT NOT NULL AUTO_INCREMENT,
  Observaciones VARCHAR(200) NOT NULL,
  Resultado VARCHAR(30) NOT NULL,
  t50OrdenDevolucion_Id_Devolucion INT NOT NULL,
  CONSTRAINT t54EvaluacionDevolucion_pk PRIMARY KEY (Id_Evaluacion),
  CONSTRAINT fk_t54_t50 FOREIGN KEY (t50OrdenDevolucion_Id_Devolucion)
    REFERENCES t50OrdenDevolucion (Id_Devolucion)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=74001;

CREATE TABLE t55EntregaProductoDevuelto (
  Id_ProductoDevuelto INT NOT NULL AUTO_INCREMENT,
  CONSTRAINT t55EntregaProductoDevuelto_pk PRIMARY KEY (Id_ProductoDevuelto)
) ENGINE=InnoDB AUTO_INCREMENT=75001;

CREATE TABLE t56ReporteDevolucion (
  Id_Reporte INT NOT NULL AUTO_INCREMENT,
  CONSTRAINT t56ReporteDevolucion_pk PRIMARY KEY (Id_Reporte)
) ENGINE=InnoDB AUTO_INCREMENT=76001;

CREATE TABLE t57DetalleOrden (
  Id_DetalleOrden INT NOT NULL AUTO_INCREMENT,
  id_orden INT NOT NULL,
  sku_producto INT NOT NULL,
  cantidad INT NOT NULL CHECK (cantidad >= 0),
  subtotal DECIMAL(12,2) NOT NULL CHECK (subtotal >= 0),
  t50OrdenDevolucion_Id_Devolucion INT NOT NULL,
  CONSTRAINT t57DetalleOrden_pk PRIMARY KEY (Id_DetalleOrden),
  CONSTRAINT fk_t57_t50 FOREIGN KEY (t50OrdenDevolucion_Id_Devolucion)
    REFERENCES t50OrdenDevolucion (Id_Devolucion)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t57_t02 FOREIGN KEY (id_orden)
    REFERENCES t02OrdenPedido (Id_OrdenPedido)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t57_t18 FOREIGN KEY (sku_producto)
    REFERENCES t18CatalogoProducto (Id_Producto)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=77001;

-- ==========================================================
-- 8) Detalles de Orden / PreOrden
-- ==========================================================

CREATE TABLE t61detapreorden (
  Id_DetaPreOrden INT NOT NULL AUTO_INCREMENT,
  t18CatalogoProducto_Id_Producto INT NOT NULL,
  t01PreOrdenPedido_Id_PreOrdenPedido INT NOT NULL,
  Cantidad INT NOT NULL,
  PRIMARY KEY (Id_DetaPreOrden),
  KEY fk_t61_t01 (t01PreOrdenPedido_Id_PreOrdenPedido),
  KEY fk_t61_t18 (t18CatalogoProducto_Id_Producto),
  CONSTRAINT fk_t61_t01 FOREIGN KEY (t01PreOrdenPedido_Id_PreOrdenPedido)
    REFERENCES t01preordenpedido (Id_PreOrdenPedido)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t61_t18 FOREIGN KEY (t18CatalogoProducto_Id_Producto)
    REFERENCES t18CatalogoProducto (Id_Producto)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT t61detapreorden_chk_1 CHECK (Cantidad >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=61019;

-- Detalle de la Orden (versión pedida: sin columnas des_*)
DROP TABLE IF EXISTS t60DetOrdenPedido;
CREATE TABLE t60DetOrdenPedido (
  Id_DetOrdenPedido INT NOT NULL AUTO_INCREMENT,
  t18CatalogoProducto_Id_Producto INT NOT NULL,
  t02OrdenPedido_Id_OrdenPedido INT NOT NULL,
  Id_Cliente INT NOT NULL,
  Cantidad INT NOT NULL CHECK (Cantidad >= 0),
  CONSTRAINT t60DetOrdenPedido_pk PRIMARY KEY (Id_DetOrdenPedido),
  CONSTRAINT fk_t60_t02 FOREIGN KEY (t02OrdenPedido_Id_OrdenPedido)
    REFERENCES t02OrdenPedido (Id_OrdenPedido)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t60_t18 FOREIGN KEY (t18CatalogoProducto_Id_Producto)
    REFERENCES t18CatalogoProducto (Id_Producto)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t60_cli FOREIGN KEY (Id_Cliente)
    REFERENCES t20Cliente (Id_Cliente)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=60001;

-- ==========================================================
-- 9) Entregas / Evidencias / Incidencias
-- ==========================================================

CREATE TABLE t40OrdenAsignacionReparto (
  Id_OrdenAsignacion INT NOT NULL AUTO_INCREMENT,
  Fecha DATE,
  Estado VARCHAR(15),
  CONSTRAINT t40OrdenAsignacionReparto_pk PRIMARY KEY (Id_OrdenAsignacion)
) ENGINE=InnoDB AUTO_INCREMENT=80001;

CREATE TABLE t401DetalleAsignacionReparto (
  Id_DetalleAsignacion INT NOT NULL AUTO_INCREMENT,
  Id_OrdenAsignacion INT NOT NULL,
  Id_OrdenPedido INT NOT NULL,
  CONSTRAINT t401DetalleAsignacionReparto_pk PRIMARY KEY (Id_DetalleAsignacion),
  CONSTRAINT fk_t401_t40 FOREIGN KEY (Id_OrdenAsignacion)
    REFERENCES t40OrdenAsignacionReparto (Id_OrdenAsignacion)
    ON UPDATE RESTRICT ON DELETE CASCADE,
  CONSTRAINT fk_t401_t02 FOREIGN KEY (Id_OrdenPedido)
    REFERENCES t02OrdenPedido (Id_OrdenPedido)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=81001;

CREATE TABLE t402HojaRuta (
  Id_HojaRuta INT NOT NULL AUTO_INCREMENT,
  Id_OrdenAsignacion INT NOT NULL,
  Observaciones VARCHAR(200),
  CONSTRAINT t402HojaRuta_pk PRIMARY KEY (Id_HojaRuta),
  CONSTRAINT fk_t402_t40 FOREIGN KEY (Id_OrdenAsignacion)
    REFERENCES t40OrdenAsignacionReparto (Id_OrdenAsignacion)
    ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=82001;

CREATE TABLE t403OrdenSalidaEntrega (
  IDOrdenSalidaEntrega INT NOT NULL AUTO_INCREMENT,
  Id_OrdenPedido INT NOT NULL,
  Estado VARCHAR(15),
  CONSTRAINT t403OrdenSalidaEntrega_pk PRIMARY KEY (IDOrdenSalidaEntrega),
  CONSTRAINT fk_t403_t02 FOREIGN KEY (Id_OrdenPedido)
    REFERENCES t02OrdenPedido (Id_OrdenPedido)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=83001;

CREATE TABLE t404EvidenciaEntrega (
  IDEvidenciaEntrega INT NOT NULL AUTO_INCREMENT,
  IDPedido INT NOT NULL,
  Cliente VARCHAR(100) NOT NULL,
  PuntoEntrega TINYINT(1) NOT NULL,
  DocumentoReceptor TINYINT(1) NOT NULL,
  CapturaEntrega TINYINT(1) NOT NULL,
  Observaciones LONGTEXT NOT NULL,
  t403OrdenSalidaEntrega_IDOrdenSalidaEntrega INT NULL,
  CONSTRAINT t404EvidenciaEntrega_pk PRIMARY KEY (IDEvidenciaEntrega),
  CONSTRAINT fk_t404_t403 FOREIGN KEY (t403OrdenSalidaEntrega_IDOrdenSalidaEntrega)
    REFERENCES t403OrdenSalidaEntrega (IDOrdenSalidaEntrega)
    ON UPDATE RESTRICT ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=84001;

CREATE TABLE t405IncidenciaEntrega (
  IDIncidenciaEntrega INT NOT NULL AUTO_INCREMENT,
  IDPedido INT NOT NULL,
  Cliente VARCHAR(20) NOT NULL,
  Direccion VARCHAR(50) NOT NULL,
  PuntoEntrega VARCHAR(50) NOT NULL,
  Motivo VARCHAR(50) NOT NULL,
  Observaciones VARCHAR(50) NOT NULL,
  t403OrdenSalidaEntrega_IDOrdenSalidaEntrega INT NULL,
  CONSTRAINT t405IncidenciaEntrega_pk PRIMARY KEY (IDIncidenciaEntrega),
  CONSTRAINT fk_t405_t403 FOREIGN KEY (t403OrdenSalidaEntrega_IDOrdenSalidaEntrega)
    REFERENCES t403OrdenSalidaEntrega (IDOrdenSalidaEntrega)
    ON UPDATE RESTRICT ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=85001;

-- ==========================================================
-- 11) Índices útiles
-- ==========================================================
CREATE INDEX idx_t58_nombre ON t58Producto (nombre);
CREATE INDEX idx_t02_estado ON t02OrdenPedido (Estado);
CREATE INDEX idx_t26_estado ON t26ReporteCierreCaja (estado, Fec_emision);
CREATE INDEX idx_t21_cmp ON t21NotaCredito (Nro_comprobantePago);
CREATE INDEX idx_t09_fec ON t09OrdenIngresoAlmacen (fec_ingreso);

-- Ya existe como KEY dentro de t01preordenpedido, no repetir aquí:
-- CREATE INDEX idx_preorden_cliente_estado_fec ON t01preordenpedido (t20Cliente_Id_Cliente, Estado, Fec_Emision);

-- Rendimiento recomendado:
CREATE INDEX idx_t60_orden      ON t60DetOrdenPedido (t02OrdenPedido_Id_OrdenPedido);
CREATE INDEX idx_t61_pre_prod   ON t61detapreorden (t01PreOrdenPedido_Id_PreOrdenPedido, t18CatalogoProducto_Id_Producto);
