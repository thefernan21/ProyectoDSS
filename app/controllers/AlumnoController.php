<?php
require_once BASE_PATH . '/app/models/Alumno.php';
require_once BASE_PATH . '/app/models/Asistencia.php';
require_once BASE_PATH . '/app/models/Grupo.php';
require_once BASE_PATH . '/app/models/Usuario.php';

class AlumnoController {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    private function getIdAlumno() {
        $stmt = $this->pdo->prepare("SELECT id_alumno FROM alumnos WHERE id_usuario = ? LIMIT 1");
        $stmt->execute([$_SESSION['usuario_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id_alumno'] : null;
    }

    public function panelAlumno() {
        $idAlumno    = $this->getIdAlumno();
        $modeloAsist = new Asistencia($this->pdo);

        $conteo             = $idAlumno ? $modeloAsist->conteoAlumno($idAlumno) : [];
        $porcentajes        = $idAlumno ? $modeloAsist->obtenerPorcentajeAlumno($idAlumno) : [];
        $totalClases        = (int)($conteo['total_clases']    ?? 0);
        $totalAsistencias   = (int)($conteo['asistencias']     ?? 0);
        $totalRetardos      = (int)($conteo['retardos']        ?? 0);
        $totalInasistencias = (int)($conteo['inasistencias']   ?? 0);

        require_once BASE_PATH . '/app/views/alumnos/panel.php';
    }

    public function misAsistencias() {
        $idAlumno      = $this->getIdAlumno();
        $modeloAsist   = new Asistencia($this->pdo);
        $modeloGrupo   = new Grupo($this->pdo);
        $idGrupoFiltro = isset($_GET['id_grupo']) && $_GET['id_grupo'] !== '' ? (int)$_GET['id_grupo'] : null;
        $misGrupos     = $idAlumno ? $modeloGrupo->obtenerPorAlumno($idAlumno) : [];
        $historial     = $idAlumno ? $modeloAsist->obtenerHistorialAlumno($idAlumno, $idGrupoFiltro) : [];

        // Resumen por grupo
        $resumenGrupos = [];
        foreach ($misGrupos as $g) {
            $stmt = $this->pdo->prepare(
                "SELECT
                    COUNT(*) AS total_clases,
                    SUM(tipo_asistencia='asistencia')   AS asistencias,
                    SUM(tipo_asistencia='retardo')      AS retardos,
                    SUM(tipo_asistencia='inasistencia') AS faltas,
                    ROUND(SUM(valor)/NULLIF(COUNT(*),0)*100,1) AS porcentaje
                 FROM asistencias WHERE id_alumno=? AND id_grupo=?"
            );
            $stmt->execute([$idAlumno, $g['id_grupo']]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            $resumenGrupos[] = array_merge($g, $res ?? []);
        }
        require_once BASE_PATH . '/app/views/alumnos/mis_asistencias.php';
    }

    public function misCalificaciones() {
        $idAlumno   = $this->getIdAlumno();
        $misGrupos  = $idAlumno ? (new Grupo($this->pdo))->obtenerPorAlumno($idAlumno) : [];
        $calificaciones = [];

        require_once BASE_PATH . '/app/models/Unidad.php';
        require_once BASE_PATH . '/app/models/Actividad.php';
        $modeloUnid = new Unidad($this->pdo);
        $modeloAct  = new Actividad($this->pdo);

        foreach ($misGrupos as $g) {
            $unidades = $modeloUnid->obtenerPorGrupo($g['id_grupo']);
            $unidadesData = [];
            foreach ($unidades as $u) {
                $resumen = $modeloAct->resumenUnidad($g['id_grupo'], $u['id_unidad']);
                foreach ($resumen as $r) {
                    if ($r['id_alumno'] == $idAlumno) {
                        $unidadesData[] = array_merge($u, $r);
                        break;
                    }
                }
            }
            if (!empty($unidadesData)) {
                $calificaciones[$g['id_grupo']] = [
                    'materia'     => $g['nombre_materia'],
                    'nombre_grupo'=> $g['nombre_grupo'],
                    'periodo'     => $g['periodo'],
                    'unidades'    => $unidadesData,
                ];
            }
        }
        require_once BASE_PATH . '/app/views/alumnos/mis_calificaciones.php';
    }

    public function miHistorial() {
        $idAlumno      = $this->getIdAlumno();
        $modeloAsist   = new Asistencia($this->pdo);
        $modeloGrupo   = new Grupo($this->pdo);
        $idGrupoFiltro = isset($_GET['id_grupo']) && $_GET['id_grupo'] !== '' ? (int)$_GET['id_grupo'] : null;
        $misGrupos     = $idAlumno ? $modeloGrupo->obtenerPorAlumno($idAlumno) : [];
        $historial     = $idAlumno ? $modeloAsist->obtenerHistorialAlumno($idAlumno, $idGrupoFiltro) : [];
        require_once BASE_PATH . '/app/views/alumnos/historial.php';
    }

    /**
     * Importa alumnos desde CSV y CREA automáticamente su cuenta de usuario.
     * Usuario  = numero_control
     * Password = numero_control  (el alumno puede cambiarla después)
     */
    public function importarAlumnos($archivo) {
        if ($archivo['size'] > 4096) return "Error: El archivo supera los 4KB permitidos.";

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeReal = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeReal, ['text/plain', 'text/csv', 'application/csv'])) {
            return "Error: Tipo de archivo no válido. Sube un CSV o TXT.";
        }

        $handle      = fopen($archivo['tmp_name'], "r");
        $alumnos     = [];
        $primeraFila = true;

        while (($fila = fgetcsv($handle, 1000, ",")) !== false) {
            if ($primeraFila) { $primeraFila = false; continue; }
            if (count($fila) < 3) continue;

            $noControl = trim($fila[0]);
            $nombre    = trim(trim($fila[1]) . ' ' . trim($fila[3] ?? ''));
            $correo    = filter_var(trim($fila[2]), FILTER_SANITIZE_EMAIL);

            if (filter_var($correo, FILTER_VALIDATE_EMAIL) && !empty($noControl)) {
                $alumnos[] = [
                    'numero_control' => htmlspecialchars($noControl, ENT_QUOTES, 'UTF-8'),
                    'nombre'         => htmlspecialchars($nombre,    ENT_QUOTES, 'UTF-8'),
                    'correo'         => $correo
                ];
            }
        }
        fclose($handle);

        if (empty($alumnos)) return "Error: No se encontraron datos válidos.";

        $modeloAlumno  = new Alumno($this->pdo);
        $modeloUsuario = new Usuario($this->pdo);
        $insertados    = 0;
        $cuentasCreadas = 0;

        foreach ($alumnos as $a) {
            // 1. Insertar en tabla alumnos (si no existe)
            $idAlumno = $modeloAlumno->insertarUno($a['numero_control'], $a['nombre'], $a['correo']);

            if ($idAlumno) {
                $insertados++;

                // 2. Crear cuenta de usuario automáticamente
                //    usuario = numero_control | password = numero_control
                $idUsuario = $modeloUsuario->crearCuenta(
                    $a['numero_control'],
                    $a['numero_control'],  // contraseña por defecto
                    Usuario::ROL_ALUMNO
                );

                // 3. Vincular cuenta con alumno
                $modeloUsuario->vincularAlumno($idUsuario, $idAlumno);
                $cuentasCreadas++;
            }
        }

        return "✅ ¡Éxito! $insertados alumnos nuevos registrados con su cuenta de acceso.<br>
                <small>🔑 <strong>Usuario y contraseña inicial = Número de control</strong> (el alumno puede cambiarla)</small>";
    }

