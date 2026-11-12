<?php
namespace App\Models;

class EjercicioModel {
    private $db;
    public const GIF_BASE = '/front/dist/imagenes/ejercicios/';

    public function __construct($db) {
        $this->db = $db;
        try {
            self::asegurarColumnaImagen($db);
            self::asegurarCatalogo($db);
        } catch (\Throwable $e) {
            // Si la columna no se puede crear, no bloquea el resto de la API.
        }
    }

    public static function asegurarColumnaImagen($db): void {
        static $hecho = false;
        if ($hecho) {
            return;
        }
        $hecho = true;
        $col = $db->query("SHOW COLUMNS FROM ejercicios LIKE 'imagen'")->fetch();
        if (!$col) {
            $db->exec('ALTER TABLE ejercicios ADD COLUMN imagen VARCHAR(255) NULL');
        }
        $filas = $db->query(
            'SELECT id_ejercicio, nombre, grupo_muscular, imagen FROM ejercicios'
        )->fetchAll(\PDO::FETCH_ASSOC);
        $st = $db->prepare('UPDATE ejercicios SET imagen = :img WHERE id_ejercicio = :id');
        foreach ($filas as $fila) {
            if (!empty($fila['imagen'])) {
                continue;
            }
            $st->execute([
                'img' => self::rutaGif($fila['nombre'] ?? '', $fila['grupo_muscular'] ?? ''),
                'id' => $fila['id_ejercicio'],
            ]);
        }
    }

