CREATE DATABASE IF NOT EXISTS emporio_brillante CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE emporio_brillante;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL  -- hash BCRYPT
);
CREATE TABLE productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  tipo ENUM('impresora','servicio') NOT NULL,
  categoria VARCHAR(50) NOT NULL DEFAULT '',
  descripcion VARCHAR(500) NOT NULL DEFAULT '',
  precio DECIMAL(10,2) NOT NULL,
  imagen VARCHAR(100) NOT NULL DEFAULT ''
);
CREATE TABLE cupones (
  codigo VARCHAR(30) PRIMARY KEY,
  descuento TINYINT UNSIGNED NOT NULL  -- porcentaje
);
CREATE TABLE compras (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL, direccion VARCHAR(150) NOT NULL,
  telefono VARCHAR(30) NOT NULL, email VARCHAR(100) NOT NULL,
  pago ENUM('efectivo','transferencia','tarjeta') NOT NULL,
  cupon VARCHAR(30) NULL, total DECIMAL(12,2) NOT NULL,
  creada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE compra_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  compra_id INT NOT NULL, producto_id INT NOT NULL,
  cantidad SMALLINT UNSIGNED NOT NULL, precio_unitario DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id)
);
CREATE TABLE consultas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL,
  servicio VARCHAR(50) NOT NULL, consulta TEXT NOT NULL,
  creada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE encuestas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  satisfaccion TINYINT UNSIGNED NOT NULL,
  recomienda ENUM('Sí','Tal vez','No') NOT NULL,
  comentario VARCHAR(200) NOT NULL DEFAULT '',
  creada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Usuario admin / contraseña: Admin2026!  (cambiala después)
INSERT INTO usuarios (usuario, password) VALUES
('admin', '$2y$12$IeFwqBD/WG5.dXtZCdE4VeyIqzPxLPlmj9era9PMYoTuT4Z1oJ7hG');
INSERT INTO cupones VALUES ('UCP10', 10);
INSERT INTO productos (nombre,tipo,categoria,descripcion,precio,imagen) VALUES
('Impresora gigante','impresora','Impresora','Una impresora realmente grande, ideal para copias de alto volumen.',2000,'impresora.jpg'),
('Impresora chiquitita','impresora','Impresora','Impresora muy chiquitita.',1500,'impresora_chiquitita.jpg'),
('Impresora antigua','impresora','Impresora','Impresora antigua, para gente antigua.',1000,'impresora_antigua.jpg'),
('Impresora normal','impresora','Impresora','Impresora totalmente normal.',5000,'imprsora_normal.jpg'),
('Reparación de pantalla','servicio','Servicio','Reparación de pantalla.',45000,''),
('Cambio de batería','servicio','Servicio','Cambio de batería.',30000,''),
('Limpieza de notebook','servicio','Servicio','Limpieza de notebook.',20000,''),
('Formateo de PC','servicio','Servicio','Formateo de PC.',25000,'');
