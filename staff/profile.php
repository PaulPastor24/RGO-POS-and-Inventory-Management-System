<?php

declare(strict_types=1);

require_once __DIR__ . '/../admin/auth.php';
require_once __DIR__ . '/../app/layout.php';

requireStaff();
$user = currentUser();

renderHeader('Staff Profile');
?>
<h1 class="dash-title">Profile</h1>
<p class="dash-subtitle">View your account details and assigned access level.</p>

<div class="dash-panel">
    <p><strong>Name:</strong> <?php echo e((string) ($user['name'] ?? '-')); ?></p>
    <p><strong>Email:</strong> <?php echo e((string) ($user['email'] ?? '-')); ?></p>
    <p><strong>Role:</strong> <?php echo e(strtoupper((string) ($user['role'] ?? 'staff'))); ?></p>
</div>
<?php renderFooter();
