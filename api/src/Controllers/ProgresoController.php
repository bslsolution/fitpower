<?php
namespace App\Controllers;

use App\Models\ProgresoModel;
use App\Models\RutinaModel;
use App\Utils\ApiResponse;
use App\Utils\Auth;

class ProgresoController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listar() {
        $usuario = $this->exigirSocio();
        if (!$usuario) {
            return;
        }
        $modelo = new ProgresoModel($this->db);
        ApiResponse::ok(['entrenamientos' => $modelo->listarDeSocio((int) $usuario['id_socio'])]);
    }

    public function ver($id) {
        $usuario = $this->exigirSocio();
        if (!$usuario) {
            return;
        }
        $modelo = new ProgresoModel($this->db);
        $entrenamiento = $modelo->porId((int) $id);
        if (!$entrenamiento || (int) $entrenamiento['id_socio'] !== (int) $usuario['id_socio']) {
            ApiResponse::error('Entrenamiento no encontrado', 404);
            return;
        }
        $series = $modelo->seriesDe((int) $id);
        $ejercicios = [];
        foreach ($series as $serie) {
            $idEj = (int) $serie['id_ejercicio'];
            if (!isset($ejercicios[$idEj])) {
                $ejercicios[$idEj] = [
                    'id_ejercicio' => $idEj,
                    'nombre' => $serie['nombre_ejercicio'],
                    'grupo_muscular' => $serie['grupo_muscular'],
                    'series' => [],
                ];
            }
            $ejercicios[$idEj]['series'][] = [
                'id_serie_origen' => $serie['id_serie_origen'] !== null ? (int) $serie['id_serie_origen'] : null,
                'serie_orden' => (int) $serie['serie_orden'],
                'repeticiones' => (int) $serie['repeticiones'],
                'peso_kg' => $serie['peso_kg'] !== null ? (float) $serie['peso_kg'] : null,
                'descanso_segundos' => $serie['descanso_segundos'] !== null ? (int) $serie['descanso_segundos'] : null,
            ];
        }
        ApiResponse::ok([
            'entrenamiento' => $entrenamiento,
            'ejercicios' => array_values($ejercicios),
        ]);
    }

    public function terminar() {
        $usuario = $this->exigirSocio();
        if (!$usuario) {
            return;
        }
        $datos = ApiResponse::input();
        $idSerie = (int) ($datos['id_serie'] ?? 0);
        $rutinas = new RutinaModel($this->db);
        $serie = $idSerie > 0 ? $rutinas->seriePorId($idSerie) : null;
        if (!$serie || (int) ($serie['id_socio'] ?? 0) !== (int) $usuario['id_socio']) {
            ApiResponse::error('Serie no encontrada', 404);
            return;
        }

        $peso = $this->parsePeso($datos['peso_kg'] ?? null);
        if ($peso === false) {
            ApiResponse::error('El peso tiene que ser un número en kg');
            return;
        }

        $modelo = new ProgresoModel($this->db);
        $idEntrenamiento = (int) ($datos['id_entrenamiento'] ?? 0);
        if ($idEntrenamiento > 0) {
            $actual = $modelo->porId($idEntrenamiento);
            if (!$actual || (int) $actual['id_socio'] !== (int) $usuario['id_socio']) {
                ApiResponse::error('Entrenamiento no encontrado', 404);
                return;
            }
        } else {
            $idEntrenamiento = $modelo->crearEntrenamiento(
                (int) $usuario['id_socio'],
                (int) $serie['id_rutina'],
                (string) ($serie['rutina_nombre'] ?? 'Entrenamiento')
            );
        }

        $modelo->guardarSerie($idEntrenamiento, [
            'id_serie_origen' => (int) $serie['id_serie'],
            'id_ejercicio' => (int) $serie['id_ejercicio'],
            'nombre_ejercicio' => (string) ($serie['ejercicio_nombre'] ?? 'Ejercicio'),
            'grupo_muscular' => $serie['grupo_muscular'] ?? null,
            'serie_orden' => (int) $serie['orden'],
            'repeticiones' => (int) $serie['repeticiones'],
            'peso_kg' => $peso,
            'descanso_segundos' => $serie['descanso_segundos'] !== null ? (int) $serie['descanso_segundos'] : null,
        ]);
        $rutinas->actualizarPesoSerie((int) $serie['id_serie'], $peso);

        ApiResponse::ok([
            'entrenamiento' => $modelo->porId($idEntrenamiento),
            'message' => 'Serie terminada',
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

    private function parsePeso($valor) {
        if ($valor === null || $valor === '') {
            return null;
        }
        if (!is_numeric($valor)) {
            return false;
        }
        $n = round((float) $valor, 2);
        if ($n < 0 || $n > 999.99) {
            return false;
        }
        return $n;
    }
}
