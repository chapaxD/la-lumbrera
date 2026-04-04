-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 03-04-2026 a las 12:51:54
-- Versión del servidor: 8.2.0
-- Versión de PHP: 8.3.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `botanero_ventas`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caja_diaria`
--

DROP TABLE IF EXISTS `caja_diaria`;
CREATE TABLE IF NOT EXISTS `caja_diaria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idUsuarioApertura` int NOT NULL,
  `montoApertura` decimal(10,2) NOT NULL,
  `fechaApertura` datetime NOT NULL,
  `idUsuarioCierre` int DEFAULT NULL,
  `montoCierre` decimal(10,2) DEFAULT NULL,
  `ventasTotales` decimal(10,2) DEFAULT NULL,
  `fechaCierre` datetime DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'ABIERTA',
  `gastosTotales` decimal(10,2) DEFAULT '0.00',
  `ventasTarjeta` decimal(10,2) DEFAULT '0.00',
  `ventasQR` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


--
-- Estructura de tabla para la tabla `cancelaciones`
--

DROP TABLE IF EXISTS `cancelaciones`;
CREATE TABLE IF NOT EXISTS `cancelaciones` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tipo` enum('LOCAL','DELIVERY') NOT NULL,
  `referencia` varchar(50) NOT NULL,
  `idOrden` bigint UNSIGNED DEFAULT NULL,
  `idUsuario` bigint UNSIGNED DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



--
-- Estructura de tabla para la tabla `categorias`
--

