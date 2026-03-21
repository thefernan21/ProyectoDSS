<?php
class Grupo {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    public function obtenerTodos() {
        $sql = "SELECT g.*, m.nombre AS nombre_materia, d.nombre AS nombre_docente
                FROM grupos g
                JOIN materias m ON m.id_materia = g.id_materia
                JOIN docentes d ON d.id_docente = g.id_docente
                WHERE g.activo = 1
                ORDER BY g.periodo DESC, g.nombre_grupo ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorDocente($idDocente) {
        $sql = "SELECT g.*, m.nombre AS nombre_materia
                FROM grupos g
                JOIN materias m ON m.id_materia = g.id_materia
                WHERE g.id_docente = ? AND g.activo = 1
                ORDER BY g.nombre_grupo ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$idDocente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorAlumno($idAlumno) {
        $sql = "SELECT g.*, m.nombre AS nombre_materia
                FROM grupo_alumnos ga
                JOIN grupos g ON g.id_grupo = ga.id_grupo
                JOIN materias m ON m.id_materia = g.id_materia
                WHERE ga.id_alumno = ? AND g.activo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$idAlumno]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerAlumnosPorGrupo($idGrupo) {
        $sql = "SELECT a.id_alumno, a.numero_control, a.nombre
                FROM grupo_alumnos ga
                JOIN alumnos a ON a.id_alumno = ga.id_alumno
                WHERE ga.id_grupo = ?
                ORDER BY a.nombre ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$idGrupo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar($idMateria, $idDocente, $nombreGrupo, $periodo) {
        $sql = "INSERT INTO grupos (id_materia, id_docente, nombre_grupo, periodo) VALUES (?,?,?,?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$idMateria, (int)$idDocente, trim($nombreGrupo), trim($periodo)]);
        return $stmt->rowCount() > 0;
    }
}
?>
