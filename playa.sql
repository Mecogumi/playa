-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 18-11-2025 a las 23:16:25
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `playa`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_habitacion`
--

CREATE TABLE `categorias_habitacion` (
  `id_categoria` int(11) NOT NULL,
  `nombre_categoria` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias_habitacion`
--

INSERT INTO `categorias_habitacion` (`id_categoria`, `nombre_categoria`, `descripcion`) VALUES
(1, 'Estándar', 'Habitaciones cómodas y funcionales con todas las comodidades básicas'),
(2, 'Deluxe', 'Habitaciones amplias con amenidades premium y vistas espectaculares'),
(3, 'Suite', 'Suites de lujo con sala de estar, comedor y servicios exclusivos'),
(4, 'Presidencial', 'La experiencia más lujosa con servicios personalizados y espacios amplios');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_reservacion`
--

CREATE TABLE `detalles_reservacion` (
  `id_detalle` int(11) NOT NULL,
  `id_reservacion` int(11) NOT NULL,
  `id_habitacion` int(11) NOT NULL,
  `cantidad_habitaciones` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habitaciones`
--

CREATE TABLE `habitaciones` (
  `id_habitacion` int(11) NOT NULL,
  `numero_habitacion` varchar(20) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio_noche` decimal(10,2) NOT NULL,
  `capacidad_personas` int(11) NOT NULL,
  `cantidad_disponible` int(11) NOT NULL DEFAULT 0,
  `caracteristicas` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `habitaciones`
--

INSERT INTO `habitaciones` (`id_habitacion`, `numero_habitacion`, `id_categoria`, `nombre`, `descripcion`, `precio_noche`, `capacidad_personas`, `cantidad_disponible`, `caracteristicas`, `activo`, `fecha_registro`) VALUES
(1, '101', 1, 'Habitación Estándar Individual', 'Habitación acogedora con cama individual, ideal para viajeros solitarios', 850.00, 1, 5, 'TV de pantalla plana, WiFi gratuito, Aire acondicionado, Baño privado', 1, '2025-11-10 19:07:37'),
(2, '102', 1, 'Habitación Estándar Doble', 'Habitación con dos camas matrimoniales, perfecta para familias pequeñas', 1200.00, 4, 8, 'TV de pantalla plana, WiFi gratuito, Aire acondicionado, Minibar, Baño privado', 1, '2025-11-10 19:07:37'),
(3, '201', 2, 'Habitación Deluxe con Vista al Mar', 'Habitación elegante con balcón y vista panorámica al océano', 2500.00, 2, 4, 'Cama King Size, TV Smart, WiFi gratuito, Aire acondicionado, Minibar, Balcón privado, Cafetera, Baño con jacuzzi', 1, '2025-11-10 19:07:37'),
(4, '202', 2, 'Habitación Deluxe Familiar', 'Espaciosa habitación con zona de estar, ideal para familias', 3000.00, 5, 3, 'Cama King Size + Sofá cama, TV Smart, WiFi gratuito, Aire acondicionado, Minibar, Sala de estar, Baño amplio', 1, '2025-11-10 19:07:37'),
(5, '301', 3, 'Suite Romántica', 'Suite elegante con jacuzzi privado y decoración romántica', 4500.00, 2, 2, 'Cama King Size, TV Smart, WiFi gratuito, Aire acondicionado, Minibar premium, Sala de estar, Jacuzzi privado, Balcón con vista al mar, Servicio de habitación 24h', 1, '2025-11-10 19:07:37'),
(6, '302', 3, 'Suite Familiar Premium', 'Amplia suite de dos habitaciones con todas las comodidades', 5500.00, 6, 2, '2 habitaciones, Cama King Size + 2 Matrimoniales, 2 TV Smart, WiFi gratuito, Aire acondicionado, Minibar premium, Sala de estar, Comedor, 2 baños completos', 1, '2025-11-10 19:07:37'),
(7, '401', 4, 'Suite Presidencial', 'La experiencia definitiva en lujo y confort con servicios exclusivos', 8500.00, 4, 1, 'Habitación principal King Size + Habitación secundaria, 3 TV Smart, WiFi premium, Aire acondicionado, Bar completo, Sala de estar amplia, Comedor privado, Terraza privada, 2 baños de lujo con jacuzzi, Mayordomo personal, Servicio de habitación 24h', 1, '2025-11-10 19:07:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `imagenes_habitacion`
--

CREATE TABLE `imagenes_habitacion` (
  `id_imagen` int(11) NOT NULL,
  `id_habitacion` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(500) NOT NULL,
  `es_principal` tinyint(1) DEFAULT 0,
  `orden_visualizacion` int(11) DEFAULT 0,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `imagenes_habitacion`
--

INSERT INTO `imagenes_habitacion` (`id_imagen`, `id_habitacion`, `nombre_archivo`, `ruta_archivo`, `es_principal`, `orden_visualizacion`, `fecha_subida`) VALUES
(1, 1, 'hab_1_691cbe2ec50de.webp', 'uploads/habitaciones/hab_1_691cbe2ec50de.webp', 1, 0, '2025-11-18 18:42:54'),
(2, 1, 'hab_1_691cbe43932ab.webp', 'uploads/habitaciones/hab_1_691cbe43932ab.webp', 0, 0, '2025-11-18 18:43:15'),
(3, 4, 'hab_4_691cbe8e8f3de.webp', 'uploads/habitaciones/hab_4_691cbe8e8f3de.webp', 1, 0, '2025-11-18 18:44:30'),
(4, 4, 'hab_4_691cbea0b8008.webp', 'uploads/habitaciones/hab_4_691cbea0b8008.webp', 0, 0, '2025-11-18 18:44:48'),
(5, 4, 'hab_4_691cbea0b89eb.webp', 'uploads/habitaciones/hab_4_691cbea0b89eb.webp', 0, 1, '2025-11-18 18:44:48'),
(6, 5, 'hab_5_691cbfe504f5a.jpg', 'uploads/habitaciones/hab_5_691cbfe504f5a.jpg', 1, 0, '2025-11-18 18:50:13'),
(7, 5, 'hab_5_691cbfe505a31.jpg', 'uploads/habitaciones/hab_5_691cbfe505a31.jpg', 0, 1, '2025-11-18 18:50:13'),
(8, 2, 'hab_2_691cc18d76e07.jpg', 'uploads/habitaciones/hab_2_691cc18d76e07.jpg', 1, 0, '2025-11-18 18:57:17'),
(9, 2, 'hab_2_691cc18d774f9.jpg', 'uploads/habitaciones/hab_2_691cc18d774f9.jpg', 0, 1, '2025-11-18 18:57:17'),
(10, 7, 'hab_7_691cc1d878f1d.jpg', 'uploads/habitaciones/hab_7_691cc1d878f1d.jpg', 1, 0, '2025-11-18 18:58:32'),
(11, 7, 'hab_7_691cc1d879686.jpg', 'uploads/habitaciones/hab_7_691cc1d879686.jpg', 0, 1, '2025-11-18 18:58:32'),
(12, 7, 'hab_7_691cc1d879dcd.jpg', 'uploads/habitaciones/hab_7_691cc1d879dcd.jpg', 0, 2, '2025-11-18 18:58:32'),
(13, 3, 'hab_3_691cc2784f040.jpg', 'uploads/habitaciones/hab_3_691cc2784f040.jpg', 1, 0, '2025-11-18 19:01:12'),
(14, 3, 'hab_3_691cc2784fa6b.jpg', 'uploads/habitaciones/hab_3_691cc2784fa6b.jpg', 0, 1, '2025-11-18 19:01:12'),
(15, 6, 'hab_6_691cc2b60cffa.jpg', 'uploads/habitaciones/hab_6_691cc2b60cffa.jpg', 1, 0, '2025-11-18 19:02:14'),
(16, 6, 'hab_6_691cc2b60d691.jpg', 'uploads/habitaciones/hab_6_691cc2b60d691.jpg', 0, 1, '2025-11-18 19:02:14'),
(17, 6, 'hab_6_691cc2b60dcc0.jpg', 'uploads/habitaciones/hab_6_691cc2b60dcc0.jpg', 0, 2, '2025-11-18 19:02:14'),
(18, 6, 'hab_6_691cc2b60e334.jpg', 'uploads/habitaciones/hab_6_691cc2b60e334.jpg', 0, 3, '2025-11-18 19:02:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservaciones`
--

CREATE TABLE `reservaciones` (
  `id_reservacion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha_entrada` date NOT NULL,
  `fecha_salida` date NOT NULL,
  `numero_noches` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `impuestos` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `estado_reservacion` enum('pendiente','confirmada','cancelada','completada') DEFAULT 'pendiente',
  `fecha_reservacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `tipo_usuario` enum('admin','huesped','no_registrado') NOT NULL DEFAULT 'no_registrado',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre_usuario`, `contrasena`, `nombre_completo`, `email`, `telefono`, `tipo_usuario`, `fecha_registro`, `activo`) VALUES
(1, 'admin', '$2a$12$b4b6zHTfzTdrC224cwTdhO73sGpv2Z9n0278E35IJDGJYAq455luC', 'Administrador del Sistema', 'admin@hotel.com', '5551234567', 'admin', '2025-11-10 19:07:36', 1),
(2, 'huesped', '$2a$12$lnmQRh8ltM2ikQ1rJtQ69uxShpgpSMgNcGK6v1p.58qHTyz2wtNq.', 'Juan Pérez García', 'juan.perez@email.com', '5559876543', 'huesped', '2025-11-10 19:07:37', 1);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_habitaciones_completa`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_habitaciones_completa` (
`id_habitacion` int(11)
,`numero_habitacion` varchar(20)
,`nombre` varchar(100)
,`descripcion` text
,`precio_noche` decimal(10,2)
,`capacidad_personas` int(11)
,`cantidad_disponible` int(11)
,`caracteristicas` text
,`activo` tinyint(1)
,`id_categoria` int(11)
,`nombre_categoria` varchar(50)
,`categoria_descripcion` text
,`total_imagenes` bigint(21)
,`imagen_principal` varchar(500)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_reservaciones_completa`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_reservaciones_completa` (
`id_reservacion` int(11)
,`fecha_entrada` date
,`fecha_salida` date
,`numero_noches` int(11)
,`subtotal` decimal(10,2)
,`impuestos` decimal(10,2)
,`total` decimal(10,2)
,`estado_reservacion` enum('pendiente','confirmada','cancelada','completada')
,`fecha_reservacion` timestamp
,`notas` text
,`id_usuario` int(11)
,`nombre_usuario` varchar(50)
,`nombre_completo` varchar(100)
,`email` varchar(100)
,`telefono` varchar(20)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_habitaciones_completa`
--
DROP TABLE IF EXISTS `vista_habitaciones_completa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_habitaciones_completa`  AS SELECT `h`.`id_habitacion` AS `id_habitacion`, `h`.`numero_habitacion` AS `numero_habitacion`, `h`.`nombre` AS `nombre`, `h`.`descripcion` AS `descripcion`, `h`.`precio_noche` AS `precio_noche`, `h`.`capacidad_personas` AS `capacidad_personas`, `h`.`cantidad_disponible` AS `cantidad_disponible`, `h`.`caracteristicas` AS `caracteristicas`, `h`.`activo` AS `activo`, `c`.`id_categoria` AS `id_categoria`, `c`.`nombre_categoria` AS `nombre_categoria`, `c`.`descripcion` AS `categoria_descripcion`, (select count(0) from `imagenes_habitacion` where `imagenes_habitacion`.`id_habitacion` = `h`.`id_habitacion`) AS `total_imagenes`, (select `imagenes_habitacion`.`ruta_archivo` from `imagenes_habitacion` where `imagenes_habitacion`.`id_habitacion` = `h`.`id_habitacion` and `imagenes_habitacion`.`es_principal` = 1 limit 1) AS `imagen_principal` FROM (`habitaciones` `h` join `categorias_habitacion` `c` on(`h`.`id_categoria` = `c`.`id_categoria`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_reservaciones_completa`
--
DROP TABLE IF EXISTS `vista_reservaciones_completa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_reservaciones_completa`  AS SELECT `r`.`id_reservacion` AS `id_reservacion`, `r`.`fecha_entrada` AS `fecha_entrada`, `r`.`fecha_salida` AS `fecha_salida`, `r`.`numero_noches` AS `numero_noches`, `r`.`subtotal` AS `subtotal`, `r`.`impuestos` AS `impuestos`, `r`.`total` AS `total`, `r`.`estado_reservacion` AS `estado_reservacion`, `r`.`fecha_reservacion` AS `fecha_reservacion`, `r`.`notas` AS `notas`, `u`.`id_usuario` AS `id_usuario`, `u`.`nombre_usuario` AS `nombre_usuario`, `u`.`nombre_completo` AS `nombre_completo`, `u`.`email` AS `email`, `u`.`telefono` AS `telefono` FROM (`reservaciones` `r` join `usuarios` `u` on(`r`.`id_usuario` = `u`.`id_usuario`)) ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias_habitacion`
--
ALTER TABLE `categorias_habitacion`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `nombre_categoria` (`nombre_categoria`),
  ADD KEY `idx_nombre_categoria` (`nombre_categoria`);

--
-- Indices de la tabla `detalles_reservacion`
--
ALTER TABLE `detalles_reservacion`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `idx_reservacion` (`id_reservacion`),
  ADD KEY `idx_habitacion` (`id_habitacion`);

--
-- Indices de la tabla `habitaciones`
--
ALTER TABLE `habitaciones`
  ADD PRIMARY KEY (`id_habitacion`),
  ADD UNIQUE KEY `numero_habitacion` (`numero_habitacion`),
  ADD KEY `idx_categoria` (`id_categoria`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `imagenes_habitacion`
--
ALTER TABLE `imagenes_habitacion`
  ADD PRIMARY KEY (`id_imagen`),
  ADD KEY `idx_habitacion` (`id_habitacion`);

--
-- Indices de la tabla `reservaciones`
--
ALTER TABLE `reservaciones`
  ADD PRIMARY KEY (`id_reservacion`),
  ADD KEY `idx_usuario` (`id_usuario`),
  ADD KEY `idx_fechas` (`fecha_entrada`,`fecha_salida`),
  ADD KEY `idx_estado` (`estado_reservacion`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_nombre_usuario` (`nombre_usuario`),
  ADD KEY `idx_tipo_usuario` (`tipo_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias_habitacion`
--
ALTER TABLE `categorias_habitacion`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `detalles_reservacion`
--
ALTER TABLE `detalles_reservacion`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `habitaciones`
--
ALTER TABLE `habitaciones`
  MODIFY `id_habitacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `imagenes_habitacion`
--
ALTER TABLE `imagenes_habitacion`
  MODIFY `id_imagen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `reservaciones`
--
ALTER TABLE `reservaciones`
  MODIFY `id_reservacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalles_reservacion`
--
ALTER TABLE `detalles_reservacion`
  ADD CONSTRAINT `detalles_reservacion_ibfk_1` FOREIGN KEY (`id_reservacion`) REFERENCES `reservaciones` (`id_reservacion`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalles_reservacion_ibfk_2` FOREIGN KEY (`id_habitacion`) REFERENCES `habitaciones` (`id_habitacion`);

--
-- Filtros para la tabla `habitaciones`
--
ALTER TABLE `habitaciones`
  ADD CONSTRAINT `habitaciones_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias_habitacion` (`id_categoria`);

--
-- Filtros para la tabla `imagenes_habitacion`
--
ALTER TABLE `imagenes_habitacion`
  ADD CONSTRAINT `imagenes_habitacion_ibfk_1` FOREIGN KEY (`id_habitacion`) REFERENCES `habitaciones` (`id_habitacion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reservaciones`
--
ALTER TABLE `reservaciones`
  ADD CONSTRAINT `reservaciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
