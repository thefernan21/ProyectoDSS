<?php
$pageTitle    = 'Calificaciones';
$accionActual = 'calificaciones_docente';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<!-- Selector grupo + unidad -->
<div class="card" style="margin-bottom:20px;">
  <form action="index.php?accion=calificaciones_docente" method="GET"
        style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
    <input type="hidden" name="accion" value="calificaciones_docente">
    <div class="form-group" style="flex:1; min-width:220px; margin-bottom:0;">
      <label class="form-label">Grupo</label>
      <select name="id_grupo" class="form-control" onchange="this.form.submit()">
        <option value="">— Selecciona grupo —</option>
        <?php foreach ($misGrupos ?? [] as $g): ?>
          <option value="<?= (int)$g['id_grupo'] ?>"
            <?= (isset($_GET['id_grupo']) && $_GET['id_grupo'] == $g['id_grupo']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($g['nombre_grupo'], ENT_QUOTES, 'UTF-8') ?> —
            <?= htmlspecialchars($g['nombre_materia'], ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if (!empty($unidades)): ?>
    <div class="form-group" style="flex:0 0 220px; margin-bottom:0;">
      <label class="form-label">Unidad</label>
      <select name="id_unidad" class="form-control" onchange="this.form.submit()">
        <?php foreach ($unidades as $u): ?>
          <option value="<?= (int)$u['id_unidad'] ?>"
            <?= (isset($_GET['id_unidad']) && $_GET['id_unidad'] == $u['id_unidad']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8') ?>
            <?= $u['cerrada'] ? ' ✓ Cerrada' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
  </form>
</div>

<?php if (!empty($resumen) && !empty($unidadSel)): ?>

<!-- Header unidad seleccionada -->
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
  <div>
    <h3 style="font-size:16px; font-weight:700;">
      <?= htmlspecialchars($unidadSel['nombre'], ENT_QUOTES, 'UTF-8') ?>
      <?php if ($unidadSel['cerrada']): ?>
        <span class="badge badge-green" style="margin-left:8px;">✓ Cerrada</span>
      <?php else: ?>
        <span class="badge badge-amber" style="margin-left:8px;">En curso</span>
      <?php endif; ?>
    </h3>
    <?php if ($unidadSel['fecha_fin']): ?>
      <div style="font-size:12px; color:#64748b;">
        Fecha límite: <strong><?= htmlspecialchars($unidadSel['fecha_fin'], ENT_QUOTES, 'UTF-8') ?></strong>
      </div>
    <?php endif; ?>
  </div>
  <?php if (!$unidadSel['cerrada']): ?>
    <a href="index.php?accion=cerrar_unidad&id_unidad=<?= (int)$unidadSel['id_unidad'] ?>&id_grupo=<?= (int)$_GET['id_grupo'] ?>"
       class="btn btn-primary btn-sm"
       onclick="return confirm('¿Cerrar esta unidad? Las calificaciones quedarán visibles para el administrador.')">
      🔒 Cerrar unidad y enviar calificaciones
    </a>
  <?php else: ?>
    <a href="index.php?accion=reabrir_unidad&id_unidad=<?= (int)$unidadSel['id_unidad'] ?>&id_grupo=<?= (int)$_GET['id_grupo'] ?>"
       class="btn btn-outline btn-sm">
      🔓 Reabrir unidad
    </a>
  <?php endif; ?>
</div>

<!-- Tabla de calificaciones -->
<div class="card">
  <div class="card-header">
    <div>
      <div class="card-title">Calificaciones por alumno</div>
      <div class="card-subtitle">
        Fórmula: Asistencia 20% + Actividades 40% + Tareas 40%
      </div>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>No. Control</th><th>Alumno</th>
          <th style="text-align:center;">Asistencia<br><small style="color:#94a3b8;">(20%)</small></th>
          <th style="text-align:center;">Actividades<br><small style="color:#94a3b8;">(40%)</small></th>
          <th style="text-align:center;">Tareas<br><small style="color:#94a3b8;">(40%)</small></th>
          <th style="text-align:center; background:#f8fafc;">Calificación<br>Unidad</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($resumen as $r): ?>
          <?php
            $cal = (float)$r['calificacion_unidad'];
            $badgeCal = $cal >= 7 ? 'badge-green' : ($cal >= 6 ? 'badge-amber' : 'badge-red');
          ?>
          <tr>
            <td><span class="badge badge-blue"><?= htmlspecialchars($r['numero_control'], ENT_QUOTES, 'UTF-8') ?></span></td>
            <td style="font-weight:500;"><?= htmlspecialchars($r['nombre_alumno'], ENT_QUOTES, 'UTF-8') ?></td>
            <td style="text-align:center;">
              <?= number_format((float)$r['calif_asistencia'], 1) ?>
            </td>
            <td style="text-align:center;">
              <?= $r['promedio_actividades'] !== null ? number_format((float)$r['promedio_actividades'], 1) : '<span style="color:#94a3b8;">—</span>' ?>
            </td>
            <td style="text-align:center;">
              <?= $r['promedio_tareas'] !== null ? number_format((float)$r['promedio_tareas'], 1) : '<span style="color:#94a3b8;">—</span>' ?>
            </td>
            <td style="text-align:center; background:#f8fafc;">
              <span class="badge <?= $badgeCal ?>" style="font-size:14px; padding:5px 12px;">
                <?= number_format($cal, 1) ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif (!empty($idGrupo)): ?>
  <div class="card">
    <p style="text-align:center;color:#94a3b8;padding:40px;">Selecciona una unidad para ver calificaciones.</p>
  </div>
<?php endif; ?>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
