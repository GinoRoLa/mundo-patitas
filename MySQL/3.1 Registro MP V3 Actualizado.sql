use mundo_patitas3;
-- t16CatalogoTrabajadores
INSERT INTO t16CatalogoTrabajadores
  (id_Trabajador, DNITrabajador, des_apepatTrabajador, des_apematTrabajador, des_nombreTrabajador, num_telefono, direccion, email, cargo, estado)
VALUES
  (50001, '33333333', 'Pérez',  'Lopez',  'María',  '999111222', 'Av. Central 123',    'maria.perez@mp.com',  'Cajero',  'Activo'),
  (50002, '44444444', 'García', 'Torres', 'Carlos', '999222333', 'Calle Falsa 742',    'carlos.garcia@mp.com','Vendedor','Activo'),
  (50003, '55555555', 'Ramos',  'Vera',   'Lucía',  '999333444', 'Jr. Las Flores 321', 'lucia.ramos@mp.com',  'Almacén', 'Activo'),
  (50004, '22222222', 'Flores', 'Diaz',   'Carla',  '988222333', 'Mz B Lt 2',          'carla.flores@demo.com','Responsable de Pedidos','Activo'),
  (50005, '66666666', 'López',  'Mora',   'Ana',    '999444555', 'Av. Secundaria 456', 'ana.lopez@mp.com',   'Responsable de Almacén', 'Activo'),
  (50006, '77777777', 'Martín', 'Salas',  'José',   '999555666', 'Av. Terciaria 789',  'jose.martin@mp.com',  'Responsable de Almacén', 'Activo'),
  (50007, '78541296', 'Martin', 'Saldarriaga',  'Josue Miguel',   '924785136', 'Av. Cuaternaria 452',  'jmartin@mp.com',  'Responsable de distribución', 'Activo'),
  (50008, '81234567', 'Quispe',  'Huamán',  'Luis',    '999111333', 'Av. Los Olivos 456',       'luis.quispe@mp.com',  'Repartidor', 'Activo'),
  (50009, '84567890', 'Vargas',  'Meza',    'Javier',  '999333555', 'Calle Cuzco 120',          'javier.vargas@mp.com','Repartidor', 'Activo'),
  (50010, '85678901', 'Rojas',   'Torres',  'Erick',   '999444666', 'Av. Grau 908',             'erick.rojas@mp.com',  'Repartidor', 'Activo'),
  (50011, '86789012', 'Mendoza', 'Quispe',  'Hugo',    '999555777', 'Av. Perú 345',             'hugo.mendoza@mp.com', 'Repartidor', 'Activo'),
  (50012, '87890123', 'Gómez',   'Paredes', 'Mario',   '999666888', 'Jr. Arequipa 678',         'mario.gomez@mp.com',  'Repartidor', 'Activo'),
  (50013, '88901234', 'Castro',  'Ramos',   'Ricardo', '999777999', 'Av. Argentina 1234',       'ricardo.castro@mp.com','Repartidor','Activo'),
  (50014, '89012345', 'Torres',  'Silva',   'Juan',    '999888000', 'Mz A Lt 5',                'juan.torres@mp.com',  'Repartidor', 'Activo'),
  (50015, '80123456', 'Ramos',   'Campos',  'Carlos',  '999999111', 'Calle Lima 87',            'carlos.ramos@mp.com', 'Repartidor', 'Activo'),
  (50016, '81234568', 'Luna',    'Lopez',   'Diego',   '999112233', 'Av. Colonial 432',         'diego.luna@mp.com',   'Repartidor', 'Activo'),
  (50017, '82345679', 'Reyes',   'Flores',  'Marco',   '999223344', 'Calle Bolognesi 654',      'marco.reyes@mp.com',  'Repartidor', 'Activo'),
  (50018, '83456780', 'Pérez',   'Cruz',    'Jorge',   '999334455', 'Jr. Piura 876',            'jorge.perez@mp.com',  'Repartidor', 'Activo'),
  (50019, '84567891', 'Huamán',  'Valdez',  'Samuel',  '999445566', 'Av. Universitaria 221',    'samuel.huaman@mp.com','Repartidor','Activo'),
  (50020, '85678902', 'Campos',  'Romero',  'Rodrigo', '999556677', 'Jr. Los Rosales 102',      'rodrigo.campos@mp.com','Repartidor','Activo'),
  (50021, '86789013', 'Lopez',   'Reátegui','Andrés',  '999667788', 'Av. Faucett 332',          'andres.lopez@mp.com', 'Repartidor', 'Activo'),
  (50022, '87890124', 'Salas',   'Carrillo','Oscar',   '999778899', 'Jr. Chosica 202',          'oscar.salas@mp.com',  'Repartidor', 'Activo'),
  (50023, '88901235', 'Ramos',   'Peña',    'Henry',   '999889900', 'Av. Argentina 1021',       'henry.ramos@mp.com',  'Repartidor', 'Activo'),
  (50024, '89012346', 'Flores',  'Morales', 'Alexis',  '999990011', 'Calle Surco 77',           'alexis.flores@mp.com','Repartidor','Activo'),
  (50025, '80123457', 'Romero',  'Vega',    'Renzo',   '999101122', 'Av. Colonial 999',         'renzo.romero@mp.com', 'Repartidor', 'Activo'),
  (50026, '81234569', 'Valdez',  'Guerra',  'Cristian','999212233', 'Jr. Puno 445',             'cristian.valdez@mp.com','Repartidor','Activo'),
  (50027, '83456789', 'Sánchez', 'Rojas',   'Pedro',   '999222444', 'Jr. Lima 234',             'pedro.sanchez@mp.com','Repartidor', 'Activo');


-- t37DetalleRequerimiento
INSERT INTO t37DetalleRequerimiento (Id_DetaRequerimiento, Observacion) VALUES
  (37001, 'Reposición estándar'),
  (37002, 'Campaña'),
  (37003, 'Urgente');


