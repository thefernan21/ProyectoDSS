<?php
$pageTitle    = 'Panel Docente';
$accionActual = 'panel_docente';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue">🏫</div>
    <div>
      <div class="stat-value"><?= $totalGrupos ?? 0 ?></div>
      <div class="stat-label">Grupos a cargo</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green">🎒</div>
    <div>
      <div class="stat-value"><?= $totalAlumnos ?? 0 ?></div>
      <div class="stat-label">Alumnos en total</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon amber">📅</div>
    <div>
      <div class="stat-value"><?= $clasesHoy ?? 0 ?></div>
      <div class="stat-label">Listas pasadas hoy</div>
    </div>
  </div>
</div>

<div class="grid-2">

  <!-- Mis grupos -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Mis Grupos</div>
        <div class="card-subtitle">Grupos asignados este periodo</div>
      </div>
      <a href="index.php?accion=pasar_lista" class="btn btn-primary btn-sm">✅ Pasar lista</a>
    </div>
    <?php if (!empty($misGrupos)): ?>
      <div style="display:flex; flex-direction:column; gap:8px;">
        <?php foreach ($misGrupos as $g): ?>
          <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 14px; background:#f8fafc; border-radius:10px;">
            <div>
              <div style="font-weight:600; font-size:14px;"><?= htmlspecialchars($g['nombre_grupo'], ENT_QUOTES, 'UTF-8') ?></div>
              <div style="font-size:12px; color:#64748b; margin-top:2px;"><?= htmlspecialchars($g['nombre_materia'] ?? '', ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($g['periodo'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <a href="index.php?accion=pasar_lista&id_grupo=<?= (int)$g['id_grupo'] ?>&fecha=<?= date('Y-m-d') ?>" class="btn btn-outline btn-sm">
              Pasar hoy →
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p style="color:#94a3b8; font-size:13px; text-align:center; padding:20px 0;">Sin grupos asignados.</p>
    <?php endif; ?>
  </div>

  <!-- Última actividad -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Últimas listas pasadas</div>
        <div class="card-subtitle">Actividad reciente</div>
      </div>
    </div>
    <?php if (!empty($ultimasListas)): ?>
      <div style="display:flex; flex-direction:column; gap:8px;">
        <?php foreach ($ultimasListas as $l): ?>
          <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; background:#f8fafc; border-radius:8px; font-size:13px;">
            <div>
              <span style="font-weight:500;"><?= htmlspecialchars($l['nombre_grupo'], ENT_QUOTES, 'UTF-8') ?></span>
              <span style="color:#94a3b8; margin-left:6px;"><?= htmlspecialchars($l['fecha'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <span class="badge badge-green">✓ Registrada</span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p style="color:#94a3b8; font-size:13px; text-align:center; padding:20px 0;">Aún no has pasado lista.</p>
    <?php endif; ?>
  </div>

</div>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
