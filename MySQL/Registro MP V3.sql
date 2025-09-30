USE mundo_patitas2;

-- ==========================================================
-- 1) Almacenes y Trabajadores
-- ==========================================================
INSERT INTO t73DireccionAlmacen
  (Id_DireccionAlmacen, NombreAlmacen, DireccionOrigen, DistritoOrigen, Estado)
VALUES
  (1, 'Almacén Central', 'Av. Principal 100', 'CERCADO DE LIMA', 'Activo'),
  (2, 'Almacén Norte',   'Calle Norte 234',   'LOS OLIVOS',      'Activo'),
  (3, 'Almacén Sur',     'Jr. Sur 456',       'VILLA EL SALVADOR','Activo');

INSERT INTO t16CatalogoTrabajadores
  (id_Trabajador, DNITrabajador, des_apepatTrabajador, des_apematTrabajador, des_nombreTrabajador, num_telefono, direccion, email, cargo, estado, Id_DireccionAlmacen)
VALUES
  (50001, '33333333', 'Pérez',  'Lopez',  'María',  '999111222', 'Av. Central 123',    'maria.perez@mp.com',  'Cajero',  'Activo', 1),
  (50002, '44444444', 'García', 'Torres', 'Carlos', '999222333', 'Calle Falsa 742',    'carlos.garcia@mp.com','Vendedor','Activo', 1),
  (50003, '55555555', 'Ramos',  'Vera',   'Lucía',  '999333444', 'Jr. Las Flores 321', 'lucia.ramos@mp.com',  'Almacén', 'Activo', 1),
  (50004, '22222222', 'Flores', 'Diaz',   'Carla',  '988222333', 'Mz B Lt 2',          'carla.flores@demo.com','Responsable de Pedidos','Activo', 1),
  (50005, '66666666', 'López',  'Mora',   'Ana',    '999444555', 'Av. Secundaria 456', 'ana.lopez@mp.com',   'Responsable de Almacén', 'Activo', 1),
  (50006, '77777777', 'Martín', 'Salas',  'José',   '999555666', 'Av. Terciaria 789',  'jose.martin@mp.com',  'Responsable de Almacén', 'Activo', 2);

-- ==========================================================
-- 2) Catálogos de producto base
-- ==========================================================
INSERT INTO t31CategoriaProducto (Id_Categoria, Descripcion) VALUES
  (21001, 'Alimentos'),
  (21002, 'Accesorios'),
  (21003, 'Higiene');

INSERT INTO t34UnidadMedida (Id_UnidadMedida, Descripcion) VALUES
  (24001, 'Und'),
  (24002, 'Kg'),
  (24003, 'L');

INSERT INTO t37DetalleRequerimiento (Id_DetaRequerimiento, Observacion) VALUES
  (37001, 'Reposición estándar'),
  (37002, 'Campaña'),
  (37003, 'Urgente');

-- ==========================================================
-- 3) Productos (usar nombre correcto de tabla: t18CatalogoProducto)
--    Los 6 primeros con Id explícito para que concuerden con tus totales
-- ==========================================================
INSERT INTO t18CatalogoProducto
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
-- 4) Métodos de entrega (sin columna Costo)
-- ==========================================================
INSERT INTO t27MetodoEntrega (Id_MetodoEntrega, Descripcion, Estado) VALUES
  (9001, 'Recojo en tienda',     'Activo'),
  (9002, 'Delivery - estándar',  'Activo');

-- ==========================================================
-- 5) Clientes y sus direcciones
-- ==========================================================
INSERT INTO t20Cliente
  (Id_Cliente, DniCli, des_apepatCliente, des_apematCliente, des_nombreCliente, num_telefonoCliente, email_cliente, direccionCliente, estado)