-- t31CategoriaProducto
INSERT INTO t31CategoriaProducto (Id_Categoria, Descripcion) VALUES
(24000, 'Alimento para perro'),
(24001, 'Alimento para gato'),
(24002, 'Arena y piedras sanitarias'),
(24003, 'Premios y snacks'),
(24004, 'Juguetes'),
(24005, 'Higiene y limpieza'),
(24006, 'Transporte y viaje'),
(24007, 'Accesorios'),
(24008, 'Camas y descanso'),
(24009, 'Jaulas y acuarios'),
(24010, 'Cuidado dental'),
(24011, 'Alimento para aves'),
(24012, 'Accesorios para acuarios'),
(24013, 'Ropa y accesorios para mascotas'),
(24014, 'Vitaminas y suplementos');


-- t34UnidadMedida
INSERT INTO t34UnidadMedida (Id_UnidadMedida, Descripcion) VALUES
(21000, 'Bolsa 1 kg'),
(21001, 'Bolsa 2 kg'),
(21002, 'Bolsa 4 kg'),
(21003, 'Bolsa 8 kg'),
(21004, 'Lata 400 g'),
(21005, 'Balde 5 L'),
(21006, 'Unidad'),
(21007, 'Paquete'),
(21008, 'Litro'),
(21009, 'ml'),
(21010, 'Pieza'),
(21011, 'Caja'),
(21012, 'Botella 500 ml'),
(21013, 'Paquete 250g');


