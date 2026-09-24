-- =============================================================================
-- Ventas → Precios (ruta /Ventas/Costos)
-- Crea: vista de menú, permiso ver_ventas_costos, perfiles Master + Proyecciones,
--       y asignación a usuarios master y Rene.
-- Idempotente: se puede ejecutar más de una vez.
-- =============================================================================

SET @dept_ventas := (
    SELECT id FROM tbldepartamentos WHERE nombre = 'Ventas' LIMIT 1
);
SET @perfil_master := (
    SELECT id FROM tblperfiles WHERE nombre = 'Master' LIMIT 1
);
SET @perfil_proyecciones := (
    SELECT id FROM tblperfiles WHERE nombre = 'Proyecciones de Ventas' LIMIT 1
);
SET @now := NOW();

-- 1) Vista / menú (sidebar: Precios → /Ventas/Costos)
INSERT INTO tblvistas (nombre, descripcion, estado, iddepartamento, orden, created_at, updated_at)
SELECT 'Precios', 'Ventas/Costos', 1, @dept_ventas, 4, @now, @now
WHERE @dept_ventas IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tblvistas WHERE descripcion = 'Ventas/Costos'
  );

UPDATE tblvistas
SET nombre = 'Precios',
    iddepartamento = @dept_ventas,
    orden = 4,
    estado = 1,
    updated_at = @now
WHERE descripcion = 'Ventas/Costos';

SET @vista_id := (
    SELECT id FROM tblvistas WHERE descripcion = 'Ventas/Costos' LIMIT 1
);

-- 2) Permiso / acción
INSERT INTO tblacciones (nombre_accion, descripcion_accion, idvista, created_at, updated_at, created_by)
SELECT 'ver_ventas_costos', 'Ver Ventas / Costos (Precios de productos)', @vista_id, @now, @now, 'sql'
WHERE @vista_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tblacciones WHERE nombre_accion = 'ver_ventas_costos'
  );

UPDATE tblacciones
SET idvista = @vista_id,
    descripcion_accion = 'Ver Ventas / Costos (Precios de productos)',
    updated_at = @now
WHERE nombre_accion = 'ver_ventas_costos';

SET @accion_id := (
    SELECT id FROM tblacciones WHERE nombre_accion = 'ver_ventas_costos' LIMIT 1
);

-- 3) Permiso en perfiles (Master + Proyecciones de Ventas)
INSERT INTO tblperfil_acciones (idperfil, idaccion, created_at, updated_at)
SELECT @perfil_master, @accion_id, @now, @now
WHERE @perfil_master IS NOT NULL
  AND @accion_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tblperfil_acciones
      WHERE idperfil = @perfil_master AND idaccion = @accion_id
  );

INSERT INTO tblperfil_acciones (idperfil, idaccion, created_at, updated_at)
SELECT @perfil_proyecciones, @accion_id, @now, @now
WHERE @perfil_proyecciones IS NOT NULL
  AND @accion_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tblperfil_acciones
      WHERE idperfil = @perfil_proyecciones AND idaccion = @accion_id
  );

-- 4) Permiso directo a usuarios master y Rene
INSERT INTO tblusuario_acciones (idacciones, idusuario, created_at, updated_at, created_by)
SELECT @accion_id, u.id, @now, @now, 'sql'
FROM users u
WHERE u.name IN ('master', 'Rene')
  AND @accion_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tblusuario_acciones ua
      WHERE ua.idusuario = u.id AND ua.idacciones = @accion_id
  );

-- 5) Pantalla en menú por usuario (opcional / legado)
INSERT INTO tblusuario_pantallas (idusuario, idvista, iddepartamento, estado, created_at, updated_at, created_by)
SELECT u.id, @vista_id, @dept_ventas, 'A', @now, @now, 'sql'
FROM users u
WHERE u.name IN ('master', 'Rene')
  AND @vista_id IS NOT NULL
  AND @dept_ventas IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tblusuario_pantallas up
      WHERE up.idusuario = u.id AND up.idvista = @vista_id
  );

-- 6) Asegurar perfil Proyecciones de Ventas en esos usuarios
INSERT INTO tblusuario_perfiles (id_perfil, id_usuario, created_at, updated_at)
SELECT @perfil_proyecciones, u.id, @now, @now
FROM users u
WHERE u.name IN ('master', 'Rene')
  AND @perfil_proyecciones IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tblusuario_perfiles up
      WHERE up.id_usuario = u.id AND up.id_perfil = @perfil_proyecciones
  );

-- 7) Restaurar perfiles típicos de master (solo INSERT, no borra nada)
SET @perfil_tesoreria := (SELECT id FROM tblperfiles WHERE nombre = 'Tesorería' LIMIT 1);
SET @perfil_almacenista := (SELECT id FROM tblperfiles WHERE nombre = 'Almacenista' LIMIT 1);

INSERT INTO tblusuario_perfiles (id_perfil, id_usuario, created_at, updated_at)
SELECT @perfil_tesoreria, u.id, @now, @now
FROM users u
WHERE u.name = 'master'
  AND @perfil_tesoreria IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tblusuario_perfiles up
      WHERE up.id_usuario = u.id AND up.id_perfil = @perfil_tesoreria
  );

INSERT INTO tblusuario_perfiles (id_perfil, id_usuario, created_at, updated_at)
SELECT @perfil_almacenista, u.id, @now, @now
FROM users u
WHERE u.name = 'master'
  AND @perfil_almacenista IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tblusuario_perfiles up
      WHERE up.id_usuario = u.id AND up.id_perfil = @perfil_almacenista
  );

-- Verificación
SELECT v.id AS vista_id, v.nombre, v.descripcion, v.orden, v.estado
FROM tblvistas v
WHERE v.descripcion = 'Ventas/Costos';

SELECT a.id AS accion_id, a.nombre_accion, a.descripcion_accion, a.idvista
FROM tblacciones a
WHERE a.nombre_accion = 'ver_ventas_costos';

SELECT p.nombre AS perfil, a.nombre_accion
FROM tblperfil_acciones pa
JOIN tblperfiles p ON p.id = pa.idperfil
JOIN tblacciones a ON a.id = pa.idaccion
WHERE a.nombre_accion = 'ver_ventas_costos';

SELECT u.name AS usuario, a.nombre_accion
FROM tblusuario_acciones ua
JOIN users u ON u.id = ua.idusuario
JOIN tblacciones a ON a.id = ua.idacciones
WHERE a.nombre_accion = 'ver_ventas_costos';