VALUES
  (60001, '12345678', 'Quispe', 'Huamán', 'Ana',  '987654321', 'ana.quispe@correo.com',  'Av. Los Olivos 456', 'Activo'),
  (60002, '87654321', 'Flores', 'Rojas',  'Pedro','981234567', 'pedro.flores@correo.com','Jr. San Martín 789', 'Activo'),
  (60003, '11223344', 'Soto',   'Méndez', 'Luisa','980112233', 'luisa.soto@correo.com',  'Calle Norte 101',    'Inactivo'),
  (60004, '33445566', 'Ramírez','Poma',   'Jorge','986543210', 'jorge.ramirez@correo.com','Av. Arequipa 1234', 'Activo'),
  (60005, '99887766', 'García', 'Luna',   'María','989112233', 'maria.garcia@correo.com','Jr. Las Magnolias 350','Activo'),
  (60006, '55667788', 'Torres', 'Campos', 'Carlos','981223344','carlos.torres@correo.com','Calle Los Cedros 220','Activo'),
  (60007, '44556677', 'Pérez',  'Saldaña','Rocío','984556677','rocio.perez@correo.com','Av. El Ejército 765','Activo'),
  (60008, '22334455', 'López',  'Vega',   'Hugo','982334455','hugo.lopez@correo.com','Mz. B Lote 12 Urb. Progreso','Activo'),
  (60009, '77889911', 'Mendoza','Ríos',   'Karla','983778899','karla.mendoza@correo.com','Psje. Los Olivos 114','Activo'),
  (60010, '66778899', 'Rojas',  'Cárdenas','Diego','985667788','diego.rojas@correo.com','Av. Universitaria 1020','Activo'),
  (60011, '12121212', 'Salazar','Quispe', 'Elena','986121212','elena.salazar@correo.com','Jr. Puno 456','Activo'),
  (60012, '34343434', 'Valdez', 'Núñez',  'Marco','987343434','marco.valdez@correo.com','Calle Lima 890','Activo'),
  (60013, '56565656', 'Castillo','Zapata','Patricia','989565656','patricia.castillo@correo.com','Av. Brasil 1500','Activo'),
  (60014, '78787878', 'Aguilar','Sánchez','Bruno','981787878','bruno.aguilar@correo.com','Av. La Marina 700','Activo'),
  (60015, '90909090', 'Chávez', 'Ibarra', 'Verónica','983909090','veronica.chavez@correo.com','Jr. Ancash 210','Activo');

INSERT INTO t70DireccionEnvioCliente
  (Id_Cliente, NombreContacto, TelefonoContacto, Direccion, Distrito, DniReceptor)
VALUES
  (60001, 'Ana Quispe Huamán',    '987654321', 'Av. Los Olivos 456 Dpto. 302', 'LOS OLIVOS',              '12345678'),
  (60001, 'Ana Quispe Huamán',    '987654321', 'Jr. Los Sauces 120',           'LOS OLIVOS',              '12345678'),
  (60002, 'Pedro Flores Rojas',   '981234567', 'Jr. San Martín 789 Int. 201',  'SAN MARTÍN DE PORRES',    '87654321'),
  (60004, 'Jorge Ramírez Poma',   '986543210', 'Av. Arequipa 1234',            'LINCE',                   '33445566'),
  (60005, 'María García Luna',    '989112233', 'Jr. Las Magnolias 350',        'SAN BORJA',               '99887766'),
  (60006, 'Carlos Torres Campos', '981223344', 'Calle Los Cedros 220',         'SANTIAGO DE SURCO',       '55667788'),
  (60007, 'Rocío Pérez Saldaña',  '984556677', 'Av. El Ejército 765',          'JESÚS MARÍA',             '44556677'),
  (60008, 'Hugo López Vega',      '982334455', 'Mz. B Lote 12 Urb. Progreso',  'COMAS',                   '22334455'),
  (60009, 'Karla Mendoza Ríos',   '983778899', 'Psje. Los Olivos 114',         'INDEPENDENCIA',           '77889911'),
  (60010, 'Diego Rojas Cárdenas', '985667788', 'Av. Universitaria 1020',       'SAN MIGUEL',              '66778899'),
  (60011, 'Elena Salazar Quispe', '986121212', 'Jr. Puno 456',                 'CERCADO DE LIMA',         '12121212'),
  (60012, 'Marco Valdez Núñez',   '987343434', 'Calle Lima 890',               'BREÑA',                   '34343434'),
  (60013, 'Patricia Castillo Zapata','989565656','Av. Brasil 1500',            'JESÚS MARÍA',             '56565656'),
  (60014, 'Bruno Aguilar Sánchez','981787878', 'Av. La Marina 700',            'PUEBLO LIBRE',            '78787878'),
  (60015, 'Verónica Chávez Ibarra','983909090','Jr. Ancash 210',               'CERCADO DE LIMA',         '90909090');