    public static function catalogoBase(): array {
        $base = self::GIF_BASE;
        return [
            ['Sentadilla', 'Pierna', 'Barra en espalda, cadera atrás, rodillas alineadas.', $base . 'sentadilla.gif'],
            ['Sentadilla búlgara', 'Pierna', 'Pie trasero apoyado, baja con el torso erguido.', $base . 'sentadilla-bulgara.gif'],
            ['Peso muerto', 'Pierna', 'Barra cerca de las piernas, espalda neutra al levantar.', $base . 'peso-muerto.gif'],
            ['Peso muerto rumano', 'Pierna', 'Cadera atrás, rodillas semiflexionadas, barra pegada.', $base . 'peso-muerto-rumano.gif'],
            ['Extensión de cuádriceps', 'Pierna', 'Extiende las rodillas sin despegar la espalda del respaldo.', $base . 'extension-cuadriceps.gif'],
            ['Curl femoral', 'Pierna', 'Acostado, flexioná las rodillas sin arquear la lumbar.', $base . 'extensiones.gif'],
            ['Prensa de piernas', 'Pierna', 'Bajá el trineo controlado y empujá con talones.', $base . 'prensa-piernas.gif'],
            ['Zancadas', 'Pierna', 'Paso largo, rodilla de atrás cerca del piso.', $base . 'zancadas.gif'],
            ['Hip thrust', 'Pierna', 'Omóplatos en el banco, empujá la cadera arriba.', $base . 'hip-thrust.gif'],
            ['Elevación de gemelos', 'Pierna', 'Subí a la punta de los pies y bajá lento.', $base . 'gemelos.gif'],
            ['Press banca', 'Pecho', 'Escápulas juntas, bajada controlada al pecho.', $base . 'press-banca.gif'],
            ['Press inclinado', 'Pecho', 'Banco inclinado, barra al pecho alto.', $base . 'press-inclinado.gif'],
            ['Press inclinado con mancuernas', 'Pecho', 'Mancuernas en banco inclinado, recorré hasta arriba.', $base . 'press-inclinado-mancuernas.gif'],
            ['Press declinado', 'Pecho', 'Banco declinado, barra al pecho bajo.', $base . 'press-declinado.gif'],
            ['Peck deck', 'Pecho', 'Juntá los brazos al frente sin encoger hombros.', $base . 'peck-deck.gif'],
            ['Aperturas con mancuernas', 'Pecho', 'Brazos semi-extendidos, abrí y cerrá sobre el pecho.', $base . 'aperturas.gif'],
            ['Flexiones', 'Pecho', 'Cuerpo en línea, pecho cerca del piso.', $base . 'flexiones.gif'],
            ['Fondos', 'Pecho', 'Bajada en paralelas hasta 90 grados.', $base . 'fondos.gif'],
            ['Remo con barra', 'Espalda', 'Torso estable, llevar la barra al abdomen.', $base . 'remo.gif'],
            ['Remo con mancuerna', 'Espalda', 'Una mano en el banco, codo pegado al torso.', $base . 'remo-mancuerna.gif'],
            ['Remo en T', 'Espalda', 'Pecho apoyado, llevá el peso hacia la cadera.', $base . 'remo-t.gif'],
            ['Jalón al pecho', 'Espalda', 'Tirón al pecho alto, omóplatos juntos.', $base . 'jalon-pecho.gif'],
            ['Dominadas', 'Espalda', 'Pecho al frente, barbilla por encima de la barra.', $base . 'dominadas.gif'],
            ['Remo en polea', 'Espalda', 'Sentado, llevá el agarre al abdomen.', $base . 'remo-polea.gif'],
            ['Remo sentado', 'Espalda', 'Máquina sentado, omóplatos atrás al tirar.', $base . 'remo-sentado.gif'],
            ['Hiperextensiones', 'Espalda', 'Banco romano, subí el torso en línea.', $base . 'hiperextensiones.gif'],
            ['Face pull', 'Espalda', 'Polea alta a la cara, abrí los codos.', $base . 'face-pull.gif'],
            ['Press militar', 'Hombros', 'Barra desde los hombros, sin arquear la lumbar.', $base . 'press-militar.gif'],
            ['Elevaciones laterales', 'Hombros', 'Mancuernas al costado, hasta la altura del hombro.', $base . 'elevaciones-laterales.gif'],
            ['Elevaciones frontales', 'Hombros', 'Subí las mancuernas al frente, brazos semi-extendidos.', $base . 'elevaciones.gif'],
            ['Pájaros', 'Hombros', 'Torso inclinado, abrí los brazos a los costados.', $base . 'pajaros.gif'],
            ['Arnold press', 'Hombros', 'Rotá las palmas al subir las mancuernas.', $base . 'arnold-press.gif'],
            ['Encogimientos', 'Hombros', 'Subí los hombros hacia las orejas, sin doblar brazos.', $base . 'encogimientos.gif'],
            ['Curl de bíceps', 'Brazos', 'Barra o mancuernas, codos fijos al costado.', $base . 'curl.gif'],
            ['Curl martillo', 'Brazos', 'Agarre neutro, subí sin balancear el torso.', $base . 'curl-martillo.gif'],
            ['Curl predicador', 'Brazos', 'Brazo apoyado, recorré completo sin despegar.', $base . 'curl-predicador.gif'],
            ['Curl concentración', 'Brazos', 'Codo interno en el muslo, subí controlado.', $base . 'curl-concentracion.gif'],
            ['Press francés', 'Brazos', 'Bajá la barra detrás de la cabeza, codos fijos.', $base . 'press-frances.gif'],
            ['Tríceps en polea', 'Brazos', 'Empujá la polea abajo, codos pegados al cuerpo.', $base . 'triceps-polea.gif'],
            ['Patada de tríceps', 'Brazos', 'Torso inclinado, extendé el codo hacia atrás.', $base . 'patada-triceps.gif'],
            ['Plancha', 'Core', 'Cuerpo en línea, sin hundir la lumbar.', $base . 'plancha.gif'],
            ['Crunch', 'Core', 'Encogé el abdomen, hombros apenas despegan.', $base . 'crunch.gif'],
            ['Crunch oblicuo', 'Core', 'Llevá el hombro hacia la rodilla opuesta.', $base . 'plancha-lateral.gif'],
            ['Elevación de piernas', 'Core', 'Colgado, subí las piernas sin balancear.', $base . 'elevacion-piernas.gif'],
            ['Russian twist', 'Core', 'Girá el torso de lado a lado con el abdomen firme.', $base . 'russian-twist.gif'],
            ['Mountain climbers', 'Core', 'En plancha, llevá las rodillas al pecho en ritmo.', $base . 'mountain-climbers.gif'],
            ['Burpees', 'Core', 'Sentadilla, plancha, flexión y salto.', $base . 'burpees.gif'],
        ];
    }

