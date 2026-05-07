<?php
$pageTitle    = 'Módulo Grupos';
$accionActual = 'grupos_panel_docente';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<?php if (!empty($mensaje)): ?>
  <div class="alert alert-<?= $tipoMensaje ?? 'success' ?>"><?= $mensaje ?></div>
<?php endif; ?>

<?php if (empty($misGrupos)): ?>
  <div class="card" style="text-align:center; padding:48px 24px;">
    <div style="font-size:48px; margin-bottom:16px;">🏫</div>
    <p style="font-size:15px; font-weight:600; margin-bottom:8px;">Aún no tienes grupos</p>
    <p style="font-size:13px; color:#64748b; margin-bottom:20px;">Crea tu primer grupo o importa tu horario.</p>
    <div style="display:flex; gap:10px; justify-content:center;">
      <a href="index.php?accion=mis_grupos_docente"      class="btn btn-primary">➕ Crear grupo</a>
      <a href="index.php?accion=importar_horario_docente" class="btn btn-outline">📋 Importar horario</a>
    </div>
  </div>
<?php else: ?>

<?php foreach ($misGrupos as $g): ?>
  <?php
    $idG     = (int)$g['id_grupo'];
    $unids   = $unidadesPorGrupo[$idG] ?? [];
    $rubrica = $rubricaPorGrupo[$idG] ?? [];
    $sumPeso = array_sum(array_column($rubrica, 'peso'));
    $rubricaOk = abs($sumPeso - 100) < 0.01 && count($rubrica) > 0;
  ?>
  <div class="card" style="margin-bottom:24px;">

    <!-- ── CABECERA DEL GRUPO ── -->
    <div style="display:flex; align-items:center; justify-content:space-between;
                flex-wrap:wrap; gap:12px; margin-bottom:20px; padding-bottom:16px;
                border-bottom:1px solid #f1f5f9;">
      <div style="display:flex; align-items:center; gap:14px;">
        <div style="width:46px; height:46px; background:#eff6ff; border-radius:10px;
                    display:flex; align-items:center; justify-content:center; font-size:20px;">
          🏫
        </div>
        <div>
          <div style="font-size:16px; font-weight:700;">
            <?= htmlspecialchars($g['nombre_grupo'],ENT_QUOTES,'UTF-8') ?>
          </div>
          <div style="font-size:13px; color:#64748b; margin-top:2px;">
            📚 <?= htmlspecialchars($g['nombre_materia'],ENT_QUOTES,'UTF-8') ?>
            &nbsp;·&nbsp; 🗓 <?= htmlspecialchars($g['periodo'],ENT_QUOTES,'UTF-8') ?>
            &nbsp;·&nbsp; <?= (int)$g['total_alumnos'] ?> alumnos
          </div>
        </div>
      </div>
      <!-- Atajos rápidos -->
      <div style="display:flex; gap:6px; flex-wrap:wrap;">
        <a href="index.php?accion=alumnos_grupo_docente&id_grupo=<?= $idG ?>"
           class="btn btn-outline btn-sm">👥 Alumnos</a>
        <a href="index.php?accion=historial_grupo&id_grupo=<?= $idG ?>"
           class="btn btn-outline btn-sm">📅 Historial</a>
        <a href="index.php?accion=calendario_docente&id_grupo=<?= $idG ?>"
           class="btn btn-outline btn-sm">📆 Calendario</a>
        <a href="index.php?accion=pasar_lista&id_grupo=<?= $idG ?>&fecha=<?= date('Y-m-d') ?>"
           class="btn btn-primary btn-sm">✅ Pasar lista</a>
      </div>
    </div>

    <div class="grid-2" style="align-items:start; gap:24px;">

      <!-- ── UNIDADES ── -->
      <div>
        <div style="font-size:13px; font-weight:600; margin-bottom:12px; display:flex;
                    align-items:center; justify-content:space-between;">
          <span>📖 Unidades (<?= count($unids) ?>)</span>
          <form action="index.php?accion=actualizar_num_unidades" method="POST"
                style="display:flex; align-items:center; gap:6px;">
            <input type="hidden" name="id_grupo" value="<?= $idG ?>">
            <input type="number" name="num_unidades" value="<?= (int)$g['num_unidades'] ?>"
                   min="1" max="10" style="width:56px;" class="form-control"
                   title="Cambiar número de unidades">
            <button type="submit" class="btn btn-outline btn-sm">Aplicar</button>
          </form>
        </div>

        <?php foreach ($unids as $u): ?>
          <form action="index.php?accion=guardar_unidad_panel" method="POST"
                style="margin-bottom:10px;">
            <input type="hidden" name="id_unidad" value="<?= (int)$u['id_unidad'] ?>">
            <input type="hidden" name="id_grupo"  value="<?= $idG ?>">
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;
                        padding:10px 12px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
              <!-- Número -->
              <div style="width:28px; height:28px; background:<?= $u['cerrada']?'#dcfce7':'#eff6ff' ?>;
                          border-radius:6px; display:flex; align-items:center; justify-content:center;
                          font-weight:700; font-size:13px; color:<?= $u['cerrada']?'#166534':'#1d4ed8' ?>;
                          flex-shrink:0;">
                <?= (int)$u['numero_unidad'] ?>
              </div>
              <!-- Nombre -->
              <input type="text" name="nombre" value="<?= htmlspecialchars($u['nombre']??'',ENT_QUOTES,'UTF-8') ?>"
                     class="form-control" style="flex:1; min-width:120px; height:34px; font-size:13px;"
                     placeholder="Nombre de la unidad"
                     <?= $u['cerrada'] ? 'disabled' : '' ?>>
              <!-- Fecha fin -->
              <div style="flex:0 0 140px;">
                <input type="date" name="fecha_fin"
                       value="<?= htmlspecialchars($u['fecha_fin']??'',ENT_QUOTES,'UTF-8') ?>"
                       class="form-control" style="height:34px; font-size:12px;"
                       <?= $u['cerrada'] ? 'disabled' : '' ?>>
              </div>
              <!-- Estado / Botones -->
              <?php if ($u['cerrada']): ?>
                <span class="badge badge-green" style="flex-shrink:0;">✓ Cerrada</span>
                <a href="index.php?accion=reabrir_unidad&id_unidad=<?= (int)$u['id_unidad'] ?>&id_grupo=<?= $idG ?>"
                   class="btn btn-outline btn-sm" style="flex-shrink:0;">🔓</a>
              <?php else: ?>
                <button type="submit" class="btn btn-outline btn-sm" style="flex-shrink:0;">💾</button>
                <a href="index.php?accion=cerrar_unidad&id_unidad=<?= (int)$u['id_unidad'] ?>&id_grupo=<?= $idG ?>"
                   class="btn btn-sm" style="background:#f0fdf4;color:#166534;flex-shrink:0;"
                   onclick="return confirm('¿Cerrar esta unidad?')">🔒</a>
              <?php endif; ?>
            </div>
          </form>
        <?php endforeach; ?>
      </div>

      <!-- ── RÚBRICA ── -->
      <div>
        <div style="font-size:13px; font-weight:600; margin-bottom:12px; display:flex;
                    align-items:center; justify-content:space-between;">
          <span>📐 Rúbrica de evaluación</span>
          <?php if ($rubricaOk): ?>
            <span class="badge badge-green">✓ Configurada</span>
          <?php elseif (count($rubrica) > 0): ?>
            <span class="badge badge-red">⚠ Pesos: <?= $sumPeso ?>%</span>
          <?php else: ?>
            <span class="badge badge-gray">Sin definir</span>
          <?php endif; ?>
        </div>

        <form action="index.php?accion=guardar_rubrica" method="POST" id="formRubrica<?= $idG ?>">
          <input type="hidden" name="id_grupo" value="<?= $idG ?>">

          <div id="criteriosWrap<?= $idG ?>" style="display:flex; flex-direction:column; gap:6px;">
            <?php
            $criteriosInit = !empty($rubrica) ? $rubrica : Rubrica::plantillaDefault();
            foreach ($criteriosInit as $idx => $c):
            ?>
              <div class="criterio-row" style="display:flex; gap:6px; align-items:center;">
                <input type="text"
                       name="criterios[<?= $idx ?>][nombre]"
                       value="<?= htmlspecialchars($c['nombre'],ENT_QUOTES,'UTF-8') ?>"
                       class="form-control" style="flex:1; height:34px; font-size:12px;"
                       placeholder="Criterio">
                <input type="text"
                       name="criterios[<?= $idx ?>][descripcion]"
                       value="<?= htmlspecialchars($c['descripcion']??'',ENT_QUOTES,'UTF-8') ?>"
                       class="form-control" style="flex:1; height:34px; font-size:12px;"
                       placeholder="Descripción (opc.)">
                <div style="display:flex; align-items:center; gap:3px; flex-shrink:0;">
                  <input type="number"
                         name="criterios[<?= $idx ?>][peso]"
                         value="<?= (float)$c['peso'] ?>"
                         min="1" max="100" step="0.5"
                         class="form-control peso-input" data-grupo="<?= $idG ?>"
                         style="width:62px; height:34px; font-size:12px; text-align:center;">
                  <span style="font-size:12px; color:#64748b;">%</span>
                </div>
                <button type="button" onclick="quitarCriterio(this)"
                        style="background:none;border:none;cursor:pointer;color:#ef4444;
                               font-size:16px;padding:0 2px;">✕</button>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Total de pesos en vivo -->
          <div style="display:flex; align-items:center; justify-content:space-between;
                      margin-top:8px; font-size:12px;">
            <button type="button" onclick="agregarCriterio('<?= $idG ?>')"
                    class="btn btn-outline btn-sm">+ Criterio</button>
            <span>Total: <strong id="totalPeso<?= $idG ?>"><?= $sumPeso ?></strong>%
              <span id="pesoOk<?= $idG ?>"><?= $rubricaOk ? '✅' : '⚠️' ?></span>
            </span>
          </div>

          <button type="submit" class="btn btn-primary btn-sm" style="width:100%; margin-top:10px;">
            💾 Guardar rúbrica
          </button>
        </form>
      </div>

    </div><!-- /grid-2 -->
  </div><!-- /card -->