    /**
     * Importa alumnos desde CSV y los inscribe OBLIGATORIAMENTE a un grupo.
     * No es posible registrar alumnos sin un grupo asignado.
     */
    public function importarAlumnosAGrupo($archivo, $idGrupo) {
        if (!$idGrupo) return "❌ Error: No se especificó un grupo destino.";
        if ($archivo['size'] > 4096) return "❌ Error: El archivo supera los 4KB permitidos.";

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeReal = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeReal, ['text/plain', 'text/csv', 'application/csv'])) {
            return "❌ Error: Tipo de archivo no válido. Sube un CSV o TXT.";
        }

        $handle      = fopen($archivo['tmp_name'], "r");
        $alumnos     = [];
        $primeraFila = true;

        while (($fila = fgetcsv($handle, 1000, ",")) !== false) {
            if ($primeraFila) { $primeraFila = false; continue; }
            if (count($fila) < 3) continue;

            $noControl = trim($fila[0]);
            $nombre    = trim(trim($fila[1]) . ' ' . trim($fila[3] ?? ''));
            $correo    = filter_var(trim($fila[2]), FILTER_SANITIZE_EMAIL);

            if (filter_var($correo, FILTER_VALIDATE_EMAIL) && !empty($noControl)) {
                $alumnos[] = [
                    'numero_control' => htmlspecialchars($noControl, ENT_QUOTES, 'UTF-8'),
                    'nombre'         => htmlspecialchars($nombre,    ENT_QUOTES, 'UTF-8'),
                    'correo'         => $correo
                ];
            }
        }
        fclose($handle);

