-- Remap de TODAS las cuentas cortas con nombre único en el catálogo SAP,
-- o con una sola cuenta de gasto (6000…).
-- No toca INDEMNIZACIONES 879/945 (hay dos FormatCode y no se adivina).
-- phpMyAdmin → Importar este archivo.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

DROP TABLE IF EXISTS tbl_cc_asignacion_cuentas_bak_remap;
DROP TABLE IF EXISTS tbl_cc_presupuestos_bak_remap;
DROP TABLE IF EXISTS tbl_cc_grupo_cuentas_bak_remap;
CREATE TABLE tbl_cc_asignacion_cuentas_bak_remap AS SELECT * FROM tbl_cc_asignacion_cuentas;
CREATE TABLE tbl_cc_presupuestos_bak_remap AS SELECT * FROM tbl_cc_presupuestos;
CREATE TABLE tbl_cc_grupo_cuentas_bak_remap AS SELECT * FROM tbl_cc_grupo_cuentas;

START TRANSACTION;

DROP TABLE IF EXISTS tmp_cc_cat_hits;
CREATE TABLE tmp_cc_cat_hits (
  empresa VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  nombre_norm VARCHAR(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  codigo VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  UNIQUE KEY uq_cc_hit (empresa, nombre_norm, codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO tmp_cc_cat_hits (empresa, nombre_norm, codigo)
SELECT
  CONVERT(IF(UPPER(TRIM(empresa)) = 'ABSA', 'AUSTIN', UPPER(TRIM(empresa))) USING utf8mb4) COLLATE utf8mb4_unicode_ci,
  CONVERT(TRIM(REGEXP_REPLACE(REGEXP_REPLACE(
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
      UPPER(TRIM(COALESCE(nombre, ''))),
      'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ü','U'),'Ñ','N'),
    '[^A-Z0-9]+', ' '), ' +', ' ')) USING utf8mb4) COLLATE utf8mb4_unicode_ci,
  CONVERT(TRIM(codigo) USING utf8mb4) COLLATE utf8mb4_unicode_ci
FROM tbl_cc_catalogo_cuentas
WHERE TRIM(COALESCE(nombre, '')) <> ''
  AND TRIM(COALESCE(codigo, '')) <> '';

DROP TABLE IF EXISTS tmp_cc_map;
CREATE TABLE tmp_cc_map (
  empresa VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  nombre_norm VARCHAR(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  codigo_sap VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (empresa, nombre_norm)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_cc_map (empresa, nombre_norm, codigo_sap)
SELECT empresa, nombre_norm, codigo_sap
FROM (
  SELECT
    empresa,
    nombre_norm,
    CASE
      WHEN COUNT(*) = 1 THEN MIN(codigo)
      WHEN SUM(codigo LIKE '6000%') = 1 THEN MAX(CASE WHEN codigo LIKE '6000%' THEN codigo END)
      ELSE NULL
    END AS codigo_sap
  FROM tmp_cc_cat_hits
  GROUP BY empresa, nombre_norm
) x
WHERE codigo_sap IS NOT NULL;

DROP TABLE IF EXISTS tmp_cc_asig_src;
CREATE TABLE tmp_cc_asig_src (
  id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  asignacion_id BIGINT UNSIGNED NOT NULL,
  empresa VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  nombre_norm VARCHAR(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  cuenta_codigo VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  KEY idx_emp_nom (empresa, nombre_norm)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_cc_asig_src (id, asignacion_id, empresa, nombre_norm, cuenta_codigo)
SELECT
  c.id,
  c.asignacion_id,
  CONVERT(IF(UPPER(TRIM(a.empresa)) = 'ABSA', 'AUSTIN', UPPER(TRIM(a.empresa))) USING utf8mb4) COLLATE utf8mb4_unicode_ci,
  CONVERT(TRIM(REGEXP_REPLACE(REGEXP_REPLACE(
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
      UPPER(TRIM(COALESCE(c.cuenta_nombre, ''))),
      'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ü','U'),'Ñ','N'),
    '[^A-Z0-9]+', ' '), ' +', ' ')) USING utf8mb4) COLLATE utf8mb4_unicode_ci,
  CONVERT(TRIM(c.cuenta_codigo) USING utf8mb4) COLLATE utf8mb4_unicode_ci
FROM tbl_cc_asignacion_cuentas c
INNER JOIN tbl_cc_asignaciones a ON a.id = c.asignacion_id
WHERE c.cuenta_codigo LIKE '%SYS%'
   OR (c.cuenta_codigo REGEXP '^[0-9]+$' AND CHAR_LENGTH(TRIM(c.cuenta_codigo)) < 8);

DROP TABLE IF EXISTS tmp_cc_asig_map;
CREATE TABLE tmp_cc_asig_map (
  id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  asignacion_id BIGINT UNSIGNED NOT NULL,
  codigo_sap VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_cc_asig_map (id, asignacion_id, codigo_sap)
SELECT s.id, s.asignacion_id, m.codigo_sap
FROM tmp_cc_asig_src s
INNER JOIN tmp_cc_map m
  ON m.empresa = s.empresa
 AND m.nombre_norm = s.nombre_norm
WHERE m.codigo_sap <> s.cuenta_codigo;

DELETE c
FROM tbl_cc_asignacion_cuentas c
INNER JOIN tmp_cc_asig_map t ON t.id = c.id
INNER JOIN tbl_cc_asignacion_cuentas sap
  ON sap.asignacion_id = t.asignacion_id
 AND CONVERT(sap.cuenta_codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci = t.codigo_sap
 AND sap.id <> c.id;

UPDATE tbl_cc_asignacion_cuentas c
INNER JOIN tmp_cc_asig_map t ON t.id = c.id
LEFT JOIN tbl_cc_asignacion_cuentas sap
  ON sap.asignacion_id = t.asignacion_id
 AND CONVERT(sap.cuenta_codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci = t.codigo_sap
 AND sap.id <> c.id
SET c.cuenta_codigo = t.codigo_sap, c.updated_at = NOW()
WHERE sap.id IS NULL;

DROP TABLE IF EXISTS tmp_cc_ppto_src;
CREATE TABLE tmp_cc_ppto_src (
  id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  ciclo_codigo VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  empresa VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  centro_codigo VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  nombre_norm VARCHAR(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  cuenta_codigo VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  KEY idx_emp_nom (empresa, nombre_norm)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_cc_ppto_src (id, ciclo_codigo, empresa, centro_codigo, nombre_norm, cuenta_codigo)
SELECT
  p.id,
  CONVERT(p.ciclo_codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci,
  CONVERT(IF(UPPER(TRIM(p.empresa)) = 'ABSA', 'AUSTIN', UPPER(TRIM(p.empresa))) USING utf8mb4) COLLATE utf8mb4_unicode_ci,
  CONVERT(p.centro_codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci,
  CONVERT(TRIM(REGEXP_REPLACE(REGEXP_REPLACE(
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
      UPPER(TRIM(COALESCE(p.cuenta_nombre, ''))),
      'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ü','U'),'Ñ','N'),
    '[^A-Z0-9]+', ' '), ' +', ' ')) USING utf8mb4) COLLATE utf8mb4_unicode_ci,
  CONVERT(TRIM(p.cuenta_codigo) USING utf8mb4) COLLATE utf8mb4_unicode_ci
FROM tbl_cc_presupuestos p
WHERE p.cuenta_codigo LIKE '%SYS%'
   OR (p.cuenta_codigo REGEXP '^[0-9]+$' AND CHAR_LENGTH(TRIM(p.cuenta_codigo)) < 8);

DROP TABLE IF EXISTS tmp_cc_ppto_map;
CREATE TABLE tmp_cc_ppto_map (
  id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  ciclo_codigo VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  empresa VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  centro_codigo VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  codigo_sap VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_cc_ppto_map (id, ciclo_codigo, empresa, centro_codigo, codigo_sap)
SELECT s.id, s.ciclo_codigo, s.empresa, s.centro_codigo, m.codigo_sap
FROM tmp_cc_ppto_src s
INNER JOIN tmp_cc_map m
  ON m.empresa = s.empresa
 AND m.nombre_norm = s.nombre_norm
WHERE m.codigo_sap <> s.cuenta_codigo;

UPDATE tbl_cc_presupuestos sap
INNER JOIN tmp_cc_ppto_map t
  ON t.ciclo_codigo = CONVERT(sap.ciclo_codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci
 AND t.empresa = CONVERT(IF(UPPER(TRIM(sap.empresa)) = 'ABSA', 'AUSTIN', UPPER(TRIM(sap.empresa))) USING utf8mb4) COLLATE utf8mb4_unicode_ci
 AND t.centro_codigo = CONVERT(sap.centro_codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci
 AND t.codigo_sap = CONVERT(sap.cuenta_codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci
INNER JOIN tbl_cc_presupuestos corto ON corto.id = t.id
SET
  sap.mes_01 = IF(sap.mes_01 IS NULL, corto.mes_01, sap.mes_01),
  sap.mes_02 = IF(sap.mes_02 IS NULL, corto.mes_02, sap.mes_02),
  sap.mes_03 = IF(sap.mes_03 IS NULL, corto.mes_03, sap.mes_03),
  sap.mes_04 = IF(sap.mes_04 IS NULL, corto.mes_04, sap.mes_04),
  sap.mes_05 = IF(sap.mes_05 IS NULL, corto.mes_05, sap.mes_05),
  sap.mes_06 = IF(sap.mes_06 IS NULL, corto.mes_06, sap.mes_06),
  sap.mes_07 = IF(sap.mes_07 IS NULL, corto.mes_07, sap.mes_07),
  sap.mes_08 = IF(sap.mes_08 IS NULL, corto.mes_08, sap.mes_08),
  sap.mes_09 = IF(sap.mes_09 IS NULL, corto.mes_09, sap.mes_09),
  sap.mes_10 = IF(sap.mes_10 IS NULL, corto.mes_10, sap.mes_10),
  sap.mes_11 = IF(sap.mes_11 IS NULL, corto.mes_11, sap.mes_11),
  sap.mes_12 = IF(sap.mes_12 IS NULL, corto.mes_12, sap.mes_12),
  sap.updated_at = NOW();

DELETE p
FROM tbl_cc_presupuestos p
INNER JOIN tmp_cc_ppto_map t ON t.id = p.id
INNER JOIN tbl_cc_presupuestos sap
  ON t.ciclo_codigo = CONVERT(sap.ciclo_codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci
 AND t.empresa = CONVERT(IF(UPPER(TRIM(sap.empresa)) = 'ABSA', 'AUSTIN', UPPER(TRIM(sap.empresa))) USING utf8mb4) COLLATE utf8mb4_unicode_ci
 AND t.centro_codigo = CONVERT(sap.centro_codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci
 AND t.codigo_sap = CONVERT(sap.cuenta_codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci
 AND sap.id <> p.id;

UPDATE tbl_cc_presupuestos p
INNER JOIN tmp_cc_ppto_map t ON t.id = p.id
LEFT JOIN tbl_cc_presupuestos sap
  ON t.ciclo_codigo = CONVERT(sap.ciclo_codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci
 AND t.empresa = CONVERT(IF(UPPER(TRIM(sap.empresa)) = 'ABSA', 'AUSTIN', UPPER(TRIM(sap.empresa))) USING utf8mb4) COLLATE utf8mb4_unicode_ci
 AND t.centro_codigo = CONVERT(sap.centro_codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci
 AND t.codigo_sap = CONVERT(sap.cuenta_codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci
 AND sap.id <> p.id
SET p.cuenta_codigo = t.codigo_sap, p.updated_at = NOW()
WHERE sap.id IS NULL;

DROP TABLE IF EXISTS tmp_cc_grupo_src;
CREATE TABLE tmp_cc_grupo_src (
  id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  grupo_id BIGINT UNSIGNED NOT NULL,
  empresa VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  nombre_norm VARCHAR(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  cuenta_codigo VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  KEY idx_emp_nom (empresa, nombre_norm)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_cc_grupo_src (id, grupo_id, empresa, nombre_norm, cuenta_codigo)
SELECT
  i.id,
  i.grupo_id,
  CONVERT(IF(UPPER(TRIM(g.empresa)) = 'ABSA', 'AUSTIN', UPPER(TRIM(g.empresa))) USING utf8mb4) COLLATE utf8mb4_unicode_ci,
  CONVERT(TRIM(REGEXP_REPLACE(REGEXP_REPLACE(
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
      UPPER(TRIM(COALESCE(i.cuenta_nombre, ''))),
      'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ü','U'),'Ñ','N'),
    '[^A-Z0-9]+', ' '), ' +', ' ')) USING utf8mb4) COLLATE utf8mb4_unicode_ci,
  CONVERT(TRIM(i.cuenta_codigo) USING utf8mb4) COLLATE utf8mb4_unicode_ci
FROM tbl_cc_grupo_cuentas i
INNER JOIN tbl_cc_grupos_cuenta g ON g.id = i.grupo_id
WHERE i.cuenta_codigo LIKE '%SYS%'
   OR (i.cuenta_codigo REGEXP '^[0-9]+$' AND CHAR_LENGTH(TRIM(i.cuenta_codigo)) < 8);

DROP TABLE IF EXISTS tmp_cc_grupo_map;
CREATE TABLE tmp_cc_grupo_map (
  id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  grupo_id BIGINT UNSIGNED NOT NULL,
  codigo_sap VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_cc_grupo_map (id, grupo_id, codigo_sap)
SELECT s.id, s.grupo_id, m.codigo_sap
FROM tmp_cc_grupo_src s
INNER JOIN tmp_cc_map m
  ON m.empresa = s.empresa
 AND m.nombre_norm = s.nombre_norm
WHERE m.codigo_sap <> s.cuenta_codigo;

DELETE i
FROM tbl_cc_grupo_cuentas i
INNER JOIN tmp_cc_grupo_map t ON t.id = i.id
INNER JOIN tbl_cc_grupo_cuentas sap
  ON sap.grupo_id = t.grupo_id
 AND CONVERT(sap.cuenta_codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci = t.codigo_sap
 AND sap.id <> i.id;

UPDATE tbl_cc_grupo_cuentas i
INNER JOIN tmp_cc_grupo_map t ON t.id = i.id
LEFT JOIN tbl_cc_grupo_cuentas sap
  ON sap.grupo_id = t.grupo_id
 AND CONVERT(sap.cuenta_codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci = t.codigo_sap
 AND sap.id <> i.id
SET i.cuenta_codigo = t.codigo_sap, i.updated_at = NOW()
WHERE sap.id IS NULL;

DROP TABLE IF EXISTS tmp_cc_asig_src;
DROP TABLE IF EXISTS tmp_cc_asig_map;
DROP TABLE IF EXISTS tmp_cc_ppto_src;
DROP TABLE IF EXISTS tmp_cc_ppto_map;
DROP TABLE IF EXISTS tmp_cc_grupo_src;
DROP TABLE IF EXISTS tmp_cc_grupo_map;
DROP TABLE IF EXISTS tmp_cc_map;
DROP TABLE IF EXISTS tmp_cc_cat_hits;

COMMIT;

SELECT 'asig_cortos_unicos_pendientes' AS q, COUNT(*) AS n
FROM tbl_cc_asignacion_cuentas c
INNER JOIN tbl_cc_asignaciones a ON a.id = c.asignacion_id
WHERE c.cuenta_codigo REGEXP '^[0-9]+$'
  AND CHAR_LENGTH(TRIM(c.cuenta_codigo)) < 8
  AND c.cuenta_codigo NOT IN ('879', '945');
