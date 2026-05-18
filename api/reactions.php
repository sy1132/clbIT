<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure reactions table exists
db()->exec("CREATE TABLE IF NOT EXISTS reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    emoji VARCHAR(10) NOT NULL DEFAULT '👍',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reaction (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get reactions for a post (or multiple posts)
    $postIds = $_GET['post_ids'] ?? ($_GET['post_id'] ?? '');

    if ($postIds === '') {
        echo json_encode(['success' => false, 'error' => 'Missing post_id']);
        exit;
    }

    // Support comma-separated IDs
    $ids = array_map('intval', explode(',', (string) $postIds));
    $ids = array_filter($ids, fn($id) => $id > 0);

    if (empty($ids)) {
        echo json_encode(['success' => false, 'error' => 'Invalid post_id']);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Get all reactions grouped by post
    $stmt = db()->prepare("SELECT post_id, emoji, COUNT(*) as cnt FROM reactions WHERE post_id IN ($placeholders) GROUP BY post_id, emoji");
    $stmt->execute(array_values($ids));
    $rows = $stmt->fetchAll();

    $result = [];
    foreach ($rows as $row) {
        $pid = (int) $row['post_id'];
        if (!isset($result[$pid])) {
            $result[$pid] = ['counts' => [], 'total' => 0, 'user_emoji' => null];
        }
        $result[$pid]['counts'][$row['emoji']] = (int) $row['cnt'];
        $result[$pid]['total'] += (int) $row['cnt'];
    }

    // Get current user's reaction
    $userId = current_user_id();
    if ($userId) {
        $userStmt = db()->prepare("SELECT post_id, emoji FROM reactions WHERE post_id IN ($placeholders) AND user_id = ?");
        $params = array_values($ids);
        $params[] = $userId;
        $userStmt->execute($params);
        $userReactions = $userStmt->fetchAll();
        foreach ($userReactions as $ur) {
            $pid = (int) $ur['post_id'];
            if (isset($result[$pid])) {
                $result[$pid]['user_emoji'] = $ur['emoji'];
            }
        }
    }

    // Fill missing posts with empty data
    foreach ($ids as $id) {
        if (!isset($result[$id])) {
            $result[$id] = ['counts' => new \stdClass(), 'total' => 0, 'user_emoji' => null];
        } elseif (empty($result[$id]['counts'])) {
            $result[$id]['counts'] = new \stdClass();
        }
    }

    echo json_encode(['success' => true, 'reactions' => $result]);
    exit;
}

if ($method === 'POST') {
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Vui lòng đăng nhập']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $postId = (int) ($input['post_id'] ?? 0);
    $emoji = trim($input['emoji'] ?? '👍');

    $allowedEmojis = ['👍', '❤️', '😂', '😮', '😢', '😡'];
    if (!in_array($emoji, $allowedEmojis, true)) {
        echo json_encode(['success' => false, 'error' => 'Invalid emoji']);
        exit;
    }

    if ($postId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid post_id']);
        exit;
    }

    $userId = current_user_id();

    // Check if user already reacted
    $checkStmt = db()->prepare('SELECT id, emoji FROM reactions WHERE post_id = :post_id AND user_id = :user_id');
    $checkStmt->execute(['post_id' => $postId, 'user_id' => $userId]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        if ($existing['emoji'] === $emoji) {
            // Same emoji → remove reaction (toggle off)
            $deleteStmt = db()->prepare('DELETE FROM reactions WHERE id = :id');
            $deleteStmt->execute(['id' => $existing['id']]);
            $action = 'removed';
            $userEmoji = null;
        } else {
            // Different emoji → update
            $updateStmt = db()->prepare('UPDATE reactions SET emoji = :emoji WHERE id = :id');
            $updateStmt->execute(['emoji' => $emoji, 'id' => $existing['id']]);
            $action = 'updated';
            $userEmoji = $emoji;
        }
    } else {
        // New reaction
        $insertStmt = db()->prepare('INSERT INTO reactions (post_id, user_id, emoji) VALUES (:post_id, :user_id, :emoji)');
        $insertStmt->execute(['post_id' => $postId, 'user_id' => $userId, 'emoji' => $emoji]);
        $action = 'added';
        $userEmoji = $emoji;
    }

    // Return updated counts
    $countStmt = db()->prepare('SELECT emoji, COUNT(*) as cnt FROM reactions WHERE post_id = :post_id GROUP BY emoji');
    $countStmt->execute(['post_id' => $postId]);
    $countRows = $countStmt->fetchAll();

    $counts = new \stdClass();
    $total = 0;
    foreach ($countRows as $cr) {
        $counts->{$cr['emoji']} = (int) $cr['cnt'];
        $total += (int) $cr['cnt'];
    }

    echo json_encode([
        'success' => true,
        'action' => $action,
        'user_emoji' => $userEmoji,
        'counts' => $counts,
        'total' => $total,
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
