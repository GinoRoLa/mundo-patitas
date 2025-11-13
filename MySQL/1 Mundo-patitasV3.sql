use mundo_patitas3;
-- ==========================================================
-- Mundo Patitas - Script Definitivo (MySQL 8 / InnoDB / utf8mb4)
-- V3 FIXED: orden de creación, FKs coherentes, nombres consistentes
-- ==========================================================

DROP DATABASE IF EXISTS mundo_patitas3;
CREATE DATABASE mundo_patitas3
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_0900_ai_ci;
USE mundo_patitas3;

SET sql_mode = 'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- ==========================================================
-- 0) Catálogos sin dependencias directas
-- ==========================================================
CREATE TABLE t31CategoriaProducto (
  Id_Categoria INT NOT NULL,
  Descripcion VARCHAR(50) NOT NULL,
  PRIMARY KEY (Id_Categoria)
) ENGINE=InnoDB;

CREATE TABLE t34UnidadMedida (
  Id_UnidadMedida INT NOT NULL,
  Descripcion VARCHAR(30) NOT NULL,
  PRIMARY KEY (Id_UnidadMedida)
) ENGINE=InnoDB;

CREATE TABLE t37DetalleRequerimiento (
  Id_DetaRequerimiento INT NOT NULL AUTO_INCREMENT,
  Observacion VARCHAR(200),
  PRIMARY KEY (Id_DetaRequerimiento)
) ENGINE=InnoDB AUTO_INCREMENT=37001;

CREATE TABLE t28_Metodopago (
  Id_MetodoPago INT NOT NULL AUTO_INCREMENT,
  Descripcion VARCHAR(40) NOT NULL,
  PRIMARY KEY (Id_MetodoPago)
) ENGINE=InnoDB AUTO_INCREMENT=28001;

CREATE TABLE t30TipoBanco (
  Id_TipoBanco INT NOT NULL AUTO_INCREMENT,
  Descripcion VARCHAR(40) NOT NULL,
  PRIMARY KEY (Id_TipoBanco)
) ENGINE=InnoDB AUTO_INCREMENT=30001;

-- Zonas y Distritos (para que estén disponibles antes de t73/t71)
CREATE TABLE t76ZonaEnvio (
  Id_Zona INT NOT NULL,
  DescZona VARCHAR(100) NOT NULL,
  Estado VARCHAR(15) NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (Id_Zona)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE t77DistritoEnvio (
  Id_Distrito INT NOT NULL,
  Id_Zona     INT NOT NULL,
  DescNombre  VARCHAR(120) NOT NULL,
  MontoCosto  DECIMAL(10,2) NOT NULL DEFAULT 0.00 CHECK (MontoCosto >= 0),
  Estado      VARCHAR(15) NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (Id_Distrito),
  CONSTRAINT fk_t77_t76 FOREIGN KEY (Id_Zona)
    REFERENCES t76ZonaEnvio(Id_Zona)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- 1) Personas
-- ==========================================================
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
  PRIMARY KEY (Id_Cliente),
  UNIQUE KEY uq_t20_dni (DniCli)
) ENGINE=InnoDB AUTO_INCREMENT=60001;

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
  PRIMARY KEY (id_Trabajador),
  UNIQUE KEY uq_t16_dni (DNITrabajador)
) ENGINE=InnoDB AUTO_INCREMENT=50001;

