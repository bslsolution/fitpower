# Modelo Entidad-Relación (MER)

Dominio: gimnasio FitPower con administrador, entrenador y socio. El entrenador arma rutinas (ejercicios + series, con orden) y las asigna a socios.

Parte del MER de clase (`USUARIO` / `PERSONA` / `ENTRENAMIENTO`) y lo completa: ejercicios, series, orden para armar/mover la rutina, y tres roles.

## Diagrama

```mermaid
erDiagram
    ROLES ||--o{ USUARIOS : tiene
    USUARIOS ||--o| ENTRENADORES : es
    USUARIOS ||--o| SOCIOS : es
    ENTRENADORES ||--o{ SOCIOS : tieneACargo
    ENTRENADORES ||--o{ RUTINAS : crea
    SOCIOS ||--o{ RUTINAS : recibe
    RUTINAS ||--o{ RUTINA_EJERCICIOS : contiene
    EJERCICIOS ||--o{ RUTINA_EJERCICIOS : seUsaEn
    ENTRENADORES ||--o{ EJERCICIOS : carga
    GRUPOS_MUSCULARES ||--o{ EJERCICIOS : clasifica
    RUTINA_EJERCICIOS ||--o{ SERIES : tiene
    SOCIOS ||--o{ ENTRENAMIENTOS : registra
    RUTINAS ||--o{ ENTRENAMIENTOS : origina
    ENTRENAMIENTOS ||--o{ ENTRENAMIENTO_SERIES : contiene
    EJERCICIOS ||--o{ ENTRENAMIENTO_SERIES : seMideEn

    ROLES {
        int id_rol PK
        string nombre UK
    }

    USUARIOS {
        int id_usuario PK
        int id_rol FK
        string nombre
        string apellido
        string email UK
        string password_hash
        string telefono
        bool activo
        datetime fecha_alta
    }

    ENTRENADORES {
        int id_entrenador PK
        int id_usuario FK UK
        string especialidad
        string bio
    }

    SOCIOS {
        int id_socio PK
        int id_usuario FK UK
        int id_entrenador FK
        date fecha_nacimiento
        string notas
        int fitpoints
    }

    GRUPOS_MUSCULARES {
        int id_grupo PK
        string nombre UK
    }

    EJERCICIOS {
        int id_ejercicio PK
        int id_entrenador_creador FK
        string nombre
        string grupo_muscular
        string descripcion
    }

    RUTINAS {
        int id_rutina PK
        int id_entrenador FK
        int id_socio FK
        string nombre
        string objetivo
        string estado
        datetime fecha_creacion
    }

    RUTINA_EJERCICIOS {
        int id_rutina_ejercicio PK
        int id_rutina FK
        int id_ejercicio FK
        int orden
        string notas
    }

    SERIES {
        int id_serie PK
        int id_rutina_ejercicio FK
        int orden
        int repeticiones
        decimal peso_kg
        int descanso_segundos
    }

    ENTRENAMIENTOS {
        int id_entrenamiento PK
        int id_socio FK
        int id_rutina FK
        string nombre
        datetime fecha
        bool fitpoints_otorgados
    }

    ENTRENAMIENTO_SERIES {
        int id_entrenamiento_serie PK
        int id_entrenamiento FK
        int id_ejercicio FK
        string nombre_ejercicio
        int serie_orden
        int repeticiones
        decimal peso_kg
    }
```

## Entidades

| Entidad | Qué representa |
| --- | --- |
| **ROL** | Catálogo: administrador, entrenador, socio |
| **USUARIO** | Cuenta de login. Solo el administrador las da de alta |
| **ENTRENADOR** | Perfil 1:1 de un usuario con rol entrenador |
| **SOCIO** | Perfil 1:1 de un usuario con rol socio |
| **GRUPO_MUSCULAR** | Catálogo de grupos (Pierna, Pecho…). Lo edita el administrador |
| **EJERCICIO** | Catálogo reutilizable (Sentadilla, Remo, etc.) |
| **RUTINA** | Plan de entrenamiento. Sin socio = plantilla. Con socio = plan asignado (copia) |
| **RUTINA_EJERCICIO** | Un ejercicio **dentro** de una rutina, con **orden** para moverlo |
| **SERIE** | Una serie de ese ejercicio en esa rutina (reps, peso, descanso, orden) |
| **ENTRENAMIENTO** | Una sesión que el socio guarda al apretar Terminar |
| **ENTRENAMIENTO_SERIE** | Series y pesos de ese entrenamiento |

El administrador **no** tiene tabla extra: alcanza con `USUARIOS.id_rol`.

## Relaciones y cardinalidades

| Relación | Cardinalidad | Lectura |
| --- | --- | --- |
| ROL — USUARIO | 1 : N | Un rol lo tienen muchos usuarios; un usuario tiene un solo rol |
| USUARIO — ENTRENADOR | 1 : 0..1 | Un usuario puede ser (o no) entrenador |
| USUARIO — SOCIO | 1 : 0..1 | Un usuario puede ser (o no) socio |
| ENTRENADOR — SOCIO | 1 : N | El administrador asigna un entrenador al socio |
| GRUPO_MUSCULAR — EJERCICIO | 1 : N | El administrador define los grupos; el ejercicio elige uno |
| ENTRENADOR — EJERCICIO | 1 : N | El entrenador carga ejercicios al catálogo |
| ENTRENADOR — RUTINA | 1 : N | El entrenador crea muchas rutinas |
| SOCIO — RUTINA | 0..1 : N | Un socio tiene varias rutinas; una rutina asignada es de un socio. La plantilla no tiene socio |
| RUTINA — RUTINA_EJERCICIO | 1 : N | La rutina se arma con varios ejercicios ordenados |
| EJERCICIO — RUTINA_EJERCICIO | 1 : N | El mismo ejercicio del catálogo entra en muchas rutinas |
| RUTINA_EJERCICIO — SERIE | 1 : N | Cada ejercicio de la rutina tiene varias series |
| SOCIO — ENTRENAMIENTO | 1 : N | El socio guarda sesiones completadas |
| RUTINA — ENTRENAMIENTO | 0..1 : N | El entrenamiento recuerda de qué rutina salió |
| ENTRENAMIENTO — ENTRENAMIENTO_SERIE | 1 : N | Cada sesión tiene las series terminadas |
| EJERCICIO — ENTRENAMIENTO_SERIE | 1 : N | El catálogo se reutiliza en el historial |

## Restricciones de negocio (no son cajitas del MER, pero importan)

- Un usuario no es a la vez entrenador y socio (v1).
- Solo el administrador inserta en `usuarios` y asigna el entrenador del socio (`socios.id_entrenador`).
- Solo el administrador crea y edita `grupos_musculares`. Al crear un ejercicio, nombre y grupo son obligatorios; la descripción es opcional.
- El socio **consulta** `rutinas` donde `id_socio` es el suyo. Puede escribir el peso de cada serie; el progreso se guarda **solo** al apretar **Terminar**, agrupado por entrenamiento.
- Reordenar la rutina = cambiar `rutina_ejercicios.orden` (y opcionalmente `series.orden`).
- Asignar a un socio = **copiar** plantilla (nueva fila en `rutinas` + copiar ejercicios y series), no compartir la misma PK entre dos socios.
