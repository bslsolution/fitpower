# FitPower — gimnasio / personal trainer (API-driven + MVC)

Aplicación de gimnasio: el **administrador** registra usuarios, el **entrenador** arma rutinas (ejercicios y series, con orden para moverlas) y las asigna a **socios**, que solo ven las suyas.

## Levantar

En la carpeta que tiene `docker-compose.yml` (no en la carpeta de arriba):

```bash
docker compose up --build
```

Esperá a que termine de armar MySQL (la primera vez tarda). Después:

- App: http://localhost:8080
- Login: http://localhost:8080/front/dist/pages/login.html
- API: http://localhost:8080/api/estado
- phpMyAdmin: http://localhost:8081

Abrí la app **en el navegador con esa URL**. No abras el HTML haciendo doble clic al archivo.

Usuarios de prueba (contraseña `password`): `admin@gym.local`, `entrenador@gym.local`, `socio@gym.local`.

Si el login no entra, o MySQL ya tenía datos viejos:

```bash
docker compose down -v
docker compose up --build
```

## Zip en otra PC

1. Descomprimí el zip.
2. Entrá a la carpeta que contiene `docker-compose.yml`.
3. En esa carpeta corré:

```bash
docker compose down
docker compose up --build
```

Si el puerto 8080 no abre, es común que esa PC ya tenga MySQL/XAMPP en el 3306: este compose **ya no usa el 3306 del Windows**, solo Docker. Dejá que termine el build y abrí http://localhost:8080

4. Entrá a http://localhost:8080 (no uses `file://`).
5. Login: `admin@gym.local` / `password`.

Si todavía no entra, `docker compose down -v` y volvé a `docker compose up --build`.

Si copiaste `api/.env.example` a `api/.env`, dejá `DB_HOST=db` (no `localhost`).

## Documentación

- Checklist: [`documentos/CHECKLIST.md`](documentos/CHECKLIST.md)
- App y arquitectura: [`documentos/documentacion-app.md`](documentos/documentacion-app.md)
- MER: [`documentos/base-de-datos/mer.md`](documentos/base-de-datos/mer.md)
- Pasaje a tablas: [`documentos/base-de-datos/pasaje-a-tablas.md`](documentos/base-de-datos/pasaje-a-tablas.md)
- SQL: [`documentos/base-de-datos/schema.sql`](documentos/base-de-datos/schema.sql)

## Stack

PHP 8.2 (Apache) + MySQL 8. Frontend estático. El HTML no habla con la base: solo JSON vía `/api`.
