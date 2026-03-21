<?php
class Asistencia {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    /**
     * Guarda o actualiza la asistencia de un alumno en un grupo para una fecha.
     * Usa INSERT ... ON DUPLICATE KEY UPDATE para manejar correcciones del docente.
     */
    public function guardarOActualizar($idAlumno, $idGrupo, $fecha, $tipo, $registradoPor) {
        $tiposValidos = ['asistencia', 'retardo', 'inasistencia'];
        if (!in_array($tipo, $tiposValidos)) return false;

        $sql = "INSERT INTO asistencias (id_alumno, id_grupo, fecha, tipo_asistencia, registrado_por)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    tipo_asistencia = VALUES(tipo_asistencia),
                    registrado_por  = VALUES(registrado_por)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$idAlumno, (int)$idGrupo, $fecha, $tipo, (int)$registradoPor]);
        return true;
    }

    /**
     * Retorna las asistencias de HOY para un grupo, indexadas por id_alumno.
     */
    public function obtenerPorGrupoFecha($idGrupo, $fecha) {
        $sql = "SELECT id_alumno, tipo_asistencia
                FROM asistencias WHERE id_grupo = ? AND fecha = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$idGrupo, $fecha]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map  = [];
        foreach ($rows as $r) $map[$r['id_alumno']] = $r['tipo_asistencia'];
        return $map;
    }

    /**
     * Historial completo de un alumno, opcionalmente filtrado por grupo.
     */
    public function obtenerHistorialAlumno($idAlumno, $idGrupo = null) {
        $sql = "SELECT a.fecha, a.tipo_asistencia, a.valor,
                       g.nombre_grupo, m.nombre AS materia
                FROM asistencias a
                JOIN grupos   g ON g.id_grupo   = a.id_grupo
                JOIN materias m ON m.id_materia = g.id_materia
                WHERE a.id_alumno = ?";
        $params = [(int)$idAlumno];
        if ($idGrupo) { $sql .= " AND a.id_grupo = ?"; $params[] = (int)$idGrupo; }
        $sql .= " ORDER BY a.fecha DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Porcentaje de asistencia por materia para un alumno (usa la vista de la BD).
     */
    public function obtenerPorcentajeAlumno($idAlumno) {
        $sql = "SELECT * FROM vista_porcentaje_asistencia WHERE id_alumno = ? ORDER BY materia ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$idAlumno]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Resumen rápido de conteos para el panel del alumno */
    public function conteoAlumno($idAlumno) {
        $sql = "SELECT
                    COUNT(*) AS total_clases,
                    SUM(tipo_asistencia = 'asistencia')   AS asistencias,
                    SUM(tipo_asistencia = 'retardo')      AS retardos,
                    SUM(tipo_asistencia = 'inasistencia') AS inasistencias
                FROM asistencias WHERE id_alumno = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$idAlumno]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Total de registros en el sistema (para dashboard admin) */
    public function conteoTotal() {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM asistencias")->fetchColumn();
    }

    /** Últimas fechas en las que un docente pasó lista */
    public function ultimasListasPorDocente($idUsuario, $limite = 5) {
        $sql = "SELECT DISTINCT a.fecha, g.nombre_grupo
                FROM asistencias a
                JOIN grupos g ON g.id_grupo = a.id_grupo
                WHERE a.registrado_por = ?
                ORDER BY a.fecha DESC, a.id_asistencia DESC
                LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$idUsuario, (int)$limite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
