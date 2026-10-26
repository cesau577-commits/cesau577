-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Servidor: (omitido)
-- Tiempo de generación: 22-10-2026 a las 10:49:41
-- Versión del servidor: 11.4.13-MariaDB
-- Versión de PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `grupoproyecto`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cv_accesos`
--

CREATE TABLE `cv_accesos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `resultado` enum('Correcto','Fallido') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cv_accesos`
--
-- (datos omitidos; use carga_masiva_datos.sql)


-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cv_correos`
--

CREATE TABLE `cv_correos` (
  `id` int(11) NOT NULL,
  `visita_id` int(11) NOT NULL,
  `destinatario` varchar(190) NOT NULL,
  `estado` enum('Pendiente','Enviada','Error') NOT NULL DEFAULT 'Pendiente',
  `intentos` int(11) NOT NULL DEFAULT 0,
  `ultimo_error` varchar(255) DEFAULT NULL,
  `enviado_en` datetime DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cv_correos`
--
-- (datos omitidos; use carga_masiva_datos.sql)


-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cv_notificaciones`
--

CREATE TABLE `cv_notificaciones` (
  `id` int(11) NOT NULL,
  `mensaje` varchar(255) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `usuario_id` int(11) DEFAULT NULL,
  `visita_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cv_personal`
--

CREATE TABLE `cv_personal` (
  `id` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `cargo` varchar(80) NOT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `correo` varchar(190) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cv_personal`
--
-- (datos omitidos; use carga_masiva_datos.sql)


-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cv_usuarios`
--

CREATE TABLE `cv_usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('administrador','recepcionista','docente') NOT NULL DEFAULT 'recepcionista',
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cv_usuarios`
--
-- (datos omitidos; use carga_masiva_datos.sql)


-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cv_visitantes`
--

CREATE TABLE `cv_visitantes` (
  `id` int(11) NOT NULL,
  `documento` varchar(30) DEFAULT NULL,
  `nombre` varchar(120) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `institucion` varchar(120) DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cv_visitantes`
--
-- (datos omitidos; use carga_masiva_datos.sql)


-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cv_visitas`
--

CREATE TABLE `cv_visitas` (
  `id` int(11) NOT NULL,
  `visitante_id` int(11) NOT NULL,
  `personal_id` int(11) DEFAULT NULL,
  `motivo` varchar(255) NOT NULL,
  `fecha` date NOT NULL,
  `hora_entrada` datetime NOT NULL DEFAULT current_timestamp(),
  `hora_salida` datetime DEFAULT NULL,
  `estado` enum('Dentro','Finalizada') NOT NULL DEFAULT 'Dentro',
  `registrado_por` int(11) DEFAULT NULL,
  `notificacion_estado` enum('Pendiente','Enviada','Error','Sin correo') NOT NULL DEFAULT 'Pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cv_visitas`
--
-- (datos omitidos; use carga_masiva_datos.sql)


--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cv_accesos`
--
ALTER TABLE `cv_accesos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `cv_correos`
--
ALTER TABLE `cv_correos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_visita_correo` (`visita_id`);

--
-- Indices de la tabla `cv_notificaciones`
--
ALTER TABLE `cv_notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `fk_cv_notif_visita` (`visita_id`);

--
-- Indices de la tabla `cv_personal`
--
ALTER TABLE `cv_personal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cv_personal_usuario` (`usuario_id`);

--
-- Indices de la tabla `cv_usuarios`
--
ALTER TABLE `cv_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Indices de la tabla `cv_visitantes`
--
ALTER TABLE `cv_visitantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `documento` (`documento`);

--
-- Indices de la tabla `cv_visitas`
--
ALTER TABLE `cv_visitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visitante_id` (`visitante_id`),
  ADD KEY `personal_id` (`personal_id`),
  ADD KEY `registrado_por` (`registrado_por`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cv_accesos`
--
ALTER TABLE `cv_accesos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de la tabla `cv_correos`
--
ALTER TABLE `cv_correos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `cv_notificaciones`
--
ALTER TABLE `cv_notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cv_personal`
--
ALTER TABLE `cv_personal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `cv_usuarios`
--
ALTER TABLE `cv_usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `cv_visitantes`
--
ALTER TABLE `cv_visitantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `cv_visitas`
--
ALTER TABLE `cv_visitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cv_accesos`
--
ALTER TABLE `cv_accesos`
  ADD CONSTRAINT `cv_accesos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `cv_usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `cv_correos`
--
ALTER TABLE `cv_correos`
  ADD CONSTRAINT `cv_correos_ibfk_1` FOREIGN KEY (`visita_id`) REFERENCES `cv_visitas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cv_notificaciones`
--
ALTER TABLE `cv_notificaciones`
  ADD CONSTRAINT `cv_notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `cv_usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cv_notif_visita` FOREIGN KEY (`visita_id`) REFERENCES `cv_visitas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cv_personal`
--
ALTER TABLE `cv_personal`
  ADD CONSTRAINT `fk_cv_personal_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `cv_usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `cv_visitas`
--
ALTER TABLE `cv_visitas`
  ADD CONSTRAINT `cv_visitas_ibfk_1` FOREIGN KEY (`visitante_id`) REFERENCES `cv_visitantes` (`id`),
  ADD CONSTRAINT `cv_visitas_ibfk_2` FOREIGN KEY (`personal_id`) REFERENCES `cv_personal` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cv_visitas_ibfk_3` FOREIGN KEY (`registrado_por`) REFERENCES `cv_usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
