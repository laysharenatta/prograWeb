-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS nikenza_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nikenza_store;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de productos
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    features TEXT, -- JSON string con las características
    image_icon VARCHAR(50), -- Clase de icono FontAwesome
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de paquetes
CREATE TABLE IF NOT EXISTS paquetes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    items TEXT NOT NULL, -- JSON string con los items del paquete
    featured BOOLEAN DEFAULT FALSE,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar usuario administrador por defecto
INSERT INTO usuarios (username, email, password_hash, role) 
VALUES ('admin', 'admin@nikenza.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE username = username;

-- Insertar productos de ejemplo basados en el HTML original
INSERT INTO productos (name, description, price, category, features, image_icon) VALUES
('Taza Estándar', 'Taza estándar de cerámica personalizada', 85.00, 'tazas', '["Taza estándar de cerámica - $85", "Taza con asa de color - $95", "Taza mágica - $120", "Par de tazas corazón - $215", "Taza recta grande 444ml - $130"]', 'fas fa-mug-hot'),
('Tapete Afelpado', 'Tapete blanco afelpado suave para interior', 300.00, 'tapetes', '["Tapete blanco afelpado suave", "Para interior, revés antideslizante", "Medida: 34 x 58 cm", "Impresión máxima: 30 x 43 cm", "Diseño personalizado"]', 'fas fa-home'),
('Sudaderas', 'Sudaderas personalizadas de algodón', 425.00, 'sudaderas', '["Sudadera sencilla - $425", "Sudadera con gorra - $500", "Algodón, sencilla, cerrada", "Cuello redondo", "Diseño personalizado"]', 'fas fa-tshirt'),
('Camisas Uniforme', 'Camisas de uniforme en gabardina peinada', 195.00, 'uniformes', '["Gabardina peinada, muy durable", "Para dama y caballero", "Tallas: CH, M, G, EG, 2XL, 3XL, 4XL", "Frente: $195 - $535", "Frente y vuelta: $510 - $550"]', 'fas fa-user-tie')
ON DUPLICATE KEY UPDATE name = name;

-- Insertar paquetes de ejemplo
INSERT INTO paquetes (name, description, price, items, featured) VALUES
('Paquete Básico', 'Paquete básico con productos esenciales', 250.00, '["1 Taza estándar", "1 Llavero de acero con impresión frente y vuelta", "1 Rompecabezas tamaño carta", "Diseño personalizado a tu elección"]', FALSE),
('Paquete Viajero', 'Paquete ideal para viajeros', 320.00, '["1 Bolsa ecológica chica", "2 Llaveros de acero con impresión frente y vuelta", "1 Cojín 20 x 30 cm", "Diseño personalizado a tu elección"]', TRUE),
('Paquete Deportivo', 'Paquete para deportistas', 575.00, '["1 Playera deportiva Dryfit impresa al frente", "1 Gorra combinada (color a elegir)", "1 Vaso alto de acero (blanco o plata)", "Diseño personalizado a tu elección"]', FALSE),
('Paquete Potterhead', 'Paquete temático de Harry Potter', 635.00, '["1 Playera deportiva Dryfit con diseño de Quidditch", "1 Termo cafetero con asa y escudo de Hogwarts", "1 Llavero de acero con impresión frente y vuelta", "Diseño de tu casa de Hogwarts y fecha de cumpleaños"]', FALSE)
ON DUPLICATE KEY UPDATE name = name;



-- Tabla para almacenar los pedidos/compras
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    estado ENUM('pendiente', 'procesando', 'completado', 'cancelado') DEFAULT 'pendiente',
    nombre_cliente VARCHAR(100) NOT NULL,
    email_cliente VARCHAR(100) NOT NULL,
    telefono_cliente VARCHAR(20),
    direccion_entrega TEXT,
    notas TEXT,
    fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_estado (estado),
    INDEX idx_fecha_pedido (fecha_pedido)
);

-- Tabla para almacenar los detalles de cada pedido
CREATE TABLE IF NOT EXISTS detalles_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    INDEX idx_pedido_id (pedido_id),
    INDEX idx_producto_id (producto_id)
);

-- Insertar algunos productos de ejemplo para pruebas
INSERT INTO productos (name, description, price, category, features, image_icon, active) VALUES
('Taza Personalizada Premium', 'Taza de cerámica de alta calidad con diseño personalizado', 25.99, 'tazas', '["Cerámica premium", "Diseño personalizado", "Apta para microondas", "Resistente al lavavajillas"]', 'fas fa-mug-hot', 1),
('Sudadera Corporativa', 'Sudadera con capucha para uniformes corporativos', 45.50, 'sudaderas', '["Algodón 100%", "Bordado incluido", "Tallas S-XXL", "Colores personalizables"]', 'fas fa-tshirt', 1),
('Tapete Antideslizante', 'Tapete personalizado con logo empresarial', 35.00, 'tapetes', '["Material antideslizante", "Impresión de alta calidad", "Resistente al agua", "Fácil limpieza"]', 'fas fa-home', 1),
('Uniforme Completo', 'Set completo de uniforme empresarial', 89.99, 'uniformes', '["Camisa + Pantalón", "Telas de calidad", "Bordado incluido", "Tallas completas"]', 'fas fa-user-tie', 1);

