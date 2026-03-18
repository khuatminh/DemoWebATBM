<?php if(!isset($page_title)) $page_title = 'VulnShop'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - VulnShop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="/" class="nav-logo">
                <span>Vuln</span><span class="logo-accent">Shop</span>
            </a>
            <div class="nav-links">
                <a href="/" class="nav-link">Trang chủ</a>
                <a href="/search.php" class="nav-link">Sản phẩm</a>
                <a href="/feedback.php" class="nav-link">Đánh giá</a>
                <?php if(isset($_SESSION['user'])): ?>
                    <a href="/profile.php?uid=<?php echo $_SESSION['user']['id']; ?>" class="nav-link">Tài khoản</a>
                    <?php if($_SESSION['user']['role'] === 'admin'): ?>
                        <a href="/admin/dashboard.php" class="nav-link">Quản trị</a>
                    <?php endif; ?>
                    <div class="nav-user">
                        <div class="nav-avatar"><?php echo strtoupper(substr($_SESSION['user']['username'], 0, 1)); ?></div>
                        <a href="/logout.php" class="btn btn--sm btn--secondary">Đăng xuất</a>
                    </div>
                <?php else: ?>
                    <a href="/login.php" class="btn btn--sm btn--primary">Đăng nhập</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <main class="main-content">
