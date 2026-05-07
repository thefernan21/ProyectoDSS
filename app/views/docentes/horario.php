<?php
$pageTitle    = 'Mi Horario';
$accionActual = 'horario_docente';
require_once BASE_PATH . '/app/views/layout/sidebar.php';

$DIAS_LABEL = [
  'lunes'     => 'Lunes',
  'martes'    => 'Martes',
  'miercoles' => 'Miércoles',
  'jueves'    => 'Jueves',
  'viernes'   => 'Viernes',
  'sabado'    => 'Sábado',
];

// Paleta de colores por grupo (cicla automáticamente)
$COLORES = [
  ['bg'=>'#eff6ff','border'=>'#3b82f6','text'=>'#1e40af'],
  ['bg'=>'#f0fdf4','border'=>'#10b981','text'=>'#166534'],
  ['bg'=>'#faf5ff','border'=>'#8b5cf6','text'=>'#5b21b6'],
  ['bg'=>'#fffbeb','border'=>'#f59e0b','text'=>'#92400e'],
  ['bg'=>'#fef2f2','border'=>'#ef4444','text'=>'#991b1b'],
  ['bg'=>'#f0fdfa','border'=>'#14b8a6','text'=>'#134e4a'],
];

// Agrupar horarios por grupo y asignar color
$grupoColores = [];
$colorIdx     = 0;
$porDia       = [];

foreach ($horarios as $h) {
  $key = $h['id_grupo'];
  if (!isset($grupoColores[$key])) {
    $grupoColores[$key] = $COLORES[$colorIdx % count($COLORES)];
    $colorIdx++;
  }
  $porDia[$h['dia']][] = $h;
}
?>

