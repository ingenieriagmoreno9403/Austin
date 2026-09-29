-- =============================================================================
-- PRODUCCIÓN — Permiso: Editar precios por mes en Captura
-- Acción: editar_precios_captura
-- Pantalla: Captura e Indicadores (Ventas/Captura)
-- Solo asignación individual (Agregar acción puntual). Idempotente.
-- =============================================================================

SET @vista_id := (
    SELECT id FROM tblvistas WHERE descripcion = 'Ventas/Captura' LIMIT 1
);
SET @now := NOW();

INSERT INTO tblacciones (nombre_accion, descripcion_accion, idvista, created_at, updated_at, created_by)
SELECT 'editar_precios_captura', 'Editar precios por mes (Captura)', @vista_id, @now, @now, 'sql'
WHERE @vista_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tblacciones WHERE nombre_accion = 'editar_precios_captura'
  );

UPDATE tblacciones
SET idvista = @vista_id,
    descripcion_accion = 'Editar precios por mes (Captura)',
    updated_at = @now
WHERE nombre_accion = 'editar_precios_captura';

SET @accion_id := (
    SELECT id FROM tblacciones WHERE nombre_accion = 'editar_precios_captura' LIMIT 1
);

-- No ligar a perfiles
DELETE FROM tblperfil_acciones
WHERE @accion_id IS NOT NULL
  AND idaccion = @accion_id;

-- Verificación
SELECT a.id AS accion_id, a.nombre_accion, a.descripcion_accion, v.nombre AS vista
FROM tblacciones a
LEFT JOIN tblvistas v ON v.id = a.idvista
WHERE a.nombre_accion = 'editar_precios_captura';
