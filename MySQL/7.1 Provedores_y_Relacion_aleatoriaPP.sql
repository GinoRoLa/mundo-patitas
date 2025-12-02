use mundo_patitas3;

/*!40000 ALTER TABLE `t17catalogoproveedor` DISABLE KEYS */;

INSERT INTO `t17catalogoproveedor` 
(`Id_NumRuc`, `des_RazonSocial`, `DireccionProv`, `Telefono`, `Correo`, `estado`) 
VALUES
('20568795410','Agrovet Importaciones S.R.L.','Av. Industrial 445, Lima Cercado','014822369','2020100577@ucss.pe','Activo'),
('20587456912','CanCat Distribuidores S.R.L.','Av. Universitaria 1890, Los Olivos','014125879','2021100277@ucss.pe','Activo'),
('20598741230','PetSupplies Perú E.I.R.L.','Av. Tupac Amaru 4800, Comas','014825793','2020100944@ucss.pe','Activo'),
('20651247895','EcoPet Natural S.A.C.','Av. Los Alisos 760, Los Olivos','014932015','2020102007@ucss.pe','Activo'),
('20652369841','Distribuidora Animalia S.A.C.','Jr. Cajamarca 215, San Martín de Porres','014763218','2021102518@ucss.pe','Activo'),
('20654123987','Pet Nutrition S.A.C.','Av. Argentina 2450, Callao','014563210','2020100577@ucss.pe','Activo'),
('20654789563','PetHouse Distribuciones S.A.C.','Av. Perú 1587, San Martín de Porres','014532014','2021100277@ucss.pe','Activo'),
('20657412308','HappyPet Corporation S.A.','Av. Angélica Gamarra 720, Los Olivos','014653297','2020100944@ucss.pe','Activo'),
('20658432109','Mascotienda Perú E.I.R.L.','Jr. Los Pinos 430, Comas','014362158','2020102007@ucss.pe','Activo'),
('20659874102','Vetamarket Perú S.A.','Av. Carlos Izaguirre 1025, Independencia','014712365','2021102518@ucss.pe','Activo');

/*!40000 ALTER TABLE `t17catalogoproveedor` ENABLE KEYS */;

-- ----------------------------------------------------------------------------------------------

SET SQL_SAFE_UPDATES = 0;
-- ------------------------------------------------------------
-- Limpiar tabla
DELETE FROM t99_proveedores_productos;

-- Asignar a cada producto un proveedor aleatorio garantizado
INSERT INTO t99_proveedores_productos (Id_NumRuc, Id_Producto)
SELECT 
  (SELECT Id_NumRuc FROM t17catalogoproveedor ORDER BY RAND() LIMIT 1),
  p.Id_Producto
FROM 
  t18catalogoproducto p;

-- Opcional: agregar proveedores adicionales aleatorios
INSERT INTO t99_proveedores_productos (Id_NumRuc, Id_Producto)
SELECT 
    pr.Id_NumRuc,
    p.Id_Producto
FROM 
    t17catalogoproveedor pr
JOIN 
    t18catalogoproducto p
WHERE 
    RAND() < 0.15;  -- 15% de relaciones extra

-- ------------------------------------------------------------
SET SQL_SAFE_UPDATES = 1;
-- ----------------------------------------------------------------------------------------------




