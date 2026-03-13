<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../app/layout.php';

requireAdmin();
$user = currentUser();

$allowedStatuses = ['Pending', 'Processing', 'Ready for Pickup', 'Completed', 'Cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken('/CAPSTONE/admin/orders.php');

    $orderId = (int) ($_POST['order_id'] ?? 0);
    $status = trim((string) ($_POST['status'] ?? ''));
    $reason = trim((string) ($_POST['reason'] ?? ''));

    if ($orderId > 0 && in_array($status, $allowedStatuses, true)) {
        $pdo = db();
        $lookup = $pdo->prepare('SELECT status FROM orders WHERE id = :id');
        $lookup->execute([':id' => $orderId]);
        $oldStatus = (string) ($lookup->fetchColumn() ?: '');

        if ($oldStatus === '') {
            flash('error', 'Order not found.');
            redirect('/CAPSTONE/admin/orders.php');
        }

        $stmt = $pdo->prepare('UPDATE orders SET status = :status, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            ':status' => $status,
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id' => $orderId,
        ]);

        if ($oldStatus !== $status) {
            recordOrderStatusHistory(
                $pdo,
                $orderId,
                $oldStatus,
                $status,
                (string) ($user['email'] ?? null),
                $reason !== '' ? $reason : null
            );

            recordAuditLog(
                $pdo,
                'order',
                $orderId,
                'status_update',
                'status',
                $oldStatus,
                $status,
                (string) ($user['email'] ?? null),
                'admin'
            );
        }

        flash('success', 'Order status updated.');
    } else {
        flash('error', 'Invalid status update request.');
    }

    redirect('/CAPSTONE/admin/orders.php');
}

$orders = db()->query('SELECT * FROM orders ORDER BY id DESC')->fetchAll();

renderHeader('Manage Orders');
?>
<h1 class="dash-title">Order Management</h1>
<p class="dash-subtitle">Review submitted orders and update fulfillment status.</p>

<div class="dash-panel">
    <?php if (empty($orders)): ?>
        <p>No orders yet.</p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th>Order</th>
                <th>Student</th>
                <th>Campus</th>
                <th>Contact</th>
                <th>Total</th>
                <th>Status</th>
                <th>Created</th>
                <th>Update</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>
                        <strong><?php echo e($order['order_code']); ?></strong>
                        <?php if (!empty($order['notes'])): ?>
                            <div class="small">Note: <?php echo e($order['notes']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo e($order['full_name']); ?><br>
                        <span class="small"><?php echo e($order['student_no']); ?></span>
                    </td>
                    <td><?php echo e($order['campus']); ?></td>
                    <td><?php echo e($order['contact_no']); ?></td>
                    <td><?php echo formatPeso((float) $order['total_amount']); ?></td>
                    <td><?php echo e($order['status']); ?></td>
                    <td><?php echo e($order['created_at']); ?></td>
                    <td>
                        <form method="post" action="/CAPSTONE/admin/orders.php">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
                            <select name="status">
                                <?php foreach ($allowedStatuses as $status): ?>
                                    <option value="<?php echo e($status); ?>" <?php echo $status === $order['status'] ? 'selected' : ''; ?>>
                                        <?php echo e($status); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input style="margin-top:0.5rem;" type="text" name="reason" placeholder="Reason (optional)">
                            <button style="margin-top:0.5rem;" type="submit">Save</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php renderFooter();
