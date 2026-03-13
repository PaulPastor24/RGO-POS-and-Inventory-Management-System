<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../app/layout.php';

requireAdmin();

$logs = db()->query('SELECT actor_email, actor_role, action_type, target_entity, target_id, created_at FROM audit_logs ORDER BY id DESC LIMIT 100')->fetchAll();

renderHeader('Activity Logs');
?>
<h1 class="dash-title">Activity Logs</h1>
<p class="dash-subtitle">Review system activity history and operational trace records.</p>

<div class="dash-panel">
    <?php if (empty($logs)): ?>
        <p>No activity logs found.</p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th>Actor</th>
                <th>Role</th>
                <th>Action</th>
                <th>Target</th>
                <th>Target ID</th>
                <th>Created</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $row): ?>
                <tr>
                    <td><?php echo e((string) ($row['actor_email'] ?? '-')); ?></td>
                    <td><?php echo e((string) ($row['actor_role'] ?? '-')); ?></td>
                    <td><?php echo e((string) ($row['action_type'] ?? '-')); ?></td>
                    <td><?php echo e((string) ($row['target_entity'] ?? '-')); ?></td>
                    <td><?php echo (int) ($row['target_id'] ?? 0); ?></td>
                    <td><?php echo e((string) ($row['created_at'] ?? '-')); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php renderFooter();
