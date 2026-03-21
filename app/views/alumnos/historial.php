<?php
$pageTitle    = 'Mi Historial';
$accionActual = 'mi_historial';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<!-- Filtro de grupo -->
<div class="card" style="margin-bottom:20px;">
  <form action="index.php?accion=mi_historial" method="GET" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
    <input type="hidden" name="accion" value="mi_historial">
    <div class="form-group" style="flex:1; min-width:200px; margin-bottom:0;">
      <label class="form-label">Filtrar por materia/grupo</label>
      <select name="id_grupo" class="form-control" onchange="this.form.submit()">
        <option value="">— Todas las materias —</option>
        <?php foreach ($misGrupos ?? [] as $g): ?>
          <option value="<?= (int)$g['id_grupo'] ?>" <?= (isset($_GET['id_grupo']) && $_GET['id_grupo'] == $g['id_grupo']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($g['nombre_grupo'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($g['nombre_materia'], ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-header">
    <div>
      <div class="card-title">Historial de Asistencias</div>
      <div class="card-subtitle"><?= count($historial ?? []) ?> registros encontrados</div>
    </div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Materia</th>
          <th>Grupo</th>
          <th style="text-align:center;">Tipo</th>
          <th style="text-align:center;">Valor</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($historial)): ?>
          <?php foreach ($historial as $h): ?>
            <?php
              $tipo = $h['tipo_asistencia'];
              $badgeClass = match($tipo) {
                'asistencia'   => 'badge-green',
                'retardo'      => 'badge-amber',
                'inasistencia' => 'badge-red',
                default        => 'badge-gray'
              };
              $iconos = [
                'asistencia'   => '✅',
                'retardo'      => '⏰',
                'inasistencia' => '❌',
              ];
            ?>
            <tr>
              <td style="font-size:13px;"><?= htmlspecialchars($h['fecha'], ENT_QUOTES, 'UTF-8') ?></td>
              <td style="font-weight:500; font-size:13px;"><?= htmlspecialchars($h['materia'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td style="font-size:12px; color:#64748b;"><?= htmlspecialchars($h['nombre_grupo'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td style="text-align:center;">
                <span class="badge <?= $badgeClass ?>">
                  <?= $iconos[$tipo] ?? '' ?> <?= ucfirst($tipo) ?>
                </span>
              </td>
              <td style="text-align:center; font-weight:700; color:
                <?= $tipo==='asistencia' ? '#10b981' : ($tipo==='retardo' ? '#f59e0b' : '#ef4444') ?>;">
                <?= number_format((float)$h['valor'], 1) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" style="text-align:center; color:#94a3b8; padding:40px;">
              Sin registros de asistencia aún.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
