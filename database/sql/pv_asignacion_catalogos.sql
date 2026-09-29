-- Catálogos ligeros para Asignaciones PV (clientes / productos)
-- Ejecutar en la BD de producción (ej. austin2)

CREATE TABLE IF NOT EXISTS `tbl_pv_cliente_catalogo` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa` VARCHAR(40) NOT NULL,
  `codigo` VARCHAR(40) NOT NULL,
  `nombre` VARCHAR(180) NULL,
  `anio` SMALLINT UNSIGNED NULL,
  `origen` VARCHAR(20) NOT NULL DEFAULT 'sap',
  `synced_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pv_cli_cat_unica` (`empresa`, `codigo`),
  KEY `pv_cli_cat_emp_anio` (`empresa`, `anio`),
  KEY `pv_cli_cat_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_pv_producto_cliente_catalogo` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa` VARCHAR(40) NOT NULL,
  `cliente_codigo` VARCHAR(40) NOT NULL,
  `codigo` VARCHAR(80) NOT NULL,
  `nombre` VARCHAR(220) NULL,
  `grupo` VARCHAR(80) NULL,
  `costo` DECIMAL(18,6) NOT NULL DEFAULT 0.000000,
  `anio` SMALLINT UNSIGNED NULL,
  `origen` VARCHAR(20) NOT NULL DEFAULT 'sap',
  `synced_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pv_prod_cli_cat_unica` (`empresa`, `cliente_codigo`, `codigo`),
  KEY `pv_prod_cli_cat_emp_cli` (`empresa`, `cliente_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
