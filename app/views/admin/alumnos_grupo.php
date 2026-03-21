<?php
$pageTitle    = 'Alumnos de ' . htmlspecialchars($grupo['nombre_grupo'] ?? '', ENT_QUOTES, 'UTF-8');
$accionActual = 'ver_grupos';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<div style="margin-bottom:20px; display:flex; align-items:center; justify-content:space-between;">
  <a href="index.php?accion=ver_grupos"
     style="color:#64748b; font-size:13px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
    ← Volver a Grupos
  </a>
  <a href="index.php?accion=cargar_lista_grupo&id_grupo=<?= (int)($grupo['id_grupo'] ?? 0) ?>"
     class="btn btn-primary btn-sm">
    📂 Cargar más alumnos
  </a>
</div>

<!-- Info del grupo -->
<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px;
            padding:16px 20px; margin-bottom:20px;
            display:flex; align-items:center; gap:16px;">
  <div style="font-size:32px;">🏫</div>
  <div style="flex:1;">
    <div style="font-weight:700; font-size:16px;">
      <?= htmlspecialchars($grupo['nombre_grupo'] ?? '', ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div style="font-size:12px; color:#64748b; margin-top:2px;">
      📚 <?= htmlspecialchars($grupo['nombre_materia'] ?? '', ENT_QUOTES, 'UTF-8') ?>
      &nbsp;·&nbsp; 👨‍🏫 <?= htmlspecialchars($grupo['nombre_docente'] ?? '', ENT_QUOTES, 'UTF-8') ?>
      &nbsp;·&nbsp; 🗓 <?= htmlspecialchars($grupo['periodo'] ?? '', ENT_QUOTES, 'UTF-8') ?>
    </div>
  </div>
  <div style="text-align:center;">
    <div style="font-size:28px; font-weight:700; color:#1d4ed8;"><?= count($alumnosInscritos ?? []) ?></div>
    <div style="font-size:11px; color:#94a3b8;">alumnos</div>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>No. Control</th>
          <th>Nombre completo</th>
          <th>Correo</th>
          <th>Cuenta</th>
          <th>Fecha inscripción</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($alumnosInscritos)): ?>
          <?php foreach ($alumnosInscritos as $i => $a): ?>
            <tr>
              <td style="color:#94a3b8; font-size:12px;"><?= $i + 1 ?></td>
              <td><span class="badge badge-blue"><?= htmlspecialchars($a['numero_control'], ENT_QUOTES, 'UTF-8') ?></span></td>
              <td style="font-weight:500;"><?= htmlspecialchars($a['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
              <td style="font-size:12px; color:#64748b;"><?= htmlspecialchars($a['correo'], ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <?php if (!empty($a['id_usuario'])): ?>
                  <span class="badge badge-green">✓ Activa</span>
                <?php else: ?>
                  <span class="badge badge-red">Sin cuenta</span>
                <?php endif; ?>
              </td>
              <td style="font-size:12px; color:#64748b;"><?= htmlspecialchars($a['fecha_inscripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align:center; color:#94a3b8; padding:40px;">
              Sin alumnos inscritos.
              <a href="index.php?accion=cargar_lista_grupo&id_grupo=<?= (int)($grupo['id_grupo'] ?? 0) ?>"
                 style="color:#3b82f6; margin-left:4px;">Cargar lista CSV →</a>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
