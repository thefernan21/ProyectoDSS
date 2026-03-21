<?php
$pageTitle    = 'Grupos';
$accionActual = 'ver_grupos';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<?php if (!empty($mensaje)): ?>
  <div class="alert alert-<?= $tipoMensaje ?? 'success' ?>"><?= $mensaje ?></div>
<?php endif; ?>

<div class="grid-2" style="align-items:start;">

  <!-- FORMULARIO NUEVO GRUPO -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Nuevo Grupo</div>
        <div class="card-subtitle">Paso 1 — Define el grupo antes de cargar alumnos</div>
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
        <div style="color:#94a3b8; font-size:10px;">Cargar lista CSV</div>
      </div>
    </div>

    <form action="index.php?accion=guardar_grupo" method="POST">
      <div class="form-group">
        <label class="form-label">Materia</label>
        <select name="id_materia" class="form-control" required>
          <option value="">— Selecciona materia —</option>
          <?php foreach ($materias ?? [] as $m): ?>
            <option value="<?= (int)$m['id_materia'] ?>">
              [<?= htmlspecialchars($m['clave_materia'], ENT_QUOTES, 'UTF-8') ?>]
              <?= htmlspecialchars($m['nombre'], ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Docente</label>
        <select name="id_docente" class="form-control" required>
          <option value="">— Selecciona docente —</option>
          <?php foreach ($docentes ?? [] as $d): ?>
            <option value="<?= (int)$d['id_docente'] ?>">
              <?= htmlspecialchars($d['nombre'], ENT_QUOTES, 'UTF-8') ?>
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
        <small style="color:#64748b;font-size:11px;margin-top:4px;display:block;">
          El sistema creará automáticamente las unidades al guardar
        </small>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;">
        ➕ Crear Grupo → luego cargar lista
      </button>
    </form>
  </div>

  <!-- LISTADO DE GRUPOS -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Grupos Activos</div>
        <div class="card-subtitle"><?= count($grupos ?? []) ?> grupos registrados</div>
      </div>
    </div>

    <?php if (!empty($grupos)): ?>
      <div style="display:flex; flex-direction:column; gap:12px;">
        <?php foreach ($grupos as $g): ?>
          <div style="border:1px solid #e2e8f0; border-radius:10px; overflow:hidden;">

            <!-- Cabecera del grupo -->
            <div style="display:flex; align-items:center; justify-content:space-between;
                        padding:12px 16px; background:#f8fafc;">
              <div>
                <div style="font-weight:600; font-size:14px;">
                  <?= htmlspecialchars($g['nombre_grupo'], ENT_QUOTES, 'UTF-8') ?>
                  <span class="badge badge-gray" style="margin-left:6px; font-size:10px;">
                    <?= htmlspecialchars($g['periodo'], ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </div>
                <div style="font-size:12px; color:#64748b; margin-top:2px;">
                  📚 <?= htmlspecialchars($g['nombre_materia'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                  &nbsp;·&nbsp;
                  👨‍🏫 <?= htmlspecialchars($g['nombre_docente'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </div>
              </div>

              <!-- Contador de alumnos inscritos -->
              <div style="text-align:center; min-width:60px;">
                <div style="font-size:22px; font-weight:700; color:#1d4ed8; line-height:1;">
                  <?= (int)($g['total_alumnos'] ?? 0) ?>
                </div>
                <div style="font-size:10px; color:#94a3b8;">alumnos</div>
              </div>
            </div>

            <!-- Acciones del grupo -->
            <div style="padding:10px 16px; display:flex; gap:8px; background:#fff; border-top:1px solid #f1f5f9;">

              <!-- BOTÓN PRINCIPAL: Cargar lista CSV -->
              <a href="index.php?accion=cargar_lista_grupo&id_grupo=<?= (int)$g['id_grupo'] ?>"
                 class="btn btn-primary btn-sm">
                📂 Cargar lista CSV
              </a>

              <!-- Ver alumnos del grupo -->
              <a href="index.php?accion=ver_alumnos_grupo&id_grupo=<?= (int)$g['id_grupo'] ?>"
                 class="btn btn-outline btn-sm">
                👥 Ver alumnos
              </a>
              <a href="index.php?accion=eliminar_grupo&id_grupo=<?= (int)$g['id_grupo'] ?>"
                 class="btn btn-sm" style="background:#fee2e2;color:#991b1b;"
                 onclick="return confirm('¿Eliminar este grupo y TODOS sus registros? Esta acción no se puede deshacer.')">
                🗑 Eliminar
              </a>

            </div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <div style="text-align:center; padding:40px 0; color:#94a3b8;">
        <div style="font-size:48px; margin-bottom:12px;">🏫</div>
        <p style="font-size:14px;">No hay grupos registrados aún.</p>
        <p style="font-size:12px; margin-top:4px;">Crea el primer grupo con el formulario de la izquierda.</p>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
