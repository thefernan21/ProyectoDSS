<?php
$pageTitle    = 'Agregar alumnos — ' . htmlspecialchars($grupo['nombre_grupo'] ?? '', ENT_QUOTES, 'UTF-8');
$accionActual = 'cargar_lista_grupo_docente';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<div style="margin-bottom:20px;">
  <a href="index.php?accion=mis_grupos"
     style="color:#64748b; font-size:13px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
    ← Volver a Mis Grupos
  </a>
</div>

<!-- Info del grupo -->
<div style="background: linear-gradient(135deg, #1e3a5f, #1d4ed8);
            border-radius:12px; padding:20px 24px; margin-bottom:24px;
            display:flex; align-items:center; gap:20px;">
  <div style="font-size:40px;">🏫</div>
  <div>
    <div style="color:rgba(255,255,255,.6); font-size:11px; font-weight:600;
                text-transform:uppercase; letter-spacing:.08em; margin-bottom:4px;">
      Grupo
    </div>
    <div style="color:#fff; font-size:20px; font-weight:700;">
      <?= htmlspecialchars($grupo['nombre_grupo'] ?? '', ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div style="color:rgba(255,255,255,.6); font-size:13px; margin-top:4px;">
      📚 <?= htmlspecialchars($grupo['nombre_materia'] ?? '', ENT_QUOTES, 'UTF-8') ?>
      &nbsp;·&nbsp;
      🗓 <?= htmlspecialchars($grupo['periodo'] ?? '', ENT_QUOTES, 'UTF-8') ?>
    </div>
  </div>
  <div style="margin-left:auto; text-align:center;">
    <div style="color:#fff; font-size:32px; font-weight:700; line-height:1;">
      <?= (int)($grupo['total_alumnos'] ?? 0) ?>
    </div>
    <div style="color:rgba(255,255,255,.5); font-size:11px;">alumnos actuales</div>
  </div>
</div>

<?php if (!empty($mensaje)): ?>
  <div class="alert alert-<?= htmlspecialchars($tipoMensaje ?? 'success', ENT_QUOTES, 'UTF-8') ?>">
    <?= $mensaje ?>
  </div>
<?php endif; ?>

<div class="grid-2" style="align-items:start;">

  <!-- IZQUIERDA: DOS FORMULARIOS EN COLUMNA -->
  <div style="display:flex; flex-direction:column; gap:16px;">

    <!-- FORMULARIO CSV -->
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">📂 Cargar lista (CSV)</div>
          <div class="card-subtitle">
            Importa varios alumnos a la vez al grupo
            <strong><?= htmlspecialchars($grupo['nombre_grupo'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
          </div>
        </div>
      </div>

      <form action="index.php?accion=procesar_lista_grupo_docente" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_grupo" value="<?= (int)($grupo['id_grupo'] ?? 0) ?>">

        <div class="form-group">
          <label class="form-label">Archivo CSV o TXT (máx. 4KB)</label>
          <input type="file" name="archivo_sii" accept=".csv,.txt" class="form-control" required>
          <small style="color:#64748b; font-size:11px; margin-top:5px; display:block;">
            Formato: <code>username, firstname, email, lastname</code>
          </small>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;">
          📤 Cargar e inscribir al grupo
        </button>
      </form>
    </div>

    <!-- FORMULARIO ALUMNO MANUAL -->
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">➕ Agregar alumno manualmente</div>
          <div class="card-subtitle">Para incorporar un alumno individual al grupo</div>
        </div>
      </div>

      <form action="index.php?accion=agregar_alumno_manual" method="POST">
        <input type="hidden" name="id_grupo" value="<?= (int)($grupo['id_grupo'] ?? 0) ?>">

        <div class="form-group">
          <label class="form-label">Número de control</label>
          <input type="text" name="numero_control" class="form-control"
                 placeholder="Ej: 20300001" required maxlength="20">
        </div>

        <div class="form-group">
          <label class="form-label">Nombre completo</label>
          <input type="text" name="nombre" class="form-control"
                 placeholder="Ej: Juan Pérez López" required maxlength="120">
        </div>

        <div class="form-group">
          <label class="form-label">Correo institucional</label>
          <input type="email" name="correo" class="form-control"
                 placeholder="usuario@itlac.edu.mx" required>
        </div>

        <button type="submit" class="btn btn-success" style="width:100%;">
          ✅ Registrar e inscribir al grupo
        </button>

        <small style="color:#64748b; font-size:11px; margin-top:10px; display:block;">
          🔑 Si el alumno es nuevo, su usuario y contraseña serán su <strong>número de control</strong>.
          Si ya existía en el sistema, solo se inscribirá al grupo.
        </small>
      </form>
    </div>

  </div>

  <!-- DERECHA: FORMATO + ALUMNOS INSCRITOS -->
  <div style="display:flex; flex-direction:column; gap:16px;">

    <div class="card">
      <div class="card-header">
        <div class="card-title">📋 Formato del CSV</div>
      </div>
      <div style="background:#0f1629; border-radius:8px; padding:14px; font-size:12px;
                  font-family:monospace; color:#e2e8f0; line-height:1.8; overflow-x:auto;">
        <span style="color:#64748b;">username,firstname,email,lastname</span><br>
        <span style="color:#34d399;">u300001</span>,Juan,<span style="color:#60a5fa;">u300001@itlac.edu.mx</span>,Pérez López<br>
        <span style="color:#34d399;">u300002</span>,María,<span style="color:#60a5fa;">u300002@itlac.edu.mx</span>,González Ruiz<br>
        <span style="color:#64748b;">...</span>
      </div>
      <div style="margin-top:12px; display:flex; flex-direction:column; gap:6px; font-size:12px; color:#475569;">
        <div>🔑 <strong>username</strong> → número de control y usuario de login</div>
        <div>👤 <strong>firstname + lastname</strong> → nombre completo</div>
        <div>📧 <strong>email</strong> → correo institucional</div>
      </div>
    </div>

    <?php if (!empty($alumnosInscritos)): ?>
    <div class="card">
      <div class="card-header">
        <div class="card-title">👥 Alumnos inscritos</div>
        <div class="card-subtitle"><?= count($alumnosInscritos) ?> en este grupo</div>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>No. Control</th>
              <th>Nombre</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($alumnosInscritos as $a): ?>
              <tr>
                <td><span class="badge badge-blue"><?= htmlspecialchars($a['numero_control'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td style="font-size:13px;"><?= htmlspecialchars($a['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php else: ?>
    <div class="card">
      <div style="text-align:center; padding:24px 0; color:#94a3b8;">
        <div style="font-size:36px; margin-bottom:8px;">🎒</div>
        <p style="font-size:13px;">Este grupo aún no tiene alumnos inscritos.</p>
        <p style="font-size:12px; margin-top:4px;">Carga un CSV o agrega un alumno manualmente.</p>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
