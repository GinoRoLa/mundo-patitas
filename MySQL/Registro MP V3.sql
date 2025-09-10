USE mundo_patitas2;

-- ==========================================================
-- 1) Trabajadores (incluye el DNI 33333333 que usa la vista)
-- ==========================================================
INSERT INTO t16CatalogoTrabajadores
  (id_Trabajador, DNITrabajador, des_apepatTrabajador, des_apematTrabajador, des_nombreTrabajador, num_telefono, direccion, email, cargo, estado)
VALUES
  (50001, '33333333', 'Pérez',  'Lopez',  'María',  '999111222', 'Av. Central 123',    'maria.perez@mp.com',  'Cajero',  'Activo'),
  (50002, '44444444', 'García', 'Torres', 'Carlos', '999222333', 'Calle Falsa 742',    'carlos.garcia@mp.com','Vendedor','Activo'),
  (50003, '55555555', 'Ramos',  'Vera',   'Lucía',  '999333444', 'Jr. Las Flores 321', 'lucia.ramos@mp.com',  'Almacén', 'Activo'),
  (50004,'22222222','Flores','Diaz','Carla','988222333','Mz B Lt 2','carla.flores@demo.com','Responsable de Pedidos','Activo');

-- ==========================================================
-- 2) Catálogos de producto
-- ==========================================================
INSERT INTO t31CategoriaProducto
  (Id_Categoria, Descripcion)
VALUES
  (21001, 'Alimentos'),
  (21002, 'Accesorios'),
  (21003, 'Higiene');

INSERT INTO t34UnidadMedida
  (Id_UnidadMedida, Descripcion)
VALUES
  (24001, 'Und'),
  (24002, 'Kg'),
  (24003, 'L');

INSERT INTO t37DetalleRequerimiento
  (Id_DetaRequerimiento, Observacion)
VALUES
  (37001, 'Reposición estándar'),
  (37002, 'Campaña'),
  (37003, 'Urgente');

-- ==========================================================
-- 3) Productos (precios alineados con totales de preórdenes)
-- ==========================================================
-- Precios: 1001=25.50, 1002=27.90, 1003=22.50, 1004=18.00, 1005=12.00, 1006=15.00
INSERT INTO t18catalogoproducto
  (Id_Producto, NombreProducto, Descripcion, Marca, PrecioUnitario,
   StockActual, StockMinimo, StockMaximo, Estado,
   t31CategoriaProducto_Id_Categoria, t34UnidadMedida_Id_UnidadMedida)
VALUES
  (1001, 'Alimento seco premium 2Kg',  'Para perros adultos',  'DogPlus', 25.50, 150, 10, 500, 'Activo', 21001, 24002), -- Kg
  (1002, 'Arnés talla M',              'Nylon reforzado',      'PetGear', 27.90,  80,  5, 200, 'Activo', 21002, 24001), -- Und
  (1003, 'Juguete cuerda',             'Algodón trenzado',     'HappyPet',22.50, 120, 10, 400, 'Activo', 21002, 24001), -- Und
  (1004, 'Shampoo neutro 500ml',       'pH balanceado',        'CleanPet',18.00,  90,  5, 300, 'Activo', 21003, 24003), -- L
  (1005, 'Snacks dentales x7',         'Reduce sarro',         'ChewCare',12.00, 200, 10, 600, 'Activo', 21001, 24001), -- Und
  (1006, 'Collar reflectivo',          'Ajustable',            'NightPaw',15.00, 110,  5, 300, 'Activo', 21002, 24001); -- Und

-- ==========================================================
-- 4) Métodos de entrega (activos para el combo del front)
-- ==========================================================
INSERT INTO t27MetodoEntrega
  (Id_MetodoEntrega, Descripcion, Costo, Estado)
VALUES
  (9001, 'Recojo en tienda',        0.00, 'Activo'),
  (9002, 'Delivery - estándar',     8.00, 'Activo'),
  (9003, 'Delivery - express',    15.00, 'Inactivo');

