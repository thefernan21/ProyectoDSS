<?php
$pageTitle    = 'Importar Alumnos';
$accionActual = 'importar_view';
require_once BASE_PATH . '/app/views/layout/sidebar.php';
?>

<?php if (!empty($mensaje)): ?>
  <div class="alert alert-<?= $tipoMensaje ?? 'success' ?>"><?= $mensaje ?></div>
<?php endif; ?>

<div class="grid-2" style="align-items:start;">

  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Subir lista de alumnos</div>
        <div class="card-subtitle">Archivo CSV exportado del SII institucional</div>
      </div>
    </div>

    <div class="alert alert-info">
      📋 El archivo debe ser <strong>CSV o TXT</strong>, máximo <strong>4 KB</strong>.<br>
      Formato esperado: <code>username, firstname, email, lastname</code>
    </div>

    <form action="index.php?accion=procesar_archivo" method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label class="form-label">Seleccionar archivo</label>
        <input type="file" name="archivo_sii" accept=".csv,.txt" class="form-control" required>
      </div>
      <div style="display:flex; gap:10px;">
        <button type="submit" class="btn btn-primary">📤 Importar alumnos</button>
        <a href="index.php?accion=ver_alumnos" class="btn btn-outline">👁 Ver directorio</a>
      </div>
    </form>
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">¿Cómo obtener el CSV del SII?</div>
        <div class="card-subtitle">Guía paso a paso</div>
      </div>
    </div>
    <ol style="padding-left:18px; display:flex; flex-direction:column; gap:12px; font-size:14px; color:#475569; line-height:1.6;">
      <li>Ingresa al Sistema de Información Institucional (SII)</li>
      <li>Navega al módulo de <strong>Grupos / Alumnos</strong></li>
      <li>Selecciona el grupo y semestre correspondiente</li>
      <li>Usa la opción <strong>Exportar → CSV</strong> o <strong>Descargar lista</strong></li>
      <li>Sube el archivo descargado aquí directamente</li>
    </ol>
    <div style="margin-top:20px; padding:14px; background:#f8fafc; border-radius:8px; font-size:12px; color:#64748b;">
      <strong>Ejemplo de encabezado esperado:</strong><br>
      <code style="color:#3b82f6;">username,firstname,email,lastname</code><br>
      <code>u300001,Juan,u300001@itlac.edu.mx,Pérez López</code>
    </div>
  </div>

</div>

<?php require_once BASE_PATH . '/app/views/layout/footer.php'; ?>
