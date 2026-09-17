# Modelo Entidad-Relación (MER)

Dominio: gimnasio FitPower con administrador, entrenador y socio. El entrenador arma rutinas (ejercicios + series, con orden) y las asigna a socios.

Parte del MER de clase (`USUARIO` / `PERSONA` / `ENTRENAMIENTO`) y lo completa: ejercicios, series, orden para armar/mover la rutina, y tres roles.

## Diagrama

```mermaid
erDiagram
    ROLES ||--o{ USUARIOS : tiene
    USUARIOS ||--o| ENTRENADORES : es
    USUARIOS ||--o| SOCIOS : es
    ENTRENADORES ||--o{ RUTINAS : crea
    SOCIOS ||--o{ RUTINAS : recibe
    RUTINAS ||--o{ RUTINA_EJERCICIOS : contiene
    EJERCICIOS ||--o{ RUTINA_EJERCICIOS : seUsaEn
    ENTRENADORES ||--o{ EJERCICIOS : carga
    RUTINA_EJERCICIOS ||--o{ SERIES : tiene

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
        date fecha_nacimiento
        string notas
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
```

## Entidades

| Entidad | Qué representa |
| --- | --- |
| **ROL** | Catálogo: administrador, entrenador, socio |
| **USUARIO** | Cuenta de login. Solo el administrador las da de alta |
| **ENTRENADOR** | Perfil 1:1 de un usuario con rol entrenador |
| **SOCIO** | Perfil 1:1 de un usuario con rol socio |
| **EJERCICIO** | Catálogo reutilizable (Sentadilla, Remo, etc.) |
| **RUTINA** | Plan de entrenamiento. Sin socio = plantilla. Con socio = plan asignado (copia) |
| **RUTINA_EJERCICIO** | Un ejercicio **dentro** de una rutina, con **orden** para moverlo |
| **SERIE** | Una serie de ese ejercicio en esa rutina (reps, peso, descanso, orden) |

El administrador **no** tiene tabla extra: alcanza con `USUARIOS.id_rol`.

## Relaciones y cardinalidades

| Relación | Cardinalidad | Lectura |
| --- | --- | --- |
| ROL — USUARIO | 1 : N | Un rol lo tienen muchos usuarios; un usuario tiene un solo rol |
| USUARIO — ENTRENADOR | 1 : 0..1 | Un usuario puede ser (o no) entrenador |
| USUARIO — SOCIO | 1 : 0..1 | Un usuario puede ser (o no) socio |
| ENTRENADOR — EJERCICIO | 1 : N | El entrenador carga ejercicios al catálogo |
| ENTRENADOR — RUTINA | 1 : N | El entrenador crea muchas rutinas |
| SOCIO — RUTINA | 0..1 : N | Un socio tiene varias rutinas; una rutina asignada es de un socio. La plantilla no tiene socio |
| RUTINA — RUTINA_EJERCICIO | 1 : N | La rutina se arma con varios ejercicios ordenados |
| EJERCICIO — RUTINA_EJERCICIO | 1 : N | El mismo ejercicio del catálogo entra en muchas rutinas |
| RUTINA_EJERCICIO — SERIE | 1 : N | Cada ejercicio de la rutina tiene varias series |

## Restricciones de negocio (no son cajitas del MER, pero importan)

- Un usuario no es a la vez entrenador y socio (v1).
- Solo el administrador inserta en `usuarios`.
- El socio **consulta** `rutinas` donde `id_socio` es el suyo.
- Reordenar la rutina = cambiar `rutina_ejercicios.orden` (y opcionalmente `series.orden`).
- Asignar a un socio = **copiar** plantilla (nueva fila en `rutinas` + copiar ejercicios y series), no compartir la misma PK entre dos socios.