-- t18CatalogoProducto
INSERT INTO t18CatalogoProducto (NombreProducto, Descripcion, Marca, PrecioUnitario, StockActual, StockMinimo, StockMaximo, Estado, Peso, Volumen, t31CategoriaProducto_Id_Categoria, t34UnidadMedida_Id_UnidadMedida) 
VALUES ('Pedigree Alimento Adulto 1kg', 'Alimento seco para perro adulto, sabor carne', 'Pedigree', 85.00, 30, 10, 50, 'Disponible', 1.00, 0.0360, 24000, 21000), 
('Pedigree Alimento Adulto 2kg', 'Alimento seco para perro adulto, sabor carne', 'Pedigree', 155.00, 30, 10, 50, 'Disponible', 2.00, 0.0720, 24000, 21001), 
('Pedigree Alimento Adulto 4kg', 'Alimento seco para perro adulto, sabor carne', 'Pedigree', 270.00, 18, 6, 30, 'Disponible', 4.00, 0.1500, 24000, 21002), 
('Dog Chow Adulto 1kg', 'Alimento balanceado para perro adulto, sabor pollo', 'Dog Chow', 95.00, 42, 14, 70, 'Disponible', 1.00, 0.0360, 24000, 21000), 
('Dog Chow Adulto 2kg', 'Alimento balanceado para perro adulto, sabor pollo', 'Dog Chow', 178.00, 24, 8, 40, 'Disponible', 2.00, 0.0720, 24000, 21001), 
('Dog Chow Adulto 8kg', 'Alimento balanceado para perro adulto, sabor pollo', 'Dog Chow', 540.00, 12, 4, 20, 'Disponible', 8.00, 0.2800, 24000, 21003), 
('Nutra Nuggets Puppy 4kg', 'Alimento seco para cachorro, alto en proteínas', 'Nutra Nuggets', 320.00, 18, 6, 30, 'Disponible', 4.00, 0.1500, 24000, 21002), 
('Eukanuba Puppy 2kg', 'Alimento seco para cachorro, alto en proteínas', 'Eukanuba', 258.00, 18, 6, 30, 'Disponible', 2.00, 0.0720, 24000, 21001), 
('Royal Canin Mini Adult 1kg', 'Alimento seco para perro adulto raza pequeña', 'Royal Canin', 245.00, 24, 8, 40, 'Disponible', 1.00, 0.0360, 24000, 21000), 
('Royal Canin Mini Adult 4kg', 'Alimento seco para perro adulto raza pequeña', 'Royal Canin', 915.00, 12, 4, 20, 'Disponible', 4.00, 0.1500, 24000, 21002),
('Hill’s Science Diet 2kg', 'Alimento seco premium para perro adulto', 'Hill’s', 312.00, 12, 4, 20, 'Disponible', 2.00, 0.0720, 24000, 21001), 
('Kirkland Signature Adult Dog 18kg', 'Alimento seco para perro adulto', 'Kirkland', 1420.00, 7, 2, 12, 'Disponible', 18.00, 0.5700, 24000, 21003), 
('Purina Cat Chow Adulto 1kg', 'Alimento seco para gato adulto, pollo', 'Purina Cat Chow', 119.00, 30, 10, 50, 'Disponible', 1.00, 0.0360, 24001, 21000), 
('Purina Cat Chow Adulto 4kg', 'Alimento seco para gato adulto, pollo', 'Purina Cat Chow', 398.00, 12, 4, 20, 'Disponible', 4.00, 0.1500, 24001, 21002), 
('Whiskas Adulto 400g', 'Alimento húmedo para gato adulto, sabor pescado', 'Whiskas', 42.00, 42, 14, 70, 'Disponible', 0.40, 0.0040, 24001, 21004), 
('Whiskas Adulto 1kg', 'Alimento seco para gato adulto', 'Whiskas', 92.00, 36, 12, 60, 'Disponible', 1.00, 0.0360, 24001, 21000), 
('Whiskas Adulto 2kg', 'Alimento seco para gato adulto', 'Whiskas', 173.00, 12, 4, 20, 'Disponible', 2.00, 0.0720, 24001, 21001), 
('Felix Fantastic Gato 85g', 'Alimento húmedo para gato, sobres', 'Felix', 21.00, 42, 14, 70, 'Disponible', 0.09, 0.0001, 24001, 21006), 
('Felix Party Mix 60g', 'Snacks para gato, crocantes', 'Felix', 45.00, 48, 16, 80, 'Disponible', 0.06, 0.00006, 24003, 21006), 
('Cepillo Dental Para Perro Medium', 'Cepillo limpia dientes perros medianos', 'PetSmile', 120.00, 21, 7, 35, 'Disponible', 0.10, 0.0010, 24010, 21006), 
('Hueso Dental Chiquito', 'Premio dental para perros pequeños', 'DentalBone', 80.00, 6, 2, 10, 'Disponible', 0.15, 0.0015, 24010, 21010), 
('Mezcla de Semillas para Aves 1kg', 'Alimento completo para aves pequeñas', 'BirdBox', 160.00, 15, 5, 25, 'Disponible', 1.00, 0.0360, 24011, 21000), 
('Jaula Para Canario Mediana', 'Jaula de 45x30x40 cm para canarios', 'BirdHome', 450.00, 3, 1, 6, 'Disponible', 3.50, 0.0540, 24011, 21006), 
('Filtro para Acuario 3L', 'Filtro externo para acuarios pequeños', 'AquaPure', 350.00, 4, 1, 8, 'Disponible', 1.80, 0.0050, 24012, 21006), 
('Termómetro Digital Acuario', 'Termómetro para acuarios con lectura digital', 'AquaTech', 95.00, 9, 3, 15, 'Disponible', 0.10, 0.0008, 24012, 21006), 
('Suéter para Perro Mediano', 'Ropa térmica para perros medianos', 'PetFashion', 230.00, 9, 3, 15, 'Disponible', 0.40, 0.0040, 24013, 21006), 
('Vitaminas Multinivel para Perro 250g', 'Suplementos vitamínicos para crecimiento', 'NutriPet', 180.00, 12, 4, 20, 'Disponible', 0.25, 0.0025, 24014, 21013), 
('Vitaminas de Salmón para Gato 500ml', 'Suplemento líquido sabor salmón para gatos', 'FelineVits', 210.00, 9, 3, 15, 'Disponible', 0.50, 0.0005, 24014, 21012), 
('Dentastix para perros pequeños 3pzas', 'Premios dentales para perros pequeños', 'Pedigree', 55.00, 6, 2, 10, 'Disponible', 0.05, 0.0005, 24010, 21010), 
('Snack Mixto Para Aves Tropicales 500g', 'Snack saludable para aves tropicales', 'BirdBox', 88.00, 9, 3, 15, 'Disponible', 0.50, 0.0180, 24011, 21013),
('Cama para perro tamaño pequeño', 'Cama acolchonada para perros pequeños', 'ComfyPet', 550.00, 7, 2, 12, 'Disponible', 1.2, 0.0250, 24008, 21006), 
('Mordedor de nylon para perros grandes', 'Juguete duradero para masticar', 'Nylabone', 180.00, 9, 3, 15, 'Disponible', 0.45, 0.0040, 24004, 21006), 
('Arena Perfume Lavanda 10L', 'Arena para gato con aroma a lavanda', 'Sanicat', 250.00, 12, 4, 20, 'Disponible', 10.00, 0.0120, 24002, 21005), 
('Shampoo antipulgas 500ml', 'Shampoo antipulgas para perros y gatos', 'PetClean', 215.00, 12, 4, 20, 'Disponible', 0.50, 0.0005, 24005, 21012), 
('Arnés réflex para perro mediano', 'Arnés con cinta reflectante para seguridad', 'Rogz', 370.00, 10, 3, 18, 'Disponible', 0.30, 0.0020, 24007, 21006), 
('Alimento Premium Para Perro Senior 3kg', 'Alimento nutricional para perros mayores', 'Royal Canin', 420.00, 12, 4, 20, 'Disponible', 3.00, 0.1080, 24000, 21002), 
('Snack Saludable Para Perro 100g', 'Snack natural y bajo en calorías', 'HealthyPet', 65.00, 18, 6, 30, 'Disponible', 0.10, 0.0020, 24003, 21013), 
('Plato Inoxidable Para Mascotas 1L', 'Plato resistente para comida o agua', 'PetSupplies', 125.00, 18, 6, 30, 'Disponible', 0.80, 0.0015, 24007, 21008), 
('Caja De Transporte Para Gato Mediano', 'Transportadora cómoda para gatos', 'FurHome', 775.00, 4, 1, 7, 'Disponible', 3.00, 0.0400, 24006, 21011), 
('Juguete Interactivo Para Gato', 'Rascador y juguete con plumas', 'Trixie', 180.00, 10, 3, 18, 'Disponible', 0.25, 0.0008, 24004, 21006), 
('Líquido Anti-Pulgas 250ml', 'Líquido antipulgas para mascotas', 'PetCare', 195.00, 12, 4, 20, 'Disponible', 0.25, 0.0003, 24005, 21012), 
('Cepillo Dental Para Perro Medium', 'Cepillo limpia dientes perros medianos', 'PetSmile', 120.00, 6, 2, 10, 'Disponible', 0.10, 0.0010, 24010, 21006), 
('Hueso Dental Chiquito', 'Premio dental para perros pequeños', 'DentalBone', 80.00, 6, 2, 10, 'Disponible', 0.15, 0.0015, 24010, 21010), 
('Mezcla de Semillas para Aves 1kg', 'Alimento completo para aves pequeñas', 'BirdBox', 160.00, 15, 5, 25, 'Disponible', 1.00, 0.0360, 24011, 21000), 
('Jaula Para Canario Mediana', 'Jaula de 45x30x40 cm para canarios', 'BirdHome', 450.00, 3, 1, 6, 'Disponible', 3.50, 0.0540, 24011, 21006), 
('Filtro para Acuario 3L', 'Filtro externo para acuarios pequeños', 'AquaPure', 350.00, 4, 1, 8, 'Disponible', 1.80, 0.0050, 24012, 21006),
('Termómetro Digital Acuario', 'Termómetro para acuarios con lectura digital', 'AquaTech', 95.00, 9, 3, 15, 'Disponible', 0.10, 0.0008, 24012, 21006), 
('Suéter para Perro Mediano', 'Ropa térmica para perros medianos', 'PetFashion', 230.00, 9, 3, 15, 'Disponible', 0.40, 0.0040, 24013, 21006), 
('Vitaminas Multinivel para Perro 250g', 'Suplementos vitamínicos para crecimiento', 'NutriPet', 180.00, 12, 4, 20, 'Disponible', 0.25, 0.0025, 24014, 21013), 
('Vitaminas de Salmón para Gato 500ml', 'Suplemento líquido sabor salmón para gatos', 'FelineVits', 210.00, 9, 3, 15, 'Disponible', 0.50, 0.0005, 24014, 21012), 
('Dentastix para perros pequeños 3pzas', 'Premios dentales para perros pequeños', 'Pedigree', 55.00, 6, 2, 10, 'Disponible', 0.05, 0.0005, 24010, 21010), 
('Snack Mixto Para Aves Tropicales 500g', 'Snack saludable para aves tropicales', 'BirdBox', 88.00, 9, 3, 15, 'Disponible', 0.50, 0.0180, 24011, 21013), 
('Cama para perro tamaño pequeño', 'Cama acolchonada para perros pequeños', 'ComfyPet', 550.00, 7, 2, 12, 'Disponible', 1.2, 0.0250, 24008, 21006), 
('Mordedor de nylon para perros grandes', 'Juguete duradero para masticar', 'Nylabone', 180.00, 9, 3, 15, 'Disponible', 0.45, 0.0040, 24004, 21006), 
('Arena Perfume Lavanda 10L', 'Arena para gato con aroma a lavanda', 'Sanicat', 250.00, 12, 4, 20, 'Disponible', 10.00, 0.0120, 24002, 21005), 
('Shampoo antipulgas 500ml', 'Shampoo antipulgas para perros y gatos', 'PetClean', 215.00, 12, 4, 20, 'Disponible', 0.50, 0.0005, 24005, 21012), 
('Arnés réflex para perro mediano', 'Arnés con cinta reflectante para seguridad', 'Rogz', 370.00, 10, 3, 18, 'Disponible', 0.30, 0.0020, 24007, 21006), 
('Alimento Premium Para Perro Senior 3kg', 'Alimento nutricional para perros mayores', 'Royal Canin', 420.00, 12, 4, 20, 'Disponible', 3.00, 0.1080, 24000, 21002), 
('Snack Saludable Para Perro 100g', 'Snack natural y bajo en calorías', 'HealthyPet', 65.00, 18, 6, 30, 'Disponible', 0.10, 0.0020, 24003, 21013), 
('Plato Inoxidable Para Mascotas 1L', 'Plato resistente para comida o agua', 'PetSupplies', 125.00, 18, 6, 30, 'Disponible', 0.80, 0.0015, 24007, 21008), 
('Caja De Transporte Para Gato Mediano', 'Transportadora cómoda para gatos', 'FurHome', 775.00, 4, 1, 7, 'Disponible', 3.00, 0.0400, 24006, 21011), 
('Juguete Interactivo Para Gato', 'Rascador y juguete con plumas', 'Trixie', 180.00, 10, 3, 18, 'Disponible', 0.25, 0.0008, 24004, 21006), 
('Líquido Anti-Pulgas 250ml', 'Líquido antipulgas para mascotas', 'PetCare', 195.00, 12, 4, 20, 'Disponible', 0.25, 0.0003, 24005, 21012), 
('Snack para perro sabor pollo 500g', 'Snack saludable y bajo en calorías', 'HealthyBites', 95.00, 12, 4, 20, 'Disponible', 0.50, 0.0180, 24003, 21013), 
('Aceite de pescado para perros 250ml', 'Suplemento para pelaje brillante', 'NutriFish', 190.00, 10, 3, 18, 'Disponible', 0.25, 0.0003, 24014, 21012), 
('Juguete de cuerda para cachorros', 'Juguete para fortalecer mandíbula', 'PuppyPlay', 120.00, 9, 3, 15, 'Disponible', 0.20, 0.0015, 24004, 21010), 
('Alimento para gato esterilizado 2kg', 'Alimento especial para gatos esterilizados', 'FelineCare', 210.00, 9, 3, 15, 'Disponible', 2.00, 0.0720, 24001, 21001), 
('Jaula pequeña para hamsters', 'Jaula segura y cómoda', 'SmallPets', 350.00, 4, 1, 8, 'Disponible', 2.00, 0.0300, 24009, 21006), 
('Bebedero automático para gatos 2L', 'Fuente de agua limpia y fresca', 'HydroPet', 290.00, 9, 3, 15, 'Disponible', 2.00, 0.0020, 24007, 21008), 
('Shampoo natural para perros 1L', 'Shampoo sin químicos agresivos', 'GreenPet', 220.00, 12, 4, 20, 'Disponible', 1.00, 0.0010, 24005, 21008), 
('Cama ortopédica para perros grandes', 'Apoyo para perros con problemas articulares', 'OrthoPet', 880.00, 6, 2, 10, 'Disponible', 3.00, 0.0400, 24008, 21006), 
('Huesos de cuero para perros', 'Snack natural para masticar', 'NaturalBite', 75.00, 18, 6, 30, 'Disponible', 0.15, 0.0003, 24003, 21006), 
('Collar antipulgas para perros', 'Protección duradera contra pulgas y garrapatas', 'FleaStop', 140.00, 10, 3, 18, 'Disponible', 0.05, 0.0001, 24007, 21006), 
('Vitaminas para gatos seniors 200g', 'Suplemento para gatos mayores', 'SeniorVits', 195.00, 7, 2, 12, 'Disponible', 0.20, 0.0005, 24014, 21013), 
('Alimento húmedo para perros en lata 400g', 'Alimento balanceado en lata', 'Pedigree', 58.00, 18, 6, 30, 'Disponible', 0.40, 0.0040, 24000, 21004), 
('Juguete interactivo para gatos', 'Juguete para estimular actividad', 'CatJoy', 110.00, 12, 4, 20, 'Disponible', 0.10, 0.0008, 24004, 21006), 
('Transportadora para perros medianos', 'Bolso de transporte cómodo y seguro', 'SafeTravel', 650.00, 3, 1, 6, 'Disponible', 3.00, 0.0300, 24006, 21006), 
('Comida balanceada para loros 1kg', 'Alimento especial para loros', 'BirdCare', 170.00, 9, 3, 15, 'Disponible', 1.00, 0.0360, 24011, 21000), 
('Lámpara UV para reptiles', 'Lámpara UVB para terrarios', 'ReptiLight', 375.00, 6, 2, 10, 'Disponible', 0.30, 0.0010, 24012, 21006), 
('Kit dental para perros', 'Kit completo para higiene oral', 'PetSmile', 140.00, 9, 3, 15, 'Disponible', 0.50, 0.0015, 24010, 21006), 
('Juego de correas para perros pequeños', 'Juego variado de correas resistentes', 'LeadPro', 240.00, 7, 2, 12, 'Disponible', 0.25, 0.0012, 24007, 21006), 
('Alimento para cachorros 3kg', 'Alimento nutricional para cachorros', 'Royal Canin', 420.00, 12, 4, 20, 'Disponible', 3.00, 0.1080, 24000, 21002), 
('Snacks de salmón para gatos 100g', 'Snack natural y rico en omega', 'FelineTreats', 90.00, 18, 6, 30, 'Disponible', 0.10, 0.0020, 24003, 21013), 
('Almohadilla refrescante para perros', 'Alfombrilla para mantener fresco a mascotas', 'CoolPet', 350.00, 6, 2, 10, 'Disponible', 0.95, 0.0200, 24008, 21006), 
('Plato resistente para perros grandes', 'Plato antideslizante de acero inoxidable', 'PetSupplies', 180.00, 12, 4, 20, 'Disponible', 1.00, 0.0015, 24007, 21008), 
('Collar GPS para perros', 'Collar con localizador GPS integrado', 'TrackPet', 850.00, 4, 1, 8, 'Disponible', 0.20, 0.0006, 24007, 21006), 
('Arena aglutinante para gatos 10L', 'Arena con control efectivo de olores', 'Fresh Step', 270.00, 12, 4, 20, 'Disponible', 10.00, 0.0120, 24002, 21005), 
('Caja casa para conejos', 'Casa cómoda y segura para conejos', 'SmallPets', 480.00, 3, 1, 6, 'Disponible', 3.00, 0.0350, 24009, 21006), 
('Pelota para perros medianos', 'Pelota resistente para jugar', 'PlayBall', 120.00, 10, 3, 18, 'Disponible', 0.25, 0.0030, 24004, 21010), 
('Jabón antipulgas para perros', 'Jabón natural contra pulgas y garrapatas', 'PetClean', 160.00, 12, 4, 20, 'Disponible', 0.50, 0.0010, 24005, 21008), 
('Jaula para pájaros tropicales', 'Jaula amplia para especies tropicales', 'BirdHome', 520.00, 4, 1, 7, 'Disponible', 5.00, 0.0450, 24011, 21006), 
('Protector solar para perros', 'Protección solar para pieles sensibles', 'SunProtect', 120.00, 9, 3, 15, 'Disponible', 0.10, 0.0003, 24005, 21012), 
('Cama inflable para perros', 'Cama confortable y portátil', 'ComfyPet', 300.00, 7, 2, 12, 'Disponible', 1.50, 0.0300, 24008, 21006), 
('Collar luminoso para perros', 'Collar con luces LED para visibilidad nocturna', 'LightPet', 180.00, 10, 3, 18, 'Disponible', 0.10, 0.0004, 24007, 21006), 
('Comida húmeda para gatitos 85g', 'Alimento húmedo para gatitos', 'Whiskas', 25.00, 18, 6, 30, 'Disponible', 0.085, 0.00009, 24001, 21006), 
('Arena ecológica para gatos 3kg', 'Arena biodegradable para gatos', 'EcoCat', 210.00, 9, 3, 15, 'Disponible', 3.00, 0.0035, 24002, 21002), 
('Juguete masticable para perros medianos', 'Juguete para fortalecer dientes y mandíbula', 'ChewMaster', 150.00, 9, 3, 15, 'Disponible', 0.30, 0.0020, 24004, 21006), 
('Bebedero automático para perros 4L', 'Fuente de agua para perros', 'HydroMax', 350.00, 6, 2, 10, 'Disponible', 4.00, 0.0040, 24007, 21005), 
('Paquete de premios para gatos 150g', 'Premios variados para gatos', 'FelineSnack', 85.00, 12, 4, 20, 'Disponible', 0.15, 0.0010, 24003, 21013), 
('Kit de limpieza para acuarios', 'Set completo para mantenimiento', 'AquaClean', 400.00, 4, 1, 8, 'Disponible', 2.00, 0.0100, 24012, 21011), 
('Ropa impermeable para perros', 'Impermeable con capucha para perros', 'RainDog', 390.00, 9, 3, 15, 'Disponible', 0.35, 0.0020, 24013, 21006), 
('Lámpara UV para reptiles 15W', 'Lámpara especial para terrarios', 'ReptiLight', 420.00, 3, 1, 6, 'Disponible', 0.50, 0.0010, 24012, 21006), 
('Suplemento de calcio para aves', 'Suplemento para fortalecer huesos', 'BirdCare', 150.00, 10, 3, 18, 'Disponible', 0.20, 0.0008, 24014, 21013), 
('Juguete interactivo con varita para gatos', 'Varita con plumas para juego', 'CatFun', 95.00, 12, 4, 20, 'Disponible', 0.05, 0.0005, 24004, 21006), 
('Snacks de salmón para perros 200g', 'Snack natural y saludable', 'DogTreats', 100.00, 15, 5, 25, 'Disponible', 0.20, 0.0020, 24003, 21013), 
('Cama para mascotas ortopédica pequeña', 'Cama para perros pequeños', 'ComfortPet', 600.00, 7, 2, 12, 'Disponible', 1.30, 0.0200, 24008, 21006), 
('Arnés para perros medianos con reflectivo', 'Arnés cómodo y seguro', 'SafeWalk', 310.00, 9, 3, 15, 'Disponible', 0.25, 0.0015, 24007, 21006),
('Platos dobles para perros', 'Platos para agua y comida', 'PetDishes', 120.00, 12, 4, 20, 'Disponible', 0.50, 0.0015, 24007, 21007), 
('Comida para gatos esterilizados 3kg', 'Alimento balanceado especial', 'Royal Canin', 400.00, 9, 3, 15, 'Disponible', 3.00, 0.1080, 24001, 21002), 
('Rascador para gatos tipo torre', 'Estructura para rascar y jugar', 'FeliPlay', 550.00, 6, 2, 10, 'Disponible', 3.50, 0.0800, 24004, 21006), 
('Snacks dentales para perros', 'Premios que ayudan a limpiar dientes', 'HealthyBite', 130.00, 15, 5, 25, 'Disponible', 0.10, 0.0008, 24003, 21013), 
('Cepillo para perros de pelo corto', 'Cepillo ergonómico para pelajes cortos', 'PetBrush', 115.00, 10, 3, 18, 'Disponible', 0.12, 0.0010, 24010, 21006), 
('Alimento para perros cachorros 5kg', 'Alimento equilibrado para crecimiento', 'NutriDog', 520.00, 9, 3, 15, 'Disponible', 5.00, 0.1800, 24000, 21003), 
('Golosinas naturales para gatos', 'Premios saludables para gatos', 'GatoSnack', 90.00, 18, 6, 30, 'Disponible', 0.10, 0.0009, 24003, 21013), 
('Jaula para conejos pequeña', 'Jaula segura y cómoda', 'SmallPets', 420.00, 3, 1, 6, 'Disponible', 2.50, 0.0300, 24009, 21006), 
('Bebedero automático para gatos', 'Fuente de agua limpia y fresca', 'HydroCat', 280.00, 9, 3, 15, 'Disponible', 2.00, 0.0020, 24007, 21008), 
('Shampoo para gatos 500ml', 'Shampoo especial para gato', 'CatClean', 210.00, 12, 4, 20, 'Disponible', 0.50, 0.0005, 24005, 21012), 
('Juguete de pelota para perros', 'Pelota resistente para juegos', 'PlayPet', 130.00, 12, 4, 20, 'Disponible', 0.40, 0.0030, 24004, 21010), 
('Cinta reflectante para collares', 'Cinta segura para visibilidad nocturna', 'SafeLead', 50.00, 24, 8, 40, 'Disponible', 0.02, 0.0001, 24007, 21013), 
('Caja transportadora para gatos pequeños', 'Transportadora cómoda y segura', 'PetCarrier', 700.00, 3, 1, 6, 'Disponible', 1.50, 0.0300, 24006, 21011), 
('Aceite de coco para piel de mascotas 250ml', 'Aceite natural para pelajes y piel', 'NaturalPet', 190.00, 9, 3, 15, 'Disponible', 0.25, 0.0003, 24014, 21012), 
('Cama para perros medianos', 'Cama acolchonada para perros medianos', 'ComfyPet', 550.00, 7, 2, 12, 'Disponible', 1.80, 0.0250, 24008, 21006), 
('Antipulgas en spray para perros', 'Protección efectiva contra pulgas', 'FleaAway', 180.00, 12, 4, 20, 'Disponible', 0.50, 0.0005, 24005, 21012);


