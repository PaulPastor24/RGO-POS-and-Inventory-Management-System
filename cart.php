<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/layout.php';

$cart = getCart();
$products = [];
$total = 0.0;

if (!empty($cart)) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT id, name, price FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);

    foreach ($stmt->fetchAll() as $row) {
        $qty = (int) ($cart[$row['id']] ?? 0);
        if ($qty <= 0) {
            continue;
        }

        $lineTotal = (float) $row['price'] * $qty;
        $products[] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'price' => (float) $row['price'],
            'qty' => $qty,
            'line_total' => $lineTotal,
        ];
        $total += $lineTotal;
    }
}

renderHeader('Your Cart');
?>
<section class="hero">
    <h1>Your Cart</h1>
    <p>Review quantities, then place your order for processing by RGO.</p>
</section>

<?php if (empty($products)): ?>
    <section class="section">
        <p>Your cart is currently empty.</p>
        <a class="btn" href="/CAPSTONE/index.php">Browse Products</a>
    </section>
<?php else: ?>
    <section class="section">
        <form action="/CAPSTONE/actions/update_cart.php" method="post">
            <?php echo csrfField(); ?>
            <table class="table">
                <thead>
                <tr>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Line Total</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $item): ?>
                    <tr>
                        <td><?php echo e($item['name']); ?></td>
                        <td><?php echo formatPeso($item['price']); ?></td>
                        <td>
                            <input type="number" min="0" max="99" name="qty[<?php echo $item['id']; ?>]" value="<?php echo $item['qty']; ?>">
                        </td>
                        <td><?php echo formatPeso($item['line_total']); ?></td>
                        <td>
                            <button type="submit" class="btn ghost" formaction="/CAPSTONE/actions/remove_from_cart.php" formmethod="post" name="id" value="<?php echo (int) $item['id']; ?>">Remove</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <p><strong>Total: <?php echo formatPeso($total); ?></strong></p>
            <button type="submit">Update Cart</button>
            <button type="submit" class="btn ghost" style="margin-left:0.4rem;" formaction="/CAPSTONE/actions/clear_cart.php" formmethod="post">Clear Cart</button>
        </form>
    </section>

    <section class="section">
        <h2 style="margin-top:0;">Checkout Details</h2>
        <form action="/CAPSTONE/actions/checkout.php" method="post">
            <?php echo csrfField(); ?>
            <div class="form-grid">
                <div>
                    <label for="full_name">Full Name</label>
                    <input id="full_name" name="full_name" required>
                </div>
                <div>
                    <label for="student_no">Student Number</label>
                    <input id="student_no" name="student_no" required>
                </div>
                <div>
                    <label for="campus">Campus</label>
                    <select id="campus" name="campus" required>
                        <option value="">Select campus</option>
                        <?php global $campuses; foreach ($campuses as $campus): ?>
                            <option value="<?php echo e($campus); ?>"><?php echo e($campus); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="contact_no">Contact Number</label>
                    <input id="contact_no" name="contact_no" required>
                </div>
            </div>
            <div style="margin-top:0.8rem;">
                <label for="notes">Notes (optional)</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Preferred release date, section, etc."></textarea>
            </div>
            <button style="margin-top:0.8rem;" type="submit">Place Order</button>
        </form>
    </section>
<?php endif; ?>

<?php renderFooter();
