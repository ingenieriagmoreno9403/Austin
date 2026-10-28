-- Remap CC: consecutivos viejos → FormatCode SAP (nombre único o único 6000)
-- + indemnizaciones 879/945 → 600060001013 y 600060001024
-- phpMyAdmin → Importar este archivo (o pegar y Ejecutar).
-- Usa el catálogo de ESTA base (tbl_cc_catalogo_cuentas).

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
  IF(UPPER(TRIM(empresa)) = 'ABSA', 'AUSTIN', UPPER(TRIM(empresa))),
  TRIM(REGEXP_REPLACE(REGEXP_REPLACE(
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
      UPPER(TRIM(COALESCE(nombre, ''))),
      'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ü','U'),'Ñ','N'),
    '[^A-Z0-9]+', ' '), ' +', ' ')),
  TRIM(codigo)
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

-- ===== Asignaciones =====
DROP TABLE IF EXISTS tmp_cc_asig_map;
CREATE TABLE tmp_cc_asig_map (
  id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  asignacion_id BIGINT UNSIGNED NOT NULL,
  codigo_sap VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_cc_asig_map (id, asignacion_id, codigo_sap)
SELECT c.id, c.asignacion_id, m.codigo_sap
FROM tbl_cc_asignacion_cuentas c
INNER JOIN tbl_cc_asignaciones a ON a.id = c.asignacion_id
INNER JOIN tmp_cc_map m
  ON m.empresa = (IF(UPPER(TRIM(a.empresa)) = 'ABSA', 'AUSTIN', UPPER(TRIM(a.empresa))) COLLATE utf8mb4_unicode_ci)
 AND m.nombre_norm = (TRIM(REGEXP_REPLACE(REGEXP_REPLACE(
        REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
          UPPER(TRIM(COALESCE(c.cuenta_nombre, ''))),
          'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ü','U'),'Ñ','N'),
        '[^A-Z0-9]+', ' '), ' +', ' ')) COLLATE utf8mb4_unicode_ci)
WHERE m.codigo_sap <> (TRIM(c.cuenta_codigo) COLLATE utf8mb4_unicode_ci)
  AND (
    c.cuenta_codigo LIKE '%SYS%'
    OR (c.cuenta_codigo REGEXP '^[0-9]+$' AND CHAR_LENGTH(TRIM(c.cuenta_codigo)) < 8)
  );

DELETE c
FROM tbl_cc_asignacion_cuentas c
INNER JOIN tmp_cc_asig_map t ON t.id = c.id
INNER JOIN tbl_cc_asignacion_cuentas sap
  ON sap.asignacion_id = t.asignacion_id
 AND sap.cuenta_codigo = (t.codigo_sap COLLATE utf8mb4_unicode_ci)
 AND sap.id <> c.id;

UPDATE tbl_cc_asignacion_cuentas c
INNER JOIN tmp_cc_asig_map t ON t.id = c.id
LEFT JOIN tbl_cc_asignacion_cuentas sap
  ON sap.asignacion_id = t.asignacion_id
 AND sap.cuenta_codigo = (t.codigo_sap COLLATE utf8mb4_unicode_ci)
 AND sap.id <> c.id
SET c.cuenta_codigo = t.codigo_sap, c.updated_at = NOW()
WHERE sap.id IS NULL;

-- ===== Presupuestos =====
DROP TABLE IF EXISTS tmp_cc_ppto_map;
CREATE TABLE tmp_cc_ppto_map (
  id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  ciclo_codigo VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  empresa VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  centro_codigo VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  codigo_sap VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_cc_ppto_map (id, ciclo_codigo, empresa, centro_codigo, codigo_sap)
SELECT p.id, p.ciclo_codigo, p.empresa, p.centro_codigo, m.codigo_sap
FROM tbl_cc_presupuestos p
INNER JOIN tmp_cc_map m
  ON m.empresa = (IF(UPPER(TRIM(p.empresa)) = 'ABSA', 'AUSTIN', UPPER(TRIM(p.empresa))) COLLATE utf8mb4_unicode_ci)
 AND m.nombre_norm = (TRIM(REGEXP_REPLACE(REGEXP_REPLACE(
        REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
          UPPER(TRIM(COALESCE(p.cuenta_nombre, ''))),
          'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ü','U'),'Ñ','N'),
        '[^A-Z0-9]+', ' '), ' +', ' ')) COLLATE utf8mb4_unicode_ci)
WHERE m.codigo_sap <> (TRIM(p.cuenta_codigo) COLLATE utf8mb4_unicode_ci)
  AND (
    p.cuenta_codigo LIKE '%SYS%'
    OR (p.cuenta_codigo REGEXP '^[0-9]+$' AND CHAR_LENGTH(TRIM(p.cuenta_codigo)) < 8)
  );

UPDATE tbl_cc_presupuestos sap
INNER JOIN tmp_cc_ppto_map t ON t.ciclo_codigo = sap.ciclo_codigo
  AND t.empresa = sap.empresa
  AND t.centro_codigo = sap.centro_codigo
  AND t.codigo_sap = (sap.cuenta_codigo COLLATE utf8mb4_unicode_ci)
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
  ON sap.ciclo_codigo = t.ciclo_codigo
 AND sap.empresa = t.empresa
 AND sap.centro_codigo = t.centro_codigo
 AND sap.cuenta_codigo = (t.codigo_sap COLLATE utf8mb4_unicode_ci)
 AND sap.id <> p.id;

UPDATE tbl_cc_presupuestos p
INNER JOIN tmp_cc_ppto_map t ON t.id = p.id
LEFT JOIN tbl_cc_presupuestos sap
  ON sap.ciclo_codigo = t.ciclo_codigo
 AND sap.empresa = t.empresa
 AND sap.centro_codigo = t.centro_codigo
 AND sap.cuenta_codigo = (t.codigo_sap COLLATE utf8mb4_unicode_ci)
 AND sap.id <> p.id