-- ==========================================================
-- 6) Distritos de envío (usar columnas del nuevo t77)
-- ==========================================================
INSERT INTO t77DistritoEnvio (DescNombre, MontoCosto, Estado) VALUES
('LOS OLIVOS',              15.00, 'Activo'),
('SAN MARTÍN DE PORRES',    16.00, 'Activo'),
('LINCE',                   13.00, 'Activo'),
('SAN BORJA',               14.50, 'Activo'),
('SANTIAGO DE SURCO',       15.50, 'Activo'),
('COMAS',                   18.00, 'Activo'),
('INDEPENDENCIA',           17.00, 'Activo'),
('CERCADO DE LIMA',         12.00, 'Activo'),
('BREÑA',                   13.50, 'Activo');

-- ==========================================================
-- 7) Preórdenes (cliente 60001) y sus detalles
-- ==========================================================
INSERT INTO t01PreOrdenPedido
  (Id_PreOrdenPedido, t20Cliente_Id_Cliente, Fec_Emision, Estado, Total)
VALUES
  (10, 60001, NOW(), 'Emitido',   73.50),
  (11, 60001, NOW(), 'Emitido',   42.00),
  (12, 60001, NOW(), 'Emitido',   18.00),
  (13, 60001, NOW(), 'Procesado', 27.90);

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
--    Subtotal = 73.50 + 42.00 = 115.50
--    Descuento = 5.00
--    Costo entrega (snapshot) = 8.00
--    Total = 118.50
-- ==========================================================
INSERT INTO t02OrdenPedido
  (Id_OrdenPedido, Fecha, Id_Cliente, Id_MetodoEntrega, CostoEntrega, Descuento, Total, Estado)
VALUES
  (10001, NOW(), 60001, 9002, 8.00, 5.00, 118.50, 'Generada');

-- Vincular preórdenes 10 y 11 a la orden 10001 (trazabilidad)
INSERT INTO t90PreOrden_OrdenPedido (Id_OrdenPedido, Id_PreOrdenPedido)
VALUES (10001, 10), (10001, 11);


-- ==========================================================
-- 9) Detalle de la Orden
-- ==========================================================
INSERT INTO t60DetOrdenPedido
  (Id_DetOrdenPedido, t18CatalogoProducto_Id_Producto, t02OrdenPedido_Id_OrdenPedido, Id_Cliente, Cantidad)
VALUES
  (60001, 1001, 10001, 60001, 2),
  (60002, 1003, 10001, 60001, 1),
  (60003, 1005, 10001, 60001, 1),
  (60004, 1006, 10001, 60001, 2);

-- ==========================================================
-- 10) Métodos de pago / Bancos
-- ==========================================================
INSERT INTO t28_Metodopago (Id_MetodoPago, Descripcion) VALUES
  (28001, 'Efectivo'),
  (28002, 'Tarjeta');

INSERT INTO t30TipoBanco (Id_TipoBanco, Descripcion) VALUES
  (30001, 'BCP'),
  (30002, 'BBVA');

-- ==========================================================
-- 11) Más categorías (usar tabla con nombre correcto y SIN colisionar)
-- ==========================================================
INSERT INTO t31CategoriaProducto (Id_Categoria, Descripcion) VALUES
(24000, 'Alimento Perro'), (24001, 'Alimento Gato'), (24002, 'Alimento Pez'),
(24003, 'Alimento Ave'), (24004, 'Snacks'), (24005, 'Jaulas/Casas'),
(24006, 'Camas'), (24007, 'Arneses/Correas'), (24008, 'Juguetes'),
(24009, 'Acuarios/Accesorios'), (24010, 'Arena'), (24011, 'Higiene'),
(24012, 'Transportadoras'), (24013, 'Adiestramiento'), (24014, 'Vitaminas/Suplementos'),
(24015, 'Rascadores'), (24016, 'Peceras Ornamentos'), (24017, 'Accesorios Roedores'),
(24018, 'Pelotas'), (24019, 'Collares');