-- t27MetodoEntrega
INSERT INTO t27MetodoEntrega (Id_MetodoEntrega, Descripcion, Estado) VALUES
  (9001, 'Recojo en tienda',     'Activo'),
  (9002, 'Delivery - estándar',  'Activo');


-- t20Cliente
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


-- t76ZonaEnvio
INSERT INTO t76ZonaEnvio (Id_Zona, DescZona, Estado) VALUES
(1, 'LIMA NORTE', 'Activo'),
(2, 'LIMA SUR', 'Activo'),
(3, 'LIMA ESTE', 'Activo'),
(4, 'LIMA OESTE', 'Activo');


-- t77DistritoEnvio
INSERT INTO t77DistritoEnvio (Id_Distrito, Id_Zona, DescNombre, MontoCosto, Estado) VALUES
-- 🔵 Zona Norte (Id_Zona = 1, Id_Distrito = 101–199)
(101, 1, 'ANCÓN',                18.00, 'Activo'),
(102, 1, 'CARABAYLLO',           17.50, 'Activo'),
(103, 1, 'COMAS',                16.00, 'Activo'),
(104, 1, 'INDEPENDENCIA',        15.50, 'Activo'),
(105, 1, 'LOS OLIVOS',           15.00, 'Activo'),
(106, 1, 'PUENTE PIEDRA',        17.00, 'Activo'),
(107, 1, 'SAN MARTÍN DE PORRES', 16.50, 'Activo'),
(108, 1, 'SANTA ROSA',           18.50, 'Activo'),

