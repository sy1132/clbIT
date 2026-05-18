<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$pageLayout = 'public';
$pageTitle = 'Bài viết của tôi';

$userId = current_user_id();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = db()->prepare('DELETE FROM posts WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        flash_set('success', 'Đã xóa bài viết.');
        redirect('/my-posts.php');
    }
}

$myPosts = db()->prepare("SELECT p.*, c.name AS category_name FROM posts p INNER JOIN categories c ON c.id = p.category_id WHERE p.user_id = :user_id ORDER BY p.created_at DESC")->execute(['user_id' => $userId]) || 
    db()->prepare("SELECT p.*, c.name AS category_name FROM posts p INNER JOIN categories c ON c.id = p.category_id WHERE p.user_id = :user_id ORDER BY p.created_at DESC");

$stmt = db()->prepare("SELECT p.*, c.name AS category_name FROM posts p INNER JOIN categories c ON c.id = p.category_id WHERE p.user_id = :user_id ORDER BY p.created_at DESC");
$stmt->execute(['user_id' => $userId]);
$myPosts = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="clubit-card p-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h1 class="section-title mb-0">📝 Bài viết của tôi</h1>
        <a href="<?php echo BASE_URL; ?>/create-post.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Viết bài mới</a>
    </div>

    <?php if (empty($myPosts)): ?>
        <div class="alert alert-info border-0 mb-0">
            <strong>Bạn chưa viết bài nào.</strong> <a href="<?php echo BASE_URL; ?>/create-post.php" class="alert-link">Viết bài đầu tiên ngay!</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tiêu đề</th>
                        <th>Danh mục</th>
                        <th>Hiển thị</th>
                        <th>Trạng thái duyệt</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myPosts as $post): ?>
                        <tr>
                            <td>
                                <strong><?php echo e(short_text($post['title'], 50)); ?></strong>
                            </td>
                            <td>
                                <span class="soft-badge"><?php echo e($post['category_name']); ?></span>
                            </td>
                            <td>
                                <?php if (($post['privacy'] ?? 'public') === 'private'): ?>
                                    <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 border border-warning border-opacity-25 rounded-pill"><i class="bi bi-lock-fill me-1"></i> Riêng tư</span>
                                <?php else: ?>
                                    <span class="badge bg-info bg-opacity-10 text-info px-3 py-2 border border-info border-opacity-25 rounded-pill"><i class="bi bi-globe-americas me-1"></i> Công khai</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($post['status'] === 'pending'): ?>
                                    <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 border border-warning border-opacity-25 rounded-pill d-inline-flex align-items-center gap-1"><i class="bi bi-hourglass-split"></i> Chờ duyệt</span>
                                <?php elseif ($post['status'] === 'published'): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 border border-success border-opacity-25 rounded-pill d-inline-flex align-items-center gap-1"><i class="bi bi-check-circle-fill"></i> Đã duyệt</span>
                                <?php elseif ($post['status'] === 'draft'): ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 border border-secondary border-opacity-25 rounded-pill d-inline-flex align-items-center gap-1"><i class="bi bi-pencil-fill"></i> Bản nháp</span>
                                <?php else: ?>
                                    <span class="badge bg-info bg-opacity-10 text-info px-3 py-2 border border-info border-opacity-25 rounded-pill d-inline-flex align-items-center gap-1"><i class="bi bi-archive-fill"></i> Lưu trữ</span>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?php echo e(format_datetime($post['created_at'])); ?></td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="<?php echo BASE_URL; ?>/post.php?id=<?php echo (int) $post['id']; ?>" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1" target="_blank">
                                        <?php if ($post['status'] === 'published'): ?>
                                            <i class="bi bi-eye"></i> Xem
                                        <?php else: ?>
                                            <i class="bi bi-search"></i> Xem trước
                                        <?php endif; ?>
                                    </a>
                                    <form method="post" onsubmit="return confirm('Xóa bài viết này?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int) $post['id']; ?>">
                                        <button class="btn btn-outline-danger btn-sm">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="row g-4 mt-4">
    <div class="col-lg-6">
        <div class="clubit-card p-4 h-100 border border-white border-opacity-10">
            <h5 class="fw-bold mb-3 text-primary d-flex align-items-center gap-2">
                <i class="bi bi-info-circle-fill"></i> Hướng dẫn trạng thái duyệt
            </h5>
            <div class="d-flex flex-column gap-3 mt-3">
                <div class="d-flex align-items-start gap-2">
                    <span class="badge bg-warning bg-opacity-10 text-warning px-2.5 py-1.5 border border-warning border-opacity-25 rounded-pill fw-semibold small">
                        <i class="bi bi-hourglass-split me-1"></i> Chờ duyệt
                    </span>
                    <div class="small text-white-50 flex-grow-1">Bài viết của bạn đang đợi Ban quản trị duyệt. Bài viết sẽ chỉ hiển thị công khai khi được duyệt.</div>
                </div>
                <div class="d-flex align-items-start gap-2">
                    <span class="badge bg-success bg-opacity-10 text-success px-2.5 py-1.5 border border-success border-opacity-25 rounded-pill fw-semibold small">
                        <i class="bi bi-check-circle-fill me-1"></i> Đã duyệt
                    </span>
                    <div class="small text-white-50 flex-grow-1">Bài viết đã được phê duyệt thành công và hiển thị công khai/riêng tư theo ý của bạn.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="clubit-card p-4 h-100 border border-white border-opacity-10">
            <h5 class="fw-bold mb-3 text-info d-flex align-items-center gap-2">
                <i class="bi bi-shield-lock-fill"></i> Hướng dẫn chế độ hiển thị
            </h5>
            <div class="d-flex flex-column gap-3 mt-3">
                <div class="d-flex align-items-start gap-2">
                    <span class="badge bg-info bg-opacity-10 text-info px-2.5 py-1.5 border border-info border-opacity-25 rounded-pill fw-semibold small">
                        <i class="bi bi-globe-americas me-1"></i> Công khai
                    </span>
                    <div class="small text-white-50 flex-grow-1">Tất cả mọi thành viên đều có thể xem bài viết này sau khi bài viết được duyệt.</div>
                </div>
                <div class="d-flex align-items-start gap-2">
                    <span class="badge bg-warning bg-opacity-10 text-warning px-2.5 py-1.5 border border-warning border-opacity-25 rounded-pill fw-semibold small">
                        <i class="bi bi-lock-fill me-1"></i> Riêng tư
                    </span>
                    <div class="small text-white-50 flex-grow-1">Chỉ duy nhất bạn (tác giả) mới có thể xem được bài viết này.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
