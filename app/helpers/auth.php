<?php

function attempt_login(string $username, string $password): bool
{
    $rows = supabase_rest('GET', 'admins', [
        'select' => 'id,username,password_hash',
        'username' => 'eq.' . $username,
        'limit' => '1',
    ]);
    $admin = $rows[0] ?? null;

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];

    return true;
}

function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

function logout_admin(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}