-- 🔴 Zona Sur (Id_Zona = 2, Id_Distrito = 201–299)
(201, 2, 'CHORRILLOS',              15.50, 'Activo'),
(202, 2, 'LURÍN',                   19.00, 'Activo'),
(203, 2, 'PACHACÁMAC',              19.50, 'Activo'),
(204, 2, 'PUCUSANA',                20.00, 'Activo'),
(205, 2, 'PUNTA HERMOSA',           20.00, 'Activo'),
(206, 2, 'PUNTA NEGRA',             20.00, 'Activo'),
(207, 2, 'SAN BARTOLO',             20.00, 'Activo'),
(208, 2, 'SAN JUAN DE MIRAFLORES',  16.00, 'Activo'),
(209, 2, 'SANTA MARÍA DEL MAR',     20.00, 'Activo'),
(210, 2, 'SANTIAGO DE SURCO',       15.50, 'Activo'),
(211, 2, 'VILLA EL SALVADOR',       17.00, 'Activo'),
(212, 2, 'VILLA MARÍA DEL TRIUNFO', 17.50, 'Activo'),

-- 🟢 Zona Este (Id_Zona = 3, Id_Distrito = 301–399)
(301, 3, 'ATE',                  15.50, 'Activo'),
(302, 3, 'CHACLACAYO',           18.50, 'Activo'),
(303, 3, 'CIENEGUILLA',          18.50, 'Activo'),
(304, 3, 'EL AGUSTINO',          14.00, 'Activo'),
(305, 3, 'LA MOLINA',            15.50, 'Activo'),
(306, 3, 'LURIGANCHO (CHOSICA)', 18.00, 'Activo'),
(307, 3, 'SAN JUAN DE LURIGANCHO', 16.50, 'Activo'),
(308, 3, 'SANTA ANITA',          15.00, 'Activo'),

