<?php

declare(strict_types=1);

require_once __DIR__ . '/../admin/auth.php';
require_once __DIR__ . '/../app/layout.php';

requireStaff();

$orders = db()->query("SELECT order_code, full_name, status, updated_at FROM orders WHERE status = 'Ready for Pickup' ORDER BY id DESC")->fetchAll();

renderHeader('Order Pickup');
?>
<h1 class="dash-title">Order Pickup</h1>
<p class="dash-subtitle">Confirm and release completed requests to students.</p>

<div class="dash-panel">
    <?php if (empty($orders)): ?>
        <p>No orders are currently tagged as ready for pickup.</p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th>Order Code</th>
                <th>Student</th>
                <th>Status</th>
                <th>Updated</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo e((string) $order['order_code']); ?></td>
                    <td><?php echo e((string) $order['full_name']); ?></td>
                    <td><?php echo e((string) $order['status']); ?></td>
                    <td><?php echo e((string) ($order['updated_at'] ?? '-')); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php renderFooter();