<style>
  .horario-grid {
    display: grid;
    grid-template-columns: 70px repeat(<?= count($DIAS_LABEL) ?>, 1fr);
    gap: 4px;
    font-size: 13px;
  }
  .dia-header {
    background: #0f1629;
    color: #fff;
    text-align: center;
    padding: 10px 6px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 12px;
  }
  .hora-label {
    color: #94a3b8;
    font-size: 11px;
    text-align: right;
    padding: 4px 8px 0 0;
    font-weight: 500;
  }
  .celda-dia {
    background: #f8fafc;
    border-radius: 8px;
    min-height: 52px;
    padding: 4px;
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
  .bloque-clase {
    border-radius: 6px;
    padding: 7px 10px;
    border-left: 3px solid;
    cursor: default;
    transition: transform .1s;
  }
  .bloque-clase:hover { transform: scale(1.01); }
  .bloque-materia  { font-weight: 600; font-size: 12px; line-height: 1.3; }
  .bloque-grupo    { font-size: 11px; margin-top: 2px; opacity: .8; }
  .bloque-hora     { font-size: 11px; margin-top: 3px; }
  .bloque-aula     { font-size: 10px; margin-top: 2px; opacity: .7; }
  .sin-clase       { color: #e2e8f0; font-size: 11px; text-align: center; padding-top: 16px; }

  /* Leyenda */
  .leyenda-chip {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; border-radius: 999px; font-size: 12px;
    border: 1px solid;
  }
  .leyenda-dot { width: 10px; height: 10px; border-radius: 50%; }
</style>

<?php if (empty($horarios)): ?>
  <div class="card">
    <p style="text-align:center; color:#94a3b8; padding:40px; font-size:14px;">
      No tienes horarios registrados aún.<br>
      <small>El administrador debe importar tu horario desde la sección "Importar Horario".</small>
    </p>
  </div>
<?php else: ?>

<!-- Leyenda de materias -->
<div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:20px;">
  <?php foreach ($grupoColores as $idGrupo => $color):
    // Buscar nombre de materia para este grupo
    $nombreMat = '';
    $nombreGrp = '';
    foreach ($horarios as $h) {
      if ($h['id_grupo'] == $idGrupo) {
        $nombreMat = $h['nombre_materia'];
        $nombreGrp = $h['nombre_grupo'];
        break;
      }
    }
  ?>
    <div class="leyenda-chip"
         style="background:<?= $color['bg'] ?>; border-color:<?= $color['border'] ?>; color:<?= $color['text'] ?>;">
      <span class="leyenda-dot" style="background:<?= $color['border'] ?>;"></span>
      <strong><?= htmlspecialchars($nombreGrp, ENT_QUOTES, 'UTF-8') ?></strong>
      — <?= htmlspecialchars($nombreMat, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endforeach; ?>
</div>

<!-- TABLA DE HORARIO -->
<div class="card" style="overflow-x:auto;">
  <div class="horario-grid">

    <!-- Encabezados -->
    <div></div>
    <?php foreach ($DIAS_LABEL as $diaKey => $diaLabel): ?>
      <div class="dia-header"><?= $diaLabel ?></div>
    <?php endforeach; ?>

    <!-- Filas de horario por hora -->
    <?php
    // Calcular horas únicas ordenadas
    $horasUnicas = [];
    foreach ($horarios as $h) $horasUnicas[$h['hora_inicio']] = true;
    ksort($horasUnicas);
    $horasUnicas = array_keys($horasUnicas);

    foreach ($horasUnicas as $hora): ?>
      <div class="hora-label"><?= $hora ?></div>
      <?php foreach (array_keys($DIAS_LABEL) as $diaKey): ?>
        <div class="celda-dia">
          <?php
          $clasesDia = array_filter($porDia[$diaKey] ?? [], fn($h) => $h['hora_inicio'] === $hora);
          if (empty($clasesDia)):
          ?>
            <div class="sin-clase">—</div>
          <?php else:
            foreach ($clasesDia as $h):
              $c = $grupoColores[$h['id_grupo']];
          ?>
            <div class="bloque-clase"
                 style="background:<?= $c['bg'] ?>; border-color:<?= $c['border'] ?>; color:<?= $c['text'] ?>;">
              <div class="bloque-materia"><?= htmlspecialchars($h['nombre_materia'], ENT_QUOTES, 'UTF-8') ?></div>
              <div class="bloque-grupo">Grupo: <?= htmlspecialchars($h['nombre_grupo'], ENT_QUOTES, 'UTF-8') ?></div>
              <div class="bloque-hora">⏰ <?= $h['hora_inicio'] ?>–<?= $h['hora_fin'] ?></div>
              <?php if ($h['aula']): ?>
                <div class="bloque-aula">🏛 <?= htmlspecialchars($h['aula'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </div>
</div>

<!-- Vista de lista compacta debajo -->
<div class="card" style="margin-top:20px;">
  <div class="card-header">
    <div class="card-title">Lista de clases</div>
    <div class="card-subtitle"><?= count($horarios) ?> sesiones por semana</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Día</th><th>Materia</th><th>Grupo</th>
          <th style="text-align:center;">Horario</th>
          <th style="text-align:center;">Aula</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($DIAS_LABEL as $diaKey => $diaLabel):
          $clasesDia = $porDia[$diaKey] ?? [];
          foreach ($clasesDia as $h):
            $c = $grupoColores[$h['id_grupo']];
        ?>
          <tr>
            <td style="font-weight:600;"><?= $diaLabel ?></td>
            <td><?= htmlspecialchars($h['nombre_materia'], ENT_QUOTES, 'UTF-8') ?>
              <div style="font-size:11px; color:#94a3b8;"><?= htmlspecialchars($h['clave_materia'], ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td><span class="badge badge-blue"><?= htmlspecialchars($h['nombre_grupo'], ENT_QUOTES, 'UTF-8') ?></span></td>
            <td style="text-align:center; font-weight:500;">
              <?= $h['hora_inicio'] ?> – <?= $h['hora_fin'] ?>
            </td>
            <td style="text-align:center;">
              <?= $h['aula'] ? '<span class="badge badge-gray">🏛 '.htmlspecialchars($h['aula'], ENT_QUOTES, 'UTF-8').'</span>' : '—' ?>
            </td>
          </tr>
        <?php endforeach; endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>
<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
