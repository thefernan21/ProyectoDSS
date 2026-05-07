<?php
$pageTitle    = 'Mis Grupos';
$accionActual = 'mis_grupos_docente';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<?php if (!empty($mensaje)): ?>
  <div class="alert alert-<?= $tipoMensaje ?? 'success' ?>"><?= $mensaje ?></div>
<?php endif; ?>

<div class="grid-2" style="align-items:start;">

  <!-- ══ CREAR NUEVO GRUPO ══════════════════════════════════ -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">➕ Crear nuevo grupo</div>
        <div class="card-subtitle">Se asignará automáticamente a tu cuenta</div>
      </div>
    </div>

    <!-- Indicador de pasos -->
    <div style="display:flex; gap:0; margin-bottom:20px;">
      <div style="flex:1; text-align:center; padding:8px; background:#1d4ed8; border-radius:6px 0 0 6px;">
        <div style="color:#fff; font-size:11px; font-weight:600;">PASO 1</div>
        <div style="color:#bfdbfe; font-size:10px;">Crear grupo</div>
      </div>
      <div style="flex:1; text-align:center; padding:8px; background:#e2e8f0; border-radius:0 6px 6px 0;">
        <div style="color:#94a3b8; font-size:11px; font-weight:600;">PASO 2</div>
        <div style="color:#94a3b8; font-size:10px;">Agregar alumnos</div>
      </div>
    </div>

    <form action="index.php?accion=guardar_grupo_docente" method="POST">
      <div class="form-group">
        <label class="form-label">Materia</label>
        <select name="id_materia" class="form-control" required>
          <option value="">— Selecciona —</option>
          <?php foreach ($materias ?? [] as $m): ?>
            <option value="<?= (int)$m['id_materia'] ?>">
              [<?= htmlspecialchars($m['clave_materia'],ENT_QUOTES,'UTF-8') ?>]
              <?= htmlspecialchars($m['nombre'],ENT_QUOTES,'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Nombre del grupo</label>
        <input type="text" name="nombre_grupo" class="form-control"
               placeholder="Ej: ISC-501-A" required maxlength="50">
      </div>
      <div class="form-group">
        <label class="form-label">Periodo</label>
        <input type="text" name="periodo" class="form-control"
               placeholder="Ej: Ene-Jun 2026" required maxlength="30">
      </div>
      <div class="form-group">
        <label class="form-label">Número de unidades</label>
        <input type="number" name="num_unidades" class="form-control"
               value="4" min="1" max="10" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;">
        🏫 Crear grupo
      </button>
    </form>
  </div>

  <!-- ══ MIS GRUPOS ═════════════════════════════════════════ -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Mis grupos activos</div>
        <div class="card-subtitle"><?= count($misGrupos ?? []) ?> grupos</div>
      </div>
    </div>

    <?php if (!empty($misGrupos)): ?>
      <div style="display:flex; flex-direction:column; gap:12px;">
        <?php foreach ($misGrupos as $g): ?>
          <div style="border:1px solid #e2e8f0; border-radius:10px; overflow:hidden;">
            <div style="padding:12px 16px; background:#f8fafc; display:flex;
                        align-items:center; justify-content:space-between;">
              <div>
                <div style="font-weight:600; font-size:14px;">
                  <?= htmlspecialchars($g['nombre_grupo'],ENT_QUOTES,'UTF-8') ?>
                  <span class="badge badge-gray" style="font-size:10px; margin-left:6px;">
                    <?= htmlspecialchars($g['periodo'],ENT_QUOTES,'UTF-8') ?>
                  </span>
                </div>
                <div style="font-size:12px; color:#64748b; margin-top:2px;">
                  📚 <?= htmlspecialchars($g['nombre_materia'],ENT_QUOTES,'UTF-8') ?>
                </div>
              </div>
              <div style="text-align:center; min-width:50px;">
                <div style="font-size:22px; font-weight:700; color:#1d4ed8; line-height:1;">
                  <?= (int)($g['total_alumnos'] ?? 0) ?>
                </div>
                <div style="font-size:10px; color:#94a3b8;">alumnos</div>
              </div>
            </div>
            <div style="padding:8px 12px; background:#fff; border-top:1px solid #f1f5f9;
                        display:flex; gap:6px; flex-wrap:wrap;">
              <a href="index.php?accion=alumnos_grupo_docente&id_grupo=<?= (int)$g['id_grupo'] ?>"
                 class="btn btn-primary btn-sm">👥 Gestionar alumnos</a>
              <a href="index.php?accion=pasar_lista&id_grupo=<?= (int)$g['id_grupo'] ?>&fecha=<?= date('Y-m-d') ?>"
                 class="btn btn-outline btn-sm">✅ Pasar lista</a>
              <a href="index.php?accion=calendario_docente&id_grupo=<?= (int)$g['id_grupo'] ?>"
                 class="btn btn-outline btn-sm">📆 Calendario</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div style="text-align:center; padding:40px; color:#94a3b8;">
        <div style="font-size:40px; margin-bottom:12px;">🏫</div>
        <p style="font-size:14px;">Aún no tienes grupos creados.</p>
        <p style="font-size:12px; margin-top:4px;">Usa el formulario de la izquierda para crear uno.</p>
      </div>
    <?php endif; ?>
  </div>

</div>
<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