DROP TABLE IF EXISTS `categorias`;
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tipo` enum('PLATILLO','BEBIDA') NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `tipo`, `nombre`, `descripcion`) VALUES
(1, 'PLATILLO', 'Plato Principal', 'Descripción de Plato Principal'),
(2, 'BEBIDA', 'Sodas', 'Todo tipo de bebida gaseosa'),
(3, 'PLATILLO', 'Carnes', 'Carnes a la parrilla '),
(4, 'BEBIDA', 'Postres', 'Postres dulces especialidad de la casa'),
(5, 'PLATILLO', 'Sopas', 'Descripción Sopas'),
(6, 'BEBIDA', 'Aguas', 'Todo tipo de aguas'),
(7, 'BEBIDA', 'Jugos', 'Todo tipo de jugos'),
(8, 'BEBIDA', 'Refrescos', 'Refrescos especialidad de la casa'),
(9, 'PLATILLO', 'Acompañamientos', 'Complementos'),
(10, 'PLATILLO', 'Pollos', 'Porciones de pollo a la parrilla'),
(11, 'PLATILLO', 'Hamburguesa', 'Hamburguesas de la casa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

DROP TABLE IF EXISTS `clientes`;
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nit` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `apellido`, `telefono`, `email`, `nit`, `direccion`, `notas`, `created_at`) VALUES
(1, 'Juan', 'Perez', '32658965', 'juan@gmail.com', '6598324', 'Av Siempre viva #123', 'Alérgico a la mantequilla de maní y mariscos', '2026-04-02 20:14:28'),
(2, 'Laura', 'Bozo', '54872154', 'laurabozo@gmail.com', '6589324', 'Lima #123', 'Alérgica a la mantequilla de maní', '2026-04-02 20:41:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

DROP TABLE IF EXISTS `facturas`;
CREATE TABLE IF NOT EXISTS `facturas` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `numero` int UNSIGNED NOT NULL,
  `fechaHora` datetime NOT NULL,
  `nitComprador` varchar(20) NOT NULL DEFAULT '99001',
  `nombreComprador` varchar(150) NOT NULL DEFAULT 'SIN NOMBRE',
  `codigoControl` varchar(100) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `descuentos` decimal(10,2) NOT NULL DEFAULT '0.00',
  `baseCredito` decimal(10,2) NOT NULL DEFAULT '0.00',
  `iva` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `nota` text,
  `idVenta` bigint UNSIGNED DEFAULT NULL,
  `idUsuario` bigint UNSIGNED NOT NULL,
  `estado` enum('EMITIDA','ANULADA') NOT NULL DEFAULT 'EMITIDA',
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_numero_factura` (`numero`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



--
-- Estructura de tabla para la tabla `factura_items`
--

DROP TABLE IF EXISTS `factura_items`;
CREATE TABLE IF NOT EXISTS `factura_items` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `idFactura` bigint UNSIGNED NOT NULL,
  `cantidad` int UNSIGNED NOT NULL DEFAULT '1',
  `descripcion` varchar(255) NOT NULL,
  `precioUnitario` decimal(10,2) NOT NULL DEFAULT '0.00',
  `descuento` decimal(10,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `fk_fi_factura` (`idFactura`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



--
-- Estructura de tabla para la tabla `gastos_caja`
--

DROP TABLE IF EXISTS `gastos_caja`;
CREATE TABLE IF NOT EXISTS `gastos_caja` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idCaja` int NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` datetime NOT NULL,
  `idUsuario` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



--
-- Estructura de tabla para la tabla `historial_stock`
--

DROP TABLE IF EXISTS `historial_stock`;
CREATE TABLE IF NOT EXISTS `historial_stock` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idInsumo` int NOT NULL,
  `idUsuario` int NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `nota` varchar(255) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=225 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



--
-- Estructura de tabla para la tabla `informacion_negocio`
--

DROP TABLE IF EXISTS `informacion_negocio`;
CREATE TABLE IF NOT EXISTS `informacion_negocio` (
  `nombre` varchar(100) DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `numeroMesas` tinyint DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT '',
  `nit_emisor` varchar(50) DEFAULT NULL,
  `razon_social` varchar(150) DEFAULT NULL,
  `actividad` varchar(255) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `num_autorizacion` varchar(100) DEFAULT NULL,
  `fecha_limite_emision` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `informacion_negocio`
--

INSERT INTO `informacion_negocio` (`nombre`, `telefono`, `numeroMesas`, `logo`, `direccion`, `nit_emisor`, `razon_social`, `actividad`, `ciudad`, `num_autorizacion`, `fecha_limite_emision`) VALUES
('La Lumbrera', '77376746', 10, './fotos/69c7e7a950f8e.png', 'Av. Alemana 5375 entre 5to y 6to anillo, Santa Cruz de la Sierra, Bolivia\n\n', '5801935', 'La Lumbrera', 'restaurante', 'Santa Cruz de la Sierra', '545412', '2026-04-30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumos`
--

DROP TABLE IF EXISTS `insumos`;
CREATE TABLE IF NOT EXISTS `insumos` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` varchar(100) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `precio` decimal(6,2) NOT NULL,
  `tipo` enum('PLATILLO','BEBIDA') NOT NULL,
  `categoria` bigint UNSIGNED NOT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `stockMinimo` int NOT NULL DEFAULT '5',
  `stockMateria` int DEFAULT '0',
  `tipoCorte` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `insumos`
--

INSERT INTO `insumos` (`id`, `codigo`, `nombre`, `descripcion`, `precio`, `tipo`, `categoria`, `stock`, `stockMinimo`, `stockMateria`, `tipoCorte`) VALUES
(1, 'P001', 'Picante De Gallina', 'Picante de gallina criolla', 25.00, 'PLATILLO', 1, 3, 6, 0, 0),
(2, 'S001', 'Coca Cola 2Lts.', 'Gaseosa de 2 litrod', 12.00, 'BEBIDA', 2, 5, 5, 0, 0),
(3, 'C001', 'Ojo de bife ', 'Porción de 250 gr', 50.00, 'PLATILLO', 3, 13, 5, 0, 0),
(4, 'S002', 'Coca Cola 500 ml', 'Gaseosa de 500 ml ', 8.00, 'BEBIDA', 2, 6, 5, 0, 0),
(5, 'S003', 'Coca Cola Popular', 'Gaseosa de 650 ml', 8.00, 'BEBIDA', 2, 10, 5, 0, 0),
(6, 'S004', 'Coca Cola 1 Lts.', 'Gaseosa de 1 litro', 10.00, 'BEBIDA', 2, 11, 5, 0, 0),
(7, 'S005', 'Coca Cola Peque', 'Gaseosa de 250 ml', 5.00, 'BEBIDA', 2, 8, 5, 0, 0),
(8, 'A001', 'Agua Vital 2Lts.', 'Agua sin gas de 2 Lts.', 12.00, 'BEBIDA', 6, 10, 5, 0, 0),
(9, 'A002', 'Agua Vital 600 ml', 'Agua sin gas de 600 ml', 7.00, 'BEBIDA', 6, 10, 5, 0, 0),
(10, 'S006', 'Coca Cola Mini 190 ml', 'Gaseosa de 190 ml', 2.00, 'BEBIDA', 2, 8, 5, 0, 0),
(11, 'J001', 'Tropifrut 1 Lt', 'Jugo natural de frutas', 10.00, 'BEBIDA', 7, 10, 5, 0, 0),
(12, 'R001', 'Limonada 1Lt', 'Refresco natural de un litro', 10.00, 'BEBIDA', 8, 8, 5, 20, 0),
(13, 'C002', 'Bife de chorizo', 'Porción de 250 kg', 50.00, 'PLATILLO', 3, 4, 5, 0, 0),
(14, 'C003', 'Cuadril', 'Porción de 250 kg', 50.00, 'PLATILLO', 3, 9, 5, 0, 0),
(15, 'C004', 'Punta de \"S\"', 'Porción de 250 kg', 50.00, 'PLATILLO', 3, 18, 5, 0, 0),
(16, 'C005', 'Costilla de res', 'Porción  de 200 gr ', 35.00, 'PLATILLO', 3, 19, 5, 0, 0),
(17, 'C006', 'Pacumuto', 'Pacumuto de carne', 35.00, 'PLATILLO', 3, 8, 5, 0, 0),
(18, 'C007', 'Pacumuto mixto', 'De carne y cerdo', 35.00, 'PLATILLO', 3, 20, 5, 0, 0),
(19, 'C008', 'Colita de cuadril', '250 gr de cola de cuadril', 35.00, 'PLATILLO', 3, 5, 5, 0, 0),
(20, 'C009', 'Tablita', 'Tablita de carnes', 50.00, 'PLATILLO', 3, 11, 5, 0, 0),
(21, 'C010', 'Parrillada 3P', 'Parrillada 3P', 80.00, 'PLATILLO', 3, 7, 5, 0, 0),
(22, 'C011', 'Parrillada 6P', 'Parrillada 6P ', 100.00, 'PLATILLO', 3, 8, 5, 0, 0),
(23, 'C012', 'Matambre ', 'Matambre normal', 35.00, 'PLATILLO', 3, 1, 5, 0, 0),
(24, 'C013', 'Matambre pizza', 'Matambre con queso', 30.00, 'PLATILLO', 3, 8, 5, 0, 0),
(25, 'A001', 'Ubre', 'Porción de ubre', 10.00, 'PLATILLO', 9, 20, 5, 0, 0),
(26, 'A001', 'Corazón', 'Porción de corazón', 10.00, 'PLATILLO', 9, 18, 5, 0, 0),
(27, 'A003', 'Chorizo', 'Un chorizo', 10.00, 'PLATILLO', 9, 20, 5, 0, 0),
(28, 'A004', 'Papa', 'Porción de papa', 10.00, 'PLATILLO', 9, 17, 5, 0, 0),
(29, 'A005', 'Arroz', 'Porción de arroz', 10.00, 'PLATILLO', 9, 4, 5, 0, 0),
(30, 'A006', 'Yuca', 'Porción de yuca', 10.00, 'PLATILLO', 9, 17, 5, 0, 0),
(31, 'A007', 'Queso', 'Con queso', 5.00, 'PLATILLO', 9, 20, 5, 0, 0),
(32, '008', 'Tocino', 'con tocino', 5.00, 'PLATILLO', 9, 20, 5, 0, 0),
(33, '009', 'Huevo', 'Con huevo', 5.00, 'PLATILLO', 9, 19, 5, 0, 0),
(34, 'H001', 'Hamburguesa simple', 'Hamburguesa simple', 30.00, 'PLATILLO', 11, 2, 5, 0, 0),
(35, 'H002', 'Hamburguesa doble/C', 'Hamburguesa 250 gramos', 45.00, 'PLATILLO', 11, 0, 5, 0, 0),
(36, 'S001', 'Sopa de maní', 'Sopa de maní ', 10.00, 'PLATILLO', 5, 4, 5, 0, 0),
(37, 'S002', 'Sopa de fideo', 'Sopa de fideo ', 10.00, 'PLATILLO', 5, 44, 5, 0, 0),
(38, 'S003', 'Fanta', 'Soda Sabor naranja de 2 lts', 15.00, 'BEBIDA', 2, 12, 5, 8, 2000),
(39, 'F002', 'Fanta', '500 ml', 8.00, 'BEBIDA', 2, 10, 5, 3, 500),
(40, 'S001', 'Sprite de 2lt', 'Botella retornable de dos litros', 12.00, 'BEBIDA', 2, 9, 5, 20, 2000),
(41, 'P002', 'Tallarines con pollo', 'Tallarines con pollo ', 20.00, 'PLATILLO', 1, 15, 5, 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumos_venta`
--

DROP TABLE IF EXISTS `insumos_venta`;
CREATE TABLE IF NOT EXISTS `insumos_venta` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `idInsumo` bigint UNSIGNED NOT NULL,
  `precio` decimal(6,2) NOT NULL,
  `cantidad` int NOT NULL,
  `idVenta` bigint UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=218 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


--
-- Estructura de tabla para la tabla `items_orden`
--

DROP TABLE IF EXISTS `items_orden`;
CREATE TABLE IF NOT EXISTS `items_orden` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idOrden` int NOT NULL,
  `idInsumo` int NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `nombre` varchar(150) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `caracteristicas` text,
  `cantidad` int DEFAULT '1',
  `estado` varchar(20) DEFAULT 'pendiente',
  `tipo` varchar(30) NOT NULL DEFAULT 'PLATILLO',
  `pagado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idOrden` (`idOrden`)
) ENGINE=InnoDB AUTO_INCREMENT=434 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menu_dia`
--

DROP TABLE IF EXISTS `menu_dia`;
CREATE TABLE IF NOT EXISTS `menu_dia` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `idInsumo` bigint UNSIGNED NOT NULL,
  `diaSemana` tinyint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idInsumo` (`idInsumo`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


--
-- Estructura de tabla para la tabla `ordenes_activas`
--

DROP TABLE IF EXISTS `ordenes_activas`;
CREATE TABLE IF NOT EXISTS `ordenes_activas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo` enum('LOCAL','DELIVERY') NOT NULL,
  `referencia` varchar(50) NOT NULL,
  `atiende` varchar(100) DEFAULT NULL,
  `idUsuario` int DEFAULT NULL,
  `total` decimal(10,2) DEFAULT '0.00',
  `estado` varchar(20) DEFAULT 'ocupada',
  `cliente` varchar(100) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `tipo_orden` varchar(20) NOT NULL DEFAULT 'LOCAL',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_orden` (`tipo`,`referencia`)
) ENGINE=InnoDB AUTO_INCREMENT=125 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes_cocina`
--

DROP TABLE IF EXISTS `reportes_cocina`;
CREATE TABLE IF NOT EXISTS `reportes_cocina` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idInsumo` bigint UNSIGNED DEFAULT NULL,
  `nombreInsumo` varchar(255) NOT NULL,
  `tipo` enum('FALTANTE','BAJO_STOCK','VENCIDO','OTRO') NOT NULL DEFAULT 'FALTANTE',
  `nota` text,
  `idUsuario` bigint UNSIGNED DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `resuelto` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idInsumo` (`idInsumo`),
  KEY `idUsuario` (`idUsuario`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



--
-- Estructura de tabla para la tabla `reservas`
--

DROP TABLE IF EXISTS `reservas`;
CREATE TABLE IF NOT EXISTS `reservas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_cliente` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `personas` int DEFAULT '1',
  `idMesa` int DEFAULT NULL,
  `estado` enum('PENDIENTE','CONFIRMADA','CANCELADA','COMPLETADA','SENTADA','NO-SHOW') DEFAULT 'PENDIENTE',
  `notas` text,
  `idUsuario` bigint UNSIGNED DEFAULT NULL,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_reserva_usuario` (`idUsuario`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `correo` varchar(100) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` varchar(20) DEFAULT 'mesero',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `correo`, `nombre`, `telefono`, `password`, `rol`) VALUES
(1, 'roger@gmail.com', 'roger andia', '6598565', '$2y$10$6ZznJ3QhEYm378nb8PfCYOuoqzJ8CtgY2EuqsQMJMV5TcPnWAL.q6', 'admin'),
(2, 'alex@gmail.com', 'Alex Andia', '65983265', '$2y$10$gtVzLaQqx044hmTPFNWXXOOzlJF5xLj6Dopp31tcXAOtw4tvmMgBi', 'mesero'),
(3, 'diego@gmail.com', 'Diego', '32659865', '$2y$10$RLX2TEidSXC3ghIx.qJ.3.kV6WyNPJLp88Zd/gl7bCTNMTwzmRv5m', 'mesero'),
(4, 'cocina@gmail.com', 'Cocina', '65326598', '$2y$10$c.W3R4/WSiV5mGG9rnCsIeIXbQKSP5UiWqXdZ2FYLH9KsP2QzxmbO', 'cocina'),
(5, 'rafa@lumbrera.com', 'Rafael ', '54659865', '$2y$10$UkwENyE8/QPARHVFZfoWG.L/5itiJO9OCHdc6effkmBJLF0N1KKXi', 'admin');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

DROP TABLE IF EXISTS `ventas`;
CREATE TABLE IF NOT EXISTS `ventas` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `idMesa` tinyint NOT NULL,
  `cliente` varchar(100) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  `total` decimal(6,2) NOT NULL,
  `pagado` decimal(6,2) NOT NULL,
  `idUsuario` bigint UNSIGNED NOT NULL,
  `metodoPago` varchar(50) DEFAULT 'EFECTIVO',
  `montoEfectivo` decimal(10,2) DEFAULT '0.00',
  `montoTarjeta` decimal(10,2) DEFAULT '0.00',
  `montoQR` decimal(10,2) DEFAULT '0.00',
  `tipo_orden` enum('LOCAL','DELIVERY') DEFAULT 'LOCAL',
  `direccion` text,
  `telefono` varchar(20) DEFAULT NULL,
  `estado_delivery` enum('PENDIENTE','REPARTO','ENTREGADO') DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=146 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Restricciones para tablas volcadas
--

-- Filtros para la tabla `factura_items`
--
ALTER TABLE `factura_items`
  ADD CONSTRAINT `fk_fi_factura` FOREIGN KEY (`idFactura`) REFERENCES `facturas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `items_orden`
--
ALTER TABLE `items_orden`
  ADD CONSTRAINT `items_orden_ibfk_1` FOREIGN KEY (`idOrden`) REFERENCES `ordenes_activas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `menu_dia`
--
ALTER TABLE `menu_dia`
  ADD CONSTRAINT `menu_dia_ibfk_1` FOREIGN KEY (`idInsumo`) REFERENCES `insumos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reportes_cocina`
--
ALTER TABLE `reportes_cocina`
  ADD CONSTRAINT `reportes_cocina_ibfk_1` FOREIGN KEY (`idInsumo`) REFERENCES `insumos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reportes_cocina_ibfk_2` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `fk_reserva_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