CREATE TABLE t41LicenciaConductor (
  Id_Licencia INT NOT NULL AUTO_INCREMENT,
  id_Trabajador INT NOT NULL,
  Num_Licencia VARCHAR(20) NOT NULL,
  Categoria VARCHAR(10) NOT NULL,
  Fec_Emision DATE NOT NULL,
  Fec_Revalidacion DATE NOT NULL,
  Estado VARCHAR(15) NOT NULL DEFAULT 'Vigente',
  PRIMARY KEY (Id_Licencia),
  UNIQUE KEY uq_t41_num_licencia (Num_Licencia),
  KEY idx_t41_trabajador (id_Trabajador),
  KEY idx_t41_estado_revalidacion (Estado, Fec_Revalidacion),
  CONSTRAINT fk_t41_t16 FOREIGN KEY (id_Trabajador)
    REFERENCES t16CatalogoTrabajadores (id_Trabajador)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=41001;

-- ==========================================================
-- 2) Productos / Proveedores
-- ==========================================================
CREATE TABLE t18CatalogoProducto (
  Id_Producto INT NOT NULL AUTO_INCREMENT,
  NombreProducto VARCHAR(100) NOT NULL,
  Descripcion VARCHAR(200) NOT NULL,
  Marca VARCHAR(30) NOT NULL,
  PrecioUnitario DECIMAL(12,2) NOT NULL,
  StockActual INT NOT NULL,
  Estado VARCHAR(15) NOT NULL,
  Peso DECIMAL(8,4) NOT NULL COMMENT 'kg',
  Volumen DECIMAL(8,4) NOT NULL COMMENT 'litros',
  t31CategoriaProducto_Id_Categoria INT NOT NULL,
  t34UnidadMedida_Id_UnidadMedida INT NOT NULL,
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
  CONSTRAINT t18_chk_precio CHECK (PrecioUnitario >= 0),
  CONSTRAINT t18_chk_stock1 CHECK (StockActual >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=1000;

CREATE TABLE t58Producto (
  Id_Producto INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(60) NOT NULL,
  descripcion VARCHAR(200) NOT NULL,
  precio DECIMAL(12,2) NOT NULL CHECK (precio >= 0),
  PRIMARY KEY (Id_Producto)
) ENGINE=InnoDB AUTO_INCREMENT=58001;

CREATE TABLE t17CatalogoProveedor (
  Id_NumRuc VARCHAR(11) NOT NULL,
  des_RazonSocial VARCHAR(50) NOT NULL,
  DireccionProv VARCHAR(50) NOT NULL,
  Telefono VARCHAR(15) NOT NULL,
  Correo VARCHAR(100) NOT NULL,
  estado VARCHAR(15) NOT NULL,
  PRIMARY KEY (Id_NumRuc)
) ENGINE=InnoDB;

CREATE TABLE t13Stock (
  IdStock INT NOT NULL AUTO_INCREMENT,
  id_Producto INT NOT NULL,
  Cantidad INT NOT NULL,
  precioPromedio DECIMAL(12,2),
  Periodo VARCHAR(25) NOT NULL,      -- Ej: '01/09/2025 - 30/09/2025'
  PeriodoClave DATE NOT NULL,        -- Ej: '2025-09-01'
  Fecha DATE NOT NULL,
  Estado VARCHAR(15) NOT NULL,
  PRIMARY KEY (IdStock),
  KEY fk_t11_prod (id_Producto),
  CONSTRAINT fk_t11_prod FOREIGN KEY (id_Producto)
    REFERENCES t18CatalogoProducto (Id_Producto)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=1100;


CREATE TABLE t14RequerimientoCompra (
  Id_Requerimiento INT NOT NULL AUTO_INCREMENT,
  FechaRequerimiento DATE NOT NULL,
  Total DECIMAL(12,2) NOT NULL,
  PrecioPromedio DECIMAL(12,2) NOT NULL,
  Periodo VARCHAR(25) NOT NULL,
  PeriodoClave DATE NOT NULL,
  Estado VARCHAR(15) NOT NULL,
  PRIMARY KEY (Id_Requerimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci AUTO_INCREMENT=4500;

CREATE TABLE t15DetalleRequerimientoCompra (
  Id_Detalle INT NOT NULL AUTO_INCREMENT,
  Id_Requerimiento INT NOT NULL,
  Id_Producto INT NOT NULL,
  Cantidad INT NOT NULL,
  PrecioPromedio DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (Id_Detalle),
  KEY fk_t15_t14 (Id_Requerimiento),
  KEY fk_t15_prod (Id_Producto),
  CONSTRAINT fk_t15_t14 FOREIGN KEY (Id_Requerimiento)
    REFERENCES t14RequerimientoCompra (Id_Requerimiento)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_t15_prod FOREIGN KEY (Id_Producto)
    REFERENCES t18CatalogoProducto (Id_Producto)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT t15_chk_cantidad CHECK (Cantidad > 0),
  CONSTRAINT t15_chk_precio CHECK (PrecioPromedio >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=23450 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE t29DetalleStockProducto (
  Id_DetalleStockProducto INT NOT NULL AUTO_INCREMENT,
  t18CatalogoProducto_Id_Producto INT NOT NULL,
  StockMaximo INT NOT NULL,
  StockMinimo INT NOT NULL,
  FechaUltimaActualizacion DATE NOT NULL,
  Estado VARCHAR(15) NOT NULL,
  PRIMARY KEY (Id_DetalleStockProducto),
  KEY fk_t29_producto (t18CatalogoProducto_Id_Producto),
  CONSTRAINT fk_t29_producto FOREIGN KEY (t18CatalogoProducto_Id_Producto)
    REFERENCES t18CatalogoProducto (Id_Producto)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT t29_chk_stockmax CHECK (StockMaximo >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=5000;


CREATE TABLE t406PartidaPeriodo (
  Id_PartidaPeriodo INT NOT NULL AUTO_INCREMENT,
  CodigoPartida VARCHAR(15) NOT NULL,
  Descripcion VARCHAR(200) NOT NULL,
  Mes VARCHAR(15) NOT NULL,
  MontoPeriodo DECIMAL(12,2) NOT NULL,
  Estado VARCHAR(15) NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (Id_PartidaPeriodo),
  CONSTRAINT chk_montos_partida CHECK (MontoPeriodo >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=1000;

CREATE TABLE t407RequerimientoEvaluado (
  Id_ReqEvaluacion INT NOT NULL AUTO_INCREMENT,
  Id_Requerimiento INT NOT NULL,
  Id_PartidaPeriodo INT NOT NULL,
  FechaEvaluacion DATETIME NOT NULL,
  CriterioEvaluacion VARCHAR(50) NOT NULL,
  MontoSolicitado DECIMAL(12,2) NOT NULL,
  MontoAprobado DECIMAL(12,2) NOT NULL,
  /* Total DECIMAL(12,2) NOT NULL, */  -- Comentado con sintaxis correcta
  SaldoRestantePeriodo DECIMAL(12,2) NOT NULL,
  Observaciones TEXT,
  Estado VARCHAR(30) NOT NULL DEFAULT 'Aprobado',
  PRIMARY KEY (Id_ReqEvaluacion),
  KEY fk_t407_t14 (Id_Requerimiento),
  KEY fk_t407_t406 (Id_PartidaPeriodo),
  CONSTRAINT fk_t407_t14 FOREIGN KEY (Id_Requerimiento)
    REFERENCES t14RequerimientoCompra (Id_Requerimiento)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_t407_t406 FOREIGN KEY (Id_PartidaPeriodo)
    REFERENCES t406PartidaPeriodo (Id_PartidaPeriodo)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT chk_montos_eval CHECK (MontoSolicitado >= 0 AND MontoAprobado >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=2000;

CREATE TABLE t408DetalleReqEvaluado (
  Id_DetalleEvaluacion INT NOT NULL AUTO_INCREMENT,
  Id_ReqEvaluacion INT NOT NULL,
  Id_Producto INT NOT NULL,
  Cantidad INT NOT NULL,
  Precio DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (Id_DetalleEvaluacion),
  KEY fk_t408_eval (Id_ReqEvaluacion),
  KEY fk_t408_prod (Id_Producto),
  CONSTRAINT fk_t408_eval FOREIGN KEY (Id_ReqEvaluacion)
    REFERENCES t407RequerimientoEvaluado (Id_ReqEvaluacion)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_t408_prod FOREIGN KEY (Id_Producto)
    REFERENCES t18CatalogoProducto (Id_Producto)
    ON DELETE RESTRICT ON UPDATE RESTRICT,  
  CONSTRAINT chk_cantidad CHECK (Cantidad > 0),
  CONSTRAINT chk_precio CHECK (Precio >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=3000;

CREATE TABLE t409HistorialEvaluacion (
  Id_Historial INT NOT NULL AUTO_INCREMENT,
  Id_ReqEvaluacion INT NOT NULL,
  FechaCambio DATETIME NOT NULL,
  DetalleCambio TEXT NOT NULL,
  PRIMARY KEY (Id_Historial),
  KEY fk_t409_eval (Id_ReqEvaluacion),
  CONSTRAINT fk_t409_eval FOREIGN KEY (Id_ReqEvaluacion)
    REFERENCES t407RequerimientoEvaluado (Id_ReqEvaluacion)
    ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5000;

CREATE TABLE t410ConsumoPartida (
  Id_Consumo INT NOT NULL AUTO_INCREMENT,
  Id_PartidaPeriodo INT NOT NULL,
  Id_ReqEvaluacion INT NOT NULL,
  MontoConsumido DECIMAL(12,2) NOT NULL,
  FechaRegistro DATETIME NOT NULL,
  SaldoDespues DECIMAL(12,2) DEFAULT 0,
  PRIMARY KEY (Id_Consumo),
  KEY fk_t410_t406 (Id_PartidaPeriodo),
  KEY fk_t410_t407 (Id_ReqEvaluacion),
  CONSTRAINT fk_t410_t406 FOREIGN KEY (Id_PartidaPeriodo)
    REFERENCES t406PartidaPeriodo (Id_PartidaPeriodo)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_t410_t407 FOREIGN KEY (Id_ReqEvaluacion)
    REFERENCES t407RequerimientoEvaluado (Id_ReqEvaluacion)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT chk_consumo CHECK (MontoConsumido >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=8000;

-- ==========================================================
-- 3) Métodos de entrega / direcciones / zonas
-- ==========================================================
CREATE TABLE t27MetodoEntrega (
  Id_MetodoEntrega INT NOT NULL AUTO_INCREMENT,
  Descripcion VARCHAR(40) NOT NULL,
  Estado VARCHAR(20) NOT NULL,
  PRIMARY KEY (Id_MetodoEntrega)
) ENGINE=InnoDB AUTO_INCREMENT=9001;

-- Catálogo de direcciones del cliente
CREATE TABLE t70DireccionEnvioCliente (
  Id_DireccionEnvio INT AUTO_INCREMENT PRIMARY KEY,
  Id_Cliente        INT NOT NULL,
  NombreContacto    VARCHAR(120) NOT NULL,
  TelefonoContacto  VARCHAR(20)  NOT NULL,
  Direccion         VARCHAR(255) NOT NULL,
  Id_Distrito       INT NOT NULL,
  DniReceptor       VARCHAR(8)   NOT NULL,
  INDEX idx_t70_cliente  (Id_Cliente),
  INDEX idx_t70_distrito (Id_Distrito),
  CONSTRAINT fk_t70_cliente FOREIGN KEY (Id_Cliente)
    REFERENCES t20Cliente (Id_Cliente)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_t70_distrito FOREIGN KEY (Id_Distrito)
    REFERENCES t77DistritoEnvio (Id_Distrito)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Direcciones de origen (almacenes) - ya existe t77
CREATE TABLE t73DireccionAlmacen (
  Id_DireccionAlmacen INT NOT NULL AUTO_INCREMENT,
  NombreAlmacen       VARCHAR(120) NOT NULL,
  DireccionOrigen     VARCHAR(255) NOT NULL,
  Id_Distrito         INT NOT NULL,
  Estado              VARCHAR(20) NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (Id_DireccionAlmacen),
  KEY fk_t73_t77 (Id_Distrito),
  CONSTRAINT fk_t73_t77 FOREIGN KEY (Id_Distrito)
    REFERENCES t77DistritoEnvio(Id_Distrito)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Puente trabajadores ↔ almacenes
CREATE TABLE t94TrabajadoresAlmacenes (
  id_Trabajador INT NOT NULL,
  Id_DireccionAlmacen INT NOT NULL,
  PRIMARY KEY (id_Trabajador, Id_DireccionAlmacen),
  KEY fk_t94_t16 (id_Trabajador),
  KEY fk_t94_t73 (Id_DireccionAlmacen),
  CONSTRAINT fk_t94_t16 FOREIGN KEY (id_Trabajador)
    REFERENCES t16CatalogoTrabajadores(id_Trabajador)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t94_t73 FOREIGN KEY (Id_DireccionAlmacen)
    REFERENCES t73DireccionAlmacen(Id_DireccionAlmacen)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ==========================================================
-- 4) Ventas: Orden / Preorden / comprobantes / OSE
-- ==========================================================
CREATE TABLE t02OrdenPedido (
  Id_OrdenPedido INT NOT NULL AUTO_INCREMENT,
  Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Id_Cliente INT NOT NULL,
  Id_MetodoEntrega INT NULL,
  CostoEntrega DECIMAL(10,2) NOT NULL DEFAULT 0 CHECK (CostoEntrega >= 0),
  Descuento    DECIMAL(10,2) NOT NULL DEFAULT 0 CHECK (Descuento >= 0),
  Total        DECIMAL(12,2) NOT NULL DEFAULT 0 CHECK (Total >= 0),
  Peso_total        DECIMAL(12,4) NOT NULL,
  Volumen_total        DECIMAL(12,4) NOT NULL,
  Estado VARCHAR(15),
  PRIMARY KEY (Id_OrdenPedido),
  KEY fk_t02_cliente (Id_Cliente),
  KEY fk_t02_metodo (Id_MetodoEntrega),
  CONSTRAINT fk_t02_cliente FOREIGN KEY (Id_Cliente)
    REFERENCES t20Cliente (Id_Cliente)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t02_metodo FOREIGN KEY (Id_MetodoEntrega)
    REFERENCES t27MetodoEntrega (Id_MetodoEntrega)
    ON UPDATE RESTRICT ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10001;

CREATE TABLE t01PreOrdenPedido (
  Id_PreOrdenPedido INT NOT NULL AUTO_INCREMENT,
  t20Cliente_Id_Cliente INT NULL,
  Fec_Emision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Estado VARCHAR(15) NOT NULL DEFAULT 'Emitido',
  Total DECIMAL(10,2) DEFAULT 0.00,
  PRIMARY KEY (Id_PreOrdenPedido),
  KEY idx_preorden_cliente_estado_fec (t20Cliente_Id_Cliente, Estado, Fec_Emision),
  CONSTRAINT fk_t01_cliente FOREIGN KEY (t20Cliente_Id_Cliente)
    REFERENCES t20Cliente (Id_Cliente)
    ON UPDATE RESTRICT ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10;

CREATE TABLE t90PreOrden_OrdenPedido (
  Id                INT NOT NULL AUTO_INCREMENT,
  Id_OrdenPedido    INT NOT NULL,
  Id_PreOrdenPedido INT NOT NULL,
  Fec_Vinculo       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (Id),
  UNIQUE KEY uq_t90_par (Id_OrdenPedido, Id_PreOrdenPedido),
  UNIQUE KEY uq_t90_preorden_unica (Id_PreOrdenPedido),
  KEY fk_t90_orden (Id_OrdenPedido),
  KEY fk_t90_preorden (Id_PreOrdenPedido),
  CONSTRAINT fk_t90_orden FOREIGN KEY (Id_OrdenPedido)
    REFERENCES t02OrdenPedido (Id_OrdenPedido)
    ON UPDATE RESTRICT ON DELETE CASCADE,
  CONSTRAINT fk_t90_preorden FOREIGN KEY (Id_PreOrdenPedido)
    REFERENCES t01PreOrdenPedido (Id_PreOrdenPedido)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE t03ComprobantePago (
  Nro_ComproPago INT NOT NULL AUTO_INCREMENT,
  Tipo VARCHAR(20),
  Id_MetodoPago INT NULL,
  Id_TipoBanco  INT NULL,
  PRIMARY KEY (Nro_ComproPago),
  KEY fk_cmp_metodo (Id_MetodoPago),
  KEY fk_cmp_banco (Id_TipoBanco),
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
  Estado           VARCHAR(20),
  FecCreacion      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (Id_OSE),
  UNIQUE KEY uq_t59_orden (Id_OrdenPedido),
  KEY fk_t59_orden (Id_OrdenPedido),
  CONSTRAINT fk_t59_orden FOREIGN KEY (Id_OrdenPedido)
    REFERENCES t02OrdenPedido (Id_OrdenPedido)
    ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE t04Factura (
  Id_Factura INT NOT NULL AUTO_INCREMENT,
  Nro_ComproPago INT,
  PRIMARY KEY (Id_Factura),
  KEY fk_t04_cmp (Nro_ComproPago),
  CONSTRAINT fk_t04_cmp FOREIGN KEY (Nro_ComproPago)
    REFERENCES t03ComprobantePago (Nro_ComproPago)
    ON UPDATE RESTRICT ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=104001;

CREATE TABLE t05Boleta (
  Id_Boleta INT NOT NULL AUTO_INCREMENT,
  Nro_ComproPago INT,
  PRIMARY KEY (Id_Boleta),
  KEY fk_t05_cmp (Nro_ComproPago),
  CONSTRAINT fk_t05_cmp FOREIGN KEY (Nro_ComproPago)
    REFERENCES t03ComprobantePago (Nro_ComproPago)
    ON UPDATE RESTRICT ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=105001;

-- ==========================================================
-- 5) Snapshot dirección por orden y referencia a catálogo
-- ==========================================================
CREATE TABLE t71OrdenDirecEnvio (
  Id_OrdenDirecEnvio INT AUTO_INCREMENT PRIMARY KEY,
  Id_OrdenPedido     INT NOT NULL,
  NombreContactoSnap VARCHAR(120) NOT NULL,
  TelefonoSnap       VARCHAR(20)  NOT NULL,
  DireccionSnap      VARCHAR(255) NOT NULL,
  ReceptorDniSnap    VARCHAR(8)   NOT NULL,
  Id_Distrito        INT NULL,
  UNIQUE KEY uq_t71_orden (Id_OrdenPedido),
  KEY fk_t71_orden (Id_OrdenPedido),
  KEY fk_t71_distrito (Id_Distrito),
  CONSTRAINT fk_t71_orden FOREIGN KEY (Id_OrdenPedido)
    REFERENCES t02OrdenPedido (Id_OrdenPedido)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_t71_distrito FOREIGN KEY (Id_Distrito)
    REFERENCES t77DistritoEnvio (Id_Distrito)
    ON UPDATE RESTRICT ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE t92Ref_Snapshot_DirCatalogo (
  Id                  INT AUTO_INCREMENT PRIMARY KEY,
  Id_OrdenDirecEnvio  INT NOT NULL,
  Id_DireccionEnvio   INT NOT NULL,
  UNIQUE KEY uq_t92_snapshot (Id_OrdenDirecEnvio),
  KEY idx_t92_dir (Id_DireccionEnvio),
  CONSTRAINT fk_t92_t71 FOREIGN KEY (Id_OrdenDirecEnvio)
    REFERENCES t71OrdenDirecEnvio (Id_OrdenDirecEnvio)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_t92_t70 FOREIGN KEY (Id_DireccionEnvio)
    REFERENCES t70DireccionEnvioCliente (Id_DireccionEnvio)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- 6) Flota y asignaciones
-- ==========================================================
CREATE TABLE t78Vehiculo (
  Id_Vehiculo   INT PRIMARY KEY,
  Marca         VARCHAR(60) NOT NULL,
  Modelo        VARCHAR(60) NULL,
  Placa         VARCHAR(15) NOT NULL,
  Anio          SMALLINT NULL,
  Volumen       DECIMAL(12,4) NOT NULL DEFAULT 8.00,
  CapacidadPesoKg DECIMAL(12,4) NOT NULL DEFAULT 1100.00,
  Estado        VARCHAR(15) NOT NULL DEFAULT 'Disponible',
  UNIQUE KEY uq_t78_placa (Placa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE t79AsignacionRepartidorVehiculo (
  Id_AsignacionRepartidorVehiculo   INT AUTO_INCREMENT PRIMARY KEY,
  Id_Trabajador   INT NOT NULL,
  Id_Vehiculo     INT NOT NULL,
  Fecha_Inicio    DATE NOT NULL,
  Fecha_Fin       DATE NULL,
  Estado          VARCHAR(15) NOT NULL DEFAULT 'Activo',
  UNIQUE KEY uq_t79_trabajador (Id_Trabajador),
  UNIQUE KEY uq_t79_vehiculo   (Id_Vehiculo),
  KEY fk_t79_t16 (Id_Trabajador),
  KEY fk_t79_t78 (Id_Vehiculo),
  CONSTRAINT fk_t79_t16 FOREIGN KEY (Id_Trabajador)
    REFERENCES t16CatalogoTrabajadores(Id_Trabajador)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t79_t78 FOREIGN KEY (Id_Vehiculo)
    REFERENCES t78Vehiculo(Id_Vehiculo)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

DROP TABLE IF EXISTS t40OrdenAsignacionReparto;
CREATE TABLE t40OrdenAsignacionReparto (
  Id_OrdenAsignacion              INT NOT NULL AUTO_INCREMENT,
  Id_AsignacionRepartidorVehiculo INT NOT NULL,
  FechaProgramada                 DATE NOT NULL,                  -- Fecha prevista de entrega
  FecCreacion                     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,  -- Fecha y hora de creación
  Estado                          VARCHAR(15) NOT NULL DEFAULT 'Pendiente',
  CONSTRAINT t40OrdenAsignacionReparto_pk PRIMARY KEY (Id_OrdenAsignacion),
  CONSTRAINT fk_t40_t79 FOREIGN KEY (Id_AsignacionRepartidorVehiculo)
    REFERENCES t79AsignacionRepartidorVehiculo(Id_AsignacionRepartidorVehiculo)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=80000;

CREATE TABLE t80disponibilidadvehiculo (
  Id_Disponibilidad INT NOT NULL AUTO_INCREMENT,
  Id_AsignacionRepartidorVehiculo INT NOT NULL,
  Id_OrdenAsignacion INT NULL,   -- Nueva columna FK a t40OrdenAsignacionReparto
  Fecha DATE NOT NULL,
  HoraInicio TIME NOT NULL DEFAULT '09:00:00',
  HoraFin TIME NOT NULL DEFAULT '18:00:00',
  Estado VARCHAR(15) NOT NULL DEFAULT 'Ocupado',
  PRIMARY KEY (Id_Disponibilidad),
  KEY fk_t80_t79 (Id_AsignacionRepartidorVehiculo),
  KEY fk_t80_t40 (Id_OrdenAsignacion),
  CONSTRAINT fk_t80_t79 FOREIGN KEY (Id_AsignacionRepartidorVehiculo)
    REFERENCES t79asignacionrepartidorvehiculo (Id_AsignacionRepartidorVehiculo)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_t80_t40 FOREIGN KEY (Id_OrdenAsignacion)
    REFERENCES t40OrdenAsignacionReparto (Id_OrdenAsignacion)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



-- ==========================================================
-- 7) Guías de remisión
-- ==========================================================
CREATE TABLE t72GuiaRemision (
  Id_Guia              INT NOT NULL AUTO_INCREMENT,
  Serie                VARCHAR(3) NOT NULL DEFAULT '001',
  Numero               INT NOT NULL,
  NumeroTexto          VARCHAR(20) GENERATED ALWAYS AS (CONCAT(Serie,'-',LPAD(Numero,6,'0'))),
  Fec_Emision          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Estado               VARCHAR(20) NOT NULL DEFAULT 'Emitida',
  RemitenteRUC         VARCHAR(11)  NOT NULL,
  RemitenteRazonSocial VARCHAR(120) NOT NULL,
  DestinatarioNombre   VARCHAR(120) NOT NULL,
  DniReceptor          VARCHAR(8)   NOT NULL,
  DireccionDestino     VARCHAR(100) NOT NULL,
  DistritoDestino      VARCHAR(120) NOT NULL,
  Id_DireccionAlmacen  INT NOT NULL,
  Id_AsignacionRepartidorVehiculo INT NOT NULL,
  ModalidadTransporte  VARCHAR(20) NOT NULL DEFAULT 'PROPIO',
  Marca                VARCHAR(10)  NULL,
  Placa                VARCHAR(10)  NULL,
  Conductor            VARCHAR(30) NULL,
  Licencia             VARCHAR(20)  NULL,
  Motivo               VARCHAR(30)  NOT NULL DEFAULT 'Venta',
  FechaInicioTraslado  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (Id_Guia),
  UNIQUE KEY uq_t72_numero (Serie, Numero),
  KEY fk_t72_origen (Id_DireccionAlmacen),
  KEY fk_t72_asignacion (Id_AsignacionRepartidorVehiculo),
  CONSTRAINT fk_t72_origen FOREIGN KEY (Id_DireccionAlmacen)
    REFERENCES t73DireccionAlmacen (Id_DireccionAlmacen)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t72_asignacion FOREIGN KEY (Id_AsignacionRepartidorVehiculo)
    REFERENCES t79AsignacionRepartidorVehiculo (Id_AsignacionRepartidorVehiculo)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_t72_estado_emision ON t72GuiaRemision (Estado, Fec_Emision);

CREATE TABLE t74DetalleGuia (
  Id_DetalleGuia  INT NOT NULL AUTO_INCREMENT,
  Id_Guia         INT NOT NULL,
  Id_Producto     INT NOT NULL,
  Descripcion     VARCHAR(200) NOT NULL,
  Unidad          VARCHAR(30)  NOT NULL,
  Cantidad        INT NOT NULL,
  PRIMARY KEY (Id_DetalleGuia),
  KEY idx_t74_guia (Id_Guia),
  KEY idx_t74_prod (Id_Producto),
  CONSTRAINT fk_t74_guia FOREIGN KEY (Id_Guia)
    REFERENCES t72GuiaRemision (Id_Guia)
    ON UPDATE RESTRICT ON DELETE CASCADE,
  CONSTRAINT fk_t74_prod FOREIGN KEY (Id_Producto)
    REFERENCES t18CatalogoProducto (Id_Producto)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE t93Guia_OrdenPedido (
  Id_Guia        INT NOT NULL,
  Id_OrdenPedido INT NOT NULL,
  PRIMARY KEY (Id_Guia, Id_OrdenPedido),
  KEY fk_t93_guia (Id_Guia),
  KEY fk_t93_orden (Id_OrdenPedido),
  CONSTRAINT fk_t93_guia FOREIGN KEY (Id_Guia)
    REFERENCES t72GuiaRemision (Id_Guia)
    ON UPDATE RESTRICT ON DELETE CASCADE,
  CONSTRAINT fk_t93_orden FOREIGN KEY (Id_OrdenPedido)
    REFERENCES t02OrdenPedido (Id_OrdenPedido)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- (Opcional: queda si deseas histórico con ID propio)
CREATE TABLE t75GuiaOrden (
  Id INT NOT NULL AUTO_INCREMENT,
  Id_Guia INT NOT NULL,
  Id_OrdenPedido INT NOT NULL,
  PRIMARY KEY (Id),
  UNIQUE KEY uq_t75 (Id_Guia, Id_OrdenPedido),
  KEY fk_t75_g (Id_Guia),
  KEY fk_t75_o (Id_OrdenPedido),
  CONSTRAINT fk_t75_g FOREIGN KEY (Id_Guia)
    REFERENCES t72GuiaRemision (Id_Guia)
    ON UPDATE RESTRICT ON DELETE CASCADE,
  CONSTRAINT fk_t75_o FOREIGN KEY (Id_OrdenPedido)
    REFERENCES t02OrdenPedido (Id_OrdenPedido)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ==========================================================
-- 8) Compras / Movimientos de inventario
-- ==========================================================
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

/* === Cabecera de Cotización (mínima) ==================================== */
DROP TABLE IF EXISTS t86Cotizacion;
CREATE TABLE t86Cotizacion (
  Id_Cotizacion     INT AUTO_INCREMENT PRIMARY KEY,
  Id_ReqEvaluacion  INT  NOT NULL,       -- 👈 ya no Id_Requerimiento
  RUC_Proveedor     VARCHAR(11) NOT NULL,
  NroCotizacionProv VARCHAR(50) NULL,
  FechaEmision      DATE        NOT NULL,
  FechaEntrega     DATE    NOT NULL,
  Observaciones     VARCHAR(400) NULL,
  SubTotal          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  IGV               DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  Total             DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  Estado            VARCHAR(11)   NOT NULL DEFAULT 'Recibida',
  CONSTRAINT fk_t86_eval FOREIGN KEY (Id_ReqEvaluacion)
    REFERENCES t407RequerimientoEvaluado (Id_ReqEvaluacion)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t86_prov FOREIGN KEY (RUC_Proveedor)
    REFERENCES t17CatalogoProveedor (Id_NumRuc)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
  INDEX ix_t86_eval_estado (Id_ReqEvaluacion, Estado),
  INDEX ix_t86_prov        (RUC_Proveedor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


/* === Detalle de Cotización (mínima) ===================================== */
DROP TABLE IF EXISTS t87DetalleCotizacion;
CREATE TABLE t87DetalleCotizacion (
  Id_DetalleCot      INT AUTO_INCREMENT PRIMARY KEY,
  Id_Cotizacion      INT NOT NULL,
  Id_Producto        INT NOT NULL,
  Descripcion        VARCHAR(100) NOT NULL,
  CantidadOfertada   INT NOT NULL CHECK (CantidadOfertada >= 0),
  PrecioUnitario     DECIMAL(12,4) NOT NULL CHECK (PrecioUnitario >= 0),
  TotalLinea         DECIMAL(12,2) GENERATED ALWAYS AS (CantidadOfertada * PrecioUnitario) STORED,
  CONSTRAINT fk_t87_cot FOREIGN KEY (Id_Cotizacion)
    REFERENCES t86Cotizacion (Id_Cotizacion)
      ON UPDATE RESTRICT ON DELETE CASCADE,
  CONSTRAINT fk_t87_prod FOREIGN KEY (Id_Producto)
    REFERENCES t18CatalogoProducto (Id_Producto)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
  INDEX ix_t87_prod     (Id_Producto),
  INDEX ix_t87_cot_prod (Id_Cotizacion, Id_Producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS t88ArchivoCotizacion;
CREATE TABLE t88ArchivoCotizacion (
  Id_Archivo       INT AUTO_INCREMENT PRIMARY KEY,
  Id_ReqEvaluacion INT NOT NULL,          -- 👈 aquí también
  RUC_Proveedor    VARCHAR(11) NULL,
  FileName         VARCHAR(255) NOT NULL,
  FileSize         BIGINT NOT NULL,
  FileHash         VARCHAR(64) NOT NULL,
  LastModified     DATETIME NOT NULL,
  ImportStatus     ENUM('pending','imported','error','ignored') NOT NULL DEFAULT 'imported',
  Id_Cotizacion    INT NULL,
  ErrorMsg         VARCHAR(255) NULL,
  FechaRegistro    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_filehash (FileHash),
  KEY ix_t88_eval   (Id_ReqEvaluacion),
  KEY ix_t88_prov   (RUC_Proveedor),
  KEY ix_t88_status (ImportStatus),
  CONSTRAINT fk_t88_eval FOREIGN KEY (Id_ReqEvaluacion)
    REFERENCES t407RequerimientoEvaluado (Id_ReqEvaluacion)
      ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


/* === Orden de Compra - Cabecera (mínimo necesario) ====================== */
DROP TABLE IF EXISTS t06OrdenCompra;
CREATE TABLE t06OrdenCompra (
  Id_OrdenCompra    INT NOT NULL AUTO_INCREMENT,
  Serie CHAR(4) NOT NULL DEFAULT '2025',
  NumeroOrdenCompra VARCHAR(20) NOT NULL,
  Fec_Emision       DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  RUC_Proveedor     VARCHAR(11) NOT NULL,
  RazonSocial       VARCHAR(150) NULL,
  Id_ReqEvaluacion  INT  NULL,
  Id_Cotizacion INT NOT NULL,
  Reprogramacion    INT NOT NULL DEFAULT 0,
  Moneda            CHAR(3)     NOT NULL DEFAULT 'PEN',
  PorcentajeIGV     DECIMAL(5,2) NOT NULL DEFAULT 18.00,
  SubTotal          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  Impuesto          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  MontoTotal        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  Estado            VARCHAR(20)  NOT NULL DEFAULT 'Emitida',
  PRIMARY KEY (Id_OrdenCompra),
  KEY ix_t06_prov (RUC_Proveedor),
  KEY ix_t06_eval (Id_ReqEvaluacion),
  CONSTRAINT fk_t06_prov FOREIGN KEY (RUC_Proveedor)
    REFERENCES t17CatalogoProveedor (Id_NumRuc)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t06_t86 FOREIGN KEY (Id_Cotizacion)
    REFERENCES t86Cotizacion (Id_Cotizacion)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t06_eval FOREIGN KEY (Id_ReqEvaluacion)
    REFERENCES t407RequerimientoEvaluado (Id_ReqEvaluacion)
      ON UPDATE RESTRICT ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


/* === Orden de Compra - Detalle (mínimo necesario) ======================= */
DROP TABLE IF EXISTS t07DetalleOrdenCompra;
CREATE TABLE t07DetalleOrdenCompra (
  Id_Detalle      INT NOT NULL AUTO_INCREMENT,
  Id_OrdenCompra  INT NOT NULL,
  Id_Producto     INT NOT NULL,
  Descripcion  VARCHAR(200) NOT NULL,
  Unidad       VARCHAR(15)  NULL,
  Cantidad        INT NOT NULL CHECK (Cantidad > 0),
  PrecioUnitario  DECIMAL(12,2) NOT NULL CHECK (PrecioUnitario >= 0),
  SubTotal        DECIMAL(12,2) GENERATED ALWAYS AS (Cantidad * PrecioUnitario) STORED,
  PRIMARY KEY (Id_Detalle),
  KEY ix_t07_oc   (Id_OrdenCompra),
  KEY ix_t07_prod (Id_Producto),
  CONSTRAINT fk_t07_oc FOREIGN KEY (Id_OrdenCompra)
    REFERENCES t06OrdenCompra (Id_OrdenCompra)
      ON UPDATE RESTRICT ON DELETE CASCADE,
  CONSTRAINT fk_t07_prod FOREIGN KEY (Id_Producto)
    REFERENCES t18CatalogoProducto (Id_Producto)
      ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



CREATE TABLE t11OrdenSalida (
  Id_ordenSalida INT NOT NULL AUTO_INCREMENT,
  Fec_Transaccion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  t02OrdenPedido_Id_OrdenPedido INT NOT NULL,
  Tipo_Movimiento VARCHAR(10) NOT NULL,
  PRIMARY KEY (Id_ordenSalida),
  KEY idx_ordenPedido (t02OrdenPedido_Id_OrdenPedido),
  KEY idx_fecha (Fec_Transaccion),
  CONSTRAINT fk_t11_t02 FOREIGN KEY (t02OrdenPedido_Id_OrdenPedido)
    REFERENCES t02OrdenPedido (Id_OrdenPedido)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3001;

CREATE TABLE t10Kardex (
  Id_Kardex INT NOT NULL AUTO_INCREMENT,
  TipoTransaccion VARCHAR(15) NOT NULL,
  id_Producto INT NOT NULL,
  precio DECIMAL(12,2),
  Cantidad INT NOT NULL CHECK (Cantidad >= 0),
  Fec_Transaccion DATE NOT NULL,
  PRIMARY KEY (Id_Kardex),
  KEY fk_t10_prod (id_Producto),
  CONSTRAINT fk_t10_prod FOREIGN KEY (id_Producto)
    REFERENCES t18CatalogoProducto (Id_Producto)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5000;

CREATE TABLE t19OrdenIngresoCompra (
  Id_OrdenIngresoCompra INT NOT NULL AUTO_INCREMENT,
  t06OrdenCompra_Id_OrdenCompra INT NOT NULL,
  id_Trabajador INT NOT NULL,
  Fec_Emision DATE NOT NULL,
  ListaProductos LONGTEXT NOT NULL,
  Estado VARCHAR(15) NOT NULL,
  PRIMARY KEY (Id_OrdenIngresoCompra),
  KEY fk_t19_t06 (t06OrdenCompra_Id_OrdenCompra),
  KEY fk_t19_trab (id_Trabajador),
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
  PRIMARY KEY (Id_OrdenIngresoAlmacen),
  KEY fk_t09_t19 (t19OrdenIngresoCompra_Id_OrdenIngresoCompra),
  KEY fk_t09_t10 (t10Kardex_Id_Kardex),
  KEY fk_t09_trab (id_Trabajador),
  CONSTRAINT fk_t09_t19 FOREIGN KEY (t19OrdenIngresoCompra_Id_OrdenIngresoCompra)
    REFERENCES t19OrdenIngresoCompra (Id_OrdenIngresoCompra)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t09_t10 FOREIGN KEY (t10Kardex_Id_Kardex)
    REFERENCES t10Kardex (Id_Kardex)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t09_trab FOREIGN KEY (id_Trabajador)
    REFERENCES t16CatalogoTrabajadores (id_Trabajador)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=20081;

-- Reporte/Informe de incidencias (con FK válidas)
CREATE TABLE t08ReporteIncidencia (
  Id_ReporteIncidencia INT NOT NULL AUTO_INCREMENT,
  id_Trabajador INT NOT NULL,
  fec_Informe DATE NOT NULL,
  Lista_Productos LONGTEXT NOT NULL,
  DetaIncidencia LONGTEXT NOT NULL,
  Estado VARCHAR(15) NOT NULL,
  PRIMARY KEY (Id_ReporteIncidencia),
  KEY fk_t08_trab (id_Trabajador),
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
  PRIMARY KEY (Id_InfomeIncidencia),
  KEY fk_t12_t08 (t08ReporteIncidencia_Id_ReporteIncidencia),
  KEY fk_t12_trab (id_Trabajador),
  CONSTRAINT fk_t12_t08 FOREIGN KEY (t08ReporteIncidencia_Id_ReporteIncidencia)
    REFERENCES t08ReporteIncidencia (Id_ReporteIncidencia)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t12_trab FOREIGN KEY (id_Trabajador)
    REFERENCES t16CatalogoTrabajadores (id_Trabajador)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=12001;

-- ==========================================================
-- 9) Notas / Cierre / Cobertura
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
  PRIMARY KEY (id_ReporteCierre),
  KEY fk_t26_t02 (t02OrdenPedido_Id_OrdenPedido),
  KEY fk_t26_trab (id_Trabajador),
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
  PRIMARY KEY (Id_NotaCredito),
  KEY fk_t21_cliente (t20Cliente_Id_Cliente),
  KEY fk_t21_cmp (Nro_comprobantePago),
  KEY fk_t21_orden (Id_OrdenPedido),
  KEY fk_t21_trab (id_Trabajador),
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
  PRIMARY KEY (id_NotaFaltante),
  KEY fk_t23_t26 (t26ReporteCierreCaja_id_ReporteCierre),
  KEY fk_t23_trab (id_Trabajador),
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
  PRIMARY KEY (id_NotaSobrante),
  KEY fk_t24_t26 (t26ReporteCierreCaja_id_ReporteCierre),
  KEY fk_t24_trab (id_Trabajador),
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
  PRIMARY KEY (id_NotaDescargo),
  KEY fk_t22_t23 (t23NotaFaltanteCaja_id_NotaFaltante),
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
  PRIMARY KEY (id_Cobertura),
  KEY fk_t25_trab (id_Trabajador),
  CONSTRAINT fk_t25_trab FOREIGN KEY (id_Trabajador)
    REFERENCES t16CatalogoTrabajadores (id_Trabajador)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=45001;

-- ==========================================================
-- 10) Devoluciones
-- ==========================================================
CREATE TABLE t52MotivoDevolucion (
  Id_Motivo INT NOT NULL AUTO_INCREMENT,
  Descripcion VARCHAR(100) NOT NULL,
  PRIMARY KEY (Id_Motivo)
) ENGINE=InnoDB AUTO_INCREMENT=52001;

CREATE TABLE t53TipoDevolucion (
  Id_tipo INT NOT NULL AUTO_INCREMENT,
  Descripcion VARCHAR(50) NOT NULL,
  PRIMARY KEY (Id_tipo)
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
  PRIMARY KEY (Id_Devolucion),
  KEY fk_t50_cliente (t20Cliente_Id_Cliente),
  KEY fk_t50_nc (t21NotaCredito_Id_NotaCredito),
  KEY fk_t50_tipo (Id_Tipo),
  KEY fk_t50_motivo (Id_Motivo),
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
  PRIMARY KEY (Id_Evaluacion),
  KEY fk_t54_t50 (t50OrdenDevolucion_Id_Devolucion),
  CONSTRAINT fk_t54_t50 FOREIGN KEY (t50OrdenDevolucion_Id_Devolucion)
    REFERENCES t50OrdenDevolucion (Id_Devolucion)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=74001;

CREATE TABLE t55EntregaProductoDevuelto (
  Id_ProductoDevuelto INT NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (Id_ProductoDevuelto)
) ENGINE=InnoDB AUTO_INCREMENT=75001;

CREATE TABLE t56ReporteDevolucion (
  Id_Reporte INT NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (Id_Reporte)
) ENGINE=InnoDB AUTO_INCREMENT=76001;

CREATE TABLE t57DetalleOrden (
  Id_DetalleOrden INT NOT NULL AUTO_INCREMENT,
  id_orden INT NOT NULL,
  sku_producto INT NOT NULL,
  cantidad INT NOT NULL CHECK (cantidad >= 0),
  subtotal DECIMAL(12,2) NOT NULL CHECK (subtotal >= 0),
  t50OrdenDevolucion_Id_Devolucion INT NOT NULL,
  PRIMARY KEY (Id_DetalleOrden),
  KEY fk_t57_t50 (t50OrdenDevolucion_Id_Devolucion),
  KEY fk_t57_t02 (id_orden),
  KEY fk_t57_t18 (sku_producto),
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
-- 11) Detalles de PreOrden / Orden
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
    REFERENCES t01PreOrdenPedido (Id_PreOrdenPedido)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t61_t18 FOREIGN KEY (t18CatalogoProducto_Id_Producto)
    REFERENCES t18CatalogoProducto (Id_Producto)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT t61_chk_cant CHECK (Cantidad >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=1;

CREATE TABLE t60DetOrdenPedido (
  Id_DetOrdenPedido INT NOT NULL AUTO_INCREMENT,
  t18CatalogoProducto_Id_Producto INT NOT NULL,
  t02OrdenPedido_Id_OrdenPedido INT NOT NULL,
  Id_Cliente INT NOT NULL,
  Cantidad INT NOT NULL CHECK (Cantidad >= 0),
  PRIMARY KEY (Id_DetOrdenPedido),
  KEY fk_t60_t02 (t02OrdenPedido_Id_OrdenPedido),
  KEY fk_t60_t18 (t18CatalogoProducto_Id_Producto),
  KEY fk_t60_cli (Id_Cliente),
  CONSTRAINT fk_t60_t02 FOREIGN KEY (t02OrdenPedido_Id_OrdenPedido)
    REFERENCES t02OrdenPedido (Id_OrdenPedido)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t60_t18 FOREIGN KEY (t18CatalogoProducto_Id_Producto)
    REFERENCES t18CatalogoProducto (Id_Producto)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t60_cli FOREIGN KEY (Id_Cliente)
    REFERENCES t20Cliente (Id_Cliente)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=1;

-- ==========================================================
-- 12) Asignación de reparto / hoja de ruta / salida / evidencias
-- ==========================================================
DROP TABLE IF EXISTS t401DetalleAsignacionReparto;
CREATE TABLE t401DetalleAsignacionReparto (
  Id_DetalleAsignacion INT NOT NULL AUTO_INCREMENT,
  Id_OrdenAsignacion   INT NOT NULL,  -- FK a t40OrdenAsignacionReparto
  Id_OSE               INT NOT NULL,  -- FK a t59OrdenServicioEntrega
  PRIMARY KEY (Id_DetalleAsignacion),
  KEY fk_t401_t40 (Id_OrdenAsignacion),
  KEY fk_t401_t59 (Id_OSE),
  CONSTRAINT fk_t401_t40 FOREIGN KEY (Id_OrdenAsignacion)
    REFERENCES t40OrdenAsignacionReparto (Id_OrdenAsignacion)
    ON UPDATE RESTRICT ON DELETE CASCADE,
  CONSTRAINT fk_t401_t59 FOREIGN KEY (Id_OSE)
    REFERENCES t59OrdenServicioEntrega (Id_OSE)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=81001;

DROP TABLE IF EXISTS t402DetalleRuta;
CREATE TABLE t402DetalleRuta (
  Id_DetalleRuta    INT NOT NULL AUTO_INCREMENT,
  Id_OrdenAsignacion INT NOT NULL,       -- FK a la orden de asignación de reparto
  Id_Distrito        INT NOT NULL,       -- FK al distrito de entrega
  DireccionSnap      VARCHAR(255) NOT NULL, -- Dirección exacta del pedido
  Orden              INT NOT NULL,       -- Secuencia en la ruta
  RutaPolyline       TEXT NULL,          -- Codificación polilínea de Google Maps
  PRIMARY KEY (Id_DetalleRuta),
  CONSTRAINT fk_t402_ordenAsignacion FOREIGN KEY (Id_OrdenAsignacion)
    REFERENCES t40OrdenAsignacionReparto(Id_OrdenAsignacion)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_t402_distrito FOREIGN KEY (Id_Distrito)
    REFERENCES t77DistritoEnvio(Id_Distrito)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- FIX: nombres de constraints y columnas consistentes
CREATE TABLE t403OrdenSalidaEntrega (
  Id_ordenSalida INT NOT NULL AUTO_INCREMENT,
  t02OrdenPedido_Id_OrdenPedido INT NOT NULL,
  Fec_Transaccion DATE NOT NULL,
  Tipo_Movimiento VARCHAR(10) NOT NULL,
  id_Trabajador INT NOT NULL,
  PRIMARY KEY (Id_ordenSalida),
  KEY fk_t403_t02 (t02OrdenPedido_Id_OrdenPedido),
  KEY fk_t403_t16 (id_Trabajador),
  CONSTRAINT fk_t403_t02 FOREIGN KEY (t02OrdenPedido_Id_OrdenPedido)
    REFERENCES t02OrdenPedido (Id_OrdenPedido)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_t403_t16 FOREIGN KEY (id_Trabajador)
    REFERENCES t16CatalogoTrabajadores (id_Trabajador)
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
  t11OrdenSalida_Id_ordenSalida INT NULL,
  PRIMARY KEY (IDEvidenciaEntrega),
  KEY fk_t404_t11 (t11OrdenSalida_Id_ordenSalida),
  CONSTRAINT fk_t404_t11 FOREIGN KEY (t11OrdenSalida_Id_ordenSalida)
    REFERENCES t11OrdenSalida (Id_ordenSalida)
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
  t11OrdenSalida_Id_ordenSalida INT NULL,
  PRIMARY KEY (IDIncidenciaEntrega),
  KEY fk_t405_t11 (t11OrdenSalida_Id_ordenSalida),
  CONSTRAINT fk_t405_t11 FOREIGN KEY (t11OrdenSalida_Id_ordenSalida)
    REFERENCES t11OrdenSalida (Id_ordenSalida)
    ON UPDATE RESTRICT ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=85001;

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

-- ==========================================================
-- 13) Índices adicionales
-- ==========================================================
CREATE INDEX idx_t58_nombre ON t58Producto (nombre);
CREATE INDEX idx_t02_estado ON t02OrdenPedido (Estado);
CREATE INDEX idx_t26_estado ON t26ReporteCierreCaja (estado, Fec_emision);
CREATE INDEX idx_t21_cmp ON t21NotaCredito (Nro_comprobantePago);
CREATE INDEX idx_t09_fec ON t09OrdenIngresoAlmacen (fec_ingreso);
CREATE INDEX idx_t60_orden ON t60DetOrdenPedido (t02OrdenPedido_Id_OrdenPedido);
CREATE INDEX idx_t61_pre_prod ON t61detapreorden (t01PreOrdenPedido_Id_PreOrdenPedido, t18CatalogoProducto_Id_Producto);