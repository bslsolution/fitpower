<?php
namespace App\Controllers;

use App\Models\NotificacionModel;
use App\Models\SocioModel;
use App\Utils\ApiResponse;
use App\Utils\Auth;

class NotificacionController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listar() {
        $usuario = Auth::exigir(['administrador', 'entrenador', 'socio']);
        $modelo = new NotificacionModel($this->db);
        $id = (int) $usuario['id_usuario'];
        ApiResponse::ok([
            'notificaciones' => $modelo->listarParaUsuario($usuario['rol'], $id),
            'nuevas' => $modelo->contarNuevas($usuario['rol'], $id),
        ]);
    }

    public function marcarLeidas() {
        $usuario = Auth::exigir(['administrador', 'entrenador', 'socio']);
        $modelo = new NotificacionModel($this->db);
        $modelo->marcarLeidas($usuario['rol'], (int) $usuario['id_usuario']);
        ApiResponse::ok(['nuevas' => 0]);
    }

    public function enviar() {
        Auth::exigir(['administrador']);
        $datos = ApiResponse::input();
        $inactividad = !empty($datos['inactividad']);
        $mensualidad = !empty($datos['mensualidad']);
        if (!$inactividad && !$mensualidad) {
            ApiResponse::error('Elegí al menos un tipo de aviso');
            return;
        }
        $socios = new SocioModel($this->db);
        $notif = new NotificacionModel($this->db);
        $enviadas = 0;
        if ($inactividad) {
            $enviadas += $this->avisar($notif, $socios->inactivos(7), 'inactividad');
        }
        if ($mensualidad) {
            $enviadas += $this->avisar($notif, $socios->mensualidadVencida(), 'mensualidad_vencida');
        }
        ApiResponse::ok(['enviadas' => $enviadas]);
    }

    private function avisar(NotificacionModel $notif, array $socios, string $tipo): int {
        $n = 0;
        foreach ($socios as $s) {
            $idSocio = (int) $s['id_socio'];
            $idUser = (int) ($s['id_usuario'] ?? 0);
            $base = [
                'id_socio' => $idSocio,
                'nombre' => $s['nombre'] ?? '',
                'apellido' => $s['apellido'] ?? '',
            ];
            if ($idUser > 0 && !$notif->existeNueva('socio', $tipo, $idUser, $idSocio)) {
                $notif->crear('socio', $tipo, $base + ['para' => 'socio'], $idUser);
                $n++;
            }
            $idEnt = (int) ($s['id_usuario_entrenador'] ?? 0);
            if ($idEnt > 0 && !$notif->existeNueva('entrenador', $tipo, $idEnt, $idSocio)) {
                $notif->crear('entrenador', $tipo, $base + ['para' => 'entrenador'], $idEnt);
                $n++;
            }
        }
        return $n;
    }
}
