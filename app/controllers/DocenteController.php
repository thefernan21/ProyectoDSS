<?php
require_once BASE_PATH . '/app/models/Grupo.php';
require_once BASE_PATH . '/app/models/Asistencia.php';
require_once BASE_PATH . '/app/models/Unidad.php';
require_once BASE_PATH . '/app/models/Actividad.php';

class DocenteController {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    private function getIdDocente() {
        $stmt = $this->pdo->prepare("SELECT id_docente FROM docentes WHERE id_usuario = ? LIMIT 1");
        $stmt->execute([$_SESSION['usuario_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id_docente'] : null;
    }

    private function getMisGrupos($idDocente) {
        if (!$idDocente) return [];
        $stmt = $this->pdo->prepare(
            "SELECT g.*, m.nombre AS nombre_materia
             FROM grupos g JOIN materias m ON m.id_materia = g.id_materia
             WHERE g.id_docente = ? AND g.activo = 1 ORDER BY g.nombre_grupo ASC"
        );
        $stmt->execute([$idDocente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function panelDocente() {
        $idDocente   = $this->getIdDocente();
        $misGrupos   = $this->getMisGrupos($idDocente);
        $totalGrupos = count($misGrupos);
        $totalAlumnos = 0;
        $modeloGrupo = new Grupo($this->pdo);
        foreach ($misGrupos as $g) $totalAlumnos += count($modeloGrupo->obtenerAlumnosPorGrupo($g['id_grupo']));

        $stmtHoy = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT id_grupo) FROM asistencias WHERE registrado_por=? AND fecha=?"
        );
        $stmtHoy->execute([$_SESSION['usuario_id'], date('Y-m-d')]);
        $clasesHoy = (int)$stmtHoy->fetchColumn();

        $modeloAsist  = new Asistencia($this->pdo);
        $ultimasListas = $modeloAsist->ultimasListasPorDocente($_SESSION['usuario_id']);
        require_once BASE_PATH . '/app/views/docentes/panel.php';
    }

    public function cargarLista() {
        $idGrupo   = isset($_GET['id_grupo']) ? (int)$_GET['id_grupo'] : null;
        $fecha     = $_GET['fecha'] ?? date('Y-m-d');
        $idDocente = $this->getIdDocente();
        $misGrupos = $this->getMisGrupos($idDocente);

        $modeloGrupo = new Grupo($this->pdo);
        $modeloAsist = new Asistencia($this->pdo);
        $modeloAct   = new Actividad($this->pdo);
        $modeloUnid  = new Unidad($this->pdo);

        $alumnos        = $idGrupo ? $modeloGrupo->obtenerAlumnosPorGrupo($idGrupo) : [];
        $asistenciasHoy = $idGrupo ? $modeloAsist->obtenerPorGrupoFecha($idGrupo, $fecha) : [];
        $actividadDia   = $idGrupo ? $modeloAct->obtenerActividadDia($idGrupo, $fecha) : null;
        $tareaDia       = $idGrupo ? $modeloAct->obtenerTareaDia($idGrupo, $fecha) : null;
        $califActividadHoy = $idGrupo ? $modeloAct->califActividadDia($idGrupo, $fecha) : [];
        $califTareaHoy     = $idGrupo ? $modeloAct->califTareaDia($idGrupo, $fecha) : [];
        $unidades          = $idGrupo ? $modeloUnid->obtenerPorGrupo($idGrupo) : [];

        // Determinar unidad activa (la que tiene la fecha más reciente sin cerrar, o la primera)
        $idUnidadGet = isset($_GET['id_unidad']) ? (int)$_GET['id_unidad'] : 0;
        $unidadActual = null;
        if ($idUnidadGet) {
            foreach ($unidades as $u) { if ($u['id_unidad'] == $idUnidadGet) { $unidadActual = $u; break; } }
        }
        if (!$unidadActual) {
            foreach ($unidades as $u) { if (!$u['cerrada']) { $unidadActual = $u; break; } }
        }
        if (!$unidadActual && !empty($unidades)) $unidadActual = end($unidades);

        $nombreGrupo = '';
        foreach ($misGrupos as $g) {
            if ($g['id_grupo'] == $idGrupo) {
                $nombreGrupo = $g['nombre_grupo'] . ' — ' . $g['nombre_materia']; break;
            }
        }
        require_once BASE_PATH . '/app/views/docentes/pasar_lista.php';
    }

    /** Guarda asistencia + actividad + tarea en una sola llamada.
     *  Actividades y tareas son TOTALMENTE OPCIONALES:
     *  - Solo se guardan si el docente escribió un nombre.
     *  - Además requieren una unidad válida (id > 0).
     *  La asistencia SÍ es obligatoria cada sesión.
     */
    public function guardarSesion($datos) {
        $idGrupo         = (int)($datos['id_grupo']  ?? 0);
        $idUnidad        = (int)($datos['id_unidad'] ?? 0);
        $fecha           = $datos['fecha'] ?? date('Y-m-d');
        $guardados       = 0;
        $nombreActividad = trim($datos['actividad_nombre'] ?? '');
        $nombreTarea     = trim($datos['tarea_nombre']     ?? '');

        // 1. Asistencias — siempre obligatorias
        $modeloAsist = new Asistencia($this->pdo);
        foreach ($datos['asistencia'] ?? [] as $idAlumno => $tipo) {
            $modeloAsist->guardarOActualizar($idAlumno, $idGrupo, $fecha, $tipo, $_SESSION['usuario_id']);
            $guardados++;
        }

        $modeloAct = new Actividad($this->pdo);

        // 2. Actividad — OPCIONAL: solo si tiene nombre y unidad válida
        if ($idUnidad > 0 && !empty($nombreActividad)) {
            $modeloAct->guardarActividad(
                $idGrupo, $idUnidad, $fecha,
                $nombreActividad,
                $datos['actividad_calif'] ?? [],
                $_SESSION['usuario_id']
            );
        }

        // 3. Tarea — OPCIONAL: solo si tiene nombre y unidad válida
        if ($idUnidad > 0 && !empty($nombreTarea)) {
            $modeloAct->guardarTarea(
                $idGrupo, $idUnidad, $fecha,
                $nombreTarea,
                $datos['tarea_calif'] ?? [],
                $_SESSION['usuario_id']
            );
        }

        $extras = [];
        if ($idUnidad > 0 && !empty($nombreActividad)) $extras[] = "actividad registrada";
        if ($idUnidad > 0 && !empty($nombreTarea))     $extras[] = "tarea registrada";
        $extraStr    = !empty($extras) ? " · " . implode(" · ", $extras) : "";
        $mensaje     = "✅ Sesión guardada: $guardados asistencias para el $fecha$extraStr.";
        $tipoMensaje = 'success';

        $_GET['id_grupo']  = $idGrupo;
        $_GET['id_unidad'] = $idUnidad;
        $_GET['fecha']     = $fecha;
        $this->cargarLista();
    }

    public function historialGrupo() {
        $idGrupo   = isset($_GET['id_grupo']) ? (int)$_GET['id_grupo'] : null;
        $vista     = $_GET['vista'] ?? 'sesiones';
        $idDocente = $this->getIdDocente();
        $misGrupos = $this->getMisGrupos($idDocente);
        $fechas    = [];
        $resumenAlumnos = [];

        if ($idGrupo) {
            if ($vista === 'sesiones') {
                // Obtener todas las fechas con resumen
                $stmt = $this->pdo->prepare(
                    "SELECT
                        a.fecha,
                        SUM(a.tipo_asistencia='asistencia')   AS presentes,
                        SUM(a.tipo_asistencia='retardo')      AS retardos,
                        SUM(a.tipo_asistencia='inasistencia') AS faltas,
                        (SELECT nombre FROM actividades_clase WHERE id_grupo=? AND fecha=a.fecha LIMIT 1) AS actividad,
                        (SELECT nombre FROM tareas_clase      WHERE id_grupo=? AND fecha=a.fecha LIMIT 1) AS tarea
                     FROM asistencias a WHERE a.id_grupo=? GROUP BY a.fecha ORDER BY a.fecha DESC"
                );
                $stmt->execute([$idGrupo, $idGrupo, $idGrupo]);
                $fechas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Resumen por alumno
                $stmt = $this->pdo->prepare(
                    "SELECT al.numero_control, al.nombre,
                            COUNT(a.id_asistencia) AS total_clases,
                            SUM(a.tipo_asistencia='asistencia')   AS asistencias,
                            SUM(a.tipo_asistencia='retardo')      AS retardos,
                            SUM(a.tipo_asistencia='inasistencia') AS faltas
                     FROM grupo_alumnos ga
                     JOIN alumnos al ON al.id_alumno = ga.id_alumno
                     LEFT JOIN asistencias a ON a.id_alumno=al.id_alumno AND a.id_grupo=?
                     WHERE ga.id_grupo=? GROUP BY al.id_alumno ORDER BY al.nombre ASC"
                );
                $stmt->execute([$idGrupo, $idGrupo]);
                $resumenAlumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        require_once BASE_PATH . '/app/views/docentes/historial_grupo.php';
    }

    public function calificacionesDocente() {
        $idGrupo   = isset($_GET['id_grupo']) ? (int)$_GET['id_grupo'] : null;
        $idUnidad  = isset($_GET['id_unidad']) ? (int)$_GET['id_unidad'] : null;
        $idDocente = $this->getIdDocente();
        $misGrupos = $this->getMisGrupos($idDocente);
        $unidades  = $idGrupo ? (new Unidad($this->pdo))->obtenerPorGrupo($idGrupo) : [];
        $unidadSel = null;
        $resumen   = [];

        if ($idUnidad) {
            $modeloUnid = new Unidad($this->pdo);
            $unidadSel  = $modeloUnid->obtenerPorId($idUnidad);
            $resumen    = (new Actividad($this->pdo))->resumenUnidad($idGrupo, $idUnidad);
        } elseif (!empty($unidades)) {
            // Seleccionar la primera no cerrada por defecto
            foreach ($unidades as $u) {
                if (!$u['cerrada']) { $unidadSel = $u; break; }
            }
            if (!$unidadSel) $unidadSel = $unidades[0];
            if ($unidadSel) {
                $_GET['id_unidad'] = $unidadSel['id_unidad'];
                $resumen = (new Actividad($this->pdo))->resumenUnidad($idGrupo, $unidadSel['id_unidad']);
            }
        }
        require_once BASE_PATH . '/app/views/docentes/calificaciones.php';
    }

    public function unidadesDocente() {
        $idGrupo   = isset($_GET['id_grupo']) ? (int)$_GET['id_grupo'] : null;
        $idDocente = $this->getIdDocente();
        $misGrupos = $this->getMisGrupos($idDocente);
        $unidades  = $idGrupo ? (new Unidad($this->pdo))->obtenerPorGrupo($idGrupo) : [];
        require_once BASE_PATH . '/app/views/docentes/unidades.php';
    }

    public function guardarUnidad($datos) {
        $idUnidad = (int)($datos['id_unidad'] ?? 0);
        $idGrupo  = (int)($datos['id_grupo']  ?? 0);
        $modeloUnid = new Unidad($this->pdo);
        $modeloUnid->actualizarNombreYFecha($idUnidad, $datos['nombre'] ?? '', $datos['fecha_fin'] ?? '');
        $mensaje     = '✅ Unidad actualizada.';
        $tipoMensaje = 'success';
        $_GET['id_grupo'] = $idGrupo;
        $misGrupos = $this->getMisGrupos($this->getIdDocente());
        $unidades  = $modeloUnid->obtenerPorGrupo($idGrupo);
        require_once BASE_PATH . '/app/views/docentes/unidades.php';
    }

    public function cerrarUnidad($idUnidad, $idGrupo) {
        (new Unidad($this->pdo))->cerrar($idUnidad);
        header("Location: index.php?accion=calificaciones_docente&id_grupo=$idGrupo&id_unidad=$idUnidad");
        exit();
    }

    public function reabrirUnidad($idUnidad, $idGrupo) {
        (new Unidad($this->pdo))->reabrir($idUnidad);
        header("Location: index.php?accion=calificaciones_docente&id_grupo=$idGrupo&id_unidad=$idUnidad");
        exit();
    }
}
?>
