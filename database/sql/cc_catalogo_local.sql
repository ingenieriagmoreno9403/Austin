-- Catálogo local de Centros de Costo (centros / cuentas)
-- Uso: lectura rápida en /ControlCentros y Admin/Asignar.
-- El botón «Actualizar catálogo desde SAP» reescribe SOLO estas tablas
-- (nombres, departamentos, grupos). NO toca presupuesto capturado
-- (tbl_cc_presupuestos) ni asignaciones.
--
-- Ejecutar en la BD de la aplicación (ej. austin2).

CREATE TABLE IF NOT EXISTS `tbl_cc_catalogo_centros` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa` VARCHAR(40) NOT NULL,
  `codigo` VARCHAR(40) NOT NULL,
  `nombre` VARCHAR(180) NULL,
  `departamento` VARCHAR(80) NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `origen` VARCHAR(20) NOT NULL DEFAULT 'sap',
  `synced_at` TIMESTAMP NULL DEFAULT NULL,
  `synced_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cc_cat_centros_unica` (`empresa`, `codigo`),
  KEY `cc_cat_centros_emp` (`empresa`),
  KEY `cc_cat_centros_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_cc_catalogo_cuentas` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa` VARCHAR(40) NOT NULL,
  `codigo` VARCHAR(40) NOT NULL,
  `nombre` VARCHAR(180) NULL,
  `grupo` VARCHAR(80) NULL,
  `grupo_id` VARCHAR(40) NULL,
  `origen` VARCHAR(20) NOT NULL DEFAULT 'sap',
  `synced_at` TIMESTAMP NULL DEFAULT NULL,
  `synced_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cc_cat_cuentas_unica` (`empresa`, `codigo`),
  KEY `cc_cat_cuentas_emp` (`empresa`),
  KEY `cc_cat_cuentas_grupo` (`empresa`, `grupo_id`),
  KEY `cc_cat_cuentas_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_cc_catalogo_sync` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa` VARCHAR(40) NOT NULL,
  `centros` INT UNSIGNED NOT NULL DEFAULT 0,
  `cuentas` INT UNSIGNED NOT NULL DEFAULT 0,
  `ok` TINYINT(1) NOT NULL DEFAULT 0,
  `mensaje` VARCHAR(255) NULL,
  `origen` VARCHAR(20) NOT NULL DEFAULT 'sap',
  `synced_at` TIMESTAMP NULL DEFAULT NULL,
  `synced_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cc_cat_sync_unica` (`empresa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
