-- =============================================================================
-- Permiso: editar_ventas_costos (solo asignación individual)
-- Crea la acción bajo la vista Ventas/Costos.
-- NO la liga a perfiles: se asigna en "Agregar acción puntual".
-- Idempotente.
-- =============================================================================

SET @dept_ventas := (
    SELECT id FROM tbldepartamentos WHERE nombre = 'Ventas' LIMIT 1
);
SET @vista_id := (
    SELECT id FROM tblvistas WHERE descripcion = 'Ventas/Costos' LIMIT 1
);
SET @now := NOW();

INSERT INTO tblvistas (nombre, descripcion, estado, iddepartamento, orden, created_at, updated_at)
SELECT 'Precios', 'Ventas/Costos', 1, @dept_ventas, 4, @now, @now
WHERE @dept_ventas IS NOT NULL
  AND @vista_id IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM tblvistas WHERE descripcion = 'Ventas/Costos'
  );

SET @vista_id := (
    SELECT id FROM tblvistas WHERE descripcion = 'Ventas/Costos' LIMIT 1
);

INSERT INTO tblacciones (nombre_accion, descripcion_accion, idvista, created_at, updated_at, created_by)
SELECT 'editar_ventas_costos', 'Editar precios de productos', @vista_id, @now, @now, 'sql'
WHERE @vista_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tblacciones WHERE nombre_accion = 'editar_ventas_costos'
  );

UPDATE tblacciones
SET idvista = @vista_id,
    descripcion_accion = 'Editar precios de productos',
    updated_at = @now
WHERE nombre_accion = 'editar_ventas_costos';

SET @accion_id := (
    SELECT id FROM tblacciones WHERE nombre_accion = 'editar_ventas_costos' LIMIT 1
);

-- Quitar de cualquier perfil (solo acción puntual)
DELETE FROM tblperfil_acciones
WHERE @accion_id IS NOT NULL
  AND idaccion = @accion_id;

-- Incluir Precios en módulos de empresas que ya tienen Ventas
INSERT INTO tblempresa_vistas (id_empresa, id_vista, created_at, updated_at)
SELECT DISTINCT ev.id_empresa, @vista_id, @now, @now
FROM tblempresa_vistas ev
INNER JOIN tblvistas v ON v.id = ev.id_vista
INNER JOIN tbldepartamentos d ON d.id = v.iddepartamento
WHERE d.nombre = 'Ventas'
  AND v.descripcion <> 'Ventas/Costos'
  AND @vista_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tblempresa_vistas x
      WHERE x.id_empresa = ev.id_empresa AND x.id_vista = @vista_id
  );

SELECT 'ok' AS status, @vista_id AS vista_id, @accion_id AS accion_id;
