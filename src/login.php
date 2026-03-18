<?php
$page_title = 'Đăng nhập';
require_once 'includes/db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // ⚠️ VULNERABLE: String concatenation - NO prepared statements
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = MD5('$password')";

    try {
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $_SESSION['user'] = $user;
            header("Location: /");
            exit;
        } else {
            $error = "Tên đăng nhập hoặc mật khẩu không chính xác.";
        }
    } catch (mysqli_sql_exception $e) {
        $error = "Tên đăng nhập hoặc mật khẩu không chính xác.";
    }
}

require_once 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo">VulnShop</div>
        <p class="auth-subtitle">Đăng nhập vào tài khoản của bạn</p>

        <?php if ($error): ?>
            <div class="alert alert--error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="username">Tên đăng nhập</label>
                <input type="text" id="username" name="username" class="form-input"
                       placeholder="Nhập tên đăng nhập"
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                       autocomplete="username">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" class="form-input"
                       placeholder="Nhập mật khẩu"
                       autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn--primary btn--full btn--lg">Đăng nhập</button>
        </form>

        <p class="auth-divider">hoặc</p>
        <p style="text-align: center; font-size: 0.85rem; color: var(--text-muted);">
            Chưa có tài khoản? <a href="#" style="color: var(--primary); font-weight: 500;">Đăng ký ngay</a>
        </p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
