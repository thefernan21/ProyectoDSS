<?php
require_once BASE_PATH . '/app/models/Materia.php';
require_once BASE_PATH . '/app/models/Grupo.php';
require_once BASE_PATH . '/app/models/Asistencia.php';
require_once BASE_PATH . '/app/models/Alumno.php';
require_once BASE_PATH . '/app/models/Usuario.php';
require_once BASE_PATH . '/app/models/Unidad.php';
require_once BASE_PATH . '/app/models/Actividad.php';

class AdminController {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    // ══ DASHBOARD ═══════════════════════════════════════════

    public function dashboard() {
        $modeloAsist   = new Asistencia($this->pdo);
        $modeloMateria = new Materia($this->pdo);

        $totalAlumnos    = (int)$this->pdo->query("SELECT COUNT(*) FROM alumnos")->fetchColumn();
        $totalDocentes   = (int)$this->pdo->query("SELECT COUNT(*) FROM docentes")->fetchColumn();
        $totalMaterias   = count($modeloMateria->obtenerTodas());
        $totalAsistencias = $modeloAsist->conteoTotal();

        $auditLog = $this->pdo->query(
            "SELECT accion, fecha, detalle FROM audit_log ORDER BY fecha DESC LIMIT 5"
        )->fetchAll(PDO::FETCH_ASSOC);

        require_once BASE_PATH . '/app/views/admin/dashboard.php';
    }

    // ══ MATERIAS ═════════════════════════════════════════════

    public function verMaterias() {
        $modelo   = new Materia($this->pdo);
        $materias = $modelo->obtenerTodas();
        require_once BASE_PATH . '/app/views/admin/materias.php';
    }

    public function guardarMateria($datos) {
        $modelo      = new Materia($this->pdo);
        $mensaje     = '';
        $tipoMensaje = 'success';
        try {
            $ok = $modelo->guardar($datos['clave_materia'], $datos['nombre'], $datos['creditos'] ?? 5);
            $mensaje = $ok ? '✅ Materia guardada correctamente.' : 'Error: No se pudo guardar.';
            if (!$ok) $tipoMensaje = 'danger';
        } catch (PDOException $e) {
            $mensaje     = '❌ Error: La clave de materia ya existe.';
            $tipoMensaje = 'danger';
        }
        $materias = $modelo->obtenerTodas();
        require_once BASE_PATH . '/app/views/admin/materias.php';
    }

    // ══ GRUPOS ═══════════════════════════════════════════════

