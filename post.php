<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageLayout = 'public';
$pageTitle = 'Chi tiết bài viết';

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT p.*, c.name AS category_name, u.fullname AS author_name, u.avatar AS author_avatar
    FROM posts p
    INNER JOIN categories c ON c.id = p.category_id
    INNER JOIN users u ON u.id = p.user_id
    WHERE p.id = :id AND p.status = 'published'");
$stmt->execute(['id' => $id]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    echo 'Không tìm thấy bài viết.';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    $content = trim($_POST['content'] ?? '');

    if ($content === '') {
        flash_set('error', 'Vui lòng nhập nội dung bình luận.');
        redirect('/post.php?id=' . $id);
    }

    $commentStatus = current_user_role() === 'admin' ? 'approved' : 'pending';
    $commentStmt = db()->prepare('INSERT INTO comments (post_id, user_id, content, status) VALUES (:post_id, :user_id, :content, :status)');
    $commentStmt->execute([
        'post_id' => $id,
        'user_id' => current_user_id(),
        'content' => $content,
        'status' => $commentStatus,
    ]);

    flash_set('success', $commentStatus === 'approved' ? 'Bình luận đã được đăng.' : 'Bình luận đã được gửi để duyệt.');
    redirect('/post.php?id=' . $id);
}

$viewerUserId = current_user_id() ?? 0;
$commentStmt = db()->prepare("SELECT c.*, u.fullname, u.avatar FROM comments c INNER JOIN users u ON u.id = c.user_id WHERE c.post_id = :post_id AND (c.status = 'approved' OR (:viewer_user_id_check > 0 AND c.user_id = :viewer_user_id_filter)) ORDER BY c.created_at DESC");
$commentStmt->execute([
    'post_id' => $id,
    'viewer_user_id_check' => $viewerUserId,
    'viewer_user_id_filter' => $viewerUserId,
]);
$comments = $commentStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="clubit-card p-4 mb-4">
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <span class="soft-badge"><?php echo e($post['category_name']); ?></span>
        <span class="text-secondary small"><i class="bi bi-person"></i> <?php echo e($post['author_name']); ?></span>
        <span class="text-secondary small"><i class="bi bi-clock"></i> <?php echo e(format_datetime($post['published_at'] ?? $post['created_at'])); ?></span>
    </div>
    <h1 class="section-title mb-3"><?php echo e($post['title']); ?></h1>
    <p class="lead text-secondary mb-4"><?php echo e($post['excerpt']); ?></p>
    <?php if (!empty($post['image'])): ?>
        <img src="<?php echo e(UPLOAD_URL . '/' . $post['image']); ?>" alt="<?php echo e($post['title']); ?>" class="card-media mb-4" style="aspect-ratio: 21 / 9;">
    <?php endif; ?>
    <div class="prose">
        <?php echo $post['content']; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="clubit-card p-4">
            <h3 class="section-title mb-4">Bình luận</h3>
            <?php if (is_logged_in()): ?>
                <form method="post" class="mb-4">
                    <label class="form-label">Viết bình luận</label>
                    <textarea name="content" class="form-control mb-3" rows="4" placeholder="Chia sẻ ý kiến của bạn..."></textarea>
                    <button class="btn btn-primary">Gửi bình luận</button>
                </form>
            <?php else: ?>
                <div class="alert alert-info">Đăng nhập để bình luận và tham gia thảo luận.</div>
            <?php endif; ?>

            <div class="d-grid gap-3">
                <?php foreach ($comments as $comment): ?>
                    <div class="border rounded-4 p-3 bg-white bg-opacity-50">
                        <div class="d-flex gap-3 align-items-start">
                            <img src="<?php echo e($comment['avatar'] ? UPLOAD_URL . '/' . $comment['avatar'] : BASE_URL . '/assets/images/default-avatar.svg'); ?>" alt="<?php echo e($comment['fullname']); ?>" width="48" height="48" class="rounded-circle">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between gap-2 flex-wrap">
                                    <div class="fw-semibold"><?php echo e($comment['fullname']); ?></div>
                                    <span class="badge <?php echo e(badge_class_for_status($comment['status'])); ?>"><?php echo e($comment['status']); ?></span>
                                </div>
                                <div class="text-secondary small mb-2"><?php echo e(format_datetime($comment['created_at'])); ?></div>
                                <div><?php echo nl2br(e($comment['content'])); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$comments): ?>
                    <div class="text-center text-secondary">Chưa có bình luận nào.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="clubit-card p-4">
            <h3 class="section-title mb-3">Tác giả</h3>
            <div class="d-flex gap-3 align-items-center">
                <img src="<?php echo e($post['author_avatar'] ? UPLOAD_URL . '/' . $post['author_avatar'] : BASE_URL . '/assets/images/default-avatar.svg'); ?>" width="64" height="64" class="rounded-circle" alt="<?php echo e($post['author_name']); ?>">
                <div>
                    <div class="fw-semibold"><?php echo e($post['author_name']); ?></div>
                    <div class="text-secondary small">Thành viên CLB IT</div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
