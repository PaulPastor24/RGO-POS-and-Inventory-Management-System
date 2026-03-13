<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/CAPSTONE/cart.php');
}

requireCsrfToken('/CAPSTONE/cart.php');

$incoming = $_POST['qty'] ?? [];
$cart = getCart();

foreach ($incoming as $productId => $qty) {
    $id = (int) $productId;
    $newQty = (int) $qty;

    if ($newQty <= 0) {
        unset($cart[$id]);
        continue;
    }

    $cart[$id] = min(99, $newQty);
}

setCart($cart);
flash('success', 'Cart updated.');
redirect('/CAPSTONE/cart.php');
