<?php
namespace App\Controllers;

use App\Models\EjercicioModel;
use App\Models\RutinaModel;
use App\Models\SocioModel;
use App\Utils\ApiResponse;
use App\Utils\Auth;

class RutinaController {
    private $db;
    private $modelo;

    public function __construct($db) {
        $this->db = $db;
        $this->modelo = new RutinaModel($db);
    }

    public function listar() {
        $usuario = Auth::exigir(['administrador', 'entrenador', 'socio']);

        if ($usuario['rol'] === 'socio') {
            if (!$usuario['id_socio']) {
                ApiResponse::error('Este usuario no tiene perfil de socio', 403);
                return;
            }
            ApiResponse::ok(['rutinas' => $this->modelo->listarDeSocio((int) $usuario['id_socio'])]);
            return;
        }

        if ($usuario['rol'] === 'entrenador') {
            ApiResponse::ok(['rutinas' => $this->modelo->listarDeEntrenador((int) $usuario['id_entrenador'])]);
            return;
        }

        ApiResponse::ok(['rutinas' => $this->modelo->listarDeEntrenador((int) ($_GET['id_entrenador'] ?? 0))]);
    }

    public function deSocio() {
        $usuario = Auth::exigir(['socio']);
        if (!$usuario['id_socio']) {
            ApiResponse::error('Este usuario no tiene perfil de socio', 403);
            return;
        }
        $rutinas = $this->modelo->listarDeSocio((int) $usuario['id_socio']);
        $entrenador = $this->modelo->entrenadorDeSocio((int) $usuario['id_socio']);
        $vence = null;
        if (!empty($usuario['fecha_alta'])) {
            $vence = (new \DateTime($usuario['fecha_alta']))->modify('+1 year')->format('d/m/Y');
        }
        ApiResponse::ok([
            'rutinas' => $rutinas,
            'entrenador' => $entrenador,
            'membresia' => $usuario['activo'] ? 'activa' : 'inactiva',
            'vence_el' => $vence,
        ]);
    }

    public function ver($id) {
        $usuario = Auth::exigir(['administrador', 'entrenador', 'socio']);
        $rutina = $this->modelo->porId((int) $id);
        if (!$rutina) {
            ApiResponse::error('Rutina no encontrada', 404);
            return;
        }
        if (!$this->puedeVer($usuario, $rutina)) {
            ApiResponse::error('No tenés permiso para ver esta rutina', 403);
            return;
        }

        $ejercicios = $this->modelo->ejerciciosDe((int) $id);
        foreach ($ejercicios as &$ej) {
            $ej['series'] = $this->modelo->seriesDe((int) $ej['id_rutina_ejercicio']);
        }
        unset($ej);

        ApiResponse::ok([
            'rutina' => $rutina,
            'ejercicios' => $ejercicios,
            'solo_lectura' => $usuario['rol'] === 'socio',
        ]);
    }

    public function crear() {
        $usuario = Auth::exigir(['entrenador', 'administrador']);
        if ($usuario['rol'] === 'entrenador' && !$usuario['id_entrenador']) {
            ApiResponse::error('Este usuario no tiene perfil de entrenador', 403);
            return;
        }
        $datos = ApiResponse::input();
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        if ($nombre === '') {
            ApiResponse::error('El nombre de la rutina es obligatorio');
            return;
        }
        $id = $this->modelo->crear([
            'id_entrenador' => $usuario['id_entrenador'] ?? (int) ($datos['id_entrenador'] ?? 0),
            'id_socio' => null,
            'nombre' => $nombre,
            'objetivo' => trim((string) ($datos['objetivo'] ?? '')) ?: null,
            'estado' => 'activa',
        ]);
        ApiResponse::ok(['rutina' => $this->modelo->porId($id)], 201);
    }

    public function actualizar($id) {
        $usuario = Auth::exigir(['entrenador', 'administrador']);
        $rutina = $this->exigirEscritura($usuario, (int) $id);
        if (!$rutina) {
            return;
        }
        $datos = ApiResponse::input();
        $nombre = trim((string) ($datos['nombre'] ?? $rutina['nombre']));
        if ($nombre === '') {
            ApiResponse::error('El nombre de la rutina es obligatorio');
            return;
        }
        $this->modelo->actualizar((int) $id, [
            'nombre' => $nombre,
            'objetivo' => trim((string) ($datos['objetivo'] ?? '')) ?: null,
            'estado' => $datos['estado'] ?? $rutina['estado'],
        ]);
        ApiResponse::ok(['rutina' => $this->modelo->porId((int) $id)]);
    }

