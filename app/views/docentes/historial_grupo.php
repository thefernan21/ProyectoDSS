<?php
$pageTitle    = 'Historial del Grupo';
$accionActual = 'historial_grupo';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<!-- Selector de grupo -->
<div class="card" style="margin-bottom:20px;">
  <form action="index.php?accion=historial_grupo" method="GET"
        style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
    <input type="hidden" name="accion" value="historial_grupo">
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
  </form>
</div>

<?php if (!empty($idGrupo)): ?>

<!-- Tabs: Historial de sesiones / Resumen por alumno -->
<div style="display:flex; gap:4px; margin-bottom:20px; border-bottom:2px solid #e2e8f0;">
  <a href="index.php?accion=historial_grupo&id_grupo=<?= $idGrupo ?>&vista=sesiones"
     style="padding:10px 20px; font-size:14px; font-weight:500; text-decoration:none; border-radius:8px 8px 0 0;
     <?= (($vista??'sesiones')==='sesiones') ? 'background:#1d4ed8;color:#fff;' : 'color:#64748b;' ?>">
     📅 Sesiones
  </a>
  <a href="index.php?accion=historial_grupo&id_grupo=<?= $idGrupo ?>&vista=alumnos"
     style="padding:10px 20px; font-size:14px; font-weight:500; text-decoration:none; border-radius:8px 8px 0 0;
     <?= (($vista??'')==='alumnos') ? 'background:#1d4ed8;color:#fff;' : 'color:#64748b;' ?>">
     🎒 Resumen por alumno
  </a>
</div>

<?php if (($vista??'sesiones') === 'sesiones'): ?>
<!-- ══ VISTA: SESIONES ══ -->
<div class="card">
  <div class="card-header">
    <div class="card-title">Sesiones registradas</div>
    <div class="card-subtitle"><?= count($fechas ?? []) ?> sesiones · Haz clic en "Editar" para modificar una</div>
  </div>
  <?php if (!empty($fechas)): ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Fecha</th><th>Actividad</th><th>Tarea</th>
          <th style="text-align:center;">Presentes</th>
          <th style="text-align:center;">Retardos</th>
          <th style="text-align:center;">Faltas</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($fechas as $s): ?>
          <tr>
            <td style="font-weight:500;"><?= htmlspecialchars($s['fecha'], ENT_QUOTES, 'UTF-8') ?></td>
            <td style="font-size:12px; color:#475569;">
              <?= !empty($s['actividad']) ? htmlspecialchars($s['actividad'], ENT_QUOTES, 'UTF-8') : '<span style="color:#94a3b8;">—</span>' ?>
            </td>
            <td style="font-size:12px; color:#475569;">
              <?= !empty($s['tarea']) ? htmlspecialchars($s['tarea'], ENT_QUOTES, 'UTF-8') : '<span style="color:#94a3b8;">—</span>' ?>
            </td>
            <td style="text-align:center;"><span class="badge badge-green"><?= (int)$s['presentes'] ?></span></td>
            <td style="text-align:center;"><span class="badge badge-amber"><?= (int)$s['retardos'] ?></span></td>
            <td style="text-align:center;"><span class="badge badge-red"><?= (int)$s['faltas'] ?></span></td>
            <td>
              <a href="index.php?accion=cargar_lista&id_grupo=<?= $idGrupo ?>&fecha=<?= $s['fecha'] ?>"
                 class="btn btn-outline btn-sm">✏️ Editar</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
    <p style="text-align:center;color:#94a3b8;padding:40px;font-size:14px;">Sin sesiones registradas.</p>
  <?php endif; ?>
</div>

<?php else: ?>
<!-- ══ VISTA: RESUMEN POR ALUMNO ══ -->
<div class="card">
  <div class="card-header">
    <div class="card-title">Resumen de asistencia por alumno</div>
    <div class="card-subtitle">Solo visible para el docente</div>
  </div>
  <?php if (!empty($resumenAlumnos)): ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>No. Control</th><th>Alumno</th>
          <th style="text-align:center;">Total clases</th>
          <th style="text-align:center; color:#10b981;">✅ Asistencias</th>
          <th style="text-align:center; color:#f59e0b;">⏰ Retardos</th>
          <th style="text-align:center; color:#ef4444;">❌ Faltas</th>
          <th style="text-align:center;">% Asistencia</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($resumenAlumnos as $r): ?>
          <?php
            $pct = $r['total_clases'] > 0
              ? round(($r['asistencias'] + $r['retardos'] * 0.8) / $r['total_clases'] * 100, 1)
              : 0;
            $colorPct = $pct >= 80 ? '#10b981' : ($pct >= 70 ? '#f59e0b' : '#ef4444');
            $badgePct = $pct >= 80 ? 'badge-green' : ($pct >= 70 ? 'badge-amber' : 'badge-red');
          ?>
          <tr>
            <td><span class="badge badge-blue"><?= htmlspecialchars($r['numero_control'], ENT_QUOTES, 'UTF-8') ?></span></td>
            <td style="font-weight:500;"><?= htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
            <td style="text-align:center;"><?= (int)$r['total_clases'] ?></td>
            <td style="text-align:center; font-weight:600; color:#10b981;"><?= (int)$r['asistencias'] ?></td>
            <td style="text-align:center; font-weight:600; color:#f59e0b;"><?= (int)$r['retardos'] ?></td>
            <td style="text-align:center; font-weight:600; color:#ef4444;"><?= (int)$r['faltas'] ?></td>
            <td style="text-align:center;">
              <span class="badge <?= $badgePct ?>"><?= $pct ?>%</span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
    <p style="text-align:center;color:#94a3b8;padding:40px;font-size:14px;">Sin registros aún.</p>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php endif; ?>
<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
