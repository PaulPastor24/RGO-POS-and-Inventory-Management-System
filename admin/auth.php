<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

function currentUser(): ?array
{
    $user = $_SESSION['auth_user'] ?? null;
    return is_array($user) ? $user : null;
}

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

function signInUser(string $email, string $name, string $role): void
{
    $_SESSION['auth_user'] = [
        'email' => strtolower(trim($email)),
        'name' => trim($name) !== '' ? trim($name) : strtolower(trim($email)),
        'role' => $role,
    ];
}

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

function attemptLocalLogin(string $email, string $password): ?array
{
    $normalizedEmail = strtolower(trim($email));
    $role = resolveAuthorizedRole($normalizedEmail);

    if ($normalizedEmail === '' || $password === '' || $role === null) {
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

function isLoggedIn(): bool
{
    return currentUser() !== null;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        flash('error', 'Please sign in using your BatState-U Workspace account.');
        redirect('/CAPSTONE/admin/login.php');
    }
}

function requireRole(string $role): void
{
    requireLogin();
    $user = currentUser();

    if (($user['role'] ?? '') !== $role) {
        flash('error', 'Access denied for your account role.');
        redirect('/CAPSTONE/admin/login.php');
    }
}

function requireAdmin(): void
{
    requireRole('admin');
}

function requireStaff(): void
{
    requireRole('staff');
}
