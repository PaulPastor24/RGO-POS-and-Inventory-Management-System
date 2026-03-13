<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../app/layout.php';

requireAdmin();

renderHeader('POS Transactions');
?>
<h1 class="dash-title">POS Transactions</h1>
<p class="dash-subtitle">Monitor cashier-side walk-in sales transactions.</p>

<div class="dash-panel">
    <p>POS transaction details can be connected to a dedicated transaction table in this module.</p>
</div>
<?php renderFooter();