    public static function asegurarCatalogo($db): void {
        static $hecho = false;
        if ($hecho) {
            return;
        }
        $hecho = true;
        $existentes = $db->query('SELECT nombre FROM ejercicios')->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        $visto = [];
        foreach ($existentes as $nombre) {
            $visto[self::normalizar((string) $nombre)] = true;
        }
        $ent = $db->query('SELECT id_entrenador FROM entrenadores ORDER BY id_entrenador ASC LIMIT 1')->fetchColumn();
        $ent = $ent !== false ? (int) $ent : null;
        $st = $db->prepare(
            'INSERT INTO ejercicios (id_entrenador_creador, nombre, grupo_muscular, descripcion, imagen)
             VALUES (:ent, :nombre, :grupo, :desc, :img)'
        );
        foreach (self::catalogoBase() as $ej) {
            $clave = self::normalizar($ej[0]);
            if (isset($visto[$clave])) {
                continue;
            }
            $st->execute([
                'ent' => $ent,
                'nombre' => $ej[0],
                'grupo' => $ej[1],
                'desc' => $ej[2],
                'img' => $ej[3],
            ]);
            $visto[$clave] = true;
        }
    }

    public static function rutaGif(string $nombre, string $grupo = ''): string {
        $n = self::normalizar($nombre);
        $reglas = [
            ['sentadilla bulgara', 'sentadilla-bulgara.gif'],
            ['press inclinado con mancuerna', 'press-inclinado-mancuernas.gif'],
            ['press inclinado', 'press-inclinado.gif'],
            ['press declinado', 'press-declinado.gif'],
            ['press banca', 'press-banca.gif'],
            ['press de banca', 'press-banca.gif'],
            ['remo en polea', 'remo-polea.gif'],
            ['remo sentado', 'remo-sentado.gif'],
            ['remo en t', 'remo-t.gif'],
            ['remo con mancuerna', 'remo-mancuerna.gif'],
            ['curl predicador', 'curl-predicador.gif'],
            ['curl concentracion', 'curl-concentracion.gif'],
            ['curl martillo', 'curl-martillo.gif'],
            ['elevaciones laterales', 'elevaciones-laterales.gif'],
            ['elevaciones frontales', 'elevaciones.gif'],
            ['press militar', 'press-militar.gif'],
            ['press frances', 'press-frances.gif'],
            ['extension de cuadriceps', 'extension-cuadriceps.gif'],
            ['cuadriceps', 'extension-cuadriceps.gif'],
            ['curl femoral', 'extensiones.gif'],
            ['peck deck', 'peck-deck.gif'],
            ['jalon', 'jalon-pecho.gif'],
            ['triceps en polea', 'triceps-polea.gif'],
            ['patada de triceps', 'patada-triceps.gif'],
            ['peso muerto rumano', 'peso-muerto-rumano.gif'],
            ['peso muerto', 'peso-muerto.gif'],
            ['prensa', 'prensa-piernas.gif'],
            ['zancada', 'zancadas.gif'],
            ['hip thrust', 'hip-thrust.gif'],
            ['gemelo', 'gemelos.gif'],
            ['apertura', 'aperturas.gif'],
            ['face pull', 'face-pull.gif'],
            ['hiperextension', 'hiperextensiones.gif'],
            ['pajaro', 'pajaros.gif'],
            ['arnold', 'arnold-press.gif'],
            ['encogimiento', 'encogimientos.gif'],
            ['mountain', 'mountain-climbers.gif'],
            ['russian', 'russian-twist.gif'],
            ['oblicuo', 'plancha-lateral.gif'],
            ['elevacion de pierna', 'elevacion-piernas.gif'],
            ['sentadilla', 'sentadilla.gif'],
            ['remo', 'remo.gif'],
            ['plancha', 'plancha.gif'],
            ['fondos', 'fondos.gif'],
            ['dominada', 'dominadas.gif'],
            ['flexion', 'flexiones.gif'],
            ['laterales', 'elevaciones-laterales.gif'],
            ['militar', 'press-militar.gif'],
            ['frances', 'press-frances.gif'],
            ['martillo', 'curl-martillo.gif'],
            ['curl', 'curl.gif'],
            ['crunch', 'crunch.gif'],
            ['burpee', 'burpees.gif'],
            ['polea', 'triceps-polea.gif'],
        ];
        foreach ($reglas as [$clave, $archivo]) {
            if ($n !== '' && str_contains($n, $clave)) {
                return self::GIF_BASE . $archivo;
            }
        }
        $g = self::normalizar($grupo);
        $porGrupo = [
            'pierna' => 'sentadilla.gif',
            'pecho' => 'press-banca.gif',
            'espalda' => 'remo.gif',
            'core' => 'plancha.gif',
            'hombros' => 'press-militar.gif',
            'hombro' => 'press-militar.gif',
            'brazos' => 'curl-martillo.gif',
            'biceps' => 'curl-martillo.gif',
            'triceps' => 'triceps-polea.gif',
        ];
        return self::GIF_BASE . ($porGrupo[$g] ?? 'sentadilla.gif');
    }

