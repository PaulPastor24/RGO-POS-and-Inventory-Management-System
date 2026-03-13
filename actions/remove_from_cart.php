<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	redirect('/CAPSTONE/cart.php');
}

requireCsrfToken('/CAPSTONE/cart.php');

$id = (int) ($_POST['id'] ?? 0);
$cart = getCart();
unset($cart[$id]);
setCart($cart);

flash('success', 'Item removed from cart.');
redirect('/CAPSTONE/cart.php');
