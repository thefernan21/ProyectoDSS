<?php
class Alumno {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    /**
     * Inserta un alumno individual y devuelve su id_alumno.
     * Si ya existe por numero_control, devuelve el id existente.
     */
    public function insertarUno($noControl, $nombre, $correo) {
        // ¿Ya existe?
        $stmt = $this->pdo->prepare("SELECT id_alumno FROM alumnos WHERE numero_control = ? LIMIT 1");
        $stmt->execute([$noControl]);
        $existente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existente) return false; // Ya existe, no contar como nuevo

        $sql  = "INSERT INTO alumnos (numero_control, nombre, correo) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$noControl, $nombre, $correo]);
        return (int)$this->pdo->lastInsertId();
    }

    public function obtenerTodos() {
        $sql  = "SELECT a.id_alumno, a.numero_control, a.nombre, a.correo, a.id_usuario
                 FROM alumnos a ORDER BY a.nombre ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