-- 🟡 Zona Oeste (Id_Zona = 4, Id_Distrito = 401–499)
(401, 4, 'BARRANCO',          14.00, 'Activo'),
(402, 4, 'BREÑA',             13.50, 'Activo'),
(403, 4, 'CERCADO DE LIMA',   12.00, 'Activo'),
(404, 4, 'JESÚS MARÍA',       13.50, 'Activo'),
(405, 4, 'LA VICTORIA',       14.00, 'Activo'),
(406, 4, 'LINCE',             13.00, 'Activo'),
(407, 4, 'MAGDALENA DEL MAR', 13.50, 'Activo'),
(408, 4, 'MIRAFLORES',        14.50, 'Activo'),
(409, 4, 'PUEBLO LIBRE',      13.50, 'Activo'),
(410, 4, 'RÍMAC',             14.50, 'Activo'),
(411, 4, 'SAN BORJA',         14.50, 'Activo'),
(412, 4, 'SAN ISIDRO',        14.50, 'Activo'),
(413, 4, 'SAN LUIS',          13.50, 'Activo'),
(414, 4, 'SAN MIGUEL',        14.00, 'Activo'),
(415, 4, 'SURQUILLO',         14.00, 'Activo');


-- t73DireccionAlmacen
INSERT INTO t73DireccionAlmacen
  (Id_DireccionAlmacen, NombreAlmacen, DireccionOrigen, Id_Distrito, Estado)