-- ==========================================================
-- 12) Lote extra de productos (usar t18CatalogoProducto)
--     (idéntico a tu data, sólo corrigiendo nombre de tabla)
-- ==========================================================
INSERT INTO t18CatalogoProducto
(NombreProducto, Descripcion, Marca, PrecioUnitario, StockActual, StockMinimo, StockMaximo, Estado, t31CategoriaProducto_Id_Categoria, t34UnidadMedida_Id_UnidadMedida)
VALUES
-- Alimentos para perros
('Alimento Seco Perro Adulto', 'Croquetas sabor res para perro adulto', 'Purina', 850.00, 50, 10, 100, 'ACTIVO', 24000, 24002),
('Alimento Seco Perro Adulto Premium', 'Croquetas premium para perro adulto raza grande', 'Pedigree', 920.00, 40, 8, 90, 'ACTIVO', 24000, 24002),
('Alimento Cachorro', 'Croquetas especiales con DHA para cachorros', 'Royal Canin', 950.00, 30, 8, 70, 'ACTIVO', 24000, 24002),
('Alimento Perro Senior', 'Croquetas para perros mayores con glucosamina', 'Eukanuba', 880.00, 35, 8, 80, 'ACTIVO', 24000, 24002),
('Alimento Perro Hipoalergénico', 'Croquetas sin cereales para piel sensible', 'Orijen', 1350.00, 20, 5, 50, 'ACTIVO', 24000, 24002),

-- Alimentos para gatos
('Alimento Gato Adulto', 'Croquetas sabor salmón para gatos adultos', 'Whiskas', 460.00, 80, 15, 140, 'ACTIVO', 24001, 24002),
('Alimento Gato Sterilizado', 'Croquetas para gatos esterilizados control peso', 'Hill''s', 870.00, 40, 6, 90, 'ACTIVO', 24001, 24002),
('Alimento Gato Senior', 'Alimento con antioxidantes para gatos mayores', 'Royal Canin', 1020.00, 35, 7, 90, 'ACTIVO', 24001, 24002),

-- Alimentos para peces
('Alimento Escamas Peces', 'Alimento en escamas para peces tropicales', 'Tetra', 120.00, 80, 10, 150, 'ACTIVO', 24002, 24001),
('Alimento Gránulos Peces', 'Alimento granulado para peces cíclidos', 'Sera', 160.00, 70, 12, 130, 'ACTIVO', 24002, 24001),

-- Alimentos para aves
('Alimento Canario', 'Mezcla nutritiva para canarios', 'Kaytee', 240.00, 50, 10, 90, 'ACTIVO', 24003, 24001),

-- Snacks
('Snacks Huesitos Pollo', 'Snacks en huesitos sabor pollo para perro', 'Pedigree', 120.00, 50, 10, 100, 'ACTIVO', 24004, 24001),
('Snack Dental', 'Barritas dentales para perros medianos', 'Greenies', 180.00, 40, 7, 70, 'ACTIVO', 24004, 24001),

-- Jaulas y casas
('Casa Plástica Perro Mediana', 'Casa de plástico ventilada tamaño mediano', 'Petmate', 1200.00, 20, 3, 40, 'ACTIVO', 24005, 24001),
('Casa Grande Madera', 'Casa grande para perro hecha de madera tratada', 'Dogloo', 1800.00, 14, 3, 30, 'ACTIVO', 24005, 24001),

-- Camas
('Cama Acolchada Mediana', 'Cama circular acolchada mediana', 'Petmate', 550.00, 25, 5, 50, 'ACTIVO', 24006, 24001),
('Cama Ortopédica Grande', 'Cama con espuma memory tamaño grande', 'Ferribiella', 1150.00, 20, 5, 40, 'ACTIVO', 24006, 24001),

-- Arneses y correas
('Correa Cuero Trenzado', 'Correa de cuero trenzado 1.5m', 'Ferribiella', 450.00, 20, 4, 45, 'ACTIVO', 24007, 24001),
('Arnés Ajustable Mediano', 'Arnés acolchado para paseos', 'PetSafe', 350.00, 42, 8, 80, 'ACTIVO', 24007, 24001),

