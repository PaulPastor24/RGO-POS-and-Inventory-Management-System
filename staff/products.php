<?php

declare(strict_types=1);

require_once __DIR__ . '/../admin/auth.php';
require_once __DIR__ . '/../app/layout.php';

requireStaff();

$products = db()->query('SELECT name, category, price FROM products WHERE is_active = 1 ORDER BY category, name')->fetchAll();

renderHeader('Staff Products');
?>
<h1 class="dash-title">Products</h1>
<p class="dash-subtitle">View active items currently offered by the office.</p>

<div class="dash-panel">
    <?php if (empty($products)): ?>
        <p>No active products found.</p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $row): ?>
                <tr>
                    <td><?php echo e((string) $row['name']); ?></td>
                    <td><?php echo e((string) $row['category']); ?></td>
                    <td><?php echo formatPeso((float) $row['price']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php renderFooter();
