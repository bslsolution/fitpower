# Pasaje del MER a tablas

Reglas usadas:

1. Cada **entidad** fuerte → una tabla.
2. El **identificador** → clave primaria (`id_*`).
3. Relación **1:N** → la FK va del lado N.
4. Relación **1:1** → FK única en la tabla débil/perfil (`entrenadores.id_usuario`, `socios.id_usuario`).
5. Relación **N:N** entre RUTINA y EJERCICIO → tabla intermedia `rutina_ejercicios` (además guarda `orden` y `notas`, que no son de ninguna de las dos entidades solas).
6. SERIES depende de RUTINA_EJERCICIO (no del catálogo EJERCICIO): las series son del plan concreto.

## Tabla `roles`

| Columna | Tipo | Nulo | Clave | Notas |
| --- | --- | --- | --- | --- |
| id_rol | INT AI | NO | PK | |
| nombre | VARCHAR(30) | NO | UK | `administrador`, `entrenador`, `socio` |

## Tabla `usuarios`

| Columna | Tipo | Nulo | Clave | Notas |
| --- | --- | --- | --- | --- |
| id_usuario | INT AI | NO | PK | |
| id_rol | INT | NO | FK → roles | Un rol por usuario |
| nombre | VARCHAR(80) | NO | | |
| apellido | VARCHAR(80) | NO | | |
| email | VARCHAR(120) | NO | UK | Login |
| password_hash | VARCHAR(255) | NO | | `password_hash()` de PHP |
| telefono | VARCHAR(30) | SÍ | | |
| activo | TINYINT(1) | NO | | 1 = puede entrar |
| fecha_alta | DATETIME | NO | | DEFAULT CURRENT_TIMESTAMP |

## Tabla `entrenadores`

| Columna | Tipo | Nulo | Clave | Notas |
| --- | --- | --- | --- | --- |
| id_entrenador | INT AI | NO | PK | |
| id_usuario | INT | NO | FK → usuarios, UK | 1:1 |
| especialidad | VARCHAR(80) | SÍ | | |
| bio | VARCHAR(255) | SÍ | | |

## Tabla `socios`

| Columna | Tipo | Nulo | Clave | Notas |
| --- | --- | --- | --- | --- |
| id_socio | INT AI | NO | PK | |
| id_usuario | INT | NO | FK → usuarios, UK | 1:1 |
| id_entrenador | INT | SÍ | FK → entrenadores | NULL = todavía sin entrenador a cargo |
| fecha_nacimiento | DATE | SÍ | | |
| notas | VARCHAR(255) | SÍ | | Comentario interno del staff |
| fitpoints | INT | NO | | Saldo de FitPoints. DEFAULT 0. Suma 10 al terminar una rutina |

## Tabla `grupos_musculares`

| Columna | Tipo | Nulo | Clave | Notas |
| --- | --- | --- | --- | --- |
| id_grupo | INT AI | NO | PK | |
| nombre | VARCHAR(50) | NO | UK | Lo edita el administrador. El entrenador lo elige de un listado |

## Tabla `ejercicios`

| Columna | Tipo | Nulo | Clave | Notas |
| --- | --- | --- | --- | --- |
| id_ejercicio | INT AI | NO | PK | |
| id_entrenador_creador | INT | SÍ | FK → entrenadores | NULL si lo cargó un seed/admin |
| nombre | VARCHAR(100) | NO | | |
| grupo_muscular | VARCHAR(50) | NO al crear | | Debe coincidir con `grupos_musculares.nombre` |
| descripcion | TEXT | SÍ | | |

## Tabla `rutinas`

| Columna | Tipo | Nulo | Clave | Notas |
| --- | --- | --- | --- | --- |
| id_rutina | INT AI | NO | PK | |
| id_entrenador | INT | NO | FK → entrenadores | Dueño / autor |
| id_socio | INT | SÍ | FK → socios | NULL = plantilla; NOT NULL = asignada |
| id_rutina_origen | INT | SÍ | FK → rutinas | De qué plantilla se copió |
| nombre | VARCHAR(100) | NO | | |
| objetivo | VARCHAR(150) | SÍ | | hipertrofia, fuerza… |
| estado | VARCHAR(20) | NO | | `borrador`, `activa`, `finalizada` |
| fecha_creacion | DATETIME | NO | | DEFAULT CURRENT_TIMESTAMP |

## Tabla `rutina_ejercicios` (intermedia N:N + atributos)

| Columna | Tipo | Nulo | Clave | Notas |
| --- | --- | --- | --- | --- |
| id_rutina_ejercicio | INT AI | NO | PK | Surrogada: hay atributos propios |
| id_rutina | INT | NO | FK → rutinas | ON DELETE CASCADE |
| id_ejercicio | INT | NO | FK → ejercicios | |
| orden | INT | NO | | 1..N — **mover la rutina = cambiar esto** |
| notas | VARCHAR(255) | SÍ | | “controlar rodilla”, etc. |