-- Juguetes
('Juguete Pelota Goma', 'Pelota de goma resistente para perros', 'Kong', 180.00, 90, 15, 150, 'ACTIVO', 24008, 24001),
('Juguete Ratón Plush', 'Ratón de tela con catnip para gatos', 'Trixie', 100.00, 85, 18, 170, 'ACTIVO', 24008, 24001),

-- Acuarios y accesorios
('Acuario 40 Litros', 'Acuario de vidrio 40l con tapa plástica', 'Aquarium Systems', 1350.00, 15, 3, 25, 'ACTIVO', 24009, 24003),
('Filtro Interno Pecera', 'Filtro interno 200l/h para pecera', 'Hikari', 480.00, 25, 6, 50, 'ACTIVO', 24009, 24001),

-- Arena para gatos
('Arena Absorbente 10kg', 'Arena absorbente sin aroma, saco 10kg', 'Cat''s Best', 240.00, 70, 15, 120, 'ACTIVO', 24010, 24002),
('Arena Aglomerante 10kg', 'Arena aglomerante con aroma lavanda', 'Fresh Step', 300.00, 60, 12, 100, 'ACTIVO', 24010, 24002),

-- Higiene y cuidado
('Shampoo Perro Pelo Corto', 'Shampoo para perros pelo corto', 'PetClean', 180.00, 40, 8, 70, 'ACTIVO', 24011, 24001),
('Spray Antipulgas', 'Spray antipulgas para perros', 'Hartz', 210.00, 33, 7, 65, 'ACTIVO', 24011, 24001),

-- Transportadoras
('Transportadora Pequeña', 'Caja transportadora plástica pequeña', 'Petmate', 390.00, 18, 4, 35, 'ACTIVO', 24012, 24001),
('Transportadora Grande', 'Transportadora con puerta metálica', 'Savic', 750.00, 12, 3, 30, 'ACTIVO', 24012, 24001),

-- Adiestramiento
('Silbato Entrenamiento', 'Silbato para entrenamiento canino', 'PetSafe', 150.00, 50, 10, 100, 'ACTIVO', 24013, 24001),

-- Vitaminas y suplementos
('Suplemento Omega 3', 'Suplemento para piel y pelo', 'Vet''s Best', 350.00, 25, 5, 60, 'ACTIVO', 24014, 24001),

-- Rascadores para gato
('Rascador Torre Deluxe', 'Torre con niveles para rascar y jugar', 'Trixie', 1850.00, 9, 2, 20, 'ACTIVO', 24015, 24001),

-- Peceras ornamentos
('Ornamento Castillo', 'Decoración castillo para pecera', 'Aquarium Systems', 180.00, 20, 5, 40, 'ACTIVO', 24016, 24001),

-- Accesorios para roedores
('Heno Conejo 2kg', 'Heno natural para conejos', 'Supreme', 220.00, 35, 8, 70, 'ACTIVO', 24017, 24002),

-- Pelotas para perros
('Pelota Tenis', 'Pelota de tenis reforzada para perros', 'Kong', 130.00, 45, 10, 90, 'ACTIVO', 24018, 24001),

-- Collares para perros
('Collar Ajustable Nylon', 'Collar nylon ajustable para perros', 'PetSafe', 120.00, 60, 10, 90, 'ACTIVO', 24019, 24001),

