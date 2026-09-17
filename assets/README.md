# Assets — maquetas FitPower

Referencia visual. El sitio que corre está en `front/`.

| Archivo | Pantalla |
| --- | --- |
| `landing.png` | Home pública (entrenadores, planes Básico/Pro/Elite) |
| `login.png` | Inicio de sesión |
| `dashboards.png` | Inicio de socio, entrenador y administrador (versión a seguir) |
| `dashboards-v1.png` | Borrador anterior de los mismos dashboards |

## Notas para implementar

- Marca: **FitPower**.
- Login del diseño pide email + número de socio (sin contraseña). En la API v1 el login es **email + contraseña**; el número de socio puede sumarse después como dato del perfil.
- Admin: acciones **Editar usuarios**, **Gestionar FitPoints**, **Editar entrenadores** (FitPoints queda fuera de v1).
- Entrenador: **Crear rutina**, **Ver mis socios**, **Gestionar ejercicios**.
- Socio: membresía, entrenador asignado, cards de rutinas.

Si copiás HTML de diseño, no dejes llamadas PHP: el JS tiene que hablar con `/api` usando `front/dist/js/api.js`.
