<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

unset($_SESSION['auth_user'], $_SESSION['admin_logged_in']);
flash('success', 'Session ended.');
redirect('/CAPSTONE/admin/login.php');
