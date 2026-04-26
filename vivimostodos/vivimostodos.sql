-- ============================================================
--  Vivimostodos — Script de Base de Datos Completo
--  Ejecutar en phpMyAdmin o MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS vivimostodos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vivimostodos;

-- ── USUARIOS ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario      INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100) NOT NULL,
    apellido        VARCHAR(100) NOT NULL,
    correo          VARCHAR(150) UNIQUE NOT NULL,
    password        VARCHAR(255) NOT NULL,
    cedula          VARCHAR(20)  UNIQUE NOT NULL,
    telefono        VARCHAR(20),
    rol             ENUM('ADMIN','RESIDENTE','SUPERVISOR') NOT NULL,
    estado          ENUM('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
    fecha_creacion  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── INVENTARIO ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS inventario (
    id_insumo           INT AUTO_INCREMENT PRIMARY KEY,
    nombre              VARCHAR(100) NOT NULL,
    descripcion         TEXT,
    cantidad_total      INT NOT NULL,
    cantidad_disponible INT NOT NULL,
    precio_unitario     DECIMAL(10,2) DEFAULT 0,
    estado              ENUM('DISPONIBLE','NO DISPONIBLE') DEFAULT 'DISPONIBLE'
);

-- ── RESERVAS ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reservas (
    id_reserva      INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario      INT NOT NULL,
    fecha_evento    DATE NOT NULL,
    hora_inicio     TIME DEFAULT '12:00:00',
    hora_fin        TIME DEFAULT '23:59:00',
    estado          ENUM('PENDIENTE','APROBADA','RECHAZADA','CANCELADA') DEFAULT 'PENDIENTE',
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observaciones   TEXT,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- ── DETALLE RESERVA ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS detalle_reserva (
    id_detalle  INT AUTO_INCREMENT PRIMARY KEY,
    id_reserva  INT,
    id_insumo   INT,
    cantidad    INT NOT NULL,
    subtotal    DECIMAL(10,2),
    FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva),
    FOREIGN KEY (id_insumo)  REFERENCES inventario(id_insumo)
);

-- ── PAGOS ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pagos (
    id_pago     INT AUTO_INCREMENT PRIMARY KEY,
    id_reserva  INT,
    comprobante VARCHAR(255),
    monto       DECIMAL(10,2),
    estado      ENUM('PENDIENTE','VALIDADO','RECHAZADO') DEFAULT 'PENDIENTE',
    fecha_pago  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva)
);

-- ── NOTIFICACIONES ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notificaciones (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario      INT,
    mensaje         TEXT,
    leido           BOOLEAN DEFAULT FALSE,
    fecha           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- ── REPORTES ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reportes (
    id_reporte        INT AUTO_INCREMENT PRIMARY KEY,
    tipo              VARCHAR(100),
    fecha_generacion  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archivo           VARCHAR(255)
);

-- ── DATOS INICIALES ──────────────────────────────────────────
-- Admin por defecto — contraseña: Admin123
INSERT INTO usuarios (nombre, apellido, correo, password, cedula, telefono, rol) VALUES
('Administrador', 'Sistema', 'admin@vivimostodos.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '1000000000', '3000000000', 'ADMIN');

-- Supervisor de prueba — contraseña: Admin123
INSERT INTO usuarios (nombre, apellido, correo, password, cedula, telefono, rol) VALUES
('Carlos', 'Supervisor', 'supervisor@vivimostodos.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '1000000001', '3001111111', 'SUPERVISOR');

-- Residente de prueba — contraseña: Admin123
INSERT INTO usuarios (nombre, apellido, correo, password, cedula, telefono, rol) VALUES
('María', 'García', 'residente@vivimostodos.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '1000000002', '3002222222', 'RESIDENTE');

-- Insumos del salón
INSERT INTO inventario (nombre, descripcion, cantidad_total, cantidad_disponible, precio_unitario, estado) VALUES
('Sillas',       'Sillas plásticas color blanco',    100, 100, 0,     'DISPONIBLE'),
('Mesas',        'Mesas rectangulares 1.5m',          20,  20, 0,     'DISPONIBLE'),
('Video Beam',   'Proyector Epson Full HD',             1,   1, 25000, 'DISPONIBLE'),
('Telón Blanco', 'Telón para proyección 2x2m',         1,   1, 0,     'DISPONIBLE'),
('Sonido',       'Parlante profesional + micrófono',   1,   1, 50000, 'DISPONIBLE'),
('Mantelería',   'Manteles blancos para mesas',        20,  20, 0,     'DISPONIBLE'),
('Extensiones',  'Extensiones eléctricas 10m',          5,   5, 0,     'DISPONIBLE');

-- ============================================================
--  FIN DEL SCRIPT
-- ============================================================
