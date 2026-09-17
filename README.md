# FitPower — gimnasio / personal trainer (API-driven + MVC)

Aplicación de gimnasio: el **administrador** registra usuarios, el **entrenador** arma rutinas (ejercicios y series, con orden para moverlas) y las asigna a **socios**, que solo ven las suyas.

## Levantar

```bash
docker compose up --build
```

- App: http://localhost:8080
- API: http://localhost:8080/api/estado
- phpMyAdmin: http://localhost:8081

Usuarios de prueba (contraseña `password`): `admin@gym.local`, `entrenador@gym.local`, `socio@gym.local`.

Si el SQL cambió y MySQL ya tenía datos viejos (tablas de automotora):

```bash
docker compose down -v
docker compose up --build
```

Variables de entorno: copiar `api/.env.example` → `api/.env` (ese archivo no se sube a Git).

## Documentación

- Checklist: [`documentos/CHECKLIST.md`](documentos/CHECKLIST.md)
- App y arquitectura: [`documentos/documentacion-app.md`](documentos/documentacion-app.md)
- MER: [`documentos/base-de-datos/mer.md`](documentos/base-de-datos/mer.md)
- Pasaje a tablas: [`documentos/base-de-datos/pasaje-a-tablas.md`](documentos/base-de-datos/pasaje-a-tablas.md)
- SQL: [`documentos/base-de-datos/schema.sql`](documentos/base-de-datos/schema.sql)

## Stack

PHP 8.2 (Apache) + MySQL 8. Frontend estático. El HTML no habla con la base: solo JSON vía `/api`.
