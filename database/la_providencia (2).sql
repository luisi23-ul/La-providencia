-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 24-05-2026 a las 02:45:03
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
-- Base de datos: `la_providencia`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre_categoria` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre_categoria`) VALUES
(1, 'Aluminio'),
(2, 'Lencería'),
(3, 'Plástico');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ventas`
--

CREATE TABLE `detalle_ventas` (
  `id` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_ventas`
--

INSERT INTO `detalle_ventas` (`id`, `id_venta`, `id_producto`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 23, 5, 6.00, 30.00),
(2, 1, 20, 3, 6.00, 18.00),
(3, 1, 24, 1, 4.00, 4.00),
(4, 2, 23, 15, 6.00, 90.00),
(5, 2, 20, 5, 6.00, 30.00),
(6, 3, 32, 1, 5.00, 5.00),
(7, 4, 32, 3, 5.00, 15.00),
(8, 5, 32, 1, 5.00, 5.00),
(9, 6, 32, 1, 5.00, 5.00),
(10, 7, 32, 1, 5.00, 5.00),
(11, 8, 34, 1, 15.00, 15.00),
(12, 9, 39, 1, 4.00, 4.00),
(13, 10, 32, 1, 5.00, 5.00),
(14, 11, 38, 1, 25.00, 25.00),
(15, 12, 46, 1, 16.00, 16.00),
(16, 13, 44, 1, 5.00, 5.00),
(17, 14, 41, 1, 15.00, 15.00),
(18, 15, 43, 1, 18.00, 18.00),
(19, 15, 34, 1, 15.00, 15.00),
(20, 15, 40, 1, 10.00, 10.00),
(21, 15, 36, 3, 30.00, 90.00),
(22, 15, 39, 1, 4.00, 4.00),
(23, 16, 36, 1, 30.00, 30.00),
(24, 16, 35, 1, 1.50, 1.50),
(25, 17, 46, 1, 16.00, 16.00),
(26, 17, 44, 1, 5.00, 5.00),
(27, 17, 41, 1, 15.00, 15.00),
(28, 18, 45, 1, 7.00, 7.00),
(29, 18, 46, 1, 16.00, 16.00),
(30, 19, 44, 1, 5.00, 5.00),
(31, 20, 47, 1, 15.00, 15.00),
(32, 21, 45, 1, 7.00, 7.00),
(33, 22, 47, 1, 15.00, 15.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `favoritos`
--

CREATE TABLE `favoritos` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `fecha_agregado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodos_pago`
--

CREATE TABLE `metodos_pago` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `metodos_pago`
--

INSERT INTO `metodos_pago` (`id`, `nombre`) VALUES
(1, 'Efectivo Bs'),
(2, 'Efectivo $'),
(3, 'Transferencia'),
(4, 'Pago Móvil');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `nombre_producto` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `imagen` varchar(255) DEFAULT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `estado` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `nombre_producto`, `descripcion`, `precio`, `stock`, `imagen`, `id_categoria`, `estado`) VALUES
(20, 'banco', 'hogar', 6.00, 5, '1778693541_photo_5186299810699480024_m.jpg', 3, 0),
(23, 'ban', 'hogar', 6.00, 15, '1779038651_luisana_proceso.png', 3, 0),
(24, 'ss', 'ssfs', 6.98, 97, 'photo_5186299810699480023_x.jpg', 1, 0),
(31, 'silla', 'casa', 4.00, 0, 'photo_5186299810699480023_x.jpg', 1, 0),
(32, 'Mesa de niños', 'Hogar', 5.00, 6, '1779040920_photo_5186299810699480032_y.jpg', 1, 1),
(33, 'silla', 'hogar. color rojo.', 15.00, 15, '1779050159_photo_5186299810699480040_y.jpg', 3, 1),
(34, 'Topper de plastico', 'hogar', 15.00, 9, 'photo_5186299810699480039_x.jpg', 3, 1),
(35, 'Vaso de plastico', 'Hogar', 1.50, 40, 'vaso-plastico.jpg', 2, 1),
(36, 'Set de ollas y sartenes', 'Cocina', 30.00, 12, 'Set-ollas.jpg', 1, 1),
(37, 'Juego de sabanas (Blanco y rosa)', 'Set de sabanas con estampado a cuadros blaco y rosado', 25.00, 22, 'Juego-sabanas2.jpg', 2, 1),
(38, 'Juego de sabnas (Azul)', 'Juego de sabanas azul cielo', 25.00, 32, 'Juego-sabanas1.jpg', 2, 1),
(39, 'Ponchera plastica', 'Ponchera de plastico roja ', 4.00, 4, 'Ponchera.jpg', 3, 1),
(40, 'Caldero', 'Caldero grande de aluminio', 10.00, 6, 'Caldero.jpg', 1, 1),
(41, 'Mesa  ', 'Mesa de plastico grande coloro azul', 15.00, 14, 'mesa-plastico.jpg', 3, 1),
(42, 'Maceto', 'Jardin', 8.00, 4, 'Maceta-plastico.jpg', 3, 1),
(43, 'Platera', 'Cocina', 18.00, 7, 'Platera.jpg', 1, 1),
(44, 'Colador', 'Cocina', 5.00, 5, 'Colador-plastico.jpg', 3, 1),
(45, 'Cesta de ropa', 'Hogar', 7.00, 13, 'Cesta-ropa.jpg', 3, 1),
(46, 'Cava', 'hogar', 16.00, 9, 'Cava.jpg', 3, 1),
(47, 'Contenedor', 'Hogar', 15.00, 23, 'Contenedor-plastico.jpg', 3, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre_rol`) VALUES
(1, 'Master'),
(2, 'Administrador'),
(3, 'Cliente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(50) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `id_rol` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `telefono`, `correo`, `clave`, `id_rol`) VALUES
(2, 'luisana', 'santeliz', '0427818865', 'santelizluisana27@gmail.com', '101010', 3),
(3, 'pedfro', 'soto', '04145343633', 'pedrito@gmail.com', '121212', 3),
(4, 'leo', 'saul', '01010254', 'sal@gmail.com', '$2y$10$Qtz6/p1qvRj46rl1wYfyvuwG9ylfH5AhDEkz5wZUMvTYa16mceKyu', 3),
(5, 'maria', 'perez', '04145686213', 'mari@gmail.com', '$2y$10$D5C06k2.M5HW7Cjdjia8behcpc/fIOWPygTJIq2.l5hc3xV5Ecd1S', 3),
(6, 'mario', 'pedro', '55555858', 'ekre@gmail.com', '$2y$10$ue8vCWGr1qxV2F/t3J1Yme55NiHCuTJmyfTF1ruFIJO1MZU8TbKLO', 3),
(7, 'rita', 'soto', '04127818865', 'rita@gmail.com', '$2y$10$R6dcMmFm7P.wW2KzASHyb.a4Bt15kfpEc.fqSMx1oehuqlNrhVHka', 3),
(8, 'edgar duno', NULL, '04120995076', 'eduno17@gmail.com', '$2y$10$UeOisS63VXTrPBuqvTMoeeaSzJnpcYKGHMTferJGuk1c.c/xRmsqG', 3),
(9, 'leo', NULL, '04145433432', 'leoleo@gmail.com', '$2y$10$hycLXqBhaIn1RmoDOgeH3.gp5IT.kfQt5Cb6J8s7W5R75cSHz/BE.', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','pagado','retirado','completado','cancelado') DEFAULT 'pendiente',
  `id_metodo_pago` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `id_usuario`, `fecha`, `total`, `estado`, `id_metodo_pago`) VALUES
(1, 7, '2026-05-15 15:34:54', 52.00, '', NULL),
(2, 7, '2026-05-15 23:37:23', 120.00, '', NULL),
(3, 7, '2026-05-17 16:12:26', 5.00, '', NULL),
(4, 7, '2026-05-17 16:13:48', 15.00, '', NULL),
(5, 7, '2026-05-17 16:18:05', 5.00, '', NULL),
(6, 7, '2026-05-17 16:20:39', 5.00, '', NULL),
(7, 7, '2026-05-17 16:20:54', 5.00, '', NULL),
(8, 7, '2026-05-22 13:56:56', 15.00, '', NULL),
(9, 7, '2026-05-22 14:01:41', 4.00, '', NULL),
(10, 7, '2026-05-22 14:02:00', 5.00, '', NULL),
(11, 7, '2026-05-22 14:16:51', 25.00, 'retirado', NULL),
(12, 7, '2026-05-22 15:34:41', 16.00, 'retirado', NULL),
(13, 7, '2026-05-22 15:53:34', 5.00, 'pagado', NULL),
(14, 7, '2026-05-22 15:55:28', 15.00, 'pagado', NULL),
(15, 7, '2026-05-22 15:56:30', 137.00, 'retirado', NULL),
(16, 9, '2026-05-22 15:58:36', 31.50, 'pagado', NULL),
(17, 7, '2026-05-22 22:24:11', 36.00, 'pagado', NULL),
(18, 7, '2026-05-23 16:21:19', 23.00, 'pagado', 1),
(19, 9, '2026-05-23 16:32:54', 5.00, 'pagado', 2),
(20, 9, '2026-05-23 16:34:14', 15.00, 'pagado', 4),
(21, 9, '2026-05-23 16:34:40', 7.00, 'retirado', 3),
(22, 9, '2026-05-23 17:02:49', 15.00, 'pagado', 4);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_venta` (`id_venta`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`,`id_producto`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `id_rol` (`id_rol`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `fk_ventas_metodo_pago` (`id_metodo_pago`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD CONSTRAINT `detalle_ventas_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_ventas_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `favoritos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favoritos_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`);

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `fk_ventas_metodo_pago` FOREIGN KEY (`id_metodo_pago`) REFERENCES `metodos_pago` (`id`),
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
