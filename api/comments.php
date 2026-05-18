<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $postId = (int) ($_GET['post_id'] ?? 0);
    $limit = min(20, max(1, (int) ($_GET['limit'] ?? 5)));
    $offset = max(0, (int) ($_GET['offset'] ?? 0));

    if ($postId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid post_id']);
        exit;
    }

    // Total count
    $countStmt = db()->prepare("SELECT COUNT(*) FROM comments WHERE post_id = :post_id AND status = 'approved'");
    $countStmt->execute(['post_id' => $postId]);
    $total = (int) $countStmt->fetchColumn();

    // Fetch comments
    $stmt = db()->prepare("SELECT c.id, c.content, c.created_at, u.fullname, u.avatar
        FROM comments c
        INNER JOIN users u ON u.id = c.user_id
        WHERE c.post_id = :post_id AND c.status = 'approved'
        ORDER BY c.created_at DESC
        LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $comments = $stmt->fetchAll();

    // Format
    $formatted = [];
    foreach ($comments as $c) {
        $avatarUrl = $c['avatar']
            ? UPLOAD_URL . '/' . $c['avatar']
            : BASE_URL . '/assets/images/default-avatar.svg';

        $formatted[] = [
            'id' => (int) $c['id'],
            'fullname' => $c['fullname'],
            'avatar' => $avatarUrl,
            'content' => $c['content'],
            'created_at' => format_datetime($c['created_at']),
        ];
    }

    echo json_encode([
        'success' => true,
        'comments' => $formatted,
        'total' => $total,
        'has_more' => ($offset + $limit) < $total,
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
    $postId = (int) ($input['post_id'] ?? 0);
    $content = trim($input['content'] ?? '');

    if ($postId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid post_id']);
        exit;
    }

    if ($content === '') {
        echo json_encode(['success' => false, 'error' => 'Vui lòng nhập nội dung bình luận']);
        exit;
    }

    if (mb_strlen($content) > 1000) {
        echo json_encode(['success' => false, 'error' => 'Bình luận quá dài (tối đa 1000 ký tự)']);
        exit;
    }

    // Insert
    $stmt = db()->prepare('INSERT INTO comments (post_id, user_id, content, status) VALUES (:post_id, :user_id, :content, :status)');
    $stmt->execute([
        'post_id' => $postId,
        'user_id' => current_user_id(),
        'content' => $content,
        'status' => 'approved',
    ]);

    $commentId = (int) db()->lastInsertId();

    // Return the new comment
    $user = current_user_row();
    $avatarUrl = ($user && $user['avatar'])
        ? UPLOAD_URL . '/' . $user['avatar']
        : BASE_URL . '/assets/images/default-avatar.svg';

    echo json_encode([
        'success' => true,
        'comment' => [
            'id' => $commentId,
            'fullname' => current_user_name(),
            'avatar' => $avatarUrl,
            'content' => $content,
            'created_at' => format_datetime(date('Y-m-d H:i:s')),
        ],
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