VALUES
  (1, 'Almacén Central', 'Jirón Gualberto Guevara 135 Altura Cruce de Av. Colonial, Av. Nicolás Dueñas y, Lima 15107', 403, 'Activo');


-- t94TrabajadoresAlmacenes
INSERT INTO t94TrabajadoresAlmacenes (id_Trabajador, Id_DireccionAlmacen) VALUES 
(50003, 1),
(50004, 1),
(50006, 1);


-- t70DireccionEnvioCliente
INSERT INTO t70DireccionEnvioCliente
  (Id_Cliente, NombreContacto, TelefonoContacto, Direccion, Id_Distrito, DniReceptor)
VALUES
  (60001, 'Ana Quispe Huamán',    '987654321', 'Av. Naranjal 456, Los Olivos',              105, '12345678'),
  (60001, 'Ana Quispe Huamán',    '987654321', 'Jr. Las Palmeras 120, Los Olivos',          105, '12345678'),
  (60002, 'Pedro Flores Rojas',   '981234567', 'Av. Universitaria 789, San Martín de Porres', 107, '87654321'),
  (60004, 'Jorge Ramírez Poma',   '986543210', 'Av. Arequipa 1234, Lince',                  406, '33445566'),
  (60005, 'María García Luna',    '989112233', 'Av. San Borja Norte 350, San Borja',        411, '99887766'),
  (60006, 'Carlos Torres Campos', '981223344', 'Av. Benavides 2200, Santiago de Surco',     210, '55667788'),
  (60007, 'Rocío Pérez Saldaña',  '984556677', 'Av. Mariátegui 765, Jesús María',           404, '44556677'),
  (60008, 'Hugo López Vega',      '982334455', 'Av. Universitaria 1200, Comas',             103, '22334455'),
  (60009, 'Karla Mendoza Ríos',   '983778899', 'Av. Túpac Amaru 114, Independencia',        104, '77889911'),
  (60010, 'Diego Rojas Cárdenas', '985667788', 'Av. La Marina 1020, San Miguel',            414, '66778899'),
  (60011, 'Elena Salazar Quispe', '986121212', 'Jr. Puno 456, Cercado de Lima',             403, '12121212'),
  (60012, 'Marco Valdez Núñez',   '987343434', 'Av. Venezuela 890, Breña',                  402, '34343434'),
  (60013, 'Patricia Castillo Zapata','989565656','Av. Brasil 1500, Jesús María',            404, '56565656'),
  (60014, 'Bruno Aguilar Sánchez','981787878', 'Av. La Marina 700, Pueblo Libre',           409, '78787878'),
  (60015, 'Verónica Chávez Ibarra','983909090','Jr. Ancash 210, Cercado de Lima',           403, '90909090');


-- t28_Metodopago
INSERT INTO t28_Metodopago (Id_MetodoPago, Descripcion) VALUES
  (28001, 'Efectivo'),
  (28002, 'Tarjeta');


-- t30TipoBanco
INSERT INTO t30TipoBanco (Id_TipoBanco, Descripcion) VALUES
  (30001, 'BCP'),
  (30002, 'BBVA');


-- t78Vehiculo
INSERT INTO t78Vehiculo 
(Id_Vehiculo, Marca, Modelo, Placa, Anio, Volumen, CapacidadPesoKg, Estado)
VALUES
(14000, 'Toyota',    'Hiace',   'ABC-500', 2020, 8.0000, 1100.0000, 'Disponible'),
(14001, 'Hyundai',   'H1',      'BCD-501', 2021, 8.0000, 1100.0000, 'Disponible'),
(14002, 'Nissan',    'Urvan',   'CDE-502', 2019, 8.0000, 1100.0000, 'Disponible'),
(14003, 'Kia',       'Pregio',  'DEF-503', 2020, 8.0000, 1100.0000, 'Disponible'),
(14004, 'Mercedes',  'Sprinter','EFG-504', 2022, 8.0000, 1100.0000, 'Disponible'),
(14005, 'Toyota',    'Hiace',   'FGH-505', 2018, 8.0000, 1100.0000, 'Disponible'),
(14006, 'Hyundai',   'H1',      'GHI-506', 2021, 8.0000, 1100.0000, 'Disponible'),
(14007, 'Chevrolet', 'N300',    'HIJ-507', 2020, 8.0000, 1100.0000, 'Disponible'),
(14008, 'Nissan',    'NV350',   'IJK-508', 2022, 8.0000, 1100.0000, 'Disponible'),
(14009, 'Kia',       'Bongo',   'JKL-509', 2019, 8.0000, 1100.0000, 'Disponible'),
(14010, 'Toyota',    'Hiace',   'KLM-510', 2021, 8.0000, 1100.0000, 'Disponible'),
(14011, 'Hyundai',   'H1',      'LMN-511', 2019, 8.0000, 1100.0000, 'Disponible'),
(14012, 'Nissan',    'Urvan',   'MNO-512', 2020, 8.0000, 1100.0000, 'Disponible'),
(14013, 'Kia',       'Pregio',  'NOP-513', 2022, 8.0000, 1100.0000, 'Disponible'),
(14014, 'Mercedes',  'Sprinter','OPQ-514', 2020, 8.0000, 1100.0000, 'Disponible'),
(14015, 'Toyota',    'Hiace',   'PQR-515', 2021, 8.0000, 1100.0000, 'Disponible'),
(14016, 'Hyundai',   'Porter',  'QRS-516', 2018, 8.0000, 1100.0000, 'Disponible'),
(14017, 'Chevrolet', 'N300',    'RST-517', 2020, 8.0000, 1100.0000, 'Disponible'),
(14018, 'Nissan',    'NV350',   'STU-518', 2021, 8.0000, 1100.0000, 'Disponible'),
(14019, 'Kia',       'Bongo',   'TUV-519', 2022, 8.0000, 1100.0000, 'Disponible'),
(14020, 'Toyota',    'Hiace',   'UVW-520', 2020, 8.0000, 1100.0000, 'Disponible'),
(14021, 'Hyundai',   'H1',      'VWX-521', 2021, 8.0000, 1100.0000, 'Disponible'),
(14022, 'Nissan',    'Urvan',   'WXY-522', 2022, 8.0000, 1100.0000, 'Disponible'),
(14023, 'Kia',       'Pregio',  'XYZ-523', 2019, 8.0000, 1100.0000, 'Disponible'),
(14024, 'Mercedes',  'Sprinter','YZA-524', 2021, 8.0000, 1100.0000, 'Disponible');


