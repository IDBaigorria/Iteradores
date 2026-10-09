# Plan actual — Línea de trabajo en curso

Este archivo documenta la **línea de trabajo activa** del piloto.
Reemplaza a la sección "Discusión actual" del `prompt_piloto.md`,
que queda **congelada como referencia histórica** desde v1.5piloto.76v.

**Reglas:**

- Se actualiza al cerrar **cada tanda** (no el prompt del piloto).
- Es lo primero que hay que leer para retomar el trabajo.
- Estado (qué se cerró, decisiones, bugs, aprendizajes) arriba.
- Plan de la línea de tandas (fases y detalle de la fase en curso) abajo.

---

## 1. ESTADO ACTUAL

### 1.1 Última actualización

**Última actualización de este archivo:** v1.5piloto.76v
(reorganización de prompts + inicio de la Fase B2.3).
Con v76u quedó cerrada la Fase B2.2 completa (Viaje, Venta,
ViajeAsientos, Empresa). Próximo paso: **Fase B2.3**, con un
cambio de enfoque respecto del plan original. En lugar del
"compartido plano" (opción A original), se va con la **opción D**:
árboles paralelos con nombres de enlace parametrizados en
`miscelaneas/Arbol.php`. Detalle en §2.3.)

### 1.2 Estado de la conversación

**Cadena de cierres de la Fase B (modelo topológico):**

- **v76i**: solo documentación. §8.7 en el prompt del piloto con
  el plan inicial de contextos. Sin cambios de código.
- **v76j**: cierre de la Fase 2 del framework (SQL64 en PHP,
  IndexedDB64 en JS, interfaz `PerdurarSuperestructuraConContexto`,
  4 métodos en el Controlador y flag `$grafo_parcial`). Fix del
  bug de tipos de ID en el `Map` del espejo JS. Deuda anotada:
  dejar el Nodo limpio antes de la Fase 5.
- **v76k**: Fase A del piloto. Usuarios como IDs especiales
  `us_<nombre>`. Comando `grafo:reemplazar_referencias`. Script
  `miscelaneas/migrar_usuarios_especiales.php` y bloque
  `?migrar_usuarios_especiales=1`.
- **v76l**: solo diseño. Modelo topológico por niveles de
  exposición (publico, privado, compartido_con_X).
- **v76m**: Fase B1. Contenedores `publico` y `privado` como
  alias de los nodos existentes. Comando
  `grafo:crear_niveles_usuario`. Script
  `miscelaneas/migrar_niveles_usuario.php` y bloque
  `?migrar_niveles_usuario=1`.
- **v76n**: pestaña Grafo ampliada ("Nodos raíz", "Migraciones").
  Nodo `aplicacion` con contenedor `migraciones`. Módulo
  `Aplicacion/Migraciones/`. Comandos `grafo:raices` (framework)
  y `app:migracion_*` (app, registrados directo).
- **v76o**: solo documentación. Tres pendientes del framework
  (espejar `grafo:reemplazar_referencias`, mover
  `grafo:crear_niveles_usuario` al app, verificar
  `grafo:raices` desde el SW del plugin).
- **v76p**: solo documentación. Sistema de comandos (§4.4 del
  framework, §5.16 del piloto). Lecciones 24-27.
- **v76q**: Fase B2.1. Compartidos por terminal creados como
  contenedores filtrados. Comando
  `app:crear_compartidos_terminal`. Bloque
  `?migrar_compartidos_terminal=1`. No repunta nada.
- **v76r**: Fase B2.2.1. Contexto opcional en
  `obtener_contenedor_viajes_dueno`. Helper `_contexto_terminal`.
  `listar_viajes_de_terminal` lo usa.
- **v76s**: Fase B2.2.2. Venta.php: contexto opcional en
  `obtener_contenedor_ventas_dueno`, filtro opcional por
  terminal en las búsquedas por id, contexto en
  `listar_ventas_por_terminal` y `confirmar_venta_actual`.
- **v76t**: Fase B2.2.3. ViajeAsientos.php: contexto opcional
  en las funciones que navegan por el contenedor de viajes.
- **v76u**: Fase B2.2.4. Empresa.php: contexto opcional en
  `listar_empresas_de_dueno`. Con esto queda cerrada la
  Fase B2.2.

**Tanda actual:** v76v (reorganización de prompts).

### 1.3 Decisiones de diseño en vigor

- **SQL es siempre el método principal.** JSON/XML son respaldo
  manual (no automático desde v74m).
- **Host y credenciales en `config_servidor.php`.** Es el único
  archivo que no se toca al desplegar.
- **Rol `soporte`.** Ve las mismas pestañas que el admin,
  solo sobre sus dueños asignados.
- **Los prompts viven en el proyecto.** Los tres clásicos
  (framework, piloto, sistema de scripts) + este `plan_actual.md`.
- **Comandos del framework vs de la app.** Framework → en
  `Controlador.php`. App → en `Aplicacion/`, registrados desde
  `index.php` después de los `require_once`.
- **El modelo topológico.** Seguridad por topología: un usuario
  solo alcanza lo que tiene camino desde su raíz. Admin y
  soporte siguen accediendo por código, no por topología.

### 1.4 Bugs conocidos

Ninguno de prioridad alta. Los históricos (Bug 1 opciones de
cobro, Bug 2 ligadura comprador-pasajero) están resueltos.

