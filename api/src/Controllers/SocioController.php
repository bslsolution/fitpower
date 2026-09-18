<?php
namespace App\Controllers;

use App\Models\RutinaModel;
use App\Models\SocioModel;
use App\Utils\ApiResponse;
use App\Utils\Auth;

class SocioController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listar() {
        $usuario = Auth::exigir(['administrador', 'entrenador']);
        $modelo = new SocioModel($this->db);
        if ($usuario['rol'] === 'entrenador') {
            if (!$usuario['id_entrenador']) {
                ApiResponse::error('Este usuario no tiene perfil de entrenador', 403);
                return;
            }
            ApiResponse::ok(['socios' => $modelo->listarDeEntrenador((int) $usuario['id_entrenador'])]);
            return;
        }
        ApiResponse::ok(['socios' => $modelo->listar()]);
    }

    public function listarEntrenadores() {
        Auth::exigir(['administrador', 'entrenador']);
        $modelo = new SocioModel($this->db);
        ApiResponse::ok(['entrenadores' => $modelo->listarEntrenadores()]);
    }

    public function asignarEntrenador($id) {
        Auth::exigir(['administrador']);
        $modelo = new SocioModel($this->db);
        $socio = $modelo->porId((int) $id);
        if (!$socio) {
            ApiResponse::error('Socio no encontrado', 404);
            return;
        }

        $datos = ApiResponse::input();
        $idEntrenador = $datos['id_entrenador'] ?? null;
        if ($idEntrenador === '' || $idEntrenador === null || (int) $idEntrenador < 1) {
            $idEntrenador = null;
        } else {
            $idEntrenador = (int) $idEntrenador;
            if (!$modelo->entrenadorExiste($idEntrenador)) {
                ApiResponse::error('Entrenador no encontrado');
                return;
            }
        }

        $modelo->asignarEntrenador((int) $id, $idEntrenador);
        ApiResponse::ok(['socio' => $modelo->porId((int) $id)]);
    }

    public function resumenEntrenador() {
        $usuario = Auth::exigir(['entrenador']);
        if (!$usuario['id_entrenador']) {
            ApiResponse::error('Este usuario no tiene perfil de entrenador', 403);
            return;
        }
        $rutinas = new RutinaModel($this->db);
        $socios = new SocioModel($this->db);
        $idEntrenador = (int) $usuario['id_entrenador'];
        ApiResponse::ok([
            'resumen' => $rutinas->resumenEntrenador($idEntrenador),
            'socios' => $socios->listarDeEntrenador($idEntrenador),
        ]);
    }
}
