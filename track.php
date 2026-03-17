<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/admin/auth.php';
require_once __DIR__ . '/app/layout.php';

requireLogin();

$user = currentUser();
$userEmail = (string) ($user['email'] ?? '');
$order = null;
$items = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken('/CAPSTONE/track.php');

    $orderCode = trim((string) ($_POST['order_code'] ?? ''));
    $studentNo = trim((string) ($_POST['student_no'] ?? ''));

    if ($orderCode === '' || $studentNo === '') {
        flash('error', 'Please provide both Order Code and Student Number.');
        redirect('/CAPSTONE/track.php');
    }

    $stmt = db()->prepare('SELECT * FROM orders WHERE order_code = :order_code AND student_no = :student_no');
    $stmt->execute([
        ':order_code' => $orderCode,
        ':student_no' => $studentNo,
    ]);

    $order = $stmt->fetch();

    if ($order !== false) {
        $itemStmt = db()->prepare('SELECT product_name, quantity, unit_price, line_total FROM order_items WHERE order_id = :order_id');
        $itemStmt->execute([':order_id' => $order['id']]);
        $items = $itemStmt->fetchAll();
    }
}

renderHeader('Track Order');
?>
<section class="hero">
    <h1>Track Your Order</h1>
    <p>Enter your order code and student number to check status.</p>
</section>

<section class="section">
    <form method="post" action="/CAPSTONE/track.php">
        <?php echo csrfField(); ?>
        <div class="form-grid">
            <div>
                <label for="order_code">Order Code</label>
                <input id="order_code" name="order_code" placeholder="RGO-20260310-AB12" required>
            </div>
            <div>
                <label for="student_no">Student Number</label>
                <input id="student_no" name="student_no" required>
            </div>
        </div>
        <button style="margin-top:0.8rem;" type="submit">Check Status</button>
    </form>
</section>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <?php if ($order === false || $order === null): ?>
        <section class="section">
            <p>No matching order was found. Please check your details and try again.</p>
        </section>
    <?php else: ?>
        <section class="section">
            <h2 style="margin-top:0;">Order <?php echo e($order['order_code']); ?></h2>
            <p><strong>Status:</strong> <?php echo e($order['status']); ?></p>
            <p><strong>Placed:</strong> <?php echo e($order['created_at']); ?></p>
            <p><strong>Total:</strong> <?php echo formatPeso((float) $order['total_amount']); ?></p>

            <table class="table">
                <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Line Total</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo e($item['product_name']); ?></td>
                        <td><?php echo (int) $item['quantity']; ?></td>
                        <td><?php echo formatPeso((float) $item['unit_price']); ?></td>
                        <td><?php echo formatPeso((float) $item['line_total']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
<?php endif; ?>

<?php renderFooter();
