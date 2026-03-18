<?php
$page_title = 'Tìm kiếm sản phẩm';
require_once 'includes/db_connect.php';
require_once 'includes/header.php';

$keyword = $_GET['q'] ?? '';
$results = [];
$error = '';
$num_results = 0;
?>

<div class="page-header">
    <h1 class="page-title">Sản phẩm</h1>
    <p class="page-subtitle">Tìm kiếm và khám phá các sản phẩm công nghệ mới nhất</p>
</div>

<div class="search-section">
    <form method="GET" action="" class="search-bar">
        <input type="text" name="q" class="form-input"
               placeholder="Tìm kiếm sản phẩm..."
               value="<?php echo htmlspecialchars($keyword); ?>">
        <button type="submit" class="btn btn--primary">Tìm kiếm</button>
    </form>
</div>

<?php if ($keyword !== ''):
    // ⚠️ VULNERABLE: String concatenation - UNION injection possible
    $sql = "SELECT id, name, price, category FROM products WHERE name LIKE '%$keyword%'";

    try {
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $num_results = mysqli_num_rows($result);
            while ($row = mysqli_fetch_assoc($result)) {
                $results[] = $row;
            }
        }
    } catch (mysqli_sql_exception $e) {
        $error = $e->getMessage();
    }
?>

    <?php if ($error): ?>
        <div class="alert alert--error">Đã xảy ra lỗi khi tìm kiếm. Vui lòng thử lại.</div>
    <?php elseif (count($results) > 0): ?>
        <p class="search-results-info">Tìm thấy <strong><?php echo $num_results; ?></strong> kết quả cho "<strong><?php echo htmlspecialchars($keyword); ?></strong>"</p>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên sản phẩm</th>
                        <th>Giá</th>
                        <th>Danh mục</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><a href="/product.php?id=<?php echo $row['id']; ?>" style="color: var(--primary); font-weight: 500;"><?php echo $row['name']; ?></a></td>
                        <td><?php echo is_numeric($row['price']) ? number_format($row['price'], 0, ',', '.') . '₫' : $row['price']; ?></td>
                        <td><?php echo $row['category']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert--warning">Không tìm thấy sản phẩm nào cho "<strong><?php echo htmlspecialchars($keyword); ?></strong>"</div>
    <?php endif; ?>

<?php else: ?>
    <?php
    // Hiển thị tất cả sản phẩm nếu chưa search
    $all = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
    $all_products = [];
    while ($r = mysqli_fetch_assoc($all)) { $all_products[] = $r; }
    ?>
    <div class="product-grid">
        <?php foreach ($all_products as $p): ?>
        <a href="/product.php?id=<?php echo $p['id']; ?>" class="product-card">
            <div class="product-card-img-wrap">
                <img src="<?php echo $p['image_url']; ?>" alt="<?php echo $p['name']; ?>" class="product-card-img">
            </div>
            <div class="product-card-body">
                <div class="product-card-category"><?php echo $p['category']; ?></div>
                <h3 class="product-card-name"><?php echo $p['name']; ?></h3>
                <div class="product-card-footer">
                    <span class="product-card-price"><?php echo number_format($p['price'], 0, ',', '.'); ?>₫</span>
                    <span class="product-card-stock">Còn hàng</span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
