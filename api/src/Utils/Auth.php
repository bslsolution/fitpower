<?php
namespace App\Utils;

class Auth {
    public static function iniciarSesion(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $enDocker = file_exists('/.dockerenv') || file_exists('/run/.containerenv');
        if ($enDocker) {
            $dir = '/tmp/fitpower-sessions';
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            if (is_dir($dir) && is_writable($dir)) {
                session_save_path($dir);
            }
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443)
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $https,
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
