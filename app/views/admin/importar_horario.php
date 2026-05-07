<?php
$pageTitle    = 'Importar Horario';
$accionActual = 'importar_horario';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<style>
  .paste-area {
    width: 100%;
    min-height: 140px;
    border: 2px dashed var(--border);
    border-radius: 10px;
    padding: 20px;
    font-family: monospace;
    font-size: 13px;
    resize: vertical;
    background: #f8fafc;
    color: var(--text-main);
    transition: border-color .2s;
  }
  .paste-area:focus { outline: none; border-color: var(--accent); background: #fff; }

  .preview-table { border-collapse: collapse; width: 100%; font-size: 13px; }
  .preview-table th { background: #0f1629; color: #fff; padding: 8px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; }
  .preview-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
  .preview-table tr:hover td { background: #f8fafc; }
  .tag-dia { display: inline-block; background: #eff6ff; color: #1d4ed8; border-radius: 5px; padding: 2px 7px; font-size: 11px; font-weight: 600; margin: 2px; }
  .tag-hora { color: #64748b; font-size: 12px; }
  .tag-aula { display: inline-block; background: #f0fdf4; color: #166534; border-radius: 5px; padding: 2px 7px; font-size: 11px; margin-left: 4px; }
  .row-nuevo  { background: #f0fdf4 !important; }
  .row-existe { background: #fef9c3 !important; }
</style>

<?php if (!empty($mensaje)): ?>
  <div class="alert alert-<?= $tipoMensaje ?? 'success' ?>"><?= $mensaje ?></div>
<?php endif; ?>

<!-- PASO 1: Pegar tabla -->
<div class="card" style="margin-bottom:20px;" id="paso1">
  <div class="card-header">
    <div>
      <div class="card-title">📋 Pegar tabla de horarios</div>
      <div class="card-subtitle">
        Copia la tabla desde Excel (Ctrl+C sobre las celdas) y pégala aquí.
        El sistema detectará los grupos, materias y horarios automáticamente.
      </div>
    </div>
  </div>

  <!-- Formato esperado -->
  <div style="background:#0f1629; border-radius:8px; padding:14px; margin-bottom:16px; font-size:12px; font-family:monospace; color:#e2e8f0; overflow-x:auto;">
    <div style="color:#64748b; margin-bottom:6px;">Columnas esperadas (separadas por TAB al copiar de Excel):</div>
    <span style="color:#34d399;">Clave</span> &nbsp;│&nbsp;
    <span style="color:#60a5fa;">Grupo</span> &nbsp;│&nbsp;
    <span style="color:#f59e0b;">Materia</span> &nbsp;│&nbsp;
    <span style="color:#a78bfa;">Lunes</span> &nbsp;│&nbsp;
    <span style="color:#a78bfa;">Martes</span> &nbsp;│&nbsp;
    <span style="color:#a78bfa;">Miércoles</span> &nbsp;│&nbsp;
    <span style="color:#a78bfa;">Jueves</span> &nbsp;│&nbsp;
    <span style="color:#a78bfa;">Viernes</span> &nbsp;│&nbsp;
    <span style="color:#a78bfa;">Sábado</span>
    <div style="color:#64748b; margin-top:8px;">Ejemplo de celda de horario: <span style="color:#fbbf24;">07:00-09:00/M 15</span>
    &nbsp;(hora_inicio - hora_fin / aula)</div>
  </div>

  <div class="form-group">
    <label class="form-label">Pega aquí tu tabla (Ctrl+V):</label>
    <textarea id="tablaPegada" class="paste-area"
              placeholder="Haz clic aquí y presiona Ctrl+V para pegar tu tabla de Excel..."
              spellcheck="false"></textarea>
  </div>

  <div style="display:flex; gap:10px;">
    <button type="button" onclick="parsearTabla()" class="btn btn-primary">
      🔍 Analizar tabla
    </button>
    <button type="button" onclick="limpiar()" class="btn btn-outline">
      🗑 Limpiar
    </button>
  </div>
</div>

<!-- PASO 2: Vista previa -->
<div id="paso2" style="display:none;">
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">
      <div>
        <div class="card-title">👁 Vista previa — Grupos detectados</div>
        <div class="card-subtitle" id="subtitlePreview"></div>
      </div>
    </div>
    <div class="table-wrap">
      <table class="preview-table">
        <thead>
          <tr>
            <th>Clave</th>
            <th>Materia</th>
            <th>Grupo</th>
            <th>Horario detectado</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody id="tbodyPreview"></tbody>
      </table>
    </div>
  </div>

  <!-- Configuración de periodo y unidades -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">
      <div class="card-title">⚙️ Configuración para todos los grupos</div>
    </div>
    <div style="display:flex; gap:16px; flex-wrap:wrap;">
      <div class="form-group" style="flex:1; min-width:200px;">
        <label class="form-label">Periodo</label>
        <input type="text" id="cfgPeriodo" class="form-control"
               placeholder="Ej: Ene-Jun 2026" value="ENE-JUN 2026">
      </div>
      <div class="form-group" style="flex:0 0 160px;">
        <label class="form-label">Número de unidades</label>
        <input type="number" id="cfgUnidades" class="form-control" value="4" min="1" max="10">
      </div>
      <div class="form-group" style="flex:1; min-width:200px;">
        <label class="form-label">Docente asignado</label>
        <select id="cfgDocente" class="form-control">
          <option value="">— Selecciona docente —</option>
          <?php foreach ($docentes ?? [] as $d): ?>
            <option value="<?= (int)$d['id_docente'] ?>">
              <?= htmlspecialchars($d['nombre'], ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <form id="formImportar" action="index.php?accion=procesar_importar_horario" method="POST">
    <input type="hidden" name="grupos_json" id="gruposJson">
    <input type="hidden" name="periodo"     id="hdPeriodo">
    <input type="hidden" name="num_unidades" id="hdUnidades">
    <input type="hidden" name="id_docente"  id="hdDocente">
    <div style="display:flex; gap:10px; justify-content:flex-end;">
      <button type="button" onclick="volverPaso1()" class="btn btn-outline">← Volver</button>
      <button type="button" onclick="confirmarImportar()" class="btn btn-success"
              style="padding:10px 28px; font-size:15px;">
        ✅ Crear grupos automáticamente
      </button>
    </div>
  </form>
</div>

<script>
// Materias ya existentes en el sistema (para detectar duplicados)
const MATERIAS_EXISTENTES = <?= json_encode(array_column($materias ?? [], 'clave_materia'), JSON_UNESCAPED_UNICODE) ?>;
const GRUPOS_EXISTENTES   = <?= json_encode(array_map(fn($g) => $g['nombre_grupo'].'|'.$g['clave_materia'], $gruposExistentes ?? []), JSON_UNESCAPED_UNICODE) ?>;

let gruposParsed = [];

// ── Parser principal ─────────────────────────────────────────
function parsearTabla() {
  const texto = document.getElementById('tablaPegada').value.trim();
  if (!texto) { alert('Pega primero tu tabla.'); return; }

  const lineas = texto.split('\n').map(l => l.trimEnd());
  gruposParsed  = [];

  const DIAS = ['lunes','martes','miercoles','jueves','viernes','sabado'];

  // Detectar si la primera fila es encabezado
  let inicio = 0;
  const primeraFila = lineas[0].toLowerCase();
  if (primeraFila.includes('clave') || primeraFila.includes('grupo') || primeraFila.includes('materia')) {
    inicio = 1;
  }

  for (let i = inicio; i < lineas.length; i++) {
    const cols = lineas[i].split('\t');
    if (cols.length < 3) continue;

    const clave   = cols[0]?.trim();
    const grupo   = cols[1]?.trim();
    const materia = cols[2]?.trim();
    if (!clave || !grupo || !materia) continue;

    const horarios = [];
    // Columnas 3..8 = Lunes..Sábado
    for (let d = 0; d < DIAS.length; d++) {
      const celda = (cols[3 + d] || '').trim();
      if (!celda) continue;

      // Puede haber varias líneas en la celda (separadas por \n o por salto dentro de Excel)
      const entradas = celda.split('\n').map(e => e.trim()).filter(Boolean);
      for (const entrada of entradas) {
        const parsed = parsearCeldaHorario(entrada);
        if (parsed) { horarios.push({ dia: DIAS[d], ...parsed }); }
      }
    }

    const claveUpper = clave.toUpperCase();
    const existe = GRUPOS_EXISTENTES.includes(grupo + '|' + claveUpper);

    gruposParsed.push({ clave: claveUpper, grupo, materia, horarios, existe });
  }

  if (!gruposParsed.length) {
    alert('No se detectaron grupos válidos. Verifica que el formato sea el correcto.');
    return;
  }

  mostrarPreview();
}

// Parsea "07:00-09:00/M 15" → {hora_inicio, hora_fin, aula}
function parsearCeldaHorario(celda) {
  // Formato: HH:MM-HH:MM/AULA  o  HH:MM-HH:MM  o  HH:MM - HH:MM / AULA
  const re = /(\d{1,2}:\d{2})\s*[-–]\s*(\d{1,2}:\d{2})(?:\s*[\/\|]\s*(.+))?/;
  const m  = celda.match(re);
  if (!m) return null;
  return {
    hora_inicio: m[1].padStart(5, '0'),
    hora_fin:    m[2].padStart(5, '0'),
    aula:        m[3]?.trim() || null
  };
}

// ── Mostrar vista previa ─────────────────────────────────────
function mostrarPreview() {
  const tbody   = document.getElementById('tbodyPreview');
  const subtitle = document.getElementById('subtitlePreview');
  const nuevos  = gruposParsed.filter(g => !g.existe).length;
  const existen = gruposParsed.filter(g =>  g.existe).length;

  subtitle.textContent = `${gruposParsed.length} grupos detectados · ${nuevos} nuevos · ${existen} ya existen (se actualizará el horario)`;
  tbody.innerHTML = '';

  gruposParsed.forEach(g => {
    const tr = document.createElement('tr');
    tr.className = g.existe ? 'row-existe' : 'row-nuevo';

    const horarioHtml = g.horarios.map(h => `
      <span class="tag-dia">${h.dia.charAt(0).toUpperCase() + h.dia.slice(1)}</span>
      <span class="tag-hora">${h.hora_inicio}–${h.hora_fin}</span>
      ${h.aula ? `<span class="tag-aula">🏛 ${h.aula}</span>` : ''}
    `).join('<br>') || '<span style="color:#94a3b8;">Sin horario detectado</span>';

    tr.innerHTML = `
      <td><strong>${g.clave}</strong></td>
      <td>${g.materia}</td>
      <td><span class="badge badge-blue">${g.grupo}</span></td>
      <td>${horarioHtml}</td>
      <td>${g.existe
        ? '<span class="badge badge-amber">⚠ Ya existe · actualiza horario</span>'
        : '<span class="badge badge-green">✨ Nuevo</span>'}</td>
    `;
    tbody.appendChild(tr);
  });

  document.getElementById('paso1').style.display = 'none';
  document.getElementById('paso2').style.display = 'block';
}

function volverPaso1() {
  document.getElementById('paso1').style.display = 'block';
  document.getElementById('paso2').style.display = 'none';
}

function limpiar() {
  document.getElementById('tablaPegada').value = '';
  volverPaso1();
}

function confirmarImportar() {
  const periodo    = document.getElementById('cfgPeriodo').value.trim();
  const numUnidades = document.getElementById('cfgUnidades').value;
  const idDocente  = document.getElementById('cfgDocente').value;

  if (!periodo)   { alert('Ingresa el periodo.'); return; }
  if (!idDocente) { alert('Selecciona el docente.'); return; }

  document.getElementById('gruposJson').value = JSON.stringify(gruposParsed);
  document.getElementById('hdPeriodo').value  = periodo;
  document.getElementById('hdUnidades').value = numUnidades;
  document.getElementById('hdDocente').value  = idDocente;
  document.getElementById('formImportar').submit();
}
</script>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
