<?php
$page_title = 'Cửa hàng công nghệ';
require_once 'includes/db_connect.php';
require_once 'includes/header.php';

// Lấy sản phẩm
$products = [];
$sql = "SELECT * FROM products ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

// Lấy danh mục unique
$categories = [];
foreach ($products as $p) {
    if (!in_array($p['category'], $categories)) {
        $categories[] = $p['category'];
    }
}
?>

<!-- Hero Banner -->
<div class="hero-banner">
    <span class="hero-badge">🔥 Khuyến mãi đặc biệt</span>
    <h1 class="hero-title">Công nghệ đỉnh cao,<br>giá không thể tốt hơn.</h1>
    <p class="hero-subtitle">
        Mua sắm các sản phẩm công nghệ chính hãng với giá ưu đãi tốt nhất thị trường.
    </p>
    <div class="hero-actions">
        <a href="/search.php" class="btn btn--primary btn--lg">Khám phá ngay</a>
        <a href="#products" class="btn btn--lg" style="background: rgba(255,255,255,0.12); color: white;">Xem sản phẩm ↓</a>
    </div>
</div>

<!-- Categories -->
<div class="category-pills">
    <span class="category-pill active">Tất cả</span>
    <?php foreach ($categories as $cat): ?>
        <a href="/search.php?q=<?php echo urlencode($cat); ?>" class="category-pill"><?php echo $cat; ?></a>
    <?php endforeach; ?>
</div>

<!-- Product Grid -->
<div id="products">
    <div class="section-header">
        <h2 class="section-title">Sản phẩm nổi bật</h2>
        <a href="/search.php" class="section-link">Xem tất cả →</a>
    </div>
    <div class="product-grid">
        <?php foreach ($products as $p): ?>
        <a href="/product.php?id=<?php echo $p['id']; ?>" class="product-card">
            <div class="product-card-img-wrap">
                <img src="<?php echo $p['image_url']; ?>" alt="<?php echo $p['name']; ?>" class="product-card-img">
            </div>
            <div class="product-card-body">
                <div class="product-card-category"><?php echo $p['category']; ?></div>
                <h3 class="product-card-name"><?php echo $p['name']; ?></h3>
                <div class="product-card-footer">
                    <span class="product-card-price"><?php echo number_format($p['price'], 0, ',', '.'); ?>₫</span>
                    <span class="product-card-stock <?php echo $p['stock'] <= 0 ? 'out' : ''; ?>">
                        <?php echo $p['stock'] > 0 ? 'Còn hàng' : 'Hết hàng'; ?>
                    </span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