### 1.5 Aprendizajes a la fuerza

1. **Los datos del framework viajan como strings.** Castear al
   comparar. Ejemplo: `"2" !== 2` en JS.
2. **PHP convierte claves de array string numéricas a int.**
   Forzar `(string)$clave` en los foreach que iteran sobre DNI
   o patentes.
3. **Los IDs sin ID especial cambian entre cargas.** Si un
   nodo debe sobrevivir a guardar/cargar y ser referenciado,
   usar ID especial.
4. **Nunca confiar en datos que el cliente manda** cuando hay
   fuente de verdad del lado del servidor.
5. **El guardado SQL debe ser transaccional y por chunks.**
6. **El guardado nunca debe pisar el grafo con vacío.**
7. **Los includes importan.** `guardar_ambos` disponible antes
   de usarse.
8. **`if ($elemento)` descarta falsy.** Usar `!== null` cuando
   el valor puede ser falsy legítimamente.
9. **PHP y JS van espejados.** Framework PHP ↔ espejo JS en
   la misma tanda, dos scripts, dos commits.
10. **En MV3, `import()` dinámico está prohibido en el
    `ServiceWorkerGlobalScope`.** Para debug desde la consola
    del SW, exponer `globalThis.Controlador = Controlador` en
    el bootstrap.

### 1.6 Preguntas abiertas

Ninguna. La conversación quedó en un punto de pausa limpio.

---

## 2. PLAN DE LA LÍNEA DE TANDAS

### 2.1 Contexto

La línea en curso es la **Fase B del modelo topológico**.
Objetivo: aislar por topología, para que cada usuario solo
alcance lo que le corresponde. La seguridad emerge de la
estructura del grafo, no de chequeos de código.

### 2.2 Fases

1. **Fase A** — Usuarios como IDs especiales `us_<nombre>`.
   **Completada** (v76k).
2. **Fase B1** — Contenedores `publico` y `privado` en cada
   usuario, como alias de los nodos existentes. **Completada**
   (v76m).
3. **Fase B2.1** — Compartidos por terminal (`compartido_con_us_termX`)
   creados con referencias filtradas. **Completada** (v76q).
4. **Fase B2.2.1** — Contexto opcional en `obtener_contenedor_viajes_dueno`.
   **Completada** (v76r).
5. **Fase B2.2.2** — Contexto y filtro en Venta.php.
   **Completada** (v76s).
6. **Fase B2.2.3** — Contexto en ViajeAsientos.php.
   **Completada** (v76t).
7. **Fase B2.2.4** — Contexto en Empresa.php.
   **Completada** (v76u).
8. **Fase B2.3** — Árboles paralelos con nombres parametrizados
   en `Arbol.php`. **En curso.** Detalle en §2.3.
9. **Fase B2.4** — Mantener los compartidos vivos (cada operación
   que modifique el contenido del compartido lo actualiza).
   **Pendiente.**
10. **Fase B3** — Eliminar los accesos viejos. **Pendiente.**
11. **Fase C** (opcional) — Tipos como IDs especiales, ortogonal.
12. **Fase D** — Aprovechar la carga parcial como optimización.
    **Pendiente.**

### 2.3 Detalle de la Fase B2.3 (en curso)

**Cambio de enfoque respecto del plan original.** El plan
original proponía un "compartido plano" (opción A): el
`compartido_con_us_termX` guarda enlaces planos por id, y las
funciones del terminal iteran con `adyacentes()`. Se descartó
en favor de la **opción D**: árboles paralelos con nombres de
enlace parametrizados.

**Por qué.** El compartido puede usar el mismo mecanismo de
árbol binario (`hmi`/`hd`/`p`) que el privado, solo que con
nombres alternativos. Cada venta pertenece a UN solo terminal,
así que solo participa en 2 árboles: el del dueño (con
`hmi`/`hd`/`p`) y el del terminal que la vendió (con
`hmi_<terminal>`/`hd_<terminal>`/`p_<terminal>`). No hay
N+1 enlaces por nodo.

**La raíz del árbol no se duplica.** El contenedor padre es el
mismo nodo físico, referenciado desde dos lugares:
`privado/ventas` y `compartido_con_us_termX/ventas`. Los hijos
se enlazan con los nombres que correspondan a cada árbol.

**Sub-tandas de B2.3:**

- **B2.3.1** — Parametrizar `miscelaneas/Arbol.php`. Las
  funciones `_hmi`, `_hd`, `hmi`, `hd`, `p`, `eliminar_hmi` y
  `eliminar_hd` aceptan un `?array $nombres = null`. Si es
  null, usan los nombres default (`hmi`/`hd`/`p`). Refactor
  sin cambio de comportamiento.
- **B2.3.2** — Ajustar las funciones del piloto para que el
  terminal use los nombres parametrizados. Sin repuntar.
- **B2.3.3** — Migración que convierte los compartidos
  existentes (B2.1) de enlaces planos a árboles paralelos
  con los nombres parametrizados.
- **B2.3.4** — Repuntar `us_termX → dueno` al compartido.
  Acá se activa el aislamiento. Con pruebas del plugin.

### 2.4 Próximas fases

- **B2.4** — Mantener vivos los compartidos.
- **B3** — Eliminar accesos viejos.
- **C** (opcional) — Tipos como IDs especiales.
- **D** — Carga parcial como optimización.

---

**FIN DEL PLAN ACTUAL**