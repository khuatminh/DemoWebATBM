# 🛡️ VulnShop - SQL Injection Demo Lab

> **CEH Module 15 - SQL Injection**  
> Ứng dụng web chứa lỗ hổng SQL Injection có chủ đích, phục vụ học tập.

## ⚠️ CẢNH BÁO

Ứng dụng này chứa **lỗ hổng bảo mật có chủ đích**. Chỉ sử dụng trong:
- Môi trường lab cá nhân
- Máy ảo / Docker container cô lập
- **KHÔNG BAO GIỜ** triển khai trên server production

---

## 🚀 Cài đặt & Chạy

### Yêu cầu
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) đã cài đặt

### Khởi chạy

```bash
cd vulnshop
docker compose up -d --build
```

### Truy cập

| Service        | URL                   | Thông tin       |
| -------------- | --------------------- | --------------- |
| **VulnShop**   | http://localhost:8080 | Ứng dụng chính  |
| **phpMyAdmin** | http://localhost:8081 | root / rootpass |

### Dừng lab

```bash
docker compose down
```

### Reset dữ liệu (xóa sạch)

```bash
docker compose down -v
docker compose up -d --build
```

---

## 📋 Tài khoản demo

| Username   | Password    | Role  |
| ---------- | ----------- | ----- |
| admin      | admin123    | admin |
| nguyenvana | password123 | user  |
| tranthib   | mypass456   | user  |
| levanc     | secure789   | user  |
| phamthid   | pass2024    | user  |

---

## 🎯 Danh sách lỗ hổng

### 1. Authentication Bypass (`/login.php`)
- **Loại:** In-band SQLi
- **Payload:** `admin' -- ` (username)
- **Kết quả:** Đăng nhập admin không cần mật khẩu

### 2. UNION-based SQLi (`/search.php`)
- **Loại:** In-band SQLi  
- **Payload:** `' UNION SELECT 1,username,password,email FROM users-- `
- **Kết quả:** Trích xuất toàn bộ dữ liệu database

### 3. Error-based SQLi (`/product.php?id=1`)
- **Loại:** In-band SQLi
- **Payload:** `1 AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT database()),0x7e))`
- **Kết quả:** Thông tin DB lộ qua error message

### 4. Boolean-based Blind SQLi (`/profile.php?uid=1`)
- **Loại:** Blind SQLi
- **Payload:** `1 AND SUBSTRING(database(),1,1)='v'`
- **Kết quả:** Xác định thông tin từng ký tự qua TRUE/FALSE

### 5. Time-based Blind SQLi (`/feedback.php`)
- **Loại:** Blind SQLi
- **Payload:** `1) AND IF(1=1,SLEEP(3),0)-- ` (trường rating)
- **Kết quả:** Xác định thông tin qua thời gian delay

---

## 🗂️ Cấu trúc dự án

```
vulnshop/
├── docker-compose.yml        # Docker setup
├── php.ini                   # PHP config (hiển thị lỗi)
├── setup/
│   └── database.sql          # Schema + dữ liệu mẫu
└── src/
    ├── index.php             # Trang chủ
    ├── login.php             # Auth Bypass
    ├── search.php            # UNION-based SQLi
    ├── product.php           # Error-based SQLi
    ├── profile.php           # Boolean Blind SQLi
    ├── feedback.php          # Time-based Blind SQLi
    ├── logout.php            # Đăng xuất
    ├── admin/
    │   └── dashboard.php     # Admin panel
    ├── includes/
    │   ├── db_connect.php    # Kết nối DB
    │   ├── header.php        # Header chung
    │   └── footer.php        # Footer chung
    └── css/
        └── style.css         # Giao diện
```
