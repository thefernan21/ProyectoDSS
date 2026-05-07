<?php
class Horario {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    /** Guarda los horarios de un grupo (borra los anteriores primero) */
    public function guardarParaGrupo(int $idGrupo, array $horarios): void {
        $this->pdo->prepare("DELETE FROM horario_grupos WHERE id_grupo = ?")->execute([$idGrupo]);
        $stmt = $this->pdo->prepare(
            "INSERT INTO horario_grupos (id_grupo, dia, hora_inicio, hora_fin, aula)
             VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($horarios as $h) {
            if (empty($h['dia']) || empty($h['hora_inicio']) || empty($h['hora_fin'])) continue;
            $stmt->execute([
                $idGrupo,
                strtolower($h['dia']),
                $h['hora_inicio'],
                $h['hora_fin'],
                $h['aula'] ?? null
            ]);
        }
    }

    /** Obtiene todos los horarios de un grupo */
    public function obtenerPorGrupo(int $idGrupo): array {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM horario_grupos WHERE id_grupo = ?
             ORDER BY FIELD(dia,'lunes','martes','miercoles','jueves','viernes','sabado'), hora_inicio"
        );
        $stmt->execute([$idGrupo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Obtiene horario completo de un docente (todos sus grupos activos) */
    public function obtenerPorDocente(int $idDocente): array {
        $stmt = $this->pdo->prepare(
            "SELECT h.*, g.nombre_grupo, g.periodo, m.nombre AS nombre_materia, m.clave_materia
             FROM horario_grupos h
             JOIN grupos   g ON g.id_grupo   = h.id_grupo
             JOIN materias m ON m.id_materia = g.id_materia
             WHERE g.id_docente = ? AND g.activo = 1
             ORDER BY FIELD(h.dia,'lunes','martes','miercoles','jueves','viernes','sabado'), h.hora_inicio"
        );
        $stmt->execute([$idDocente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
