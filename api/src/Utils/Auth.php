<?php
namespace App\Utils;

class Auth {
    public static function iniciarSesion(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function usuario(): ?array {
        return $_SESSION['usuario'] ?? null;
    }

    public static function guardar(array $usuario): void {
        $_SESSION['usuario'] = $usuario;
    }

    public static function salir(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function exigir(?array $roles = null): array {
        $usuario = self::usuario();
        if (!$usuario) {
            ApiResponse::error('No autenticado', 401);
            exit;
        }
        if ($roles && !in_array($usuario['rol'], $roles, true)) {
            ApiResponse::error('No tenés permiso para esta acción', 403);
            exit;
        }
        return $usuario;
    }
}
