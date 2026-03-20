<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/layout.php';
require_once __DIR__ . '/auth.php';

requireAdmin();

$action = strtolower(trim((string) ($_GET['action'] ?? '')));
$message = '';
$messageType = '';

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    requireCsrfToken('/CAPSTONE/admin/user_management.php?action=create');

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $name = trim((string) ($_POST['name'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $role = strtolower(trim((string) ($_POST['role'] ?? DEFAULT_ROLE)));

    if (!isSchoolDomain($email)) {
        $message = 'Only @g.batstate-u.edu.ph email addresses are allowed.';
        $messageType = 'error';
    } elseif ($name === '') {
        $message = 'Name is required.';
        $messageType = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters.';
        $messageType = 'error';
    } elseif (!in_array($role, ALLOWED_ROLES, true)) {
        $message = 'Invalid role selected.';
        $messageType = 'error';
    } else {
        $newUser = registerUser(db(), $email, $name, $password, $role);
        if ($newUser !== null) {
            flash('success', 'User created successfully: ' . htmlspecialchars($newUser['name']) . ' (' . $newUser['role'] . ')');
            redirect('/CAPSTONE/admin/user_management.php');
        } else {
            $message = 'Failed to create user. Email may already exist.';
            $messageType = 'error';
        }
    }
}

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_role') {
    requireCsrfToken('/CAPSTONE/admin/user_management.php?action=update_role');

    $userId = (int) ($_POST['user_id'] ?? 0);
    $newRole = strtolower(trim((string) ($_POST['role'] ?? '')));

    if ($userId === 0) {
        $message = 'Invalid user ID.';
        $messageType = 'error';
    } elseif (!in_array($newRole, ALLOWED_ROLES, true)) {
        $message = 'Invalid role selected.';
        $messageType = 'error';
    } else {
        try {
            $stmt = db()->prepare('UPDATE users SET role = :role WHERE id = :id');
            $stmt->execute([':role' => $newRole, ':id' => $userId]);
            flash('success', 'User role updated successfully.');
            redirect('/CAPSTONE/admin/user_management.php');
        } catch (PDOException $e) {
            $message = 'Failed to update user role.';
            $messageType = 'error';
        }
    }
}

// Handle user deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'deactivate') {
    requireCsrfToken('/CAPSTONE/admin/user_management.php?action=deactivate');

    $userId = (int) ($_POST['user_id'] ?? 0);

    if ($userId === 0) {
        $message = 'Invalid user ID.';
        $messageType = 'error';
    } else {
        try {
            $stmt = db()->prepare('UPDATE users SET is_active = 0 WHERE id = :id');
            $stmt->execute([':id' => $userId]);
            flash('success', 'User deactivated successfully.');
            redirect('/CAPSTONE/admin/user_management.php');
        } catch (PDOException $e) {
            $message = 'Failed to deactivate user.';
            $messageType = 'error';
        }
    }
}

// Handle user reactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reactivate') {
    requireCsrfToken('/CAPSTONE/admin/user_management.php?action=reactivate');

    $userId = (int) ($_POST['user_id'] ?? 0);

    if ($userId === 0) {
        $message = 'Invalid user ID.';
        $messageType = 'error';
    } else {
        try {
            $stmt = db()->prepare('UPDATE users SET is_active = 1 WHERE id = :id');
            $stmt->execute([':id' => $userId]);
            flash('success', 'User reactivated successfully.');
            redirect('/CAPSTONE/admin/user_management.php');
        } catch (PDOException $e) {
            $message = 'Failed to reactivate user.';
            $messageType = 'error';
        }
    }
}

// Get all users
$users = db()->query('SELECT id, email, name, role, is_active, created_at, last_login_at FROM users ORDER BY created_at DESC')->fetchAll();

$tab = strtolower(trim((string) ($_GET['tab'] ?? 'list')));
if (!in_array($tab, ['list', 'create'], true)) {
    $tab = 'list';
}

renderHeader('User Management');
?>
<section class="section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1>User Management</h1>
        <a href="/CAPSTONE/admin/dashboard.php" class="btn ghost">Back to Dashboard</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 1rem;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="tabs-container" style="margin-bottom: 2rem;">
        <a href="/CAPSTONE/admin/user_management.php?tab=list" class="tab-link <?php echo $tab === 'list' ? 'active' : ''; ?>">All Users</a>
        <a href="/CAPSTONE/admin/user_management.php?tab=create" class="tab-link <?php echo $tab === 'create' ? 'active' : ''; ?>">Create New User</a>
    </div>

    <?php if ($tab === 'create'): ?>
    <div class="card" style="max-width: 500px; margin: 0 auto;">
        <h2>Create New User</h2>
        <form method="post" action="/CAPSTONE/admin/user_management.php?action=create">
            <?php echo csrfField(); ?>

            <label for="name">Full Name *</label>
            <input type="text" id="name" name="name" required>

            <label for="create_email">Email *</label>
            <input type="email" id="create_email" name="email" placeholder="name@g.batstate-u.edu.ph" required>

            <label for="create_password">Password *</label>
            <input type="password" id="create_password" name="password" placeholder="At least 6 characters" required>

            <label for="create_role">Role *</label>
            <select id="create_role" name="role" required>
                <option value="student">Student</option>
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
            </select>

            <button type="submit" style="margin-top: 1rem;">Create User</button>
        </form>
    </div>

    <?php else: ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) $user['email']); ?></td>
                        <td><?php echo htmlspecialchars((string) $user['name']); ?></td>
                        <td>
                            <form method="post" action="/CAPSTONE/admin/user_management.php?action=update_role" style="display: inline;">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                <select name="role" onchange="this.form.submit()">
                                    <?php foreach (ALLOWED_ROLES as $role): ?>
                                        <option value="<?php echo $role; ?>" <?php echo $user['role'] === $role ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($role); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td>
                            <span class="badge <?php echo (int) $user['is_active'] === 1 ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo (int) $user['is_active'] === 1 ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime((string) $user['created_at'])); ?></td>
                        <td><?php echo $user['last_login_at'] ? date('M d, Y H:i', strtotime((string) $user['last_login_at'])) : 'Never'; ?></td>
                        <td>
                            <?php if ((int) $user['is_active'] === 1): ?>
                                <form method="post" action="/CAPSTONE/admin/user_management.php?action=deactivate" style="display: inline;">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Deactivate this user?');">Deactivate</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="/CAPSTONE/admin/user_management.php?action=reactivate" style="display: inline;">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">Reactivate</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #999;">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

<style>
.table-container {
    overflow-x: auto;
    margin-top: 1rem;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.95rem;
}

table th {
    background-color: #f5f5f5;
    padding: 0.75rem;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid #ddd;
}

table td {
    padding: 0.75rem;
    border-bottom: 1px solid #eee;
}

table tbody tr:hover {
    background-color: #f9f9f9;
}

.badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-active {
    background-color: #d4edda;
    color: #155724;
}

.badge-inactive {
    background-color: #f8d7da;
    color: #721c24;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
}

.btn-success {
    background-color: #28a745;
    color: white;
}

.btn-success:hover {
    background-color: #218838;
}

.tabs-container {
    display: flex;
    gap: 0;
    border-bottom: 2px solid #eee;
}

.tab-link {
    padding: 0.75rem 1.5rem;
    text-decoration: none;
    color: #666;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
    font-weight: 500;
}

.tab-link:hover {
    color: #333;
}

.tab-link.active {
    color: #007bff;
    border-bottom-color: #007bff;
}

.alert {
    padding: 1rem;
    border-radius: 4px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

select {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
}
</style>

<?php renderFooter();
