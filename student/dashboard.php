<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/layout.php';

$pdo = db();
$cartCount = cartCount();

$activeOrders = $pdo->query("
    SELECT COUNT(*) AS cnt,
           SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) AS pending,
           SUM(CASE WHEN status='Processing' THEN 1 ELSE 0 END) AS processing,
           SUM(CASE WHEN status='Ready for Pickup' THEN 1 ELSE 0 END) AS ready
    FROM orders WHERE status NOT IN ('Completed','Cancelled')
")->fetch();

$recentOrders = $pdo->query("
    SELECT order_code, total_amount, status, created_at
    FROM orders ORDER BY id DESC LIMIT 6
")->fetchAll();

renderHeader('Student Dashboard', 'student');
?>
<h1 class="dash-title">My Dashboard</h1>
<p class="dash-subtitle">Welcome! Track your orders and browse available RGO items.</p>

<div class="stat-row">
    <div class="stat-card accent-blue">
        <span class="stat-label">Cart Items</span>
        <span class="stat-value"><?php echo $cartCount; ?></span>
        <span class="stat-sub"><a href="/CAPSTONE/cart.php">View cart</a></span>
    </div>
    <div class="stat-card accent-orange">
        <span class="stat-label">Pending Orders</span>
        <span class="stat-value"><?php echo (int)($activeOrders['pending'] ?? 0); ?></span>
        <span class="stat-sub">Awaiting processing</span>
    </div>
    <div class="stat-card accent-green">
        <span class="stat-label">Ready for Pickup</span>
        <span class="stat-value"><?php echo (int)($activeOrders['ready'] ?? 0); ?></span>
        <span class="stat-sub">Go claim your order</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Processing</span>
        <span class="stat-value"><?php echo (int)($activeOrders['processing'] ?? 0); ?></span>
        <span class="stat-sub">Being prepared</span>
    </div>
</div>

<div class="dash-panel">
    <div class="dash-panel-header">
        <h2 class="dash-panel-title">Recent Orders</h2>
        <a class="btn" href="/CAPSTONE/student/my_orders.php" style="font-size:0.82rem;padding:0.4rem 0.9rem;">View All</a>
    </div>
    <?php if (empty($recentOrders)): ?>
        <p class="small">You haven't placed any orders yet. <a href="/CAPSTONE/student/browse.php">Browse items</a> to get started.</p>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Order Code</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($recentOrders as $o): ?>
            <?php
                $s = strtolower(str_replace(' ', '-', (string)$o['status']));
                $badgeClass = match(true) {
                    str_contains($s,'pending')    => 'badge-pending',
                    str_contains($s,'processing') => 'badge-processing',
                    str_contains($s,'pickup')     => 'badge-pickup',
                    str_contains($s,'completed')  => 'badge-completed',
                    str_contains($s,'cancelled')  => 'badge-cancelled',
                    default                        => '',
                };
            ?>
            <tr>
                <td><strong><?php echo e((string)$o['order_code']); ?></strong></td>
                <td><?php echo formatPeso((float)$o['total_amount']); ?></td>
                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo e((string)$o['status']); ?></span></td>
                <td class="small"><?php echo e((string)$o['created_at']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php renderFooter('student');
