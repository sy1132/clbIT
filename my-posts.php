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
        <a href="/create-post.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Viết bài mới</a>
    </div>

    <?php if (empty($myPosts)): ?>
        <div class="alert alert-info border-0 mb-0">
            <strong>Bạn chưa viết bài nào.</strong> <a href="/create-post.php" class="alert-link">Viết bài đầu tiên ngay!</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tiêu đề</th>
                        <th>Danh mục</th>
                        <th>Trạng thái</th>
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
                                <span class="badge <?php echo e(badge_class_for_status($post['status'])); ?>">
                                    <?php 
                                        $statusText = [
                                            'pending' => '⏳ Chờ duyệt',
                                            'published' => '✅ Công khai',
                                            'draft' => '📝 Nháp',
                                            'archived' => '📦 Lưu trữ'
                                        ];
                                        echo $statusText[$post['status']] ?? e($post['status']);
                                    ?>
                                </span>
                            </td>
                            <td class="small"><?php echo e(format_datetime($post['created_at'])); ?></td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <?php if ($post['status'] === 'published'): ?>
                                        <a href="/post.php?id=<?php echo (int) $post['id']; ?>" class="btn btn-outline-primary btn-sm" target="_blank">Xem</a>
                                    <?php endif; ?>
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
        <div class="clubit-card p-4">
            <h5 class="fw-bold mb-3">📊 Hướng dẫn trạng thái</h5>
            <div class="small">
                <p class="mb-2"><strong>⏳ Chờ duyệt:</strong> Bài vừa nộp, admin đang kiểm tra</p>
                <p class="mb-2"><strong>✅ Công khai:</strong> Admin đã duyệt, bài được hiển thị</p>
                <p class="mb-0"><strong>🗑️ Xóa:</strong> Bạn có thể xóa bài bất kỳ lúc nào</p>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="clubit-card p-4">
            <h5 class="fw-bold mb-3">💡 Mẹo</h5>
            <div class="small">
                <p class="mb-0">✓ Viết nội dung chất lượng để admin duyệt nhanh hơn<br>
                   ✓ Tránh spam hoặc nội dung không phù hợp<br>
                   ✓ Liên hệ admin nếu bài bị từ chối</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
