# TODO — Grupos Multi-Materia + Avance Semestre + Horario estricto docente

## 1) Base de datos
- [ ] Revisar scripts SQL actuales en `proyecto_dss/database/` para identificar esquema real.
- [ ] Crear migración SQL:
  - [ ] Tabla `grupo_materias (id_grupo, id_materia, PK compuesta, FKs, índices)`.
  - [ ] Migrar datos existentes desde `grupos.id_materia` a `grupo_materias`.
  - [ ] Mantener compatibilidad sin romper consultas existentes.

## 2) Backend: Admin
- [ ] `app/controllers/AdminController.php`
  - [ ] Actualizar `verGrupos()` para mostrar materias agregadas por grupo.
  - [ ] Actualizar `guardarGrupo()` para aceptar múltiples materias.
  - [ ] Agregar acción para avanzar grupo de semestre con seguridad/transacción.

## 3) Backend: Docente
- [ ] `app/controllers/DocenteController.php`
  - [ ] Adaptar consultas de grupos a multi-materia.
  - [ ] Actualizar `guardarGrupoDocente()` para múltiples materias.
  - [ ] Agregar avance de semestre de grupos propios.
  - [ ] Endurecer validación en `procesarImportarHorarioDocente()`:
    - [ ] Validar campos permitidos.
    - [ ] Validar formato/intervalos de horario.
    - [ ] Requerir y validar: `num_unidades`, `fecha_inicio_semestre`, `fecha_fin_semestre`.

## 4) Rutas
- [ ] `public/index.php`
  - [ ] Agregar rutas nuevas para avance de semestre (admin/docente).
  - [ ] Ajustar rutas relacionadas si cambia nombre/acción de formularios.

## 5) Vistas
- [ ] `app/views/admin/grupos.php`
  - [ ] Formulario con selección múltiple de materias.
  - [ ] Mostrar materias múltiples en listado.
- [ ] `app/views/docentes/grupos.php`
  - [ ] Formulario con selección múltiple de materias.
  - [ ] Mostrar materias múltiples en listado.
  - [ ] Botón “Avanzar semestre”.
- [ ] `app/views/docentes/importar_horario.php`
  - [ ] Pedir explícitamente:
    - [ ] Número de unidades
    - [ ] Fecha inicio semestre
    - [ ] Fecha fin semestre
  - [ ] Validaciones front-end coherentes con backend.

## 6) Validaciones de seguridad/robustez
- [ ] Sanitizar y validar entradas críticas (grupo, periodo, materias[], fechas, unidades).
- [ ] Confirmar ownership del docente en acciones sensibles.
- [ ] Mantener prepared statements y transacciones.

## 7) Verificación
- [ ] Validación sintáctica (`php -l`) en archivos modificados.
- [ ] Confirmar que no se rompen flujos existentes de:
  - [ ] crear grupo
  - [ ] pasar lista
  - [ ] importar horario
  - [ ] gestión de alumnos por grupo
