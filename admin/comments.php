<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$pageLayout = 'admin';
$pageTitle = 'Quản lý bình luận';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'approve';
    $id = (int) ($_POST['id'] ?? 0);

    if ($id <= 0) {
        redirect('/admin/comments.php');
    }

    if ($action === 'delete') {
        $stmt = db()->prepare('DELETE FROM comments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        flash_set('success', 'Đã xóa bình luận.');
    } else {
        $status = in_array($action, ['approved', 'spam', 'pending'], true) ? $action : 'approved';
        $stmt = db()->prepare('UPDATE comments SET status = :status WHERE id = :id');
        $stmt->execute([
            'status' => $status,
            'id' => $id,
        ]);
        flash_set('success', 'Đã cập nhật trạng thái bình luận.');
    }

    redirect('/admin/comments.php');
}

$comments = db()->query("SELECT c.*, p.title AS post_title, u.fullname AS author_name
    FROM comments c
    INNER JOIN posts p ON p.id = c.post_id
    INNER JOIN users u ON u.id = c.user_id
    ORDER BY c.created_at DESC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="clubit-card p-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h3 class="section-title mb-0">Danh sách bình luận</h3>
        <span class="text-secondary">Quản lý bình luận: Chặn spam hoặc xóa nội dung không phù hợp</span>
    </div>
    <div class="table-responsive">
        <table class="table table-clubit align-middle mb-0">
            <thead>
                <tr>
                    <th class="text-white">Bài viết</th>
                    <th class="text-white">Tác giả</th>
                    <th class="text-white">Nội dung</th>
                    <th class="text-white">Trạng thái</th>
                    <th class="text-white">Ngày tạo</th>
                    <th class="text-white">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comments as $comment): ?>
                    <tr>
                        <td class="text-white"><?php echo e(short_text($comment['post_title'], 30)); ?></td>
                        <td class="text-white"><?php echo e($comment['author_name']); ?></td>
                        <td class="text-white"><?php echo e(short_text($comment['content'], 70)); ?></td>
                        <td><span class="badge <?php echo e(badge_class_for_status($comment['status'])); ?>"><?php echo e($comment['status']); ?></span></td>
                        <td class="text-white"><?php echo e(format_datetime($comment['created_at'])); ?></td>
                        <td class="d-flex gap-2 flex-wrap">
                            <form method="post">
                                <input type="hidden" name="id" value="<?php echo (int) $comment['id']; ?>">
                                <input type="hidden" name="action" value="spam">
                                <button class="btn btn-outline-warning btn-sm">Chặn</button>
                            </form>
                            <form method="post" onsubmit="return confirm('Xóa bình luận này?');">
                                <input type="hidden" name="id" value="<?php echo (int) $comment['id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="btn btn-outline-danger btn-sm">Xóa</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
