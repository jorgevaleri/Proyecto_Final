-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 08-11-2025 a las 21:00:56
-- Versión del servidor: 10.4.27-MariaDB
-- Versión de PHP: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `seminario`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditorias`
--

CREATE TABLE `auditorias` (
  `auditoria_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(150) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` varchar(64) DEFAULT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `categorias_id` int(5) NOT NULL,
  `usuarios_id` int(5) NOT NULL,
  `permisos_id` int(5) NOT NULL,
  `categorias_eliminado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`categorias_id`, `usuarios_id`, `permisos_id`, `categorias_eliminado`) VALUES
(1, 1, 1, 0),
(2, 1, 2, 0),
(3, 1, 3, 0),
(4, 1, 4, 0),
(5, 1, 5, 0),
(6, 1, 6, 0),
(7, 1, 7, 0),
(8, 1, 8, 0),
(9, 1, 9, 0),
(10, 1, 10, 0),
(11, 1, 11, 0),
(12, 1, 12, 0),
(13, 1, 13, 0),
(14, 1, 14, 0),
(15, 1, 15, 0),
(16, 1, 16, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `domicilios`
--

CREATE TABLE `domicilios` (
  `domicilios_id` int(5) NOT NULL,
  `domicilios_calle` varchar(50) NOT NULL,
  `domicilios_latitud` double NOT NULL,
  `domicilios_longitud` double NOT NULL,
  `personas_id` int(5) NOT NULL,
  `domicilios_descripcion` varchar(20) NOT NULL,
  `domicilios_predeterminado` tinyint(1) NOT NULL DEFAULT 1,
  `domicilios_habilitado` tinyint(1) NOT NULL DEFAULT 1,
  `domicilios_fechcrea` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `domicilios_eliminado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `domicilios`
--

INSERT INTO `domicilios` (`domicilios_id`, `domicilios_calle`, `domicilios_latitud`, `domicilios_longitud`, `personas_id`, `domicilios_descripcion`, `domicilios_predeterminado`, `domicilios_habilitado`, `domicilios_fechcrea`, `domicilios_eliminado`) VALUES
(1, 'AAA', 0, 0, 1, 'Administrador', 1, 1, '2023-10-30 20:49:53', 0),
(2, 'Prueba1', 1, 2, 0, '', 1, 1, '2023-10-30 20:52:54', 0),
(3, 'OCAMPO', 0, 0, 7, '', 1, 1, '2024-10-29 14:25:42', 0),
(4, 'LATZINA', 0, 0, 10, '', 1, 1, '2024-10-29 18:58:47', 0),
(5, 'LATZINA', 0, 0, 11, '', 1, 1, '2024-10-29 19:39:59', 0),
(6, 'LATZINA', 0, 0, 12, '', 1, 1, '2024-10-29 19:41:41', 0),
(7, 'FLORIDA Y JUNIN', -28, -66, 0, '', 1, 1, '2025-07-24 15:23:08', 0),
(8, 'BARRIO 920', -28, -66, 0, '', 1, 1, '2025-07-24 15:26:47', 0),
(9, 'FLORIDA Y JUNIN 2025', -28, -66, 0, '', 1, 1, '2025-07-24 15:29:10', 0),
(10, 'Av. Francisco Latzina 1042', -28, -66, 0, '', 1, 1, '2025-07-24 20:17:07', 0),
(11, 'Av. Francisco Latzina 1042', -28, -66, 0, '', 1, 1, '2025-07-24 20:20:58', 0),
(12, 'AV. FRANCISCO LATZINA 1042', -28, -66, 0, '', 1, 1, '2025-07-24 20:24:49', 0),
(13, 'AV. FRANCISCO LATZINA 1042', -28, -66, 0, '', 1, 1, '2025-07-24 20:25:39', 0),
(14, 'FRANCISCO LATZINA 1042', -28, -66, 0, '', 1, 1, '2025-07-24 20:36:35', 0),
(15, '1231231321', -28, -66, 0, '', 1, 1, '2025-07-24 21:19:11', 0),
(16, 'FLORIDA 331', -28, -66, 0, '', 1, 1, '2025-07-25 22:10:07', 0),
(17, 'FLORIDA 331', -28, -66, 0, '', 1, 1, '2025-10-01 23:39:13', 0),
(18, 'ocampo 555', -28, -66, 0, '', 1, 1, '2025-07-25 22:34:48', 0),
(33, 'TEREBINTOS 300', -28.4303908, -65.7829371, 204, 'CASA', 1, 1, '2025-07-31 00:17:42', 0),
(38, 'AV. FRANCISCO LATZINA 1042', -28.477575378667744, -65.78658342361452, 201, '27-07-2025 SALVADOR', 1, 1, '2025-08-02 12:35:42', 0),
(41, 'AV OCAMPO 330', -28.4704345, -65.7908233, 206, 'CASA', 1, 1, '2025-08-02 12:38:29', 0),
(42, 'REPUBLICA 104', -28.4686877, -65.785335, 206, 'TRABAJO', 0, 1, '2025-08-02 12:38:30', 0),
(43, 'cordoba 333', -28.484355825422274, -65.78185200691225, 202, 'casa', 1, 1, '2025-08-02 12:54:55', 0),
(44, 'florida 331', -28.48007, -65.7818851, 202, 'escuela', 0, 1, '2025-08-02 12:54:55', 0),
(45, 'REPUBLICA 104', -28.4686877, -65.785335, 203, 'CASA', 1, 1, '2025-08-02 13:23:48', 0),
(46, 'LAS TEJAS', -28.658828088875506, -65.7830858230591, 205, 'CASA', 1, 1, '2025-08-02 13:30:44', 0),
(47, 'AV. FRANCISCO LATZINA ', 0, 0, 200, '27-07-2025', 1, 1, '2025-08-02 14:49:17', 0),
(48, 'san martin 140', -28.4699255, -65.7848813, 4, 'instituto', 1, 1, '2025-08-12 14:31:11', 0),
(49, 'INTENDENTE MEDINA 487', -28.467955639289368, -65.80054163932802, 207, 'CASA', 1, 1, '2025-08-14 14:35:19', 0),
(50, 'OCAMPO 448', -28.4704255, -65.7921509, 207, 'TRABAJO', 0, 1, '2025-08-14 14:35:19', 0),
(51, 'EL AYBAL', -29.1092904, -65.3612779, 208, 'CASA', 1, 1, '2025-08-14 14:42:16', 0),
(52, 'AV. FRANCISCO LATZINA 1042', -28.4752204, -65.8004823, 209, '14825', 1, 1, '2025-08-14 14:55:42', 0),
(53, 'OCAMPO 123', -28.5042883, -65.7890652, 209, 'TRABAJO', 0, 1, '2025-08-14 14:55:42', 0),
(54, 'casa 111', 0, 0, 210, '', 1, 1, '2025-08-14 15:55:27', 0),
(55, 'OCAMPO 888', -28.4704775, -65.7976868, 211, 'CASA', 1, 1, '2025-08-14 20:16:56', 0),
(56, 'AV. FRANCISCO LATZINA 1042', -28.4752204, -65.8004823, 212, 'CASA', 1, 1, '2025-08-15 16:03:27', 0),
(57, 'AV. FRANCISCO LATZINA 1042', -28.4752204, -65.8004823, 213, 'CASA', 1, 1, '2025-08-15 16:14:33', 0),
(58, 'AV. FRANCISCO LATZINA 1042', -28.4752204, -65.8004823, 214, 'CASA', 1, 1, '2025-08-15 16:16:16', 0),
(59, 'AV. FRANCISCO LATZINA 1042', -28.4752204, -65, 216, '666', 1, 1, '2025-09-22 15:43:25', 0),
(60, 'LAS TEJAS S/N', -28.6592523, -65, 217, '999', 1, 1, '2025-09-22 23:24:16', 0),
(61, 'OCAMPO 999', -28.4703254, -65, 217, '999', 0, 1, '2025-09-22 23:24:16', 0),
(62, 'AV. FRANCISCO LATZINA 1042', -28.4752204, -65, 218, '666 999', 1, 1, '2025-09-23 22:47:49', 0),
(63, 'AV. FRANCISCO LATZINA 1042', -28.4752204, -65.8004823, 220, 'CASA', 1, 1, '2025-09-29 12:38:06', 0),
(64, 'OCAMPO 888', -28.4704775, -65.7976868, 220, 'CASA 2', 0, 1, '2025-09-29 12:38:06', 0),
(65, 'AV. FRANCISCO LATZINA 1042', -28.4752204, -65.8004823, 221, 'CASA', 1, 1, '2025-09-29 13:39:54', 0),
(66, 'AV. FRANCISCO LATZINA 1042', -28.4752204, -65.8004823, 2, 'CASA', 1, 1, '2025-09-29 13:41:34', 0),
(67, 'AV. FRANCISCO LATZINA 1042', -28.4752204, -65.8004823, 222, 'CASA 99899', 1, 1, '2025-09-29 14:04:35', 0),
(68, 'AV. FRANCISCO LATZINA 1042', -28.4752204, -65.8004823, 3, 'CASA', 1, 1, '2025-09-29 14:15:24', 0),
(69, 'SARMIENTO 550', -28.4677001, -65.7796927, 3, 'OFICINA', 0, 1, '2025-09-29 14:15:25', 0),
(72, 'FLORIDA 331', -28.4702492, -65.7933954, 0, '', 1, 1, '2025-10-03 16:58:48', 0),
(73, 'cordoba 333', -28.4844498, -65.7818565, 0, '', 1, 1, '2025-10-08 14:57:24', 0),
(74, 'OCAMPO 555', -28.4702492, -65.7933954, 0, '', 1, 1, '2025-10-08 14:58:39', 0),
(75, 'FLORIDA 666', -28.479648, -65.7770207, 0, '', 1, 1, '2025-10-10 13:27:54', 0),
(76, 'FLORIDA 331', -28.48007, -65.7818851, 0, '', 1, 1, '2025-10-10 13:52:07', 0),
(77, 'LAS TEJAS', -28.6592523, -65.7850486, 0, '', 1, 1, '2025-10-14 14:26:19', 0),
(78, 'ocampo 666', 0, 0, 224, 'asdf', 1, 1, '2025-10-16 23:33:17', 0),
(79, 'OCAMPO 888', -28.4704775, -65.7976868, 225, '', 1, 1, '2025-10-17 13:52:12', 0),
(80, 'LATZINA 1042', -28.4752204, -65.8004823, 225, '', 0, 1, '2025-10-17 13:52:12', 0),
(83, 'FRANCISCO LATZINA 1042', -28.4752204, -65.8004823, 226, '', 1, 1, '2025-10-17 14:01:40', 0),
(84, 'FRANCISCO LATZINA 1042', -28.4752204, -65.8004823, 226, '', 0, 1, '2025-10-17 14:01:40', 0),
(88, 'FRANCISCO LATZINA 1042', -28.4752204, -65.8004823, 223, 'asas23232', 1, 1, '2025-10-30 16:03:59', 0),
(89, 'Intendente Medina 487', -28.4645849, -65.800698, 223, 'Casa 3', 0, 1, '2025-10-30 16:03:59', 0),
(90, 'cordoba 333', -31.4127026, -64.17753, 223, 'asdfa', 0, 1, '2025-10-30 16:03:59', 0),
(91, 'SARMIENTO 333', -28.4649214, -65.7799809, 227, 'CASA', 1, 1, '2025-11-02 15:14:36', 0),
(92, 'belgrano 20', -28.4606224, -65.786991, 228, 'CASA', 1, 1, '2025-11-02 15:19:45', 0),
(93, 'AV. FRANCISCO LATZINA 1042', -28.4752204, -65.8004823, 228, 'empleo', 0, 1, '2025-11-02 15:19:45', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `escuelas`
--

CREATE TABLE `escuelas` (
  `escuelas_id` int(5) NOT NULL,
  `escuelas_nombre` varchar(100) NOT NULL,
  `escuelas_cue` int(10) NOT NULL,
  `domicilios_id` int(5) NOT NULL,
  `escuelas_eliminado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `escuelas`
--

INSERT INTO `escuelas` (`escuelas_id`, `escuelas_nombre`, `escuelas_cue`, `domicilios_id`, `escuelas_eliminado`) VALUES
(1, 'Escuela de Prueba', 0, 2, 0),
(2, 'edja 38', 1000842, 0, 0),
(3, 'edja 61', 1000345, 0, 0),
(4, 'escuela de jorge valeri', 666, 0, 0),
(5, 'escuela ejemplo', 12345, 0, 1),
(6, 'escuela de Tony', 123456, 0, 0),
(7, 'ESCUELA CARINA', 123456, 0, 1),
(8, 'EDJA 38 666', 100000, 9, 0),
(9, 'escuela jorge', 123456, 10, 0),
(10, 'ESCUELA JORGE 2', 123456, 11, 0),
(11, 'ESCUELA JORGE 3', 123456, 12, 0),
(12, 'ESCUELA JORGE 4', 123456, 13, 0),
(13, 'ESCUELA JORGE 5', 123456, 14, 0),
(14, 'ESCUELA 2', 123, 15, 1),
(15, 'ESCUELA JULIO 2025', 123456, 17, 0),
(16, 'ESCUELA JULIO 666', 123456, 18, 0),
(17, 'ESCUELA 2025103', 123456, 72, 0),
(18, 'ESCUELA MAYRA bonita', 14789632, 73, 0),
(19, 'ESCUELA OCTUBRE 2025', 123456897, 74, 0),
(20, 'ESCUELA OCTUBRE 2025 2', 1234567539, 75, 0),
(21, 'ESCUELA OCTUBRE 2025 3', 2147483647, 76, 0),
(22, 'ESCUELA LAS TEJAS 2025', 100038, 77, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `formaciones_profesionales`
--

CREATE TABLE `formaciones_profesionales` (
  `formaciones_profesionales_id` int(5) NOT NULL,
  `formaciones_profesionales_nombre` varchar(100) NOT NULL,
  `formaciones_profesionales_eliminado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `formaciones_profesionales`
--

INSERT INTO `formaciones_profesionales` (`formaciones_profesionales_id`, `formaciones_profesionales_nombre`, `formaciones_profesionales_eliminado`) VALUES
(1, 'Informatica', 0),
(2, 'Manualidades', 0),
(3, 'Electricidad', 0),
(4, 'Herreria', 0),
(5, 'Refrigeracion', 0),
(6, 'Mecanica de Motos', 0),
(7, 'Plomeria', 0),
(8, 'Gasista', 0),
(9, 'Curtiembre', 0),
(10, 'Peluqueria', 0),
(12, 'Informatica', 1),
(13, 'INFORMATICA 2', 1),
(14, 'INFORMATICA 2', 1),
(15, 'MANUALIDADES 2', 1),
(16, 'INFORMATICA jorge valeri', 0),
(17, 'prueba 456', 1),
(18, 'INFORMATICA JORGE 2025 AL 2030', 1),
(19, 'INFORMATICA SALVADOR Y CATALINA', 0),
(20, 'INFORMATICA 2025-10', 0),
(21, 'HERRERIA 2025', 0),
(22, 'INFORMATICA', 0),
(23, 'INFORMATICA 2026', 0),
(24, 'Informatica 123', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones_alumnos`
--

CREATE TABLE `inscripciones_alumnos` (
  `inscripcion_id` int(11) NOT NULL,
  `personas_id` int(11) NOT NULL,
  `escuelas_id` int(11) NOT NULL,
  `formaciones_profesionales_id` int(11) NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `anio_ingreso` int(11) NOT NULL,
  `estado` enum('CURSANDO','PROMOCIONO','ABANDONO') NOT NULL DEFAULT 'CURSANDO',
  `fecha_estado` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inscripciones_alumnos`
--

INSERT INTO `inscripciones_alumnos` (`inscripcion_id`, `personas_id`, `escuelas_id`, `formaciones_profesionales_id`, `fecha_ingreso`, `anio_ingreso`, `estado`, `fecha_estado`, `created_at`, `updated_at`) VALUES
(2, 202, 2, 9, '2025-08-02', 0, 'CURSANDO', NULL, '2025-08-02 23:58:18', '2025-08-02 23:58:18'),
(3, 199, 2, 9, '2025-08-02', 0, 'CURSANDO', NULL, '2025-08-02 23:58:19', '2025-08-02 23:58:19'),
(4, 13, 2, 9, '2025-08-02', 0, 'CURSANDO', NULL, '2025-08-02 23:58:19', '2025-08-02 23:58:19'),
(5, 202, 2, 9, '2025-08-02', 0, 'CURSANDO', NULL, '2025-08-03 02:55:13', '2025-08-03 02:55:13'),
(6, 199, 2, 9, '2025-08-02', 0, 'CURSANDO', NULL, '2025-08-03 02:55:13', '2025-08-03 02:55:13'),
(7, 13, 2, 9, '2025-08-02', 0, 'CURSANDO', NULL, '2025-08-03 02:55:14', '2025-08-03 02:55:14'),
(11, 205, 2, 1, '2025-08-03', 0, 'CURSANDO', NULL, '2025-08-03 03:00:49', '2025-08-03 03:00:49'),
(12, 201, 2, 1, '2025-08-03', 0, 'CURSANDO', NULL, '2025-08-03 03:00:49', '2025-08-03 03:00:49'),
(13, 206, 2, 1, '2025-08-03', 0, 'CURSANDO', NULL, '2025-08-03 03:00:50', '2025-08-03 03:00:50'),
(14, 205, 2, 1, '2025-08-03', 0, 'CURSANDO', NULL, '2025-08-03 03:20:04', '2025-08-03 03:20:04'),
(15, 201, 2, 1, '2025-08-03', 0, 'CURSANDO', NULL, '2025-08-03 03:20:04', '2025-08-03 03:20:04'),
(16, 206, 2, 1, '2025-08-03', 0, 'CURSANDO', NULL, '2025-08-03 03:20:04', '2025-08-03 03:20:04'),
(17, 205, 2, 1, '2025-08-03', 2025, 'CURSANDO', '2025-08-03', '2025-08-03 03:23:19', '2025-08-03 13:01:48'),
(18, 201, 2, 1, '2025-08-03', 2025, 'CURSANDO', '2025-08-03', '2025-08-03 03:23:19', '2025-08-03 13:10:32'),
(20, 205, 2, 1, '2025-08-03', 2026, 'CURSANDO', NULL, '2025-08-03 03:33:34', '2025-08-03 03:33:34'),
(21, 206, 2, 1, '2025-08-03', 2025, 'CURSANDO', NULL, '2025-08-03 13:23:23', '2025-08-03 13:23:23'),
(22, 201, 2, 1, '2025-08-03', 2027, 'PROMOCIONO', '2025-08-03', '2025-08-03 13:43:07', '2025-08-03 13:43:15'),
(23, 206, 2, 1, '2025-08-03', 2027, 'ABANDONO', '2025-08-03', '2025-08-03 13:43:07', '2025-08-03 13:43:16'),
(24, 205, 2, 1, '2025-08-03', 2028, 'ABANDONO', '2025-10-27', '2025-08-03 13:59:48', '2025-10-27 14:25:28'),
(25, 201, 2, 1, '2025-08-04', 2028, 'PROMOCIONO', '2025-10-27', '2025-08-05 01:24:33', '2025-10-27 13:17:48'),
(26, 206, 2, 1, '2025-08-04', 2028, 'CURSANDO', '2025-10-27', '2025-08-05 02:01:17', '2025-10-27 13:17:48'),
(27, 205, 2, 1, '2025-08-05', 2020, 'ABANDONO', '2025-08-07', '2025-08-05 23:51:02', '2025-08-06 23:25:58'),
(28, 201, 2, 1, '2025-08-05', 2020, 'CURSANDO', '2025-08-07', '2025-08-05 23:51:03', '2025-08-06 23:25:40'),
(29, 206, 2, 1, '2025-08-05', 2020, 'PROMOCIONO', '2025-08-07', '2025-08-05 23:51:03', '2025-08-06 23:25:57'),
(30, 205, 2, 1, '2025-08-05', 2021, 'CURSANDO', '2025-08-07', '2025-08-06 00:10:56', '2025-08-06 23:07:43'),
(31, 201, 2, 1, '2025-08-06', 2021, 'ABANDONO', '2025-08-07', '2025-08-06 23:07:40', '2025-08-06 23:24:23'),
(32, 206, 2, 1, '2025-08-06', 2021, 'CURSANDO', NULL, '2025-08-06 23:24:48', '2025-08-06 23:24:48'),
(33, 209, 13, 16, '2025-08-15', 2020, 'CURSANDO', NULL, '2025-08-15 15:18:47', '2025-08-15 15:18:47'),
(34, 205, 13, 16, '2025-08-15', 2029, 'CURSANDO', '2025-08-15', '2025-08-15 16:15:04', '2025-08-15 16:15:16'),
(35, 212, 13, 16, '2025-08-15', 2029, 'CURSANDO', '2025-08-15', '2025-08-15 16:15:04', '2025-08-15 16:15:16'),
(36, 213, 13, 16, '2025-08-15', 2029, 'CURSANDO', '2025-08-15', '2025-08-15 16:15:05', '2025-08-15 16:15:16'),
(37, 201, 13, 16, '2025-08-15', 2029, 'CURSANDO', '2025-08-15', '2025-08-15 16:15:05', '2025-08-15 16:15:16'),
(38, 206, 13, 16, '2025-08-15', 2029, 'CURSANDO', '2025-08-15', '2025-08-15 16:15:05', '2025-08-15 16:15:16'),
(39, 209, 13, 16, '2025-08-15', 2029, 'CURSANDO', '2025-08-15', '2025-08-15 16:15:05', '2025-08-15 16:15:16'),
(40, 214, 13, 16, '2025-08-15', 2029, 'CURSANDO', NULL, '2025-08-15 16:16:31', '2025-08-15 16:16:31'),
(41, 225, 2, 1, '2025-10-21', 2028, 'CURSANDO', '2025-10-27', '2025-10-21 23:09:24', '2025-10-27 13:17:48'),
(42, 226, 2, 1, '2025-10-27', 2028, 'CURSANDO', '2025-10-27', '2025-10-27 14:25:21', '2025-10-27 14:25:30'),
(43, 224, 2, 9, '2025-10-27', 2021, 'CURSANDO', '2025-10-27', '2025-10-27 14:31:50', '2025-10-27 14:31:55'),
(44, 214, 2, 9, '2025-10-27', 2021, 'CURSANDO', '2025-10-27', '2025-10-27 14:31:50', '2025-10-27 14:31:55'),
(45, 212, 2, 9, '2025-10-27', 2021, 'CURSANDO', '2025-10-27', '2025-10-27 14:31:50', '2025-10-27 14:31:55'),
(46, 213, 2, 9, '2025-10-27', 2021, 'CURSANDO', NULL, '2025-10-27 14:34:19', '2025-10-27 14:34:19'),
(47, 214, 2, 1, '2025-10-27', 2028, 'CURSANDO', '2025-10-27', '2025-10-27 14:49:21', '2025-10-27 14:49:25'),
(48, 224, 11, 2, '2025-11-02', 2025, 'CURSANDO', NULL, '2025-11-02 15:21:02', '2025-11-02 15:21:02'),
(49, 214, 11, 2, '2025-11-02', 2025, 'CURSANDO', NULL, '2025-11-02 15:21:02', '2025-11-02 15:21:02'),
(50, 213, 11, 2, '2025-11-02', 2025, 'CURSANDO', NULL, '2025-11-02 15:21:02', '2025-11-02 15:21:02'),
(51, 212, 11, 2, '2025-11-02', 2025, 'CURSANDO', NULL, '2025-11-02 15:21:02', '2025-11-02 15:21:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `institucional`
--

CREATE TABLE `institucional` (
  `institucional_id` int(11) NOT NULL,
  `escuelas_id` int(11) DEFAULT NULL,
  `formaciones_profesionales_id` int(11) DEFAULT NULL,
  `personas_id` int(11) NOT NULL,
  `institucional_tipo` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `institucional`
--

INSERT INTO `institucional` (`institucional_id`, `escuelas_id`, `formaciones_profesionales_id`, `personas_id`, `institucional_tipo`) VALUES
(3, 2, 9, 204, 'Docente'),
(7, NULL, NULL, 201, 'Alumno'),
(9, NULL, NULL, 206, 'Alumno'),
(10, 11, NULL, 202, 'Director'),
(11, 8, 6, 203, 'Docente'),
(12, NULL, NULL, 205, 'Alumno'),
(13, 2, 1, 200, 'Docente'),
(14, 2, 10, 4, 'Docente'),
(15, 9, NULL, 207, 'Director'),
(16, 9, 7, 208, 'Docente'),
(17, NULL, NULL, 209, 'Alumno'),
(18, 2, NULL, 210, 'Director'),
(19, 13, 16, 211, 'Docente'),
(20, NULL, NULL, 212, 'Alumno'),
(21, NULL, NULL, 213, 'Alumno'),
(22, NULL, NULL, 214, 'Alumno'),
(23, 1, 8, 216, 'Docente'),
(24, 11, 5, 217, 'Docente'),
(25, 13, 6, 218, 'Docente'),
(26, 11, 5, 220, 'Docente'),
(27, 16, 10, 221, 'Docente'),
(28, 9, NULL, 2, 'Director'),
(29, 4, NULL, 222, 'Director'),
(30, 11, 2, 3, 'Docente'),
(31, 9, 7, 223, 'Docente'),
(32, NULL, NULL, 224, 'Alumno'),
(33, NULL, NULL, 225, 'Alumno'),
(35, NULL, NULL, 226, 'Alumno'),
(36, 2, 10, 227, 'Docente'),
(37, NULL, NULL, 228, 'Alumno');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `permisos_id` int(5) NOT NULL,
  `permisos_descripcion` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`permisos_id`, `permisos_descripcion`) VALUES
(1, 'Agregar personas'),
(2, 'Modificar personas'),
(3, 'Ver personas'),
(4, 'Eliminar personas'),
(5, 'Agregar escuelas'),
(6, 'Modificar escuelas'),
(7, 'Ver escuelas'),
(8, 'Eliminar escuelas'),
(9, 'Agregar Formacion Pr'),
(10, 'Modificar Formacion '),
(11, 'Ver Formacion Profes'),
(12, 'Eliminar Formacion P'),
(13, 'Agregar datos al reg'),
(14, 'Modificar datos del '),
(15, 'Ver datos del regist'),
(16, 'Eliminar datos del r'),
(17, 'Agregar usuarios'),
(18, 'Modificar usuarios'),
(19, 'Ver usuarios'),
(20, 'Eliminar usuarios');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personas`
--

CREATE TABLE `personas` (
  `personas_id` int(5) NOT NULL,
  `personas_dni` int(8) NOT NULL,
  `personas_apellido` varchar(30) NOT NULL,
  `personas_nombre` varchar(30) NOT NULL,
  `personas_fechnac` date NOT NULL,
  `personas_sexo` varchar(10) NOT NULL,
  `personas_habilitado` tinyint(1) NOT NULL DEFAULT 1,
  `personas_fechcrea` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `personas_eliminado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `personas`
--

INSERT INTO `personas` (`personas_id`, `personas_dni`, `personas_apellido`, `personas_nombre`, `personas_fechnac`, `personas_sexo`, `personas_habilitado`, `personas_fechcrea`, `personas_eliminado`) VALUES
(1, 0, '', 'Administrador', '2023-10-30', '', 1, '2023-10-30 21:04:58', 0),
(2, 34129892, 'Valeri Sopaga', 'Jorge Norberto', '1989-03-04', 'Masculino', 1, '2023-11-10 18:55:27', 0),
(3, 34915485, 'Segui', 'Mayra', '1990-05-01', 'Femenino', 1, '2023-11-10 18:55:45', 0),
(4, 123456, 'Lopez', 'Fernando', '2010-01-01', 'Masculino', 1, '2025-07-29 23:18:19', 0),
(5, 0, 'SOPAGA', 'NORBERTO', '0000-00-00', '', 1, '2024-09-27 22:40:03', 0),
(6, 0, 'SOPAGA', 'NORBERTO', '1989-03-04', '', 1, '2024-09-27 22:41:50', 0),
(7, 0, 'var', 'jor', '0000-00-00', '', 1, '2024-10-29 14:06:19', 0),
(8, 0, 'VARSOP', 'JORGNOB', '1989-03-04', '', 1, '2024-10-29 14:52:15', 0),
(9, 0, 'VARSOP', 'JORGNOB', '1989-03-04', '', 1, '2024-10-29 14:55:16', 0),
(10, 0, 'VARSOP', 'JORGNOB', '1989-03-04', '', 1, '2024-10-29 18:58:45', 0),
(11, 0, 'VARSOP', 'JORGNOB', '1989-03-04', '', 1, '2024-10-29 19:39:58', 0),
(12, 0, 'VARSOP', 'JORGNOB', '1989-03-04', '', 1, '2024-10-29 19:41:41', 0),
(13, 123456789, 'SOP', 'JORG', '1989-03-04', 'MASCULINO', 1, '2025-07-26 22:42:29', 0),
(14, 0, '', '', '0000-00-00', '', 1, '2025-07-30 18:55:01', 1),
(177, 20252025, 'VALERI 2025', 'JORGE 2025', '2000-01-01', 'Masculino', 1, '2025-07-26 23:55:44', 0),
(198, 34343434, 'VALERI SOPAGA 2025', 'JORGE NORBERTO 2020', '1989-03-04', 'Masculino', 1, '2025-07-26 23:56:50', 0),
(199, 34915485, 'SEGUI 202520230', 'MAYRA', '1990-05-01', 'Femenino', 1, '2025-07-27 04:23:06', 0),
(200, 34129892, 'VALERI SEGUI 2025', 'JORGE MAYRA', '1989-03-04', 'Masculino', 1, '2025-07-27 04:36:54', 0),
(201, 12345678, 'VALERI 2025', 'SALVADOR 2025', '2019-08-29', 'Masculino', 1, '2025-07-31 00:16:22', 0),
(202, 34915485, 'segui', 'mayra', '1990-05-01', 'Femenino', 1, '2025-08-02 12:54:53', 0),
(203, 12345678, 'VAQUEL', 'MANUEL', '2000-01-01', 'Masculino', 1, '2025-07-30 22:57:38', 0),
(204, 12345678, 'QUIROGA', 'TONY', '2000-01-01', 'Masculino', 1, '2025-07-31 00:17:42', 0),
(205, 12345678, 'GONZALEZ', 'MALENA', '2000-01-01', 'Femenino', 1, '2025-07-31 00:19:18', 0),
(206, 12345678, 'VALERI 20301', 'CATALINA 2030', '2000-01-01', 'Femenino', 1, '2025-08-02 12:38:29', 0),
(207, 13548982, 'SOPAGA', 'DORA', '1959-11-18', 'Femenino', 1, '2025-08-14 14:35:19', 0),
(208, 12345678, 'PEDRAZA', 'DAVID', '1986-03-18', 'Masculino', 1, '2025-08-14 14:42:16', 0),
(209, 14789123, 'VALERI SEGUI 14825', 'JORGE MAYRA 14825', '1990-01-01', 'Masculino', 1, '2025-08-14 14:55:42', 0),
(210, 12345678, 'DIRECTOR', 'DIRECTOR', '1900-01-01', 'Masculino', 1, '2025-08-14 15:55:27', 0),
(211, 12345678, 'DOCENTE', 'DOCENTE', '1900-01-01', 'Masculino', 1, '2025-08-14 20:16:56', 0),
(212, 15825, 'VALERI', 'SALVADOR', '1900-01-01', 'Masculino', 1, '2025-08-15 16:03:27', 0),
(213, 15825, 'VALERI', 'CATALINA', '1900-01-01', 'Femenino', 1, '2025-08-15 16:14:31', 0),
(214, 150825, 'SEGUI', 'MAYRA', '1900-01-01', 'Femenino', 1, '2025-08-15 16:16:16', 0),
(215, 34129892, 'valeri9090', 'jorge9090', '0000-00-00', 'masculino', 1, '2025-09-19 16:36:25', 0),
(216, 34129892, 'VALERI 666', 'JORGE 666', '1989-03-04', 'Masculino', 1, '2025-09-22 15:43:25', 0),
(217, 34129892, 'VALERI 999', 'JORGE 999', '1989-03-04', 'Masculino', 1, '2025-09-22 23:24:15', 0),
(218, 34129892, 'VALERI SOPAGA 666', 'JORGE NORBERTO 666', '1989-03-04', 'Masculino', 1, '2025-09-23 22:47:49', 0),
(220, 34129892, 'Valeri Sopaga', 'Jorge Norberto', '1989-03-04', 'Masculino', 1, '2025-09-29 12:38:05', 0),
(221, 15987456, 'asd', 'asd', '1989-03-04', 'Masculino', 1, '2025-09-29 13:39:54', 0),
(222, 12369987, 'ASDFA', 'ASDFA', '1989-03-04', 'Masculino', 1, '2025-09-29 14:04:34', 0),
(223, 459879634, 'Valeri Sopaga 202510', 'Jorge Norberto 202510', '1989-03-04', 'Masculino', 1, '2025-10-30 16:03:59', 0),
(224, 99888777, 'asdf', 'asdfasd', '1989-03-04', 'Masculino', 1, '2025-10-16 23:33:17', 0),
(225, 77888999, 'VALERI', 'JORGE', '1989-03-04', 'Masculino', 1, '2025-10-17 13:53:13', 0),
(226, 88777999, 'VALERI SOPAGA', 'JORGE', '1989-03-04', 'Masculino', 1, '2025-10-17 14:02:21', 0),
(227, 33444555, 'CASAN', 'MORIA', '2024-02-07', 'Femenino', 1, '2025-11-02 15:14:36', 0),
(228, 55666999, 'Lopez', 'daniel', '2025-10-30', 'Masculino', 1, '2025-11-02 15:19:45', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recuperar_contrasenia`
--

CREATE TABLE `recuperar_contrasenia` (
  `usuarios_email` varchar(255) NOT NULL,
  `recuperar_contrasenia_fechcrea` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `recuperar_contrasenia`
--

INSERT INTO `recuperar_contrasenia` (`usuarios_email`, `recuperar_contrasenia_fechcrea`) VALUES
('admin@admin.com', '2025-09-19 21:30:51'),
('admin@admin.com', '2025-09-19 21:39:06'),
('admin@admin.com', '2025-09-20 00:52:33'),
('admin@admin.com', '2025-09-19 19:53:09'),
('admin@admin.com', '2025-09-19 19:53:24'),
('admin@admin.com', '2025-09-19 19:53:28'),
('admin@admin.com', '2025-09-19 19:55:52'),
('admin@admin.com', '2025-09-19 20:01:16'),
('admin@admin.com', '2025-09-19 20:10:49'),
('admin@admin.com', '2025-09-19 20:11:07'),
('doce@doce.com', '2025-09-19 20:11:35'),
('doce@doce.com', '2025-09-22 12:15:50'),
('doce@doce.com', '2025-09-22 13:22:36'),
('doce@doce.com', '2025-09-22 13:23:10'),
('doce@doce.com', '2025-09-22 13:23:12'),
('doce@doce.com', '2025-09-22 13:31:33'),
('admin@admin.com', '2025-09-22 13:31:51'),
('admin@admin.com', '2025-09-22 22:56:24'),
('admin@admin.com', '2025-09-30 15:54:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registros`
--

CREATE TABLE `registros` (
  `registros_id` int(5) NOT NULL,
  `institucional_id` int(5) NOT NULL,
  `registros_anio` int(5) NOT NULL,
  `registros_mes` int(5) NOT NULL,
  `registros_dias_habi` int(5) NOT NULL,
  `registros_asi_va` int(5) NOT NULL,
  `registros_asi_mu` int(5) NOT NULL,
  `registros_asi_to` int(5) NOT NULL,
  `registros_ina_va` int(5) NOT NULL,
  `registros_ina_mu` int(5) NOT NULL,
  `registros_ina_to` int(5) NOT NULL,
  `registros_asi_me_va` int(5) NOT NULL,
  `registros_asi_me_mu` int(5) NOT NULL,
  `registros_asi_me_to` int(5) NOT NULL,
  `registros_por_asi_va` int(5) NOT NULL,
  `registros_por_asi_mu` int(5) NOT NULL,
  `registros_por_asi_to` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `registros`
--

INSERT INTO `registros` (`registros_id`, `institucional_id`, `registros_anio`, `registros_mes`, `registros_dias_habi`, `registros_asi_va`, `registros_asi_mu`, `registros_asi_to`, `registros_ina_va`, `registros_ina_mu`, `registros_ina_to`, `registros_asi_me_va`, `registros_asi_me_mu`, `registros_asi_me_to`, `registros_por_asi_va`, `registros_por_asi_mu`, `registros_por_asi_to`) VALUES
(1, 13, 2028, 3, 5, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 13, 2028, 4, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3, 13, 2028, 5, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(4, 13, 2028, 6, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(5, 13, 2028, 7, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(6, 13, 2028, 8, 0, 5, 13, 18, 4, 5, 9, 0, 0, 0, 0, 0, 0),
(7, 13, 2028, 1, 9, 4, 14, 18, 5, 4, 9, 0, 2, 2, 44, 78, 67),
(8, 13, 2028, 9, 18, 2, 34, 36, 16, 2, 18, 0, 2, 2, 11, 94, 67),
(9, 19, 2029, 8, 10, 20, 23, 43, 10, 17, 27, 2, 2, 4, 67, 58, 61),
(10, 19, 2029, 9, 10, 18, 25, 43, 12, 15, 27, 2, 3, 4, 60, 63, 61),
(11, 13, 2028, 10, 4, 6, 1, 7, 2, 7, 9, 2, 0, 2, 75, 13, 44),
(12, 13, 2028, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(13, 13, 2028, 0, 4, 4, 3, 7, 4, 5, 9, 1, 1, 2, 50, 38, 44),
(14, 13, 2028, 11, 4, 4, 3, 7, 4, 5, 9, 1, 1, 2, 50, 38, 44),
(15, 13, 2028, 12, 4, 4, 3, 7, 4, 5, 9, 1, 1, 2, 50, 38, 44),
(16, 13, 2028, 2, 4, 5, 3, 8, 7, 5, 12, 1, 1, 2, 42, 38, 40),
(17, 3, 2021, 1, 4, 5, 2, 7, 3, 2, 5, 1, 1, 2, 62, 50, 58),
(18, 13, 2027, 10, 6, 2, 3, 5, 4, 3, 7, 0, 1, 1, 33, 50, 42),
(19, 30, 2025, 3, 4, 4, 3, 7, 4, 5, 9, 1, 1, 2, 50, 38, 44);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `telefonos`
--

CREATE TABLE `telefonos` (
  `telefonos_id` int(5) NOT NULL,
  `telefonos_numero` varchar(20) NOT NULL,
  `personas_id` int(5) NOT NULL,
  `telefonos_descripcion` varchar(100) NOT NULL,
  `telefonos_predeterminado` tinyint(1) NOT NULL DEFAULT 1,
  `telefonos_habilitado` tinyint(1) NOT NULL DEFAULT 1,
  `telefonos_fechcrea` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `telefonos_eliminado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `telefonos`
--

INSERT INTO `telefonos` (`telefonos_id`, `telefonos_numero`, `personas_id`, `telefonos_descripcion`, `telefonos_predeterminado`, `telefonos_habilitado`, `telefonos_fechcrea`, `telefonos_eliminado`) VALUES
(1, '0000-000000', 1, '-', 1, 1, '2023-11-04 01:06:49', 0),
(2, '111111', 7, '', 1, 1, '2024-10-29 14:24:44', 0),
(3, '454545', 10, '', 1, 1, '2024-10-29 18:58:46', 0),
(4, '454545', 11, '', 1, 1, '2024-10-29 19:39:59', 0),
(5, '454545', 12, '', 1, 1, '2024-10-29 19:41:41', 0),
(19, '123456', 204, '', 1, 1, '2025-07-31 00:17:43', 0),
(24, '3834800300', 201, '27-07-2025 SALVADOR', 1, 1, '2025-08-02 12:35:42', 0),
(27, '3838383838', 206, 'CASA 2030', 1, 1, '2025-08-02 12:38:30', 0),
(28, '4040404040', 206, 'TRABAJO 2030', 0, 1, '2025-08-02 12:38:30', 0),
(29, '3834800300', 202, '', 0, 1, '2025-08-02 12:54:55', 0),
(30, '12345678', 202, '', 1, 1, '2025-08-02 12:54:55', 0),
(31, '333333', 203, 'CASA', 1, 1, '2025-08-02 13:23:48', 0),
(32, '123456789', 205, '', 1, 1, '2025-08-02 13:30:44', 0),
(33, '3834800300', 200, '27-07-2025', 1, 1, '2025-08-02 14:49:17', 0),
(34, '3834505050', 4, '', 1, 1, '2025-08-12 14:31:11', 0),
(35, '3834797979', 207, 'CASA', 0, 1, '2025-08-14 14:35:19', 0),
(36, '3834565656', 207, 'TRABAJO', 1, 1, '2025-08-14 14:35:19', 0),
(37, '3434343434', 208, 'CASA', 1, 1, '2025-08-14 14:42:17', 0),
(38, '5649789', 209, 'CASA', 1, 1, '2025-08-14 14:55:42', 0),
(39, '123456', 210, '', 1, 1, '2025-08-14 15:55:28', 0),
(40, '123456789', 211, 'CASA', 1, 1, '2025-08-14 20:16:56', 0),
(41, '3834800300', 212, 'CASA', 1, 1, '2025-08-15 16:03:27', 0),
(42, '3834800300', 213, 'CASA', 1, 1, '2025-08-15 16:14:33', 0),
(43, '3834800300', 214, 'CASA', 1, 1, '2025-08-15 16:16:16', 0),
(44, '3834800300', 216, '666', 1, 1, '2025-09-22 15:43:26', 0),
(45, '3834565958', 217, '999', 1, 1, '2025-09-22 23:24:16', 0),
(46, '38475959', 217, '999', 0, 1, '2025-09-22 23:24:17', 0),
(47, '1598743', 218, '666 999', 1, 1, '2025-09-23 22:47:49', 0),
(48, '46465132134', 218, '666 999', 0, 1, '2025-09-23 22:47:49', 0),
(49, '1321321321', 220, 'CASA', 1, 1, '2025-09-29 12:38:06', 0),
(50, '65463155623', 220, 'CASA 2', 0, 1, '2025-09-29 12:38:06', 0),
(51, '3834800300', 221, 'CASA', 1, 1, '2025-09-29 13:39:54', 0),
(52, '3131321', 2, 'CASA', 1, 1, '2025-09-29 13:41:35', 0),
(53, '321643132132', 222, 'CASA 99899', 1, 1, '2025-09-29 14:04:35', 0),
(54, '3592562', 3, 'CELULAR', 1, 1, '2025-09-29 14:15:25', 0),
(55, '3834505050', 3, 'OFICINA', 0, 1, '2025-09-29 14:15:25', 0),
(57, '3834800300', 224, 'asdfas', 1, 1, '2025-10-16 23:33:17', 0),
(58, '3834800300', 225, '', 1, 1, '2025-10-17 13:52:12', 0),
(59, '333333', 225, '', 0, 1, '2025-10-17 13:52:12', 0),
(62, '3834800300', 226, '', 0, 1, '2025-10-17 14:01:40', 0),
(63, '3838383838', 226, '', 1, 1, '2025-10-17 14:01:40', 0),
(65, '3132131', 223, 'sadfasd', 1, 1, '2025-10-30 16:03:59', 0),
(66, '333333', 227, 'MOVIL', 1, 1, '2025-11-02 15:14:37', 0),
(67, '33333333333333', 228, '', 1, 1, '2025-11-02 15:19:45', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `usuarios_id` int(5) NOT NULL,
  `personas_id` int(5) NOT NULL,
  `usuarios_email` varchar(30) NOT NULL,
  `usuarios_clave` varchar(100) NOT NULL,
  `usuarios_habilitado` tinyint(1) NOT NULL DEFAULT 1,
  `usuarios_fechcrea` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `usuarios_eliminado` tinyint(1) NOT NULL DEFAULT 0,
  `usuarios_rol` enum('ADMINISTRADOR','DIRECTOR','DOCENTE') NOT NULL DEFAULT 'DOCENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`usuarios_id`, `personas_id`, `usuarios_email`, `usuarios_clave`, `usuarios_habilitado`, `usuarios_fechcrea`, `usuarios_eliminado`, `usuarios_rol`) VALUES
(1, 1, 'admin@admin.com', '$2y$10$rqlA.CbugzH3CsrRW07fnuTfeNzM7GfiEaMHapjDUPhnCW7WEGSgS', 1, '2025-08-12 14:12:07', 0, 'ADMINISTRADOR'),
(2, 7, 'yo@yo.com', '123', 1, '2024-10-29 14:24:04', 0, 'DOCENTE'),
(3, 9, 'yo3@yo.com', '123456', 1, '2024-10-29 14:55:16', 0, 'DOCENTE'),
(4, 10, 'yo4@yo.com', '123456', 1, '2024-10-29 18:58:46', 0, 'DOCENTE'),
(5, 11, 'yo5@yo.com', '123456', 1, '2024-10-29 19:39:59', 0, 'DOCENTE'),
(6, 12, 'yo6@yo.com', '123456', 1, '2025-07-29 16:55:26', 0, 'DOCENTE'),
(7, 201, 'salvador@yo.com', '$2y$10$8CKKWQ//hDjlh3ZTC.XJGufxzjljkLLF3qHpF5ZpkWxmvVaHbhfzW', 1, '2025-07-29 16:58:53', 0, 'DOCENTE'),
(8, 4, 'lopez@lopez.com.ar', '$2y$10$GrOfI5NjdbaP58Y/CkPh1uSlkrhPYMBQZ6caoAAXbxAFDSrA1Bjom', 1, '2025-07-29 23:17:22', 0, 'DOCENTE'),
(9, 208, 'david@david.com', '$2y$10$Qhw0w8s1H61twBAGsImQa.t7WVtujaIX7oEMsFSC9ij0/naIihLZC', 1, '2025-08-14 14:42:47', 0, 'DOCENTE'),
(10, 209, 'val@val.com', '$2y$10$m8KOiwQyncedY/TmHXW92uCE3qm11LiZch5ta5Z5h6u4kjWDgjJUK', 1, '2025-08-14 14:55:42', 0, 'ADMINISTRADOR'),
(11, 210, 'dire@dire.com', '$2y$10$eY3mA87hqTWZmQy8ch7ZfeTPsAG57eBa/5NvKjuz57M6/Jkozsyy2', 1, '2025-08-14 15:55:28', 0, 'DIRECTOR'),
(12, 211, 'doce@doce.com', '$2y$10$CF9C.HhdpeMVa0/2ZP8gdeF7xJWp9JsROWikPq7mEYuk5abDaj0tO', 1, '2025-08-14 20:16:57', 0, 'DOCENTE'),
(13, 216, 'doce666@doce666.com', '$2y$10$6E0iFlYBLde0mpiquWuOBuUAJ5ZZcHLnvBXUoGogDw7lt9JsSAFTa', 1, '2025-09-22 15:43:26', 0, 'DOCENTE'),
(14, 217, 'doce3@doce.com', '$2y$10$ywoS65Xt2l3GxWgbTRfa4.R.cs5knwrivuNbai.73VEjwWzAAH.5.', 1, '2025-09-22 23:24:17', 0, 'DOCENTE'),
(15, 218, 'doce4@doce.com', '$2y$10$v9R/zW7itUSV59ZE8O51BumGCtd6Mw8UVgkR9UV6WU5lYXdHYES22', 1, '2025-09-23 22:47:49', 0, 'DOCENTE'),
(16, 220, 'doce5@doce.com', '$2y$10$u3GYrX8yOV3B2pkDvmuxi.1x./Fhb85VCY46QY/XnFlYVrC9dFsre', 1, '2025-09-29 12:38:06', 0, 'DOCENTE'),
(17, 221, 'doce6@docente.com', '$2y$10$.lrE9o7sj4f0i5gv5yrpVehOxzIK6gJcvhRRYlx1VlKl5iC4/QuMq', 1, '2025-09-29 13:39:54', 0, 'DOCENTE'),
(18, 2, 'dire3@dire.com', '$2y$10$SEI/ahBI0uRhdJryc6l00uAbNim5NboQY4WMGkx0KGDjIglIf8CVy', 1, '2025-09-29 13:41:35', 0, 'DIRECTOR'),
(19, 222, 'dire4@dire.com', '$2y$10$zsPQTX0tEJ3ema.7mcQCTeQPw.TNnVlI4K7aPORZ1eSCtIp3Ci1AC', 1, '2025-09-29 14:04:35', 0, 'DIRECTOR'),
(20, 3, 'maysegui90@gmail.com', '$2y$10$/5b9KhKm4v.f7009uo6X5.7yclX9AHMunw7EEvXoZLSFsBsE.wXqy', 1, '2025-09-29 14:15:25', 0, 'DOCENTE'),
(21, 223, 'doce7@doce.com', '$2y$10$2YtDfaiY7QG3CYNGYEIPg.ehOtNYmQtDXdWBeCbuhOg28ATsvZHKO', 1, '2025-10-20 14:41:59', 0, 'DOCENTE'),
(22, 226, 'jorge@admin.com', '$2y$10$6.MVprm4yXvs9j/ui23NDOvX63uGek0TfCMndIN3Wg.385yDVY8/2', 1, '2025-10-20 16:04:57', 0, 'ADMINISTRADOR'),
(23, 227, 'maysegui90@gmail.com', '$2y$10$UOfFhqhYcUrFncSM.i9f8eYkHvwi3VzRbU4W07ezalBMYLMXihVUa', 1, '2025-11-07 22:34:29', 1, 'DOCENTE');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `auditorias`
--
ALTER TABLE `auditorias`
  ADD PRIMARY KEY (`auditoria_id`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `table_name` (`table_name`),
  ADD KEY `action` (`action`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`categorias_id`);

--
-- Indices de la tabla `domicilios`
--
ALTER TABLE `domicilios`
  ADD PRIMARY KEY (`domicilios_id`);

--
-- Indices de la tabla `escuelas`
--
ALTER TABLE `escuelas`
  ADD PRIMARY KEY (`escuelas_id`);

--
-- Indices de la tabla `formaciones_profesionales`
--
ALTER TABLE `formaciones_profesionales`
  ADD PRIMARY KEY (`formaciones_profesionales_id`);

--
-- Indices de la tabla `inscripciones_alumnos`
--
ALTER TABLE `inscripciones_alumnos`
  ADD PRIMARY KEY (`inscripcion_id`),
  ADD KEY `idx_persona` (`personas_id`),
  ADD KEY `idx_escuela` (`escuelas_id`),
  ADD KEY `idx_formacion` (`formaciones_profesionales_id`);

--
-- Indices de la tabla `institucional`
--
ALTER TABLE `institucional`
  ADD PRIMARY KEY (`institucional_id`),
  ADD KEY `escuelas_id` (`escuelas_id`),
  ADD KEY `formaciones_profesionales_id` (`formaciones_profesionales_id`),
  ADD KEY `personas_id` (`personas_id`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`permisos_id`);

--
-- Indices de la tabla `personas`
--
ALTER TABLE `personas`
  ADD PRIMARY KEY (`personas_id`);

--
-- Indices de la tabla `registros`
--
ALTER TABLE `registros`
  ADD PRIMARY KEY (`registros_id`);

--
-- Indices de la tabla `telefonos`
--
ALTER TABLE `telefonos`
  ADD PRIMARY KEY (`telefonos_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`usuarios_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `auditorias`
--
ALTER TABLE `auditorias`
  MODIFY `auditoria_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `categorias_id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `domicilios`
--
ALTER TABLE `domicilios`
  MODIFY `domicilios_id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT de la tabla `escuelas`
--
ALTER TABLE `escuelas`
  MODIFY `escuelas_id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `formaciones_profesionales`
--
ALTER TABLE `formaciones_profesionales`
  MODIFY `formaciones_profesionales_id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `inscripciones_alumnos`
--
ALTER TABLE `inscripciones_alumnos`
  MODIFY `inscripcion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT de la tabla `institucional`
--
ALTER TABLE `institucional`
  MODIFY `institucional_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `permisos_id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `personas`
--
ALTER TABLE `personas`
  MODIFY `personas_id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=229;

--
-- AUTO_INCREMENT de la tabla `registros`
--
ALTER TABLE `registros`
  MODIFY `registros_id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `telefonos`
--
ALTER TABLE `telefonos`
  MODIFY `telefonos_id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `usuarios_id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `inscripciones_alumnos`
--
ALTER TABLE `inscripciones_alumnos`
  ADD CONSTRAINT `fk_insc_escuela` FOREIGN KEY (`escuelas_id`) REFERENCES `escuelas` (`escuelas_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_insc_formacion` FOREIGN KEY (`formaciones_profesionales_id`) REFERENCES `formaciones_profesionales` (`formaciones_profesionales_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_insc_persona` FOREIGN KEY (`personas_id`) REFERENCES `personas` (`personas_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `institucional`
--
ALTER TABLE `institucional`
  ADD CONSTRAINT `institucional_ibfk_1` FOREIGN KEY (`escuelas_id`) REFERENCES `escuelas` (`escuelas_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `institucional_ibfk_2` FOREIGN KEY (`formaciones_profesionales_id`) REFERENCES `formaciones_profesionales` (`formaciones_profesionales_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `institucional_ibfk_3` FOREIGN KEY (`personas_id`) REFERENCES `personas` (`personas_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
