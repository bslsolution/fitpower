# Documentación de la aplicación

Sistema **FitPower** (gimnasio / personal trainer): el administrador da de alta personas, el entrenador arma y asigna rutinas, el socio consulta lo que le toca.

Arquitectura: **API-driven** + **MVC solo en la API**. El HTML/JS no ejecuta SQL ni incluye PHP.

## 1. Qué hace cada rol

| Rol | Puede hacer | No puede hacer |
| --- | --- | --- |
| **Administrador** | Registrar usuarios (admin, entrenador o socio), activar/desactivar cuentas | No arma rutinas en v1 |
| **Entrenador** | Crear ejercicios, armar rutinas, agregar series, **mover el orden** de ejercicios/series, asignar la rutina a un socio, modificar lo asignado | No registra usuarios |
| **Socio** | Ver sus rutinas (ejercicios, orden, series) | No crea usuarios ni edita la rutina de otro |

## 2. Flujo de negocio (v1)

1. El administrador crea las cuentas (email + contraseña + rol).
2. El entrenador carga ejercicios al catálogo (ej. Sentadilla, Press banca).
3. El entrenador crea una **rutina** (nombre, objetivo, notas).
4. Dentro de la rutina agrega **ejercicios** y les pone un **orden** (1, 2, 3…). Ese orden se puede cambiar para armar la sesión.
5. Cada ejercicio de la rutina tiene **series** (repeticiones, peso, descanso). También se pueden editar y reordenar.
6. El entrenador **asigna** esa rutina a un socio (queda un ejemplar para ese socio, para poder modificarlo sin romper plantillas).
7. El socio entra a su dashboard y ve solo lo suyo.

## 3. Estructura de carpetas

```
Proyecto/
├── api/                    # Backend PHP (MVC)
│   ├── index.php           # Entrada de la API (JSON + CORS)
│   ├── routes.php          # GET/POST/PUT/DELETE → Controlador@método
│   ├── autoload.php
│   ├── config/
│   │   ├── database.php    # PDO
│   │   └── init.sql        # Se ejecuta al crear el contenedor MySQL
│   └── src/
│       ├── Controllers/    # Orquesta, no escribe SQL
│       ├── Models/         # Únicos que hablan con la BD
│       └── Utils/          # Router, EnvLoader
├── front/                  # Frontend estático
│   ├── index.html
│   └── dist/
│       ├── css/
│       ├── js/
│       │   ├── api.js      # Único puente HTTP hacia /api
│       │   └── script.js
│       └── pages/
├── assets/                 # Maquetas (landing, login, dashboards)
├── documentos/             # Esta documentación
│   └── base-de-datos/
├── docker-compose.yml
└── Dockerfile
```

## 4. Cómo se comunica el front con la API

1. El navegador carga HTML/JS.
2. `script.js` pide datos con `API.request('/ruta', 'GET'|'POST'|...)`.
3. `api.js` hace `fetch` a `/api/...`.
4. Apache reescribe `/api/*` hacia `api/index.php`.
5. El **Router** elige `Controlador@metodo`.
6. El **Controlador** usa un **Model**.
7. El Model ejecuta SQL con PDO y devuelve arrays.
8. El Controlador responde **JSON**. El JS pinta el DOM.

Nunca: `<?php` dentro del HTML, ni `fetch` a archivos `.php` sueltos.

## 5. Convención MVC (API)

- **Model**: SQL, mapeo de filas. Cero `echo`, cero headers.
- **Controller**: validar input, reglas de negocio (¿este usuario es entrenador de este socio?), armar el JSON, código HTTP.
- **Router**: solo despacha.

Rutas nuevas se anotan en `api/routes.php`, por ejemplo:

```php
$router->post('/login', 'AuthController@login');
$router->post('/usuarios', 'UsuarioController@crear');          // solo admin
$router->get('/socios/:id/rutinas', 'RutinaController@deSocio'); // socio o su entrenador
$router->put('/rutinas/:id/ejercicios/orden', 'RutinaController@reordenar');
```

## 6. Cómo levantarlo en local

Requisitos: Docker Desktop.

```bash
docker compose up --build
```

| Servicio | URL |
| --- | --- |
| App (front + API) | http://localhost:8080 |
| Front | http://localhost:8080/front/index.html |
| Health API | http://localhost:8080/api/estado |
| phpMyAdmin | http://localhost:8081 |

Variables: copiar `api/.env.example` → `api/.env` (no se sube a Git).

**Importante:** `init.sql` corre **solo** la primera vez que se crea el volumen de MySQL. Si cambiaste el SQL y las tablas viejas siguen ahí (por ejemplo las de automotora):

```bash
docker compose down -v
docker compose up --build
```

(`-v` borra datos del contenedor de base.)

## 7. Usuarios de prueba (seed)

Contraseña de todos: `password`

| Email | Rol |
| --- | --- |
| `admin@gym.local` | administrador |
| `entrenador@gym.local` | entrenador |
| `socio@gym.local` | socio |

## 8. Pantallas (carpeta `assets/`)

Marca del diseño: **FitPower**. Referencia visual en `assets/` (landing, login, dashboards).

| Pantalla | Uso en la app |
| --- | --- |
| Landing | Home pública, CTA a login |
| Login | En el diseño: email + n° de socio. En la API v1: email + contraseña → `POST /api/login` |
| Dashboard admin | Alta de usuarios (único que registra), listado |
| Dashboard entrenador | Crear rutina, socios, gestionar ejercicios (ordenar y series) |
| Dashboard socio | Ver rutinas, cargar peso por serie, ver progreso |

El front que corre Docker vive en `front/`. `assets/` es referencia visual, no se sirve como app.

FitPoints aparece en el mockup del administrador: queda fuera de v1 (ver checklist).

## 9. Endpoints actuales

| Método | Ruta | Qué hace |
| --- | --- | --- |
| GET | `/api/estado` | Comprueba API + BD + tabla `usuarios` |

El resto está en el checklist (sección 2).

## 10. Decisiones de diseño (para no pelear en el equipo)

1. **Un usuario = un rol.** No hay socio-entrenador en v1.
2. **Plantilla vs asignada.** El entrenador puede guardar una rutina plantilla (`id_socio` NULL). Al asignar se **copia** a una rutina del socio. Así se puede retocar la de Juan sin cambiar la de Ana.
3. **Orden explícito.** `rutina_ejercicios.orden` y `series.orden` son enteros. Mover = actualizar esos números (el front puede mandar un array de ids).
4. **El socio no arma la rutina.** PUT/DELETE de rutina/ejercicio/serie quedan para entrenador o admin. El socio registra el entrenamiento al apretar **Terminar** en cada serie: se crea/actualiza un `entrenamiento` y sus series.
5. **Alta de usuarios:** solo rol administrador. No hay `/register` público.

## 11. Dónde está el modelo de datos

- MER: `documentos/base-de-datos/mer.md`
- Pasaje a tablas: `documentos/base-de-datos/pasaje-a-tablas.md`
- SQL: `documentos/base-de-datos/schema.sql` (copia de trabajo: `api/config/init.sql`)