SET p.cuenta_codigo = t.codigo_sap, p.updated_at = NOW()
WHERE sap.id IS NULL;

-- ===== Grupos =====
DROP TABLE IF EXISTS tmp_cc_grupo_map;
CREATE TABLE tmp_cc_grupo_map (
  id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  grupo_id BIGINT UNSIGNED NOT NULL,
  codigo_sap VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_cc_grupo_map (id, grupo_id, codigo_sap)
SELECT i.id, i.grupo_id, m.codigo_sap
FROM tbl_cc_grupo_cuentas i
INNER JOIN tbl_cc_grupos_cuenta g ON g.id = i.grupo_id
INNER JOIN tmp_cc_map m
  ON m.empresa = (IF(UPPER(TRIM(g.empresa)) = 'ABSA', 'AUSTIN', UPPER(TRIM(g.empresa))) COLLATE utf8mb4_unicode_ci)
 AND m.nombre_norm = (TRIM(REGEXP_REPLACE(REGEXP_REPLACE(
        REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
          UPPER(TRIM(COALESCE(i.cuenta_nombre, ''))),
          'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ü','U'),'Ñ','N'),
        '[^A-Z0-9]+', ' '), ' +', ' ')) COLLATE utf8mb4_unicode_ci)
WHERE m.codigo_sap <> (TRIM(i.cuenta_codigo) COLLATE utf8mb4_unicode_ci)
  AND (
    i.cuenta_codigo LIKE '%SYS%'
    OR (i.cuenta_codigo REGEXP '^[0-9]+$' AND CHAR_LENGTH(TRIM(i.cuenta_codigo)) < 8)
  );

DELETE i
FROM tbl_cc_grupo_cuentas i
INNER JOIN tmp_cc_grupo_map t ON t.id = i.id
INNER JOIN tbl_cc_grupo_cuentas sap
  ON sap.grupo_id = t.grupo_id
 AND sap.cuenta_codigo = (t.codigo_sap COLLATE utf8mb4_unicode_ci)
 AND sap.id <> i.id;

UPDATE tbl_cc_grupo_cuentas i
INNER JOIN tmp_cc_grupo_map t ON t.id = i.id
LEFT JOIN tbl_cc_grupo_cuentas sap
  ON sap.grupo_id = t.grupo_id
 AND sap.cuenta_codigo = (t.codigo_sap COLLATE utf8mb4_unicode_ci)
 AND sap.id <> i.id
SET i.cuenta_codigo = t.codigo_sap, i.updated_at = NOW()
WHERE sap.id IS NULL;

-- ===== Indemnizaciones: el par, no adivinar 879 vs 945 =====
INSERT INTO tbl_cc_asignacion_cuentas (asignacion_id, cuenta_codigo, cuenta_nombre, agrupacion, created_at, updated_at)
SELECT asignacion_id, codigo, 'INDEMNIZACIONES', 'Gastos', NOW(), NOW()
FROM (
  SELECT DISTINCT c.asignacion_id, v.codigo
  FROM tbl_cc_asignacion_cuentas c
  CROSS JOIN (
    SELECT '600060001013' AS codigo
    UNION ALL
    SELECT '600060001024'
  ) v
  WHERE c.cuenta_codigo IN ('879', '945')
) t
WHERE NOT EXISTS (
  SELECT 1 FROM tbl_cc_asignacion_cuentas x
  WHERE x.asignacion_id = t.asignacion_id AND x.cuenta_codigo = t.codigo
);

DELETE FROM tbl_cc_asignacion_cuentas WHERE cuenta_codigo IN ('879', '945');

INSERT INTO tbl_cc_grupo_cuentas (grupo_id, cuenta_codigo, cuenta_nombre, created_at, updated_at)
SELECT grupo_id, codigo, 'INDEMNIZACIONES', NOW(), NOW()
FROM (
  SELECT DISTINCT i.grupo_id, v.codigo
  FROM tbl_cc_grupo_cuentas i
  CROSS JOIN (
    SELECT '600060001013' AS codigo
    UNION ALL
    SELECT '600060001024'
  ) v
  WHERE i.cuenta_codigo IN ('879', '945')
) t
WHERE NOT EXISTS (
  SELECT 1 FROM tbl_cc_grupo_cuentas x
  WHERE x.grupo_id = t.grupo_id AND x.cuenta_codigo = t.codigo
);

DELETE FROM tbl_cc_grupo_cuentas WHERE cuenta_codigo IN ('879', '945');

DROP TABLE tmp_cc_asig_map;
DROP TABLE tmp_cc_ppto_map;
DROP TABLE tmp_cc_grupo_map;
DROP TABLE tmp_cc_map;
DROP TABLE tmp_cc_cat_hits;

COMMIT;

-- Revisión (debe dar 0 cortos de 879/945)
SELECT 'asig_cortos' AS q, COUNT(*) n
FROM tbl_cc_asignacion_cuentas
WHERE cuenta_codigo REGEXP '^[0-9]+$' AND CHAR_LENGTH(TRIM(cuenta_codigo)) < 8
UNION ALL
SELECT 'asig_879_945', COUNT(*)
FROM tbl_cc_asignacion_cuentas
WHERE cuenta_codigo IN ('879', '945')
UNION ALL
SELECT 'asig_1013', COUNT(*)
FROM tbl_cc_asignacion_cuentas
WHERE cuenta_codigo = '600060001013'
UNION ALL
SELECT 'asig_1024', COUNT(*)
FROM tbl_cc_asignacion_cuentas
WHERE cuenta_codigo = '600060001024';
