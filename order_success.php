<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/layout.php';

$orderId = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT order_code, full_name, total_amount, status FROM orders WHERE id = :id');
$stmt->execute([':id' => $orderId]);
$order = $stmt->fetch();

renderHeader('Order Placed');
?>
<section class="hero">
    <h1>Order Submitted</h1>
    <p>Your request has been recorded by BatState-U RGO.</p>
</section>

<section class="section">
    <?php if ($order === false): ?>
        <p>Order details could not be found.</p>
    <?php else: ?>
        <p><strong>Order Code:</strong> <?php echo e($order['order_code']); ?></p>
        <p><strong>Name:</strong> <?php echo e($order['full_name']); ?></p>
        <p><strong>Total:</strong> <?php echo formatPeso((float) $order['total_amount']); ?></p>
        <p><strong>Status:</strong> <?php echo e($order['status']); ?></p>
        <p>Keep your order code to track updates.</p>
    <?php endif; ?>

    <a class="btn" href="/CAPSTONE/track.php">Track This Order</a>
    <a class="btn ghost" href="/CAPSTONE/index.php">Back to Catalog</a>
</section>
<?php renderFooter();
