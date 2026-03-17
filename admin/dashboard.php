<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../app/layout.php';

requireAdmin();
$user = currentUser();

$pdo = db();

$stats = $pdo->query("
    SELECT
        COUNT(*) AS total_orders,
        SUM(CASE WHEN status = 'Pending'          THEN 1 ELSE 0 END) AS pending_orders,
        SUM(CASE WHEN status = 'Processing'       THEN 1 ELSE 0 END) AS processing_orders,
        SUM(CASE WHEN status = 'Completed'        THEN 1 ELSE 0 END) AS completed_orders,
        SUM(CASE WHEN DATE(created_at) = DATE('now') THEN total_amount ELSE 0 END) AS sales_today,
        SUM(total_amount) AS gross_sales
    FROM orders
")->fetch();

$lowStock = $pdo->query("
    SELECT COUNT(*) AS cnt FROM inventory i
    JOIN products p ON p.id = i.product_id
    WHERE p.is_active = 1 AND i.quantity_on_hand <= i.reorder_point
")->fetchColumn();

$productCount = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();

$statusData = $pdo->query("SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status")->fetchAll();

$today = new DateTimeImmutable('today');
$start = $today->modify('-6 days')->format('Y-m-d');
$end = $today->modify('+1 day')->format('Y-m-d');
$salesTrendStmt = $pdo->prepare(
    "SELECT DATE(created_at) AS day, COUNT(*) AS cnt, SUM(total_amount) AS sales
     FROM orders
     WHERE DATE(created_at) BETWEEN :start AND :end
     GROUP BY DATE(created_at)
     ORDER BY DATE(created_at) ASC"
);
$salesTrendStmt->execute([':start' => $start, ':end' => $end]);
$salesTrend = $salesTrendStmt->fetchAll();

$trendLabels = [];
$trendSales = [];
for ($d = clone $today->modify('-6 days'); $d <= $today; $d = $d->modify('+1 day')) {
    $label = $d->format('M j');
    $trendLabels[] = $label;
    $trendSales[$d->format('Y-m-d')] = 0.0;
}
foreach ($salesTrend as $row) {
    $day = (string) ($row['day'] ?? '');
    if (isset($trendSales[$day])) {
        $trendSales[$day] = (float) ($row['sales'] ?? 0);
    }
}
$trendData = array_values($trendSales);

$recentOrders = $pdo->query("
    SELECT order_code, full_name, total_amount, status, created_at
    FROM orders ORDER BY id DESC LIMIT 8
")->fetchAll();

renderHeader('Admin Dashboard');
?>
<h1 class="dash-title">Dashboard</h1>
<p class="dash-subtitle">Welcome back, <?php echo e((string)($user['name'] ?? 'Admin')); ?>. Here's your system overview.</p>

<div class="dash-actions">
    <a class="btn" href="/CAPSTONE/admin/orders.php">View Orders</a>
    <a class="btn" href="/CAPSTONE/admin/products.php">Manage Products</a>
    <a class="btn" href="/CAPSTONE/admin/inventory.php">Inventory</a>
    <a class="btn" href="/CAPSTONE/admin/reports.php">Reports</a>
    <a class="btn" href="/CAPSTONE/admin/users.php">Users</a>
</div>

<div class="stat-row">
    <div class="stat-card accent-red">
        <span class="stat-label">Sales Today</span>
        <span class="stat-value"><?php echo formatPeso((float)($stats['sales_today'] ?? 0)); ?></span>
        <span class="stat-sub">Online orders only</span>
    </div>
    <div class="stat-card accent-blue">
        <span class="stat-label">Total Orders</span>
        <span class="stat-value"><?php echo (int)($stats['total_orders'] ?? 0); ?></span>
        <span class="stat-sub"><?php echo (int)($stats['pending_orders'] ?? 0); ?> pending</span>
    </div>
    <div class="stat-card accent-orange">
        <span class="stat-label">Low Stock Items</span>
        <span class="stat-value"><?php echo (int)$lowStock; ?></span>
        <span class="stat-sub">At or below reorder point</span>
    </div>
    <div class="stat-card accent-green">
        <span class="stat-label">Total Products</span>
        <span class="stat-value"><?php echo (int)$productCount; ?></span>
        <span class="stat-sub">Active listings</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Processing</span>
        <span class="stat-value"><?php echo (int)($stats['processing_orders'] ?? 0); ?></span>
        <span class="stat-sub">Being prepared</span>
    </div>
    <div class="stat-card accent-green">
        <span class="stat-label">Gross Sales</span>
        <span class="stat-value" style="font-size:1.1rem"><?php echo formatPeso((float)($stats['gross_sales'] ?? 0)); ?></span>
        <span class="stat-sub">All time</span>
    </div>
</div>

<div class="dash-panel">
    <div class="dash-panel-header">
        <h2 class="dash-panel-title">Orders Overview</h2>
    </div>
    <div class="grid grid-2">
        <div class="chart-card">
            <h3>Order Status Distribution</h3>
            <canvas id="orderStatusChart" width="400" height="250"></canvas>
        </div>
        <div class="chart-card">
            <h3>Sales (Last 7 Days)</h3>
            <canvas id="salesTrendChart" width="400" height="250"></canvas>
        </div>
    </div>
</div>

<div class="dash-panel">
    <div class="dash-panel-header">
        <h2 class="dash-panel-title">Recent Orders</h2>
        <a class="btn" href="/CAPSTONE/admin/orders.php" style="font-size:0.82rem;padding:0.4rem 0.9rem;">View All</a>
    </div>
    <?php if (empty($recentOrders)): ?>
        <p class="small">No orders yet.</p>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Order Code</th><th>Student</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    const statusLabels = <?php echo json_encode(array_column($statusData, 'status')); ?>;
    const statusCounts = <?php echo json_encode(array_map('intval', array_column($statusData, 'cnt'))); ?>;

    const ctxStatus = document.getElementById('orderStatusChart');
    if (ctxStatus) {
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusCounts,
                    backgroundColor: ['#f59e0b', '#2563eb', '#22c55e', '#14b8a6', '#ef4444'],
                    borderWidth: 0,
                }]
            },
            options: {
                plugins: {legend: {position: 'bottom'}},
                maintainAspectRatio: false,
            }
        });
    }

    const trendLabels = <?php echo json_encode($trendLabels); ?>;
    const trendData = <?php echo json_encode($trendData); ?>;

    const ctxTrend = document.getElementById('salesTrendChart');
    if (ctxTrend) {
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Sales',
                    data: trendData,
                    borderColor: '#7b1113',
                    backgroundColor: 'rgba(123, 17, 19, 0.18)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 3,
                }]
            },
            options: {
                plugins: {legend: {display: false}},
                scales: {
                    y: {ticks: {callback: (v) => '₱' + v.toFixed(0)} }
                },
                maintainAspectRatio: false,
            }
        });
    }
})();
</script>

<?php renderFooter();
