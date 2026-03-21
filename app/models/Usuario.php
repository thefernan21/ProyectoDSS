<?php
class Usuario {
    private $pdo;

    // IDs de rol fijos (coinciden con los datos en la tabla `roles`)
    const ROL_ADMIN   = 1;
    const ROL_DOCENTE = 2;
    const ROL_ALUMNO  = 3;

    public function __construct($pdo) { $this->pdo = $pdo; }

    public function buscarPorUsuario($nombre_usuario) {
        $sql  = "SELECT u.id_usuario, u.nombre_usuario, u.password_hash, u.id_rol, r.nombre_rol
                 FROM usuarios u
                 INNER JOIN roles r ON u.id_rol = r.id_rol
                 WHERE u.nombre_usuario = :usuario LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':usuario', $nombre_usuario, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crea una cuenta en `usuarios` y devuelve el id_usuario generado.
     * Si el nombre de usuario ya existe, retorna el id existente (sin duplicar).
     */
    public function crearCuenta($nombreUsuario, $passwordPlano, $idRol) {
        $stmt = $this->pdo->prepare("SELECT id_usuario FROM usuarios WHERE nombre_usuario = ? LIMIT 1");
        $stmt->execute([trim($nombreUsuario)]);
        $existente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existente) return (int)$existente['id_usuario'];

        $hash = password_hash($passwordPlano, PASSWORD_BCRYPT);
        $sql  = "INSERT INTO usuarios (nombre_usuario, password_hash, id_rol) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([trim($nombreUsuario), $hash, (int)$idRol]);
        return (int)$this->pdo->lastInsertId();
    }

    public function vincularAlumno($idUsuario, $idAlumno) {
        $stmt = $this->pdo->prepare("UPDATE alumnos SET id_usuario = ? WHERE id_alumno = ?");
        $stmt->execute([(int)$idUsuario, (int)$idAlumno]);
    }

    public function vincularDocente($idUsuario, $idDocente) {
        $stmt = $this->pdo->prepare("UPDATE docentes SET id_usuario = ? WHERE id_docente = ?");
        $stmt->execute([(int)$idUsuario, (int)$idDocente]);
    }

    public function obtenerTodos() {
        $sql = "SELECT u.id_usuario, u.nombre_usuario, r.nombre_rol
                FROM usuarios u
                JOIN roles r ON r.id_rol = u.id_rol
                ORDER BY r.nombre_rol, u.nombre_usuario";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
