<?php
$pageTitle    = 'Sesión de Clase';
$accionActual = 'pasar_lista';
require_once BASE_PATH . '/app/views/layout/sidebar.php';

// Preparar datos de alumnos para JavaScript
$alumnosJS = [];
foreach ($alumnos ?? [] as $al) {
    $alumnosJS[] = [
        'id'     => (int)$al['id_alumno'],
        'nombre' => $al['nombre'],
        'previo' => $asistenciasHoy[(int)$al['id_alumno']] ?? 'asistencia',
    ];
}
?>

<style>
/* ── LLAMADA DE LISTA ──────────────────────────── */
.llamada-panel {
  background: linear-gradient(135deg, #0f1629, #1e3a5f);
  border-radius: 14px;
  padding: 24px 28px;
  margin-bottom: 20px;
  color: #fff;
  display: none;
}
.llamada-panel.activa { display: block; }

.nombre-actual {
  font-size: 32px;
  font-weight: 700;
  text-align: center;
  margin: 16px 0 8px;
  min-height: 42px;
  letter-spacing: .01em;
}
.control-actual {
  text-align: center;
  color: rgba(255,255,255,.5);
  font-size: 13px;
  margin-bottom: 20px;
}
.progreso-llamada {
  background: rgba(255,255,255,.15);
  border-radius: 999px;
  height: 6px;
  margin-bottom: 20px;
  overflow: hidden;
}
.progreso-barra {
  height: 100%;
  background: #3b82f6;
  border-radius: 999px;
  transition: width .3s;
}
.btns-llamada {
  display: flex;
  gap: 12px;
  justify-content: center;
}
.btn-presente {
  background: #10b981; color: #fff; border: none;
  padding: 14px 36px; border-radius: 10px;
  font-size: 18px; font-weight: 700; cursor: pointer;
  transition: transform .1s, background .15s;
  flex: 1; max-width: 180px;
}
.btn-presente:hover  { background: #059669; }
.btn-presente:active { transform: scale(.97); }
.btn-falta {
  background: #ef4444; color: #fff; border: none;
  padding: 14px 36px; border-radius: 10px;
  font-size: 18px; font-weight: 700; cursor: pointer;
  transition: transform .1s, background .15s;
  flex: 1; max-width: 180px;
}
.btn-falta:hover  { background: #dc2626; }
.btn-falta:active { transform: scale(.97); }

/* Fila resaltada actualmente */
tr.fila-activa td { background: #fffbeb !important; outline: 2px solid #f59e0b; }

/* Sección retardos */
#seccion-retardos { display: none; }
#seccion-retardos.visible { display: block; }
</style>

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

<!-- ══ PANEL DE LLAMADA POR VOZ ══════════════════════════════ -->
<div id="panelLlamada" class="llamada-panel">
  <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
    <div>
      <div style="font-size:11px; font-weight:600; text-transform:uppercase;
                  letter-spacing:.1em; color:rgba(255,255,255,.4); margin-bottom:4px;">
        Llamada de lista en curso
      </div>
      <div style="font-size:13px; color:rgba(255,255,255,.6);">
        <span id="contadorActual">0</span> / <?= count($alumnos) ?> alumnos
      </div>
    </div>
    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
      <!-- Selector de voz -->
      <div>
        <label style="font-size:10px; color:rgba(255,255,255,.35); display:block; margin-bottom:3px; text-transform:uppercase; letter-spacing:.05em;">Voz</label>
        <select id="selectorVoz"
                style="background:rgba(255,255,255,.1); color:#fff; border:1px solid rgba(255,255,255,.2);
                       padding:5px 10px; border-radius:7px; font-size:12px; cursor:pointer; max-width:180px;">
          <option value="">Cargando voces…</option>
        </select>
      </div>
      <!-- Velocidad -->
      <div>
        <label style="font-size:10px; color:rgba(255,255,255,.35); display:block; margin-bottom:3px; text-transform:uppercase; letter-spacing:.05em;">Velocidad</label>
        <select id="selectorVelocidad"
                style="background:rgba(255,255,255,.1); color:#fff; border:1px solid rgba(255,255,255,.2);
                       padding:5px 10px; border-radius:7px; font-size:12px; cursor:pointer;">
          <option value="0.7">Lenta</option>
          <option value="0.9" selected>Normal</option>
          <option value="1.2">Rápida</option>
          <option value="1.5">Muy rápida</option>
        </select>
      </div>
      <button type="button" onclick="detenerLlamada()"
              style="background:rgba(255,255,255,.1); color:rgba(255,255,255,.7);
                     border:1px solid rgba(255,255,255,.2); padding:6px 14px;
                     border-radius:8px; cursor:pointer; font-size:12px; margin-top:14px;">
        ✕ Terminar
      </button>
    </div>
  </div>

  <div class="nombre-actual" id="nombreActual">—</div>
  <div class="control-actual" id="controlActual"></div>

  <div class="progreso-llamada">
    <div class="progreso-barra" id="progresoBarra" style="width:0%"></div>
  </div>

  <div class="btns-llamada">
    <button type="button" class="btn-presente" onclick="marcarYAvanzar('asistencia')">
      ✅ Presente
    </button>
    <button type="button" class="btn-falta" onclick="marcarYAvanzar('inasistencia')">
      ❌ Falta
    </button>
  </div>

  <div style="text-align:center; margin-top:16px; font-size:12px; color:rgba(255,255,255,.3);">
    También puedes marcar directamente en la tabla de abajo
  </div>
</div>

<!-- Botón para iniciar llamada -->
<div id="btnIniciarWrap" style="margin-bottom:20px;">
  <button type="button" onclick="iniciarLlamada()"
          style="background:#1d4ed8; color:#fff; border:none; border-radius:10px;
                 padding:12px 24px; font-size:14px; font-weight:600; cursor:pointer;
                 display:flex; align-items:center; gap:8px; transition:background .15s;">
    🔊 Iniciar llamada de lista con voz
  </button>
  <div style="font-size:12px; color:#94a3b8; margin-top:6px;">
    El sistema leerá cada nombre en voz alta. Marca ✅ o ❌ para avanzar al siguiente.
    Los retardos se asignan al finalizar.
  </div>
</div>

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
      <select name="id_unidad" class="form-control" style="height:42px;"
              onchange="document.getElementById('formSesion').submit()">
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
        <div class="card-subtitle">
          <?= count($alumnos) ?> alumnos ·
          <span style="color:#64748b; font-size:12px;">Solo ✅/❌ durante la llamada · Los retardos se marcan después</span>
        </div>
      </div>
      <div style="display:flex; gap:8px;">
        <button type="button" onclick="marcarTodos('asistencia')"  class="btn btn-success btn-sm">Todos presentes</button>
        <button type="button" onclick="marcarTodos('inasistencia')" class="btn btn-outline btn-sm">Todos ausentes</button>
      </div>
    </div>
    <div class="table-wrap">
      <table id="tablaAsistencia">
        <thead>
          <tr>
            <th>#</th>
            <th>No. Control</th>
            <th>Alumno</th>
            <th style="text-align:center; color:#10b981;">✅ Presente<br><small style="color:#94a3b8;">(1.0)</small></th>
            <th style="text-align:center; color:#ef4444;">❌ Falta<br><small style="color:#94a3b8;">(0.0)</small></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($alumnos as $i => $al): ?>
            <?php
              $idA    = (int)$al['id_alumno'];
              $regHoy = $asistenciasHoy[$idA] ?? 'asistencia';
              // Mapear retardo guardado a asistencia para mostrarlo en la tabla simplificada
              $checkedPresente    = ($regHoy === 'asistencia') ? 'checked' : '';
              $checkedInasistencia= ($regHoy === 'inasistencia') ? 'checked' : '';
              // Si tiene retardo guardado, lo mostramos como presente aquí (se gestiona abajo)
              if ($regHoy === 'retardo') $checkedPresente = 'checked';
            ?>
            <tr id="fila-<?= $idA ?>" data-nombre="<?= htmlspecialchars($al['nombre'], ENT_QUOTES, 'UTF-8') ?>">
              <td style="color:#94a3b8; font-size:12px;"><?= $i+1 ?></td>
              <td><span class="badge badge-blue"><?= htmlspecialchars($al['numero_control'], ENT_QUOTES, 'UTF-8') ?></span></td>
              <td style="font-weight:500;"><?= htmlspecialchars($al['nombre'], ENT_QUOTES, 'UTF-8') ?></td>

              <!-- Solo PRESENTE -->
              <td style="text-align:center;">
                <input type="radio" name="asistencia[<?= $idA ?>]" value="asistencia"
                  class="radio-asist" data-alumno="<?= $idA ?>" data-tipo="asistencia"
                  <?= $checkedPresente ?>
                  style="width:20px; height:20px; cursor:pointer; accent-color:#10b981;">
              </td>

              <!-- Solo FALTA -->
              <td style="text-align:center;">
                <input type="radio" name="asistencia[<?= $idA ?>]" value="inasistencia"
                  class="radio-asist" data-alumno="<?= $idA ?>" data-tipo="inasistencia"
                  <?= $checkedInasistencia ?>
                  style="width:20px; height:20px; cursor:pointer; accent-color:#ef4444;">
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ══ SECCIÓN RETARDOS (aparece al terminar la llamada) ══ -->
  <div id="seccion-retardos" class="card" style="margin-bottom:20px; border:2px solid #fde68a;">
    <div class="card-header">
      <div>
        <div class="card-title">⏰ Registrar Retardos</div>
        <div class="card-subtitle">
          ¿Algún alumno marcado como falta llegó tarde? Cámbialo a retardo aquí.
        </div>
      </div>
    </div>
    <div id="lista-retardos">
      <!-- Se llena dinámicamente con JS -->
    </div>
    <div id="sin-faltas"
         style="display:none; text-align:center; color:#94a3b8; padding:20px; font-size:13px;">
      Sin faltas registradas — no hay retardos que asignar.
    </div>
  </div>

  <!-- ══ SECCIÓN 2: ACTIVIDAD EN CLASE ══════════════════════ -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">
      <div>
        <div class="card-title">📝 Actividad en Clase
          <span style="font-size:11px; font-weight:400; color:#94a3b8;">(opcional)</span>
        </div>
        <div class="card-subtitle">Deja el nombre vacío si no hubo actividad hoy</div>
      </div>
    </div>
    <?php if (!$unidadActual || !($unidadActual['id_unidad'] ?? 0)): ?>
      <div class="alert alert-warning" style="margin-bottom:0;">
        ⚠️ Selecciona una <strong>unidad</strong> arriba para registrar actividades y tareas.
      </div>
    <?php else: ?>

    <!-- Nombre de la actividad -->
    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px; align-items:flex-end;">
      <div class="form-group" style="flex:1; min-width:200px; margin-bottom:0;">
        <label class="form-label">Nombre de la actividad</label>
        <input type="text" name="actividad_nombre" id="actNombre" class="form-control"
               placeholder="Ej: Práctica 1 — Arreglos en C++"
               value="<?= htmlspecialchars($actividadDia['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <!-- Tipo: individual o equipo -->
      <div class="form-group" style="flex:0 0 auto; margin-bottom:0;">
        <label class="form-label">Tipo de actividad</label>
        <div style="display:flex; gap:8px;">
          <label style="display:flex; align-items:center; gap:6px; cursor:pointer;
                         background:#f8fafc; border:1px solid var(--border); border-radius:8px;
                         padding:8px 14px; font-size:13px; transition:all .15s;"
                 id="lblIndividual">
            <input type="radio" name="actividad_tipo" value="individual"
                   id="tipoIndividual" onchange="cambiarTipoActividad()"
                   <?= (($actividadDia['tipo_actividad'] ?? 'individual') === 'individual') ? 'checked' : '' ?>>
            👤 Individual
          </label>
          <label style="display:flex; align-items:center; gap:6px; cursor:pointer;
                         background:#f8fafc; border:1px solid var(--border); border-radius:8px;
                         padding:8px 14px; font-size:13px; transition:all .15s;"
                 id="lblEquipo">
            <input type="radio" name="actividad_tipo" value="equipo"
                   id="tipoEquipo" onchange="cambiarTipoActividad()"
                   <?= (($actividadDia['tipo_actividad'] ?? 'individual') === 'equipo') ? 'checked' : '' ?>>
            👥 Equipos
          </label>
        </div>
      </div>
    </div>

    <!-- ══ VISTA INDIVIDUAL ══ -->
    <div id="vistaIndividual">
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
                <td><span class="badge badge-blue"><?= htmlspecialchars($al['numero_control'],ENT_QUOTES,'UTF-8') ?></span></td>
                <td style="font-weight:500;"><?= htmlspecialchars($al['nombre'],ENT_QUOTES,'UTF-8') ?></td>
                <td style="text-align:center;width:120px;">
                  <input type="number" name="actividad_calif[<?= $idA ?>]"
                         min="0" max="10" step="0.1"
                         value="<?= $califActividadHoy[$idA] ?? '' ?>"
                         class="form-control" style="text-align:center;max-width:90px;margin:auto;"
                         placeholder="—">
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ══ VISTA EQUIPOS ══ -->
    <div id="vistaEquipos" style="display:none;">
      <div style="margin-bottom:12px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <span style="font-size:13px; font-weight:500;">Número de equipos:</span>
        <input type="number" id="numEquipos" value="<?= $numEquiposActual ?? 2 ?>"
               min="2" max="20" style="width:70px;" class="form-control"
               onchange="generarEquipos()">
        <button type="button" onclick="generarEquipos()" class="btn btn-outline btn-sm">
          🔄 Generar equipos
        </button>
        <div class="alert alert-info" style="margin:0; padding:6px 12px; font-size:12px; flex:1; min-width:200px;">
          <strong>Opción A</strong>: Misma calificación para todo el equipo &nbsp;|&nbsp;
          <strong>Opción B</strong>: Calificación individual por alumno
        </div>
      </div>

      <!-- Panel: alumnos sin equipo -->
      <div id="sinEquipoPanel"
           style="background:#fffbeb; border:1px solid #fde68a; border-radius:10px;
                  padding:10px 14px; margin-bottom:14px; display:none;">
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
          <span style="font-size:13px; font-weight:600; color:#92400e;">⚠️ Alumnos sin equipo:</span>
          <span id="sinEquipoCount" style="font-size:12px; color:#92400e;">0</span>
          <select id="sinEquipoSelect" class="form-control"
                  style="flex:1; min-width:200px; max-width:340px; height:34px; font-size:13px;"></select>
          <select id="sinEquipoDestino" class="form-control"
                  style="width:140px; height:34px; font-size:13px;"></select>
          <button type="button" class="btn btn-primary btn-sm" onclick="agregarDesdeSinEquipo()">
            ➕ Asignar
          </button>
        </div>
      </div>

      <div id="equiposContainer">
        <!-- Se genera con JS -->
      </div>
    </div>

    <?php endif; ?>
  </div>

  <!-- ══ SECCIÓN 3: TAREA ════════════════════════════════════ -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">
      <div>
        <div class="card-title">📚 Tarea
          <span style="font-size:11px; font-weight:400; color:#94a3b8;">(opcional)</span>
        </div>
        <div class="card-subtitle">Deja el nombre vacío si no se revisó tarea hoy</div>
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
              <td style="color:#94a3b8; font-size:12px;"><?= $i+1 ?></td>
              <td><span class="badge badge-blue"><?= htmlspecialchars($al['numero_control'], ENT_QUOTES, 'UTF-8') ?></span></td>
              <td style="font-weight:500;"><?= htmlspecialchars($al['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
              <td style="text-align:center; width:120px;">
                <input type="number" name="tarea_calif[<?= $idA ?>]"
                       min="0" max="10" step="0.1"
                       value="<?= $califTareaHoy[$idA] ?? '' ?>"
                       class="form-control"
                       style="text-align:center; max-width:90px; margin:auto;"
                       placeholder="—">
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <div style="display:flex; justify-content:flex-end; gap:10px;">
    <a href="index.php?accion=historial_grupo&id_grupo=<?= (int)$idGrupo ?>"
       class="btn btn-outline">📋 Ver historial</a>
    <button type="submit" class="btn btn-success" style="padding:10px 28px; font-size:15px;">
      💾 Guardar sesión completa
    </button>
  </div>
</form>

<!-- ══ JAVASCRIPT: VOZ + LÓGICA ═══════════════════════════ -->
<script>
// ── Datos de alumnos desde PHP ──────────────────────────────
const ALUMNOS = <?= json_encode($alumnosJS, JSON_UNESCAPED_UNICODE) ?>;
let indiceActual = -1;
let enLlamada    = false;
let sintesis     = window.speechSynthesis;

// ── Colorear filas al cargar ─────────────────────────────────
document.querySelectorAll('.radio-asist:checked').forEach(r => colorearFila(r));

document.querySelectorAll('.radio-asist').forEach(r => {
  r.addEventListener('change', function() { colorearFila(this); });
});

function colorearFila(radio) {
  const fila = document.getElementById('fila-' + radio.dataset.alumno);
  if (!fila) return;
  fila.style.background = '';
  if (radio.dataset.tipo === 'asistencia')   fila.style.background = '#f0fdf4';
  if (radio.dataset.tipo === 'inasistencia') fila.style.background = '#fef2f2';
}

function marcarTodos(tipo) {
  document.querySelectorAll('.radio-asist[data-tipo="'+tipo+'"]').forEach(r => {
    r.checked = true;
    colorearFila(r);
  });
}

// ── Llamada por voz ──────────────────────────────────────────
function iniciarLlamada() {
  if (!('speechSynthesis' in window)) {
    alert('Tu navegador no soporta síntesis de voz. Prueba con Chrome o Edge.');
    return;
  }
  sintesis.cancel();
  indiceActual = -1;
  enLlamada    = true;
  document.getElementById('panelLlamada').classList.add('activa');
  document.getElementById('btnIniciarWrap').style.display = 'none';
  avanzarAlumno();
}

function avanzarAlumno() {
  // Quitar resaltado del anterior
  if (indiceActual >= 0 && indiceActual < ALUMNOS.length) {
    const filaAnterior = document.getElementById('fila-' + ALUMNOS[indiceActual].id);
    if (filaAnterior) filaAnterior.classList.remove('fila-activa');
  }

  indiceActual++;

  // ¿Terminamos?
  if (indiceActual >= ALUMNOS.length) {
    terminarLlamada();
    return;
  }

  const alumno = ALUMNOS[indiceActual];

  // Actualizar panel
  document.getElementById('nombreActual').textContent  = alumno.nombre;
  document.getElementById('controlActual').textContent = '';
  document.getElementById('contadorActual').textContent = indiceActual + 1;

  const pct = ((indiceActual + 1) / ALUMNOS.length * 100).toFixed(1);
  document.getElementById('progresoBarra').style.width = pct + '%';

  // Resaltar fila en la tabla
  const fila = document.getElementById('fila-' + alumno.id);
  if (fila) {
    fila.classList.add('fila-activa');
    fila.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  // Leer nombre en voz alta
  leerNombre(alumno.nombre);
}

function leerNombre(nombre) {
  sintesis.cancel();
  const utterance  = new SpeechSynthesisUtterance(nombre);
  utterance.lang   = 'es-MX';
  utterance.rate   = parseFloat(document.getElementById('selectorVelocidad')?.value ?? '0.9');
  utterance.pitch  = 1.0;
  utterance.volume = 1.0;

  // Usar la voz elegida por el docente
  const vozSeleccionada = document.getElementById('selectorVoz')?.value;
  if (vozSeleccionada) {
    const voz = sintesis.getVoices().find(v => v.name === vozSeleccionada);
    if (voz) utterance.voice = voz;
  }
  sintesis.speak(utterance);
}

// ── Cargar lista de voces disponibles ───────────────────────
function cargarVoces() {
  const select = document.getElementById('selectorVoz');
  if (!select) return;
  const voces = sintesis.getVoices();
  if (!voces.length) return;
  select.innerHTML = '';
  const vocesES    = voces.filter(v => v.lang.startsWith('es'));
  const vocesOtras = voces.filter(v => !v.lang.startsWith('es'));
  if (vocesES.length) {
    const grp = document.createElement('optgroup');
    grp.label = '🇲🇽 Español';
    vocesES.forEach(v => {
      const opt     = new Option(v.name + (v.localService ? ' ★' : ''), v.name);
      if (v.lang === 'es-MX' || v.lang === 'es-419') opt.selected = true;
      grp.appendChild(opt);
    });
    select.appendChild(grp);
  }
  if (vocesOtras.length) {
    const grp = document.createElement('optgroup');
    grp.label = '🌐 Otros idiomas';
    vocesOtras.slice(0, 12).forEach(v => {
      grp.appendChild(new Option(`[${v.lang}] ${v.name}`, v.name));
    });
    select.appendChild(grp);
  }
}

function marcarYAvanzar(tipo) {
  if (!enLlamada || indiceActual < 0 || indiceActual >= ALUMNOS.length) return;
  const alumno = ALUMNOS[indiceActual];

  // Marcar el radio correspondiente
  const radio = document.querySelector(
    `input[name="asistencia[${alumno.id}]"][value="${tipo}"]`
  );
  if (radio) {
    radio.checked = true;
    colorearFila(radio);
  }

  // Pequeña pausa antes del siguiente
  setTimeout(() => avanzarAlumno(), 300);
}

function terminarLlamada() {
  enLlamada = false;
  sintesis.cancel();
  document.getElementById('panelLlamada').classList.remove('activa');
  document.getElementById('btnIniciarWrap').style.display = 'block';

  // Quitar último resaltado
  document.querySelectorAll('.fila-activa').forEach(f => f.classList.remove('fila-activa'));

  // Mostrar sección de retardos con los que tienen falta
  mostrarSeccionRetardos();
}

function detenerLlamada() {
  sintesis.cancel();
  terminarLlamada();
}

// ── Sección de Retardos ──────────────────────────────────────
function mostrarSeccionRetardos() {
  const seccion      = document.getElementById('seccion-retardos');
  const listaDiv     = document.getElementById('lista-retardos');
  const sinFaltasDiv = document.getElementById('sin-faltas');
  seccion.classList.add('visible');

  // Obtener alumnos con falta marcada
  const conFalta = ALUMNOS.filter(al => {
    const radio = document.querySelector(
      `input[name="asistencia[${al.id}]"][value="inasistencia"]:checked`
    );
    return !!radio;
  });

  listaDiv.innerHTML = '';

  if (conFalta.length === 0) {
    sinFaltasDiv.style.display = 'block';
    return;
  }

  sinFaltasDiv.style.display = 'none';

  conFalta.forEach(al => {
    const div = document.createElement('div');
    div.style.cssText = 'display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid #f1f5f9;';
    div.innerHTML = `
      <div style="font-weight:500; font-size:14px;">${al.nombre}</div>
      <div style="display:flex; gap:8px; align-items:center;">
        <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-size:13px; color:#991b1b;">
          <input type="radio" name="asistencia[${al.id}]" value="inasistencia" checked
                 style="accent-color:#ef4444; width:16px; height:16px;"
                 onchange="colorearFilaById(${al.id}, 'inasistencia')">
          ❌ Falta
        </label>
        <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-size:13px; color:#92400e;">
          <input type="radio" name="asistencia[${al.id}]" value="retardo"
                 style="accent-color:#f59e0b; width:16px; height:16px;"
                 onchange="colorearFilaById(${al.id}, 'retardo')">
          ⏰ Retardo
        </label>
      </div>
    `;
    listaDiv.appendChild(div);
  });

  // Scroll a la sección de retardos
  seccion.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function colorearFilaById(idAlumno, tipo) {
  const fila = document.getElementById('fila-' + idAlumno);
  if (!fila) return;
  fila.style.background = '';
  if (tipo === 'retardo')      fila.style.background = '#fffbeb';
  if (tipo === 'inasistencia') fila.style.background = '#fef2f2';
  if (tipo === 'asistencia')   fila.style.background = '#f0fdf4';
  // Sync el radio en la tabla principal
  const radioTabla = document.querySelector(`#fila-${idAlumno} input[value="${tipo}"]`);
  if (radioTabla) radioTabla.checked = true;
}

// Chrome carga voces de forma asíncrona
if (sintesis.getVoices().length) { cargarVoces(); }
else { sintesis.onvoiceschanged = cargarVoces; }

// ── Actividades: Individual vs Equipo ─────────────────────
const ALUMNOS_EQUIPO = ALUMNOS.map(a => ({ id: a.id, nombre: a.nombre }));

function cambiarTipoActividad() {
  const esEquipo = document.getElementById('tipoEquipo').checked;
  document.getElementById('vistaIndividual').style.display = esEquipo ? 'none' : 'block';
  document.getElementById('vistaEquipos').style.display    = esEquipo ? 'block' : 'none';
  document.getElementById('lblIndividual').style.background = esEquipo ? '#f8fafc' : '#eff6ff';
  document.getElementById('lblEquipo').style.background     = esEquipo ? '#eff6ff' : '#f8fafc';
  if (esEquipo && !document.querySelector('.equipo-card')) generarEquipos();
}

function generarEquipos() {
  const n          = parseInt(document.getElementById('numEquipos').value) || 2;
  const container  = document.getElementById('equiposContainer');
  const alumnosXeq = Math.ceil(ALUMNOS_EQUIPO.length / n);
  container.innerHTML = '';

  for (let e = 0; e < n; e++) {
    const inicio    = e * alumnosXeq;
    const miembros  = ALUMNOS_EQUIPO.slice(inicio, inicio + alumnosXeq);

    const card = document.createElement('div');
    card.className   = 'equipo-card';
    card.dataset.eq  = e;
    card.style.cssText = 'border:1px solid #e2e8f0; border-radius:10px; margin-bottom:14px; overflow:hidden;';

    // Encabezado del equipo
    const header = document.createElement('div');
    header.style.cssText = 'background:#0f1629; padding:10px 14px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;';
    header.innerHTML = `
      <span style="color:#fff; font-size:13px; font-weight:600;">👥 Equipo ${e+1}</span>
      <input type="text" name="equipo_nombre[${e}]" value="Equipo ${e+1}"
             style="background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.2);
                    color:#fff; border-radius:6px; padding:4px 8px; font-size:12px; flex:1; max-width:180px;">
      <button type="button" class="btn btn-sm" data-add-eq="${e}"
              onclick="toggleSelectorAgregar(${e}, this)"
              style="background:#3b82f6; color:#fff; border:none; border-radius:6px;
                     padding:4px 10px; font-size:12px; cursor:pointer;">
        ➕ Agregar alumno
      </button>
      <div style="margin-left:auto; display:flex; align-items:center; gap:8px;">
        <label style="color:rgba(255,255,255,.6); font-size:11px;">Misma calif. para todos:</label>
        <input type="checkbox" class="check-misma-calif" data-equipo="${e}"
               onchange="toggleCalifEquipo(${e})"
               style="width:16px; height:16px; cursor:pointer; accent-color:#3b82f6;">
        <input type="number" name="equipo_calif_global[${e}]" id="califGlobal${e}"
               min="0" max="10" step="0.1" placeholder="—" disabled
               style="width:70px; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.2);
                      color:#fff; border-radius:6px; padding:4px 8px; font-size:12px; text-align:center;"
               oninput="aplicarCalifGlobal(${e}, this.value)">
      </div>`;
    card.appendChild(header);

    // Selector inline para agregar (oculto por defecto)
    const selWrap = document.createElement('div');
    selWrap.id = 'selectorAgregar' + e;
    selWrap.style.cssText = 'display:none; padding:10px 14px; background:#eff6ff; border-bottom:1px solid #bfdbfe; gap:8px; align-items:center;';
    selWrap.innerHTML = `
      <select id="selectAlumno${e}" class="form-control"
              style="flex:1; height:34px; font-size:13px;"></select>
      <button type="button" class="btn btn-primary btn-sm"
              onclick="confirmarAgregarAEquipo(${e})">Añadir</button>
      <button type="button" class="btn btn-outline btn-sm"
              onclick="toggleSelectorAgregar(${e}, document.querySelector('[data-add-eq=&quot;${e}&quot;]'))">Cancelar</button>
    `;
    card.appendChild(selWrap);

    // Tabla de miembros
    const tabla = document.createElement('table');
    tabla.style.cssText = 'width:100%; border-collapse:collapse; font-size:13px;';
    tabla.innerHTML = `<thead><tr>
      <th style="padding:6px 14px; background:#f8fafc; font-size:11px; text-align:left; color:#64748b; text-transform:uppercase;">Alumno</th>
      <th style="padding:6px 14px; background:#f8fafc; font-size:11px; text-align:center; color:#64748b; text-transform:uppercase; width:140px;">Calificación</th>
      <th style="padding:6px 14px; background:#f8fafc; font-size:11px; text-align:center; color:#64748b; text-transform:uppercase; width:60px;"></th>
    </tr></thead>`;
    const tbody = document.createElement('tbody');
    tbody.id = 'tbodyEq' + e;
    tabla.appendChild(tbody);
    card.appendChild(tabla);
    container.appendChild(card);

    // Insertar miembros usando helper para mantener consistencia
    miembros.forEach(al => insertarMiembroEnEquipo(e, al.id, al.nombre));
  }

  refrescarSinEquipo();
}

// ── Devuelve los IDs ya asignados a algún equipo ───────────
function obtenerIdsAsignados() {
  const ids = new Set();
  document.querySelectorAll('input[name^="equipo_miembro["]').forEach(inp => {
    ids.add(parseInt(inp.value));
  });
  return ids;
}

// ── Devuelve el equipo (índice) en el que está un alumno, o -1 ──
function equipoDelAlumno(idAlumno) {
  const cards = document.querySelectorAll('.equipo-card');
  for (const card of cards) {
    const e = parseInt(card.dataset.eq);
    if (card.querySelector(`input[name="equipo_miembro[${e}][]"][value="${idAlumno}"]`)) {
      return e;
    }
  }
  return -1;
}

// ── Inserta un alumno en un equipo (DOM) ────────────────────
function insertarMiembroEnEquipo(eIdx, idAlumno, nombreAlumno) {
  const tbody = document.getElementById('tbodyEq' + eIdx);
  if (!tbody) return;
  // Si ya está, no duplicar
  if (tbody.querySelector(`input[name="equipo_miembro[${eIdx}][]"][value="${idAlumno}"]`)) return;

  const tr = document.createElement('tr');
  tr.dataset.alumno = idAlumno;
  tr.innerHTML = `
    <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; font-weight:500;">${nombreAlumno}</td>
    <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; text-align:center;">
      <input type="number" name="actividad_calif[${idAlumno}]"
             id="califAlumno${eIdx}_${idAlumno}" class="calif-equipo-${eIdx}"
             min="0" max="10" step="0.1" placeholder="—"
             class="form-control" style="text-align:center; max-width:90px; margin:auto;">
      <input type="hidden" name="equipo_miembro[${eIdx}][]" value="${idAlumno}">
    </td>
    <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; text-align:center;">
      <button type="button" title="Quitar del equipo"
              onclick="quitarMiembro(${eIdx}, ${idAlumno})"
              style="background:#fee2e2; color:#991b1b; border:none; border-radius:6px;
                     padding:4px 10px; font-size:12px; cursor:pointer;">
        ✕
      </button>
    </td>`;
  tbody.appendChild(tr);

  // Si el equipo tiene calif global activa, aplicarla al recién agregado
  const check    = document.querySelector(`.check-misma-calif[data-equipo="${eIdx}"]`);
  const globalIn = document.getElementById('califGlobal' + eIdx);
  if (check && check.checked && globalIn && globalIn.value !== '') {
    aplicarCalifGlobal(eIdx, globalIn.value);
  }
}

// ── Quitar miembro de un equipo ─────────────────────────────
function quitarMiembro(eIdx, idAlumno) {
  const tbody = document.getElementById('tbodyEq' + eIdx);
  if (!tbody) return;
  const tr = tbody.querySelector(`tr[data-alumno="${idAlumno}"]`);
  if (tr) tr.remove();
  refrescarSinEquipo();
}

// ── Selector inline por equipo ──────────────────────────────
function toggleSelectorAgregar(eIdx, btn) {
  const wrap = document.getElementById('selectorAgregar' + eIdx);
  if (!wrap) return;
  const visible = wrap.style.display === 'flex';
  // Cerrar todos los demás
  document.querySelectorAll('[id^="selectorAgregar"]').forEach(w => w.style.display = 'none');
  if (visible) return;
  // Llenar select con disponibles
  const select   = document.getElementById('selectAlumno' + eIdx);
  const asignados = obtenerIdsAsignados();
  const disponibles = ALUMNOS_EQUIPO.filter(a => !asignados.has(a.id));
  if (!disponibles.length) {
    // permitir mover desde otro equipo: incluir todos los que NO estén ya en este equipo
    const enEsteEq = new Set();
    document.querySelectorAll(`input[name="equipo_miembro[${eIdx}][]"]`).forEach(i => enEsteEq.add(parseInt(i.value)));
    select.innerHTML = ALUMNOS_EQUIPO
      .filter(a => !enEsteEq.has(a.id))
      .map(a => {
        const eqActual = equipoDelAlumno(a.id);
        const sufijo   = eqActual >= 0 ? ` (en Equipo ${eqActual+1})` : '';
        return `<option value="${a.id}">${a.nombre}${sufijo}</option>`;
      }).join('');
  } else {
    select.innerHTML = disponibles
      .map(a => `<option value="${a.id}">${a.nombre}</option>`)
      .join('');
  }
  wrap.style.display = 'flex';
}

function confirmarAgregarAEquipo(eIdx) {
  const select = document.getElementById('selectAlumno' + eIdx);
  if (!select || !select.value) return;
  const idAlumno = parseInt(select.value);
  const alumno   = ALUMNOS_EQUIPO.find(a => a.id === idAlumno);
  if (!alumno) return;
  // Si ya está en otro equipo, removerlo de allá
  const eqActual = equipoDelAlumno(idAlumno);
  if (eqActual >= 0 && eqActual !== eIdx) {
    quitarMiembro(eqActual, idAlumno);
  }
  insertarMiembroEnEquipo(eIdx, idAlumno, alumno.nombre);
  document.getElementById('selectorAgregar' + eIdx).style.display = 'none';
  refrescarSinEquipo();
}

// ── Panel global de alumnos sin equipo ──────────────────────
function refrescarSinEquipo() {
  const panel    = document.getElementById('sinEquipoPanel');
  const select   = document.getElementById('sinEquipoSelect');
  const destino  = document.getElementById('sinEquipoDestino');
  const counter  = document.getElementById('sinEquipoCount');
  if (!panel || !select || !destino) return;

  const asignados = obtenerIdsAsignados();
  const sinEquipo = ALUMNOS_EQUIPO.filter(a => !asignados.has(a.id));

  // Llenar selector de alumnos
  select.innerHTML = sinEquipo.length
    ? sinEquipo.map(a => `<option value="${a.id}">${a.nombre}</option>`).join('')
    : '';

  // Llenar selector de equipo destino con los equipos existentes
  const cards = document.querySelectorAll('.equipo-card');
  destino.innerHTML = Array.from(cards).map(c => {
    const e = parseInt(c.dataset.eq);
    return `<option value="${e}">Equipo ${e+1}</option>`;
  }).join('');

  counter.textContent = sinEquipo.length + ' por asignar';
  panel.style.display = sinEquipo.length ? 'block' : 'none';
}

function agregarDesdeSinEquipo() {
  const select  = document.getElementById('sinEquipoSelect');
  const destino = document.getElementById('sinEquipoDestino');
  if (!select.value || !destino.value) return;
  const idAlumno = parseInt(select.value);
  const eIdx     = parseInt(destino.value);
  const alumno   = ALUMNOS_EQUIPO.find(a => a.id === idAlumno);
  if (!alumno) return;
  insertarMiembroEnEquipo(eIdx, idAlumno, alumno.nombre);
  refrescarSinEquipo();
}

function toggleCalifEquipo(e) {
  const check    = document.querySelector(`.check-misma-calif[data-equipo="${e}"]`);
  const globalIn = document.getElementById('califGlobal' + e);
  globalIn.disabled = !check.checked;
  if (check.checked) globalIn.focus();
  else aplicarCalifGlobal(e, '');
}

function aplicarCalifGlobal(e, valor) {
  document.querySelectorAll(`.calif-equipo-${e}`).forEach(inp => {
    inp.value    = valor;
    inp.disabled = !!valor;
  });
}

// Inicializar tipo al cargar
(function() {
  const esEquipo = document.getElementById('tipoEquipo')?.checked;
  if (esEquipo) cambiarTipoActividad();
})();
</script>

<?php elseif (isset($_GET['id_grupo'])): ?>
<div class="card">
  <p style="text-align:center; color:#94a3b8; padding:40px; font-size:14px;">
    No hay alumnos inscritos en este grupo aún.
  </p>
</div>
<?php endif; ?>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