    private static function normalizar(string $texto): string {
        $texto = mb_strtolower($texto, 'UTF-8');
        return str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $texto
        );
    }

    public function listar(): array {
        $stmt = $this->db->query(
            'SELECT id_ejercicio, id_entrenador_creador, nombre, grupo_muscular, descripcion, imagen
             FROM ejercicios
             ORDER BY nombre ASC'
        );
        return array_map([$this, 'conGif'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function porId(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT id_ejercicio, id_entrenador_creador, nombre, grupo_muscular, descripcion, imagen
             FROM ejercicios WHERE id_ejercicio = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ? $this->conGif($fila) : null;
    }

    public function crear(array $datos): int {
        $imagen = $datos['imagen'] ?? self::rutaGif(
            (string) ($datos['nombre'] ?? ''),
            (string) ($datos['grupo_muscular'] ?? '')
        );
        $stmt = $this->db->prepare(
            'INSERT INTO ejercicios (id_entrenador_creador, nombre, grupo_muscular, descripcion, imagen)
             VALUES (:ent, :nombre, :grupo, :desc, :img)'
        );
        $stmt->execute([
            'ent' => $datos['id_entrenador_creador'] ?? null,
            'nombre' => $datos['nombre'],
            'grupo' => $datos['grupo_muscular'] ?? null,
            'desc' => $datos['descripcion'] ?? null,
            'img' => $imagen,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): void {
        $imagen = $datos['imagen'] ?? self::rutaGif(
            (string) ($datos['nombre'] ?? ''),
            (string) ($datos['grupo_muscular'] ?? '')
        );
        $stmt = $this->db->prepare(
            'UPDATE ejercicios
             SET nombre = :nombre, grupo_muscular = :grupo, descripcion = :desc, imagen = :img
             WHERE id_ejercicio = :id'
        );
        $stmt->execute([
            'nombre' => $datos['nombre'],
            'grupo' => $datos['grupo_muscular'] ?? null,
            'desc' => $datos['descripcion'] ?? null,
            'img' => $imagen,
            'id' => $id,
        ]);
    }

    public function borrar(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM ejercicios WHERE id_ejercicio = :id');
        $stmt->execute(['id' => $id]);
    }

    private function conGif(array $fila): array {
        if (empty($fila['imagen'])) {
            $fila['imagen'] = self::rutaGif(
                (string) ($fila['nombre'] ?? ''),
                (string) ($fila['grupo_muscular'] ?? '')
            );
        }
        return $fila;
    }
}
