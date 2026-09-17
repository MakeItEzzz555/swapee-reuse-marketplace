-- Swapee schema and seed data
-- Run: mysql -u root -p < swapee.sql

CREATE DATABASE IF NOT EXISTS swapee CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE swapee;

DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    bio VARCHAR(255),
    avatar_url VARCHAR(255),
    impact_co2 DECIMAL(10,2) NOT NULL DEFAULT 0,
    impact_waste DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    category VARCHAR(80),
    item_condition VARCHAR(50),
    listing_type ENUM('swap', 'donate', 'sell') NOT NULL DEFAULT 'swap',
    price DECIMAL(10,2) DEFAULT 0,
    status ENUM('available', 'reserved', 'completed') NOT NULL DEFAULT 'available',
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_items_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    owner_id INT NOT NULL,
    actor_id INT NOT NULL,
    action ENUM('swap', 'donate', 'sell') NOT NULL,
    status ENUM('pending', 'completed') NOT NULL DEFAULT 'completed',
    impact_co2 DECIMAL(10,2) NOT NULL DEFAULT 0,
    impact_waste DECIMAL(10,2) NOT NULL DEFAULT 0,
    notes VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tx_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    CONSTRAINT fk_tx_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_tx_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Optional demo users (password: password)
INSERT INTO users (name, email, password, role, bio) VALUES
('Admin', 'admin@swapee.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Runs the marketplace'),
('Student One', 'student1@swapee.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'Excited to swap gear');

INSERT INTO items (user_id, title, description, category, item_condition, listing_type, price, status, image_url)
VALUES
(2, 'Circuit Theory Textbook', 'Clean, highlighted lightly. Ready for the next EE101 student.', 'Books', 'Good', 'swap', 0, 'available', 'https://images.unsplash.com/photo-1526304640581-d334cdbbf45e'),
(2, 'Arduino Starter Kit', 'All components included, barely used.', 'Electronics', 'Like new', 'sell', 35, 'available', 'https://images.unsplash.com/photo-1518770660439-4636190af475');