-- Más (variaciones)
('Alimento Seco Perro Adulto', 'Croquetas sabor res para perro adulto', 'Hill''s', 870.00, 40, 8, 90, 'ACTIVO', 24000, 24002),
('Alimento Seco Perro Adulto', 'Croquetas sabor res para perro adulto', 'Acana', 890.00, 45, 10, 90, 'ACTIVO', 24000, 24002),
('Alimento Gato Adulto', 'Croquetas sabor salmón para gato adulto', 'Purina ONE', 900.00, 30, 8, 60, 'ACTIVO', 24001, 24002),
('Juguete Pelota Goma', 'Pelota goma resistente', 'Petmate', 160.00, 40, 10, 90, 'ACTIVO', 24008, 24001),
('Correa Cuero Trenzado', 'Correa cuero trenzado 1.5m', 'Royal Paw', 470.00, 15, 5, 30, 'ACTIVO', 24007, 24001),
('Cama Ortopédica Grande', 'Cama con espuma memory', 'Royal Paw', 1200.00, 22, 5, 40, 'ACTIVO', 24006, 24001),
('Casa Plástica Perro Mediana', 'Casa plástica ventilada', 'Kong', 1250.00, 16, 4, 35, 'ACTIVO', 24005, 24001),
('Snacks Huesitos Pollo', 'Snacks sabor pollo', 'Purina', 130.00, 60, 10, 100, 'ACTIVO', 24004, 24001),
('Arena Absorbente 10kg', 'Arena sin aroma', 'Kitty Friend', 270.00, 55, 12, 90, 'ACTIVO', 24010, 24002);

INSERT INTO t18CatalogoProducto
(NombreProducto, Descripcion, Marca, PrecioUnitario, StockActual, StockMinimo, StockMaximo, Estado, t31CategoriaProducto_Id_Categoria, t34UnidadMedida_Id_UnidadMedida)
VALUES
('Alimento Perro Light', 'Croquetas bajas en grasa para control de peso', 'Purina', 890.00, 45, 10, 90, 'ACTIVO', 24000, 24002),
('Alimento Perro Light', 'Croquetas bajas en grasa razas pequeñas', 'Hill''s', 970.00, 42, 8, 85, 'ACTIVO', 24000, 24002),
('Alimento Perro Senior', 'Comida senior con glucosamina', 'Royal Canin', 1010.00, 32, 7, 75, 'ACTIVO', 24000, 24002),
('Alimento Perro Hipoalergénico', 'Libre de gluten y cereales', 'Acana', 1280.00, 26, 6, 60, 'ACTIVO', 24000, 24002),
('Alimento Gato Indoor', 'Para gatos de interior bajo en calorías', 'Purina ONE', 890.00, 38, 8, 80, 'ACTIVO', 24001, 24002),

('Alimento Gato Indoor', 'Control bolas de pelo', 'Royal Canin', 1020.00, 36, 7, 80, 'ACTIVO', 24001, 24002),
('Alimento Gato Senior', 'Con antioxidantes', 'Hill''s', 980.00, 27, 5, 55, 'ACTIVO', 24001, 24002),
('Alimento Gato Senior', 'Croquetas para gatos mayores', 'Eukanuba', 940.00, 29, 6, 60, 'ACTIVO', 24001, 24002),

('Arnés Ajustable Mediano', 'Arnés acolchado confort', 'PetSafe', 350.00, 42, 8, 80, 'ACTIVO', 24007, 24001),
('Arnés Ajustable Mediano', 'Estilo deportivo', 'Kong', 385.00, 33, 7, 70, 'ACTIVO', 24007, 24001),
('Arnés Ajustable Grande', 'Con reflectantes', 'Flexi', 420.00, 28, 5, 60, 'ACTIVO', 24007, 24001),
('Correa Cuero', 'Cuero trenzado 1.5m', 'Ferribiella', 450.00, 20, 4, 45, 'ACTIVO', 24007, 24001),
('Correa Cuero', 'Piel natural reforzada', 'Royal Paw', 480.00, 19, 3, 40, 'ACTIVO', 24007, 24001),

('Frisbee Goma', 'Disco volador goma flexible', 'Kong', 200.00, 55, 12, 100, 'ACTIVO', 24008, 24001),
('Frisbee Goma', 'Seguro para boca perro', 'Chuckit!', 220.00, 58, 10, 110, 'ACTIVO', 24008, 24001),
('Cuerda Algodón', 'Cuerda para morder y jalar', 'Petmate', 140.00, 60, 14, 120, 'ACTIVO', 24008, 24001),
('Cuerda Algodón', 'Algodón trenzado 50cm', 'Nylabone', 160.00, 65, 12, 115, 'ACTIVO', 24008, 24001),

