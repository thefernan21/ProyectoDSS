<?php
class Calendario {
    private $pdo;

    // ── Días festivos oficiales de México ───────────────────
    // Incluye los fijos y los de "lunes más cercano" (Ley Lunes)
    public static function getFestivos(int $year): array {
        $festivos = [];

        // Fijos
        $fijos = [
            "$year-01-01" => "Año Nuevo",
            "$year-05-01" => "Día del Trabajo",
            "$year-09-16" => "Independencia de México",
            "$year-12-25" => "Navidad",
        ];
        foreach ($fijos as $fecha => $nombre) {
            $festivos[$fecha] = $nombre;
        }

        // Ley Lunes: Constitución = primer lunes de febrero
        $festivos[self::primerLunes($year, 2)] = "Día de la Constitución";

        // Ley Lunes: Benito Juárez = tercer lunes de marzo
        $festivos[self::tercerLunes($year, 3)] = "Natalicio de Benito Juárez";

        // Ley Lunes: Revolución = tercer lunes de noviembre
        $festivos[self::tercerLunes($year, 11)] = "Revolución Mexicana";

        // Semana Santa (Jueves y Viernes Santos — variables)
        $pascua   = self::calcularPascua($year);
        $jueves   = date('Y-m-d', strtotime("$pascua -3 days"));
        $viernes  = date('Y-m-d', strtotime("$pascua -2 days"));
        $festivos[$jueves]  = "Jueves Santo";
        $festivos[$viernes] = "Viernes Santo";

        ksort($festivos);
        return $festivos;
    }

    // Primer lunes de un mes dado
    private static function primerLunes(int $year, int $month): string {
        $d = new DateTime("$year-$month-01");
        $dow = (int)$d->format('N'); // 1=lunes, 7=domingo
        $diff = ($dow === 1) ? 0 : (8 - $dow);
        $d->modify("+{$diff} days");
        return $d->format('Y-m-d');
    }

    // Tercer lunes de un mes dado
    private static function tercerLunes(int $year, int $month): string {
        $first = self::primerLunes($year, $month);
        return date('Y-m-d', strtotime("$first +14 days"));
    }

    // Algoritmo de Meeus/Jones/Butcher para calcular Pascua
    private static function calcularPascua(int $year): string {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day   = (($h + $l - 7 * $m + 114) % 31) + 1;
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    public function __construct($pdo) { $this->pdo = $pdo; }

    /**
     * Genera todas las fechas de clase de un grupo en un rango,
     * según su horario semanal, excluyendo festivos.
     * Devuelve array de ['fecha'=>'Y-m-d', 'dia'=>'lunes', 'festivo'=>false/nombre]
     */
    public function generarFechasClase(int $idGrupo, string $fechaInicio, string $fechaFin): array {
        // Días que tiene clase este grupo
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT dia FROM horario_grupos WHERE id_grupo = ?"
        );
        $stmt->execute([$idGrupo]);
        $diasClase = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($diasClase)) return [];

        $mapDia = [
            'lunes'=>1,'martes'=>2,'miercoles'=>3,
            'jueves'=>4,'viernes'=>5,'sabado'=>6
        ];

        $numerosClase = array_map(fn($d) => $mapDia[$d] ?? 0, $diasClase);
        $inicio  = new DateTime($fechaInicio);
        $fin     = new DateTime($fechaFin);
        $fechas  = [];

        // Cargar festivos de los años del rango
        $festivos = [];
        for ($y = (int)$inicio->format('Y'); $y <= (int)$fin->format('Y'); $y++) {
            $festivos = array_merge($festivos, self::getFestivos($y));
        }

        $current = clone $inicio;
        while ($current <= $fin) {
            $dow  = (int)$current->format('N'); // 1=lunes … 6=sab
            $str  = $current->format('Y-m-d');
            if (in_array($dow, $numerosClase)) {
                $festivo = $festivos[$str] ?? false;
                $fechas[] = [
                    'fecha'   => $str,
                    'dia'     => array_search($dow, $mapDia),
                    'festivo' => $festivo,
                    'dow'     => $dow,
                ];
            }
            $current->modify('+1 day');
        }
        return $fechas;
    }

    /**
     * Asistencias de todos los alumnos del grupo en un rango,
     * devuelve [ id_alumno => [ 'Y-m-d' => tipo ] ]
     */
    public function obtenerAsistenciasRango(int $idGrupo, string $desde, string $hasta): array {
        $stmt = $this->pdo->prepare(
            "SELECT id_alumno, fecha, tipo_asistencia
             FROM asistencias
             WHERE id_grupo=? AND fecha BETWEEN ? AND ?"
        );
        $stmt->execute([$idGrupo, $desde, $hasta]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $mapa = [];
        foreach ($rows as $r) {
            $mapa[$r['id_alumno']][$r['fecha']] = $r['tipo_asistencia'];
        }
        return $mapa;
    }

    /** Alumnos del grupo ordenados por nombre */
    public function obtenerAlumnos(int $idGrupo): array {
        $stmt = $this->pdo->prepare(
            "SELECT a.id_alumno, a.nombre, a.numero_control
             FROM grupo_alumnos ga
             JOIN alumnos a ON a.id_alumno = ga.id_alumno
             WHERE ga.id_grupo = ?
             ORDER BY a.nombre ASC"
        );
        $stmt->execute([$idGrupo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Unidades del grupo con sus fechas de cierre */
    public function obtenerUnidades(int $idGrupo): array {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM unidades WHERE id_grupo=? ORDER BY numero_unidad ASC"
        );
        $stmt->execute([$idGrupo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
