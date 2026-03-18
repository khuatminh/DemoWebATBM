<?php
$page_title = 'Hồ sơ cá nhân';
require_once 'includes/db_connect.php';

$uid = $_GET['uid'] ?? '';
$user = null;
$orders = [];

if ($uid !== '') {
    // ⚠️ VULNERABLE: No parameterization, no type casting
    $sql = "SELECT id, username, email, full_name, role, created_at FROM users WHERE id = $uid";

    try {
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $page_title = $user['full_name'];

            $order_sql = "SELECT o.*, p.name as product_name FROM orders o 
                          JOIN products p ON o.product_id = p.id 
                          WHERE o.user_id = " . intval($user['id']) . " ORDER BY o.created_at DESC";
            $order_result = mysqli_query($conn, $order_sql);
            if ($order_result) {
                while ($row = mysqli_fetch_assoc($order_result)) {
                    $orders[] = $row;
                }
            }
        }
    } catch (mysqli_sql_exception $e) {
        // Silently fail - important for boolean blind (shows "not found" on error)
        $user = null;
    }
}

require_once 'includes/header.php';
?>

<div class="breadcrumb">
    <a href="/">Trang chủ</a> / Tài khoản
</div>

<?php if ($user): ?>
    <div class="card" style="max-width: 700px;">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
            <div>
                <div class="profile-name"><?php echo $user['full_name']; ?></div>
                <div class="profile-role">@<?php echo $user['username']; ?> · Thành viên từ <?php echo date('m/Y', strtotime($user['created_at'])); ?></div>
            </div>
        </div>

        <div class="profile-info">
            <span class="profile-label">Email</span>
            <span class="profile-value"><?php echo $user['email']; ?></span>

            <span class="profile-label">Vai trò</span>
            <span class="profile-value">
                <span class="badge badge--<?php echo $user['role']; ?>">
                    <?php echo $user['role'] === 'admin' ? 'Quản trị viên' : 'Khách hàng'; ?>
                </span>
            </span>

            <span class="profile-label">Ngày tham gia</span>
            <span class="profile-value"><?php echo $user['created_at']; ?></span>
        </div>
    </div>

    <?php if (count($orders) > 0): ?>
    <div class="card" style="max-width: 700px; margin-top: 20px;">
        <div class="card-header">
            <h3 class="card-title">Lịch sử đơn hàng</h3>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Đơn hàng</th>
                        <th>Sản phẩm</th>
                        <th>Số lượng</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td><?php echo $order['product_name']; ?></td>
                        <td><?php echo $order['quantity']; ?></td>
                        <td><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>₫</td>
                        <td>
                            <span class="badge badge--<?php echo $order['status']; ?>">
                                <?php
                                $status_labels = ['pending' => 'Đang xử lý', 'completed' => 'Hoàn thành', 'cancelled' => 'Đã hủy'];
                                echo $status_labels[$order['status']] ?? $order['status'];
                                ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

<?php else: ?>
    <div class="alert alert--warning">Không tìm thấy thông tin tài khoản.</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