-- t79AsignacionRepartidorVehiculo
INSERT INTO t79AsignacionRepartidorVehiculo
  (Id_Trabajador, Id_Vehiculo, Fecha_Inicio, Fecha_Fin, Estado)
VALUES
  (50008, 14000, '2025-08-17', NULL, 'Activo'),
  (50009, 14001, '2025-08-19', NULL, 'Activo'),
  (50010, 14002, '2025-08-21', NULL, 'Activo'),
  (50011, 14003, '2025-08-23', NULL, 'Activo'),
  (50012, 14004, '2025-08-25', NULL, 'Activo'),
  (50013, 14005, '2025-08-27', NULL, 'Activo'),
  (50014, 14006, '2025-08-29', NULL, 'Activo'),
  (50015, 14007, '2025-09-01', NULL, 'Activo'),
  (50016, 14008, '2025-09-05', NULL, 'Activo'),
  (50017, 14009, '2025-09-09', NULL, 'Activo'),
  (50018, 14010, '2025-09-13', NULL, 'Activo'),
  (50019, 14011, '2025-09-18', NULL, 'Activo'),
  (50020, 14012, '2025-09-22', NULL, 'Activo'),
  (50021, 14013, '2025-09-26', NULL, 'Activo'),
  (50022, 14014, '2025-09-30', NULL, 'Activo');


-- t78Vehiculo
UPDATE t78Vehiculo
SET Estado = 'Asignado'
WHERE Id_Vehiculo IN (
  14000, 14001, 14002, 14003, 14004,
  14005, 14006, 14007, 14008, 14009,
  14010, 14011, 14012, 14013, 14014
);


-- t41LicenciaConductor
INSERT INTO t41LicenciaConductor 
  (id_Trabajador, Num_Licencia, Categoria, Fec_Emision, Fec_Revalidacion, Estado)
VALUES
  (50002, 'Q44444444', 'A-II-a', '2020-03-15', '2025-03-15', 'Vigente');


/*PRODUCTOS PARA PRUEBA*/
INSERT INTO t18CatalogoProducto 
(NombreProducto, Descripcion, Marca, PrecioUnitario, StockActual, StockMinimo, StockMaximo, Estado, Peso, Volumen, t31CategoriaProducto_Id_Categoria, t34UnidadMedida_Id_UnidadMedida)
VALUES
('Producto 1', 'Descripción del producto 1', 'Marca 1', 120.50, 40, 10, 100, 'Disponible', 50.5, 0.8, 24000, 21000),
('Producto 2', 'Descripción del producto 2', 'Marca 2', 85.00, 40, 10, 100, 'Disponible', 60.2, 1.0, 24001, 21001),
('Producto 3', 'Descripción del producto 3', 'Marca 3', 310.75, 40, 10, 100, 'Disponible', 45.0, 1.1, 24002, 21002),
('Producto 4', 'Descripción del producto 4', 'Marca 4', 200.00, 40, 10, 100, 'Disponible', 55.8, 0.9, 24000, 21000),
('Producto 5', 'Descripción del producto 5', 'Marca 5', 150.30, 40, 10, 100, 'Disponible', 35.2, 0.7, 24001, 21001),
('Producto 6', 'Descripción del producto 6', 'Marca 1', 220.10, 40, 10, 100, 'Disponible', 65.0, 1.2, 24002, 21002),
('Producto 7', 'Descripción del producto 7', 'Marca 2', 175.50, 40, 10, 100, 'Disponible', 40.5, 0.6, 24000, 21003),
('Producto 8', 'Descripción del producto 8', 'Marca 3', 95.00, 40, 10, 100, 'Disponible', 70.0, 1.0, 24001, 21000),
('Producto 9', 'Descripción del producto 9', 'Marca 4', 135.25, 40, 10, 100, 'Disponible', 20.3, 0.5, 24002, 21001),
('Producto 10', 'Descripción del producto 10', 'Marca 5', 280.40, 40, 10, 100, 'Disponible', 60.5, 1.1, 24000, 21002),
('Producto 11', 'Descripción del producto 11', 'Marca 1', 310.00, 40, 10, 100, 'Disponible', 55.0, 0.9, 24001, 21003),
('Producto 12', 'Descripción del producto 12', 'Marca 2', 180.60, 40, 10, 100, 'Disponible', 25.0, 0.7, 24002, 21000),
('Producto 13', 'Descripción del producto 13', 'Marca 3', 145.80, 40, 10, 100, 'Disponible', 65.2, 1.2, 24000, 21001),
('Producto 14', 'Descripción del producto 14', 'Marca 4', 220.90, 40, 10, 100, 'Disponible', 50.0, 0.8, 24001, 21002),
('Producto 15', 'Descripción del producto 15', 'Marca 5', 195.40, 40, 10, 100, 'Disponible', 45.5, 0.9, 24002, 21003),
('Producto 16', 'Descripción del producto 16', 'Marca 1', 210.00, 40, 10, 100, 'Disponible', 35.8, 0.6, 24000, 21000),
('Producto 17', 'Descripción del producto 17', 'Marca 2', 175.25, 40, 10, 100, 'Disponible', 55.6, 0.7, 24001, 21001),
('Producto 18', 'Descripción del producto 18', 'Marca 3', 320.75, 40, 10, 100, 'Disponible', 60.0, 1.2, 24002, 21002),
('Producto 19', 'Descripción del producto 19', 'Marca 4', 140.50, 40, 10, 100, 'Disponible', 30.0, 0.5, 24000, 21003),
('Producto 20', 'Descripción del producto 20', 'Marca 5', 255.30, 40, 10, 100, 'Disponible', 65.5, 1.0, 24001, 21000);