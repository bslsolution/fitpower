# Base de datos — FitPower

Motor: **MySQL 8** (contenedor `db` de Docker). Charset: `utf8mb4`.

## Contenido de esta carpeta

| Archivo | Para qué |
| --- | --- |
| [mer.md](./mer.md) | Modelo Entidad-Relación (diagrama + cardinalidades) |
| [pasaje-a-tablas.md](./pasaje-a-tablas.md) | Del MER al modelo relacional (PK, FK, NN) |
| [schema.sql](./schema.sql) | CREATE + INSERT de prueba. Misma lógica que `api/config/init.sql` |

## Cómo aplicarlo

1. **Docker nuevo:** `docker compose up` monta `api/config/init.sql` en `/docker-entrypoint-initdb.d/`.
2. **Ya tenías volumen viejo (tablas de automotora):**  
   `docker compose down -v` y volver a levantar.
3. **phpMyAdmin:** http://localhost:8081 — servidor `db`, usuario `usuario` / `password` (o root según tu `.env`).

Si editás el SQL, cambiá **los dos** archivos (`schema.sql` y `api/config/init.sql`) para que no se desfasen.

## Entidades (resumen)

Usuarios con rol → (opcional) perfil entrenador o socio → rutinas del entrenador, opcionales para un socio → ejercicios ordenados en la rutina → series de cada ejercicio.
