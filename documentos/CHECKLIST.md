# Checklist del proyecto — FitPower

Marcá cada ítem cuando esté hecho. Este es el orden de trabajo para el equipo.

## 0. Arranque

- [x] Carpeta `documentos/` con checklist y documentación
- [x] Carpeta `documentos/base-de-datos/` (MER, pasaje a tablas, SQL)
- [x] Esquema SQL alineado con Docker (`api/config/init.sql`)
- [x] Carpeta `assets/` con landing, login y dashboards (entrenador, socio, admin)
- [x] Repositorio en GitHub para trabajar en equipo

## 1. Base de datos

- [x] MER definido
- [x] Pasaje a tablas (PK / FK / cardinalidades)
- [x] Script SQL de creación + datos de prueba
- [x] Levantar Docker y verificar tablas en phpMyAdmin (`http://localhost:8081`)
- [x] Confirmar `/api/estado` responde y cuenta usuarios

## 2. API (MVC)

- [x] Auth: login (POST) y sesión/token
- [x] Middleware de roles: `administrador`, `entrenador`, `socio`
- [x] Solo el administrador registra usuarios
- [x] CRUD de ejercicios (catálogo)
- [x] CRUD de rutinas (entrenador)
- [x] Agregar / editar / borrar ejercicios dentro de una rutina
- [x] Reordenar ejercicios de la rutina (`PUT` de `orden`)
- [x] Series por ejercicio: crear, editar, borrar, reordenar
- [x] Asignar rutina a un socio
- [x] Socio: listar y ver detalle de sus rutinas (solo lectura)

## 3. Frontend (API-driven)

- [x] Integrar maquetas de `assets/` en `front/`
- [x] Landing pública
- [x] Login (HTML nunca habla con PHP directo: todo por `front/dist/js/api.js`)
- [x] Dashboard administrador: alta de usuarios
- [x] Dashboard entrenador: armar rutina, mover ejercicios, asignar a socio
- [x] Dashboard socio: ver rutinas, ejercicios y series
- [x] Socio: cargar peso por serie y ver progreso
- [x] Estilos desktop + mobile

## 4. Calidad y entrega

- [ ] Probar cada rol (admin / entrenador / socio) de punta a punta
- [ ] No commitear `.env` ni contraseñas reales
- [ ] README actualizado para que un compañero pueda clonar y levantar Docker

## 5. Más adelante (fuera de v1)

- [ ] FitPoints (aparece en el mockup del administrador)
