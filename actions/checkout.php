<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../admin/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/CAPSTONE/cart.php');
}

requireLogin();
requireCsrfToken('/CAPSTONE/cart.php');

$user = currentUser();
$userEmail = (string) ($user['email'] ?? '');

$fullName = trim((string) ($_POST['full_name'] ?? ''));
$studentNo = trim((string) ($_POST['student_no'] ?? ''));
$campus = trim((string) ($_POST['campus'] ?? ''));
$contactNo = trim((string) ($_POST['contact_no'] ?? ''));
$notes = trim((string) ($_POST['notes'] ?? ''));

if ($fullName === '' || $studentNo === '' || $campus === '' || $contactNo === '') {
    flash('error', 'Please complete all required checkout fields.');
    redirect('/CAPSTONE/cart.php');
}

$cart = getCart();
if (empty($cart)) {
    flash('error', 'Your cart is empty.');
    redirect('/CAPSTONE/cart.php');
}

$ids = array_keys($cart);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = db()->prepare(
    "SELECT p.id, p.name, p.price, COALESCE(i.quantity_on_hand, 0) AS quantity_on_hand
     FROM products p
     LEFT JOIN inventory i ON i.product_id = p.id
     WHERE p.id IN ($placeholders)"
);
$stmt->execute($ids);
$productRows = $stmt->fetchAll();

if (empty($productRows)) {
    flash('error', 'No valid products found in cart.');
    redirect('/CAPSTONE/cart.php');
}

$items = [];
$total = 0.0;
$stockErrors = [];
foreach ($productRows as $row) {
    $qty = (int) ($cart[$row['id']] ?? 0);
    if ($qty <= 0) {
        continue;
    }

    $stockOnHand = (int) $row['quantity_on_hand'];
    if ($qty > $stockOnHand) {
        $stockErrors[] = $row['name'] . ' (available: ' . $stockOnHand . ')';
        continue;
    }

    $lineTotal = (float) $row['price'] * $qty;
    $items[] = [
        'product_id' => (int) $row['id'],
        'name' => $row['name'],
        'qty' => $qty,
        'price' => (float) $row['price'],
        'line_total' => $lineTotal,
        'stock_on_hand' => $stockOnHand,
    ];

    $total += $lineTotal;
}

if (!empty($stockErrors)) {
    flash('error', 'Insufficient stock for: ' . implode(', ', $stockErrors));
    redirect('/CAPSTONE/cart.php');
}

if (empty($items)) {
    flash('error', 'Your cart has no billable items.');
    redirect('/CAPSTONE/cart.php');
}

$orderCode = 'RGO-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
$createdAt = date('Y-m-d H:i:s');

$pdo = db();
$pdo->beginTransaction();

try {
    $orderStmt = $pdo->prepare(
        'INSERT INTO orders (order_code, full_name, student_no, campus, contact_no, notes, total_amount, user_email, created_at)
         VALUES (:order_code, :full_name, :student_no, :campus, :contact_no, :notes, :total_amount, :user_email, :created_at)'
    );

    $orderStmt->execute([
        ':order_code' => $orderCode,
        ':full_name' => $fullName,
        ':student_no' => $studentNo,
        ':campus' => $campus,
        ':contact_no' => $contactNo,
        ':notes' => $notes,
        ':total_amount' => $total,
        ':user_email' => $userEmail,
        ':created_at' => $createdAt,
    ]);

    $orderId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, line_total)
         VALUES (:order_id, :product_id, :product_name, :quantity, :unit_price, :line_total)'
    );

    foreach ($items as $item) {
        $itemStmt->execute([
            ':order_id' => $orderId,
            ':product_id' => $item['product_id'],
            ':product_name' => $item['name'],
            ':quantity' => $item['qty'],
            ':unit_price' => $item['price'],
            ':line_total' => $item['line_total'],
        ]);

        $stockStmt = $pdo->prepare(
            'UPDATE inventory
             SET quantity_on_hand = quantity_on_hand - :qty, last_counted_at = :updated_at
             WHERE product_id = :product_id AND quantity_on_hand >= :qty'
        );
        $stockStmt->execute([
            ':qty' => $item['qty'],
            ':updated_at' => $createdAt,
            ':product_id' => $item['product_id'],
        ]);

        if ($stockStmt->rowCount() < 1) {
            throw new RuntimeException('Stock update failed for product ' . $item['product_id']);
        }

        $movementStmt = $pdo->prepare(
            'INSERT INTO stock_movements (product_id, movement_type, quantity, prior_quantity, reason, reference_id, recorded_by, created_at)
             VALUES (:product_id, :movement_type, :quantity, :prior_quantity, :reason, :reference_id, :recorded_by, :created_at)'
        );
        $movementStmt->execute([
            ':product_id' => $item['product_id'],
            ':movement_type' => 'OUT',
            ':quantity' => $item['qty'],
            ':prior_quantity' => $item['stock_on_hand'],
            ':reason' => 'Order checkout ' . $orderCode,
            ':reference_id' => $orderId,
            ':recorded_by' => $studentNo,
            ':created_at' => $createdAt,
        ]);
    }

    $pdo->commit();
    setCart([]);
    flash('success', 'Order placed successfully. Keep your order code.');
    redirect('/CAPSTONE/order_success.php?id=' . $orderId);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash('error', 'Unable to place order right now. Please try again.');
    redirect('/CAPSTONE/cart.php');
}