-- ==========================================================
-- 5) Clientes (DNI de ejemplo 12345678)
-- ==========================================================
INSERT INTO t20Cliente
  (Id_Cliente, DniCli, des_apepatCliente, des_apematCliente, des_nombreCliente, num_telefonoCliente, email_cliente, direccionCliente, estado)
VALUES
  (60001, '12345678', 'Quispe', 'Huamán', 'Ana',  '987654321', 'ana.quispe@correo.com',  'Av. Los Olivos 456', 'Activo'),
  (60002, '87654321', 'Flores', 'Rojas',  'Pedro','981234567', 'pedro.flores@correo.com','Jr. San Martín 789', 'Activo'),
  (60003, '11223344', 'Soto',   'Méndez', 'Luisa','980112233', 'luisa.soto@correo.com',  'Calle Norte 101',    'Inactivo');

-- ==========================================================
-- 6) Preórdenes (cliente 60001) - primero SIN vínculo a orden
--     10 y 11: vigentes; 12: no vigente; 13: procesada (luego se vincula)
-- ==========================================================
INSERT INTO t01preordenpedido
  (Id_PreOrdenPedido, t02OrdenPedido_Id_OrdenPedido, t20Cliente_Id_Cliente, Fec_Emision, Estado, Total)
VALUES
  (10, NULL, 60001, NOW(), 'Emitido',   73.50),
  (11, NULL, 60001, NOW(), 'Emitido',   42.00),
  (12, NULL, 60001, NOW(),  'Emitido',   18.00),
  (13, NULL, 60001, NOW(), 'Procesado', 27.90);

-- ==========================================================
-- 7) Detalle de preórdenes (alineado con los totales)
-- ==========================================================
INSERT INTO t61detapreorden
  (Id_DetaPreOrden, t18CatalogoProducto_Id_Producto, t01PreOrdenPedido_Id_PreOrdenPedido, Cantidad)
VALUES
  (61019, 1001, 10, 2),
  (61020, 1003, 10, 1),
  (61021, 1005, 11, 1),
  (61022, 1006, 11, 2),
  (61023, 1004, 12, 1),
  (61024, 1002, 13, 1);

-- ==========================================================
-- 8) Orden de pedido (consolidación de 10 y 11)
--     Subtotal = 73.50 + 42.00 = 115.50
--     Descuento = 5.00 (regla HU002 de ejemplo)
--     Costo entrega (9002) = 8.00
--     Total = 118.50
-- ==========================================================
INSERT INTO t02OrdenPedido
  (Id_OrdenPedido, Fecha, Id_Cliente, Id_MetodoEntrega, CostoEntrega, Descuento, Total, Estado)
VALUES
  (10001, NOW(), 60001, 9002, 8.00, 5.00, 118.50, 'Generada');

-- Vincular la preorden 13 a la orden 10001 ahora que la orden existe
UPDATE t01preordenpedido
   SET t02OrdenPedido_Id_OrdenPedido = 10001
 WHERE Id_PreOrdenPedido = 13;

-- ==========================================================
-- 9) Detalle de la Orden (resultado de la consolidación de 10 y 11)
-- ==========================================================
INSERT INTO t60DetOrdenPedido
  (Id_DetOrdenPedido, t18CatalogoProducto_Id_Producto, t02OrdenPedido_Id_OrdenPedido, Id_Cliente, Cantidad)
VALUES
  (60001, 1001, 10001, 60001, 2),
  (60002, 1003, 10001, 60001, 1),
  (60003, 1005, 10001, 60001, 1),
  (60004, 1006, 10001, 60001, 2);

-- ==========================================================
-- 10) (Opcional) Métodos de pago / Bancos, por si luego los usas
-- ==========================================================
INSERT INTO t28_Metodopago
  (Id_MetodoPago, Descripcion)
VALUES
  (28001, 'Efectivo'),
  (28002, 'Tarjeta');

INSERT INTO t30TipoBanco
  (Id_TipoBanco, Descripcion)
VALUES
  (30001, 'BCP'),
  (30002, 'BBVA');
