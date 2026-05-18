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
    WHERE p.id = :id");
$stmt->execute(['id' => $id]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    echo 'Không tìm thấy bài viết.';
    exit;
}

$isAuthor = (current_user_id() !== null && (int)$post['user_id'] === current_user_id());
$isAdmin = (current_user_role() === 'admin');

if ($post['status'] !== 'published' && !$isAuthor && !$isAdmin) {
    http_response_code(403);
    echo 'Bài viết này đang chờ phê duyệt từ Ban quản trị và không công khai.';
    exit;
}

if (($post['privacy'] ?? 'public') === 'private' && !$isAuthor && !$isAdmin) {
    http_response_code(403);
    echo 'Bạn không có quyền xem bài viết này.';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    $content = trim($_POST['content'] ?? '');

    if ($content === '') {
        flash_set('error', 'Vui lòng nhập nội dung bình luận.');
        redirect('/post.php?id=' . $id);
    }

    $commentStmt = db()->prepare('INSERT INTO comments (post_id, user_id, content, status) VALUES (:post_id, :user_id, :content, :status)');
    $commentStmt->execute([
        'post_id' => $id,
        'user_id' => current_user_id(),
        'content' => $content,
        'status' => 'approved',
    ]);

    flash_set('success', 'Bình luận đã được đăng.');
    redirect('/post.php?id=' . $id);
}

