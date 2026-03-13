<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/layout.php';

$products = db()->query('SELECT id, name, category, price, image_path FROM products WHERE is_active = 1 ORDER BY category, name')->fetchAll();

renderHeader(APP_NAME);
?>
<section class="hero hero-home">
    <div class="hero-home-inner">
        <h1>BatState-U Resource Generation Office</h1>
        <div class="hero-actions">
            <a class="btn" href="/CAPSTONE/admin/login.php">Login</a>
            <a class="btn ghost" href="#catalog">Browse Items</a>
        </div>
    </div>
</section>

<section class="section catalog-section" id="catalog">
    <div class="section-heading">
        <div>
            <h2>Available RGO Items</h2>
        </div>
        <p class="small">Select quantity and add items directly to your cart.</p>
    </div>

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
</section>

<section class="section" id="about-rgo">
    <div class="section-heading">
        <div>
            <h2>About RGO</h2>
        </div>
        <p class="small">Resource Generation Office information for students, staff, and campus visitors.</p>
    </div>
    <p>
        The BatState-U Resource Generation Office (RGO) supports campus services through managed merchandise and order fulfillment.
        This portal helps students browse official items, submit orders, and monitor progress in one place.
    </p>
</section>

<section class="section" id="contact-rgo">
    <div class="section-heading">
        <div>
            <h2>Contact</h2>
        </div>
        <p class="small">Reach the office for order concerns, pickup concerns, and inventory inquiries.</p>
    </div>
    <p><strong>Office:</strong> BatState-U Resource Generation Office</p>
    <p><strong>Email:</strong> rgo@g.batstate-u.edu.ph</p>
    <p><strong>Phone:</strong> (043) 123-4567</p>
    <p><strong>Hours:</strong> Monday to Friday, 8:00 AM to 5:00 PM</p>
</section>
<?php renderFooter();
