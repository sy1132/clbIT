<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function current_user_row(): ?array
{
    if (!isset($_SESSION['user']['id'])) {
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => (int) $_SESSION['user']['id']]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'active') {
        unset($_SESSION['user']);
        return null;
    }

    return $user;
}

function is_logged_in(): bool
{
    return current_user_row() !== null;
}

function current_user_id(): ?int
{
    $user = current_user_row();
    return $user ? (int) $user['id'] : null;
}

function current_user_role(): ?string
{
    $user = current_user_row();
    return $user ? (string) $user['role'] : null;
}

function current_user_name(): string
{
    $user = current_user_row();
    return $user ? (string) $user['full_name'] : 'Khách';
}

function current_user_avatar(): string
{
    $user = current_user_row();
    if (!$user || !$user['avatar']) {
        return BASE_URL . '/assets/images/default-avatar.svg';
    }

    return UPLOAD_URL . '/' . ltrim((string) $user['avatar'], '/');
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('/login.php');
    }
}

function require_admin(): void
{
    require_login();
    if (current_user_role() !== 'admin') {
        http_response_code(403);
        echo 'Bạn không có quyền truy cập.';
        exit;
    }
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function flash_get(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function old(string $key, string $default = ''): string
{
    if (isset($_POST[$key])) {
        return trim((string) $_POST[$key]);
    }

    if (isset($_GET[$key])) {
        return trim((string) $_GET[$key]);
    }

    return $default;
}

function money(float $amount): string
{
    return number_format($amount, 0, ',', '.') . ' đ';
}

function format_datetime(?string $value): string
{
    if (!$value) {
        return '';
    }

    return date('d/m/Y H:i', strtotime($value));
}

function format_date(?string $value): string
{
    if (!$value) {
        return '';
    }

    return date('d/m/Y', strtotime($value));
}

function short_text(?string $value, int $limit = 140): string
{
    $value = trim((string) $value);
    if (mb_strlen($value) <= $limit) {
        return $value;
    }

    return mb_substr($value, 0, $limit - 3) . '...';
}

function slugify(string $value): string
{
    $value = trim(mb_strtolower($value));
    $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value) ?: $value;
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? $value;

    return trim($value, '-') ?: 'item';
}

function upload_file(string $field, array $allowedExtensions): ?string
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($_FILES[$field]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Tải file thất bại.');
    }

    $originalName = (string) ($_FILES[$field]['name'] ?? '');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('Định dạng file không hợp lệ.');
    }

    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0777, true);
    }

    $safeName = bin2hex(random_bytes(8)) . '_' . time() . '.' . $extension;
    $targetPath = UPLOAD_PATH . '/' . $safeName;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath)) {
        throw new RuntimeException('Không thể lưu file tải lên.');
    }

    return $safeName;
}

function delete_uploaded_file(?string $relativePath): void
{
    if (!$relativePath) {
        return;
    }

    $fullPath = UPLOAD_PATH . '/' . ltrim($relativePath, '/');
    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}

function badge_class_for_status(string $status): string
{
    return match ($status) {
        'active', 'published', 'approved' => 'text-bg-success',
        'locked', 'draft', 'pending' => 'text-bg-warning',
        'archived', 'spam' => 'text-bg-secondary',
        default => 'text-bg-primary',
    };
}

function safe_page(int $value, int $min = 1): int
{
    return max($min, $value);
}
