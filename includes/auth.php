<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $db = get_db();
    $stmt = $db->prepare('SELECT id, name, email, role, impact_co2, impact_waste, bio, avatar_url FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    return $user ?: null;
}

function find_user_by_email(string $email): ?array
{
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    return $user ?: null;
}

function login(string $email, string $password): ?array
{
    $user = find_user_by_email($email);
    if (!$user || !password_verify($password, $user['password'])) {
        return null;
    }

    $_SESSION['user_id'] = $user['id'];
    return $user;
}

function register_user(string $name, string $email, string $password, string $bio = ''): ?array
{
    $db = get_db();
    $existing = find_user_by_email($email);
    if ($existing) {
        return null;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (name, email, password, bio) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $name, $email, $hash, $bio);

    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $userId = $stmt->insert_id;
    $stmt->close();

    $_SESSION['user_id'] = $userId;
    return current_user();
}

function require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    $user = current_user();
    if (!$user || $user['role'] !== 'admin') {
        header('Location: dashboard.php');
        exit;
    }
}

function sanitize(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function update_impact(int $userId, float $co2, float $waste): void
{
    $db = get_db();
    $stmt = $db->prepare('UPDATE users SET impact_co2 = impact_co2 + ?, impact_waste = impact_waste + ? WHERE id = ?');
    $stmt->bind_param('ddi', $co2, $waste, $userId);
    $stmt->execute();
    $stmt->close();
}
