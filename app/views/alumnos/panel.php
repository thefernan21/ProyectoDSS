<?php
$pageTitle    = 'Mi Panel';
$accionActual = 'panel_alumno';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue">📅</div>
    <div>
      <div class="stat-value"><?= $totalClases ?? 0 ?></div>
      <div class="stat-label">Clases totales</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green">✅</div>
    <div>
      <div class="stat-value"><?= $totalAsistencias ?? 0 ?></div>
      <div class="stat-label">Asistencias</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon amber">⏰</div>
    <div>
      <div class="stat-value"><?= $totalRetardos ?? 0 ?></div>
      <div class="stat-label">Retardos</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fef2f2;">❌</div>
    <div>
      <div class="stat-value"><?= $totalInasistencias ?? 0 ?></div>
      <div class="stat-label">Inasistencias</div>
    </div>
  </div>
</div>

<!-- Porcentaje por materia -->
<div class="card" style="margin-bottom:20px;">
  <div class="card-header">
    <div>
      <div class="card-title">Porcentaje de asistencia por materia</div>
      <div class="card-subtitle">Basado en el valor ponderado (Asist=1.0 · Retardo=0.8 · Inasist=0.0)</div>
    </div>
    <a href="index.php?accion=mi_historial" class="btn btn-outline btn-sm">Ver historial completo →</a>
  </div>

  <?php if (!empty($porcentajes)): ?>
    <div style="display:flex; flex-direction:column; gap:16px;">
      <?php foreach ($porcentajes as $p): ?>
        <?php
          $pct = (float)($p['porcentaje_asistencia'] ?? 0);
          $color = $pct >= 80 ? '#10b981' : ($pct >= 70 ? '#f59e0b' : '#ef4444');
          $badgeClass = $pct >= 80 ? 'badge-green' : ($pct >= 70 ? 'badge-amber' : 'badge-red');
        ?>
        <div>
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <div>
              <span style="font-weight:500; font-size:14px;"><?= htmlspecialchars($p['materia'], ENT_QUOTES, 'UTF-8') ?></span>
              <span style="color:#94a3b8; font-size:12px; margin-left:8px;"><?= htmlspecialchars($p['nombre_grupo'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <span class="badge <?= $badgeClass ?>"><?= $pct ?>%</span>
          </div>
          <div class="progress-wrap">
            <div class="progress-bar" style="width:<?= min($pct,100) ?>%; background:<?= $color ?>;"></div>
          </div>
          <div style="margin-top:4px; font-size:11px; color:#94a3b8;">
            <?= (int)$p['total_clases'] ?> clases · <?= $p['puntos_acumulados'] ?> puntos
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p style="text-align:center; color:#94a3b8; padding:30px 0; font-size:14px;">
      Aún no hay asistencias registradas para tus grupos.
    </p>
  <?php endif; ?>
</div>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
