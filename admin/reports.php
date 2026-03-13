<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../app/layout.php';

requireAdmin();

$daily = db()->query("SELECT date(created_at) AS order_day, COUNT(*) AS orders_count, SUM(total_amount) AS gross_total FROM orders GROUP BY date(created_at) ORDER BY order_day DESC LIMIT 14")->fetchAll();

renderHeader('Admin Reports');
?>
<h1 class="dash-title">Reports</h1>
<p class="dash-subtitle">Sales and inventory reporting for planning and audit.</p>

<div class="dash-panel">
    <?php if (empty($daily)): ?>
        <p>No report data available.</p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th>Date</th>
                <th>Orders</th>
                <th>Gross Total</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($daily as $row): ?>
                <tr>
                    <td><?php echo e((string) $row['order_day']); ?></td>
                    <td><?php echo (int) $row['orders_count']; ?></td>
                    <td><?php echo formatPeso((float) ($row['gross_total'] ?? 0)); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php renderFooter();
