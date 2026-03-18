<?php
$page_title = 'Đánh giá sản phẩm';
require_once 'includes/db_connect.php';

$message = '';
$msg_type = '';

// Lấy danh sách sản phẩm
$products = [];
$prod_result = mysqli_query($conn, "SELECT id, name FROM products ORDER BY name");
while ($p = mysqli_fetch_assoc($prod_result)) {
    $products[] = $p;
}

// Xử lý form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? '';
    $user_name = $_POST['user_name'] ?? '';
    $comment = $_POST['comment'] ?? '';
    $rating = $_POST['rating'] ?? '5';

    // ⚠️ VULNERABLE: String concatenation in INSERT
    $sql = "INSERT INTO feedbacks (product_id, user_name, comment, rating) VALUES ($product_id, '$user_name', '$comment', $rating)";

    try {
        mysqli_query($conn, $sql);
    } catch (mysqli_sql_exception $e) {
        // Silently ignore - always show same response (time-based blind)
    }

    // Always show success - no difference in response (time-based blind)
    $message = "Cảm ơn bạn đã gửi đánh giá! Phản hồi của bạn rất có giá trị với chúng tôi.";
    $msg_type = 'success';
}

// Lấy feedbacks gần đây
$recent_feedbacks = [];
$fb_sql = "SELECT f.*, p.name as product_name FROM feedbacks f JOIN products p ON f.product_id = p.id ORDER BY f.created_at DESC LIMIT 10";
$fb_result = mysqli_query($conn, $fb_sql);
while ($fb = mysqli_fetch_assoc($fb_result)) {
    $recent_feedbacks[] = $fb;
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Đánh giá sản phẩm</h1>
    <p class="page-subtitle">Chia sẻ trải nghiệm của bạn về sản phẩm đã mua</p>
</div>

<div class="two-col--sidebar" style="display: grid; grid-template-columns: 1fr 380px; gap: 32px;">
    <!-- Recent Reviews -->
    <div>
        <div class="section-header">
            <h2 class="section-title">Đánh giá gần đây</h2>
            <span style="font-size: 0.85rem; color: var(--text-muted);"><?php echo count($recent_feedbacks); ?> đánh giá</span>
        </div>
        <?php if (count($recent_feedbacks) > 0): ?>
        <div class="feedback-list">
            <?php foreach ($recent_feedbacks as $fb): ?>
            <div class="feedback-item">
                <div class="feedback-header">
                    <div>
                        <span class="feedback-author"><?php echo htmlspecialchars($fb['user_name']); ?></span>
                        <div class="feedback-product"><?php echo htmlspecialchars($fb['product_name']); ?></div>
                    </div>
                    <span class="feedback-stars"><?php echo str_repeat('★', min(5, max(0, intval($fb['rating'])))) . str_repeat('☆', max(0, 5 - intval($fb['rating']))); ?></span>
                </div>
                <p class="feedback-text"><?php echo htmlspecialchars($fb['comment']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <div class="alert alert--info">Chưa có đánh giá nào.</div>
        <?php endif; ?>
    </div>

    <!-- Submit Form -->
    <div>
        <?php if ($message): ?>
            <div class="alert alert--<?php echo $msg_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card" style="position: sticky; top: 80px;">
            <div class="card-header">
                <h3 class="card-title">Viết đánh giá</h3>
            </div>
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="product_id">Sản phẩm</label>
                    <select name="product_id" id="product_id" class="form-select">
                        <?php foreach ($products as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="user_name">Tên của bạn</label>
                    <input type="text" id="user_name" name="user_name" class="form-input"
                           placeholder="Nhập tên của bạn" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="comment">Nhận xét</label>
                    <textarea id="comment" name="comment" class="form-textarea"
                              placeholder="Chia sẻ trải nghiệm của bạn..." required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="rating">Đánh giá</label>
                    <select name="rating" id="rating" class="form-select">
                        <option value="5">★★★★★ Xuất sắc</option>
                        <option value="4">★★★★☆ Tốt</option>
                        <option value="3">★★★☆☆ Bình thường</option>
                        <option value="2">★★☆☆☆ Kém</option>
                        <option value="1">★☆☆☆☆ Rất kém</option>
                    </select>
                </div>

                <button type="submit" class="btn btn--primary btn--full">Gửi đánh giá</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
