<?php
class Rubrica {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    public function obtenerPorGrupo(int $idGrupo): array {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM rubrica_criterios WHERE id_grupo=? ORDER BY orden ASC, id_criterio ASC"
        );
        $stmt->execute([$idGrupo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Reemplaza todos los criterios del grupo */
    public function guardar(int $idGrupo, array $criterios): array {
        // Validar que los pesos sumen 100
        $sumaPesos = array_sum(array_column($criterios, 'peso'));
        if (abs($sumaPesos - 100) > 0.01) {
            return ['ok' => false, 'msg' => "❌ Los pesos deben sumar 100%. Actualmente suman {$sumaPesos}%."];
        }

        $this->pdo->prepare("DELETE FROM rubrica_criterios WHERE id_grupo=?")->execute([$idGrupo]);
        $stmt = $this->pdo->prepare(
            "INSERT INTO rubrica_criterios (id_grupo, nombre, descripcion, peso, orden)
             VALUES (?,?,?,?,?)"
        );
        foreach ($criterios as $orden => $c) {
            $nombre = trim($c['nombre'] ?? '');
            $peso   = (float)($c['peso'] ?? 0);
            if (!$nombre || $peso <= 0) continue;
            $stmt->execute([$idGrupo, $nombre, trim($c['descripcion'] ?? ''), $peso, $orden + 1]);
        }
        return ['ok' => true, 'msg' => '✅ Rúbrica guardada correctamente.'];
    }

    /** Calcula la calificación final de un alumno usando la rúbrica del grupo */
    public function calcularCalificacion(int $idGrupo, int $idAlumno, int $idUnidad): float {
        $criterios = $this->obtenerPorGrupo($idGrupo);
        if (empty($criterios)) return 0.0;

        $total = 0.0;
        $pesoUsado = 0.0;

        foreach ($criterios as $c) {
            $nombre = strtolower($c['nombre']);
            $peso   = (float)$c['peso'] / 100;
            $nota   = null;

            // Detectar qué tipo de criterio es por su nombre
            if (str_contains($nombre, 'asist')) {
                // Asistencia: valor promedio * 10
                $stmt = $this->pdo->prepare(
                    "SELECT ROUND(COALESCE(AVG(a.valor),0)*10,2)
                     FROM asistencias a WHERE a.id_alumno=? AND a.id_grupo=?"
                );
                $stmt->execute([$idAlumno, $idGrupo]);
                $nota = (float)$stmt->fetchColumn();

            } elseif (str_contains($nombre, 'actividad') || str_contains($nombre, 'clase')) {
                $stmt = $this->pdo->prepare(
                    "SELECT ROUND(COALESCE(AVG(ca.calificacion),0),2)
                     FROM calif_actividades ca
                     JOIN actividades_clase ac ON ac.id_actividad = ca.id_actividad
                     WHERE ca.id_alumno=? AND ac.id_unidad=?"
                );
                $stmt->execute([$idAlumno, $idUnidad]);
                $nota = (float)$stmt->fetchColumn();

            } elseif (str_contains($nombre, 'tarea') || str_contains($nombre, 'homework')) {
                $stmt = $this->pdo->prepare(
                    "SELECT ROUND(COALESCE(AVG(ct.calificacion),0),2)
                     FROM calif_tareas ct
                     JOIN tareas_clase tc ON tc.id_tarea = ct.id_tarea
                     WHERE ct.id_alumno=? AND tc.id_unidad=?"
                );
                $stmt->execute([$idAlumno, $idUnidad]);
                $nota = (float)$stmt->fetchColumn();
            }

            if ($nota !== null) {
                $total      += $nota * $peso;
                $pesoUsado  += $peso;
            }
        }
        return $pesoUsado > 0 ? round($total / $pesoUsado * $pesoUsado + ($total - $total), 2) : round($total, 2);
    }

    /** Criterios predefinidos para iniciar rápido */
    public static function plantillaDefault(): array {
        return [
            ['nombre' => 'Asistencia',          'descripcion' => 'Porcentaje de asistencia ponderado', 'peso' => 20],
            ['nombre' => 'Actividades en clase', 'descripcion' => 'Promedio de actividades realizadas', 'peso' => 40],
            ['nombre' => 'Tareas',               'descripcion' => 'Promedio de tareas entregadas',      'peso' => 40],
        ];
    }
}
?>
