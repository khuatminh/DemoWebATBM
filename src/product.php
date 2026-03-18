<?php
$page_title = 'Chi tiết sản phẩm';
require_once 'includes/db_connect.php';

$id = $_GET['id'] ?? '';
$product = null;
$feedbacks = [];
$error = '';

if ($id !== '') {
    // ⚠️ VULNERABLE: No quotes around $id, no type casting
    $sql = "SELECT * FROM products WHERE id = $id";

    try {
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $product = mysqli_fetch_assoc($result);
            if ($product) {
                $page_title = $product['name'];
                $fb_sql = "SELECT * FROM feedbacks WHERE product_id = " . intval($product['id']) . " ORDER BY created_at DESC";
                $fb_result = mysqli_query($conn, $fb_sql);
                while ($fb = mysqli_fetch_assoc($fb_result)) {
                    $feedbacks[] = $fb;
                }
            }
        }
    } catch (mysqli_sql_exception $e) {
        // ⚠️ VULNERABLE: Displaying SQL error message to user
        $error = $e->getMessage();
    }
}

// Sản phẩm liên quan
$related = [];
if ($product) {
    $rel_sql = "SELECT * FROM products WHERE category = '" . mysqli_real_escape_string($conn, $product['category']) . "' AND id != " . intval($product['id']) . " LIMIT 4";
    $rel_result = mysqli_query($conn, $rel_sql);
    while ($r = mysqli_fetch_assoc($rel_result)) {
        $related[] = $r;
    }
}

require_once 'includes/header.php';
?>

<div class="breadcrumb">
    <a href="/">Trang chủ</a> / <a href="/search.php">Sản phẩm</a> / <?php echo $product ? $product['name'] : 'Chi tiết'; ?>
</div>

<?php if ($error): ?>
    <div class="alert alert--error">
        Đã xảy ra lỗi: <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if ($product): ?>
    <div class="product-detail">
        <div class="product-img-wrap">
            <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>" class="product-detail-img">
        </div>
        <div>
            <div class="product-detail-category"><?php echo $product['category']; ?></div>
            <h1 class="product-detail-name"><?php echo $product['name']; ?></h1>
            <div class="product-detail-price"><?php echo number_format($product['price'], 0, ',', '.'); ?>₫</div>
            <p class="product-detail-desc"><?php echo $product['description']; ?></p>
            <div class="product-meta">
                <div class="product-meta-item">
                    <span class="product-meta-label">Kho:</span>
                    <strong><?php echo $product['stock']; ?> sản phẩm</strong>
                </div>
                <div class="product-meta-item">
                    <span class="product-meta-label">Mã SP:</span>
                    <strong>#<?php echo $product['id']; ?></strong>
                </div>
            </div>
            <div class="product-actions">
                <button class="btn btn--primary btn--lg">Thêm vào giỏ hàng</button>
                <button class="btn btn--secondary btn--lg">♡ Yêu thích</button>
            </div>
        </div>
    </div>

    <!-- Reviews -->
    <?php if (count($feedbacks) > 0): ?>
    <div style="margin-top: 48px;">
        <div class="section-header">
            <h2 class="section-title">Đánh giá sản phẩm</h2>
            <span style="font-size: 0.88rem; color: var(--text-muted);"><?php echo count($feedbacks); ?> đánh giá</span>
        </div>
        <div class="feedback-list">
            <?php foreach ($feedbacks as $fb): ?>
            <div class="feedback-item">
                <div class="feedback-header">
                    <span class="feedback-author"><?php echo htmlspecialchars($fb['user_name']); ?></span>
                    <span class="feedback-stars"><?php echo str_repeat('★', $fb['rating']) . str_repeat('☆', 5 - $fb['rating']); ?></span>
                </div>
                <p class="feedback-text"><?php echo htmlspecialchars($fb['comment']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Related Products -->
    <?php if (count($related) > 0): ?>
    <div style="margin-top: 48px;">
        <div class="section-header">
            <h2 class="section-title">Sản phẩm tương tự</h2>
        </div>
        <div class="product-grid">
            <?php foreach ($related as $r): ?>
            <a href="/product.php?id=<?php echo $r['id']; ?>" class="product-card">
                <div class="product-card-img-wrap">
                    <img src="<?php echo $r['image_url']; ?>" alt="<?php echo $r['name']; ?>" class="product-card-img">
                </div>
                <div class="product-card-body">
                    <div class="product-card-category"><?php echo $r['category']; ?></div>
                    <h3 class="product-card-name"><?php echo $r['name']; ?></h3>
                    <div class="product-card-footer">
                        <span class="product-card-price"><?php echo number_format($r['price'], 0, ',', '.'); ?>₫</span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

<?php elseif (!$error): ?>
    <div class="alert alert--warning">Không tìm thấy sản phẩm.</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
