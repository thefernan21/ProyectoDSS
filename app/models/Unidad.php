<?php
class Unidad {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    /** Crea N unidades vacías al crear un grupo */
    public function crearParaGrupo($idGrupo, $numUnidades) {
        $stmt = $this->pdo->prepare(
            "INSERT IGNORE INTO unidades (id_grupo, numero_unidad, nombre) VALUES (?, ?, ?)"
        );
        for ($i = 1; $i <= $numUnidades; $i++) {
            $stmt->execute([$idGrupo, $i, "Unidad $i"]);
        }
    }

    public function obtenerPorGrupo($idGrupo) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM unidades WHERE id_grupo = ? ORDER BY numero_unidad ASC"
        );
        $stmt->execute([$idGrupo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($idUnidad) {
        $stmt = $this->pdo->prepare("SELECT * FROM unidades WHERE id_unidad = ? LIMIT 1");
        $stmt->execute([$idUnidad]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarNombreYFecha($idUnidad, $nombre, $fechaFin) {
        $stmt = $this->pdo->prepare(
            "UPDATE unidades SET nombre = ?, fecha_fin = ? WHERE id_unidad = ?"
        );
        $stmt->execute([
            trim($nombre),
            ($fechaFin ?: null),
            (int)$idUnidad
        ]);
    }

    public function cerrar($idUnidad) {
        $stmt = $this->pdo->prepare(
            "UPDATE unidades SET cerrada = 1, fecha_cierre = NOW() WHERE id_unidad = ?"
        );
        $stmt->execute([(int)$idUnidad]);
    }

    public function reabrir($idUnidad) {
        $stmt = $this->pdo->prepare(
            "UPDATE unidades SET cerrada = 0, fecha_cierre = NULL WHERE id_unidad = ?"
        );
        $stmt->execute([(int)$idUnidad]);
    }

    /** Verifica si todas las unidades de un grupo están cerradas */
    public function todasCerradas($idGrupo) {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM unidades WHERE id_grupo = ? AND cerrada = 0"
        );
        $stmt->execute([$idGrupo]);
        return (int)$stmt->fetchColumn() === 0;
    }
}
?>
