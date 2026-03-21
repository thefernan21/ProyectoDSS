-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 21-03-2026 a las 02:53:18
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `asistencias`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades_clase`
--

CREATE TABLE `actividades_clase` (
  `id_actividad` int(11) NOT NULL,
  `id_grupo` int(11) NOT NULL,
  `id_unidad` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `nombre` varchar(150) NOT NULL COMMENT 'Ej: Práctica 1 - Arreglos',
  `registrado_por` int(11) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `actividades_clase`
--

INSERT INTO `actividades_clase` (`id_actividad`, `id_grupo`, `id_unidad`, `fecha`, `nombre`, `registrado_por`, `fecha_registro`) VALUES
(2, 3, 1, '2026-03-16', 'Procesos iniciales', 7, '2026-03-20 19:18:14'),
(3, 3, 1, '2026-03-16', 'Valores personales', 7, '2026-03-20 19:18:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos`
--

CREATE TABLE `alumnos` (
  `id_alumno` int(11) NOT NULL,
  `numero_control` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `alumnos`
--

INSERT INTO `alumnos` (`id_alumno`, `numero_control`, `nombre`, `correo`, `id_usuario`) VALUES
(2, '22560123', 'PABLO CESAR BANUELOS GUERRERO', 'itlac22560123@lcardenas.tecnm.mx', 3),
(3, '22560001', 'JUAN Magana Lopez', 'itlac22560001@lcardenas.tecnm.mx', 4),
(4, '22560067', 'FERNANDO Valdez Arreola', 'itlac22560067@lcardenas.tecnm.mx', 5),
(5, '22560510', 'JESUS EINAR TAPIA FRANCO', 'itlac22560510@lcardenas.tecnm.mx', 6),
(6, '25560001', 'Juan Pérez López', 'itlac25560001@lcardenas.tecnm.mx', 8),
(7, '25560002', 'María González Ruiz', 'itlac25560002@itlac.edu.mx', 9),
(8, '25560003', 'Juan Pablo Marin Lopez', 'itlac22560003@itlac.edu.mx', 10);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias`
--

CREATE TABLE `asistencias` (
  `id_asistencia` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `id_grupo` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `tipo_asistencia` enum('asistencia','retardo','inasistencia') NOT NULL,
  `valor` decimal(3,1) GENERATED ALWAYS AS (case `tipo_asistencia` when 'asistencia' then 1.0 when 'retardo' then 0.8 when 'inasistencia' then 0.0 end) STORED COMMENT 'Calculado automáticamente: 1.0 / 0.8 / 0.0',
  `registrado_por` int(11) DEFAULT NULL COMMENT 'id_usuario del docente que pasó lista',
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de asistencias con valor ponderado automático';

--
-- Volcado de datos para la tabla `asistencias`
--

INSERT INTO `asistencias` (`id_asistencia`, `id_alumno`, `id_grupo`, `fecha`, `tipo_asistencia`, `registrado_por`, `fecha_registro`) VALUES
(1, 8, 2, '2026-03-20', 'retardo', 7, '2026-03-20 14:56:59'),
(2, 6, 2, '2026-03-20', 'inasistencia', 7, '2026-03-20 14:56:59'),
(3, 7, 2, '2026-03-20', 'asistencia', 7, '2026-03-20 14:56:59'),
(13, 8, 2, '2026-03-18', 'retardo', 7, '2026-03-20 16:28:51'),
(14, 6, 2, '2026-03-18', 'asistencia', 7, '2026-03-20 16:28:51'),
(15, 7, 2, '2026-03-18', 'inasistencia', 7, '2026-03-20 16:28:51'),
(16, 8, 3, '2026-03-16', 'asistencia', 7, '2026-03-20 19:18:14'),
(17, 6, 3, '2026-03-16', 'retardo', 7, '2026-03-20 19:18:14'),
(18, 7, 3, '2026-03-16', 'inasistencia', 7, '2026-03-20 19:18:14');

--
-- Disparadores `asistencias`
--
DELIMITER $$
CREATE TRIGGER `trg_asistencias_insert_audit` AFTER INSERT ON `asistencias` FOR EACH ROW BEGIN
  INSERT INTO `audit_log` (`accion`, `detalle`)
  VALUES (
    'INSERT_ASISTENCIA',
    CONCAT(
      'Alumno ID ', NEW.id_alumno,
      ' | Grupo ID ', NEW.id_grupo,
      ' | Fecha: ', NEW.fecha,
      ' | Tipo: ', NEW.tipo_asistencia,
      ' | Valor: ', NEW.valor
    )
  );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_asistencias_update_audit` AFTER UPDATE ON `asistencias` FOR EACH ROW BEGIN
  INSERT INTO `audit_log` (`accion`, `detalle`)
  VALUES (
    'UPDATE_ASISTENCIA',
    CONCAT(
      'Alumno ID ', OLD.id_alumno,
      ' | Grupo ID ', OLD.id_grupo,
      ' | Fecha: ', OLD.fecha,
      ' | Cambio: "', OLD.tipo_asistencia, '" → "', NEW.tipo_asistencia, '"',
      ' | Valor: ', OLD.valor, ' → ', NEW.valor
    )
  );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `audit_log`
--

CREATE TABLE `audit_log` (
  `id_audit` int(11) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `detalle` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `audit_log`
--

INSERT INTO `audit_log` (`id_audit`, `accion`, `fecha`, `detalle`) VALUES
(1, 'INSERT_ASISTENCIA', '2026-03-20 14:56:59', 'Alumno ID 8 | Grupo ID 2 | Fecha: 2026-03-20 | Tipo: retardo | Valor: 0.8'),
(2, 'INSERT_ASISTENCIA', '2026-03-20 14:56:59', 'Alumno ID 6 | Grupo ID 2 | Fecha: 2026-03-20 | Tipo: inasistencia | Valor: 0.0'),
(3, 'INSERT_ASISTENCIA', '2026-03-20 14:56:59', 'Alumno ID 7 | Grupo ID 2 | Fecha: 2026-03-20 | Tipo: asistencia | Valor: 1.0'),
(4, 'INSERT_ASISTENCIA', '2026-03-20 16:28:51', 'Alumno ID 8 | Grupo ID 2 | Fecha: 2026-03-18 | Tipo: retardo | Valor: 0.8'),
(5, 'INSERT_ASISTENCIA', '2026-03-20 16:28:51', 'Alumno ID 6 | Grupo ID 2 | Fecha: 2026-03-18 | Tipo: asistencia | Valor: 1.0'),
(6, 'INSERT_ASISTENCIA', '2026-03-20 16:28:51', 'Alumno ID 7 | Grupo ID 2 | Fecha: 2026-03-18 | Tipo: inasistencia | Valor: 0.0'),
(7, 'INSERT_ASISTENCIA', '2026-03-20 19:18:14', 'Alumno ID 8 | Grupo ID 3 | Fecha: 2026-03-16 | Tipo: asistencia | Valor: 1.0'),
(8, 'INSERT_ASISTENCIA', '2026-03-20 19:18:14', 'Alumno ID 6 | Grupo ID 3 | Fecha: 2026-03-16 | Tipo: retardo | Valor: 0.8'),
(9, 'INSERT_ASISTENCIA', '2026-03-20 19:18:14', 'Alumno ID 7 | Grupo ID 3 | Fecha: 2026-03-16 | Tipo: inasistencia | Valor: 0.0');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calif_actividades`
--

CREATE TABLE `calif_actividades` (
  `id_calif` int(11) NOT NULL,
  `id_actividad` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `calificacion` decimal(5,2) DEFAULT NULL COMMENT '0.00 a 10.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `calif_actividades`
--

INSERT INTO `calif_actividades` (`id_calif`, `id_actividad`, `id_alumno`, `calificacion`) VALUES
(1, 2, 8, 10.00),
(2, 2, 6, 8.00),
(3, 2, 7, 6.00),
(4, 3, 8, 10.00),
(5, 3, 6, 8.00),
(6, 3, 7, 6.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calif_tareas`
--

CREATE TABLE `calif_tareas` (
  `id_calif` int(11) NOT NULL,
  `id_tarea` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `calificacion` decimal(5,2) DEFAULT NULL COMMENT '0.00 a 10.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `calif_tareas`
--

INSERT INTO `calif_tareas` (`id_calif`, `id_tarea`, `id_alumno`, `calificacion`) VALUES
(1, 1, 8, 10.00),
(2, 1, 6, 10.00),
(3, 1, 7, 10.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docentes`
--

CREATE TABLE `docentes` (
  `id_docente` int(11) NOT NULL,
  `numero_empleado` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `docentes`
--

INSERT INTO `docentes` (`id_docente`, `numero_empleado`, `nombre`, `correo`, `id_usuario`) VALUES
(1, '123456', 'Osvaldo Bernal Piña', 'Osvaldo.Bernal@iltlac.com', 2),
(2, '123321', 'Esteban Valdez Ramirez', 'esteban.valdez@lcardenas.tecnm.mx', 7);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos`
--

CREATE TABLE `grupos` (
  `id_grupo` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL,
  `id_docente` int(11) NOT NULL,
  `nombre_grupo` varchar(50) NOT NULL COMMENT 'Ej: ISC-501-A',
  `periodo` varchar(30) NOT NULL COMMENT 'Ej: Ene-Jun 2026',
  `num_unidades` tinyint(3) UNSIGNED NOT NULL DEFAULT 4,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Relación Docente-Materia-Periodo';

--
-- Volcado de datos para la tabla `grupos`
--

INSERT INTO `grupos` (`id_grupo`, `id_materia`, `id_docente`, `nombre_grupo`, `periodo`, `num_unidades`, `activo`) VALUES
(1, 1, 1, '152T', 'ENE-JUN 2026', 4, 1),
(2, 2, 2, '11 E', 'ENE-JUN 2026', 4, 1),
(3, 3, 2, '12tT', 'AGO-DIC', 6, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupo_alumnos`
--

CREATE TABLE `grupo_alumnos` (
  `id_inscripcion` int(11) NOT NULL,
  `id_grupo` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `fecha_inscripcion` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Alumnos inscritos en cada grupo';

--
-- Volcado de datos para la tabla `grupo_alumnos`
--

INSERT INTO `grupo_alumnos` (`id_inscripcion`, `id_grupo`, `id_alumno`, `fecha_inscripcion`) VALUES
(1, 2, 6, '2026-03-20'),
(2, 2, 7, '2026-03-20'),
(3, 2, 8, '2026-03-20'),
(4, 3, 6, '2026-03-20'),
(5, 3, 7, '2026-03-20'),
(6, 3, 8, '2026-03-20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materias`
--

CREATE TABLE `materias` (
  `id_materia` int(11) NOT NULL,
  `clave_materia` varchar(20) NOT NULL COMMENT 'Clave institucional, ej: SCD-1001',
  `nombre` varchar(150) NOT NULL,
  `creditos` tinyint(3) UNSIGNED DEFAULT 5,
  `activa` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=activa, 0=inactiva'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de asignaturas del plan de estudios';

--
-- Volcado de datos para la tabla `materias`
--

INSERT INTO `materias` (`id_materia`, `clave_materia`, `nombre`, `creditos`, `activa`) VALUES
(1, 'TCS-52A', 'Hacking Peligroso', 5, 1),
(2, 'SPM-2026', 'Sistemas profesionales de mantenimiento', 3, 1),
(3, 'ET-214', 'Taller de etica', 3, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre_rol`) VALUES
(1, 'Admin'),
(3, 'Alumno'),
(2, 'Docente');

--
-- Disparadores `roles`
--
DELIMITER $$
CREATE TRIGGER `trg_roles_update_audit` AFTER UPDATE ON `roles` FOR EACH ROW BEGIN
    INSERT INTO `audit_log` (`accion`, `detalle`)
    VALUES (
        'UPDATE_ROL', 
        CONCAT('Se cambió el rol ID ', OLD.id_rol, ' de "', OLD.nombre_rol, '" a "', NEW.nombre_rol, '"')
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tareas_clase`
--

CREATE TABLE `tareas_clase` (
  `id_tarea` int(11) NOT NULL,
  `id_grupo` int(11) NOT NULL,
  `id_unidad` int(11) NOT NULL,
  `fecha` date NOT NULL COMMENT 'Fecha en que se revisa/registra',
  `nombre` varchar(150) NOT NULL COMMENT 'Ej: Tarea 2 - Funciones recursivas',
  `registrado_por` int(11) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tareas_clase`
--

INSERT INTO `tareas_clase` (`id_tarea`, `id_grupo`, `id_unidad`, `fecha`, `nombre`, `registrado_por`, `fecha_registro`) VALUES
(1, 3, 1, '2026-03-16', 'Tipos de etica', 7, '2026-03-20 19:18:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidades`
--

CREATE TABLE `unidades` (
  `id_unidad` int(11) NOT NULL,
  `id_grupo` int(11) NOT NULL,
  `numero_unidad` tinyint(3) UNSIGNED NOT NULL COMMENT 'Ej: 1, 2, 3',
  `nombre` varchar(100) DEFAULT NULL COMMENT 'Ej: Unidad 1 - Fundamentos',
  `fecha_fin` date DEFAULT NULL COMMENT 'Fecha límite que establece el docente',
  `cerrada` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=cerrada/calificada, 0=en curso',
  `fecha_cierre` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Unidades temáticas por grupo';

--
-- Volcado de datos para la tabla `unidades`
--

INSERT INTO `unidades` (`id_unidad`, `id_grupo`, `numero_unidad`, `nombre`, `fecha_fin`, `cerrada`, `fecha_cierre`) VALUES
(1, 3, 1, 'Unidad 1', NULL, 0, NULL),
(2, 3, 2, 'Unidad 2', NULL, 0, NULL),
(3, 3, 3, 'Unidad 3', NULL, 0, NULL),
(4, 3, 4, 'Unidad 4', NULL, 0, NULL),
(5, 3, 5, 'Unidad 5', NULL, 0, NULL),
(6, 3, 6, 'Unidad 6', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `id_rol` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre_usuario`, `password_hash`, `id_rol`) VALUES
(1, 'admin_valdez', '$2y$10$aFW7zJ6vPxqUvtRmYJ.iOOVZG4wc4leckszsQc98n3hgdtSXmg7uq', 1),
(2, '123456', '$2y$10$D0Edw0S9taoYbeU9hFXwHOMHN6iaxiXAArbeCWmNfDqNmts0xF8my', 2),
(3, '22560123', '$2y$10$xpc57SUjZdClEmkhODMA/uvnGxrh6R/RSzIxFntb2B0mWPwz4lPKy', 3),
(4, '22560001', '$2y$10$h6q5VT.5Y1iath3dGTAMfuOSDEnrLZ715H4jF/oKKHNFB1bpL/X1u', 3),
(5, '22560067', '$2y$10$DMjdVtKpMEyuZn22LyzeKOwT0.iEN38y1T6pP6c.yw3wdF7r/XPeO', 3),
(6, '22560510', '$2y$10$7Ambo2ZCi.we3tEH9WqTLusdwblbiKgtNSay.vcf9b5EH6l5Jf3nC', 3),
(7, '123321', '$2y$10$kH8PHa8KJokqaR2C0kDHE.dLvY/Lck/LyPkE7cAjO1nf2beFE7Ikm', 2),
(8, '25560001', '$2y$10$9XHvJl1J6qJQztYOYPBzk.EyIc9waQyLFJXQ..qSHX1ENQmhs4j2y', 3),
(9, '25560002', '$2y$10$QM0XEiakxg5TYgNwSmMbnORaukdAfR3RUHfkUf9sSxEaT2HyJu1ma', 3),
(10, '25560003', '$2y$10$fyLJnUP8VTgtSFgU.jkzteIMfjNs1Gy8Eiw.Hjy/GIC8JgZE0DD2m', 3);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_porcentaje_asistencia`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_porcentaje_asistencia` (
`id_alumno` int(11)
,`numero_control` varchar(20)
,`nombre_alumno` varchar(100)
,`id_grupo` int(11)
,`nombre_grupo` varchar(50)
,`materia` varchar(150)
,`periodo` varchar(30)
,`total_clases` bigint(21)
,`puntos_acumulados` decimal(25,1)
,`porcentaje_asistencia` decimal(30,2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_promedios_unidad`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_promedios_unidad` (
`id_alumno` int(11)
,`numero_control` varchar(20)
,`nombre_alumno` varchar(100)
,`id_unidad` int(11)
,`numero_unidad` tinyint(3) unsigned
,`nombre_unidad` varchar(100)
,`cerrada` tinyint(1)
,`id_grupo` int(11)
,`nombre_grupo` varchar(50)
,`materia` varchar(150)
,`periodo` varchar(30)
,`total_clases` bigint(21)
,`calif_asistencia` decimal(29,2)
,`promedio_actividades` decimal(6,2)
,`promedio_tareas` decimal(6,2)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_porcentaje_asistencia`
--
DROP TABLE IF EXISTS `vista_porcentaje_asistencia`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_porcentaje_asistencia`  AS SELECT `a`.`id_alumno` AS `id_alumno`, `al`.`numero_control` AS `numero_control`, `al`.`nombre` AS `nombre_alumno`, `g`.`id_grupo` AS `id_grupo`, `g`.`nombre_grupo` AS `nombre_grupo`, `m`.`nombre` AS `materia`, `g`.`periodo` AS `periodo`, count(`a`.`id_asistencia`) AS `total_clases`, sum(`a`.`valor`) AS `puntos_acumulados`, round(sum(`a`.`valor`) / count(`a`.`id_asistencia`) * 100,2) AS `porcentaje_asistencia` FROM (((`asistencias` `a` join `alumnos` `al` on(`al`.`id_alumno` = `a`.`id_alumno`)) join `grupos` `g` on(`g`.`id_grupo` = `a`.`id_grupo`)) join `materias` `m` on(`m`.`id_materia` = `g`.`id_materia`)) GROUP BY `a`.`id_alumno`, `al`.`numero_control`, `al`.`nombre`, `g`.`id_grupo`, `g`.`nombre_grupo`, `m`.`nombre`, `g`.`periodo` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_promedios_unidad`
--
DROP TABLE IF EXISTS `vista_promedios_unidad`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_promedios_unidad`  AS SELECT `ga`.`id_alumno` AS `id_alumno`, `al`.`numero_control` AS `numero_control`, `al`.`nombre` AS `nombre_alumno`, `u`.`id_unidad` AS `id_unidad`, `u`.`numero_unidad` AS `numero_unidad`, `u`.`nombre` AS `nombre_unidad`, `u`.`cerrada` AS `cerrada`, `g`.`id_grupo` AS `id_grupo`, `g`.`nombre_grupo` AS `nombre_grupo`, `m`.`nombre` AS `materia`, `g`.`periodo` AS `periodo`, count(distinct `a`.`fecha`) AS `total_clases`, round(coalesce(sum(`a`.`valor`),0) / nullif(count(distinct `a`.`fecha`),0) * 10,2) AS `calif_asistencia`, (select round(avg(`ca`.`calificacion`),2) from (`calif_actividades` `ca` join `actividades_clase` `ac` on(`ac`.`id_actividad` = `ca`.`id_actividad`)) where `ca`.`id_alumno` = `ga`.`id_alumno` and `ac`.`id_unidad` = `u`.`id_unidad`) AS `promedio_actividades`, (select round(avg(`ct`.`calificacion`),2) from (`calif_tareas` `ct` join `tareas_clase` `tc` on(`tc`.`id_tarea` = `ct`.`id_tarea`)) where `ct`.`id_alumno` = `ga`.`id_alumno` and `tc`.`id_unidad` = `u`.`id_unidad`) AS `promedio_tareas` FROM (((((`grupo_alumnos` `ga` join `alumnos` `al` on(`al`.`id_alumno` = `ga`.`id_alumno`)) join `grupos` `g` on(`g`.`id_grupo` = `ga`.`id_grupo`)) join `materias` `m` on(`m`.`id_materia` = `g`.`id_materia`)) join `unidades` `u` on(`u`.`id_grupo` = `g`.`id_grupo`)) left join `asistencias` `a` on(`a`.`id_alumno` = `ga`.`id_alumno` and `a`.`id_grupo` = `g`.`id_grupo`)) GROUP BY `ga`.`id_alumno`, `al`.`numero_control`, `al`.`nombre`, `u`.`id_unidad`, `u`.`numero_unidad`, `u`.`nombre`, `u`.`cerrada`, `g`.`id_grupo`, `g`.`nombre_grupo`, `m`.`nombre`, `g`.`periodo` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `actividades_clase`
--
ALTER TABLE `actividades_clase`
  ADD PRIMARY KEY (`id_actividad`),
  ADD UNIQUE KEY `idx_actividad_dia` (`id_grupo`,`fecha`,`nombre`),
  ADD KEY `fk_act_unidad` (`id_unidad`),
  ADD KEY `fk_act_docente` (`registrado_por`);

--
-- Indices de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  ADD PRIMARY KEY (`id_alumno`),
  ADD UNIQUE KEY `idx_numero_control` (`numero_control`),
  ADD KEY `fk_alumno_usuario` (`id_usuario`);

--
-- Indices de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD PRIMARY KEY (`id_asistencia`),
  ADD UNIQUE KEY `idx_unico_asistencia` (`id_alumno`,`id_grupo`,`fecha`),
  ADD KEY `fk_asistencia_grupo` (`id_grupo`),
  ADD KEY `fk_asistencia_registrada_por` (`registrado_por`);

--
-- Indices de la tabla `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id_audit`);

--
-- Indices de la tabla `calif_actividades`
--
ALTER TABLE `calif_actividades`
  ADD PRIMARY KEY (`id_calif`),
  ADD UNIQUE KEY `idx_calif_act_alumno` (`id_actividad`,`id_alumno`),
  ADD KEY `fk_califact_alumno` (`id_alumno`);

--
-- Indices de la tabla `calif_tareas`
--
ALTER TABLE `calif_tareas`
  ADD PRIMARY KEY (`id_calif`),
  ADD UNIQUE KEY `idx_calif_tar_alumno` (`id_tarea`,`id_alumno`),
  ADD KEY `fk_califtar_alumno` (`id_alumno`);

--
-- Indices de la tabla `docentes`
--
ALTER TABLE `docentes`
  ADD PRIMARY KEY (`id_docente`),
  ADD UNIQUE KEY `idx_numero_empleado` (`numero_empleado`),
  ADD KEY `fk_docente_usuario` (`id_usuario`);

--
-- Indices de la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`id_grupo`),
  ADD UNIQUE KEY `idx_grupo_unico` (`id_materia`,`id_docente`,`nombre_grupo`,`periodo`),
  ADD KEY `fk_grupo_docente` (`id_docente`);

--
-- Indices de la tabla `grupo_alumnos`
--
ALTER TABLE `grupo_alumnos`
  ADD PRIMARY KEY (`id_inscripcion`),
  ADD UNIQUE KEY `idx_alumno_grupo` (`id_grupo`,`id_alumno`),
  ADD KEY `fk_inscripcion_alumno` (`id_alumno`);

--
-- Indices de la tabla `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`id_materia`),
  ADD UNIQUE KEY `idx_clave_materia` (`clave_materia`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `nombre_rol` (`nombre_rol`);

--
-- Indices de la tabla `tareas_clase`
--
ALTER TABLE `tareas_clase`
  ADD PRIMARY KEY (`id_tarea`),
  ADD UNIQUE KEY `idx_tarea_dia` (`id_grupo`,`fecha`,`nombre`),
  ADD KEY `fk_tar_unidad` (`id_unidad`),
  ADD KEY `fk_tar_docente` (`registrado_por`);

--
-- Indices de la tabla `unidades`
--
ALTER TABLE `unidades`
  ADD PRIMARY KEY (`id_unidad`),
  ADD UNIQUE KEY `idx_unidad_grupo` (`id_grupo`,`numero_unidad`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD KEY `fk_usuario_rol` (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `actividades_clase`
--
ALTER TABLE `actividades_clase`
  MODIFY `id_actividad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  MODIFY `id_alumno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  MODIFY `id_asistencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id_audit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `calif_actividades`
--
ALTER TABLE `calif_actividades`
  MODIFY `id_calif` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `calif_tareas`
--
ALTER TABLE `calif_tareas`
  MODIFY `id_calif` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `docentes`
--
ALTER TABLE `docentes`
  MODIFY `id_docente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `grupos`
--
ALTER TABLE `grupos`
  MODIFY `id_grupo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `grupo_alumnos`
--
ALTER TABLE `grupo_alumnos`
  MODIFY `id_inscripcion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `materias`
--
ALTER TABLE `materias`
  MODIFY `id_materia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tareas_clase`
--
ALTER TABLE `tareas_clase`
  MODIFY `id_tarea` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `unidades`
--
ALTER TABLE `unidades`
  MODIFY `id_unidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `actividades_clase`
--
ALTER TABLE `actividades_clase`
  ADD CONSTRAINT `fk_act_docente` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_act_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_act_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`) ON DELETE CASCADE;

--
-- Filtros para la tabla `alumnos`
--
ALTER TABLE `alumnos`
  ADD CONSTRAINT `fk_alumno_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD CONSTRAINT `fk_asistencia_alumno` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asistencia_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asistencia_registrada_por` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `calif_actividades`
--
ALTER TABLE `calif_actividades`
  ADD CONSTRAINT `fk_califact_actividad` FOREIGN KEY (`id_actividad`) REFERENCES `actividades_clase` (`id_actividad`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_califact_alumno` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE;

--
-- Filtros para la tabla `calif_tareas`
--
ALTER TABLE `calif_tareas`
  ADD CONSTRAINT `fk_califtar_alumno` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_califtar_tarea` FOREIGN KEY (`id_tarea`) REFERENCES `tareas_clase` (`id_tarea`) ON DELETE CASCADE;

--
-- Filtros para la tabla `docentes`
--
ALTER TABLE `docentes`
  ADD CONSTRAINT `fk_docente_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD CONSTRAINT `fk_grupo_docente` FOREIGN KEY (`id_docente`) REFERENCES `docentes` (`id_docente`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_grupo_materia` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `grupo_alumnos`
--
ALTER TABLE `grupo_alumnos`
  ADD CONSTRAINT `fk_inscripcion_alumno` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inscripcion_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tareas_clase`
--
ALTER TABLE `tareas_clase`
  ADD CONSTRAINT `fk_tar_docente` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tar_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tar_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`) ON DELETE CASCADE;

--
-- Filtros para la tabla `unidades`
--
ALTER TABLE `unidades`
  ADD CONSTRAINT `fk_unidad_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
