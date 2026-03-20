<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

/**
 * Validate if email is from the school domain
 */
function isSchoolDomain(string $email): bool
{
    $normalizedEmail = strtolower(trim($email));
    return str_ends_with($normalizedEmail, SCHOOL_DOMAIN);
}

/**
 * Get currently logged-in user
 */
function currentUser(): ?array
{
    $user = $_SESSION['auth_user'] ?? null;
    return is_array($user) ? $user : null;
}

/**
 * Resolve user role from database
 */
function resolveUserRole(PDO $pdo, string $email): ?string
{
    $normalizedEmail = strtolower(trim($email));

    if ($normalizedEmail === '') {
        return null;
    }

    $stmt = $pdo->prepare('SELECT role FROM users WHERE email = :email AND is_active = 1');
    $stmt->execute([':email' => $normalizedEmail]);
    $role = $stmt->fetchColumn();

    return $role !== false ? (string) $role : null;
}

/**
 * Get user from database by email
 */
function getUserByEmail(PDO $pdo, string $email): ?array
{
    $normalizedEmail = strtolower(trim($email));
    $stmt = $pdo->prepare('SELECT id, email, name, role, password_hash, is_active FROM users WHERE email = :email');
    $stmt->execute([':email' => $normalizedEmail]);
    $user = $stmt->fetch();

    return is_array($user) ? $user : null;
}

/**
 * Resolve authorized role from config constants
 */
function resolveAuthorizedRole(string $email): ?string
{
    $normalizedEmail = strtolower(trim($email));

    if ($normalizedEmail === '') {
        return null;
    }

    if (in_array($normalizedEmail, array_map('strtolower', ADMIN_EMAILS), true)) {
        return 'admin';
    }

    if (in_array($normalizedEmail, array_map('strtolower', STAFF_EMAILS), true)) {
        return 'staff';
    }

    return null;
}

/**
 * Sign in a user and set session
 */
function signInUser(string $email, string $name, string $role): void
{
    $_SESSION['auth_user'] = [
        'email' => strtolower(trim($email)),
        'name' => trim($name) !== '' ? trim($name) : strtolower(trim($email)),
        'role' => $role,
    ];
}

/**
 * Update last login timestamp and IP
 */
function updateLastLogin(PDO $pdo, string $email): void
{
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $stmt = $pdo->prepare(
        'UPDATE users SET last_login_at = :last_login_at, last_login_ip = :last_login_ip WHERE email = :email'
    );
    $stmt->execute([
        ':last_login_at' => date('Y-m-d H:i:s'),
        ':last_login_ip' => $clientIp,
        ':email' => strtolower(trim($email)),
    ]);
}

/**
 * Register a new user with school domain
 */
function registerUser(PDO $pdo, string $email, string $name, string $password, string $role = DEFAULT_ROLE): ?array
{
    $normalizedEmail = strtolower(trim($email));
    $trimmedName = trim($name);
    $trimmedPassword = trim($password);

    // Validate inputs
    if ($normalizedEmail === '' || $trimmedName === '' || $trimmedPassword === '') {
        return null;
    }

    // Validate school domain
    if (!isSchoolDomain($normalizedEmail)) {
        return null;
    }

    // Validate role
    if (!in_array($role, ALLOWED_ROLES, true)) {
        $role = DEFAULT_ROLE;
    }

    // Check if user already exists
    if (getUserByEmail($pdo, $normalizedEmail) !== null) {
        return null;
    }

    $passwordHash = password_hash($trimmedPassword, PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO users (email, name, role, password_hash, is_active, created_at) 
             VALUES (:email, :name, :role, :password_hash, 1, :created_at)'
        );
        $stmt->execute([
            ':email' => $normalizedEmail,
            ':name' => $trimmedName,
            ':role' => $role,
            ':password_hash' => $passwordHash,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'email' => $normalizedEmail,
            'name' => $trimmedName,
            'role' => $role,
        ];
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Redirect user based on their role
 */
function redirectForRole(string $role): void
{
    if ($role === 'admin') {
        redirect('/CAPSTONE/admin/dashboard.php');
    }

    if ($role === 'staff') {
        redirect('/CAPSTONE/staff/dashboard.php');
    }

    if ($role === 'student') {
        redirect('/CAPSTONE/student/dashboard.php');
    }

    redirect('/CAPSTONE/index.php');
}

/**
 * Attempt login with database user
 */
function attemptDatabaseLogin(PDO $pdo, string $email, string $password): ?array
{
    $normalizedEmail = strtolower(trim($email));
    $trimmedPassword = trim($password);

    // Validate inputs
    if ($normalizedEmail === '' || $trimmedPassword === '') {
        return null;
    }

    // Validate school domain
    if (!isSchoolDomain($normalizedEmail)) {
        return null;
    }

    // Get user from database
    $user = getUserByEmail($pdo, $normalizedEmail);
    if ($user === null) {
        return null;
    }

    // Verify password
    $passwordHash = (string) ($user['password_hash'] ?? '');
    if ($passwordHash === '' || !password_verify($trimmedPassword, $passwordHash)) {
        return null;
    }

    // Check if user is active
    if ((int) ($user['is_active'] ?? 0) !== 1) {
        return null;
    }

    // Update last login
    updateLastLogin($pdo, $normalizedEmail);

    return [
        'email' => $normalizedEmail,
        'name' => (string) ($user['name'] ?? $normalizedEmail),
        'role' => (string) ($user['role'] ?? DEFAULT_ROLE),
    ];
}

/**
 * Attempt local login (for demo/development)
 */
function attemptLocalLogin(string $email, string $password): ?array
{
    $normalizedEmail = strtolower(trim($email));
    $role = resolveAuthorizedRole($normalizedEmail);

    // Validate inputs
    if ($normalizedEmail === '' || $password === '' || $role === null) {
        return null;
    }

    // Validate school domain
    if (!isSchoolDomain($normalizedEmail)) {
        return null;
    }

    foreach (LOCAL_LOGIN_USERS as $account) {
        $accountEmail = strtolower(trim((string) ($account['email'] ?? '')));
        if ($accountEmail !== $normalizedEmail) {
            continue;
        }

        $passwordHash = (string) ($account['password_hash'] ?? '');
        if ($passwordHash === '' || !password_verify($password, $passwordHash)) {
            return null;
        }

        return [
            'email' => $normalizedEmail,
            'name' => (string) ($account['name'] ?? $normalizedEmail),
            'role' => $role,
        ];
    }

    return null;
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool
{
    return currentUser() !== null;
}

/**
 * Require user to be logged in
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        flash('error', 'Please sign in using your BatState-U Workspace account.');
        redirect('/CAPSTONE/admin/login.php');
    }
}

/**
 * Require specific role
 */
function requireRole(string $role): void
{
    requireLogin();
    $user = currentUser();

    if (($user['role'] ?? '') !== $role) {
        flash('error', 'Access denied for your account role.');
        redirect('/CAPSTONE/admin/login.php');
    }
}

/**
 * Require admin role
 */
function requireAdmin(): void
{
    requireRole('admin');
}

/**
 * Require staff role
 */
function requireStaff(): void
{
    requireRole('staff');
}

/**
 * Require student role
 */
function requireStudent(): void
{
    requireRole('student');
}

/**
 * Logout user
 */
function logout(): void
{
    session_destroy();
    redirect('/CAPSTONE/index.php');
}
