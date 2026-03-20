<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/layout.php';
require_once __DIR__ . '/auth.php';

if (isLoggedIn()) {
    $role = (string) (currentUser()['role'] ?? '');
    redirectForRole($role);
}

$tab = strtolower(trim((string) ($_GET['tab'] ?? 'login')));
if (!in_array($tab, ['login', 'register'], true)) {
    $tab = 'login';
}

// Handle login form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'login') {
    requireCsrfToken('/CAPSTONE/admin/login.php?tab=login');

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    // Validate school domain
    if (!isSchoolDomain($email)) {
        flash('error', 'Only @g.batstate-u.edu.ph email addresses are allowed.');
        redirect('/CAPSTONE/admin/login.php?tab=login');
    }

    // Try database login first, then fall back to local login
    $user = attemptDatabaseLogin(db(), $email, $password) ?? attemptLocalLogin($email, $password);

    if ($user === null) {
        flash('error', 'Invalid email or password.');
        redirect('/CAPSTONE/admin/login.php?tab=login');
    }

    signInUser((string) $user['email'], (string) $user['name'], (string) $user['role']);
    flash('success', 'Signed in successfully as ' . strtoupper((string) $user['role']) . '.');
    redirectForRole((string) $user['role']);
}

// Handle registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'register') {
    requireCsrfToken('/CAPSTONE/admin/login.php?tab=register');

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $name = trim((string) ($_POST['name'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    // Validate school domain
    if (!isSchoolDomain($email)) {
        flash('error', 'Only @g.batstate-u.edu.ph email addresses are allowed for registration.');
        redirect('/CAPSTONE/admin/login.php?tab=register');
    }

    // Validate name
    if ($name === '') {
        flash('error', 'Full name is required.');
        redirect('/CAPSTONE/admin/login.php?tab=register');
    }

    // Validate password
    if ($password === '' || strlen($password) < 6) {
        flash('error', 'Password must be at least 6 characters long.');
        redirect('/CAPSTONE/admin/login.php?tab=register');
    }

    // Validate password confirmation
    if ($password !== $passwordConfirm) {
        flash('error', 'Passwords do not match.');
        redirect('/CAPSTONE/admin/login.php?tab=register');
    }

    // Register new user as student
    $newUser = registerUser(db(), $email, $name, $password, 'student');

    if ($newUser === null) {
        flash('error', 'Registration failed. Email may already be registered.');
        redirect('/CAPSTONE/admin/login.php?tab=register');
    }

    flash('success', 'Registration successful! Please sign in with your credentials.');
    redirect('/CAPSTONE/admin/login.php?tab=login');
}

renderHeader('Workspace Login');
?>
<section class="hero hero-login">
    <div class="hero-split">
        <div>
            <p class="eyebrow">Secure Access</p>
            <h1>Sign In To Manage Orders And Inventory</h1>
            <p>School domain authentication required (@g.batstate-u.edu.ph)</p>
        </div>
        <div class="hero-badge-stack">
            <div class="hero-badge">School domain only</div>
            <div class="hero-badge">Role-based access control</div>
            <div class="hero-badge">Student, Admin & Staff</div>
        </div>
    </div>
</section>

<section class="section login-shell">
    <div class="login-grid">
        <div class="login-tabs">
            <a href="/CAPSTONE/admin/login.php?tab=login" class="tab <?php echo $tab === 'login' ? 'active' : ''; ?>">Sign In</a>
            <a href="/CAPSTONE/admin/login.php?tab=register" class="tab <?php echo $tab === 'register' ? 'active' : ''; ?>">Register</a>
        </div>

        <?php if ($tab === 'login'): ?>
        <div class="login-panel">
            <h2>Sign In</h2>
            <p class="small">Use your BatState-U (@g.batstate-u.edu.ph) email and password.</p>
            <form method="post" action="/CAPSTONE/admin/login.php?tab=login" class="login-form">
                <?php echo csrfField(); ?>
                <label for="email">Email address</label>
                <input id="email" type="email" name="email" placeholder="name@g.batstate-u.edu.ph" required>

                <label for="password">Password</label>
                <input id="password" type="password" name="password" placeholder="Enter your password" required>

                <button type="submit">Sign In</button>
            </form>
            <p class="small">Don't have an account? <a href="/CAPSTONE/admin/login.php?tab=register">Register here</a></p>
        </div>

        <?php else: ?>
        <div class="login-panel">
            <h2>Create Account</h2>
            <p class="small">Register with your BatState-U email to get started as a student.</p>
            <form method="post" action="/CAPSTONE/admin/login.php?tab=register" class="login-form">
                <?php echo csrfField(); ?>
                <label for="name">Full Name</label>
                <input id="name" type="text" name="name" placeholder="Your full name" required>

                <label for="reg_email">Email address</label>
                <input id="reg_email" type="email" name="email" placeholder="name@g.batstate-u.edu.ph" required>

                <label for="reg_password">Password</label>
                <input id="reg_password" type="password" name="password" placeholder="At least 6 characters" required>

                <label for="password_confirm">Confirm Password</label>
                <input id="password_confirm" type="password" name="password_confirm" placeholder="Re-enter your password" required>

                <button type="submit">Create Account</button>
            </form>
            <p class="small">Already have an account? <a href="/CAPSTONE/admin/login.php?tab=login">Sign in here</a></p>
            <p class="small" style="margin-top: 1rem; color: #666;">
                <strong>Note:</strong> New accounts are created as students by default. Contact administrators for role upgrades.
            </p>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.login-tabs {
    display: flex;
    gap: 0;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid #e0e0e0;
}

.tab {
    flex: 1;
    padding: 0.75rem 1rem;
    text-align: center;
    text-decoration: none;
    color: #666;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
    cursor: pointer;
}

.tab:hover {
    color: #333;
    background: #f5f5f5;
}

.tab.active {
    color: #007bff;
    border-bottom-color: #007bff;
    font-weight: 600;
}
</style>

<?php renderFooter();
