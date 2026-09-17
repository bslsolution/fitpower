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
| fecha_nacimiento | DATE | SÍ | | |
| notas | VARCHAR(255) | SÍ | | Comentario interno del staff |

## Tabla `ejercicios`

| Columna | Tipo | Nulo | Clave | Notas |
| --- | --- | --- | --- | --- |
| id_ejercicio | INT AI | NO | PK | |
| id_entrenador_creador | INT | SÍ | FK → entrenadores | NULL si lo cargó un seed/admin |
| nombre | VARCHAR(100) | NO | | |
| grupo_muscular | VARCHAR(50) | SÍ | | pecho, espalda, pierna… |
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

## FKs (resumen)

```
roles.id_rol                    ← usuarios.id_rol
usuarios.id_usuario             ← entrenadores.id_usuario
usuarios.id_usuario             ← socios.id_usuario
entrenadores.id_entrenador      ← ejercicios.id_entrenador_creador
entrenadores.id_entrenador      ← rutinas.id_entrenador
socios.id_socio                 ← rutinas.id_socio
rutinas.id_rutina               ← rutinas.id_rutina_origen
rutinas.id_rutina               ← rutina_ejercicios.id_rutina
ejercicios.id_ejercicio         ← rutina_ejercicios.id_ejercicio
rutina_ejercicios.id_rutina_ejercicio ← series.id_rutina_ejercicio
```

## Por qué no hay tabla `ASIGNACIONES` aparte

Asignar = **nueva rutina** con `id_socio` cargado y, si aplica, `id_rutina_origen` apuntando a la plantilla. Evita que dos socios compartan las mismas filas de series (si el entrenador le baja el peso a Juan, Ana no se ve afectada).
