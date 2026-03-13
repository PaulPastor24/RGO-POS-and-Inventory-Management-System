<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../app/layout.php';

requireAdmin();

renderHeader('System Settings');
?>
<h1 class="dash-title">System Settings</h1>
<p class="dash-subtitle">Central place for configuration controls and platform defaults.</p>

<div class="dash-panel">
    <p>Use <code>app/config.php</code> for environment values in this local deployment.</p>
    <p>This page can be connected to a secured database-backed settings module later.</p>
</div>
<?php renderFooter();
