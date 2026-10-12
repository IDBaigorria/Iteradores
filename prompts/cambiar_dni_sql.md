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