<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? 'Sistema de Asistencias' ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --sidebar-bg:    #0f1629;
      --sidebar-hover: #1e2d4a;
      --sidebar-active:#1d4ed8;
      --accent:        #3b82f6;
      --accent-light:  #eff6ff;
      --success:       #10b981;
      --warning:       #f59e0b;
      --danger:        #ef4444;
      --text-main:     #1e293b;
      --text-muted:    #64748b;
      --border:        #e2e8f0;
      --card-bg:       #ffffff;
      --page-bg:       #f1f5f9;
      --sidebar-w:     260px;
    }
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Outfit',sans-serif; background:var(--page-bg); color:var(--text-main); display:flex; min-height:100vh; }

    /* ── SIDEBAR ── */
    .sidebar { width:var(--sidebar-w); background:var(--sidebar-bg); display:flex; flex-direction:column; position:fixed; top:0; left:0; height:100vh; z-index:100; }
    .sidebar-brand { padding:28px 24px 20px; border-bottom:1px solid rgba(255,255,255,.07); }
    .sidebar-brand .logo-icon { width:40px; height:40px; background:var(--accent); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px; margin-bottom:12px; }
    .sidebar-brand h1 { color:#fff; font-size:15px; font-weight:600; line-height:1.3; }
    .sidebar-brand span { color:rgba(255,255,255,.4); font-size:11px; font-weight:400; text-transform:uppercase; letter-spacing:.08em; }
    .sidebar-nav { flex:1; padding:16px 12px; overflow-y:auto; }
    .nav-section-label { color:rgba(255,255,255,.25); font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.12em; padding:8px 12px 6px; margin-top:8px; }
    .nav-link { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:8px; color:rgba(255,255,255,.6); text-decoration:none; font-size:14px; font-weight:400; transition:all .15s; margin-bottom:2px; }
    .nav-link:hover { background:var(--sidebar-hover); color:#fff; }
    .nav-link.active { background:var(--sidebar-active); color:#fff; font-weight:500; }
    .nav-link .icon { font-size:16px; width:20px; text-align:center; }
    .sidebar-footer { padding:16px 12px; border-top:1px solid rgba(255,255,255,.07); }
    .user-chip { display:flex; align-items:center; gap:10px; padding:10px 12px; background:rgba(255,255,255,.05); border-radius:8px; margin-bottom:8px; }
    .user-avatar { width:32px; height:32px; background:var(--accent); border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:600; color:#fff; }
    .user-info { flex:1; min-width:0; }
    .user-info .name { color:#fff; font-size:13px; font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .user-info .role { color:rgba(255,255,255,.35); font-size:11px; }

    /* ── MAIN ── */
    .main { margin-left:var(--sidebar-w); flex:1; display:flex; flex-direction:column; min-height:100vh; }
    .topbar { background:#fff; border-bottom:1px solid var(--border); padding:0 32px; height:64px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:50; }
    .topbar h2 { font-size:18px; font-weight:600; }
    .topbar .breadcrumb { font-size:13px; color:var(--text-muted); }
    .page-content { padding:32px; flex:1; }

    /* ── CARDS ── */
    .card { background:var(--card-bg); border-radius:12px; border:1px solid var(--border); padding:24px; }
    .card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; }
    .card-title { font-size:15px; font-weight:600; }
    .card-subtitle { font-size:13px; color:var(--text-muted); margin-top:2px; }

    /* ── STATS ── */
    .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-bottom:24px; }
    .stat-card { background:var(--card-bg); border:1px solid var(--border); border-radius:12px; padding:20px; display:flex; align-items:flex-start; gap:14px; }
    .stat-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
    .stat-icon.blue{background:#eff6ff;} .stat-icon.green{background:#f0fdf4;} .stat-icon.amber{background:#fffbeb;} .stat-icon.purple{background:#faf5ff;}
    .stat-value { font-size:26px; font-weight:700; line-height:1; }
    .stat-label { font-size:12px; color:var(--text-muted); margin-top:4px; }

    /* ── TABLES ── */
    .table-wrap { overflow-x:auto; }
    table { width:100%; border-collapse:collapse; font-size:14px; }
    thead th { text-align:left; padding:10px 14px; background:#f8fafc; color:var(--text-muted); font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid var(--border); }
    tbody td { padding:12px 14px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#f8fafc; }

    /* ── BADGES ── */
    .badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:500; }
    .badge-green{background:#dcfce7;color:#166534;} .badge-amber{background:#fef9c3;color:#854d0e;}
    .badge-red{background:#fee2e2;color:#991b1b;}   .badge-blue{background:#dbeafe;color:#1e40af;}
    .badge-gray{background:#f1f5f9;color:#475569;}

    /* ── BUTTONS ── */
    .btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-family:'Outfit',sans-serif; font-size:13px; font-weight:500; cursor:pointer; border:none; text-decoration:none; transition:all .15s; }
    .btn-primary{background:var(--accent);color:#fff;} .btn-primary:hover{background:#2563eb;}
    .btn-success{background:var(--success);color:#fff;} .btn-success:hover{background:#059669;}
    .btn-danger{background:var(--danger);color:#fff;}  .btn-danger:hover{background:#dc2626;}
    .btn-outline{background:#fff;color:var(--text-main);border:1px solid var(--border);} .btn-outline:hover{background:#f8fafc;}
    .btn-sm{padding:5px 11px;font-size:12px;}

    /* ── FORMS ── */
    .form-group { margin-bottom:16px; }
    .form-label { display:block; font-size:13px; font-weight:500; margin-bottom:6px; }
    .form-control { width:100%; padding:9px 12px; border:1px solid var(--border); border-radius:8px; font-family:'Outfit',sans-serif; font-size:14px; color:var(--text-main); transition:border-color .15s; background:#fff; }
    .form-control:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(59,130,246,.1); }
    select.form-control { cursor:pointer; }

    /* ── ALERTS ── */
    .alert { padding:12px 16px; border-radius:8px; font-size:13px; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
    .alert-success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;}
    .alert-danger{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;}
    .alert-warning{background:#fef9c3;color:#854d0e;border:1px solid #fef08a;}
    .alert-info{background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe;}

    /* ── PROGRESS ── */
    .progress-wrap { background:#f1f5f9; border-radius:999px; height:8px; overflow:hidden; }
    .progress-bar  { height:100%; border-radius:999px; transition:width .4s; }

    /* ── UTILS ── */
    .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
    .grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;}
    .mt-4{margin-top:16px;} .mt-6{margin-top:24px;}
    .flex{display:flex;} .items-center{align-items:center;} .justify-between{justify-content:space-between;}

    @media(max-width:768px){
      .sidebar{transform:translateX(-100%);} .main{margin-left:0;}
      .grid-2,.grid-3{grid-template-columns:1fr;}
    }
  </style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="logo-icon">🎓</div>
    <h1>SistemaAsist.</h1>
    <span>Control de Asistencias</span>
  </div>

  <nav class="sidebar-nav">
    <?php $rol = $_SESSION['rol'] ?? ''; ?>

    <?php if ($rol === 'Admin'): ?>
      <div class="nav-section-label">Administración</div>
      <a href="index.php?accion=dashboard_admin"        class="nav-link <?= ($accionActual??'')==='dashboard_admin'       ?'active':'' ?>"><span class="icon">📊</span> Dashboard</a>
      <a href="index.php?accion=ver_alumnos"            class="nav-link <?= ($accionActual??'')==='ver_alumnos'            ?'active':'' ?>"><span class="icon">🎒</span> Alumnos</a>
      <a href="index.php?accion=ver_docentes"           class="nav-link <?= ($accionActual??'')==='ver_docentes'           ?'active':'' ?>"><span class="icon">👨‍🏫</span> Docentes</a>
      <a href="index.php?accion=ver_materias"           class="nav-link <?= ($accionActual??'')==='ver_materias'           ?'active':'' ?>"><span class="icon">📚</span> Materias</a>
      <a href="index.php?accion=ver_grupos"             class="nav-link <?= ($accionActual??'')==='ver_grupos'             ?'active':'' ?>"><span class="icon">🏫</span> Grupos</a>
      <a href="index.php?accion=calificaciones_admin"   class="nav-link <?= ($accionActual??'')==='calificaciones_admin'   ?'active':'' ?>"><span class="icon">📋</span> Calificaciones</a>

    <?php elseif ($rol === 'Docente'): ?>
      <div class="nav-section-label">Docente</div>
      <a href="index.php?accion=panel_docente"          class="nav-link <?= ($accionActual??'')==='panel_docente'          ?'active':'' ?>"><span class="icon">🏠</span> Mi Panel</a>
      <a href="index.php?accion=pasar_lista"            class="nav-link <?= ($accionActual??'')==='pasar_lista'            ?'active':'' ?>"><span class="icon">✅</span> Sesión de Clase</a>
      <a href="index.php?accion=historial_grupo"        class="nav-link <?= ($accionActual??'')==='historial_grupo'        ?'active':'' ?>"><span class="icon">📅</span> Historial Grupo</a>
      <a href="index.php?accion=unidades_docente"       class="nav-link <?= ($accionActual??'')==='unidades_docente'       ?'active':'' ?>"><span class="icon">📖</span> Unidades</a>
      <a href="index.php?accion=calificaciones_docente" class="nav-link <?= ($accionActual??'')==='calificaciones_docente' ?'active':'' ?>"><span class="icon">📊</span> Calificaciones</a>

    <?php elseif ($rol === 'Alumno'): ?>
      <div class="nav-section-label">Alumno</div>
      <a href="index.php?accion=panel_alumno"           class="nav-link <?= ($accionActual??'')==='panel_alumno'           ?'active':'' ?>"><span class="icon">🏠</span> Mi Panel</a>
      <a href="index.php?accion=mis_asistencias"        class="nav-link <?= ($accionActual??'')==='mis_asistencias'        ?'active':'' ?>"><span class="icon">📅</span> Mis Asistencias</a>
      <a href="index.php?accion=mis_calificaciones"     class="nav-link <?= ($accionActual??'')==='mis_calificaciones'     ?'active':'' ?>"><span class="icon">📊</span> Mis Calificaciones</a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-chip">
      <div class="user-avatar"><?= strtoupper(substr($_SESSION['username']??'U',0,1)) ?></div>
      <div class="user-info">
        <div class="name"><?= htmlspecialchars($_SESSION['username']??'',ENT_QUOTES,'UTF-8') ?></div>
        <div class="role"><?= htmlspecialchars($rol,ENT_QUOTES,'UTF-8') ?></div>
      </div>
    </div>
    <a href="index.php?accion=logout" class="nav-link" style="color:rgba(255,100,100,.7);">
      <span class="icon">🚪</span> Cerrar Sesión
    </a>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <div>
      <h2><?= $pageTitle ?? 'Panel' ?></h2>
      <div class="breadcrumb">Sistema de Asistencias › <?= $pageTitle ?? '' ?></div>
    </div>
  </div>
  <div class="page-content">
