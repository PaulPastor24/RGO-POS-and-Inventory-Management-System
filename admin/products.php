<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../app/layout.php';

requireAdmin();

$products = db()->query('SELECT name, category, price, is_active FROM products ORDER BY category, name')->fetchAll();

renderHeader('Admin Products');
?>
<h1 class="dash-title">Products</h1>
<p class="dash-subtitle">Add, review, and maintain RGO merchandise entries.</p>

<div class="dash-panel">
    <?php if (empty($products)): ?>
        <p>No products found.</p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $row): ?>
                <tr>
                    <td><?php echo e((string) $row['name']); ?></td>
                    <td><?php echo e((string) $row['category']); ?></td>
                    <td><?php echo formatPeso((float) $row['price']); ?></td>
                    <td><?php echo ((int) $row['is_active'] === 1) ? 'Active' : 'Inactive'; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php renderFooter();
