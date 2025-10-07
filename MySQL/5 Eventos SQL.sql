use mundo_patitas2;

SHOW VARIABLES LIKE 'event_scheduler';
SET GLOBAL event_scheduler = ON;

CREATE INDEX ix_orden_estado_fecha ON t02OrdenPedido (Estado, Fecha);

-- Marca como Vencido cualquier pedido que siga en 'Generada' pasado el umbral
CREATE EVENT IF NOT EXISTS ev_ordenes_vencidas
ON SCHEDULE EVERY 10 MINUTE
DO
  UPDATE t02OrdenPedido
     SET Estado = 'Vencido'
   WHERE Estado = 'Generada'
     AND Fecha  < NOW() - INTERVAL 24 HOUR;