('Cama Ortopédica', 'Colchón memory grande', 'Royal Paw', 1100.00, 22, 4, 40, 'ACTIVO', 24006, 24001),
('Cama Ortopédica', 'Viscoelástica cama grande', 'Ferribiella', 1200.00, 20, 3, 35, 'ACTIVO', 24006, 24001),
('Cama Exterior', 'Elevada malla ventilada', 'PetSafe', 750.00, 25, 6, 55, 'ACTIVO', 24006, 24001),

('Cueva Gato', 'Cueva acolchada cerrada', 'Trixie', 520.00, 26, 5, 50, 'ACTIVO', 24005, 24001),
('Cueva Gato', 'Cama tipo iglú', 'Savic', 550.00, 28, 6, 55, 'ACTIVO', 24005, 24001),
('Casa Árbol Gato', 'Casa árbol niveles múltiples', 'Ferribiella', 1450.00, 12, 3, 25, 'ACTIVO', 24005, 24001),
('Casa Árbol Gato', 'Torre rascador con cama', 'Trixie', 1480.00, 10, 2, 22, 'ACTIVO', 24005, 24001),

('Arena Antipolvo', 'Arena antibacteriana control olor', 'Fresh Step', 310.00, 62, 12, 110, 'ACTIVO', 24010, 24002),
('Arena Antipolvo', 'Aglomerante sin polvo 9kg', 'Cat''s Best', 330.00, 60, 13, 115, 'ACTIVO', 24010, 24002),

('Acuario 200 litros', 'Acuario panorámico con gabinete', 'Fluval', 5250.00, 5, 1, 12, 'ACTIVO', 24009, 24003),
('Acuario 200 litros', 'Kit completo filtro externo', 'Tetra', 5100.00, 6, 1, 15, 'ACTIVO', 24009, 24003),
('Filtro Interno', 'Filtro sumergible 250l/h', 'Hikari', 480.00, 25, 6, 50, 'ACTIVO', 24009, 24001),

('Alimento Betta', 'Comida flotante especial bettas', 'Hikari', 95.00, 70, 15, 130, 'ACTIVO', 24002, 24001),
('Alimento Betta', 'Comida granulado bettas color', 'Sera', 100.00, 72, 15, 135, 'ACTIVO', 24002, 24001),

('Jaula Periquitos', 'Jaula mediana blanca', 'Kaytee', 620.00, 16, 3, 30, 'ACTIVO', 24005, 24001),
('Jaula Loro', 'Jaula acero inoxidable robusta', 'Savic', 2400.00, 8, 2, 18, 'ACTIVO', 24005, 24001),

('Rascador Torre Deluxe', 'Torre niveles y escondite', 'Trixie', 1850.00, 9, 2, 20, 'ACTIVO', 24015, 24001),
('Rascador Torre Deluxe', 'Rascador madera premium', 'Ferribiella', 1920.00, 7, 2, 15, 'ACTIVO', 24015, 24001),

('Acondicionador Perro', 'Acondicionador suavizante pelo', 'Hartz', 250.00, 28, 5, 55, 'ACTIVO', 24011, 24001),
('Spray Antipulgas', 'Spray antipulgas proteger', 'PetClean', 210.00, 31, 6, 60, 'ACTIVO', 24011, 24001),

('Transportadora Mediana', 'Transportadora 55cm plástico', 'Savic', 580.00, 20, 4, 40, 'ACTIVO', 24012, 24001),
('Transportadora Tela', 'Bolso transportador tela ventilado', 'Petmate', 450.00, 24, 5, 45, 'ACTIVO', 24012, 24001),

('Snack Natural Pollo', 'Snacks naturales deshidratados pollo', 'Orijen', 320.00, 40, 8, 70, 'ACTIVO', 24004, 24001),
('Snack Natural Carne', 'Snacks deshidratados carne vacuna', 'Acana', 330.00, 42, 9, 75, 'ACTIVO', 24004, 24001),

('Heno Conejo 4kg', 'Heno premium alfalfa', 'Vitakraft', 350.00, 20, 5, 40, 'ACTIVO', 24017, 24002),
('Alimento Cobayo', 'Pellets nutrientes para cobayos', 'Supreme', 280.00, 25, 5, 45, 'ACTIVO', 24017, 24002),

