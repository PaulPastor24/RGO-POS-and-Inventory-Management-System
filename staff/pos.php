<?php

declare(strict_types=1);

require_once __DIR__ . '/../admin/auth.php';
require_once __DIR__ . '/../app/layout.php';

requireStaff();

renderHeader('Staff POS');
?>
<h1 class="dash-title">Point of Sale</h1>
<p class="dash-subtitle">Process walk-in sales transactions at the RGO counter.</p>

<div class="dash-panel">
    <p>POS transaction capture can be integrated with inventory and sales reports in this module.</p>
</div>
<?php renderFooter();
