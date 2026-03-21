<?php
$pageTitle    = 'Mis Calificaciones';
$accionActual = 'mis_calificaciones';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<?php if (!empty($calificaciones)): ?>
  <?php foreach ($calificaciones as $idGrupo => $datos): ?>
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <div>
          <div class="card-title"><?= htmlspecialchars($datos['materia'], ENT_QUOTES, 'UTF-8') ?></div>
          <div class="card-subtitle"><?= htmlspecialchars($datos['nombre_grupo'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($datos['periodo'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Unidad</th>
              <th style="text-align:center;">Asistencia</th>
              <th style="text-align:center;">Actividades</th>
              <th style="text-align:center;">Tareas</th>
              <th style="text-align:center;">Calificación</th>
              <th style="text-align:center;">Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($datos['unidades'] as $u): ?>
              <?php
                $cal = (float)$u['calificacion_unidad'];
                $badgeCal = $cal >= 7 ? 'badge-green' : ($cal >= 6 ? 'badge-amber' : 'badge-red');
              ?>
              <tr>
                <td style="font-weight:500;"><?= htmlspecialchars($u['nombre_unidad'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td style="text-align:center;"><?= number_format((float)$u['calif_asistencia'], 1) ?></td>
                <td style="text-align:center;">
                  <?= $u['promedio_actividades'] !== null ? number_format((float)$u['promedio_actividades'],1) : '<span style="color:#94a3b8;">—</span>' ?>
                </td>
                <td style="text-align:center;">
                  <?= $u['promedio_tareas'] !== null ? number_format((float)$u['promedio_tareas'],1) : '<span style="color:#94a3b8;">—</span>' ?>
                </td>
                <td style="text-align:center;">
                  <?php if ($u['cerrada']): ?>
                    <span class="badge <?= $badgeCal ?>" style="font-size:14px; padding:5px 12px;"><?= number_format($cal,1) ?></span>
                  <?php else: ?>
                    <span style="color:#94a3b8; font-size:12px;">En curso…</span>
                  <?php endif; ?>
                </td>
                <td style="text-align:center;">
                  <?php if ($u['cerrada']): ?>
                    <span class="badge badge-green">✓ Cerrada</span>
                  <?php else: ?>
                    <span class="badge badge-amber">En curso</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="card">
    <p style="text-align:center;color:#94a3b8;padding:40px;">Sin calificaciones disponibles aún.</p>
  </div>
<?php endif; ?>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
