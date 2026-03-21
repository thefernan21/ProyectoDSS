<?php
$pageTitle    = 'Sesión de Clase';
$accionActual = 'pasar_lista';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<?php if (!empty($mensaje)): ?>
  <div class="alert alert-<?= $tipoMensaje ?? 'success' ?>"><?= $mensaje ?></div>
<?php endif; ?>

<!-- Selector de grupo + fecha -->
<div class="card" style="margin-bottom:20px;">
  <div class="card-header">
    <div class="card-title">Seleccionar grupo y fecha</div>
  </div>
  <form action="index.php?accion=cargar_lista" method="GET"
        style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
    <input type="hidden" name="accion" value="cargar_lista">
    <div class="form-group" style="flex:1; min-width:220px; margin-bottom:0;">
      <label class="form-label">Grupo</label>
      <select name="id_grupo" class="form-control" required>
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
    <div class="form-group" style="flex:0 0 180px; margin-bottom:0;">
      <label class="form-label">Fecha</label>
      <input type="date" name="fecha" class="form-control"
             value="<?= htmlspecialchars($_GET['fecha'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
    </div>
    <button type="submit" class="btn btn-primary">Cargar →</button>
  </form>
</div>

<?php if (!empty($alumnos) && !empty($idGrupo)): ?>