    public function borrar($id) {
        $usuario = Auth::exigir(['entrenador', 'administrador']);
        if (!$this->exigirEscritura($usuario, (int) $id)) {
            return;
        }
        $this->modelo->borrar((int) $id);
        ApiResponse::ok(['message' => 'Rutina eliminada']);
    }

    public function asignar($id) {
        $usuario = Auth::exigir(['entrenador', 'administrador']);
        $rutina = $this->exigirEscritura($usuario, (int) $id);
        if (!$rutina) {
            return;
        }
        $datos = ApiResponse::input();
        $idSocio = (int) ($datos['id_socio'] ?? 0);
        $socioModelo = new SocioModel($this->db);
        $socio = $idSocio > 0 ? $socioModelo->porId($idSocio) : null;
        if (!$socio) {
            ApiResponse::error('Socio no encontrado');
            return;
        }
        if ($usuario['rol'] === 'entrenador'
            && (int) ($socio['id_entrenador'] ?? 0) !== (int) $usuario['id_entrenador']) {
            ApiResponse::error('Ese socio no está asignado a tu cargo', 403);
            return;
        }
        $nuevoId = $this->modelo->asignarCopia((int) $id, $idSocio);
        ApiResponse::ok(['rutina' => $this->modelo->porId($nuevoId)], 201);
    }

    public function agregarEjercicio($id) {
        $usuario = Auth::exigir(['entrenador', 'administrador']);
        if (!$this->exigirEscritura($usuario, (int) $id)) {
            return;
        }
        $datos = ApiResponse::input();
        $idEjercicio = (int) ($datos['id_ejercicio'] ?? 0);
        $ejModelo = new EjercicioModel($this->db);
        if ($idEjercicio < 1 || !$ejModelo->porId($idEjercicio)) {
            ApiResponse::error('Ejercicio no encontrado');
            return;
        }
        $idRe = $this->modelo->agregarEjercicio(
            (int) $id,
            $idEjercicio,
            trim((string) ($datos['notas'] ?? '')) ?: null
        );
        ApiResponse::ok(['id_rutina_ejercicio' => $idRe], 201);
    }

    public function reordenarEjercicios($id) {
        $usuario = Auth::exigir(['entrenador', 'administrador']);
        if (!$this->exigirEscritura($usuario, (int) $id)) {
            return;
        }
        $datos = ApiResponse::input();
        $ids = $datos['orden'] ?? [];
        if (!is_array($ids) || !$ids) {
            ApiResponse::error('Mandá el array orden con los ids');
            return;
        }
        $this->modelo->reordenarEjercicios((int) $id, $ids);
        ApiResponse::ok(['message' => 'Orden actualizado']);
    }

    public function actualizarEjercicioRutina($id) {
        $usuario = Auth::exigir(['entrenador', 'administrador']);
        $re = $this->modelo->rutinaEjercicioPorId((int) $id);
        if (!$re) {
            ApiResponse::error('Ejercicio de rutina no encontrado', 404);
            return;
        }
        if (!$this->exigirEscritura($usuario, (int) $re['id_rutina'])) {
            return;
        }
        $datos = ApiResponse::input();
        $this->modelo->actualizarEjercicioRutina((int) $id, [
            'notas' => trim((string) ($datos['notas'] ?? '')) ?: null,
            'id_ejercicio' => (int) ($datos['id_ejercicio'] ?? $re['id_ejercicio']),
        ]);
        ApiResponse::ok(['message' => 'Ejercicio de la rutina actualizado']);
    }

    public function borrarEjercicioRutina($id) {
        $usuario = Auth::exigir(['entrenador', 'administrador']);
        $re = $this->modelo->rutinaEjercicioPorId((int) $id);
        if (!$re) {
            ApiResponse::error('Ejercicio de rutina no encontrado', 404);
            return;
        }
        if (!$this->exigirEscritura($usuario, (int) $re['id_rutina'])) {
            return;
        }
        $this->modelo->borrarEjercicioRutina((int) $id);
        ApiResponse::ok(['message' => 'Ejercicio quitado de la rutina']);
    }

