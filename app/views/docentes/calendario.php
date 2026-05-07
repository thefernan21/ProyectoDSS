<?php
$pageTitle    = 'Calendario de Asistencias';
$accionActual = 'calendario_docente';
require_once BASE_PATH . '/app/views/layout/sidebar.php';

$hoy = date('Y-m-d');
?>

<style>
/* ── Filtro superior ───────────────────────────────── */
.filtro-bar {
  display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;
}

/* ── Tabla de registro tipo hoja escolar ───────────── */
.registro-wrap {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid var(--border);
}
.tabla-registro {
  border-collapse: collapse;
  font-size: 12px;
  white-space: nowrap;
  min-width: 100%;
}

/* Cabecera fija (nombre alumno) */
.col-fija {
  position: sticky; left: 0; z-index: 3;
  background: #0f1629;
  color: #fff; font-size: 12px;
}
.col-fija-body {
  position: sticky; left: 0; z-index: 2;
  background: #fff;
  font-weight: 500; font-size: 12px;
  max-width: 200px; overflow: hidden; text-overflow: ellipsis;
}
tr:hover .col-fija-body { background: #f8fafc; }

/* Cabeceras de fecha */
.th-fecha {
  text-align: center; padding: 6px 4px;
  border-bottom: 2px solid var(--border);
  min-width: 44px; max-width: 48px;
  border-right: 1px solid #f1f5f9;
}
.th-dia-label {
  font-size: 9px; font-weight: 600;
  text-transform: uppercase; letter-spacing: .04em;
}
.th-fecha-num {
  font-size: 11px; font-weight: 700; margin-top: 2px;
}
.th-mes { font-size: 9px; color: #94a3b8; }

/* Encabezado de unidad */
.th-unidad {
  background: linear-gradient(135deg, #6d28d9, #4f46e5);
  color: #fff;
  text-align: center;
  padding: 5px 8px;
  font-size: 11px; font-weight: 700;
  letter-spacing: .03em;
  border-right: 2px solid #4f46e5;
}
.th-unidad-vacio { background: #0f1629; }

/* Celdas de asistencia */
.celda {
  text-align: center; padding: 5px 3px;
  border-bottom: 1px solid #f1f5f9;
  border-right: 1px solid #f1f5f9;
  cursor: default;
}
.celda-A  { background: #dcfce7; color: #166534; font-weight: 700; font-size: 13px; }
.celda-R  { background: #fef9c3; color: #854d0e; font-weight: 700; font-size: 13px; }
.celda-F  { background: #fee2e2; color: #991b1b; font-weight: 700; font-size: 13px; }
.celda-fut { background: #f8fafc; color: #cbd5e1; font-size: 11px; }
.celda-hoy { background: #dbeafe; color: #1e40af; font-weight: 700; font-size: 11px; }
.celda-fest { background: #fce7f3; color: #9d174d; font-size: 10px; line-height: 1.2; }

/* Columna de borde de unidad */
.borde-unidad { border-right: 3px solid #6d28d9 !important; }

/* Columna de asistencias totales */
.col-total { background: #f8fafc; font-weight: 700; text-align: center; padding: 5px 8px; font-size: 12px; min-width: 60px; }
.col-total-header { background: #1e293b; color: #fff; padding: 6px 8px; font-size: 10px; text-align: center; font-weight: 600; }

/* Encabezados de fila mes */
.th-mes-row {
  background: #334155; color: #94a3b8;
  text-align: center; padding: 4px 8px;
  font-size: 10px; font-weight: 600; text-transform: uppercase;
  border-right: 1px solid #475569;
}

/* Leyenda */
.leyenda-item {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 11px; color: #475569;
  padding: 4px 10px; border-radius: 999px;
}
.leyenda-dot {
  width: 14px; height: 14px; border-radius: 4px;
  display: flex; align-items: center; justify-content: center;
  font-size: 9px; font-weight: 700;
}

/* Fila de encabezado mes */
.fila-mes td { padding: 0 !important; }
</style>

<!-- ── FILTRO ──────────────────────────────────────── -->
<div class="card" style="margin-bottom:20px;">
  <form action="index.php?accion=calendario_docente" method="GET"
        class="filtro-bar">
    <input type="hidden" name="accion" value="calendario_docente">
    <div class="form-group" style="flex:1; min-width:220px; margin-bottom:0;">
      <label class="form-label">Grupo</label>
      <select name="id_grupo" class="form-control" onchange="this.form.submit()">
        <option value="">— Selecciona grupo —</option>
        <?php foreach ($misGrupos ?? [] as $g): ?>
          <option value="<?= (int)$g['id_grupo'] ?>"
            <?= (isset($_GET['id_grupo']) && $_GET['id_grupo']==$g['id_grupo'])?'selected':'' ?>>
            <?= htmlspecialchars($g['nombre_grupo'],ENT_QUOTES,'UTF-8') ?> —
            <?= htmlspecialchars($g['nombre_materia'],ENT_QUOTES,'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="flex:0 0 150px; margin-bottom:0;">
      <label class="form-label">Desde</label>
      <input type="date" name="desde" class="form-control"
             value="<?= htmlspecialchars($_GET['desde'] ?? date('Y-01-01'), ENT_QUOTES,'UTF-8') ?>">
    </div>
    <div class="form-group" style="flex:0 0 150px; margin-bottom:0;">
      <label class="form-label">Hasta</label>
      <input type="date" name="hasta" class="form-control"
             value="<?= htmlspecialchars($_GET['hasta'] ?? date('Y-07-31'), ENT_QUOTES,'UTF-8') ?>">
    </div>
    <button type="submit" class="btn btn-primary" style="margin-bottom:0;">Cargar →</button>
  </form>
</div>

<?php if (!empty($fechasClase) && !empty($alumnos)): ?>

<!-- ── LEYENDA ─────────────────────────────────────── -->
<div style="display:flex; flex-wrap:wrap; gap:4px; margin-bottom:14px; align-items:center;">
  <div class="leyenda-item"><div class="leyenda-dot celda-A">A</div> Asistencia</div>
  <div class="leyenda-item"><div class="leyenda-dot celda-R">R</div> Retardo</div>
  <div class="leyenda-item"><div class="leyenda-dot celda-F">F</div> Falta</div>
  <div class="leyenda-item"><div class="leyenda-dot celda-hoy" style="font-size:8px;">HOY</div> Hoy</div>
  <div class="leyenda-item"><div class="leyenda-dot celda-fut">—</div> Sin registro</div>
  <div class="leyenda-item"><div class="leyenda-dot celda-fest" style="width:18px;">🎌</div> Festivo</div>
  <div style="margin-left:auto; font-size:12px; color:#64748b;">
    📅 <?= count($fechasClase) ?> sesiones · <?= count($alumnos) ?> alumnos
  </div>
</div>

<!-- ── TABLA REGISTRO ──────────────────────────────── -->
<?php
// Agrupar fechas por mes para el encabezado
$porMes   = [];
$mesesES  = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
             'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$diasES   = ['','Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];

foreach ($fechasClase as $fc) {
    $mes = date('Y-m', strtotime($fc['fecha']));
    $porMes[$mes][] = $fc;
}

// Calcular a qué unidad pertenece cada fecha
function getUnidad(string $fecha, array $unidades): ?array {
    $prev = null;
    foreach ($unidades as $u) {
        if (!$u['fecha_fin']) { $prev = $u; continue; }
        if ($fecha <= $u['fecha_fin']) return $u;
        $prev = $u;
    }
    return $prev;
}

// Calcular porcentaje asistencia por alumno
function calcPct(int $idAlumno, array $asistencias, array $fechasClase): array {
    $total = 0; $asist = 0; $ret = 0; $falta = 0;
    foreach ($fechasClase as $fc) {
        if ($fc['festivo']) continue; // Festivos no cuentan
        $t = $asistencias[$idAlumno][$fc['fecha']] ?? null;
        if ($t === null) continue; // Futuras no cuentan
        $total++;
        if ($t === 'asistencia')  $asist++;
        if ($t === 'retardo')     $ret++;
        if ($t === 'inasistencia') $falta++;
    }
    $pct = $total > 0 ? round(($asist + $ret * 0.8) / $total * 100, 1) : null;
    return ['total'=>$total,'asist'=>$asist,'ret'=>$ret,'falta'=>$falta,'pct'=>$pct];
}
?>

<div class="registro-wrap">
<table class="tabla-registro">
  <thead>

    <!-- FILA 1: Nombre grupo + encabezados de mes -->
    <tr>
      <th class="col-fija" rowspan="3"
          style="padding:8px 14px; font-size:13px; min-width:180px; border-right:2px solid #334155;">
        <div style="font-size:14px; font-weight:700;"><?= htmlspecialchars($grupoInfo['nombre_grupo'],ENT_QUOTES,'UTF-8') ?></div>
        <div style="font-size:11px; color:rgba(255,255,255,.5); margin-top:2px;"><?= htmlspecialchars($grupoInfo['nombre_materia'],ENT_QUOTES,'UTF-8') ?></div>
        <div style="font-size:10px; color:rgba(255,255,255,.35);"><?= htmlspecialchars($grupoInfo['nombre_docente'],ENT_QUOTES,'UTF-8') ?></div>
      </th>
      <?php foreach ($porMes as $mes => $fcsMes):
        $mesLabel = $mesesES[(int)date('m', strtotime($mes.'-01'))];
      ?>
        <th colspan="<?= count($fcsMes) ?>" class="th-mes-row"><?= $mesLabel ?></th>
      <?php endforeach; ?>
      <th class="col-total-header" rowspan="3">%<br>Asist.</th>
      <th class="col-total-header" rowspan="3">A</th>
      <th class="col-total-header" rowspan="3">R</th>
      <th class="col-total-header" rowspan="3">F</th>
    </tr>

    <!-- FILA 2: Encabezado de unidades -->
    <tr>
      <?php
      $unidadActual = null;
      $contadorUnidad = 0;
      $bufferUnidad   = [];
      // Acumular fechas por unidad
      foreach ($fechasClase as $idx => $fc) {
          $u = getUnidad($fc['fecha'], $unidades);
          $uid = $u ? $u['id_unidad'] : 0;
          $bufferUnidad[$uid]['u']    = $u;
          $bufferUnidad[$uid]['cols'] = ($bufferUnidad[$uid]['cols'] ?? 0) + 1;
          $bufferUnidad[$uid]['last'] = $idx;
      }
      foreach ($bufferUnidad as $uid => $bu):
          $u = $bu['u'];
      ?>
        <th colspan="<?= $bu['cols'] ?>" class="th-unidad">
          <?= $u ? htmlspecialchars($u['nombre'], ENT_QUOTES,'UTF-8') : 'Sin unidad' ?>
          <?php if ($u && $u['fecha_fin']): ?>
            <div style="font-size:9px; font-weight:400; opacity:.7; margin-top:1px;">
              hasta <?= $u['fecha_fin'] ?>
            </div>
          <?php endif; ?>
        </th>
      <?php endforeach; ?>
    </tr>

    <!-- FILA 3: Fechas individuales -->
    <tr>
      <?php
      $unidadAnterior = null;
      foreach ($fechasClase as $idx => $fc):
          $fecha    = $fc['fecha'];
          $festivo  = $fc['festivo'];
          $u        = getUnidad($fecha, $unidades);
          $uid      = $u ? $u['id_unidad'] : 0;
          // Detectar si es la última fecha de esta unidad (para borde)
          $siguienteUid = null;
          if (isset($fechasClase[$idx+1])) {
              $uSig = getUnidad($fechasClase[$idx+1]['fecha'], $unidades);
              $siguienteUid = $uSig ? $uSig['id_unidad'] : 0;
          }
          $esBordeUnidad = ($uid !== $siguienteUid);
          $claseExtra    = $esBordeUnidad ? 'borde-unidad' : '';

          $dt      = new DateTime($fecha);
          $diaN    = $diasES[(int)$dt->format('N')];
          $diaNum  = $dt->format('d');
          $mesAb   = strtoupper(substr($mesesES[(int)$dt->format('m')], 0, 3));
          $esHoy   = ($fecha === $hoy);
          $esFutura = ($fecha > $hoy);

          if ($festivo):
      ?>
        <th class="th-fecha <?= $claseExtra ?>"
            style="background:#fce7f3; color:#9d174d;"
            title="🎌 <?= htmlspecialchars($festivo,ENT_QUOTES,'UTF-8') ?>">
          <div class="th-dia-label"><?= $diaN ?></div>
          <div class="th-fecha-num"><?= $diaNum ?></div>
          <div class="th-mes">🎌</div>
        </th>
      <?php else: ?>
        <th class="th-fecha <?= $claseExtra ?>"
            style="<?= $esHoy ? 'background:#dbeafe; color:#1e40af;' : '' ?>
                   <?= $esFutura ? 'color:#94a3b8;' : '' ?>">
          <div class="th-dia-label"><?= $diaN ?></div>
          <div class="th-fecha-num"><?= $diaNum ?></div>
          <div class="th-mes"><?= $mesAb ?></div>
        </th>
      <?php endif; endforeach; ?>
    </tr>

  </thead>
  <tbody>
    <?php foreach ($alumnos as $al):
      $idA  = (int)$al['id_alumno'];
      $stat = calcPct($idA, $asistencias, $fechasClase);
      $pctColor = ($stat['pct'] === null) ? '#94a3b8'
                : (($stat['pct'] >= 80) ? '#166534' : (($stat['pct'] >= 70) ? '#854d0e' : '#991b1b'));
      $pctBg    = ($stat['pct'] === null) ? '#f1f5f9'
                : (($stat['pct'] >= 80) ? '#dcfce7' : (($stat['pct'] >= 70) ? '#fef9c3' : '#fee2e2'));
    ?>
    <tr>
      <td class="col-fija-body" style="padding:7px 12px; border-bottom:1px solid #f1f5f9;
                                        border-right:2px solid #e2e8f0;">
        <div><?= htmlspecialchars($al['nombre'],ENT_QUOTES,'UTF-8') ?></div>
        <div style="font-size:10px; color:#94a3b8;"><?= htmlspecialchars($al['numero_control'],ENT_QUOTES,'UTF-8') ?></div>
      </td>

      <?php foreach ($fechasClase as $idx => $fc):
        $fecha   = $fc['fecha'];
        $festivo = $fc['festivo'];
        $u       = getUnidad($fecha, $unidades);
        $uid     = $u ? $u['id_unidad'] : 0;
        $siguienteUid = null;
        if (isset($fechasClase[$idx+1])) {
            $uSig = getUnidad($fechasClase[$idx+1]['fecha'], $unidades);
            $siguienteUid = $uSig ? $uSig['id_unidad'] : 0;
        }
        $esBordeUnidad = ($uid !== $siguienteUid);
        $claseExtra    = $esBordeUnidad ? 'borde-unidad' : '';
        $esHoy    = ($fecha === $hoy);
        $esFutura = ($fecha > $hoy);

        if ($festivo): ?>
          <td class="celda celda-fest <?= $claseExtra ?>" title="<?= htmlspecialchars($festivo,ENT_QUOTES,'UTF-8') ?>">🎌</td>
        <?php elseif ($esHoy): ?>
          <td class="celda celda-hoy <?= $claseExtra ?>">HOY</td>
        <?php else:
          $tipo = $asistencias[$idA][$fecha] ?? null;
          if ($tipo === 'asistencia'): ?>
            <td class="celda celda-A <?= $claseExtra ?>" title="Asistencia">A</td>
          <?php elseif ($tipo === 'retardo'): ?>
            <td class="celda celda-R <?= $claseExtra ?>" title="Retardo">R</td>
          <?php elseif ($tipo === 'inasistencia'): ?>
            <td class="celda celda-F <?= $claseExtra ?>" title="Falta">F</td>
          <?php elseif ($esFutura): ?>
            <td class="celda celda-fut <?= $claseExtra ?>">·</td>
          <?php else: ?>
            <td class="celda celda-fut <?= $claseExtra ?>" style="color:#fbbf24; font-size:10px;"
                title="Sin registro">?</td>
          <?php endif;
        endif; ?>
      <?php endforeach; ?>

      <!-- Totales -->
      <td class="col-total" style="background:<?= $pctBg ?>; color:<?= $pctColor ?>;">
        <?= $stat['pct'] !== null ? $stat['pct'].'%' : '—' ?>
      </td>
      <td class="col-total" style="color:#166534;"><?= $stat['asist'] ?></td>
      <td class="col-total" style="color:#854d0e;"><?= $stat['ret'] ?></td>
      <td class="col-total" style="color:#991b1b;"><?= $stat['falta'] ?></td>
    </tr>
    <?php endforeach; ?>

    <!-- Fila de totales por fecha -->
    <tr style="background:#f8fafc; font-weight:700;">
      <td class="col-fija-body" style="padding:7px 12px; border-top:2px solid var(--border);
                                        border-right:2px solid #e2e8f0; color:#64748b; font-size:11px;">
        Total presentes
      </td>
      <?php foreach ($fechasClase as $idx => $fc):
        $fecha   = $fc['fecha'];
        $festivo = $fc['festivo'];
        $u       = getUnidad($fecha, $unidades);
        $uid     = $u ? $u['id_unidad'] : 0;
        $siguienteUid = null;
        if (isset($fechasClase[$idx+1])) {
            $uSig = getUnidad($fechasClase[$idx+1]['fecha'], $unidades);
            $siguienteUid = $uSig ? $uSig['id_unidad'] : 0;
        }
        $claseExtra = ($uid !== $siguienteUid) ? 'borde-unidad' : '';

        if ($festivo || $fecha > $hoy): ?>
          <td class="celda <?= $claseExtra ?>" style="border-top:2px solid var(--border); color:#e2e8f0;">—</td>
        <?php else:
          $totalPresentes = 0;
          foreach ($alumnos as $al) {
              $t = $asistencias[(int)$al['id_alumno']][$fecha] ?? null;
              if ($t === 'asistencia' || $t === 'retardo') $totalPresentes++;
          }
          $pctPresentes = count($alumnos) > 0 ? round($totalPresentes/count($alumnos)*100) : 0;
        ?>
          <td class="celda <?= $claseExtra ?>"
              style="border-top:2px solid var(--border); font-size:11px;
                     color:<?= $pctPresentes >= 80 ? '#166534' : '#991b1b' ?>;">
            <?= $totalPresentes ?>
          </td>
        <?php endif; ?>
      <?php endforeach; ?>
      <td class="col-total" style="border-top:2px solid var(--border);"></td>
      <td class="col-total" style="border-top:2px solid var(--border);"></td>
      <td class="col-total" style="border-top:2px solid var(--border);"></td>
      <td class="col-total" style="border-top:2px solid var(--border);"></td>
    </tr>
  </tbody>
</table>
</div>

<!-- Festivos del periodo -->
<?php
$festivosDelPeriodo = array_filter(
    $festivosDelPeriodo ?? [],
    fn($f, $fecha) => $fecha >= ($_GET['desde'] ?? '') && $fecha <= ($_GET['hasta'] ?? ''),
    ARRAY_FILTER_USE_BOTH
);
if (!empty($festivosDelPeriodo)):
?>
<div class="card" style="margin-top:16px;">
  <div class="card-header">
    <div class="card-title">🎌 Días festivos en el periodo</div>
    <div class="card-subtitle">Marcados en rosa en la tabla — no cuentan para asistencia</div>
  </div>
  <div style="display:flex; flex-wrap:wrap; gap:8px;">
    <?php foreach ($festivosDelPeriodo as $fecha => $nombre): ?>
      <div style="background:#fce7f3; color:#9d174d; border:1px solid #fbcfe8;
                  border-radius:8px; padding:6px 12px; font-size:12px;">
        📅 <strong><?= htmlspecialchars($fecha,ENT_QUOTES,'UTF-8') ?></strong>
        — <?= htmlspecialchars($nombre,ENT_QUOTES,'UTF-8') ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php elseif (!empty($_GET['id_grupo'])): ?>
  <div class="card">
    <p style="text-align:center; color:#94a3b8; padding:40px; font-size:14px;">
      <?php if (empty($horarios)): ?>
        Este grupo no tiene horario registrado. Ve a <strong>Importar Horario</strong> para agregarlo.
      <?php else: ?>
        Sin fechas de clase en el rango seleccionado.
      <?php endif; ?>
    </p>
  </div>
<?php endif; ?>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
