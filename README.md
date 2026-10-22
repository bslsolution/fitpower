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

## Publicar en internet (gratis)

GitHub Pages no sirve para FitPower: hace falta **PHP + MySQL**. La opción gratuita más simple, sin tarjeta, es [InfinityFree](https://www.infinityfree.com/).

1. Creá una cuenta en https://www.infinityfree.com/ (solo email).
2. En el panel: **Create Website** y elegí un subdominio gratis, por ejemplo `fitpower.infinityfreeapp.com`.
3. Entrá a **MySQL Databases** → creá una base. Anotá **host**, **nombre de la base**, **usuario** y **contraseña** (no uses `localhost`).
4. Abrí **phpMyAdmin**, seleccioná esa base e importá `api/config/init.sql` (o `documentos/base-de-datos/schema.sql`).
5. Subí el proyecto a `htdocs` (File Manager o FTP). Tiene que quedar así:
   - `htdocs/index.html`
   - `htdocs/.htaccess`
   - `htdocs/api/`
   - `htdocs/front/`
6. En el servidor, copiá `api/.env.example` a `api/.env` y poné los datos de MySQL del paso 3.
7. En el panel, PHP **8.2 o más**.
8. Abrí `https://TU-SUBDOMINIO.infinityfreeapp.com`. Login de prueba: `admin@gym.local` / `password`.

La primera vez InfinityFree puede pedirte confirmar el email y esperar unos minutos a que active SSL.

No subas `api/.env` a GitHub: ese archivo tiene la clave de la base.

## Documentación

- Checklist: [`documentos/CHECKLIST.md`](documentos/CHECKLIST.md)
- App y arquitectura: [`documentos/documentacion-app.md`](documentos/documentacion-app.md)
- MER: [`documentos/base-de-datos/mer.md`](documentos/base-de-datos/mer.md)
- Pasaje a tablas: [`documentos/base-de-datos/pasaje-a-tablas.md`](documentos/base-de-datos/pasaje-a-tablas.md)
- SQL: [`documentos/base-de-datos/schema.sql`](documentos/base-de-datos/schema.sql)

## Stack

PHP 8.2 (Apache) + MySQL 8. Frontend estático. El HTML no habla con la base: solo JSON vía `/api`.
