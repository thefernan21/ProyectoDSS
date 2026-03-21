<?php
$pageTitle    = 'Dashboard';
$accionActual = 'dashboard_admin';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue">🎒</div>
    <div>
      <div class="stat-value"><?= $totalAlumnos ?? 0 ?></div>
      <div class="stat-label">Alumnos registrados</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green">👨‍🏫</div>
    <div>
      <div class="stat-value"><?= $totalDocentes ?? 0 ?></div>
      <div class="stat-label">Docentes activos</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon amber">📚</div>
    <div>
      <div class="stat-value"><?= $totalMaterias ?? 0 ?></div>
      <div class="stat-label">Materias en catálogo</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple">✅</div>
    <div>
      <div class="stat-value"><?= $totalAsistencias ?? 0 ?></div>
      <div class="stat-label">Registros de asistencia</div>
    </div>
  </div>
</div>

<div class="grid-2">
  <!-- Acciones rápidas -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Acciones rápidas</div>
        <div class="card-subtitle">Tareas frecuentes del administrador</div>
      </div>
    </div>
    <div style="display:flex; flex-direction:column; gap:10px;">
      <a href="index.php?accion=importar_view" class="btn btn-primary">
        📂 Importar alumnos desde CSV
      </a>
      <a href="index.php?accion=ver_materias" class="btn btn-outline">
        📚 Gestionar materias
      </a>
      <a href="index.php?accion=ver_grupos" class="btn btn-outline">
        🏫 Gestionar grupos
      </a>
      <a href="index.php?accion=ver_alumnos" class="btn btn-outline">
        🎒 Ver directorio de alumnos
      </a>
    </div>
  </div>

  <!-- Últimas acciones del audit log -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Registro de auditoría</div>
        <div class="card-subtitle">Últimas 5 acciones del sistema</div>
      </div>
    </div>
    <?php if (!empty($auditLog)): ?>
      <div style="display:flex; flex-direction:column; gap:8px;">
        <?php foreach ($auditLog as $log): ?>
          <div style="padding:10px 12px; background:#f8fafc; border-radius:8px; font-size:13px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3px;">
              <span class="badge badge-blue"><?= htmlspecialchars($log['accion'], ENT_QUOTES, 'UTF-8') ?></span>
              <span style="color:#94a3b8; font-size:11px;"><?= htmlspecialchars($log['fecha'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div style="color:#475569;"><?= htmlspecialchars($log['detalle'], ENT_QUOTES, 'UTF-8') ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p style="color:#94a3b8; font-size:13px; text-align:center; padding:20px 0;">Sin registros aún.</p>
    <?php endif; ?>
  </div>
</div>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
