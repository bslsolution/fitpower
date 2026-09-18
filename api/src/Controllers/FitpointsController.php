<?php
namespace App\Controllers;

use App\Models\FitpointsModel;
use App\Models\RecompensaModel;
use App\Utils\ApiResponse;
use App\Utils\Auth;

class FitpointsController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function ver() {
        $usuario = $this->exigirSocio();
        if (!$usuario) {
            return;
        }
        $puntos = new FitpointsModel($this->db);
        $recompensas = new RecompensaModel($this->db);
        ApiResponse::ok([
            'puntos' => $puntos->puntosDeSocio((int) $usuario['id_socio']),
            'recompensas' => $recompensas->listar(),
            'puntos_por_rutina' => FitpointsModel::PUNTOS_POR_RUTINA,
        ]);
    }

    public function completarRutina() {
        $usuario = $this->exigirSocio();
        if (!$usuario) {
            return;
        }
        $datos = ApiResponse::input();
        $idEntrenamiento = (int) ($datos['id_entrenamiento'] ?? 0);
        if ($idEntrenamiento <= 0) {
            ApiResponse::error('Entrenamiento no encontrado', 404);
            return;
        }
        $modelo = new FitpointsModel($this->db);
        $res = $modelo->completarRutina((int) $usuario['id_socio'], $idEntrenamiento);
        if (!$res['ok']) {
            $codigo = ($res['error'] ?? '') === 'Entrenamiento no encontrado' ? 404 : 400;
            ApiResponse::error($res['error'] ?? 'Entrenamiento no encontrado', $codigo);
            return;
        }
        $usuario['fitpoints'] = $res['puntos'];
        Auth::guardar($usuario);
        ApiResponse::ok([
            'puntos' => $res['puntos'],
            'otorgados' => $res['otorgados'],
            'sumados' => $res['otorgados'] ? FitpointsModel::PUNTOS_POR_RUTINA : 0,
        ]);
    }

    public function canjear() {
        $usuario = $this->exigirSocio();
        if (!$usuario) {
            return;
        }
        $datos = ApiResponse::input();
        $idRecompensa = (int) ($datos['id_recompensa'] ?? 0);
        if ($idRecompensa <= 0) {
            ApiResponse::error('Recompensa no encontrada', 404);
            return;
        }
        $modelo = new FitpointsModel($this->db);
        $res = $modelo->canjear((int) $usuario['id_socio'], $idRecompensa);
        if (!$res['ok']) {
            $codigo = ($res['error'] ?? '') === 'Recompensa no encontrada' ? 404 : 400;
            ApiResponse::error($res['error'] ?? 'Recompensa no encontrada', $codigo);
            return;
        }
        $usuario['fitpoints'] = $res['puntos'];
        Auth::guardar($usuario);
        ApiResponse::ok([
            'puntos' => $res['puntos'],
            'nombre' => $res['nombre'],
            'costo' => $res['costo'],
        ]);
    }

    private function exigirSocio(): ?array {
        $usuario = Auth::exigir(['socio']);
        if (!$usuario['id_socio']) {
            ApiResponse::error('Este usuario no tiene perfil de socio', 403);
            return null;
        }
        return $usuario;
    }
}
