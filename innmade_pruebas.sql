-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-11-2026 a las 00:48:30
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
-- Base de datos: `sistemainventario_v3.2`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ajuste_inventario`
--

CREATE TABLE `ajuste_inventario` (
  `id_ajuste` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `tipo_item` enum('producto','material','herramienta') NOT NULL,
  `id_item` int(11) NOT NULL,
  `stock_sistema` decimal(12,2) NOT NULL,
  `stock_fisico` decimal(12,2) NOT NULL,
  `diferencia` decimal(12,2) NOT NULL,
  `motivo` varchar(255) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `idcategoria` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categoria`
--

INSERT INTO `categoria` (`idcategoria`, `nombre`, `descripcion`) VALUES
(1, 'Muebles de Oficina', 'Escritorios, estanterías y mobiliario corporativo.'),
(2, 'Muebles de Hogar', 'Mesas de centro, cómodas y muebles auxiliares.'),
(3, 'Dormitorios', 'Roperos, camas y veladores de diseño artesanal e industrial.'),
(4, 'Muebles de Oficina', 'Escritorios, estanterías y mobiliario corporativo.'),
(5, 'Muebles de Hogar', 'Mesas de centro, cómodas y muebles auxiliares.'),
(6, 'Dormitorios', 'Roperos, camas y veladores de diseño artesanal e industrial.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `idcliente` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`idcliente`, `nombre`, `telefono`, `direccion`) VALUES
(1, 'Carlos Mendoza Silva', '70012345', 'Av. 3 Pasos del Frente #120'),
(2, 'Ana Sofía Ribera', '71654321', 'Barrio Equipetrol, Calle 3 Este'),
(3, 'Carlos Mendoza Silva', '70012345', 'Av. 3 Pasos del Frente #120'),
(4, 'Ana Sofía Ribera', '71654321', 'Barrio Equipetrol, Calle 3 Este');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compra`
--

CREATE TABLE `compra` (
  `id_compra` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `nro_documento` varchar(50) DEFAULT NULL,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `estado` enum('registrada','anulada') NOT NULL DEFAULT 'registrada',
  `observacion` varchar(255) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_compra`
--

CREATE TABLE `detalle_compra` (
  `id_detalle` int(11) NOT NULL,
  `id_compra` int(11) NOT NULL,
  `tipo_item` enum('producto','material','herramienta') NOT NULL,
  `id_item` int(11) NOT NULL,
  `cantidad` decimal(12,2) NOT NULL,
  `costo_unitario` decimal(12,2) NOT NULL DEFAULT 0.00
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_material`
--

CREATE TABLE `detalle_material` (
  `iddetalle_material` int(11) NOT NULL,
  `idpedido` int(11) NOT NULL,
  `idmaterial` int(11) NOT NULL,
  `cantidad_usada` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_material`
--

INSERT INTO `detalle_material` (`iddetalle_material`, `idpedido`, `idmaterial`, `cantidad_usada`) VALUES
(1, 2, 4, 7.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedido`
--

CREATE TABLE `detalle_pedido` (
  `iddetalle_pedido` int(11) NOT NULL,
  `idpedido` int(11) NOT NULL,
  `idproducto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_pedido`
--

INSERT INTO `detalle_pedido` (`iddetalle_pedido`, `idpedido`, `idproducto`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 1, 1, 850.00),
(2, 2, 4, 1, 1800.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `herramienta`
--

CREATE TABLE `herramienta` (
  `idherramienta` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `estado` varchar(50) DEFAULT 'Disponible',
  `stock` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `herramienta`
--

INSERT INTO `herramienta` (`idherramienta`, `nombre`, `estado`, `stock`) VALUES
(1, 'Taladro Inalámbrico Bosch', 'Disponible', 5),
(2, 'Sierra Circular de Banco', 'Disponible', 1),
(3, 'Taladro Inalámbrico Bosch', 'Disponible', 3),
(4, 'Sierra Circular de Banco', 'Disponible', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario`
--

CREATE TABLE `inventario` (
  `idinventario` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `idherramienta` int(11) DEFAULT NULL,
  `tipo_movimiento` varchar(50) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inventario`
--

INSERT INTO `inventario` (`idinventario`, `idusuario`, `idherramienta`, `tipo_movimiento`, `cantidad`, `fecha`) VALUES
(1, 1, 1, 'ENTRADA', 1, '2026-11-09 21:54:27'),
(2, 1, 1, 'ENTRADA', 1, '2026-11-09 21:54:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `kardex`
--

CREATE TABLE `kardex` (
  `id_kardex` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `tipo_item` enum('producto','material','herramienta') NOT NULL,
  `id_item` int(11) NOT NULL,
  `tipo_movimiento` enum('entrada','salida') NOT NULL,
  `cantidad` decimal(12,2) NOT NULL,
  `stock_anterior` decimal(12,2) NOT NULL,
  `stock_resultante` decimal(12,2) NOT NULL,
  `costo_unitario` decimal(12,2) DEFAULT NULL,
  `referencia_tipo` enum('inicial','compra','pedido','ajuste','devolucion','manual') NOT NULL DEFAULT 'manual',
  `referencia_id` int(11) DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales`
--

CREATE TABLE `materiales` (
  `idmaterial` int(11) NOT NULL,
  `idtipo_material` int(11) NOT NULL,
  `idproveedor` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `unidad_medida` varchar(50) NOT NULL COMMENT 'Ej: metros, kilos, piezas',
  `stock_actual` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `materiales`
--

INSERT INTO `materiales` (`idmaterial`, `idtipo_material`, `idproveedor`, `nombre`, `unidad_medida`, `stock_actual`) VALUES
(1, 1, 1, 'Tablero Melamina 18mm Blanco', 'piezas', 50.00),
(2, 2, 2, 'Bisagra Cazoleta con Retén', 'piezas', 200.00),
(3, 1, 1, 'Tablero Melamina 18mm Blanco', 'piezas', 50.00),
(4, 2, 2, 'Bisagra Cazoleta con Retén', 'piezas', 193.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `idpago` int(11) NOT NULL,
  `idpedido` int(11) NOT NULL,
  `monto` decimal(11,2) NOT NULL,
  `metodo_pago` varchar(50) NOT NULL,
  `fecha_pago` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`idpago`, `idpedido`, `monto`, `metodo_pago`, `fecha_pago`) VALUES
(1, 1, 400.00, 'Efectivo', '2026-11-09 21:33:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido`
--

CREATE TABLE `pedido` (
  `idpedido` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `idcliente` int(11) NOT NULL,
  `fecha_pedido` datetime DEFAULT current_timestamp(),
  `total` decimal(11,2) NOT NULL,
  `estado` varchar(50) NOT NULL DEFAULT 'PENDIENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedido`
--

INSERT INTO `pedido` (`idpedido`, `idusuario`, `idcliente`, `fecha_pedido`, `total`, `estado`) VALUES
(1, 1, 1, '2026-11-09 21:33:18', 850.00, 'PENDIENTE'),
(2, 1, 4, '2026-11-09 21:40:18', 1800.00, 'PENDIENTE');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `idproducto` int(11) NOT NULL,
  `idcategoria` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `precio_venta` decimal(10,2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `ancho` decimal(10,2) DEFAULT NULL COMMENT '1. Ancho en cm',
  `alto` decimal(10,2) DEFAULT NULL COMMENT '2. Alto en cm',
  `profundidad` decimal(10,2) DEFAULT NULL COMMENT '3. Profundidad en cm',
  `largo` decimal(10,2) DEFAULT NULL COMMENT '4. Largo en cm',
  `espesor` decimal(10,2) DEFAULT NULL COMMENT '5. Espesor de material en mm o cm',
  `peso` decimal(10,2) DEFAULT NULL COMMENT '6. Peso estimado en kg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`idproducto`, `idcategoria`, `codigo`, `descripcion`, `stock`, `precio_venta`, `imagen`, `fecha`, `ancho`, `alto`, `profundidad`, `largo`, `espesor`, `peso`) VALUES
(1, 1, 'ESC-001', 'Escritorio Ejecutivo Minimalista', 5, 850.00, 'mueble1.jpg', '2026-11-09 21:30:41', 120.00, 75.00, 60.00, 120.00, 1.80, 25.50),
(2, 3, 'ROP-002', 'Ropero de 3 Puertas con Espejo', 2, 1800.00, 'muebles2.jpg', '2026-11-09 21:30:41', 150.00, 190.00, 55.00, 150.00, 1.80, 65.00),
(3, 1, 'ESC-001', 'Escritorio Ejecutivo Minimalista', 5, 850.00, 'muebles3.webp', '2026-11-09 21:33:18', 120.00, 75.00, 60.00, 120.00, 1.80, 25.50),
(4, 3, 'ROP-002', 'Ropero de 3 Puertas con Espejo', 1, 1800.00, 'muebles4.jpg', '2026-11-09 21:33:18', 150.00, 190.00, 55.00, 150.00, 1.80, 65.00),
(6, 1, '6767676', 'sofa de madera', 11, 65.00, 'vistas/img/productos/78110ca1747d83a1b33aae85b7213ca3.jpg', '2026-11-10 06:35:28', 3.00, 4.00, 2.00, 3.00, 3.00, 3.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor`
--

CREATE TABLE `proveedor` (
  `idproveedor` int(11) NOT NULL,
  `nombre_proveedor` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedor`
--

INSERT INTO `proveedor` (`idproveedor`, `nombre_proveedor`, `telefono`, `direccion`) VALUES
(1, 'Maderas del Oriente S.R.L.', '33456789', 'Parque Industrial, Manzana 12'),
(2, 'Herrajes y Accesorios Santa Cruz', '33211223', 'Comercial Chiriguano, Local 45'),
(3, 'Maderas del Oriente S.R.L.', '33456789', 'Parque Industrial, Manzana 12'),
(4, 'Herrajes y Accesorios Santa Cruz', '33211223', 'Comercial Chiriguano, Local 45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_material`
--

CREATE TABLE `tipo_material` (
  `idtipo_material` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_material`
--

INSERT INTO `tipo_material` (`idtipo_material`, `nombre`, `descripcion`) VALUES
(1, 'Tableros y Maderas', 'Melamina, MDF y madera maciza para estructuras.'),
(2, 'Herrajes y Accesorios', 'Tornillos, bisagras, rieles y manijas metálicas.'),
(3, 'Tableros y Maderas', 'Melamina, MDF y madera maciza para estructuras.'),
(4, 'Herrajes y Accesorios', 'Tornillos, bisagras, rieles y manijas metálicas.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `idusuario` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `perfil` varchar(50) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `ultimo_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`idusuario`, `nombre`, `usuario`, `password`, `perfil`, `foto`, `estado`, `ultimo_login`) VALUES
(1, 'Administrador', 'admin', '$2y$10$H.ODasooUiLX0kj68SXFq.u4u8jRaLj7m8rq9/cTZTSCg5Hg3Ec9W', 'Administrador', NULL, 1, '2026-11-14 19:31:33');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `ajuste_inventario`
--
ALTER TABLE `ajuste_inventario`
  ADD PRIMARY KEY (`id_ajuste`),
  ADD KEY `idx_ajuste_item` (`tipo_item`,`id_item`,`fecha`);

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`idcategoria`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`idcliente`);

--
-- Indices de la tabla `compra`
--
ALTER TABLE `compra`
  ADD PRIMARY KEY (`id_compra`),
  ADD KEY `idx_compra_proveedor` (`id_proveedor`),
  ADD KEY `idx_compra_fecha` (`fecha`);

--
-- Indices de la tabla `detalle_compra`
--
ALTER TABLE `detalle_compra`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `idx_detcompra_compra` (`id_compra`),
  ADD KEY `idx_detcompra_item` (`tipo_item`,`id_item`);

--
-- Indices de la tabla `detalle_material`
--
ALTER TABLE `detalle_material`
  ADD PRIMARY KEY (`iddetalle_material`),
  ADD KEY `fk_detmat_pedido` (`idpedido`),
  ADD KEY `fk_detmat_material` (`idmaterial`);

--
-- Indices de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD PRIMARY KEY (`iddetalle_pedido`),
  ADD KEY `fk_detpedido_pedido` (`idpedido`),
  ADD KEY `fk_detpedido_producto` (`idproducto`);

--
-- Indices de la tabla `herramienta`
--
ALTER TABLE `herramienta`
  ADD PRIMARY KEY (`idherramienta`);

--
-- Indices de la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD PRIMARY KEY (`idinventario`),
  ADD KEY `fk_inv_usuario` (`idusuario`),
  ADD KEY `fk_inv_herramienta` (`idherramienta`);

--
-- Indices de la tabla `kardex`
--
ALTER TABLE `kardex`
  ADD PRIMARY KEY (`id_kardex`),
  ADD KEY `idx_kardex_item` (`tipo_item`,`id_item`,`fecha`),
  ADD KEY `idx_kardex_ref` (`referencia_tipo`,`referencia_id`);

--
-- Indices de la tabla `materiales`
--
ALTER TABLE `materiales`
  ADD PRIMARY KEY (`idmaterial`),
  ADD KEY `fk_material_tipo` (`idtipo_material`),
  ADD KEY `fk_material_proveedor` (`idproveedor`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`idpago`),
  ADD KEY `fk_pago_pedido` (`idpedido`);

--
-- Indices de la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD PRIMARY KEY (`idpedido`),
  ADD KEY `fk_pedido_usuario` (`idusuario`),
  ADD KEY `fk_pedido_cliente` (`idcliente`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`idproducto`),
  ADD KEY `fk_producto_categoria` (`idcategoria`);

--
-- Indices de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  ADD PRIMARY KEY (`idproveedor`);

--
-- Indices de la tabla `tipo_material`
--
ALTER TABLE `tipo_material`
  ADD PRIMARY KEY (`idtipo_material`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`idusuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `ajuste_inventario`
--
ALTER TABLE `ajuste_inventario`
  MODIFY `id_ajuste` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `idcategoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `idcliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `compra`
--
ALTER TABLE `compra`
  MODIFY `id_compra` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_compra`
--
ALTER TABLE `detalle_compra`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_material`
--
ALTER TABLE `detalle_material`
  MODIFY `iddetalle_material` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  MODIFY `iddetalle_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `herramienta`
--
ALTER TABLE `herramienta`
  MODIFY `idherramienta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `inventario`
--
ALTER TABLE `inventario`
  MODIFY `idinventario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `kardex`
--
ALTER TABLE `kardex`
  MODIFY `id_kardex` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materiales`
--
ALTER TABLE `materiales`
  MODIFY `idmaterial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `idpago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `pedido`
--
ALTER TABLE `pedido`
  MODIFY `idpedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `idproducto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  MODIFY `idproveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `tipo_material`
--
ALTER TABLE `tipo_material`
  MODIFY `idtipo_material` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `idusuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle_compra`
--
ALTER TABLE `detalle_compra`
  ADD CONSTRAINT `fk_detcompra_compra` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_material`
--
ALTER TABLE `detalle_material`
  ADD CONSTRAINT `fk_detmat_material` FOREIGN KEY (`idmaterial`) REFERENCES `materiales` (`idmaterial`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detmat_pedido` FOREIGN KEY (`idpedido`) REFERENCES `pedido` (`idpedido`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD CONSTRAINT `fk_detpedido_pedido` FOREIGN KEY (`idpedido`) REFERENCES `pedido` (`idpedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detpedido_producto` FOREIGN KEY (`idproducto`) REFERENCES `productos` (`idproducto`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD CONSTRAINT `fk_inv_herramienta` FOREIGN KEY (`idherramienta`) REFERENCES `herramienta` (`idherramienta`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuario` (`idusuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `materiales`
--
ALTER TABLE `materiales`
  ADD CONSTRAINT `fk_material_proveedor` FOREIGN KEY (`idproveedor`) REFERENCES `proveedor` (`idproveedor`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_material_tipo` FOREIGN KEY (`idtipo_material`) REFERENCES `tipo_material` (`idtipo_material`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pagos_pedido` FOREIGN KEY (`idpedido`) REFERENCES `pedido` (`idpedido`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`idcliente`) REFERENCES `cliente` (`idcliente`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuario` (`idusuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`idcategoria`) REFERENCES `categoria` (`idcategoria`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