    public function verGrupos() {
        $modeloMateria = new Materia($this->pdo);
        $grupos = $this->pdo->query(
            "SELECT g.*, m.nombre AS nombre_materia, d.nombre AS nombre_docente,
                    COUNT(ga.id_alumno) AS total_alumnos
             FROM grupos g
             JOIN materias m ON m.id_materia = g.id_materia
             JOIN docentes d ON d.id_docente = g.id_docente
             LEFT JOIN grupo_alumnos ga ON ga.id_grupo = g.id_grupo
             WHERE g.activo = 1
             GROUP BY g.id_grupo
             ORDER BY g.periodo DESC, g.nombre_grupo ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
        $materias = $modeloMateria->obtenerTodas();
        $docentes = $this->pdo->query("SELECT id_docente, nombre FROM docentes ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        require_once BASE_PATH . '/app/views/admin/grupos.php';
    }

    public function guardarGrupo($datos) {
        $modeloMateria = new Materia($this->pdo);
        $mensaje       = '';
        $tipoMensaje   = 'success';
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO grupos (id_materia, id_docente, nombre_grupo, periodo, num_unidades) VALUES (?,?,?,?,?)"
            );
            $stmt->execute([
                (int)$datos['id_materia'], (int)$datos['id_docente'],
                trim($datos['nombre_grupo']), trim($datos['periodo']),
                (int)($datos['num_unidades'] ?? 4)
            ]);
            $idGrupoNuevo = (int)$this->pdo->lastInsertId();
            (new Unidad($this->pdo))->crearParaGrupo($idGrupoNuevo, (int)($datos['num_unidades'] ?? 4));
            header("Location: index.php?accion=cargar_lista_grupo&id_grupo=$idGrupoNuevo&nuevo=1");
            exit();
        } catch (PDOException $e) {
            $mensaje     = '❌ Error: Ese grupo ya existe en ese periodo.';
            $tipoMensaje = 'danger';
        }
        $grupos = $this->pdo->query(
            "SELECT g.*, m.nombre AS nombre_materia, d.nombre AS nombre_docente,
                    COUNT(ga.id_alumno) AS total_alumnos
             FROM grupos g
             JOIN materias m ON m.id_materia = g.id_materia
             JOIN docentes d ON d.id_docente = g.id_docente
             LEFT JOIN grupo_alumnos ga ON ga.id_grupo = g.id_grupo
             WHERE g.activo = 1 GROUP BY g.id_grupo"
        )->fetchAll(PDO::FETCH_ASSOC);
        $materias = $modeloMateria->obtenerTodas();
        $docentes = $this->pdo->query("SELECT id_docente, nombre FROM docentes ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        require_once BASE_PATH . '/app/views/admin/grupos.php';
    }

    public function eliminarGrupo($idGrupo) {
        try {
            $this->pdo->beginTransaction();
            $this->pdo->prepare("DELETE FROM grupos WHERE id_grupo=?")->execute([$idGrupo]);
            $this->pdo->commit();
            return ['ok'=>true, 'msg'=>'✅ Grupo eliminado.'];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['ok'=>false, 'msg'=>'❌ Error: '.$e->getMessage()];
        }
    }

    public function cargarListaGrupo($idGrupo) {
        $grupo = $this->obtenerInfoGrupo($idGrupo);
        if (!$grupo) { header("Location: index.php?accion=ver_grupos"); exit(); }
        $alumnosInscritos = $this->obtenerAlumnosDelGrupo($idGrupo);
        if (isset($_GET['nuevo'])) {
            $mensaje     = '✅ Grupo creado. Ahora carga el CSV con los alumnos de este grupo.';
            $tipoMensaje = 'success';
        }
        require_once BASE_PATH . '/app/views/admin/cargar_lista_grupo.php';
    }

    public function procesarListaGrupo($datos, $archivo) {
        $idGrupo = (int)($datos['id_grupo'] ?? 0);
        $grupo   = $this->obtenerInfoGrupo($idGrupo);
        if (!$grupo) { header("Location: index.php?accion=ver_grupos"); exit(); }

        require_once BASE_PATH . '/app/controllers/AlumnoController.php';
        $ctrl        = new AlumnoController($this->pdo);
        $mensaje     = $ctrl->importarAlumnosAGrupo($archivo, $idGrupo);
        $tipoMensaje = str_starts_with($mensaje, '✅') ? 'success' : 'danger';

        $alumnosInscritos = $this->obtenerAlumnosDelGrupo($idGrupo);
        require_once BASE_PATH . '/app/views/admin/cargar_lista_grupo.php';
    }

    public function verAlumnosGrupo($idGrupo) {
        $grupo = $this->obtenerInfoGrupo($idGrupo);
        if (!$grupo) { header("Location: index.php?accion=ver_grupos"); exit(); }
        $alumnosInscritos = $this->obtenerAlumnosDelGrupo($idGrupo);
        require_once BASE_PATH . '/app/views/admin/alumnos_grupo.php';
    }

    // ══ DOCENTES ═════════════════════════════════════════════

    public function verDocentes() {
        $docentes = $this->pdo->query(
            "SELECT d.*, u.nombre_usuario FROM docentes d
             LEFT JOIN usuarios u ON u.id_usuario = d.id_usuario
             ORDER BY d.nombre ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
        require_once BASE_PATH . '/app/views/admin/docentes.php';
    }

    public function guardarDocente($datos) {
        $mensaje     = '';
        $tipoMensaje = 'danger';

        if (($datos['password'] ?? '') !== ($datos['password_confirm'] ?? '')) {
            $mensaje = '❌ Las contraseñas no coinciden. Intenta de nuevo.';
        } elseif (strlen($datos['password']) < 8) {
            $mensaje = '❌ La contraseña debe tener al menos 8 caracteres.';
        } else {
            try {
                $this->pdo->beginTransaction();
                $stmt = $this->pdo->prepare(
                    "INSERT INTO docentes (numero_empleado, nombre, correo) VALUES (?, ?, ?)"
                );
                $stmt->execute([trim($datos['numero_empleado']), trim($datos['nombre']), trim($datos['correo'])]);
                $idDocente = (int)$this->pdo->lastInsertId();

                $modeloUsuario = new Usuario($this->pdo);
                $idUsuario = $modeloUsuario->crearCuenta(
                    trim($datos['numero_empleado']),
                    $datos['password'],
                    Usuario::ROL_DOCENTE
                );
                $modeloUsuario->vincularDocente($idUsuario, $idDocente);
                $this->pdo->commit();
                $mensaje     = '✅ Docente registrado correctamente.';
                $tipoMensaje = 'success';
            } catch (PDOException $e) {
                $this->pdo->rollBack();
                $mensaje = '❌ Error: El número de empleado o correo ya existe.';
            }
        }
        $docentes = $this->pdo->query(
            "SELECT d.*, u.nombre_usuario FROM docentes d
             LEFT JOIN usuarios u ON u.id_usuario = d.id_usuario
             ORDER BY d.nombre ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
        require_once BASE_PATH . '/app/views/admin/docentes.php';
    }

    public function eliminarDocente($idDocente) {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("SELECT id_usuario FROM docentes WHERE id_docente=? LIMIT 1");
            $stmt->execute([$idDocente]);
            $idUsuario = $stmt->fetchColumn();
            $this->pdo->prepare("UPDATE grupos SET activo=0 WHERE id_docente=?")->execute([$idDocente]);
            $this->pdo->prepare("DELETE FROM docentes WHERE id_docente=?")->execute([$idDocente]);
            if ($idUsuario) $this->pdo->prepare("DELETE FROM usuarios WHERE id_usuario=?")->execute([$idUsuario]);
            $this->pdo->commit();
            return ['ok'=>true, 'msg'=>'✅ Docente eliminado.'];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['ok'=>false, 'msg'=>'❌ Error: '.$e->getMessage()];
        }
    }

    // ══ ALUMNOS ══════════════════════════════════════════════

    public function eliminarAlumno($idAlumno) {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("SELECT id_usuario FROM alumnos WHERE id_alumno=? LIMIT 1");
            $stmt->execute([$idAlumno]);
            $idUsuario = $stmt->fetchColumn();
            $this->pdo->prepare("DELETE FROM calif_actividades WHERE id_alumno=?")->execute([$idAlumno]);
            $this->pdo->prepare("DELETE FROM calif_tareas      WHERE id_alumno=?")->execute([$idAlumno]);
            $this->pdo->prepare("DELETE FROM asistencias        WHERE id_alumno=?")->execute([$idAlumno]);
            $this->pdo->prepare("DELETE FROM grupo_alumnos      WHERE id_alumno=?")->execute([$idAlumno]);
            $this->pdo->prepare("DELETE FROM alumnos            WHERE id_alumno=?")->execute([$idAlumno]);
            if ($idUsuario) $this->pdo->prepare("DELETE FROM usuarios WHERE id_usuario=?")->execute([$idUsuario]);
            $this->pdo->commit();
            return ['ok'=>true, 'msg'=>'✅ Alumno eliminado.'];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['ok'=>false, 'msg'=>'❌ Error: '.$e->getMessage()];
        }
    }

    // ══ CALIFICACIONES FINALES ═══════════════════════════════

    public function calificacionesAdmin() {
        $modeloAct  = new Actividad($this->pdo);
        $modeloUnid = new Unidad($this->pdo);

        $gruposRaw = $this->pdo->query(
            "SELECT g.id_grupo, g.nombre_grupo, g.periodo, g.num_unidades,
                    m.nombre AS nombre_materia, d.nombre AS nombre_docente
             FROM grupos g
             JOIN materias m ON m.id_materia = g.id_materia
             JOIN docentes d ON d.id_docente = g.id_docente
             WHERE g.activo = 1 ORDER BY g.periodo DESC, g.nombre_grupo ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $grupos = [];
        foreach ($gruposRaw as $g) {
            $idGrupo          = $g['id_grupo'];
            $unidades         = $modeloUnid->obtenerPorGrupo($idGrupo);
            $unidadesCerradas = array_values(array_filter($unidades, fn($u) => $u['cerrada']));
            if (empty($unidadesCerradas)) continue;

            $stmtAl = $this->pdo->prepare(
                "SELECT a.id_alumno, a.numero_control, a.nombre
                 FROM grupo_alumnos ga JOIN alumnos a ON a.id_alumno=ga.id_alumno
                 WHERE ga.id_grupo=? ORDER BY a.nombre ASC"
            );
            $stmtAl->execute([$idGrupo]);
            $alumnosData = $stmtAl->fetchAll(PDO::FETCH_ASSOC);

            foreach ($alumnosData as &$al) {
                $al['calificaciones'] = [];
                foreach ($unidadesCerradas as $u) {
                    $res = $modeloAct->resumenUnidad($idGrupo, $u['id_unidad']);
                    foreach ($res as $r) {
                        if ($r['id_alumno'] == $al['id_alumno']) {
                            $al['calificaciones'][$u['id_unidad']] = $r['calificacion_unidad'];
                            break;
                        }
                    }
                }
            }
            unset($al);

            $grupos[] = array_merge($g, [
                'unidades_cerradas_lista' => $unidadesCerradas,
                'unidades_cerradas'       => count($unidadesCerradas),
                'total_unidades'          => count($unidades),
                'todas_cerradas'          => $modeloUnid->todasCerradas($idGrupo),
                'alumnos'                 => $alumnosData,
            ]);
        }
        require_once BASE_PATH . '/app/views/admin/calificaciones.php';
    }

    // ══ HELPERS PRIVADOS ═════════════════════════════════════

    private function obtenerInfoGrupo($idGrupo) {
        $stmt = $this->pdo->prepare(
            "SELECT g.*, m.nombre AS nombre_materia, d.nombre AS nombre_docente,
                    COUNT(ga.id_alumno) AS total_alumnos
             FROM grupos g
             JOIN materias m ON m.id_materia = g.id_materia
             JOIN docentes d ON d.id_docente = g.id_docente
             LEFT JOIN grupo_alumnos ga ON ga.id_grupo = g.id_grupo
             WHERE g.id_grupo = ? GROUP BY g.id_grupo"
        );
        $stmt->execute([$idGrupo]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function obtenerAlumnosDelGrupo($idGrupo) {
        $stmt = $this->pdo->prepare(
            "SELECT a.numero_control, a.nombre, a.correo, a.id_usuario, ga.fecha_inscripcion
             FROM grupo_alumnos ga
             JOIN alumnos a ON a.id_alumno = ga.id_alumno
             WHERE ga.id_grupo = ? ORDER BY a.nombre ASC"
        );
        $stmt->execute([$idGrupo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
