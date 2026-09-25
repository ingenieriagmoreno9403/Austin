-- =============================================================================
-- Cambios de base de datos: 17-sep-2026 → 20-sep-2026
-- Módulo: Proyecciones de Ventas (tbl_pv_*)
-- Idempotente (seguro re-ejecutar).
-- =============================================================================

SET @db := DATABASE();

-- -----------------------------------------------------------------------------
-- 17/09/2026 — tipo_cambio_meses en ciclos
-- Migración: 2026_09_17_200000_add_tipo_cambio_meses_to_pv_ciclos
-- Guarda el TC (MXN/USD) por cada mes del ciclo (JSON de 12 valores).
-- -----------------------------------------------------------------------------
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'tbl_pv_ciclos'
    AND COLUMN_NAME = 'tipo_cambio_meses'
);
SET @sql := IF(
  @exists = 0
    AND EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tbl_pv_ciclos'),
  'ALTER TABLE `tbl_pv_ciclos` ADD COLUMN `tipo_cambio_meses` JSON NULL AFTER `tipo_cambio`',
  'SELECT ''[skip] tbl_pv_ciclos.tipo_cambio_meses'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 17/09/2026 — precio_meses en proyecciones
-- Migración: 2026_09_17_221000_add_precio_meses_to_pv_proyecciones
-- Guarda override de precio unitario por mes (JSON de 12 valores).
-- -----------------------------------------------------------------------------
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'tbl_pv_proyecciones'
    AND COLUMN_NAME = 'precio_meses'
);
SET @sql := IF(
  @exists = 0
    AND EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tbl_pv_proyecciones'),
  'ALTER TABLE `tbl_pv_proyecciones` ADD COLUMN `precio_meses` JSON NULL AFTER `costo_unitario`',
  'SELECT ''[skip] tbl_pv_proyecciones.precio_meses'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Compatibilidad con nombre antiguo (si aún no se renombró)
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'tbl_pv_presupuestos'
    AND COLUMN_NAME = 'precio_meses'
);
SET @sql := IF(
  @exists = 0
    AND EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tbl_pv_presupuestos'),
  'ALTER TABLE `tbl_pv_presupuestos` ADD COLUMN `precio_meses` JSON NULL AFTER `costo_unitario`',
  'SELECT ''[skip] tbl_pv_presupuestos.precio_meses'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 18/09/2026 — tipo_budget en ciclos
-- Migración: 2026_09_18_060000_add_tipo_budget_to_pv_ciclos
-- Valores típicos: '3+9', '6+6', '9+3', 'SIOP' (nullable).
-- -----------------------------------------------------------------------------
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'tbl_pv_ciclos'
    AND COLUMN_NAME = 'tipo_budget'
);
SET @sql := IF(
  @exists = 0
    AND EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tbl_pv_ciclos'),
  'ALTER TABLE `tbl_pv_ciclos` ADD COLUMN `tipo_budget` VARCHAR(10) NULL AFTER `tipo_cambio_meses`',
  'SELECT ''[skip] tbl_pv_ciclos.tipo_budget'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- Verificación
-- -----------------------------------------------------------------------------
SELECT
  TABLE_NAME,
  COLUMN_NAME,
  COLUMN_TYPE,
  IS_NULLABLE,
  COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db
  AND (
    (TABLE_NAME = 'tbl_pv_ciclos' AND COLUMN_NAME IN ('tipo_cambio_meses', 'tipo_budget'))
    OR (TABLE_NAME IN ('tbl_pv_proyecciones', 'tbl_pv_presupuestos') AND COLUMN_NAME = 'precio_meses')
  )
ORDER BY TABLE_NAME, ORDINAL_POSITION;
