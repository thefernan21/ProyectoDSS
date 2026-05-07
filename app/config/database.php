<?php
// ─────────────────────────────────────────────────────
//  CONFIGURACIÓN DE BASE DE DATOS
//  Ajusta $host, $db, $user y $pass a tu entorno local
// ─────────────────────────────────────────────────────
$host    = '127.0.0.1';
$db      = 'asistencias';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    die("
    <div style='font-family:sans-serif;text-align:center;margin-top:80px;'>
      <h2 style='color:#ef4444;'>⚠️ Error de conexión</h2>
      <p style='color:#64748b;'>No se pudo conectar a la base de datos.<br>
      Verifica tu configuración en <code>app/config/database.php</code></p>
      <p style='color:#334155;margin-top:10px;'><strong>Detalle técnico:</strong> " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>
    </div>");
}
?>