        if (empty($alumnos)) return "❌ Error: No se encontraron datos válidos en el archivo.";

        $modeloAlumno  = new Alumno($this->pdo);
        $modeloUsuario = new Usuario($this->pdo);
        $nuevos        = 0;
        $inscritos     = 0;
        $yaInscritos   = 0;

        // Preparar inscripción al grupo
        $stmtInscribir = $this->pdo->prepare(
            "INSERT IGNORE INTO grupo_alumnos (id_grupo, id_alumno) VALUES (?, ?)"
        );

        foreach ($alumnos as $a) {
            // 1. Insertar alumno (si es nuevo devuelve el id, si ya existe devuelve false)
            $idAlumno = $modeloAlumno->insertarUno($a['numero_control'], $a['nombre'], $a['correo']);

            if ($idAlumno) {
                // Alumno nuevo → crear cuenta de usuario automáticamente
                $idUsuario = $modeloUsuario->crearCuenta(
                    $a['numero_control'],
                    $a['numero_control'],   // contraseña inicial = número de control
                    Usuario::ROL_ALUMNO
                );
                $modeloUsuario->vincularAlumno($idUsuario, $idAlumno);
                $nuevos++;
            } else {
                // Ya existía → obtener su id para inscribirlo igualmente
                $stmt = $this->pdo->prepare("SELECT id_alumno FROM alumnos WHERE numero_control = ? LIMIT 1");
                $stmt->execute([$a['numero_control']]);
                $row      = $stmt->fetch(PDO::FETCH_ASSOC);
                $idAlumno = $row ? (int)$row['id_alumno'] : null;
            }

            // 2. Inscribir al grupo (INSERT IGNORE evita duplicados)
            if ($idAlumno) {
                $stmtInscribir->execute([$idGrupo, $idAlumno]);
                if ($stmtInscribir->rowCount() > 0) {
                    $inscritos++;
                } else {
                    $yaInscritos++;
                }
            }
        }

        $resumen = "✅ Proceso completado: <strong>$inscritos</strong> alumno(s) inscritos al grupo.";
        if ($nuevos > 0)      $resumen .= " ($nuevos cuentas nuevas creadas)";
        if ($yaInscritos > 0) $resumen .= " · $yaInscritos ya estaban inscritos.";
        if ($nuevos > 0)      $resumen .= "<br><small>🔑 Contraseña inicial = número de control</small>";
        return $resumen;
    }

    public function listarAlumnos() {
        if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Admin', 'Docente'])) {
            die("<h3 style='color:red;text-align:center;'>Acceso denegado.</h3>");
        }
        $modeloAlumno = new Alumno($this->pdo);
        $alumnos = $modeloAlumno->obtenerTodos();
        require_once BASE_PATH . '/app/views/alumnos/lista.php';
    }
}
?>
