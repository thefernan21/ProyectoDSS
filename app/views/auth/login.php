<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso — Sistema de Asistencias</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
      font-family: 'Outfit', sans-serif;
      min-height: 100vh;
      display: flex;
      background: #0f1629;
      overflow: hidden;
    }

    /* LEFT PANEL */
    .left-panel {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 60px;
      position: relative;
      overflow: hidden;
    }
    .left-panel::before {
      content: '';
      position: absolute;
      width: 500px; height: 500px;
      background: radial-gradient(circle, rgba(59,130,246,.15) 0%, transparent 70%);
      top: -100px; left: -100px;
      pointer-events: none;
    }
    .left-panel::after {
      content: '';
      position: absolute;
      width: 400px; height: 400px;
      background: radial-gradient(circle, rgba(16,185,129,.08) 0%, transparent 70%);
      bottom: -80px; right: -80px;
      pointer-events: none;
    }
    .brand {
      display: flex; align-items: center; gap: 14px;
      margin-bottom: 60px;
    }
    .brand-icon {
      width: 48px; height: 48px;
      background: #3b82f6;
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 24px;
    }
    .brand h1 { color: #fff; font-size: 20px; font-weight: 600; }
    .brand span { color: rgba(255,255,255,.4); font-size: 12px; display: block; }

    .hero-text h2 {
      color: #fff;
      font-size: 42px;
      font-weight: 700;
      line-height: 1.15;
      margin-bottom: 16px;
    }
    .hero-text h2 em {
      font-style: normal;
      background: linear-gradient(135deg, #3b82f6, #10b981);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .hero-text p {
      color: rgba(255,255,255,.45);
      font-size: 15px;
      line-height: 1.7;
      max-width: 380px;
    }

    .features {
      margin-top: 48px;
      display: flex; flex-direction: column; gap: 14px;
    }
    .feature-item {
      display: flex; align-items: center; gap: 12px;
    }
    .feature-dot {
      width: 8px; height: 8px;
      background: #3b82f6;
      border-radius: 50%;
      flex-shrink: 0;
    }
    .feature-item span { color: rgba(255,255,255,.5); font-size: 13px; }

    /* RIGHT PANEL */
    .right-panel {
      width: 460px;
      background: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 48px;
      position: relative;
    }
    .login-box { width: 100%; max-width: 360px; }
    .login-box h3 {
      font-size: 26px;
      font-weight: 700;
      color: #0f172a;
      margin-bottom: 6px;
    }
    .login-box .subtitle {
      color: #64748b;
      font-size: 14px;
      margin-bottom: 36px;
    }

    .form-group { margin-bottom: 18px; }
    .form-label {
      display: block;
      font-size: 13px;
      font-weight: 500;
      color: #374151;
      margin-bottom: 7px;
    }
    .input-wrap { position: relative; }
    .input-icon {
      position: absolute;
      left: 13px; top: 50%;
      transform: translateY(-50%);
      font-size: 16px;
      pointer-events: none;
    }
    .form-input {
      width: 100%;
      padding: 11px 13px 11px 40px;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      font-family: 'Outfit', sans-serif;
      font-size: 14px;
      color: #0f172a;
      transition: border-color .15s, box-shadow .15s;
      background: #f8fafc;
    }
    .form-input:focus {
      outline: none;
      border-color: #3b82f6;
      background: #fff;
      box-shadow: 0 0 0 3px rgba(59,130,246,.1);
    }
    .form-input::placeholder { color: #94a3b8; }

    .btn-login {
      width: 100%;
      padding: 12px;
      background: #0f1629;
      color: #fff;
      border: none;
      border-radius: 10px;
      font-family: 'Outfit', sans-serif;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: background .15s, transform .1s;
      margin-top: 8px;
      letter-spacing: .01em;
    }
    .btn-login:hover { background: #1e2d4a; }
    .btn-login:active { transform: scale(.99); }

    .error-msg {
      background: #fee2e2;
      color: #991b1b;
      border: 1px solid #fecaca;
      padding: 10px 14px;
      border-radius: 8px;
      font-size: 13px;
      margin-bottom: 16px;
      display: flex; align-items: center; gap: 8px;
    }

    .login-footer {
      margin-top: 32px;
      padding-top: 24px;
      border-top: 1px solid #f1f5f9;
      display: flex; gap: 8px;
    }
    .role-chip {
      flex: 1;
      padding: 8px;
      border-radius: 8px;
      text-align: center;
      font-size: 11px;
      font-weight: 500;
    }
    .role-chip.admin   { background: #eff6ff; color: #1d4ed8; }
    .role-chip.docente { background: #f0fdf4; color: #166534; }
    .role-chip.alumno  { background: #faf5ff; color: #6b21a8; }
    .role-chip .role-icon { font-size: 18px; display: block; margin-bottom: 4px; }

    @media(max-width: 768px) {
      .left-panel { display: none; }
      .right-panel { width: 100%; }
    }
  </style>
</head>
<body>

<div class="left-panel">
  <div class="brand">
    <div class="brand-icon">🎓</div>
    <div>
      <h1>SistemaAsist.</h1>
      <span>Control de Asistencias</span>
    </div>
  </div>
  <div class="hero-text">
    <h2>Control de asistencias<br><em>inteligente y preciso</em></h2>
    <p>Gestiona asistencias, retardos e inasistencias con ponderación automática. Diseñado para docentes e instituciones educativas.</p>
  </div>
  <div class="features">
    <div class="feature-item">
      <div class="feature-dot"></div>
      <span>Registro de asistencia con valor ponderado automático</span>
    </div>
    <div class="feature-item">
      <div class="feature-dot"></div>
      <span>Importación masiva de alumnos desde archivos CSV del SII</span>
    </div>
    <div class="feature-item">
      <div class="feature-dot"></div>
      <span>Historial y porcentaje de asistencia en tiempo real</span>
    </div>
    <div class="feature-item">
      <div class="feature-dot"></div>
      <span>Control de acceso por roles: Admin, Docente y Alumno</span>
    </div>
  </div>
</div>

<div class="right-panel">
  <div class="login-box">
    <h3>Bienvenido</h3>
    <p class="subtitle">Ingresa tus credenciales para continuar</p>

    <?php if (!empty($error)): ?>
      <div class="error-msg">⚠️ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form action="index.php?accion=login" method="POST">
      <div class="form-group">
        <label class="form-label">Usuario</label>
        <div class="input-wrap">
          <span class="input-icon">👤</span>
          <input type="text" name="nombre_usuario" class="form-input" placeholder="Ej: admin_valdez" required autocomplete="username">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Contraseña</label>
        <div class="input-wrap">
          <span class="input-icon">🔒</span>
          <input type="password" name="password" class="form-input" placeholder="••••••••" required autocomplete="current-password">
        </div>
      </div>
      <button type="submit" class="btn-login">Iniciar Sesión →</button>
    </form>

    <div class="login-footer">
      <div class="role-chip admin">
        <span class="role-icon">🛡️</span>Admin
      </div>
      <div class="role-chip docente">
        <span class="role-icon">👨‍🏫</span>Docente
      </div>
      <div class="role-chip alumno">
        <span class="role-icon">🎒</span>Alumno
      </div>
    </div>
  </div>
</div>

</body>
</html>
