-- Base de datos para sistema de reservaciones hoteleras
-- Eliminar base de datos si existe y crearla de nuevo
DROP DATABASE IF EXISTS playa;
CREATE DATABASE playa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE playa;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telefono VARCHAR(20),
    tipo_usuario ENUM('admin', 'huesped', 'no_registrado') NOT NULL DEFAULT 'no_registrado',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo TINYINT(1) DEFAULT 1,
    INDEX idx_nombre_usuario (nombre_usuario),
    INDEX idx_tipo_usuario (tipo_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de categorías de habitaciones
CREATE TABLE categorias_habitacion (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre_categoria VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    INDEX idx_nombre_categoria (nombre_categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de habitaciones
CREATE TABLE habitaciones (
    id_habitacion INT AUTO_INCREMENT PRIMARY KEY,
    numero_habitacion VARCHAR(20) NOT NULL UNIQUE,
    id_categoria INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio_noche DECIMAL(10,2) NOT NULL,
    capacidad_personas INT NOT NULL,
    cantidad_disponible INT NOT NULL DEFAULT 0,
    caracteristicas TEXT,
    activo TINYINT(1) DEFAULT 1,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_categoria) REFERENCES categorias_habitacion(id_categoria) ON DELETE RESTRICT,
    INDEX idx_categoria (id_categoria),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de imágenes de habitaciones
CREATE TABLE imagenes_habitacion (
    id_imagen INT AUTO_INCREMENT PRIMARY KEY,
    id_habitacion INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    es_principal TINYINT(1) DEFAULT 0,
    orden_visualizacion INT DEFAULT 0,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_habitacion) REFERENCES habitaciones(id_habitacion) ON DELETE CASCADE,
    INDEX idx_habitacion (id_habitacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de reservaciones
CREATE TABLE reservaciones (
    id_reservacion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    fecha_entrada DATE NOT NULL,
    fecha_salida DATE NOT NULL,
    numero_noches INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    impuestos DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    estado_reservacion ENUM('pendiente', 'confirmada', 'cancelada', 'completada') DEFAULT 'pendiente',
    fecha_reservacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notas TEXT,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    INDEX idx_usuario (id_usuario),
    INDEX idx_fechas (fecha_entrada, fecha_salida),
    INDEX idx_estado (estado_reservacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de detalles de reservaciones
CREATE TABLE detalles_reservacion (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_reservacion INT NOT NULL,
    id_habitacion INT NOT NULL,
    cantidad_habitaciones INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_reservacion) REFERENCES reservaciones(id_reservacion) ON DELETE CASCADE,
    FOREIGN KEY (id_habitacion) REFERENCES habitaciones(id_habitacion) ON DELETE RESTRICT,
    INDEX idx_reservacion (id_reservacion),
    INDEX idx_habitacion (id_habitacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar categorías por defecto
INSERT INTO categorias_habitacion (nombre_categoria, descripcion) VALUES
('Estándar', 'Habitaciones cómodas y funcionales con todas las comodidades básicas'),
('Deluxe', 'Habitaciones amplias con amenidades premium y vistas espectaculares'),
('Suite', 'Suites de lujo con sala de estar, comedor y servicios exclusivos'),
('Presidencial', 'La experiencia más lujosa con servicios personalizados y espacios amplios');

-- Insertar usuario administrador por defecto
-- Contraseña: admin123 (debe ser hasheada en producción)
INSERT INTO usuarios (nombre_usuario, contrasena, nombre_completo, email, telefono, tipo_usuario) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador del Sistema', 'admin@hotel.com', '5551234567', 'admin');

-- Insertar usuario huésped de prueba
-- Contraseña: huesped123
INSERT INTO usuarios (nombre_usuario, contrasena, nombre_completo, email, telefono, tipo_usuario) VALUES
('huesped1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Pérez García', 'juan.perez@email.com', '5559876543', 'huesped');

-- Insertar habitaciones de ejemplo
INSERT INTO habitaciones (numero_habitacion, id_categoria, nombre, descripcion, precio_noche, capacidad_personas, cantidad_disponible, caracteristicas) VALUES
('101', 1, 'Habitación Estándar Individual', 'Habitación acogedora con cama individual, ideal para viajeros solitarios', 850.00, 1, 5, 'TV de pantalla plana, WiFi gratuito, Aire acondicionado, Baño privado'),
('102', 1, 'Habitación Estándar Doble', 'Habitación con dos camas matrimoniales, perfecta para familias pequeñas', 1200.00, 4, 8, 'TV de pantalla plana, WiFi gratuito, Aire acondicionado, Minibar, Baño privado'),
('201', 2, 'Habitación Deluxe con Vista al Mar', 'Habitación elegante con balcón y vista panorámica al océano', 2500.00, 2, 4, 'Cama King Size, TV Smart, WiFi gratuito, Aire acondicionado, Minibar, Balcón privado, Cafetera, Baño con jacuzzi'),
('202', 2, 'Habitación Deluxe Familiar', 'Espaciosa habitación con zona de estar, ideal para familias', 3000.00, 5, 3, 'Cama King Size + Sofá cama, TV Smart, WiFi gratuito, Aire acondicionado, Minibar, Sala de estar, Baño amplio'),
('301', 3, 'Suite Romántica', 'Suite elegante con jacuzzi privado y decoración romántica', 4500.00, 2, 2, 'Cama King Size, TV Smart, WiFi gratuito, Aire acondicionado, Minibar premium, Sala de estar, Jacuzzi privado, Balcón con vista al mar, Servicio de habitación 24h'),
('302', 3, 'Suite Familiar Premium', 'Amplia suite de dos habitaciones con todas las comodidades', 5500.00, 6, 2, '2 habitaciones, Cama King Size + 2 Matrimoniales, 2 TV Smart, WiFi gratuito, Aire acondicionado, Minibar premium, Sala de estar, Comedor, 2 baños completos'),
('401', 4, 'Suite Presidencial', 'La experiencia definitiva en lujo y confort con servicios exclusivos', 8500.00, 4, 1, 'Habitación principal King Size + Habitación secundaria, 3 TV Smart, WiFi premium, Aire acondicionado, Bar completo, Sala de estar amplia, Comedor privado, Terraza privada, 2 baños de lujo con jacuzzi, Mayordomo personal, Servicio de habitación 24h');

-- Crear vistas útiles
CREATE VIEW vista_habitaciones_completa AS
SELECT
    h.id_habitacion,
    h.numero_habitacion,
    h.nombre,
    h.descripcion,
    h.precio_noche,
    h.capacidad_personas,
    h.cantidad_disponible,
    h.caracteristicas,
    h.activo,
    c.id_categoria,
    c.nombre_categoria,
    c.descripcion as categoria_descripcion,
    (SELECT COUNT(*) FROM imagenes_habitacion WHERE id_habitacion = h.id_habitacion) as total_imagenes,
    (SELECT ruta_archivo FROM imagenes_habitacion WHERE id_habitacion = h.id_habitacion AND es_principal = 1 LIMIT 1) as imagen_principal
FROM habitaciones h
INNER JOIN categorias_habitacion c ON h.id_categoria = c.id_categoria;

CREATE VIEW vista_reservaciones_completa AS
SELECT
    r.id_reservacion,
    r.fecha_entrada,
    r.fecha_salida,
    r.numero_noches,
    r.subtotal,
    r.impuestos,
    r.total,
    r.estado_reservacion,
    r.fecha_reservacion,
    r.notas,
    u.id_usuario,
    u.nombre_usuario,
    u.nombre_completo,
    u.email,
    u.telefono
FROM reservaciones r
INNER JOIN usuarios u ON r.id_usuario = u.id_usuario;
