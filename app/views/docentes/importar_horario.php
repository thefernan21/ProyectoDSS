<?php
$pageTitle    = 'Importar Mi Horario';
$accionActual = 'importar_horario_docente';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<style>
  .paste-area {
    width: 100%; min-height: 160px;
    border: 2px dashed var(--border); border-radius: 10px;
    padding: 20px; font-family: monospace; font-size: 13px;
    resize: vertical; background: #f8fafc; color: var(--text-main);
    transition: border-color .2s;
  }
  .paste-area:focus { outline:none; border-color:var(--accent); background:#fff; }
  .preview-table { border-collapse:collapse; width:100%; font-size:13px; }
  .preview-table th { background:#0f1629; color:#fff; padding:8px 12px; text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.05em; }
  .preview-table td { padding:8px 12px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
  .preview-table tr:hover td { background:#f8fafc; }
  .tag-dia  { display:inline-block; background:#eff6ff; color:#1d4ed8; border-radius:5px; padding:2px 7px; font-size:11px; font-weight:600; margin:2px; }
  .tag-hora { color:#64748b; font-size:12px; }
  .tag-aula { display:inline-block; background:#f0fdf4; color:#166534; border-radius:5px; padding:2px 7px; font-size:11px; margin-left:4px; }
  .row-nuevo  { background:#f0fdf4 !important; }
  .row-existe { background:#fef9c3 !important; }
</style>

<?php if (!empty($mensaje)): ?>
  <div class="alert alert-<?= $tipoMensaje ?? 'success' ?>"><?= $mensaje ?></div>
<?php endif; ?>

<!-- PASO 1 -->
<div class="card" style="margin-bottom:20px;" id="paso1">
  <div class="card-header">
    <div>
      <div class="card-title">📋 Pegar mi tabla de horarios</div>
      <div class="card-subtitle">
        Copia las filas de tu horario desde Excel (Ctrl+C) y pégalas aquí.
        El sistema creará tus grupos y horario automáticamente.
      </div>
    </div>
  </div>

  <div style="background:#0f1629; border-radius:8px; padding:14px; margin-bottom:16px;
              font-size:12px; font-family:monospace; color:#e2e8f0; overflow-x:auto;">
    <div style="color:#64748b; margin-bottom:6px;">Formato esperado (copia solo las filas de datos, sin encabezado):</div>
    <span style="color:#34d399;">INC2505</span><span style="color:#475569;">[TAB]</span><span style="color:#60a5fa;">81T</span><span style="color:#475569;">[TAB]</span><span style="color:#f59e0b;">ANÁLISIS Y EXP.</span><span style="color:#475569;">[TAB]</span><span style="color:#a78bfa;">07:00-09:00/M 15</span><span style="color:#475569;">[TAB]</span><span style="color:#475569;">...</span>
    <div style="color:#64748b; margin-top:6px;">Las columnas de días vacías se dejan en blanco · Celdas de horario: <span style="color:#fbbf24;">HH:MM-HH:MM/AULA</span></div>
  </div>

  <div class="form-group">
    <label class="form-label">Pega aquí tu tabla (Ctrl+V):</label>
    <textarea id="tablaPegada" class="paste-area"
              placeholder="Haz clic aquí y presiona Ctrl+V…"
              spellcheck="false"></textarea>
  </div>
  <div style="display:flex; gap:10px;">
    <button type="button" onclick="parsearTabla()" class="btn btn-primary">🔍 Analizar tabla</button>
    <button type="button" onclick="limpiar()" class="btn btn-outline">🗑 Limpiar</button>
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
            <th>Clave</th><th>Materia</th><th>Grupo</th><th>Horario detectado</th><th>Estado</th>
          </tr>
        </thead>
        <tbody id="tbodyPreview"></tbody>
      </table>
    </div>
  </div>

  <!-- Config -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">
      <div class="card-title">⚙️ Configuración</div>
    </div>
    <div style="display:flex; gap:16px; flex-wrap:wrap;">
      <div class="form-group" style="flex:1; min-width:200px;">
        <label class="form-label">Periodo</label>
        <input type="text" id="cfgPeriodo" class="form-control"
               placeholder="Ej: Ene-Jun 2026" value="ENE-JUN 2026">
      </div>
      <div class="form-group" style="flex:0 0 180px;">
        <label class="form-label">Número de unidades por grupo</label>
        <input type="number" id="cfgUnidades" class="form-control" value="4" min="1" max="10">
      </div>
    </div>
    <div class="alert alert-info" style="margin-top:8px; margin-bottom:0;">
      📌 Los grupos se crearán asignados a tu cuenta automáticamente.
    </div>
  </div>

  <form id="formImportar" action="index.php?accion=procesar_importar_horario_docente" method="POST">
    <input type="hidden" name="grupos_json"   id="gruposJson">
    <input type="hidden" name="periodo"       id="hdPeriodo">
    <input type="hidden" name="num_unidades"  id="hdUnidades">
    <div style="display:flex; gap:10px; justify-content:flex-end;">
      <button type="button" onclick="volverPaso1()" class="btn btn-outline">← Volver</button>
      <button type="button" onclick="confirmarImportar()" class="btn btn-success"
              style="padding:10px 28px; font-size:15px;">
        ✅ Crear mis grupos
      </button>
    </div>
  </form>
</div>

<script>
const GRUPOS_EXISTENTES = <?= json_encode(
    array_map(fn($g) => $g['nombre_grupo'].'|'.strtoupper($g['clave_materia']), $gruposExistentes ?? []),
    JSON_UNESCAPED_UNICODE
) ?>;

let gruposParsed = [];

function parsearTabla() {
  const texto = document.getElementById('tablaPegada').value.trim();
  if (!texto) { alert('Pega primero tu tabla.'); return; }

  const DIAS = ['lunes','martes','miercoles','jueves','viernes','sabado'];
  gruposParsed  = [];

  const lineas = texto.split('\n').map(l => l.trimEnd());
  let inicio   = 0;
  // Saltar encabezado si lo hay
  if (lineas[0].toLowerCase().includes('clave') || lineas[0].toLowerCase().includes('grupo')) inicio = 1;

  for (let i = inicio; i < lineas.length; i++) {
    const cols   = lineas[i].split('\t');
    const clave  = cols[0]?.trim();
    const grupo  = cols[1]?.trim();
    const nombre = cols[2]?.trim();
    if (!clave || !grupo || !nombre) continue;

    const horarios = [];
    for (let d = 0; d < DIAS.length; d++) {
      const celda   = (cols[3 + d] || '').trim();
      if (!celda) continue;
      // Puede haber múltiples entradas por celda
      const entradas = celda.split(/[\n\r]+/).map(e => e.trim()).filter(Boolean);
      for (const e of entradas) {
        const p = parsearCelda(e);
        if (p) horarios.push({ dia: DIAS[d], ...p });
      }
    }

    const claveUp = clave.toUpperCase();
    gruposParsed.push({
      clave: claveUp, grupo, materia: nombre, horarios,
      existe: GRUPOS_EXISTENTES.includes(grupo + '|' + claveUp)
    });
  }

  if (!gruposParsed.length) {
    alert('No se detectaron grupos válidos. Verifica el formato.');
    return;
  }
  mostrarPreview();
}

function parsearCelda(celda) {
  const m = celda.match(/(\d{1,2}:\d{2})\s*[-–]\s*(\d{1,2}:\d{2})(?:\s*[\/|]\s*(.+))?/);
  if (!m) return null;
  return { hora_inicio: m[1].padStart(5,'0'), hora_fin: m[2].padStart(5,'0'), aula: m[3]?.trim() || null };
}

function mostrarPreview() {
  const tbody   = document.getElementById('tbodyPreview');
  const subtitle = document.getElementById('subtitlePreview');
  const nuevos  = gruposParsed.filter(g => !g.existe).length;
  const existen = gruposParsed.filter(g =>  g.existe).length;
  subtitle.textContent = `${gruposParsed.length} grupos · ${nuevos} nuevos · ${existen} ya existen (se actualizará el horario)`;
  tbody.innerHTML = '';

  gruposParsed.forEach(g => {
    const tr      = document.createElement('tr');
    tr.className  = g.existe ? 'row-existe' : 'row-nuevo';
    const horHtml = g.horarios.length
      ? g.horarios.map(h =>
          `<span class="tag-dia">${h.dia.charAt(0).toUpperCase()+h.dia.slice(1)}</span>
           <span class="tag-hora">${h.hora_inicio}–${h.hora_fin}</span>
           ${h.aula ? `<span class="tag-aula">🏛 ${h.aula}</span>` : ''}`
        ).join('<br>')
      : '<span style="color:#94a3b8;">Sin horario detectado</span>';

    tr.innerHTML = `
      <td><strong>${g.clave}</strong></td>
      <td>${g.materia}</td>
      <td><span class="badge badge-blue">${g.grupo}</span></td>
      <td>${horHtml}</td>
      <td>${g.existe
        ? '<span class="badge badge-amber">⚠ Existe · actualiza horario</span>'
        : '<span class="badge badge-green">✨ Nuevo</span>'}</td>`;
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
  gruposParsed = [];
  volverPaso1();
}

function confirmarImportar() {
  const periodo = document.getElementById('cfgPeriodo').value.trim();
  if (!periodo) { alert('Ingresa el periodo.'); return; }
  document.getElementById('gruposJson').value  = JSON.stringify(gruposParsed);
  document.getElementById('hdPeriodo').value   = periodo;
  document.getElementById('hdUnidades').value  = document.getElementById('cfgUnidades').value;
  document.getElementById('formImportar').submit();
}
</script>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
