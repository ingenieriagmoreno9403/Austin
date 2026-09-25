-- =============================================================================
-- Últimos cambios BD — Proyecciones de Ventas (sep 2026)
-- Idempotente: se puede re-ejecutar sin error si las columnas ya existen.
-- =============================================================================

-- 1) TC mensual por ciclo (JSON de 12 tasas) — 2026_09_17_200000
SET @db := DATABASE();

SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tbl_pv_ciclos' AND COLUMN_NAME = 'tipo_cambio_meses'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE `tbl_pv_ciclos` ADD COLUMN `tipo_cambio_meses` JSON NULL AFTER `tipo_cambio`',
  'SELECT ''tbl_pv_ciclos.tipo_cambio_meses ya existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Precio por mes en proyección — 2026_09_17_221000
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tbl_pv_proyecciones' AND COLUMN_NAME = 'precio_meses'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE `tbl_pv_proyecciones` ADD COLUMN `precio_meses` JSON NULL AFTER `costo_unitario`',
  'SELECT ''tbl_pv_proyecciones.precio_meses ya existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Compat: si aún existe la tabla antigua
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tbl_pv_presupuestos' AND COLUMN_NAME = 'precio_meses'
);
SET @sql := IF(@exists = 0 AND EXISTS(
    SELECT 1 FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tbl_pv_presupuestos'
  ),
  'ALTER TABLE `tbl_pv_presupuestos` ADD COLUMN `precio_meses` JSON NULL AFTER `costo_unitario`',
  'SELECT ''tbl_pv_presupuestos.precio_meses omitido o ya existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Tipo de budget del ciclo (3+9 / 6+6 / 9+3 / SIOP) — 2026_09_18_060000
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tbl_pv_ciclos' AND COLUMN_NAME = 'tipo_budget'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE `tbl_pv_ciclos` ADD COLUMN `tipo_budget` VARCHAR(10) NULL AFTER `tipo_cambio_meses`',
  'SELECT ''tbl_pv_ciclos.tipo_budget ya existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Verificación rápida
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_TYPE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db
  AND (
    (TABLE_NAME = 'tbl_pv_ciclos' AND COLUMN_NAME IN ('tipo_cambio_meses', 'tipo_budget'))
    OR (TABLE_NAME = 'tbl_pv_proyecciones' AND COLUMN_NAME = 'precio_meses')
    OR (TABLE_NAME = 'tbl_pv_presupuestos' AND COLUMN_NAME = 'precio_meses')
  )
ORDER BY TABLE_NAME, ORDINAL_POSITION;
