-- =========================================
-- BASE DE DATOS PARA SISTEMA DE COMANDAS
-- Compatible con login.php proporcionado
-- =========================================

CREATE DATABASE IF NOT EXISTS comandas
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_general_ci;

USE comandas;

-- ==========================
-- TABLA DE USUARIOS
-- ==========================
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NULL,
  role ENUM('waiter','bar','kitchen','admin') NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  remember_token VARCHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuarios de prueba (contraseña: password)
INSERT INTO users (name, email, role, password_hash) VALUES
('Garzón', 'ana@example.com', 'waiter', NULL),
('Bar', 'barra@example.com', 'bar', NULL),
('Cocina', 'cocina@example.com', 'kitchen', NULL),
('Admin', 'admin@example.com', 'admin', NULL);

-- ==========================
-- TABLA DE MESAS
-- ==========================
DROP TABLE IF EXISTS restaurant_tables;
CREATE TABLE restaurant_tables (
  id INT AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(20) NOT NULL,
  status ENUM('free','in_service','closed') NOT NULL DEFAULT 'free',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO restaurant_tables (label,status) VALUES
('Mesa 1','free'),('Mesa 2','free'),('Mesa 3','free'),
('Mesa 4','free'),('Mesa 5','free'),('Mesa 6','free'),
('Mesa 7','free'),('Mesa 8','free'),('Mesa 9','free'),
('Mesa 10','free');

-- ==========================
-- TABLA DE PRODUCTOS
-- ==========================
DROP TABLE IF EXISTS products;
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  area ENUM('bar','kitchen') NOT NULL,
  base_price DECIMAL(10,2) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO products (name,area,base_price) VALUES
('Cerveza Lager 500cc','bar',3500.00),
('Cerveza IPA 500cc','bar',4200.00),
('Canasta Papas Fritas con Queso','kitchen',6900.00),
('Hamburguesa Clásica','kitchen',7800.00);

-- ==========================
-- TABLA DE PEDIDOS
-- ==========================
DROP TABLE IF EXISTS orders;
CREATE TABLE orders (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  table_id INT NOT NULL,
  waiter_id INT NOT NULL,
  status ENUM(
    'awaiting_confirmation',
    'confirmed_kitchen',
    'confirmed_bar',
    'ready_for_pickup',
    'picked_up',
    'served',
    'consuming',
    'closed',
    'canceled'
  ) NOT NULL DEFAULT 'awaiting_confirmation',
  editable_until DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_table FOREIGN KEY (table_id) REFERENCES restaurant_tables(id),
  CONSTRAINT fk_orders_waiter FOREIGN KEY (waiter_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================
-- TABLA DE ÍTEMS DE PEDIDO
-- ==========================
DROP TABLE IF EXISTS order_items;
CREATE TABLE order_items (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT NOT NULL,
  product_id INT NOT NULL,
  area ENUM('bar','kitchen') NOT NULL,
  qty INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL,
  notes VARCHAR(500) NULL,
  status ENUM(
    'awaiting_confirmation',
    'confirmed',
    'in_progress',
    'ready',
    'picked_up',
    'served',
    'canceled'
  ) NOT NULL DEFAULT 'awaiting_confirmation',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================
-- TABLA DE MODIFICADORES
-- ==========================
DROP TABLE IF EXISTS item_modifiers;
CREATE TABLE item_modifiers (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_item_id BIGINT NOT NULL,
  description VARCHAR(200) NOT NULL,
  price_delta DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_mod_item FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================
-- TABLA DE HISTORIAL DE ESTADOS
-- ==========================
DROP TABLE IF EXISTS order_status_history;
CREATE TABLE order_status_history (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT NOT NULL,
  status VARCHAR(50) NOT NULL,
  set_by_user_id INT NULL,
  set_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_hist_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_hist_user FOREIGN KEY (set_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================
-- TABLA DE BOLETAS / RECIBOS
-- ==========================
DROP TABLE IF EXISTS receipts;
CREATE TABLE receipts (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT NOT NULL UNIQUE,
  subtotal DECIMAL(10,2) NOT NULL,
  total_modifiers DECIMAL(10,2) NOT NULL,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(10,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_receipt_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================
-- TRIGGERS DE SOPORTE
-- ==========================
DELIMITER $$

DROP TRIGGER IF EXISTS trg_orders_set_editable_until $$
CREATE TRIGGER trg_orders_set_editable_until
BEFORE INSERT ON orders
FOR EACH ROW
BEGIN
  SET NEW.editable_until = DATE_ADD(COALESCE(NEW.created_at, NOW()), INTERVAL 3 MINUTE);
END $$

DROP TRIGGER IF EXISTS trg_items_defaults $$
CREATE TRIGGER trg_items_defaults
BEFORE INSERT ON order_items
FOR EACH ROW
BEGIN
  DECLARE p_area ENUM('bar','kitchen');
  DECLARE p_price DECIMAL(10,2);
  SELECT area, base_price INTO p_area, p_price FROM products WHERE id = NEW.product_id;
  IF NEW.area IS NULL THEN SET NEW.area = p_area; END IF;
  IF NEW.unit_price IS NULL THEN SET NEW.unit_price = p_price; END IF;
END $$

DELIMITER ;

-- ==========================
-- ÍNDICES RELEVANTES
-- ==========================
CREATE INDEX idx_orders_table_status ON orders (table_id, status);
CREATE INDEX idx_items_area_status ON order_items (area, status);
CREATE INDEX idx_items_order ON order_items (order_id);


-- =========================================
-- BASE DE DATOS PARA SISTEMA DE COMANDAS
-- Compatible con login.php proporcionado
-- =========================================

CREATE DATABASE IF NOT EXISTS comandas
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_general_ci;

USE comandas;

-- ==========================
-- TABLA DE USUARIOS
-- ==========================
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NULL,
  role ENUM('waiter','bar','kitchen','admin') NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  remember_token VARCHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuarios de prueba (contraseña: password)
INSERT INTO users (name, email, role, password_hash) VALUES
('Garzón', 'ana@example.com', 'waiter', NULL),
('Bar', 'barra@example.com', 'bar', NULL),
('Cocina', 'cocina@example.com', 'kitchen', NULL),
('Admin', 'admin@example.com', 'admin', NULL);

-- ==========================
-- TABLA DE MESAS
-- ==========================
DROP TABLE IF EXISTS restaurant_tables;
CREATE TABLE restaurant_tables (
  id INT AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(20) NOT NULL,
  status ENUM('free','in_service','closed') NOT NULL DEFAULT 'free',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO restaurant_tables (label,status) VALUES
('Mesa 1','free'),('Mesa 2','free'),('Mesa 3','free'),
('Mesa 4','free'),('Mesa 5','free'),('Mesa 6','free'),
('Mesa 7','free'),('Mesa 8','free'),('Mesa 9','free'),
('Mesa 10','free');

-- ==========================
-- TABLA DE PRODUCTOS
-- ==========================
DROP TABLE IF EXISTS products;
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  area ENUM('bar','kitchen') NOT NULL,
  base_price DECIMAL(10,2) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO products (name,area,base_price) VALUES
('Cerveza Lager 500cc','bar',3500.00),
('Cerveza IPA 500cc','bar',4200.00),
('Canasta Papas Fritas con Queso','kitchen',6900.00),
('Hamburguesa Clásica','kitchen',7800.00);

-- ==========================
-- TABLA DE PEDIDOS
-- ==========================
DROP TABLE IF EXISTS orders;
CREATE TABLE orders (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  table_id INT NOT NULL,
  waiter_id INT NOT NULL,
  status ENUM(
    'awaiting_confirmation',
    'confirmed_kitchen',
    'confirmed_bar',
    'ready_for_pickup',
    'picked_up',
    'served',
    'consuming',
    'closed',
    'canceled'
  ) NOT NULL DEFAULT 'awaiting_confirmation',
  editable_until DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_table FOREIGN KEY (table_id) REFERENCES restaurant_tables(id),
  CONSTRAINT fk_orders_waiter FOREIGN KEY (waiter_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================
-- TABLA DE ÍTEMS DE PEDIDO
-- ==========================
DROP TABLE IF EXISTS order_items;
CREATE TABLE order_items (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT NOT NULL,
  product_id INT NOT NULL,
  area ENUM('bar','kitchen') NOT NULL,
  qty INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL,
  notes VARCHAR(500) NULL,
  status ENUM(
    'awaiting_confirmation',
    'confirmed',
    'in_progress',
    'ready',
    'picked_up',
    'served',
    'canceled'
  ) NOT NULL DEFAULT 'awaiting_confirmation',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================
-- TABLA DE MODIFICADORES
-- ==========================
DROP TABLE IF EXISTS item_modifiers;
CREATE TABLE item_modifiers (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_item_id BIGINT NOT NULL,
  description VARCHAR(200) NOT NULL,
  price_delta DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_mod_item FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================
-- TABLA DE HISTORIAL DE ESTADOS
-- ==========================
DROP TABLE IF EXISTS order_status_history;
CREATE TABLE order_status_history (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT NOT NULL,
  status VARCHAR(50) NOT NULL,
  set_by_user_id INT NULL,
  set_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_hist_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_hist_user FOREIGN KEY (set_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================
-- TABLA DE BOLETAS / RECIBOS
-- ==========================
DROP TABLE IF EXISTS receipts;
CREATE TABLE receipts (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT NOT NULL UNIQUE,
  subtotal DECIMAL(10,2) NOT NULL,
  total_modifiers DECIMAL(10,2) NOT NULL,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(10,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_receipt_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================
-- TRIGGERS DE SOPORTE
-- ==========================
DELIMITER $$

DROP TRIGGER IF EXISTS trg_orders_set_editable_until $$
CREATE TRIGGER trg_orders_set_editable_until
BEFORE INSERT ON orders
FOR EACH ROW
BEGIN
  SET NEW.editable_until = DATE_ADD(COALESCE(NEW.created_at, NOW()), INTERVAL 3 MINUTE);
END $$

DROP TRIGGER IF EXISTS trg_items_defaults $$
CREATE TRIGGER trg_items_defaults
BEFORE INSERT ON order_items
FOR EACH ROW
BEGIN
  DECLARE p_area ENUM('bar','kitchen');
  DECLARE p_price DECIMAL(10,2);
  SELECT area, base_price INTO p_area, p_price FROM products WHERE id = NEW.product_id;
  IF NEW.area IS NULL THEN SET NEW.area = p_area; END IF;
  IF NEW.unit_price IS NULL THEN SET NEW.unit_price = p_price; END IF;
END $$

DELIMITER ;

-- ==========================
-- ÍNDICES RELEVANTES
-- ==========================
CREATE INDEX idx_orders_table_status ON orders (table_id, status);
CREATE INDEX idx_items_area_status ON order_items (area, status);
CREATE INDEX idx_items_order ON order_items (order_id);




-- =========================================
-- VISTAS: COLAS DE PREPARACIÓN
-- =========================================

DROP VIEW IF EXISTS kitchen_queue;
CREATE VIEW kitchen_queue AS
SELECT
  oi.id AS order_item_id,
  oi.order_id,
  o.table_id,
  rt.label AS table_label,
  oi.qty,
  p.name AS product,
  oi.notes,
  oi.status,
  o.created_at,
  o.editable_until
FROM order_items oi
JOIN orders o            ON o.id = oi.order_id
JOIN products p          ON p.id = oi.product_id
JOIN restaurant_tables rt ON rt.id = o.table_id
WHERE oi.area = 'kitchen'
  AND oi.status IN ('awaiting_confirmation','confirmed','in_progress','ready');

DROP VIEW IF EXISTS bar_queue;
CREATE VIEW bar_queue AS
SELECT
  oi.id AS order_item_id,
  oi.order_id,
  o.table_id,
  rt.label AS table_label,
  oi.qty,
  p.name AS product,
  oi.notes,
  oi.status,
  o.created_at,
  o.editable_until
FROM order_items oi
JOIN orders o            ON o.id = oi.order_id
JOIN products p          ON p.id = oi.product_id
JOIN restaurant_tables rt ON rt.id = o.table_id
WHERE oi.area = 'bar'
  AND oi.status IN ('awaiting_confirmation','confirmed','in_progress','ready');