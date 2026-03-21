<?php
$pageTitle    = 'Mis Asistencias';
$accionActual = 'mis_asistencias';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<!-- Filtro de grupo -->
<div class="card" style="margin-bottom:20px;">
  <form action="index.php?accion=mis_asistencias" method="GET"
        style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
    <input type="hidden" name="accion" value="mis_asistencias">
    <div class="form-group" style="flex:1; min-width:200px; margin-bottom:0;">
      <label class="form-label">Filtrar por materia</label>
      <select name="id_grupo" class="form-control" onchange="this.form.submit()">
        <option value="">— Todas las materias —</option>
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

<!-- Resumen por materia (solo si no hay filtro) -->
<?php if (empty($_GET['id_grupo']) && !empty($resumenGrupos)): ?>
<div style="display:flex; flex-direction:column; gap:12px; margin-bottom:24px;">
  <?php foreach ($resumenGrupos as $rg): ?>
    <?php
      $pct = (float)($rg['porcentaje'] ?? 0);
      $color = $pct >= 80 ? '#10b981' : ($pct >= 70 ? '#f59e0b' : '#ef4444');
      $badge = $pct >= 80 ? 'badge-green' : ($pct >= 70 ? 'badge-amber' : 'badge-red');
    ?>
    <div class="card" style="padding:16px 20px;">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
        <div>
          <div style="font-weight:600; font-size:14px;"><?= htmlspecialchars($rg['nombre_materia'], ENT_QUOTES, 'UTF-8') ?></div>
          <div style="font-size:12px; color:#64748b;"><?= htmlspecialchars($rg['nombre_grupo'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div style="display:flex; gap:16px; text-align:center;">
          <div><div style="font-size:18px; font-weight:700; color:#10b981;"><?= (int)$rg['asistencias'] ?></div><div style="font-size:10px; color:#94a3b8;">Asist.</div></div>
          <div><div style="font-size:18px; font-weight:700; color:#f59e0b;"><?= (int)$rg['retardos'] ?></div><div style="font-size:10px; color:#94a3b8;">Retardos</div></div>
          <div><div style="font-size:18px; font-weight:700; color:#ef4444;"><?= (int)$rg['faltas'] ?></div><div style="font-size:10px; color:#94a3b8;">Faltas</div></div>
          <div><span class="badge <?= $badge ?>" style="font-size:14px; padding:6px 14px;"><?= $pct ?>%</span></div>
        </div>
      </div>
      <div class="progress-wrap">
        <div class="progress-bar" style="width:<?= min($pct,100) ?>%; background:<?= $color ?>;"></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Detalle de asistencias -->
<div class="card">
  <div class="card-header">
    <div class="card-title">Detalle de asistencias</div>
    <div class="card-subtitle"><?= count($historial ?? []) ?> registros</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Fecha</th><th>Materia</th><th>Grupo</th>
            <th style="text-align:center;">Tipo</th>
            <th style="text-align:center;">Valor</th></tr>
      </thead>
      <tbody>
        <?php if (!empty($historial)): ?>
          <?php foreach ($historial as $h): ?>
            <?php
              $tipo = $h['tipo_asistencia'];
              $badges = ['asistencia'=>'badge-green','retardo'=>'badge-amber','inasistencia'=>'badge-red'];
              $iconos = ['asistencia'=>'✅','retardo'=>'⏰','inasistencia'=>'❌'];
            ?>
            <tr>
              <td style="font-size:13px;"><?= htmlspecialchars($h['fecha'], ENT_QUOTES, 'UTF-8') ?></td>
              <td style="font-weight:500; font-size:13px;"><?= htmlspecialchars($h['materia'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td style="font-size:12px; color:#64748b;"><?= htmlspecialchars($h['nombre_grupo'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td style="text-align:center;">
                <span class="badge <?= $badges[$tipo] ?? 'badge-gray' ?>">
                  <?= $iconos[$tipo] ?? '' ?> <?= ucfirst($tipo) ?>
                </span>
              </td>
              <td style="text-align:center; font-weight:700; color:<?= $tipo==='asistencia'?'#10b981':($tipo==='retardo'?'#f59e0b':'#ef4444') ?>;">
                <?= number_format((float)$h['valor'], 1) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:40px;">Sin registros aún.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
