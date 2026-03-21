<?php
$pageTitle    = 'Gestión de Unidades';
$accionActual = 'unidades_docente';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<?php if (!empty($mensaje)): ?>
  <div class="alert alert-<?= $tipoMensaje ?? 'success' ?>"><?= $mensaje ?></div>
<?php endif; ?>

<!-- Selector de grupo -->
<div class="card" style="margin-bottom:20px;">
  <form action="index.php?accion=unidades_docente" method="GET"
        style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
    <input type="hidden" name="accion" value="unidades_docente">
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

<?php if (!empty($unidades)): ?>
<div style="display:flex; flex-direction:column; gap:14px;">
  <?php foreach ($unidades as $u): ?>
    <div class="card">
      <form action="index.php?accion=guardar_unidad" method="POST">
        <input type="hidden" name="id_unidad" value="<?= (int)$u['id_unidad'] ?>">
        <input type="hidden" name="id_grupo"  value="<?= (int)($_GET['id_grupo'] ?? 0) ?>">

        <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
          <!-- Número de unidad -->
          <div style="width:40px; height:40px; background:<?= $u['cerrada'] ? '#dcfce7' : '#eff6ff' ?>;
                      border-radius:10px; display:flex; align-items:center; justify-content:center;
                      font-size:18px; font-weight:700; color:<?= $u['cerrada'] ? '#166534' : '#1d4ed8' ?>;
                      flex-shrink:0;">
            <?= (int)$u['numero_unidad'] ?>
          </div>

          <!-- Nombre -->
          <div class="form-group" style="flex:1; min-width:180px; margin-bottom:0;">
            <label class="form-label" style="font-size:11px;">Nombre de la unidad</label>
            <input type="text" name="nombre" class="form-control"
                   value="<?= htmlspecialchars($u['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="Ej: Unidad <?= $u['numero_unidad'] ?> — Fundamentos"
                   <?= $u['cerrada'] ? 'disabled' : '' ?>>
          </div>

          <!-- Fecha límite -->
          <div class="form-group" style="flex:0 0 170px; margin-bottom:0;">
            <label class="form-label" style="font-size:11px;">Fecha de cierre</label>
            <input type="date" name="fecha_fin" class="form-control"
                   value="<?= htmlspecialchars($u['fecha_fin'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   <?= $u['cerrada'] ? 'disabled' : '' ?>>
          </div>

          <!-- Estado + botones -->
          <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
            <?php if ($u['cerrada']): ?>
              <span class="badge badge-green">✓ Cerrada</span>
              <small style="color:#94a3b8; font-size:11px;">
                <?= htmlspecialchars($u['fecha_cierre'] ?? '', ENT_QUOTES, 'UTF-8') ?>
              </small>
            <?php else: ?>
              <button type="submit" class="btn btn-outline btn-sm">💾 Guardar</button>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  <?php endforeach; ?>
</div>
<?php elseif (isset($_GET['id_grupo'])): ?>
  <div class="card">
    <p style="text-align:center;color:#94a3b8;padding:40px;">Sin unidades registradas para este grupo.</p>
  </div>
<?php endif; ?>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
