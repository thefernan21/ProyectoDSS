<?php
$pageTitle    = 'Calificaciones Finales';
$accionActual = 'calificaciones_admin';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<div class="alert alert-info">
  📋 Solo se muestran las calificaciones de unidades que el docente haya <strong>cerrado</strong>.
  Cuando todas las unidades de un grupo estén cerradas, aparece la calificación final de la materia.
</div>

<?php if (!empty($grupos)): ?>
  <?php foreach ($grupos as $grupo): ?>
    <div class="card" style="margin-bottom:24px;">
      <div class="card-header">
        <div>
          <div class="card-title">
            <?= htmlspecialchars($grupo['nombre_grupo'], ENT_QUOTES, 'UTF-8') ?> —
            <?= htmlspecialchars($grupo['nombre_materia'], ENT_QUOTES, 'UTF-8') ?>
          </div>
          <div class="card-subtitle">
            👨‍🏫 <?= htmlspecialchars($grupo['nombre_docente'], ENT_QUOTES, 'UTF-8') ?> ·
            🗓 <?= htmlspecialchars($grupo['periodo'], ENT_QUOTES, 'UTF-8') ?>
          </div>
        </div>
        <?php if ($grupo['todas_cerradas']): ?>
          <span class="badge badge-green" style="font-size:12px; padding:6px 12px;">✓ Materia finalizada</span>
        <?php else: ?>
          <span class="badge badge-amber">
            <?= $grupo['unidades_cerradas'] ?> / <?= $grupo['total_unidades'] ?> unidades cerradas
          </span>
        <?php endif; ?>
      </div>

      <?php if (!empty($grupo['alumnos'])): ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>No. Control</th>
              <th>Alumno</th>
              <?php foreach ($grupo['unidades_cerradas_lista'] as $u): ?>
                <th style="text-align:center; font-size:11px;">
                  <?= htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8') ?>
                </th>
              <?php endforeach; ?>
              <?php if ($grupo['todas_cerradas']): ?>
                <th style="text-align:center; background:#f0fdf4;">Promedio Final</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($grupo['alumnos'] as $al): ?>
              <tr>
                <td><span class="badge badge-blue"><?= htmlspecialchars($al['numero_control'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td style="font-weight:500;"><?= htmlspecialchars($al['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                <?php foreach ($grupo['unidades_cerradas_lista'] as $u): ?>
                  <?php
                    $cal = (float)($al['calificaciones'][$u['id_unidad']] ?? 0);
                    $badge = $cal >= 7 ? 'badge-green' : ($cal >= 6 ? 'badge-amber' : 'badge-red');
                  ?>
                  <td style="text-align:center;">
                    <span class="badge <?= $badge ?>"><?= number_format($cal, 1) ?></span>
                  </td>
                <?php endforeach; ?>
                <?php if ($grupo['todas_cerradas']): ?>
                  <?php
                    $prom = count($al['calificaciones']) > 0 ? round(array_sum($al['calificaciones']) / count($al['calificaciones']), 1) : 0;
                    $badgeProm = $prom >= 7 ? 'badge-green' : ($prom >= 6 ? 'badge-amber' : 'badge-red');
                  ?>
                  <td style="text-align:center; background:#f0fdf4;">
                    <span class="badge <?= $badgeProm ?>" style="font-size:14px; padding:6px 14px;">
                      <?= number_format($prom, 1) ?>
                    </span>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <p style="text-align:center;color:#94a3b8;padding:20px;">Sin alumnos inscritos.</p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="card">
    <p style="text-align:center;color:#94a3b8;padding:40px;">
      Aún no hay unidades cerradas por ningún docente.
    </p>
  </div>
<?php endif; ?>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
