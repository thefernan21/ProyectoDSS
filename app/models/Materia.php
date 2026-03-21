<?php
class Materia {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    public function obtenerTodas() {
        $stmt = $this->pdo->query("SELECT * FROM materias ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar($clave, $nombre, $creditos) {
        $sql = "INSERT INTO materias (clave_materia, nombre, creditos) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([trim($clave), trim($nombre), (int)$creditos]);
        return $stmt->rowCount() > 0;
    }
}
?>
