<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../admin/auth.php';
require_once __DIR__ . '/../app/layout.php';

requireStudent();

$user = currentUser();
$userEmail = (string) ($user['email'] ?? '');

$stmt = db()->prepare(
    "SELECT order_code, status, total_amount, created_at 
     FROM orders 
     WHERE (user_email = :email OR (user_email IS NULL AND student_no IN (SELECT student_no FROM orders WHERE user_email = :email))) 
     AND status IN ('Pending', 'Processing', 'Ready for Pickup') 
     ORDER BY id DESC"
);
$stmt->execute([':email' => $userEmail]);
$orders = $stmt->fetchAll();

renderHeader('My Orders', 'student');
?>
<h1 class="dash-title">My Orders</h1>
<p class="dash-subtitle">Track active order requests that are still in progress.</p>

<div class="dash-panel">
    <?php if (empty($orders)): ?>
        <p>No active orders found.</p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th>Order Code</th>
                <th>Status</th>
                <th>Total</th>
                <th>Created</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo e((string) $order['order_code']); ?></td>
                    <td><?php echo e((string) $order['status']); ?></td>
                    <td><?php echo formatPeso((float) $order['total_amount']); ?></td>
                    <td><?php echo e((string) $order['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php renderFooter('student');
