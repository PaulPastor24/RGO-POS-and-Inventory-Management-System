<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../admin/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/CAPSTONE/index.php');
}

requireLogin();
requireCsrfToken('/CAPSTONE/index.php');

$productId = (int) ($_POST['product_id'] ?? 0);
$quantity = (int) ($_POST['quantity'] ?? 1);
$quantity = max(1, min(99, $quantity));

$stmt = db()->prepare('SELECT id FROM products WHERE id = :id AND is_active = 1');
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();

if ($product === false) {
    flash('error', 'Selected product is unavailable.');
    redirect('/CAPSTONE/index.php');
}

$cart = getCart();
$cart[$productId] = min(99, (int) ($cart[$productId] ?? 0) + $quantity);
setCart($cart);

flash('success', 'Item added to cart.');
redirect('/CAPSTONE/index.php');
