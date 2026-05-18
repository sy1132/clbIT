<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    $offset = (int) ($_GET['offset'] ?? 0);
    $limit = (int) ($_GET['limit'] ?? 3);
    $seed = (int) ($_SESSION['posts_seed'] ?? 123456);

    $currentUserId = current_user_id() ?? 0;
    // Prepare and execute with bound parameters
    $stmt = db()->prepare("SELECT p.id, p.title, p.excerpt, p.image, p.images, p.files, p.published_at, p.created_at, p.privacy, c.name AS category_name, u.fullname AS author_name, u.avatar AS author_avatar 
        FROM posts p 
        INNER JOIN categories c ON c.id = p.category_id 
        INNER JOIN users u ON u.id = p.user_id 
        WHERE p.status = 'published' AND (p.privacy = 'public' OR p.user_id = :current_user_id)
        ORDER BY RAND(:seed) 
        LIMIT :limit OFFSET :offset");

    $stmt->bindValue(':current_user_id', $currentUserId, PDO::PARAM_INT);
    $stmt->bindValue(':seed', $seed, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll();

    $formattedPosts = [];
    foreach ($posts as $post) {
        $images = $post['images'] ? json_decode($post['images'], true) : [];
        $files = $post['files'] ? json_decode($post['files'], true) : [];

        $imagesUrls = array_map(function($img) {
            return UPLOAD_URL . '/' . e($img);
        }, $images);

        $formattedPosts[] = [
            'id' => (int) $post['id'],
            'title' => e($post['title']),
            'excerpt' => e($post['excerpt']),
            'image' => $post['image'] ? e($post['image']) : null,
            'image_url' => $post['image'] ? UPLOAD_URL . '/' . e($post['image']) : null,
            'images' => $images,
            'images_urls' => $imagesUrls,
            'files' => $files,
            'privacy' => $post['privacy'] ?? 'public',
            'published_at' => $post['published_at'] ?? $post['created_at'],
            'formatted_date' => format_datetime($post['published_at'] ?? $post['created_at'] ?? ''),
            'category_name' => e($post['category_name']),
            'author_name' => e($post['author_name']),
            'author_avatar' => $post['author_avatar'] ? e($post['author_avatar']) : null,
            'author_avatar_url' => $post['author_avatar'] ? UPLOAD_URL . '/' . e($post['author_avatar']) : null,
        ];
    }

    echo json_encode([
        'success' => true,
        'posts' => $formattedPosts,
        'has_more' => count($formattedPosts) === $limit
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