Índice `(id_rutina, orden)` para listar rápido. **No** hay UNIQUE de orden: así se puede intercambiar posiciones sin un valor temporal. El mismo ejercicio del catálogo **puede repetirse** en una rutina (ej. sentadilla al inicio y al final).

## Tabla `series`

| Columna | Tipo | Nulo | Clave | Notas |
| --- | --- | --- | --- | --- |
| id_serie | INT AI | NO | PK | |
| id_rutina_ejercicio | INT | NO | FK → rutina_ejercicios | ON DELETE CASCADE |
| orden | INT | NO | | Orden de las series |
| repeticiones | INT | NO | | |
| peso_kg | DECIMAL(6,2) | SÍ | | NULL = peso corporal |
| descanso_segundos | INT | SÍ | | |

## Tabla `entrenamientos`

| Columna | Tipo | Nulo | Clave | Notas |
| --- | --- | --- | --- | --- |
| id_entrenamiento | INT AI | NO | PK | Una sesión de entrenamiento |
| id_socio | INT | NO | FK → socios | ON DELETE CASCADE |
| id_rutina | INT | SÍ | FK → rutinas | ON DELETE SET NULL |
| nombre | VARCHAR(100) | NO | | Copia del nombre de la rutina |
| fecha | DATETIME | NO | | DEFAULT CURRENT_TIMESTAMP |
| fitpoints_otorgados | TINYINT(1) | NO | | 1 si esa sesión ya sumó los 10 FitPoints |

## Tabla `entrenamiento_series`

| Columna | Tipo | Nulo | Clave | Notas |
| --- | --- | --- | --- | --- |
| id_entrenamiento_serie | INT AI | NO | PK | |
| id_entrenamiento | INT | NO | FK → entrenamientos | ON DELETE CASCADE |
| id_serie_origen | INT | SÍ | | Serie de la rutina; UNIQUE con el entrenamiento |
| id_ejercicio | INT | NO | FK → ejercicios | |
| nombre_ejercicio | VARCHAR(100) | NO | | Copia del nombre |
| grupo_muscular | VARCHAR(50) | SÍ | | |
| serie_orden | INT | NO | | |
| repeticiones | INT | NO | | |
| peso_kg | DECIMAL(6,2) | SÍ | | Lo carga el socio |
| descanso_segundos | INT | SÍ | | |

UNIQUE `(id_entrenamiento, id_serie_origen)`: Terminar de nuevo en la misma serie actualiza el peso.

## FKs (resumen)

```
roles.id_rol                    ← usuarios.id_rol
usuarios.id_usuario             ← entrenadores.id_usuario
usuarios.id_usuario             ← socios.id_usuario
entrenadores.id_entrenador      ← socios.id_entrenador
entrenadores.id_entrenador      ← ejercicios.id_entrenador_creador
grupos_musculares.nombre        ← ejercicios.grupo_muscular
entrenadores.id_entrenador      ← rutinas.id_entrenador
socios.id_socio                 ← rutinas.id_socio
rutinas.id_rutina               ← rutinas.id_rutina_origen
rutinas.id_rutina               ← rutina_ejercicios.id_rutina
ejercicios.id_ejercicio         ← rutina_ejercicios.id_ejercicio
rutina_ejercicios.id_rutina_ejercicio ← series.id_rutina_ejercicio
socios.id_socio                     ← entrenamientos.id_socio
rutinas.id_rutina                   ← entrenamientos.id_rutina
entrenamientos.id_entrenamiento     ← entrenamiento_series.id_entrenamiento
ejercicios.id_ejercicio             ← entrenamiento_series.id_ejercicio
socios.id_socio                     ← canjes_fitpoints.id_socio
```

## Tabla `notificaciones`

| Columna | Tipo | Nulo | Clave | Notas |
| --- | --- | --- | --- | --- |
| id_notificacion | INT AI | NO | PK | |
| destinatario_rol | VARCHAR(20) | NO | | `administrador`, `entrenador` o `socio` |
| id_usuario_destinatario | INT | SÍ | | NULL = todos los de ese rol; si hay valor, solo ese usuario |
| tipo | VARCHAR(40) | NO | | Ej. `canje_fitpoints` |
| datos | JSON | SÍ | | Nombre del socio, recompensa y costo |
| leida | TINYINT(1) | NO | | 0 = nueva, 1 = ya vista por el administrador |
| fecha | DATETIME | NO | | DEFAULT CURRENT_TIMESTAMP |

## Por qué no hay tabla `ASIGNACIONES` aparte

El entrenador a cargo del socio va en `socios.id_entrenador`. Asignar una **rutina** = **nueva rutina** con `id_socio` cargado y, si aplica, `id_rutina_origen` apuntando a la plantilla. Evita que dos socios compartan las mismas filas de series (si el entrenador le baja el peso a Juan, Ana no se ve afectada).
