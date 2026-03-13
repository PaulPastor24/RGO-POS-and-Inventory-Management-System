<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../app/layout.php';

requireAdmin();

$adminList = array_map('strtolower', ADMIN_EMAILS);
$staffList = array_map('strtolower', STAFF_EMAILS);

renderHeader('User Management');
?>
<h1 class="dash-title">Users</h1>
<p class="dash-subtitle">Manage admin and staff account access for the ordering system.</p>

<div class="dash-panel">
    <h2>Admins</h2>
    <ul>
        <?php foreach ($adminList as $email): ?>
            <li><?php echo e($email); ?></li>
        <?php endforeach; ?>
    </ul>

    <h2>Staff</h2>
    <ul>
        <?php foreach ($staffList as $email): ?>
            <li><?php echo e($email); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php renderFooter();
