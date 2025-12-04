
use mundo_patitas3;
-- ---------------------------------------------------------------------------------------


DROP TABLE IF EXISTS t28Nota_caja;

CREATE TABLE t28Nota_caja (
    IDNotaCaja INT NOT NULL AUTO_INCREMENT,
    IDResponsableCaja INT NOT NULL,
    IDRepartidor INT NOT NULL,
    IDAsignacionReparto INT NOT NULL,
    TotalContraEntrega INT NOT NULL DEFAULT 0,
    VueltoTotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    FechaEmision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    RutaPDF VARCHAR(255) NULL,
    Estado VARCHAR(20) NOT NULL DEFAULT 'Entregado',

    PRIMARY KEY (IDNotaCaja),

    CONSTRAINT fk_t28_responsable 
        FOREIGN KEY (IDResponsableCaja) 
        REFERENCES t16catalogotrabajadores(id_Trabajador),

    CONSTRAINT fk_t28_repartidor 
        FOREIGN KEY (IDRepartidor) 
        REFERENCES t16catalogotrabajadores(id_Trabajador),

    CONSTRAINT fk_t28_asignacion 
        FOREIGN KEY (IDAsignacionReparto) 
        REFERENCES t40ordenasignacionreparto(Id_OrdenAsignacion)
);





