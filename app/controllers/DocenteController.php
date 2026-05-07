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

    /** Vista dedicada para editar sesiones pasadas */
    public function editarAsistencias() {
        $idGrupo  = isset($_GET['id_grupo']) ? (int)$_GET['id_grupo'] : null;
        $fechaSel = $_GET['fecha'] ?? null;
        $idDocente = $this->getIdDocente();
        $misGrupos = $this->getMisGrupos($idDocente);

        $fechasSesiones   = [];
        $alumnos          = [];
        $asistenciasHoy   = [];
        $actividadDia     = null;
        $tareaDia         = null;
        $califActividadHoy = [];
        $califTareaHoy    = [];
        $unidades         = [];
        $unidadActual     = null;
        $nombreGrupo      = '';

        if ($idGrupo) {
            $modeloAct  = new Actividad($this->pdo);
            $modeloUnid = new Unidad($this->pdo);
            $modeloGrupo = new Grupo($this->pdo);

            // Todas las fechas con sesiones registradas para este grupo
            $fechasSesiones = $modeloAct->obtenerFechasSesiones($idGrupo);
            $unidades       = $modeloUnid->obtenerPorGrupo($idGrupo);

            foreach ($misGrupos as $g) {
                if ($g['id_grupo'] == $idGrupo) {
                    $nombreGrupo = $g['nombre_grupo'] . ' — ' . $g['nombre_materia'];
                    break;
                }
            }

            if ($fechaSel) {
                $modeloAsist = new Asistencia($this->pdo);
                $alumnos     = $modeloGrupo->obtenerAlumnosPorGrupo($idGrupo);
                $asistenciasHoy    = $modeloAsist->obtenerPorGrupoFecha($idGrupo, $fechaSel);
                $actividadDia      = $modeloAct->obtenerActividadDia($idGrupo, $fechaSel);
                $tareaDia          = $modeloAct->obtenerTareaDia($idGrupo, $fechaSel);
                $califActividadHoy = $modeloAct->califActividadDia($idGrupo, $fechaSel);
                $califTareaHoy     = $modeloAct->califTareaDia($idGrupo, $fechaSel);

                // Determinar unidad activa
                $idUnidadGet = isset($_GET['id_unidad']) ? (int)$_GET['id_unidad'] : 0;
                foreach ($unidades as $u) {
                    if ($idUnidadGet && $u['id_unidad'] == $idUnidadGet) { $unidadActual = $u; break; }
                }
                if (!$unidadActual) {
                    foreach ($unidades as $u) { if (!$u['cerrada']) { $unidadActual = $u; break; } }
                }
                if (!$unidadActual && !empty($unidades)) $unidadActual = $unidades[0];
            }
        }

        require_once BASE_PATH . '/app/views/docentes/editar_asistencias.php';
    }
    public function horarioDocente() {
        require_once BASE_PATH . '/app/models/Horario.php';
        $idDocente = $this->getIdDocente();
        $horarios  = $idDocente ? (new Horario($this->pdo))->obtenerPorDocente($idDocente) : [];
        require_once BASE_PATH . '/app/views/docentes/horario.php';
    }

    /** Docente ve la vista para pegar su propio horario */
    public function importarHorarioDocenteView() {
        require_once BASE_PATH . '/app/models/Horario.php';
        $idDocente = $this->getIdDocente();
        // Grupos ya existentes de este docente para detectar duplicados en JS
        $stmt = $this->pdo->prepare(
            "SELECT g.nombre_grupo, m.clave_materia
             FROM grupos g JOIN materias m ON m.id_materia = g.id_materia
             WHERE g.id_docente = ?"
        );
        $stmt->execute([$idDocente]);
        $gruposExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once BASE_PATH . '/app/views/docentes/importar_horario.php';
    }

    /** Procesa el JSON pegado por el docente y crea sus grupos */
    public function procesarImportarHorarioDocente($datos) {
        require_once BASE_PATH . '/app/models/Horario.php';
        require_once BASE_PATH . '/app/models/Unidad.php';
        require_once BASE_PATH . '/app/models/Materia.php';

        $idDocente  = $this->getIdDocente();
        if (!$idDocente) {
            header("Location: index.php?accion=panel_docente"); exit();
        }

        $gruposJson = json_decode($datos['grupos_json'] ?? '[]', true);
        $periodo    = trim($datos['periodo']      ?? '');
        $numUnid    = (int)($datos['num_unidades'] ?? 4);

        if (empty($gruposJson) || !$periodo) {
            $mensaje = '❌ Datos incompletos.';
            $tipoMensaje = 'danger';
            $stmt = $this->pdo->prepare(
                "SELECT g.nombre_grupo, m.clave_materia FROM grupos g
                 JOIN materias m ON m.id_materia = g.id_materia WHERE g.id_docente = ?"
            );
            $stmt->execute([$idDocente]);
            $gruposExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            require_once BASE_PATH . '/app/views/docentes/importar_horario.php';
            return;
        }

        $modeloMateria = new Materia($this->pdo);
        $modeloUnidad  = new Unidad($this->pdo);
        $modeloHorario = new Horario($this->pdo);
        $creados = 0; $actualizados = 0;

        foreach ($gruposJson as $g) {
            $clave    = strtoupper(trim($g['clave']   ?? ''));
            $nombre   = trim($g['materia'] ?? '');
            $grupo    = trim($g['grupo']   ?? '');
            $horarios = $g['horarios'] ?? [];
            if (!$clave || !$nombre || !$grupo) continue;

            try {
                $this->pdo->beginTransaction();

                // 1. Materia: crear si no existe
                $stmtM = $this->pdo->prepare(
                    "SELECT id_materia FROM materias WHERE clave_materia = ? LIMIT 1"
                );
                $stmtM->execute([$clave]);
                $rowM = $stmtM->fetch(PDO::FETCH_ASSOC);
                if ($rowM) {
                    $idMateria = (int)$rowM['id_materia'];
                } else {
                    $this->pdo->prepare(
                        "INSERT INTO materias (clave_materia, nombre) VALUES (?, ?)"
                    )->execute([$clave, $nombre]);
                    $idMateria = (int)$this->pdo->lastInsertId();
                }

                // 2. Grupo: crear si no existe, asignado a ESTE docente
                $stmtG = $this->pdo->prepare(
                    "SELECT id_grupo FROM grupos
                     WHERE nombre_grupo=? AND id_materia=? AND id_docente=? LIMIT 1"
                );
                $stmtG->execute([$grupo, $idMateria, $idDocente]);
                $rowG = $stmtG->fetch(PDO::FETCH_ASSOC);
                if ($rowG) {
                    $idGrupo = (int)$rowG['id_grupo'];
                    $actualizados++;
                } else {
                    $this->pdo->prepare(
                        "INSERT INTO grupos (id_materia, id_docente, nombre_grupo, periodo, num_unidades)
                         VALUES (?,?,?,?,?)"
                    )->execute([$idMateria, $idDocente, $grupo, $periodo, $numUnid]);
                    $idGrupo = (int)$this->pdo->lastInsertId();
                    $modeloUnidad->crearParaGrupo($idGrupo, $numUnid);
                    $creados++;
                }

                // 3. Horario
                if (!empty($horarios)) {
                    $modeloHorario->guardarParaGrupo($idGrupo, $horarios);
                }

                $this->pdo->commit();
            } catch (PDOException $e) {
                $this->pdo->rollBack();
            }
        }

        $mensaje     = "✅ Listo: $creados grupos nuevos creados · $actualizados horarios actualizados.";
        $tipoMensaje = 'success';
        $stmt = $this->pdo->prepare(
            "SELECT g.nombre_grupo, m.clave_materia FROM grupos g
             JOIN materias m ON m.id_materia = g.id_materia WHERE g.id_docente = ?"
        );
        $stmt->execute([$idDocente]);
        $gruposExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once BASE_PATH . '/app/views/docentes/importar_horario.php';
    }

    public function calendarioDocente() {
        require_once BASE_PATH . '/app/models/Calendario.php';
        require_once BASE_PATH . '/app/models/Horario.php';

        $idDocente = $this->getIdDocente();
        $misGrupos = $this->getMisGrupos($idDocente);

        $idGrupo    = isset($_GET['id_grupo']) ? (int)$_GET['id_grupo'] : null;
        $desde      = $_GET['desde'] ?? date('Y-01-01');
        $hasta      = $_GET['hasta'] ?? date('Y-07-31');

        $fechasClase = [];
        $alumnos     = [];
        $asistencias = [];
        $unidades    = [];
        $grupoInfo   = null;
        $horarios    = [];
        $festivosDelPeriodo = [];

        if ($idGrupo) {
            $modeloCal = new Calendario($this->pdo);

            // Info del grupo
            $stmt = $this->pdo->prepare(
                "SELECT g.*, m.nombre AS nombre_materia, d.nombre AS nombre_docente
                 FROM grupos g
                 JOIN materias m ON m.id_materia = g.id_materia
                 JOIN docentes d ON d.id_docente = g.id_docente
                 WHERE g.id_grupo = ? LIMIT 1"
            );
            $stmt->execute([$idGrupo]);
            $grupoInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            // Horario del grupo
            $horarios = (new Horario($this->pdo))->obtenerPorGrupo($idGrupo);

            // Generar fechas de clase en el rango
            $fechasClase = $modeloCal->generarFechasClase($idGrupo, $desde, $hasta);

            // Asistencias registradas
            $asistencias = $modeloCal->obtenerAsistenciasRango($idGrupo, $desde, $hasta);

            // Alumnos del grupo
            $alumnos = $modeloCal->obtenerAlumnos($idGrupo);

            // Unidades
            $unidades = $modeloCal->obtenerUnidades($idGrupo);

            // Festivos del periodo (para la lista al pie)
            $anyoDesde = (int)date('Y', strtotime($desde));
            $anyoHasta = (int)date('Y', strtotime($hasta));
            $festivosDelPeriodo = [];
            for ($y = $anyoDesde; $y <= $anyoHasta; $y++) {
                $festivosDelPeriodo = array_merge($festivosDelPeriodo, Calendario::getFestivos($y));
            }
            $festivosDelPeriodo = array_filter(
                $festivosDelPeriodo,
                fn($nombre, $fecha) => $fecha >= $desde && $fecha <= $hasta,
                ARRAY_FILTER_USE_BOTH
            );
        }

        require_once BASE_PATH . '/app/views/docentes/calendario.php';
    }

    // ══ GESTIÓN DE GRUPOS (Docente) ══════════════════════════

    public function misGrupos() {
        require_once BASE_PATH . '/app/models/Materia.php';
        $idDocente = $this->getIdDocente();
        $materias  = (new Materia($this->pdo))->obtenerTodas();
        $misGrupos = $this->getGruposConConteo($idDocente);
        require_once BASE_PATH . '/app/views/docentes/mis_grupos.php';
    }

    public function guardarGrupoDocente($datos) {
        require_once BASE_PATH . '/app/models/Materia.php';
        require_once BASE_PATH . '/app/models/Unidad.php';
        $idDocente = $this->getIdDocente();
        $materias  = (new Materia($this->pdo))->obtenerTodas();
        $mensaje   = ''; $tipoMensaje = 'danger';
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare(
                "INSERT INTO grupos (id_materia, id_docente, nombre_grupo, periodo, num_unidades)
                 VALUES (?,?,?,?,?)"
            );
            $stmt->execute([
                (int)$datos['id_materia'], $idDocente,
                trim($datos['nombre_grupo']), trim($datos['periodo']),
                (int)($datos['num_unidades'] ?? 4)
            ]);
            $idGrupo = (int)$this->pdo->lastInsertId();
            (new Unidad($this->pdo))->crearParaGrupo($idGrupo, (int)($datos['num_unidades'] ?? 4));
            $this->pdo->commit();
            // Redirigir directo a gestionar alumnos
            header("Location: index.php?accion=alumnos_grupo_docente&id_grupo=$idGrupo&nuevo=1");
            exit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $mensaje = "❌ Error: Ese grupo ya existe en ese periodo.";
        }
        $misGrupos = $this->getGruposConConteo($idDocente);
        require_once BASE_PATH . '/app/views/docentes/mis_grupos.php';
    }

    // ══ GESTIÓN DE ALUMNOS (Docente) ═════════════════════════

    public function alumnosGrupoDocente($idGrupo) {
        $idDocente = $this->getIdDocente();
        $grupoInfo = $this->getGrupoInfo($idGrupo, $idDocente);
        if (!$grupoInfo) { header("Location: index.php?accion=mis_grupos_docente"); exit(); }

        $alumnosInscritos = $this->getAlumnosDelGrupo($idGrupo);
        if (isset($_GET['nuevo'])) {
            $mensaje = '✅ Grupo creado. Ahora agrega los alumnos.';
            $tipoMensaje = 'success';
        }
        require_once BASE_PATH . '/app/views/docentes/alumnos_grupo.php';
    }

    public function procesarListaGrupoDocente($datos, $archivo) {
        require_once BASE_PATH . '/app/controllers/AlumnoController.php';
        $idGrupo   = (int)($datos['id_grupo'] ?? 0);
        $idDocente = $this->getIdDocente();
        $grupoInfo = $this->getGrupoInfo($idGrupo, $idDocente);
        if (!$grupoInfo) { header("Location: index.php?accion=mis_grupos_docente"); exit(); }

        $ctrl        = new AlumnoController($this->pdo);
        $mensaje     = $ctrl->importarAlumnosAGrupo($archivo, $idGrupo);
        $tipoMensaje = str_starts_with($mensaje, '✅') ? 'success' : 'danger';
        $alumnosInscritos = $this->getAlumnosDelGrupo($idGrupo);
        require_once BASE_PATH . '/app/views/docentes/alumnos_grupo.php';
    }

    public function agregarAlumnoManual($datos) {
        require_once BASE_PATH . '/app/models/Alumno.php';
        require_once BASE_PATH . '/app/models/Usuario.php';
        $idGrupo   = (int)($datos['id_grupo'] ?? 0);
        $idDocente = $this->getIdDocente();
        $grupoInfo = $this->getGrupoInfo($idGrupo, $idDocente);
        if (!$grupoInfo) { header("Location: index.php?accion=mis_grupos_docente"); exit(); }

        $noControl = trim($datos['numero_control'] ?? '');
        $nombre    = trim($datos['nombre'] ?? '');
        $correo    = trim($datos['correo'] ?? '');
        $mensaje   = ''; $tipoMensaje = 'danger';

        if (!$noControl || !$nombre || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $mensaje = '❌ Completa todos los campos correctamente.';
        } else {
            try {
                $modeloAlumno  = new Alumno($this->pdo);
                $modeloUsuario = new Usuario($this->pdo);

                // Insertar alumno (si ya existe devuelve false)
                $idAlumno = $modeloAlumno->insertarUno($noControl, $nombre, $correo);
                if (!$idAlumno) {
                    // Ya existe, obtener su id
                    $stmt = $this->pdo->prepare(
                        "SELECT id_alumno FROM alumnos WHERE numero_control=? LIMIT 1"
                    );
                    $stmt->execute([$noControl]);
                    $idAlumno = (int)$stmt->fetchColumn();
                } else {
                    // Nuevo: crear cuenta
                    $idUsuario = $modeloUsuario->crearCuenta($noControl, $noControl, Usuario::ROL_ALUMNO);
                    $modeloUsuario->vincularAlumno($idUsuario, $idAlumno);
                }

                // Inscribir al grupo
                $stmt = $this->pdo->prepare(
                    "INSERT IGNORE INTO grupo_alumnos (id_grupo, id_alumno) VALUES (?,?)"
                );
                $stmt->execute([$idGrupo, $idAlumno]);

                if ($stmt->rowCount() > 0) {
                    $mensaje     = "✅ Alumno <strong>$nombre</strong> agregado e inscrito correctamente.";
                    $tipoMensaje = 'success';
                } else {
                    $mensaje     = "ℹ️ El alumno ya estaba inscrito en este grupo.";
                    $tipoMensaje = 'warning';
                }
            } catch (PDOException $e) {
                $mensaje = '❌ Error: ' . $e->getMessage();
            }
        }

        $alumnosInscritos = $this->getAlumnosDelGrupo($idGrupo);
        require_once BASE_PATH . '/app/views/docentes/alumnos_grupo.php';
    }

    public function buscarAlumnoDocente($idGrupo, $q) {
        $idDocente = $this->getIdDocente();
        $grupoInfo = $this->getGrupoInfo($idGrupo, $idDocente);
        if (!$grupoInfo) { header("Location: index.php?accion=mis_grupos_docente"); exit(); }

        $alumnosBusqueda = [];
        if (!empty($q)) {
            $stmt = $this->pdo->prepare(
                "SELECT a.id_alumno, a.numero_control, a.nombre
                 FROM alumnos a
                 WHERE (a.nombre LIKE ? OR a.numero_control LIKE ?)
                   AND a.id_alumno NOT IN (
                     SELECT id_alumno FROM grupo_alumnos WHERE id_grupo = ?
                   )
                 ORDER BY a.nombre ASC LIMIT 10"
            );
            $like = "%$q%";
            $stmt->execute([$like, $like, $idGrupo]);
            $alumnosBusqueda = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $alumnosInscritos = $this->getAlumnosDelGrupo($idGrupo);
        require_once BASE_PATH . '/app/views/docentes/alumnos_grupo.php';
    }

    public function inscribirAlumnoExistente($datos) {
        $idGrupo  = (int)($datos['id_grupo']  ?? 0);
        $idAlumno = (int)($datos['id_alumno'] ?? 0);
        $idDocente = $this->getIdDocente();
        $grupoInfo = $this->getGrupoInfo($idGrupo, $idDocente);
        if (!$grupoInfo) { header("Location: index.php?accion=mis_grupos_docente"); exit(); }

        $stmt = $this->pdo->prepare(
            "INSERT IGNORE INTO grupo_alumnos (id_grupo, id_alumno) VALUES (?,?)"
        );
        $stmt->execute([$idGrupo, $idAlumno]);
        $mensaje     = $stmt->rowCount() > 0 ? '✅ Alumno inscrito correctamente.' : 'ℹ️ Ya estaba inscrito.';
        $tipoMensaje = $stmt->rowCount() > 0 ? 'success' : 'warning';
        $alumnosInscritos = $this->getAlumnosDelGrupo($idGrupo);
        require_once BASE_PATH . '/app/views/docentes/alumnos_grupo.php';
    }

    public function desinscribirAlumno($idGrupo, $idAlumno) {
        $idDocente = $this->getIdDocente();
        $grupoInfo = $this->getGrupoInfo($idGrupo, $idDocente);
        if (!$grupoInfo) { header("Location: index.php?accion=mis_grupos_docente"); exit(); }

        $this->pdo->prepare(
            "DELETE FROM grupo_alumnos WHERE id_grupo=? AND id_alumno=?"
        )->execute([$idGrupo, $idAlumno]);

        $mensaje     = '✅ Alumno quitado del grupo.';
        $tipoMensaje = 'success';
        $alumnosInscritos = $this->getAlumnosDelGrupo($idGrupo);
        require_once BASE_PATH . '/app/views/docentes/alumnos_grupo.php';
    }

    // ── Helpers privados ──────────────────────────────────────
    private function getGruposConConteo(?int $idDocente): array {
        if (!$idDocente) return [];
        $stmt = $this->pdo->prepare(
            "SELECT g.*, m.nombre AS nombre_materia,
                    COUNT(ga.id_alumno) AS total_alumnos
             FROM grupos g
             JOIN materias m ON m.id_materia = g.id_materia
             LEFT JOIN grupo_alumnos ga ON ga.id_grupo = g.id_grupo
             WHERE g.id_docente = ? AND g.activo = 1
             GROUP BY g.id_grupo
             ORDER BY g.periodo DESC, g.nombre_grupo ASC"
        );
        $stmt->execute([$idDocente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getGrupoInfo(int $idGrupo, ?int $idDocente): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT g.*, m.nombre AS nombre_materia
             FROM grupos g JOIN materias m ON m.id_materia = g.id_materia
             WHERE g.id_grupo=? AND g.id_docente=? LIMIT 1"
        );
        $stmt->execute([$idGrupo, $idDocente]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function getAlumnosDelGrupo(int $idGrupo): array {
        $stmt = $this->pdo->prepare(
            "SELECT a.id_alumno, a.numero_control, a.nombre, a.correo, a.id_usuario,
                    ga.fecha_inscripcion
             FROM grupo_alumnos ga
             JOIN alumnos a ON a.id_alumno = ga.id_alumno
             WHERE ga.id_grupo=?
             ORDER BY a.nombre ASC"
        );
        $stmt->execute([$idGrupo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ══ MÓDULO GRUPOS — PANEL CENTRAL ════════════════════════

    public function gruposPanel() {
        require_once BASE_PATH . '/app/models/Rubrica.php';
        require_once BASE_PATH . '/app/models/Unidad.php';
        $idDocente = $this->getIdDocente();
        $misGrupos = $this->getGruposConConteo($idDocente);

        $unidadesPorGrupo = [];
        $rubricaPorGrupo  = [];
        $modeloUnid   = new Unidad($this->pdo);
        $modeloRubric = new Rubrica($this->pdo);
        foreach ($misGrupos as $g) {
            $unidadesPorGrupo[$g['id_grupo']] = $modeloUnid->obtenerPorGrupo($g['id_grupo']);
            $rubricaPorGrupo[$g['id_grupo']]  = $modeloRubric->obtenerPorGrupo($g['id_grupo']);
        }
        require_once BASE_PATH . '/app/views/docentes/grupos_panel.php';
    }

    public function actualizarNumUnidades($datos) {
        require_once BASE_PATH . '/app/models/Unidad.php';
        $idGrupo  = (int)($datos['id_grupo']     ?? 0);
        $numUnid  = (int)($datos['num_unidades'] ?? 4);
        $idDocente = $this->getIdDocente();
        // Verificar que el grupo pertenece al docente
        if (!$this->getGrupoInfo($idGrupo, $idDocente)) {
            header("Location: index.php?accion=grupos_panel_docente"); exit();
        }
        $modeloUnid = new Unidad($this->pdo);
        $actuales   = $modeloUnid->obtenerPorGrupo($idGrupo);
        $cantActual = count($actuales);
        if ($numUnid > $cantActual) {
            // Crear las que faltan
            for ($i = $cantActual + 1; $i <= $numUnid; $i++) {
                $this->pdo->prepare(
                    "INSERT IGNORE INTO unidades (id_grupo, numero_unidad, nombre)
                     VALUES (?,?,?)"
                )->execute([$idGrupo, $i, "Unidad $i"]);
            }
        } elseif ($numUnid < $cantActual) {
            // Eliminar las sobrantes (las de mayor número)
            $this->pdo->prepare(
                "DELETE FROM unidades WHERE id_grupo=? AND numero_unidad>? AND cerrada=0"
            )->execute([$idGrupo, $numUnid]);
        }
        $this->pdo->prepare(
            "UPDATE grupos SET num_unidades=? WHERE id_grupo=?"
        )->execute([$numUnid, $idGrupo]);
        header("Location: index.php?accion=grupos_panel_docente");
        exit();
    }

    public function guardarUnidadPanel($datos) {
        require_once BASE_PATH . '/app/models/Unidad.php';
        $idUnidad  = (int)($datos['id_unidad'] ?? 0);
        $idDocente = $this->getIdDocente();
        (new Unidad($this->pdo))->actualizarNombreYFecha(
            $idUnidad,
            $datos['nombre']    ?? '',
            $datos['fecha_fin'] ?? ''
        );
        header("Location: index.php?accion=grupos_panel_docente"); exit();
    }

    public function guardarRubrica($datos) {
        require_once BASE_PATH . '/app/models/Rubrica.php';
        $idGrupo   = (int)($datos['id_grupo'] ?? 0);
        $criterios = $datos['criterios'] ?? [];
        $resultado = (new Rubrica($this->pdo))->guardar($idGrupo, $criterios);
        $mensaje     = $resultado['msg'];
        $tipoMensaje = $resultado['ok'] ? 'success' : 'danger';
        // Recargar panel
        require_once BASE_PATH . '/app/models/Unidad.php';
        $idDocente = $this->getIdDocente();
        $misGrupos = $this->getGruposConConteo($idDocente);
        $modeloUnid   = new Unidad($this->pdo);
        $modeloRubric = new Rubrica($this->pdo);
        $unidadesPorGrupo = [];
        $rubricaPorGrupo  = [];
        foreach ($misGrupos as $g) {
            $unidadesPorGrupo[$g['id_grupo']] = $modeloUnid->obtenerPorGrupo($g['id_grupo']);
            $rubricaPorGrupo[$g['id_grupo']]  = $modeloRubric->obtenerPorGrupo($g['id_grupo']);
        }
        require_once BASE_PATH . '/app/views/docentes/grupos_panel.php';
    }

}
?>
