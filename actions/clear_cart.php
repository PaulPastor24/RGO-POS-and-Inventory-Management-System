<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	redirect('/CAPSTONE/cart.php');
}

requireCsrfToken('/CAPSTONE/cart.php');

setCart([]);
flash('success', 'Cart cleared.');
redirect('/CAPSTONE/cart.php');
