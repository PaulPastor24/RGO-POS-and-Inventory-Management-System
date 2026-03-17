<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/auth.php';

flash('success', 'You have been signed out.');
logout();
