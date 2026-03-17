<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../admin/auth.php';
require_once __DIR__ . '/../app/layout.php';

requireStudent();

$products = db()->query('SELECT id, name, category, price, image_path FROM products WHERE is_active = 1 ORDER BY category, name')->fetchAll();

renderHeader('Browse Items', 'student');
?>
<h1 class="dash-title">Browse Items</h1>
<p class="dash-subtitle">Shop available RGO items and add them to your cart.</p>

<div class="dash-panel">
    <div class="grid">
    <?php foreach ($products as $product): ?>
        <?php $imagePath = (string) ($product['image_path'] ?: '/CAPSTONE/img/products/placeholder.svg'); ?>
        <article class="card">
            <img
                class="product-image"
                src="<?php echo e($imagePath); ?>"
                alt="<?php echo e($product['name']); ?>"
                onerror="this.src='/CAPSTONE/img/products/placeholder.svg';"
            >
            <h3><?php echo e($product['name']); ?></h3>
            <p class="small"><?php echo e($product['category']); ?></p>
            <p class="price"><?php echo formatPeso((float) $product['price']); ?></p>
            <form action="/CAPSTONE/actions/add_to_cart.php" method="post">
                <?php echo csrfField(); ?>
                <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                <label class="small" for="qty_<?php echo (int) $product['id']; ?>">Quantity</label>
                <input id="qty_<?php echo (int) $product['id']; ?>" type="number" name="quantity" min="1" max="99" value="1" required>
                <button type="submit" style="margin-top:0.6rem;">Add to Cart</button>
            </form>
        </article>
    <?php endforeach; ?>
    </div>
</div>
<?php renderFooter('student');