<form action="index.php?accion=guardar_sesion" method="POST" id="formSesion">
  <input type="hidden" name="id_grupo"  value="<?= (int)$idGrupo ?>">
  <input type="hidden" name="id_unidad" value="<?= (int)($unidadActual['id_unidad'] ?? 0) ?>">
  <input type="hidden" name="fecha"     value="<?= htmlspecialchars($fecha ?? '', ENT_QUOTES, 'UTF-8') ?>">

  <!-- Info de sesión -->
  <div style="display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap;">
    <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px;
                padding:12px 18px; font-size:13px; color:#1e40af;">
      🏫 <strong><?= htmlspecialchars($nombreGrupo ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
    </div>
    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px;
                padding:12px 18px; font-size:13px; color:#166534;">
      📅 <strong><?= htmlspecialchars($fecha ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
    </div>
    <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:10px;
                padding:12px 18px; font-size:13px; color:#92400e;">
      📖 <strong><?= htmlspecialchars($unidadActual['nombre'] ?? 'Sin unidad', ENT_QUOTES, 'UTF-8') ?></strong>
    </div>
    <?php if (!empty($unidades)): ?>
    <div class="form-group" style="margin-bottom:0; min-width:200px;">
      <select name="id_unidad" class="form-control" style="height:42px;" onchange="document.getElementById('formSesion').submit()">
        <?php foreach ($unidades as $u): ?>
          <option value="<?= (int)$u['id_unidad'] ?>"
            <?= ($u['id_unidad'] == ($unidadActual['id_unidad'] ?? 0)) ? 'selected' : '' ?>>
            <?= htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
  </div>

  <!-- ══ SECCIÓN 1: ASISTENCIA ══════════════════════════════ -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">
      <div>
        <div class="card-title">✅ Asistencia</div>
        <div class="card-subtitle"><?= count($alumnos) ?> alumnos · Solo el docente puede ver este resumen</div>
      </div>
      <div style="display:flex; gap:8px;">
        <button type="button" onclick="marcarTodos('asistencia')"  class="btn btn-success btn-sm">Todos presentes</button>
        <button type="button" onclick="marcarTodos('inasistencia')" class="btn btn-outline btn-sm">Todos ausentes</button>
      </div>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th><th>No. Control</th><th>Alumno</th>
            <th style="text-align:center;color:#10b981;">✅ Asistencia<br><small style="color:#94a3b8;">(1.0)</small></th>
            <th style="text-align:center;color:#f59e0b;">⏰ Retardo<br><small style="color:#94a3b8;">(0.8)</small></th>
            <th style="text-align:center;color:#ef4444;">❌ Inasistencia<br><small style="color:#94a3b8;">(0.0)</small></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($alumnos as $i => $al): ?>
            <?php $idA = (int)$al['id_alumno']; $regHoy = $asistenciasHoy[$idA] ?? 'asistencia'; ?>
            <tr id="fila-<?= $idA ?>">
              <td style="color:#94a3b8;font-size:12px;"><?= $i+1 ?></td>
              <td><span class="badge badge-blue"><?= htmlspecialchars($al['numero_control'], ENT_QUOTES, 'UTF-8') ?></span></td>
              <td style="font-weight:500;"><?= htmlspecialchars($al['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
              <?php foreach (['asistencia','retardo','inasistencia'] as $tipo): ?>
                <td style="text-align:center;">
                  <input type="radio" name="asistencia[<?= $idA ?>]" value="<?= $tipo ?>"
                    class="radio-asist" data-alumno="<?= $idA ?>" data-tipo="<?= $tipo ?>"
                    <?= $regHoy === $tipo ? 'checked' : '' ?>
                    style="width:18px;height:18px;cursor:pointer;accent-color:<?=
                      $tipo==='asistencia'?'#10b981':($tipo==='retardo'?'#f59e0b':'#ef4444') ?>;">
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ══ SECCIÓN 2: ACTIVIDAD EN CLASE ══════════════════════ -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">
      <div>
        <div class="card-title">📝 Actividad en Clase <span style="font-size:11px;font-weight:400;color:#94a3b8;">(opcional)</span></div>
        <div class="card-subtitle">Si no hubo actividad hoy, deja el nombre vacío y se omitirá al guardar</div>
      </div>
    </div>
    <?php if (!$unidadActual || !($unidadActual['id_unidad'] ?? 0)): ?>
      <div class="alert alert-warning" style="margin-bottom:0;">
        ⚠️ Selecciona una <strong>unidad</strong> arriba para poder registrar actividades y tareas.
        La asistencia se guardará de todas formas.
      </div>
    <?php else: ?>
    <div class="form-group" style="max-width:500px;">
      <label class="form-label">Nombre de la actividad</label>
      <input type="text" name="actividad_nombre" class="form-control"
             placeholder="Ej: Práctica 1 — Arreglos en C++"
             value="<?= htmlspecialchars($actividadDia['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>#</th><th>No. Control</th><th>Alumno</th>
              <th style="text-align:center;">Calificación <small style="color:#94a3b8;">(0–10)</small></th></tr>
        </thead>
        <tbody>
          <?php foreach ($alumnos as $i => $al): ?>
            <?php $idA = (int)$al['id_alumno']; ?>
            <tr>
              <td style="color:#94a3b8;font-size:12px;"><?= $i+1 ?></td>
              <td><span class="badge badge-blue"><?= htmlspecialchars($al['numero_control'], ENT_QUOTES, 'UTF-8') ?></span></td>
              <td style="font-weight:500;"><?= htmlspecialchars($al['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
              <td style="text-align:center; width:120px;">
                <input type="number" name="actividad_calif[<?= $idA ?>]"
                       min="0" max="10" step="0.1"
                       value="<?= $califActividadHoy[$idA] ?? '' ?>"
                       class="form-control" style="text-align:center; max-width:90px; margin:auto;"
                       placeholder="—">
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

    <?php endif; ?>
  </div>

  <!-- ══ SECCIÓN 3: TAREA ════════════════════════════════════ -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">
      <div>
        <div class="card-title">📚 Tarea <span style="font-size:11px;font-weight:400;color:#94a3b8;">(opcional)</span></div>
        <div class="card-subtitle">Si no se revisó tarea hoy, deja el nombre vacío y se omitirá al guardar</div>
      </div>
    </div>
    <?php if ($unidadActual && ($unidadActual['id_unidad'] ?? 0)): ?>
    <div class="form-group" style="max-width:500px;">
      <label class="form-label">Nombre de la tarea</label>
      <input type="text" name="tarea_nombre" class="form-control"
             placeholder="Ej: Tarea 2 — Funciones recursivas"
             value="<?= htmlspecialchars($tareaDia['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>#</th><th>No. Control</th><th>Alumno</th>
              <th style="text-align:center;">Calificación <small style="color:#94a3b8;">(0–10)</small></th></tr>
        </thead>
        <tbody>
          <?php foreach ($alumnos as $i => $al): ?>
            <?php $idA = (int)$al['id_alumno']; ?>
            <tr>
              <td style="color:#94a3b8;font-size:12px;"><?= $i+1 ?></td>
              <td><span class="badge badge-blue"><?= htmlspecialchars($al['numero_control'], ENT_QUOTES, 'UTF-8') ?></span></td>
              <td style="font-weight:500;"><?= htmlspecialchars($al['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
              <td style="text-align:center; width:120px;">
                <input type="number" name="tarea_calif[<?= $idA ?>]"
                       min="0" max="10" step="0.1"
                       value="<?= $califTareaHoy[$idA] ?? '' ?>"
                       class="form-control" style="text-align:center; max-width:90px; margin:auto;"
                       placeholder="—">
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

    <?php endif; ?>
  </div>

  <div style="display:flex; justify-content:flex-end; gap:10px;">
    <a href="index.php?accion=historial_grupo&id_grupo=<?= (int)$idGrupo ?>" class="btn btn-outline">
      📋 Ver historial
    </a>
    <button type="submit" class="btn btn-success" style="padding:10px 28px; font-size:15px;">
      💾 Guardar sesión completa
    </button>
  </div>
</form>

<script>
document.querySelectorAll('.radio-asist').forEach(r => {
  r.addEventListener('change', function() {
    const fila = document.getElementById('fila-' + this.dataset.alumno);
    fila.style.background = '';
    if (this.dataset.tipo === 'asistencia')  fila.style.background = '#f0fdf4';
    if (this.dataset.tipo === 'retardo')     fila.style.background = '#fffbeb';
    if (this.dataset.tipo === 'inasistencia')fila.style.background = '#fef2f2';
  });
});
function marcarTodos(tipo) {
  document.querySelectorAll('.radio-asist[data-tipo="'+tipo+'"]').forEach(r => {
    r.checked = true; r.dispatchEvent(new Event('change'));
  });
}
document.querySelectorAll('.radio-asist:checked').forEach(r => r.dispatchEvent(new Event('change')));
</script>

<?php elseif (isset($_GET['id_grupo'])): ?>
<div class="card">
  <p style="text-align:center;color:#94a3b8;padding:40px;font-size:14px;">
    No hay alumnos inscritos en este grupo aún.
  </p>
</div>
<?php endif; ?>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
