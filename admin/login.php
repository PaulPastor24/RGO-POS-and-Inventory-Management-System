<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/layout.php';
require_once __DIR__ . '/auth.php';

if (isLoggedIn()) {
    $role = (string) (currentUser()['role'] ?? '');
    redirectForRole($role);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken('/CAPSTONE/admin/login.php');

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $user = attemptLocalLogin($email, $password);

    if ($user === null) {
        flash('error', 'Invalid email or password for an authorized account.');
        redirect('/CAPSTONE/admin/login.php');
    }

    signInUser((string) $user['email'], (string) $user['name'], (string) $user['role']);
    flash('success', 'Signed in successfully as ' . strtoupper((string) $user['role']) . '.');
    redirectForRole((string) $user['role']);
}

renderHeader('Workspace Login');
?>
<section class="hero hero-login">
    <div class="hero-split">
        <div>
            <p class="eyebrow">Secure Access</p>
            <h1>Sign In To Manage Orders And Inventory</h1>
            <p>Use the local login form for quick access in this XAMPP setup.</p>
        </div>
        <div class="hero-badge-stack">
            <div class="hero-badge">Admin and staff routing</div>
            <div class="hero-badge">Local demo accounts enabled</div>
        </div>
    </div>
</section>

<section class="section login-shell">
    <div class="login-grid">
        <div class="login-panel">
            <h2>Local Login</h2>
            <p class="small">Use an authorized email and password. Admin accounts go to the dashboard and staff accounts go to the processing queue.</p>
            <form method="post" action="/CAPSTONE/admin/login.php" class="login-form">
                <?php echo csrfField(); ?>
                <label for="email">Email address</label>
                <input id="email" type="email" name="email" placeholder="name@g.batstate-u.edu.ph" required>

                <label for="password">Password</label>
                <input id="password" type="password" name="password" placeholder="Enter your password" required>

                <button type="submit">Sign In</button>
            </form>
            <p class="small">Default demo passwords for this local setup are <strong>Admin123!</strong> and <strong>Staff123!</strong>. Change the entries in app/config.php before deployment.</p>
        </div>
    </div>
</section>
<?php renderFooter();
