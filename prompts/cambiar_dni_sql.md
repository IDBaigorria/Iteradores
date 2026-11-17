-- ============================================================
-- Fix del DNI de un pasajero (SQL64, tablas
-- `nodo_contexto` y `adyacente_contexto`).
--
-- Reemplazá en TODAS las consultas:
--   :nombre_app   → 'AdministradorDeViajes'
--   :nombre_dueno → 'Parroquia_del_Carmen'
--   :dni_mal      → el DNI viejo/mal (sin puntos)
--   :dni_bien     → el DNI nuevo (sin puntos)
--
-- Corré los SELECT primero, verificá, después los UPDATE.
-- ============================================================

SET @nombre_app   = 'AdministradorDeViajes';
SET @nombre_dueno = 'Parroquia_del_Carmen';
SET @dni_mal      = '51136797';
SET @dni_bien     = '53139344';

-- PASO 1: id_fila del contenedor de pasajeros del dueño.
SET @id_cont_pas = (
  SELECT ac.idadyacente_fila
  FROM nodo_contexto nc_dueno
  JOIN adyacente_contexto ac
    ON ac.idnodo_fila       = nc_dueno.id_fila
   AND ac.idsuperestructura = nc_dueno.idsuperestructura
  WHERE nc_dueno.idsuperestructura = @nombre_app
    AND nc_dueno.id_especial       = CONCAT('us_', @nombre_dueno)
    AND ac.enlace                  = 'pasajeros'
  LIMIT 1
);
SELECT @id_cont_pas AS id_contenedor_pasajeros;
-- Esperado: 10217.

-- PASO 2: id_fila del nodo pasajero con el DNI viejo.
SET @id_pas = (
  SELECT ac.idadyacente_fila
  FROM adyacente_contexto ac
  WHERE ac.idsuperestructura = @nombre_app
    AND ac.idnodo_fila       = @id_cont_pas
    AND ac.enlace            = @dni_mal
  LIMIT 1
);
SELECT @id_pas AS id_pasajero;
-- Esperado: un BIGINT. Si da NULL, el DNI viejo no está en ese contenedor.

-- PASO 3: verificar el nodo del pasajero (sin tocar la máscara).
SELECT id_fila, dato, id_especial
FROM nodo_contexto
WHERE idsuperestructura = @nombre_app
  AND id_fila = @id_pas;
-- Esperado: id_especial = NULL (los pasajeros no son especiales).

-- PASO 3b: verificar que el DNI nuevo NO exista ya en el contenedor.
SELECT COUNT(*) AS existe_dni_bien
FROM adyacente_contexto
WHERE idsuperestructura = @nombre_app
  AND idnodo_fila       = @id_cont_pas
  AND enlace            = @dni_bien;
-- Esperado: 0. Si da > 0, NO ejecutar los UPDATE.

-- PASO 3c: verificar que el DNI viejo no esté en OTROS contenedores.
SELECT idnodo_fila AS otro_contenedor, enlace
FROM adyacente_contexto
WHERE idsuperestructura = @nombre_app
  AND enlace            = @dni_mal
  AND idnodo_fila      <> @id_cont_pas;
-- Esperado: 0 filas.

-- PASO 4: UPDATE del dato del nodo (por si tenía el DNI viejo).
UPDATE nodo_contexto
SET dato = @dni_bien
WHERE idsuperestructura = @nombre_app
  AND id_fila = @id_pas;

-- PASO 5: UPDATE del enlace del contenedor.
UPDATE adyacente_contexto
SET enlace = @dni_bien
WHERE idsuperestructura = @nombre_app
  AND idnodo_fila       = @id_cont_pas
  AND enlace            = @dni_mal;

-- PASO 6: verificación final (sin tocar la máscara).
SELECT id_fila, dato, id_especial
FROM nodo_contexto
WHERE idsuperestructura = @nombre_app
  AND id_fila = @id_pas;
-- Esperado: dato = <dni_bien>.

SELECT idnodo_fila, enlace, idadyacente_fila
FROM adyacente_contexto
WHERE idsuperestructura = @nombre_app
  AND idnodo_fila       = @id_cont_pas
  AND enlace            IN (@dni_mal, @dni_bien);
-- Esperado: una sola fila con enlace = <dni_bien>.














para tablas viejas: 



-- ============================================================
-- Fix del DNI de un pasajero (SQL, tablas `nodo` y `adyacente`).
--
-- Reemplazá en TODAS las consultas:
--   :nombre_app   → 'AdministradorDeViajes'
--   :nombre_dueno → 'Parroquia_del_Carmen'
--   :dni_mal      → el DNI viejo/mal
--   :dni_bien     → el DNI nuevo
--
-- Corré los SELECT primero, verificá, después los UPDATE.
-- ============================================================

-- -----------------------------------------------------------------
-- PASO 1: Identificar el contenedor de pasajeros del dueño.
-- -----------------------------------------------------------------
SET @nombre_app   = 'AdministradorDeViajes';
SET @nombre_dueno = 'Parroquia_del_Carmen';
SET @dni_mal      = '20485238';
SET @dni_bien     = '28299978';

SET @id_cont_pas = (
  SELECT idadyacente FROM adyacente
  WHERE idsuperestructura = @nombre_app
    AND idnodo = CONCAT('us_', @nombre_dueno)
    AND enlace = 'pasajeros'
  LIMIT 1
);

SELECT @id_cont_pas AS id_contenedor_pasajeros;
-- Esperado: un id numérico (ej. 199). Si da NULL, revisar el paso 1.

-- -----------------------------------------------------------------
-- PASO 2: Identificar el nodo del pasajero con el DNI mal.
-- -----------------------------------------------------------------
SET @id_pas = (
  SELECT idadyacente FROM adyacente
  WHERE idsuperestructura = @nombre_app
    AND idnodo = @id_cont_pas
    AND enlace = @dni_mal
  LIMIT 1
);

SELECT @id_pas AS id_pasajero;
-- Esperado: un id numérico. Si da NULL, el DNI mal no está donde esperás.

-- -----------------------------------------------------------------
-- PASO 3: Verificar el dato del nodo del pasajero.
-- -----------------------------------------------------------------
SELECT idnodo, dato
FROM nodo
WHERE idsuperestructura = @nombre_app
  AND idnodo = @id_pas;
-- Esperado: idnodo = <id_pas>, dato = <dni_mal>.

-- -----------------------------------------------------------------
-- PASO 4: Actualizar el DATO del nodo (DNI viejo → nuevo).
-- -----------------------------------------------------------------
UPDATE nodo
SET dato = @dni_bien
WHERE idsuperestructura = @nombre_app
  AND idnodo = @id_pas;

-- -----------------------------------------------------------------
-- PASO 5: Actualizar el ENLACE del contenedor de pasajeros.
-- -----------------------------------------------------------------
UPDATE adyacente
SET enlace = @dni_bien
WHERE idsuperestructura = @nombre_app
  AND idnodo = @id_cont_pas
  AND enlace = @dni_mal;

-- -----------------------------------------------------------------
-- PASO 6: Verificación final.
-- -----------------------------------------------------------------
SELECT idnodo, dato FROM nodo WHERE idnodo = @id_pas;
SELECT idnodo, enlace, idadyacente FROM adyacente
WHERE idsuperestructura = @nombre_app
  AND idnodo = @id_cont_pas
  AND (enlace = @dni_mal OR enlace = @dni_bien);
-- Esperado: en el nodo, dato = <dni_bien>.
-- Esperado: en adyacente, una sola fila con enlace = <dni_bien>.