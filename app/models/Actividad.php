<?php
class Actividad {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    // ── ACTIVIDADES ─────────────────────────────────────────

    /** Crea o actualiza la actividad del día y sus calificaciones */
    public function guardarActividad($idGrupo, $idUnidad, $fecha, $nombre, array $califs, $registradoPor) {
        if (empty(trim($nombre))) return false;

        // Upsert actividad
        $stmt = $this->pdo->prepare(
            "INSERT INTO actividades_clase (id_grupo, id_unidad, fecha, nombre, registrado_por)
             VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), registrado_por = VALUES(registrado_por)"
        );
        $stmt->execute([$idGrupo, $idUnidad, $fecha, trim($nombre), $registradoPor]);
        // Obtener id (nuevo o existente)
        $stmtId = $this->pdo->prepare(
            "SELECT id_actividad FROM actividades_clase WHERE id_grupo=? AND fecha=? AND nombre=? LIMIT 1"
        );
        $stmtId->execute([$idGrupo, $fecha, trim($nombre)]);
        $idAct = (int)$stmtId->fetchColumn();
        if (!$idAct) return false;

        // Calificaciones individuales
        $stmtC = $this->pdo->prepare(
            "INSERT INTO calif_actividades (id_actividad, id_alumno, calificacion)
             VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE calificacion = VALUES(calificacion)"
        );
        foreach ($califs as $idAlumno => $calif) {
            $val = ($calif !== '' && $calif !== null) ? min(10, max(0, (float)$calif)) : null;
            $stmtC->execute([$idAct, (int)$idAlumno, $val]);
        }
        return true;
    }

    /** Obtiene la actividad registrada para un grupo en una fecha */
    public function obtenerActividadDia($idGrupo, $fecha) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM actividades_clase WHERE id_grupo=? AND fecha=? LIMIT 1"
        );
        $stmt->execute([$idGrupo, $fecha]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Calificaciones de actividad del día, indexadas por id_alumno */
    public function califActividadDia($idGrupo, $fecha) {
        $stmt = $this->pdo->prepare(
            "SELECT ca.id_alumno, ca.calificacion
             FROM calif_actividades ca
             JOIN actividades_clase ac ON ac.id_actividad = ca.id_actividad
             WHERE ac.id_grupo=? AND ac.fecha=?"
        );
        $stmt->execute([$idGrupo, $fecha]);
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $map[$r['id_alumno']] = $r['calificacion'];
        return $map;
    }

    // ── TAREAS ───────────────────────────────────────────────

    public function guardarTarea($idGrupo, $idUnidad, $fecha, $nombre, array $califs, $registradoPor) {
        if (empty(trim($nombre))) return false;

        $stmt = $this->pdo->prepare(
            "INSERT INTO tareas_clase (id_grupo, id_unidad, fecha, nombre, registrado_por)
             VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), registrado_por = VALUES(registrado_por)"
        );
        $stmt->execute([$idGrupo, $idUnidad, $fecha, trim($nombre), $registradoPor]);

        $stmtId = $this->pdo->prepare(
            "SELECT id_tarea FROM tareas_clase WHERE id_grupo=? AND fecha=? AND nombre=? LIMIT 1"
        );
        $stmtId->execute([$idGrupo, $fecha, trim($nombre)]);
        $idTarea = (int)$stmtId->fetchColumn();
        if (!$idTarea) return false;

        $stmtC = $this->pdo->prepare(
            "INSERT INTO calif_tareas (id_tarea, id_alumno, calificacion)
             VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE calificacion = VALUES(calificacion)"
        );
        foreach ($califs as $idAlumno => $calif) {
            $val = ($calif !== '' && $calif !== null) ? min(10, max(0, (float)$calif)) : null;
            $stmtC->execute([$idTarea, (int)$idAlumno, $val]);
        }
        return true;
    }

    public function obtenerTareaDia($idGrupo, $fecha) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM tareas_clase WHERE id_grupo=? AND fecha=? LIMIT 1"
        );
        $stmt->execute([$idGrupo, $fecha]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function califTareaDia($idGrupo, $fecha) {
        $stmt = $this->pdo->prepare(
            "SELECT ct.id_alumno, ct.calificacion
             FROM calif_tareas ct
             JOIN tareas_clase tc ON tc.id_tarea = ct.id_tarea
             WHERE tc.id_grupo=? AND tc.fecha=?"
        );
        $stmt->execute([$idGrupo, $fecha]);
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $map[$r['id_alumno']] = $r['calificacion'];
        return $map;
    }

    // ── RESUMEN POR UNIDAD ───────────────────────────────────

    /** Calificaciones completas de una unidad para todos los alumnos del grupo */
    public function resumenUnidad($idGrupo, $idUnidad) {
        $sql = "SELECT
            al.id_alumno, al.numero_control, al.nombre AS nombre_alumno,
            -- Asistencia (valor ponderado promedio * 10 = escala 0-10)
            COALESCE(ROUND(
                SUM(CASE WHEN a.id_grupo = ? THEN a.valor ELSE 0 END)
                / NULLIF(COUNT(DISTINCT CASE WHEN a.id_grupo = ? THEN a.fecha ELSE NULL END), 0)
            * 10, 2), 0) AS calif_asistencia,
            -- Promedio actividades
            COALESCE((
                SELECT ROUND(AVG(ca.calificacion),2)
                FROM calif_actividades ca
                JOIN actividades_clase ac ON ac.id_actividad = ca.id_actividad
                WHERE ca.id_alumno = al.id_alumno AND ac.id_unidad = ?
            ), NULL) AS promedio_actividades,
            -- Promedio tareas
            COALESCE((
                SELECT ROUND(AVG(ct.calificacion),2)
                FROM calif_tareas ct
                JOIN tareas_clase tc ON tc.id_tarea = ct.id_tarea
                WHERE ct.id_alumno = al.id_alumno AND tc.id_unidad = ?
            ), NULL) AS promedio_tareas
        FROM grupo_alumnos ga
        JOIN alumnos al ON al.id_alumno = ga.id_alumno
        LEFT JOIN asistencias a ON a.id_alumno = al.id_alumno AND a.id_grupo = ?
        WHERE ga.id_grupo = ?
        GROUP BY al.id_alumno
        ORDER BY al.nombre ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$idGrupo, $idGrupo, $idUnidad, $idUnidad, $idGrupo, $idGrupo]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Calcular calificación final: 20% asist + 40% act + 40% tar
        foreach ($rows as &$r) {
            $a = (float)$r['calif_asistencia'];
            $act = $r['promedio_actividades'] !== null ? (float)$r['promedio_actividades'] : null;
            $tar = $r['promedio_tareas']      !== null ? (float)$r['promedio_tareas']      : null;
            if ($act !== null && $tar !== null) {
                $r['calificacion_unidad'] = round($a * 0.20 + $act * 0.40 + $tar * 0.40, 2);
            } elseif ($act !== null) {
                $r['calificacion_unidad'] = round($a * 0.20 + $act * 0.80, 2);
            } elseif ($tar !== null) {
                $r['calificacion_unidad'] = round($a * 0.20 + $tar * 0.80, 2);
            } else {
                $r['calificacion_unidad'] = round($a, 2);
            }
        }
        return $rows;
    }

    /** Fechas en las que el docente registró algo en un grupo */
    public function obtenerFechasSesiones($idGrupo) {
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT fecha FROM asistencias WHERE id_grupo = ?
             ORDER BY fecha DESC"
        );
        $stmt->execute([$idGrupo]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>
