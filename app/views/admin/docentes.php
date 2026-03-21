<?php
$pageTitle    = 'Docentes';
$accionActual = 'ver_docentes';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<?php if (!empty($mensaje)): ?>
  <div class="alert alert-<?= $tipoMensaje ?? 'success' ?>"><?= $mensaje ?></div>
<?php endif; ?>

<div class="grid-2" style="align-items:start;">

  <!-- FORMULARIO REGISTRAR DOCENTE -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Registrar Docente</div>
        <div class="card-subtitle">Crea el perfil y la cuenta de acceso al mismo tiempo</div>
      </div>
    </div>

    <form action="index.php?accion=guardar_docente" method="POST">

      <div class="form-group">
        <label class="form-label">Número de empleado</label>
        <input type="text" name="numero_empleado" class="form-control"
               placeholder="Ej: E00123" required maxlength="20">
        <small style="color:#64748b; font-size:11px; margin-top:4px; display:block;">
          📌 Este será el <strong>usuario de acceso</strong> al sistema
        </small>
      </div>

      <div class="form-group">
        <label class="form-label">Nombre completo</label>
        <input type="text" name="nombre" class="form-control"
               placeholder="Ej: Ing. Juan Pérez López" required maxlength="100">
      </div>

      <div class="form-group">
        <label class="form-label">Correo institucional</label>
        <input type="email" name="correo" class="form-control"
               placeholder="Ej: jperez@itlac.edu.mx" required maxlength="100">
      </div>

      <div class="form-group">
        <label class="form-label">Contraseña de acceso</label>
        <input type="password" name="password" class="form-control"
               placeholder="Mínimo 8 caracteres" required minlength="8">
        <small style="color:#64748b; font-size:11px; margin-top:4px; display:block;">
          🔒 El docente deberá usar esta contraseña para ingresar
        </small>
      </div>

      <div class="form-group">
        <label class="form-label">Confirmar contraseña</label>
        <input type="password" name="password_confirm" class="form-control"
               placeholder="Repite la contraseña" required minlength="8">
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;">
        👨‍🏫 Registrar Docente y Crear Cuenta
      </button>
    </form>
  </div>

  <!-- LISTADO DE DOCENTES -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Docentes Registrados</div>
        <div class="card-subtitle"><?= count($docentes ?? []) ?> docentes en el sistema</div>
      </div>
    </div>

    <?php if (!empty($docentes)): ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>No. Empleado</th>
              <th>Nombre</th>
              <th>Correo</th>
              <th>Acceso</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($docentes as $d): ?>
              <tr>
                <td>
                  <span class="badge badge-blue">
                    <?= htmlspecialchars($d['numero_empleado'], ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td style="font-weight:500;">
                  <?= htmlspecialchars($d['nombre'], ENT_QUOTES, 'UTF-8') ?>
                </td>
                <td style="font-size:12px; color:#64748b;">
                  <?= htmlspecialchars($d['correo'], ENT_QUOTES, 'UTF-8') ?>
                </td>
                <td>
                  <?php if (!empty($d['id_usuario'])): ?>
                    <span class="badge badge-green">✓ Cuenta activa</span>
                  <?php else: ?>
                    <span class="badge badge-red">Sin cuenta</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="index.php?accion=eliminar_docente&id_docente=<?= (int)$d['id_docente'] ?>"
                     class="btn btn-sm" style="background:#fee2e2;color:#991b1b;"
                     onclick="return confirm('¿Eliminar este docente? Sus grupos serán desactivados.')">
                     🗑 Eliminar
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div style="text-align:center; padding:40px 0; color:#94a3b8;">
        <div style="font-size:48px; margin-bottom:12px;">👨‍🏫</div>
        <p style="font-size:14px;">No hay docentes registrados aún.</p>
        <p style="font-size:12px; margin-top:4px;">Usa el formulario de la izquierda para agregar el primero.</p>
      </div>
    <?php endif; ?>

    <!-- Leyenda de cómo accede el docente -->
    <div style="margin-top:20px; padding:14px; background:#eff6ff; border-radius:8px; border:1px solid #bfdbfe;">
      <p style="font-size:12px; color:#1e40af; font-weight:600; margin-bottom:6px;">🔑 ¿Cómo accede un docente?</p>
      <p style="font-size:12px; color:#1e40af; line-height:1.6;">
        El docente ingresa en la pantalla de login con:<br>
        <strong>Usuario:</strong> su número de empleado (Ej: E00123)<br>
        <strong>Contraseña:</strong> la que el admin definió al registrarlo
      </p>
    </div>
  </div>

</div>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
