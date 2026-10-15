<?php
namespace App\Controllers;

use App\Models\GrupoMuscularModel;
use App\Utils\ApiResponse;
use App\Utils\Auth;
use PDOException;

class GrupoMuscularController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listar() {
        Auth::exigir(['administrador', 'entrenador']);
        $modelo = new GrupoMuscularModel($this->db);
        ApiResponse::ok(['grupos' => $modelo->listar()]);
    }

    public function crear() {
        Auth::exigir(['administrador']);
        $datos = ApiResponse::input();
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        if ($nombre === '') {
            ApiResponse::error('El nombre del grupo es obligatorio');
            return;
        }
        $modelo = new GrupoMuscularModel($this->db);
        if ($modelo->porNombre($nombre)) {
            ApiResponse::error('Ese grupo ya existe');
            return;
        }
        try {
            $id = $modelo->crear($nombre);
        } catch (PDOException $e) {
            ApiResponse::error('No se pudo crear el grupo', 500);
            return;
        }
        ApiResponse::ok(['grupo' => $modelo->porId($id)], 201);
    }

    public function actualizar($id) {
        Auth::exigir(['administrador']);
        $modelo = new GrupoMuscularModel($this->db);
        $actual = $modelo->porId((int) $id);
        if (!$actual) {
            ApiResponse::error('Grupo no encontrado', 404);
            return;
        }
        $datos = ApiResponse::input();
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        if ($nombre === '') {
            ApiResponse::error('El nombre del grupo es obligatorio');
            return;
        }
        $otro = $modelo->porNombre($nombre);
        if ($otro && (int) $otro['id_grupo'] !== (int) $id) {
            ApiResponse::error('Ese grupo ya existe');
            return;
        }
        $modelo->actualizar((int) $id, $actual['nombre'], $nombre);
        ApiResponse::ok(['grupo' => $modelo->porId((int) $id)]);
    }

    public function borrar($id) {
        Auth::exigir(['administrador']);
        $modelo = new GrupoMuscularModel($this->db);
        $actual = $modelo->porId((int) $id);
        if (!$actual) {
            ApiResponse::error('Grupo no encontrado', 404);
            return;
        }
        if ($modelo->contarEjercicios($actual['nombre']) > 0) {
            ApiResponse::error('No se puede borrar: hay ejercicios en este grupo', 409);
            return;
        }
        $modelo->borrar((int) $id);
        ApiResponse::ok(['message' => 'Grupo eliminado']);
    }
}
