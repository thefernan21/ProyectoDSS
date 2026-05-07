<?php
session_start();
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/controllers/AuthController.php';
require_once BASE_PATH . '/app/controllers/AlumnoController.php';
require_once BASE_PATH . '/app/controllers/AdminController.php';
require_once BASE_PATH . '/app/controllers/DocenteController.php';

$accion = $_GET['accion'] ?? 'login_view';

function soloRol(...$roles) {
    if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], $roles)) {
        http_response_code(403);
        die("<div style='font-family:sans-serif;text-align:center;margin-top:80px;'>
          <h2 style='color:#ef4444;'>⛔ Acceso denegado</h2>
          <p style='color:#64748b;'>No tienes permiso para esta sección.</p>
          <a href='index.php?accion=login_view' style='color:#3b82f6;'>← Volver</a></div>");
    }
}
function sesionActiva() {
    if (!isset($_SESSION['usuario_id'])) { header("Location: index.php?accion=login_view"); exit(); }
}

switch ($accion) {

    // ── AUTH ──────────────────────────────────────────────────
    case 'login_view':
        if (isset($_SESSION['rol'])) {
            header("Location: index.php?accion=" . match($_SESSION['rol']) {
                'Admin'=>'dashboard_admin','Docente'=>'panel_docente','Alumno'=>'panel_alumno',default=>'login_view'
            }); exit();
        }
        require_once BASE_PATH . '/app/views/auth/login.php'; break;

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') (new AuthController($pdo))->procesarLogin($_POST);
        header("Location: index.php?accion=login_view"); exit();

    case 'logout':
        (new AuthController($pdo))->cerrarSesion(); break;

    // ── ADMIN ─────────────────────────────────────────────────
    case 'dashboard_admin':
        sesionActiva(); soloRol('Admin');
        (new AdminController($pdo))->dashboard(); break;

    case 'ver_materias':
        sesionActiva(); soloRol('Admin');
        (new AdminController($pdo))->verMaterias(); break;

    case 'guardar_materia':
        sesionActiva(); soloRol('Admin');
        ($_SERVER['REQUEST_METHOD']==='POST') ? (new AdminController($pdo))->guardarMateria($_POST) : header("Location: index.php?accion=ver_materias");
        break;

    case 'ver_grupos':
        sesionActiva(); soloRol('Admin');
        (new AdminController($pdo))->verGrupos(); break;

    case 'guardar_grupo':
        sesionActiva(); soloRol('Admin');
        ($_SERVER['REQUEST_METHOD']==='POST') ? (new AdminController($pdo))->guardarGrupo($_POST) : header("Location: index.php?accion=ver_grupos");
        break;

    case 'eliminar_grupo':
        sesionActiva(); soloRol('Admin');
        $res = (new AdminController($pdo))->eliminarGrupo((int)($_GET['id_grupo']??0));
        header("Location: index.php?accion=ver_grupos"); exit();

    case 'cargar_lista_grupo':
        sesionActiva(); soloRol('Admin');
        (new AdminController($pdo))->cargarListaGrupo((int)($_GET['id_grupo']??0)); break;

    case 'procesar_lista_grupo':
        sesionActiva(); soloRol('Admin');
        (isset($_FILES['archivo_sii'])) ? (new AdminController($pdo))->procesarListaGrupo($_POST, $_FILES['archivo_sii']) : header("Location: index.php?accion=ver_grupos");
        break;

    case 'ver_alumnos_grupo':
        sesionActiva(); soloRol('Admin');
        (new AdminController($pdo))->verAlumnosGrupo((int)($_GET['id_grupo']??0)); break;

    case 'ver_alumnos':
        sesionActiva(); soloRol('Admin','Docente');
        (new AlumnoController($pdo))->listarAlumnos(); break;

    case 'eliminar_alumno':
        sesionActiva(); soloRol('Admin');
        (new AdminController($pdo))->eliminarAlumno((int)($_GET['id_alumno']??0));
        header("Location: index.php?accion=ver_alumnos"); exit();

    case 'ver_docentes':
        sesionActiva(); soloRol('Admin');
        (new AdminController($pdo))->verDocentes(); break;

    case 'guardar_docente':
        sesionActiva(); soloRol('Admin');
        ($_SERVER['REQUEST_METHOD']==='POST') ? (new AdminController($pdo))->guardarDocente($_POST) : header("Location: index.php?accion=ver_docentes");
        break;

    case 'eliminar_docente':
        sesionActiva(); soloRol('Admin');
        (new AdminController($pdo))->eliminarDocente((int)($_GET['id_docente']??0));
        header("Location: index.php?accion=ver_docentes"); exit();

    case 'importar_horario':
        sesionActiva(); soloRol('Admin');
        (new AdminController($pdo))->importarHorarioView(); break;

    case 'procesar_importar_horario':
        sesionActiva(); soloRol('Admin');
        ($_SERVER['REQUEST_METHOD']==='POST') ? (new AdminController($pdo))->procesarImportarHorario($_POST) : header("Location: index.php?accion=importar_horario");
        break;

    case 'calendario_docente':
        sesionActiva(); soloRol('Docente');
        (new DocenteController($pdo))->calendarioDocente(); break;

    case 'horario_docente':
        sesionActiva(); soloRol('Docente');
        (new DocenteController($pdo))->horarioDocente(); break;

    case 'importar_horario_docente':
        sesionActiva(); soloRol('Docente');
        (new DocenteController($pdo))->importarHorarioDocenteView(); break;

    case 'procesar_importar_horario_docente':
        sesionActiva(); soloRol('Docente');
        ($_SERVER['REQUEST_METHOD']==='POST')
          ? (new DocenteController($pdo))->procesarImportarHorarioDocente($_POST)
          : header("Location: index.php?accion=importar_horario_docente");
        break;



    case 'grupos_panel_docente':
        sesionActiva(); soloRol('Docente');
        (new DocenteController($pdo))->gruposPanel(); break;

    case 'actualizar_num_unidades':
        sesionActiva(); soloRol('Docente');
        ($_SERVER['REQUEST_METHOD']==='POST')
            ? (new DocenteController($pdo))->actualizarNumUnidades($_POST)
            : header("Location: index.php?accion=grupos_panel_docente");
        break;

    case 'guardar_unidad_panel':
        sesionActiva(); soloRol('Docente');
        ($_SERVER['REQUEST_METHOD']==='POST')
            ? (new DocenteController($pdo))->guardarUnidadPanel($_POST)
            : header("Location: index.php?accion=grupos_panel_docente");
        break;

    case 'guardar_rubrica':
        sesionActiva(); soloRol('Docente');
        ($_SERVER['REQUEST_METHOD']==='POST')
            ? (new DocenteController($pdo))->guardarRubrica($_POST)
            : header("Location: index.php?accion=grupos_panel_docente");
        break;

    case 'calificaciones_admin':
        sesionActiva(); soloRol('Admin');
        (new AdminController($pdo))->calificacionesAdmin(); break;

    // ── DOCENTE ───────────────────────────────────────────────
    case 'panel_docente':
        sesionActiva(); soloRol('Docente');
        (new DocenteController($pdo))->panelDocente(); break;

    case 'pasar_lista':
    case 'cargar_lista':
        sesionActiva(); soloRol('Docente');
        (new DocenteController($pdo))->cargarLista(); break;

    case 'guardar_sesion':
        sesionActiva(); soloRol('Docente');
        ($_SERVER['REQUEST_METHOD']==='POST') ? (new DocenteController($pdo))->guardarSesion($_POST) : header("Location: index.php?accion=panel_docente");
        break;

    case 'historial_grupo':
        sesionActiva(); soloRol('Docente');
        (new DocenteController($pdo))->historialGrupo(); break;

    case 'calificaciones_docente':
        sesionActiva(); soloRol('Docente');
        (new DocenteController($pdo))->calificacionesDocente(); break;

    case 'unidades_docente':
        sesionActiva(); soloRol('Docente');
        (new DocenteController($pdo))->unidadesDocente(); break;

    case 'guardar_unidad':
        sesionActiva(); soloRol('Docente');
        ($_SERVER['REQUEST_METHOD']==='POST') ? (new DocenteController($pdo))->guardarUnidad($_POST) : header("Location: index.php?accion=unidades_docente");
        break;

    case 'cerrar_unidad':
        sesionActiva(); soloRol('Docente');
        (new DocenteController($pdo))->cerrarUnidad((int)($_GET['id_unidad']??0),(int)($_GET['id_grupo']??0)); break;

    case 'editar_asistencias':
        sesionActiva(); soloRol('Docente');
        (new DocenteController($pdo))->editarAsistencias(); break;

    case 'reabrir_unidad':
        sesionActiva(); soloRol('Docente');
        (new DocenteController($pdo))->reabrirUnidad((int)($_GET['id_unidad']??0),(int)($_GET['id_grupo']??0)); break;

    // ── DOCENTE: GRUPOS Y ALUMNOS ────────────────────────────
    case 'mis_grupos':
        sesionActiva(); soloRol('Docente');
        (new DocenteController($pdo))->misGrupos(); break;

    case 'guardar_grupo_docente':
        sesionActiva(); soloRol('Docente');
        ($_SERVER['REQUEST_METHOD']==='POST')
            ? (new DocenteController($pdo))->guardarGrupoDocente($_POST)
            : header("Location: index.php?accion=mis_grupos");
        break;

    case 'alumnos_grupo_docente':
        sesionActiva(); soloRol('Docente');
        (new DocenteController($pdo))->alumnosGrupoDocente((int)($_GET['id_grupo']??0)); break;

    case 'procesar_lista_grupo_docente':
        sesionActiva(); soloRol('Docente');
        ($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['archivo_sii']))
            ? (new DocenteController($pdo))->procesarListaGrupoDocente($_POST, $_FILES['archivo_sii'])
            : header("Location: index.php?accion=mis_grupos");
        break;

    case 'agregar_alumno_manual':
        sesionActiva(); soloRol('Docente');
        ($_SERVER['REQUEST_METHOD']==='POST')
            ? (new DocenteController($pdo))->agregarAlumnoManual($_POST)
            : header("Location: index.php?accion=mis_grupos");
        break;

    case 'buscar_alumno_docente':
        sesionActiva(); soloRol('Docente');
        (new DocenteController($pdo))->buscarAlumnoDocente(
            (int)($_GET['id_grupo']??0),
            trim($_GET['q'] ?? '')
        );
        break;

    case 'inscribir_alumno_existente':
        sesionActiva(); soloRol('Docente');
        ($_SERVER['REQUEST_METHOD']==='POST')
            ? (new DocenteController($pdo))->inscribirAlumnoExistente($_POST)
            : header("Location: index.php?accion=mis_grupos");
        break;

    case 'desinscribir_alumno':
        sesionActiva(); soloRol('Docente');
        (new DocenteController($pdo))->desinscribirAlumno(
            (int)($_GET['id_grupo']??0),
            (int)($_GET['id_alumno']??0)
        );
        break;

    // ── ALUMNO ────────────────────────────────────────────────
    case 'panel_alumno':
        sesionActiva(); soloRol('Alumno');
        (new AlumnoController($pdo))->panelAlumno(); break;

    case 'mis_asistencias':
        sesionActiva(); soloRol('Alumno');
        (new AlumnoController($pdo))->misAsistencias(); break;

    case 'mis_calificaciones':
        sesionActiva(); soloRol('Alumno');
        (new AlumnoController($pdo))->misCalificaciones(); break;

    case 'mi_historial':
        sesionActiva(); soloRol('Alumno');
        (new AlumnoController($pdo))->miHistorial(); break;

    default:
        header("Location: index.php?accion=login_view"); exit();
}
?>