('Cortauñas Mascota', 'Cortauñas acero inoxidable', 'PetClean', 120.00, 30, 6, 60, 'ACTIVO', 24011, 24001),
('Cepillo Pelo Largo', 'Cepillo metálico doble cara', 'Kong', 160.00, 26, 5, 50, 'ACTIVO', 24011, 24001),

('Ornamento Castillo', 'Decoración castillo pecera', 'Aquarium Systems', 180.00, 20, 5, 40, 'ACTIVO', 24016, 24001),
('Ornamento Planta', 'Planta artificial acuario', 'Tetra', 95.00, 25, 6, 50, 'ACTIVO', 24016, 24001),

('Pelota Caucho', 'Pelota resistente de caucho para perros', 'Kong', 140.00, 40, 9, 85, 'ACTIVO', 24018, 24001),
('Collar Reflectante', 'Collar reflectante con luz LED', 'PetSafe', 135.00, 55, 10, 95, 'ACTIVO', 24019, 24001),

-- Repeticiones para completar volumen
('Alimento Perro Light', 'Croquetas bajas en grasa sabor pollo', 'Purina', 880.00, 42, 8, 80, 'ACTIVO', 24000, 24002),
('Alimento Perro Senior', 'Croquetas especiales para perros mayores', 'Royal Canin', 940.00, 36, 7, 75, 'ACTIVO', 24000, 24002),
('Alimento Gato Gourmet', 'Croquetas con salmón', 'Whiskas', 1180.00, 28, 6, 60, 'ACTIVO', 24001, 24002),
('Snack Perro Natural', 'Snack pollo sano', 'Greenies', 330.00, 40, 9, 80, 'ACTIVO', 24004, 24001),
('Cama Premium', 'Cama ortopédica con funda lavable', 'Ferribiella', 1380.00, 16, 4, 40, 'ACTIVO', 24006, 24001),
('Casa Perro Quinta', 'Casa grande resistente', 'Dogloo', 2300.00, 9, 2, 20, 'ACTIVO', 24005, 24001),
('Correa Retráctil 7m', 'Correa reflectante', 'Flexi', 560.00, 38, 7, 85, 'ACTIVO', 24007, 24001),
('Juguete Pelota Flotante', 'Pelota azul flotante', 'Chuckit!', 220.00, 50, 10, 90, 'ACTIVO', 24008, 24001),
('Acuario 80 litros', 'Kit completo', 'Fluval', 2850.00, 10, 3, 30, 'ACTIVO', 24009, 24003),
('Arena Silica', 'Arena premium sílica', 'Fresh Step', 360.00, 40, 9, 80, 'ACTIVO', 24010, 24002),
('Shampoo Natural', 'Con aloe vera', 'Vet''s Best', 280.00, 28, 6, 65, 'ACTIVO', 24011, 24001),
('Transportadora Plástica Mediana', 'Con ventilación doble', 'Savic', 640.00, 22, 5, 45, 'ACTIVO', 24012, 24001),
('Silbato Entrenamiento', 'Silbato profesional', 'PetSafe', 165.00, 50, 9, 100, 'ACTIVO', 24013, 24001),
('Suplemento Vitaminas', 'Con Omega 3', 'Vet''s Best', 380.00, 25, 5, 60, 'ACTIVO', 24014, 24001),
('Rascador Grande', 'Multi niveles y hamaca', 'Trixie', 1950.00, 7, 2, 18, 'ACTIVO', 24015, 24001),
('Ornamento Coral', 'Coral sintético', 'Aquarium Systems', 190.00, 20, 5, 40, 'ACTIVO', 24016, 24001),
('Heno Premium', 'Alfalfa para roedores', 'Vitakraft', 360.00, 21, 5, 45, 'ACTIVO', 24017, 24002),
('Pelota Caucho', 'Pelota resistente grande', 'Kong', 145.00, 38, 8, 85, 'ACTIVO', 24018, 24001),
('Collar Reflectante', 'Collar con reflectores LED', 'PetSafe', 140.00, 52, 10, 90, 'ACTIVO', 24019, 24001);
