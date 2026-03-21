<?php
require_once BASE_PATH . '/app/models/Usuario.php';

class AuthController {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    public function procesarLogin($datos) {
        $username = $datos['nombre_usuario'] ?? $datos['username'] ?? $datos['usuario'] ?? '';
        $password = $datos['password']       ?? $datos['contrasena'] ?? '';

        $sql  = "SELECT u.*, r.nombre_rol
                 FROM usuarios u
                 INNER JOIN roles r ON u.id_rol = r.id_rol
                 WHERE u.nombre_usuario = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([trim($username)]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            session_regenerate_id(true); // Previene session fixation

            $_SESSION['usuario_id'] = $usuario['id_usuario'];
            $_SESSION['username']   = $usuario['nombre_usuario'];
            $_SESSION['rol']        = $usuario['nombre_rol'];

            $destino = match($usuario['nombre_rol']) {
                'Admin'   => 'dashboard_admin',
                'Docente' => 'panel_docente',
                'Alumno'  => 'panel_alumno',
                default   => 'login_view'
            };

            header("Location: index.php?accion=$destino");
            exit();
        }

        // Login fallido: mostrar login con error
        $error = "Usuario o contraseña incorrectos.";
        require_once BASE_PATH . '/app/views/auth/login.php';
        exit();
    }

    public function cerrarSesion() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
        }
        session_destroy();
        header("Location: index.php?accion=login_view");
        exit();
    }
}
?>
