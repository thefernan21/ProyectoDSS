<?php
$pageTitle    = 'Directorio de Alumnos';
$accionActual = 'ver_alumnos';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<div class="card">
  <div class="card-header">
    <div>
      <div class="card-title">Alumnos Registrados</div>
      <div class="card-subtitle"><?= count($alumnos ?? []) ?> alumnos en el sistema</div>
    </div>
    <a href="index.php?accion=importar_view" class="btn btn-primary btn-sm">📂 Importar más</a>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>No. Control</th>
          <th>Nombre completo</th>
          <th>Correo institucional</th>
          <th>Cuenta</th>
              <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($alumnos)): ?>
          <?php foreach ($alumnos as $i => $alumno): ?>
            <tr>
              <td style="color:#94a3b8; font-size:12px;"><?= $i + 1 ?></td>
              <td>
                <span class="badge badge-blue">
                  <?= htmlspecialchars($alumno['numero_control'], ENT_QUOTES, 'UTF-8') ?>
                </span>
              </td>
              <td style="font-weight:500;">
                <?= htmlspecialchars($alumno['nombre'], ENT_QUOTES, 'UTF-8') ?>
              </td>
              <td style="color:#64748b; font-size:13px;">
                <?= htmlspecialchars($alumno['correo'], ENT_QUOTES, 'UTF-8') ?>
              </td>
              <td>
                <?php if (!empty($alumno['id_usuario'])): ?>
                  <span class="badge badge-green">✓ Activo</span>
                <?php else: ?>
                  <span class="badge badge-gray">Sin cuenta</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="index.php?accion=eliminar_alumno&id_alumno=<?= (int)$alumno['id_alumno'] ?>"
                   class="btn btn-sm" style="background:#fee2e2;color:#991b1b;"
                   onclick="return confirm('¿Eliminar este alumno y todos sus registros? Esta acción no se puede deshacer.')">
                   🗑 Eliminar
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align:center; color:#94a3b8; padding:40px;">
              No hay alumnos registrados. <a href="index.php?accion=ver_grupos" style="color:#3b82f6;">Ir a Grupos →</a>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