<?php endforeach; ?>
<?php endif; ?>

<script>
function actualizarTotal(idGrupo) {
  let total = 0;
  document.querySelectorAll(`.peso-input[data-grupo="${idGrupo}"]`).forEach(i => {
    total += parseFloat(i.value || 0);
  });
  total = Math.round(total * 100) / 100;
  const el = document.getElementById('totalPeso' + idGrupo);
  const ok = document.getElementById('pesoOk'    + idGrupo);
  if (el) el.textContent = total;
  if (ok) {
    ok.textContent = Math.abs(total - 100) < 0.01 ? '✅' : '⚠️';
    el.style.color = Math.abs(total - 100) < 0.01 ? '#166534' : '#991b1b';
  }
}

// Escuchar cambios en todos los inputs de peso
document.querySelectorAll('.peso-input').forEach(inp => {
  inp.addEventListener('input', () => actualizarTotal(inp.dataset.grupo));
});

let criterioIdx = 99; // índice para nuevos criterios
function agregarCriterio(idGrupo) {
  const wrap = document.getElementById('criteriosWrap' + idGrupo);
  const idx  = criterioIdx++;
  const div  = document.createElement('div');
  div.className = 'criterio-row';
  div.style.cssText = 'display:flex; gap:6px; align-items:center;';
  div.innerHTML = `
    <input type="text"  name="criterios[${idx}][nombre]"      class="form-control"
           style="flex:1;height:34px;font-size:12px;" placeholder="Criterio" required>
    <input type="text"  name="criterios[${idx}][descripcion]" class="form-control"
           style="flex:1;height:34px;font-size:12px;" placeholder="Descripción (opc.)">
    <div style="display:flex;align-items:center;gap:3px;flex-shrink:0;">
      <input type="number" name="criterios[${idx}][peso]" value="0" min="1" max="100" step="0.5"
             class="form-control peso-input" data-grupo="${idGrupo}"
             style="width:62px;height:34px;font-size:12px;text-align:center;">
      <span style="font-size:12px;color:#64748b;">%</span>
    </div>
    <button type="button" onclick="quitarCriterio(this)"
            style="background:none;border:none;cursor:pointer;color:#ef4444;font-size:16px;padding:0 2px;">✕</button>`;
  wrap.appendChild(div);
  div.querySelector('.peso-input').addEventListener('input', () => actualizarTotal(idGrupo));
}

function quitarCriterio(btn) {
  const row   = btn.closest('.criterio-row');
  const input = row.querySelector('.peso-input');
  const grupo = input ? input.dataset.grupo : null;
  row.remove();
  if (grupo) actualizarTotal(grupo);
}
</script>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
