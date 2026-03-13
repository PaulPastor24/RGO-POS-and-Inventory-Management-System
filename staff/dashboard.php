<?php

declare(strict_types=1);

require_once __DIR__ . '/../admin/auth.php';
require_once __DIR__ . '/../app/layout.php';

requireStaff();
$user = currentUser();

$pdo = db();

$queueStats = $pdo->query("
    SELECT
        SUM(CASE WHEN status = 'Pending'          THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status = 'Processing'       THEN 1 ELSE 0 END) AS processing,
        SUM(CASE WHEN status = 'Ready for Pickup' THEN 1 ELSE 0 END) AS ready,
        SUM(CASE WHEN DATE(created_at) = DATE('now') THEN 1 ELSE 0 END) AS today_orders
    FROM orders
")->fetch();

$lowStock = $pdo->query("
    SELECT COUNT(*) FROM inventory i
    JOIN products p ON p.id = i.product_id
    WHERE p.is_active = 1 AND i.quantity_on_hand <= i.reorder_point
")->fetchColumn();

$queue = $pdo->query("
    SELECT order_code, full_name, total_amount, status, created_at
    FROM orders WHERE status IN ('Pending','Processing','Ready for Pickup')
    ORDER BY id DESC LIMIT 10
")->fetchAll();

renderHeader('Staff Dashboard');
?>
<h1 class="dash-title">Staff Dashboard</h1>
<p class="dash-subtitle">Welcome, <?php echo e((string)($user['name'] ?? 'Staff')); ?>. Here's today's queue.</p>

<div class="stat-row">
    <div class="stat-card accent-orange">
        <span class="stat-label">Pending Orders</span>
        <span class="stat-value"><?php echo (int)($queueStats['pending'] ?? 0); ?></span>
        <span class="stat-sub">Awaiting action</span>
    </div>
    <div class="stat-card accent-blue">
        <span class="stat-label">Processing</span>
        <span class="stat-value"><?php echo (int)($queueStats['processing'] ?? 0); ?></span>
        <span class="stat-sub">Being prepared</span>
    </div>
    <div class="stat-card accent-green">
        <span class="stat-label">Ready for Pickup</span>
        <span class="stat-value"><?php echo (int)($queueStats['ready'] ?? 0); ?></span>
        <span class="stat-sub">Student to collect</span>
    </div>
    <div class="stat-card accent-red">
        <span class="stat-label">Low Stock Alerts</span>
        <span class="stat-value"><?php echo (int)$lowStock; ?></span>
        <span class="stat-sub">Needs restocking</span>
    </div>
</div>

<div class="dash-panel">
    <div class="dash-panel-header">
        <h2 class="dash-panel-title">Active Order Queue</h2>
        <a class="btn" href="/CAPSTONE/staff/orders.php" style="font-size:0.82rem;padding:0.4rem 0.9rem;">Manage Orders</a>
    </div>
    <?php if (empty($queue)): ?>
        <p class="small">No active orders in queue.</p>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Order Code</th><th>Student</th><th>Total</th><th>Status</th><th>Created</th></tr></thead>
        <tbody>
        <?php foreach ($queue as $o): ?>
            <?php
                $s = strtolower(str_replace(' ', '-', (string)$o['status']));
                $badgeClass = match(true) {
                    str_contains($s,'pending')    => 'badge-pending',
                    str_contains($s,'processing') => 'badge-processing',
                    str_contains($s,'pickup')     => 'badge-pickup',
                    default                        => '',
                };
            ?>
            <tr>
                <td><strong><?php echo e((string)$o['order_code']); ?></strong></td>
                <td><?php echo e((string)$o['full_name']); ?></td>
                <td><?php echo formatPeso((float)$o['total_amount']); ?></td>
                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo e((string)$o['status']); ?></span></td>
                <td class="small"><?php echo e((string)$o['created_at']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php renderFooter();