$commentStmt = db()->prepare("SELECT c.*, u.fullname, u.avatar FROM comments c INNER JOIN users u ON u.id = c.user_id WHERE c.post_id = :post_id AND c.status = 'approved' ORDER BY c.created_at DESC");
$commentStmt->execute(['post_id' => $id]);
$comments = $commentStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="row g-4 animate-on-scroll">
    <div class="col-lg-8">
        <?php if ($post['status'] !== 'published'): ?>
            <div class="alert alert-warning border border-warning border-opacity-25 bg-warning bg-opacity-10 text-warning d-flex align-items-center gap-3 p-3.5 rounded-4 mb-4 shadow-sm">
                <div class="fs-4"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div>
                    <h6 class="alert-heading fw-bold mb-1">⚠️ Bài viết chưa được công khai</h6>
                    <p class="mb-0 small text-white-50">
                        Bài viết này đang ở trạng thái <strong><?php echo $post['status'] === 'pending' ? 'Chờ kiểm duyệt' : 'Bản nháp'; ?></strong>.
                        Chỉ có bạn (tác giả) và Ban quản trị mới có thể xem trang xem trước này.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Post Details -->
        <div class="clubit-card p-4 p-lg-5 mb-4">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div class="d-flex align-items-center gap-3">
                    <img src="<?php echo e($post['author_avatar'] ? UPLOAD_URL . '/' . $post['author_avatar'] : BASE_URL . '/assets/images/default-avatar.svg'); ?>" class="rounded-circle border border-2 border-primary border-opacity-25" width="56" height="56" alt="Avatar" style="object-fit: cover;">
                    <div>
                        <div class="fw-bold fs-5"><?php echo e($post['author_name']); ?></div>
                        <div class="small text-secondary d-flex align-items-center gap-1">
                            <i class="bi bi-clock"></i> <?php echo e(format_datetime($post['published_at'] ?? $post['created_at'])); ?> 
                            &middot; <?php if (($post['privacy'] ?? 'public') === 'private'): ?>
                                <i class="bi bi-lock-fill text-warning" title="Riêng tư"></i>
                            <?php else: ?>
                                <i class="bi bi-globe-americas text-primary" title="Công khai"></i>
                            <?php endif; ?>
                            &middot; <span class="badge bg-primary bg-opacity-10 text-primary ms-1"><?php echo e($post['category_name']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <h1 class="fw-bold mb-3"><?php echo e($post['title']); ?></h1>
            <p class="lead text-white-50 mb-4"><?php echo e($post['excerpt']); ?></p>

            <div class="prose text-white opacity-75 mb-4" style="font-size: 1.1rem; line-height: 1.8;">
                <?php echo $post['content']; ?>
            </div>

            <!-- Embedded Multi-Images Grid -->
            <?php 
            $postImages = !empty($post['images']) ? json_decode($post['images'], true) : [];
            if (!empty($postImages)): 
                $imgCount = count($postImages);
            ?>
                <div class="mb-4 post-media-grid grid-<?php echo min(4, $imgCount); ?>">
                    <?php foreach ($postImages as $index => $img): ?>
                        <div class="grid-item position-relative rounded-3 overflow-hidden">
                            <img src="<?php echo e(UPLOAD_URL . '/' . $img); ?>" class="w-100 h-100" style="object-fit: cover; max-height: <?php echo $imgCount === 1 ? '550px' : ($imgCount === 2 ? '380px' : '280px'); ?>;" alt="Post image" style="cursor: pointer;">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif (!empty($post['image'])): ?>
                <div class="mb-4 text-center bg-dark rounded-4 overflow-hidden">
                    <img src="<?php echo e(UPLOAD_URL . '/' . $post['image']); ?>" alt="<?php echo e($post['title']); ?>" class="img-fluid" style="max-height: 600px; object-fit: contain;">
                </div>
            <?php endif; ?>

            <!-- Embedded File Attachments -->
            <?php 
            $postFiles = !empty($post['files']) ? json_decode($post['files'], true) : [];
            if (!empty($postFiles)): 
            ?>
                <div class="post-attachments p-3 rounded-4 border border-white border-opacity-10 mb-4" style="background: rgba(255, 255, 255, 0.02);">
                    <div class="small text-white-50 fw-semibold mb-2.5"><i class="bi bi-paperclip"></i> Tài liệu đính kèm:</div>
                    <div class="d-flex flex-column gap-2.5">
                        <?php foreach ($postFiles as $f): 
                            $ext = strtolower($f['type'] ?? 'file');
                            $iconClass = 'bi-file-earmark';
                            $iconColor = 'text-secondary';

                            if ($ext === 'pdf') { $iconClass = 'bi-file-earmark-pdf-fill'; $iconColor = 'text-danger'; }
                            else if (in_array($ext, ['doc', 'docx'])) { $iconClass = 'bi-file-earmark-word-fill'; $iconColor = 'text-primary'; }
                            else if (in_array($ext, ['ppt', 'pptx'])) { $iconClass = 'bi-file-earmark-slides-fill'; $iconColor = 'text-warning'; }
                            else if (in_array($ext, ['xls', 'xlsx'])) { $iconClass = 'bi-file-earmark-excel-fill'; $iconColor = 'text-success'; }
                            else if (in_array($ext, ['zip', 'rar'])) { $iconClass = 'bi-file-earmark-zip-fill'; $iconColor = 'text-info'; }
                        ?>
                            <div class="d-flex align-items-center justify-content-between p-2.5 rounded-3 border border-white border-opacity-5" style="background: rgba(0, 0, 0, 0.15);">
                                <div class="d-flex align-items-center gap-3 overflow-hidden">
                                    <i class="bi <?php echo $iconClass; ?> <?php echo $iconColor; ?> fs-3"></i>
                                    <div class="overflow-hidden">
                                        <div class="text-white text-truncate fw-semibold" style="max-width: 320px;"><?php echo e($f['name']); ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?php echo e($f['size'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                <a href="<?php echo e(UPLOAD_URL . '/' . $f['path']); ?>" download="<?php echo e($f['name']); ?>" class="btn btn-primary py-1.5 px-4 rounded-pill fw-semibold fs-7 d-flex align-items-center gap-1.5 shadow-sm">
                                    <i class="bi bi-download"></i> Tải về
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Post Actions -->
            <div class="border-top border-bottom py-2 my-4 border-opacity-10 border-white d-flex justify-content-around">
                <button class="btn btn-lg text-white-50 flex-grow-1 hover-bg fw-semibold fs-6"><i class="bi bi-hand-thumbs-up me-2"></i> Thích</button>
                <button class="btn btn-lg text-white-50 flex-grow-1 hover-bg fw-semibold fs-6"><i class="bi bi-chat-dots me-2"></i> Bình luận</button>
                <button class="btn btn-lg text-white-50 flex-grow-1 hover-bg fw-semibold fs-6" onclick="navigator.clipboard.writeText(window.location.href); alert('Đã copy link!');"><i class="bi bi-share me-2"></i> Chia sẻ</button>
            </div>

            <!-- Comments Section -->
            <h5 class="fw-bold mb-4">Bình luận</h5>
            
            <?php if (is_logged_in()): ?>
                <div class="d-flex gap-3 mb-5">
                    <img src="<?php echo e(current_user_avatar()); ?>" class="rounded-circle" width="40" height="40" alt="Avatar" style="object-fit: cover;">
                    <form method="post" class="flex-grow-1">
                        <div class="position-relative">
                            <textarea name="content" class="form-control rounded-4 bg-dark border-secondary border-opacity-25 text-white pe-5 pt-3" rows="2" placeholder="Viết bình luận của bạn..." required></textarea>
                            <button type="submit" class="btn btn-primary position-absolute bottom-0 end-0 m-2 rounded-circle d-flex align-items-center justify-content-center p-0" style="width: 32px; height: 32px;">
                                <i class="bi bi-send-fill" style="font-size: 0.85rem;"></i>
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-info bg-opacity-10 border-info border-opacity-25 text-info mb-4">
                    <i class="bi bi-info-circle me-2"></i> Vui lòng <a href="<?php echo e(BASE_URL); ?>/login.php" class="fw-bold text-info">đăng nhập</a> để tham gia thảo luận.
                </div>
            <?php endif; ?>

            <div class="d-grid gap-4">
                <?php foreach ($comments as $comment): ?>
                    <div class="d-flex gap-3 animate-on-scroll">
                        <img src="<?php echo e($comment['avatar'] ? UPLOAD_URL . '/' . $comment['avatar'] : BASE_URL . '/assets/images/default-avatar.svg'); ?>" alt="<?php echo e($comment['fullname']); ?>" width="40" height="40" class="rounded-circle" style="object-fit: cover;">
                        <div>
                            <div class="bg-dark bg-opacity-50 p-3 rounded-4" style="display: inline-block;">
                                <div class="fw-bold mb-1 d-flex align-items-center gap-2">
                                    <?php echo e($comment['fullname']); ?>
                                </div>
                                <div class="text-white-50"><?php echo nl2br(e($comment['content'])); ?></div>
                            </div>
                            <div class="small text-secondary ms-3 mt-1 fw-semibold d-flex gap-3">
                                <span class="hover-underline" style="cursor: pointer;">Thích</span>
                                <span class="hover-underline" style="cursor: pointer;">Phản hồi</span>
                                <span><?php echo e(format_datetime($comment['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$comments): ?>
                    <div class="text-center text-secondary py-4"><i class="bi bi-chat-square-dots fs-1 mb-3 d-block opacity-50"></i>Hãy là người đầu tiên bình luận!</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar Right -->
    <div class="col-lg-4">
        <div class="clubit-card p-4 animate-on-scroll sticky-top" style="top: 80px;">
            <h5 class="fw-bold mb-4">Về tác giả</h5>
            <div class="text-center">
                <img src="<?php echo e($post['author_avatar'] ? UPLOAD_URL . '/' . $post['author_avatar'] : BASE_URL . '/assets/images/default-avatar.svg'); ?>" width="96" height="96" class="rounded-circle border border-3 border-primary border-opacity-25 mb-3" style="object-fit: cover;" alt="<?php echo e($post['author_name']); ?>">
                <h5 class="fw-bold mb-1"><?php echo e($post['author_name']); ?></h5>
                <p class="text-secondary small mb-3">Thành viên năng nổ của CLB IT</p>
                <?php if (!$isAuthor): ?>
                    <button class="btn btn-outline-primary rounded-pill px-4 w-100"><i class="bi bi-person-plus me-2"></i> Theo dõi</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
