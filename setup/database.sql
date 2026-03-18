-- ============================================
-- VulnShop Database Setup
-- CEH Module 15 - SQL Injection Demo
-- ============================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS vulnshop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vulnshop;

-- Bảng users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    full_name VARCHAR(100),
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng products
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    category VARCHAR(50),
    stock INT DEFAULT 0,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng orders
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    quantity INT DEFAULT 1,
    total_amount DECIMAL(10,2),
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Bảng feedbacks
CREATE TABLE feedbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    user_name VARCHAR(100),
    comment TEXT,
    rating INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Bảng sensitive_data (dữ liệu nhạy cảm - demo trích xuất)
CREATE TABLE sensitive_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_number VARCHAR(20),
    card_holder VARCHAR(100),
    expiry_date VARCHAR(10),
    cvv VARCHAR(5)
);

-- ============================================
-- INSERT DỮ LIỆU MẪU
-- ============================================

-- Users (password dùng MD5 - KHÔNG AN TOÀN, cố ý cho demo)
INSERT INTO users (username, password, email, full_name, role) VALUES
('admin', MD5('admin123'), 'admin@vulnshop.com', 'Administrator', 'admin'),
('nguyenvana', MD5('password123'), 'vana@gmail.com', 'Nguyễn Văn A', 'user'),
('tranthib', MD5('mypass456'), 'thib@gmail.com', 'Trần Thị B', 'user'),
('levanc', MD5('secure789'), 'levanc@gmail.com', 'Lê Văn C', 'user'),
('phamthid', MD5('pass2024'), 'phamthid@gmail.com', 'Phạm Thị D', 'user');

-- Products
INSERT INTO products (name, description, price, category, stock, image_url) VALUES
('iPhone 15 Pro Max', 'Smartphone cao cấp Apple, chip A17 Pro, camera 48MP', 29990000, 'Điện thoại', 50, 'https://placehold.co/400x300/1a1a2e/e94560?text=iPhone+15'),
('Samsung Galaxy S24 Ultra', 'Flagship Samsung, Galaxy AI, S-Pen tích hợp', 27990000, 'Điện thoại', 35, 'https://placehold.co/400x300/16213e/0f3460?text=Galaxy+S24'),
('MacBook Pro M3', 'Laptop Apple, chip M3 Pro, màn hình Liquid Retina XDR', 42990000, 'Laptop', 20, 'https://placehold.co/400x300/1a1a2e/e94560?text=MacBook+M3'),
('Dell XPS 15', 'Laptop Dell cao cấp, Intel Core i9, OLED 3.5K', 38990000, 'Laptop', 15, 'https://placehold.co/400x300/0f3460/e94560?text=Dell+XPS'),
('AirPods Pro 2', 'Tai nghe True Wireless Apple, chống ồn chủ động', 5990000, 'Phụ kiện', 100, 'https://placehold.co/400x300/16213e/00d2ff?text=AirPods'),
('Sony WH-1000XM5', 'Tai nghe chụp tai chống ồn hàng đầu Sony', 7490000, 'Phụ kiện', 45, 'https://placehold.co/400x300/1a1a2e/00d2ff?text=Sony+XM5'),
('iPad Air M2', 'Máy tính bảng Apple, chip M2, màn hình 11 inch', 16990000, 'Tablet', 30, 'https://placehold.co/400x300/0f3460/e94560?text=iPad+Air'),
('Samsung Galaxy Tab S9', 'Tablet Samsung cao cấp, AMOLED 120Hz', 18990000, 'Tablet', 25, 'https://placehold.co/400x300/16213e/00d2ff?text=Tab+S9'),
('Apple Watch Ultra 2', 'Đồng hồ thông minh Apple, titanium, GPS', 21990000, 'Đồng hồ', 40, 'https://placehold.co/400x300/1a1a2e/e94560?text=Watch+Ultra'),
('Logitech MX Master 3S', 'Chuột không dây cao cấp cho dân văn phòng', 2490000, 'Phụ kiện', 80, 'https://placehold.co/400x300/0f3460/00d2ff?text=MX+Master');

-- Sensitive Data (dữ liệu giả - demo mục đích giáo dục)
INSERT INTO sensitive_data (card_number, card_holder, expiry_date, cvv) VALUES
('4111-1111-1111-1111', 'NGUYEN VAN A', '12/25', '123'),
('5500-0000-0000-0004', 'TRAN THI B', '06/26', '456'),
('3400-0000-0000-009', 'LE VAN C', '09/27', '789'),
('6011-0000-0000-0004', 'PHAM THI D', '03/28', '012');

-- Orders
INSERT INTO orders (user_id, product_id, quantity, total_amount, status) VALUES
(2, 1, 1, 29990000, 'completed'),
(2, 5, 2, 11980000, 'completed'),
(3, 3, 1, 42990000, 'pending'),
(4, 7, 1, 16990000, 'completed'),
(5, 2, 1, 27990000, 'cancelled');

-- Feedbacks
INSERT INTO feedbacks (product_id, user_name, comment, rating) VALUES
(1, 'Nguyễn Văn A', 'Sản phẩm rất tốt, camera chụp đẹp!', 5),
(1, 'Trần Thị B', 'Pin dùng khá lâu, hài lòng', 4),
(3, 'Lê Văn C', 'MacBook chạy rất mượt, xứng đáng giá tiền', 5),
(2, 'Phạm Thị D', 'Galaxy AI rất hay, nhận diện tốt', 4),
(5, 'Nguyễn Văn A', 'Chống ồn tuyệt vời', 5);
