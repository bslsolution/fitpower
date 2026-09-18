<?php
namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Utils\ApiResponse;
use App\Utils\Auth;

class AuthController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function login() {
        $datos = ApiResponse::input();
        $email = trim((string) ($datos['email'] ?? ''));
        $password = (string) ($datos['password'] ?? '');

        if ($email === '' || $password === '') {
            ApiResponse::error('Completá email y contraseña');
            return;
        }

        $modelo = new UsuarioModel($this->db);
        $fila = $modelo->porEmail($email);
        if (!$fila || !password_verify($password, $fila['password_hash'])) {
            ApiResponse::error('Email o contraseña incorrectos', 401);
            return;
        }
        if ((int) $fila['activo'] !== 1) {
            ApiResponse::error('La cuenta está desactivada', 403);
            return;
        }

        $usuario = $modelo->publico($fila);
        Auth::guardar($usuario);
        ApiResponse::ok(['usuario' => $usuario]);
    }

    public function logout() {
        Auth::salir();
        ApiResponse::ok(['message' => 'Sesión cerrada']);
    }

    public function me() {
        $usuario = Auth::exigir();
        if (($usuario['rol'] ?? '') === 'socio' && !empty($usuario['id_socio'])) {
            $fp = new \App\Models\FitpointsModel($this->db);
            $usuario['fitpoints'] = $fp->puntosDeSocio((int) $usuario['id_socio']);
            Auth::guardar($usuario);
        }
        $notif = new \App\Models\NotificacionModel($this->db);
        $usuario['notificaciones_nuevas'] = $notif->contarNuevas(
            (string) ($usuario['rol'] ?? ''),
            (int) ($usuario['id_usuario'] ?? 0)
        );
        ApiResponse::ok(['usuario' => $usuario]);
    }
}
