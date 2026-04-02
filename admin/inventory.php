<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../app/layout.php';

requireLogin();
$user = currentUser();
$role = (string) ($user['role'] ?? '');

if (!in_array($role, ['admin', 'staff'], true)) {
    flash('error', 'Access denied.');
    redirect('/CAPSTONE/admin/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken('/CAPSTONE/admin/inventory.php');

    $productId = (int) ($_POST['product_id'] ?? 0);
    $adjustment = (int) ($_POST['adjustment'] ?? 0);
    $reason = trim((string) ($_POST['reason'] ?? ''));

    if ($productId <= 0 || $adjustment === 0 || $reason === '') {
        flash('error', 'Product, non-zero adjustment, and reason are required.');
        redirect('/CAPSTONE/admin/inventory.php');
    }

    $pdo = db();
    $lookup = $pdo->prepare('SELECT quantity_on_hand FROM inventory WHERE product_id = :product_id');
    $lookup->execute([':product_id' => $productId]);
    $prior = $lookup->fetchColumn();

    if ($prior === false) {
        flash('error', 'Inventory record not found for selected product.');
        redirect('/CAPSTONE/admin/inventory.php');
    }

    $priorQty = (int) $prior;
    $newQty = $priorQty + $adjustment;

    if ($newQty < 0) {
        flash('error', 'Adjustment results in negative stock, which is not allowed.');
        redirect('/CAPSTONE/admin/inventory.php');
    }

    $pdo->beginTransaction();
    try {
        $update = $pdo->prepare(
            'UPDATE inventory SET quantity_on_hand = :qty, last_counted_at = :updated_at WHERE product_id = :product_id'
        );
        $update->execute([
            ':qty' => $newQty,
            ':updated_at' => date('Y-m-d H:i:s'),
            ':product_id' => $productId,
        ]);

        $movement = $pdo->prepare(
            'INSERT INTO stock_movements (product_id, movement_type, quantity, prior_quantity, reason, recorded_by, created_at)
             VALUES (:product_id, :movement_type, :quantity, :prior_quantity, :reason, :recorded_by, :created_at)'
        );
        $movement->execute([
            ':product_id' => $productId,
            ':movement_type' => 'ADJUSTMENT',
            ':quantity' => abs($adjustment),
            ':prior_quantity' => $priorQty,
            ':reason' => $reason,
            ':recorded_by' => (string) ($user['email'] ?? ''),
            ':created_at' => date('Y-m-d H:i:s'),
        ]);

        recordAuditLog(
            $pdo,
            'inventory',
            $productId,
            'adjustment',
            'quantity_on_hand',
            (string) $priorQty,
            (string) $newQty,
            (string) ($user['email'] ?? ''),
            $role
        );

        $pdo->commit();
        flash('success', 'Inventory adjusted successfully.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', 'Unable to apply adjustment. Please try again.');
    }

    redirect('/CAPSTONE/admin/inventory.php');
}

$stmt = db()->query(
    'SELECT p.id, p.name, p.category, p.price, p.image_path, i.quantity_on_hand, i.reorder_point, i.last_counted_at
     FROM products p
     LEFT JOIN inventory i ON i.product_id = p.id
     WHERE p.is_active = 1
     ORDER BY p.category, p.name'
);
$rows = $stmt->fetchAll();

$totalSkus = count($rows);
$lowStockCount = 0;
$outOfStockCount = 0;
$inventoryValue = 0.0;

foreach ($rows as $summaryRow) {
    $qty = (int) ($summaryRow['quantity_on_hand'] ?? 0);
    $rp = (int) ($summaryRow['reorder_point'] ?? 10);

    if ($qty <= $rp) {
        $lowStockCount++;
    }
    if ($qty <= 0) {
        $outOfStockCount++;
    }

    $inventoryValue += ((float) ($summaryRow['price'] ?? 0)) * max(0, $qty);
}

renderHeader('Inventory Monitoring');
?>
<h1 class="dash-title">Inventory Monitoring</h1>
<p class="dash-subtitle">Track stock levels, reorder points, and apply controlled stock adjustments.</p>

<div class="stat-row inventory-summary-row">
    <div class="stat-card accent-blue">
        <span class="stat-label">Active SKUs</span>
        <span class="stat-value"><?php echo $totalSkus; ?></span>
        <span class="stat-sub">Items currently listed</span>
    </div>
    <div class="stat-card accent-orange">
        <span class="stat-label">Low Stock</span>
        <span class="stat-value"><?php echo $lowStockCount; ?></span>
        <span class="stat-sub">At or below reorder point</span>
    </div>
    <div class="stat-card accent-red">
        <span class="stat-label">Out of Stock</span>
        <span class="stat-value"><?php echo $outOfStockCount; ?></span>
        <span class="stat-sub">Needs immediate replenishment</span>
    </div>
    <div class="stat-card accent-green">
        <span class="stat-label">Inventory Value</span>
        <span class="stat-value" style="font-size:1.1rem"><?php echo formatPeso($inventoryValue); ?></span>
        <span class="stat-sub">Based on current quantity</span>
    </div>
</div>

<div class="dash-panel">
    <?php if (empty($rows)): ?>
        <p>No products found.</p>
    <?php else: ?>
        <div class="inventory-table-wrap">
        <table class="table inventory-table">
            <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Reorder Point</th>
                <th>Status</th>
                <th>Last Counted</th>
                <th>Adjust</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <?php $stock = (int) ($row['quantity_on_hand'] ?? 0); ?>
                <?php $reorderPoint = (int) ($row['reorder_point'] ?? 10); ?>
                <?php $imagePath = (string) (($row['image_path'] ?? '') ?: '/CAPSTONE/img/products/placeholder.svg'); ?>
                <?php
                    $statusLabel = 'Healthy';
                    $statusClass = 'status-ok';
                    if ($stock <= 0) {
                        $statusLabel = 'Out of Stock';
                        $statusClass = 'status-critical';
                    } elseif ($stock <= $reorderPoint) {
                        $statusLabel = 'Reorder Needed';
                        $statusClass = 'status-low';
                    }
                ?>
                <tr>
                    <td>
                        <div class="inv-product-cell">
                            <img
                                class="inv-thumb"
                                src="<?php echo e($imagePath); ?>"
                                alt="<?php echo e($row['name']); ?>"
                                width="48"
                                height="48"
                                loading="lazy"
                                decoding="async"
                                style="width:48px;height:48px;max-width:48px;max-height:48px;object-fit:cover;"
                                onerror="this.src='/CAPSTONE/img/products/placeholder.svg';"
                            >
                            <div>
                                <strong><?php echo e($row['name']); ?></strong>
                                <div class="small">SKU #<?php echo (int) $row['id']; ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo e($row['category']); ?></td>
                    <td><?php echo formatPeso((float) $row['price']); ?></td>
                    <td><strong><?php echo $stock; ?></strong></td>
                    <td><?php echo $reorderPoint; ?></td>
                    <td><span class="inv-status <?php echo $statusClass; ?>"><?php echo e($statusLabel); ?></span></td>
                    <td><?php echo e((string) ($row['last_counted_at'] ?? '-')); ?></td>
                    <td>
                        <form class="inv-adjust-form" method="post" action="/CAPSTONE/admin/inventory.php">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="product_id" value="<?php echo (int) $row['id']; ?>">
                            <input type="number" name="adjustment" placeholder="+/- qty" required>
                            <input type="text" name="reason" placeholder="Reason" required>
                            <button type="submit">Apply</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>
<?php renderFooter();
