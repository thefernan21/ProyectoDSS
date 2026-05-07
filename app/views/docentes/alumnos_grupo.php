<?php
$pageTitle    = 'Alumnos — ' . htmlspecialchars($grupoInfo['nombre_grupo'] ?? '', ENT_QUOTES,'UTF-8');
$accionActual = 'mis_grupos_docente';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<!-- Volver -->
<div style="margin-bottom:20px;">
  <a href="index.php?accion=mis_grupos_docente"
     style="color:#64748b; font-size:13px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
    ← Mis grupos
  </a>
</div>

<!-- Info del grupo -->
<div style="background:linear-gradient(135deg,#1e3a5f,#1d4ed8); border-radius:12px;
            padding:18px 24px; margin-bottom:24px; display:flex; align-items:center; gap:18px;">
  <div style="font-size:36px;">🏫</div>
  <div style="flex:1;">
    <div style="color:#fff; font-size:20px; font-weight:700;">
      <?= htmlspecialchars($grupoInfo['nombre_grupo'] ?? '',ENT_QUOTES,'UTF-8') ?>
    </div>
    <div style="color:rgba(255,255,255,.6); font-size:13px; margin-top:3px;">
      📚 <?= htmlspecialchars($grupoInfo['nombre_materia'] ?? '',ENT_QUOTES,'UTF-8') ?>
      &nbsp;·&nbsp; 🗓 <?= htmlspecialchars($grupoInfo['periodo'] ?? '',ENT_QUOTES,'UTF-8') ?>
    </div>
  </div>
  <div style="text-align:center;">
    <div style="color:#fff; font-size:32px; font-weight:700; line-height:1;">
      <?= count($alumnosInscritos ?? []) ?>
    </div>
    <div style="color:rgba(255,255,255,.5); font-size:11px;">alumnos</div>
  </div>
</div>

<?php if (!empty($mensaje)): ?>
  <div class="alert alert-<?= $tipoMensaje ?? 'success' ?>"><?= $mensaje ?></div>
<?php endif; ?>

