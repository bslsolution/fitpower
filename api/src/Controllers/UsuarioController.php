<?php
namespace App\Controllers;

use App\Models\RutinaModel;
use App\Models\SocioModel;
use App\Models\UsuarioModel;
use App\Utils\ApiResponse;
use App\Utils\Auth;
use PDOException;

class UsuarioController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listar() {
        Auth::exigir(['administrador']);
        $modelo = new UsuarioModel($this->db);
        $filas = array_map([$modelo, 'publico'], $modelo->listar());
        ApiResponse::ok(['usuarios' => $filas]);
    }

    public function crear() {
        Auth::exigir(['administrador']);
        $datos = ApiResponse::input();

        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $apellido = trim((string) ($datos['apellido'] ?? ''));
        $email = trim((string) ($datos['email'] ?? ''));
        $password = (string) ($datos['password'] ?? '');
        $rol = trim((string) ($datos['rol'] ?? 'socio'));
        $telefono = trim((string) ($datos['telefono'] ?? '')) ?: null;

        $rolesOk = ['administrador', 'entrenador', 'socio'];
        if ($nombre === '' || $apellido === '' || $email === '' || $password === '') {
            ApiResponse::error('Nombre, apellido, email y contraseña son obligatorios');
            return;
        }
        if (!in_array($rol, $rolesOk, true)) {
            ApiResponse::error('Rol inválido');
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ApiResponse::error('Email inválido');
            return;
        }

        $modelo = new UsuarioModel($this->db);
        if ($modelo->porEmail($email)) {
            ApiResponse::error('Ese email ya está registrado');
            return;
        }

        $idRol = $modelo->idRolPorNombre($rol);
        if (!$idRol) {
            ApiResponse::error('No se encontró el rol');
            return;
        }

        $idEntrenador = null;
        if ($rol === 'socio') {
            $rawEntrenador = $datos['id_entrenador'] ?? null;
            if ($rawEntrenador !== '' && $rawEntrenador !== null && (int) $rawEntrenador > 0) {
                $idEntrenador = (int) $rawEntrenador;
                $socioModelo = new SocioModel($this->db);
                if (!$socioModelo->entrenadorExiste($idEntrenador)) {
                    ApiResponse::error('Entrenador no encontrado');
                    return;
                }
            }
        }

        try {
            $id = $modelo->crear([
                'id_rol' => $idRol,
                'rol' => $rol,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                'telefono' => $telefono,
                'especialidad' => $datos['especialidad'] ?? null,
                'bio' => $datos['bio'] ?? null,
                'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? null,
                'notas' => $datos['notas'] ?? null,
                'id_entrenador' => $idEntrenador,
            ]);
        } catch (PDOException $e) {
            ApiResponse::error('No se pudo crear el usuario', 500);
            return;
        }

        $creado = $modelo->porId($id);
        ApiResponse::ok(['usuario' => $modelo->publico($creado)], 201);
    }

    public function actualizar($id) {
        Auth::exigir(['administrador']);
        $modelo = new UsuarioModel($this->db);
        $fila = $modelo->porId((int) $id);
        if (!$fila) {
            ApiResponse::error('Usuario no encontrado', 404);
            return;
        }

        $datos = ApiResponse::input();
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $apellido = trim((string) ($datos['apellido'] ?? ''));
        $email = trim((string) ($datos['email'] ?? ''));
        $password = (string) ($datos['password'] ?? '');
        $telefono = trim((string) ($datos['telefono'] ?? '')) ?: null;

        if ($nombre === '' || $apellido === '' || $email === '') {
            ApiResponse::error('Nombre, apellido y email son obligatorios');
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ApiResponse::error('Email inválido');
            return;
        }

        $otro = $modelo->porEmail($email);
        if ($otro && (int) $otro['id_usuario'] !== (int) $id) {
            ApiResponse::error('Ese email ya está registrado');
            return;
        }

        $payload = [
            'nombre' => $nombre,
            'apellido' => $apellido,
            'email' => $email,
            'telefono' => $telefono,
            'rol' => $fila['rol'] ?? '',
        ];
        if ($password !== '') {
            $payload['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        if (($fila['rol'] ?? '') === 'socio') {
            $rawEntrenador = $datos['id_entrenador'] ?? null;
            if ($rawEntrenador !== '' && $rawEntrenador !== null && (int) $rawEntrenador > 0) {
                $idEntrenador = (int) $rawEntrenador;
                $socioModelo = new SocioModel($this->db);
                if (!$socioModelo->entrenadorExiste($idEntrenador)) {
                    ApiResponse::error('Entrenador no encontrado');
                    return;
                }
                $payload['id_entrenador'] = $idEntrenador;
            } else {
                $payload['id_entrenador'] = null;
            }
        }

        try {
            $modelo->actualizar((int) $id, $payload);
        } catch (PDOException $e) {
            ApiResponse::error('No se pudo actualizar el usuario', 500);
            return;
        }

        $actualizado = $modelo->porId((int) $id);
        ApiResponse::ok(['usuario' => $modelo->publico($actualizado)]);
    }

    public function actualizarMembresia($id) {
        Auth::exigir(['administrador']);
        $modelo = new UsuarioModel($this->db);
        $fila = $modelo->porId((int) $id);
        if (!$fila) {
            ApiResponse::error('Usuario no encontrado', 404);
            return;
        }
        if (($fila['rol'] ?? '') !== 'socio') {
            ApiResponse::error('La membresía solo aplica a socios');
            return;
        }
        $datos = ApiResponse::input();
        $valor = strtolower(trim((string) ($datos['membresia'] ?? '')));
        if (!in_array($valor, ['activa', 'inactiva'], true)) {
            ApiResponse::error('Elegí una membresía válida');
            return;
        }
        $modelo->actualizarMembresia((int) $id, $valor === 'activa');
        $actualizado = $modelo->porId((int) $id);
        ApiResponse::ok(['usuario' => $modelo->publico($actualizado)]);
    }

    public function borrar($id) {
        $yo = Auth::exigir(['administrador']);
        $modelo = new UsuarioModel($this->db);
        $fila = $modelo->porId((int) $id);
        if (!$fila) {
            ApiResponse::error('Usuario no encontrado', 404);
            return;
        }
        if ((int) $id === (int) $yo['id_usuario']) {
            ApiResponse::error('No podés eliminar tu propio usuario');
            return;
        }
        if (($fila['rol'] ?? '') === 'administrador' && $modelo->contarPorRol('administrador') <= 1) {
            ApiResponse::error('No se puede eliminar al último administrador');
            return;
        }
        try {
            $modelo->borrar((int) $id);
        } catch (PDOException $e) {
            ApiResponse::error('No se pudo eliminar el usuario', 500);
            return;
        }
        ApiResponse::ok(['eliminado' => (int) $id]);
    }

    public function resumen() {
        $usuario = Auth::exigir(['administrador']);
        $rutinas = new RutinaModel($this->db);
        $socios = new SocioModel($this->db);
        ApiResponse::ok([
            'resumen' => $rutinas->resumenAdmin(),
            'socios' => $socios->listar(),
            'admin' => $usuario,
        ]);
    }
}
