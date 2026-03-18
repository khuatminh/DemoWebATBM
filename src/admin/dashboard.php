<?php
$page_title = 'Quản trị';
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: /login.php");
    exit;
}

$user_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users"))['cnt'];
$product_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM products"))['cnt'];
$order_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders"))['cnt'];
$feedback_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM feedbacks"))['cnt'];
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) as total FROM orders WHERE status='completed'"))['total'];

$users = [];
$u_result = mysqli_query($conn, "SELECT id, username, email, full_name, role, created_at FROM users ORDER BY id");
while ($u = mysqli_fetch_assoc($u_result)) { $users[] = $u; }

$recent_orders = [];
$o_result = mysqli_query($conn, "SELECT o.*, u.username, p.name as product_name FROM orders o JOIN users u ON o.user_id = u.id JOIN products p ON o.product_id = p.id ORDER BY o.created_at DESC LIMIT 10");
while ($o = mysqli_fetch_assoc($o_result)) { $recent_orders[] = $o; }

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Bảng điều khiển</h1>
    <p class="page-subtitle">Xin chào, <?php echo $_SESSION['user']['full_name']; ?></p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Người dùng</div>
        <div class="stat-value primary"><?php echo $user_count; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Sản phẩm</div>
        <div class="stat-value"><?php echo $product_count; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Đơn hàng</div>
        <div class="stat-value"><?php echo $order_count; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Đánh giá</div>
        <div class="stat-value"><?php echo $feedback_count; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Doanh thu</div>
        <div class="stat-value success"><?php echo number_format($total_revenue, 0, ',', '.'); ?>₫</div>
    </div>
</div>

<div class="two-col">
    <!-- Users -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Người dùng</h3>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>ID</th><th>Username</th><th>Họ tên</th><th>Email</th><th>Vai trò</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>#<?php echo $u['id']; ?></td>
                        <td><strong><?php echo $u['username']; ?></strong></td>
                        <td><?php echo $u['full_name']; ?></td>
                        <td><?php echo $u['email']; ?></td>
                        <td><span class="badge badge--<?php echo $u['role']; ?>"><?php echo $u['role'] === 'admin' ? 'Admin' : 'User'; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Orders -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Đơn hàng gần đây</h3>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>Mã</th><th>Khách</th><th>SP</th><th>Tổng</th><th>TT</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $o): ?>
                    <tr>
                        <td>#<?php echo $o['id']; ?></td>
                        <td><?php echo $o['username']; ?></td>
                        <td><?php echo $o['product_name']; ?></td>
                        <td><?php echo number_format($o['total_amount'], 0, ',', '.'); ?>₫</td>
                        <td><span class="badge badge--<?php echo $o['status']; ?>"><?php echo $o['status']; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