<div class="grid-2" style="align-items:start;">

  <!-- ══ PANEL IZQUIERDO: Agregar alumnos ═══════════════════ -->
  <div style="display:flex; flex-direction:column; gap:16px;">

    <!-- Opción A: CSV -->
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">📂 Importar desde CSV</div>
          <div class="card-subtitle">Carga la lista del SII institucional</div>
        </div>
      </div>
      <div class="alert alert-info" style="margin-bottom:14px;">
        Formato: <code>username, firstname, email, lastname</code><br>
        <small>Usuario y contraseña inicial = número de control</small>
      </div>
      <form action="index.php?accion=procesar_lista_grupo_docente" method="POST"
            enctype="multipart/form-data">
        <input type="hidden" name="id_grupo" value="<?= (int)($grupoInfo['id_grupo'] ?? 0) ?>">
        <div class="form-group">
          <label class="form-label">Archivo CSV o TXT</label>
          <input type="file" name="archivo_sii" accept=".csv,.txt" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">
          📤 Importar e inscribir
        </button>
      </form>
    </div>

    <!-- Opción B: Manual -->
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">✏️ Agregar alumno manualmente</div>
          <div class="card-subtitle">Ingresa los datos uno por uno</div>
        </div>
      </div>
      <form action="index.php?accion=agregar_alumno_manual_docente" method="POST">
        <input type="hidden" name="id_grupo" value="<?= (int)($grupoInfo['id_grupo'] ?? 0) ?>">
        <div class="form-group">
          <label class="form-label">Número de control</label>
          <input type="text" name="numero_control" class="form-control"
                 placeholder="Ej: u300001" required maxlength="20">
        </div>
        <div class="form-group">
          <label class="form-label">Nombre completo</label>
          <input type="text" name="nombre" class="form-control"
                 placeholder="Ej: Juan Pérez López" required maxlength="100">
        </div>
        <div class="form-group">
          <label class="form-label">Correo institucional</label>
          <input type="email" name="correo" class="form-control"
                 placeholder="Ej: u300001@itlac.edu.mx" required maxlength="100">
        </div>
        <div class="alert alert-warning" style="margin-bottom:12px; font-size:12px;">
          🔑 La contraseña inicial será igual al número de control.
          El alumno puede cambiarla después.
        </div>
        <button type="submit" class="btn btn-success" style="width:100%;">
          ➕ Agregar alumno
        </button>
      </form>
    </div>

    <!-- Opción C: Alumno ya existente -->
    <?php if (!empty($alumnosBusqueda)): ?>
    <div class="card">
      <div class="card-header">
        <div class="card-title">🔍 Alumnos encontrados</div>
      </div>
      <?php foreach ($alumnosBusqueda as $ab): ?>
        <div style="display:flex; align-items:center; justify-content:space-between;
                    padding:10px 14px; border-bottom:1px solid #f1f5f9;">
          <div>
            <div style="font-weight:500; font-size:13px;"><?= htmlspecialchars($ab['nombre'],ENT_QUOTES,'UTF-8') ?></div>
            <div style="font-size:11px; color:#94a3b8;"><?= htmlspecialchars($ab['numero_control'],ENT_QUOTES,'UTF-8') ?></div>
          </div>
          <form action="index.php?accion=inscribir_alumno_existente" method="POST" style="margin:0;">
            <input type="hidden" name="id_alumno" value="<?= (int)$ab['id_alumno'] ?>">
            <input type="hidden" name="id_grupo"  value="<?= (int)($grupoInfo['id_grupo'] ?? 0) ?>">
            <button type="submit" class="btn btn-outline btn-sm">+ Inscribir</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Opción D: Buscar alumno existente -->
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">🔍 Inscribir alumno ya registrado</div>
          <div class="card-subtitle">Si el alumno ya existe en otro grupo</div>
        </div>
      </div>
      <form action="index.php?accion=buscar_alumno_docente" method="GET">
        <input type="hidden" name="accion"    value="buscar_alumno_docente">
        <input type="hidden" name="id_grupo"  value="<?= (int)($grupoInfo['id_grupo'] ?? 0) ?>">
        <div class="form-group" style="display:flex; gap:8px; margin-bottom:0;">
          <input type="text" name="q" class="form-control"
                 placeholder="Buscar por nombre o número de control…"
                 value="<?= htmlspecialchars($_GET['q'] ?? '',ENT_QUOTES,'UTF-8') ?>">
          <button type="submit" class="btn btn-outline" style="flex-shrink:0;">Buscar</button>
        </div>
      </form>
    </div>

  </div>

  <!-- ══ PANEL DERECHO: Lista de inscritos ══════════════════ -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">👥 Alumnos inscritos</div>
        <div class="card-subtitle"><?= count($alumnosInscritos ?? []) ?> en este grupo</div>
      </div>
    </div>
    <?php if (!empty($alumnosInscritos)): ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>No. Control</th>
              <th>Nombre</th>
              <th>Cuenta</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($alumnosInscritos as $i => $al): ?>
              <tr>
                <td style="color:#94a3b8; font-size:11px;"><?= $i+1 ?></td>
                <td><span class="badge badge-blue">
                  <?= htmlspecialchars($al['numero_control'],ENT_QUOTES,'UTF-8') ?>
                </span></td>
                <td style="font-weight:500; font-size:13px;">
                  <?= htmlspecialchars($al['nombre'],ENT_QUOTES,'UTF-8') ?>
                </td>
                <td>
                  <?= $al['id_usuario']
                    ? '<span class="badge badge-green">✓ Activa</span>'
                    : '<span class="badge badge-gray">Sin cuenta</span>' ?>
                </td>
                <td>
                  <a href="index.php?accion=desinscribir_alumno&id_alumno=<?= (int)$al['id_alumno'] ?>&id_grupo=<?= (int)($grupoInfo['id_grupo'] ?? 0) ?>"
                     class="btn btn-sm" style="background:#fee2e2;color:#991b1b;"
                     onclick="return confirm('¿Quitar a este alumno del grupo?')">
                     🗑
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div style="text-align:center; padding:40px; color:#94a3b8;">
        <div style="font-size:36px; margin-bottom:8px;">🎒</div>
        <p style="font-size:13px;">Sin alumnos inscritos aún.</p>
      </div>
    <?php endif; ?>
  </div>

</div>
<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
