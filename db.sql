CREATE DATABASE IF NOT EXISTS fundador_coffee CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fundador_coffee;
CREATE TABLE IF NOT EXISTS users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL, role ENUM('customer','admin') NOT NULL DEFAULT 'customer', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS products (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, description TEXT, price DECIMAL(10,2) NOT NULL,
 image VARCHAR(255) NOT NULL, category VARCHAR(50) DEFAULT 'coffee', stock INT UNSIGNED NOT NULL DEFAULT 0,
 is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS orders (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NULL, customer_name VARCHAR(100) NOT NULL,
 customer_email VARCHAR(190) NOT NULL, total DECIMAL(10,2) NOT NULL DEFAULT 0, status ENUM('Pending','Preparing','Ready','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS order_items (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NULL,
 product_name VARCHAR(120) NOT NULL, price DECIMAL(10,2) NOT NULL, quantity INT UNSIGNED NOT NULL, subtotal DECIMAL(10,2) NOT NULL,
 FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE, FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL
);
INSERT IGNORE INTO products (id,name,description,price,image,category,stock) VALUES
(1,'Signature Latte','Rich, smooth and perfectly balanced.',145,'assets/latte.jpg','coffee',20),
(2,'Ice Caramel Latte','A sweet and creamy caramel delight.',155,'assets/iced-latte.jpg','iced',20),
(3,'Butter Croissant','Buttery, flaky and baked to perfection.',95,'assets/croissant.jpg','pastry',20),
(4,'Blueberry Cheesecake','Creamy cheesecake with a fruity blueberry topping.',165,'assets/cheesecake.jpg','dessert',20);
-- Demo admin. Change this password after first login. Password: admin123
INSERT IGNORE INTO users(name,email,password,role) VALUES('Administrator','admin@fundador.local','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llCz7g9fQ8fK9zYwJjV9K','admin');
