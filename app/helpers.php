<?php

declare(strict_types=1);

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }

    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $message;
}

function getCart(): array
{
    return $_SESSION['cart'] ?? [];
}

function setCart(array $cart): void
{
    $_SESSION['cart'] = $cart;
}

function cartCount(): int
{
    return array_sum(getCart());
}

function formatPeso(float $amount): string
{
    return 'PHP ' . number_format($amount, 2);
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function requireCsrfToken(string $redirectPath): void
{
    $submitted = (string) ($_POST['csrf_token'] ?? '');
    $current = (string) ($_SESSION['csrf_token'] ?? '');

    if ($submitted === '' || $current === '' || !hash_equals($current, $submitted)) {
        flash('error', 'Your session token is invalid or expired. Please try again.');
        redirect($redirectPath);
    }
}
