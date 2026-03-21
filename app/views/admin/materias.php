<?php
$pageTitle    = 'Materias';
$accionActual = 'ver_materias';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<?php if (!empty($mensaje)): ?>
  <div class="alert alert-<?= $tipoMensaje ?? 'success' ?>"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="grid-2" style="align-items:start;">

  <!-- FORMULARIO NUEVA MATERIA -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Nueva Materia</div>
        <div class="card-subtitle">Agregar al catálogo</div>
      </div>
    </div>
    <form action="index.php?accion=guardar_materia" method="POST">
      <div class="form-group">
        <label class="form-label">Clave institucional</label>
        <input type="text" name="clave_materia" class="form-control" placeholder="Ej: SCD-1001" required maxlength="20">
      </div>
      <div class="form-group">
        <label class="form-label">Nombre de la materia</label>
        <input type="text" name="nombre" class="form-control" placeholder="Ej: Desarrollo de Software Seguro" required maxlength="150">
      </div>
      <div class="form-group">
        <label class="form-label">Créditos</label>
        <input type="number" name="creditos" class="form-control" value="5" min="1" max="20">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;">➕ Guardar Materia</button>
    </form>
  </div>

  <!-- LISTADO DE MATERIAS -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Catálogo de Materias</div>
        <div class="card-subtitle"><?= count($materias ?? []) ?> materias registradas</div>
      </div>
    </div>
    <?php if (!empty($materias)): ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Clave</th>
              <th>Nombre</th>
              <th>Créd.</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($materias as $m): ?>
              <tr>
                <td><span class="badge badge-blue"><?= htmlspecialchars($m['clave_materia'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td style="font-weight:500;"><?= htmlspecialchars($m['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                <td style="text-align:center;"><?= (int)$m['creditos'] ?></td>
                <td>
                  <?php if ($m['activa']): ?>
                    <span class="badge badge-green">Activa</span>
                  <?php else: ?>
                    <span class="badge badge-gray">Inactiva</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p style="color:#94a3b8; font-size:13px; text-align:center; padding:20px 0;">No hay materias registradas aún.</p>
    <?php endif; ?>
  </div>

</div>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
