<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure table exists
db()->exec("CREATE TABLE IF NOT EXISTS community_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content VARCHAR(500) NOT NULL,
    status ENUM('visible', 'hidden') NOT NULL DEFAULT 'visible',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_community_messages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_community_messages_status (status),
    INDEX idx_community_messages_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $afterId = (int) ($_GET['after_id'] ?? 0);

    $sql = "SELECT m.id, m.user_id, m.content, m.created_at, u.fullname, u.avatar, u.role
            FROM community_messages m
            INNER JOIN users u ON u.id = m.user_id
            WHERE m.status = 'visible'";
    $params = [];

    if ($afterId > 0) {
        $sql .= ' AND m.id > :after_id';
        $params['after_id'] = $afterId;
    }

    $sql .= ' ORDER BY m.created_at ASC';

    // If loading all (after_id = 0), limit to last 100
    if ($afterId === 0) {
        $sql .= ' LIMIT 100';
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $messages = $stmt->fetchAll();

    $currentUserId = current_user_id();
    $formatted = [];

    foreach ($messages as $m) {
        $avatarUrl = $m['avatar']
            ? UPLOAD_URL . '/' . $m['avatar']
            : BASE_URL . '/assets/images/default-avatar.svg';

        $formatted[] = [
            'id' => (int) $m['id'],
            'user_id' => (int) $m['user_id'],
            'fullname' => $m['fullname'],
            'avatar' => $avatarUrl,
            'role' => $m['role'],
            'content' => $m['content'],
            'created_at' => format_datetime($m['created_at']),
            'is_mine' => $currentUserId !== null && (int) $m['user_id'] === $currentUserId,
        ];
    }

    echo json_encode([
        'success' => true,
        'messages' => $formatted,
        'count' => count($formatted),
    ]);
    exit;
}

if ($method === 'POST') {
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Vui lòng đăng nhập']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $content = trim($input['content'] ?? '');

    if ($content === '') {
        echo json_encode(['success' => false, 'error' => 'Vui lòng nhập nội dung tin nhắn']);
        exit;
    }

    if (mb_strlen($content) > 500) {
        echo json_encode(['success' => false, 'error' => 'Tin nhắn không được dài quá 500 ký tự']);
        exit;
    }

    $stmt = db()->prepare('INSERT INTO community_messages (user_id, content, status) VALUES (:user_id, :content, :status)');
    $stmt->execute([
        'user_id' => current_user_id(),
        'content' => $content,
        'status' => 'visible',
    ]);

    $msgId = (int) db()->lastInsertId();
    $user = current_user_row();
    $avatarUrl = ($user && $user['avatar'])
        ? UPLOAD_URL . '/' . $user['avatar']
        : BASE_URL . '/assets/images/default-avatar.svg';

    echo json_encode([
        'success' => true,
        'message' => [
            'id' => $msgId,
            'user_id' => current_user_id(),
            'fullname' => current_user_name(),
            'avatar' => $avatarUrl,
            'role' => $user['role'] ?? 'member',
            'content' => $content,
            'created_at' => format_datetime(date('Y-m-d H:i:s')),
            'is_mine' => true,
        ],
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