    public function agregarSerie($id) {
        $usuario = Auth::exigir(['entrenador', 'administrador']);
        $re = $this->modelo->rutinaEjercicioPorId((int) $id);
        if (!$re) {
            ApiResponse::error('Ejercicio de rutina no encontrado', 404);
            return;
        }
        if (!$this->exigirEscritura($usuario, (int) $re['id_rutina'])) {
            return;
        }
        $datos = ApiResponse::input();
        $reps = (int) ($datos['repeticiones'] ?? 0);
        if ($reps < 1) {
            ApiResponse::error('Las repeticiones son obligatorias');
            return;
        }
        $idSerie = $this->modelo->agregarSerie((int) $id, [
            'repeticiones' => $reps,
            'peso_kg' => isset($datos['peso_kg']) && $datos['peso_kg'] !== '' ? $datos['peso_kg'] : null,
            'descanso_segundos' => $datos['descanso_segundos'] ?? null,
        ]);
        ApiResponse::ok(['id_serie' => $idSerie], 201);
    }

    public function actualizarSerie($id) {
        $usuario = Auth::exigir(['entrenador', 'administrador']);
        $serie = $this->modelo->seriePorId((int) $id);
        if (!$serie) {
            ApiResponse::error('Serie no encontrada', 404);
            return;
        }
        if (!$this->exigirEscritura($usuario, (int) $serie['id_rutina'])) {
            return;
        }
        $datos = ApiResponse::input();
        $this->modelo->actualizarSerie((int) $id, [
            'repeticiones' => (int) ($datos['repeticiones'] ?? $serie['repeticiones']),
            'peso_kg' => array_key_exists('peso_kg', $datos) ? ($datos['peso_kg'] === '' || $datos['peso_kg'] === null ? null : $datos['peso_kg']) : $serie['peso_kg'],
            'descanso_segundos' => $datos['descanso_segundos'] ?? $serie['descanso_segundos'],
            'orden' => $datos['orden'] ?? $serie['orden'],
        ]);
        ApiResponse::ok(['message' => 'Serie actualizada']);
    }

    public function borrarSerie($id) {
        $usuario = Auth::exigir(['entrenador', 'administrador']);
        $serie = $this->modelo->seriePorId((int) $id);
        if (!$serie) {
            ApiResponse::error('Serie no encontrada', 404);
            return;
        }
        if (!$this->exigirEscritura($usuario, (int) $serie['id_rutina'])) {
            return;
        }
        $this->modelo->borrarSerie((int) $id);
        ApiResponse::ok(['message' => 'Serie eliminada']);
    }

    public function reordenarSeries($id) {
        $usuario = Auth::exigir(['entrenador', 'administrador']);
        $re = $this->modelo->rutinaEjercicioPorId((int) $id);
        if (!$re) {
            ApiResponse::error('Ejercicio de rutina no encontrado', 404);
            return;
        }
        if (!$this->exigirEscritura($usuario, (int) $re['id_rutina'])) {
            return;
        }
        $datos = ApiResponse::input();
        $ids = $datos['orden'] ?? [];
        if (!is_array($ids) || !$ids) {
            ApiResponse::error('Mandá el array orden con los ids');
            return;
        }
        $this->modelo->reordenarSeries((int) $id, $ids);
        ApiResponse::ok(['message' => 'Orden de series actualizado']);
    }

    private function puedeVer(array $usuario, array $rutina): bool {
        if ($usuario['rol'] === 'administrador') {
            return true;
        }
        if ($usuario['rol'] === 'entrenador') {
            return (int) $rutina['id_entrenador'] === (int) $usuario['id_entrenador'];
        }
        return $usuario['rol'] === 'socio'
            && $rutina['id_socio'] !== null
            && (int) $rutina['id_socio'] === (int) $usuario['id_socio'];
    }

    private function exigirEscritura(array $usuario, int $idRutina): ?array {
        $rutina = $this->modelo->porId($idRutina);
        if (!$rutina) {
            ApiResponse::error('Rutina no encontrada', 404);
            return null;
        }
        if ($usuario['rol'] === 'administrador') {
            return $rutina;
        }
        if ((int) $rutina['id_entrenador'] !== (int) $usuario['id_entrenador']) {
            ApiResponse::error('No podés modificar esta rutina', 403);
            return null;
        }
        return $rutina;
    }
}
