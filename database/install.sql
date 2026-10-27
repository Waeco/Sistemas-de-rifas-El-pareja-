CREATE DATABASE IF NOT EXISTS sistema_rifas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_rifas;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(30) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  purchase_mode ENUM('view','buy') NOT NULL DEFAULT 'buy',
  preferred_channel ENUM('email','sms','whatsapp') NOT NULL DEFAULT 'email',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  description TEXT NULL,
  price DECIMAL(12,2) NOT NULL,
  image_path VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE raffles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  code VARCHAR(30) NOT NULL UNIQUE,
  ticket_price DECIMAL(12,2) NOT NULL,
  tickets_to_cover_cost INT UNSIGNED NOT NULL,
  tickets_to_target INT UNSIGNED NOT NULL,
  sold_count INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  winner_ticket_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  closed_at DATETIME NULL,
  CONSTRAINT fk_raffle_product FOREIGN KEY(product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_raffles_status(status)
) ENGINE=InnoDB;

CREATE TABLE purchases (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  raffle_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  total DECIMAL(12,2) NOT NULL,
  status ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_purchase_user FOREIGN KEY(user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_purchase_raffle FOREIGN KEY(raffle_id) REFERENCES raffles(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_purchase_date(created_at),
  INDEX idx_purchase_user(user_id)
) ENGINE=InnoDB;

CREATE TABLE tickets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  raffle_id INT UNSIGNED NOT NULL,
  folio VARCHAR(20) NOT NULL,
  status ENUM('available','sold') NOT NULL DEFAULT 'available',
  user_id INT UNSIGNED NULL,
  purchase_id INT UNSIGNED NULL,
  is_winner TINYINT(1) NOT NULL DEFAULT 0,
  sold_at DATETIME NULL,
  CONSTRAINT fk_ticket_raffle FOREIGN KEY(raffle_id) REFERENCES raffles(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_ticket_user FOREIGN KEY(user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_ticket_purchase FOREIGN KEY(purchase_id) REFERENCES purchases(id) ON UPDATE CASCADE ON DELETE SET NULL,
  UNIQUE KEY uq_ticket_folio(raffle_id,folio),
  INDEX idx_ticket_user(user_id),
  INDEX idx_ticket_status(raffle_id,status)
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  raffle_id INT UNSIGNED NULL,
  channel ENUM('email','sms','whatsapp') NOT NULL,
  title VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  sent_at DATETIME NULL,
  CONSTRAINT fk_notif_user FOREIGN KEY(user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_notif_raffle FOREIGN KEY(raffle_id) REFERENCES raffles(id) ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_notif_user(user_id)
) ENGINE=InnoDB;

-- Usuario administrador de la aplicación (contraseña: Admin123!)
INSERT INTO users(username,name,email,password_hash,role,preferred_channel)
VALUES('admin','Administrador','admin@rifas.local','$2y$12$j8ypofroeuH4giAWm4XQY.XfdxA852ElEaWLgnq/UBEU8SpGYis3S','admin','email');


-- Datos demo de productos
USE sistema_rifas;
INSERT INTO products(name,description,price,image_path,active) VALUES
('iPhone 15','Smartphone de referencia para la demo.',14999.00,NULL,1),
('PlayStation 5','Consola de videojuegos con control.',12999.00,NULL,1),
('Laptop Gamer','Laptop para gaming y trabajo.',19999.00,NULL,1),
('Smart TV 55 pulgadas','Televisor 4K de 55 pulgadas.',11999.00,NULL,1),
('Audífonos inalámbricos','Audífonos Bluetooth.',5999.00,NULL,1),
('Nintendo Switch','Consola híbrida para videojuegos.',8999.00,NULL,1),
('Tablet 11 pulgadas','Tablet para entretenimiento y trabajo.',7499.00,NULL,1),
('Cámara fotográfica','Cámara digital para fotografía.',15999.00,NULL,1),
('Bocina portátil','Bocina Bluetooth de alta potencia.',4999.00,NULL,1),
('Monitor ultrawide','Monitor para productividad y gaming.',10999.00,NULL,1);


-- Después de importar este archivo, ejecuta: php database/seed.php
