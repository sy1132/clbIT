<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$pageLayout = 'admin';
$pageTitle = 'Quản lý bài viết';

$categories = db()->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        $stmt = db()->prepare('SELECT image FROM posts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $post = $stmt->fetch();
        if ($post) {
            delete_uploaded_file((string) $post['image']);
            $deleteStmt = db()->prepare('DELETE FROM posts WHERE id = :id');
            $deleteStmt->execute(['id' => $id]);
        }
        flash_set('success', 'Đã xóa bài viết.');
        redirect('/admin/posts.php');
    }

    $title = trim($_POST['title'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $status = $_POST['status'] ?? 'published';

    if ($title === '' || $excerpt === '' || $content === '' || $categoryId <= 0) {
        flash_set('error', 'Vui lòng nhập đầy đủ thông tin bài viết.');
        redirect('/admin/posts.php');
    }

    $image = upload_file('image', ['jpg', 'jpeg', 'png', 'webp']);
    $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;

    if ($id > 0) {
        $stmt = db()->prepare('SELECT image, published_at FROM posts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $current = $stmt->fetch();
        $image = $image ?? ($current['image'] ?? null);
        $publishedAt = $publishedAt ?? ($current['published_at'] ?? null);

        $updateSql = 'UPDATE posts SET title = :title, excerpt = :excerpt, content = :content, category_id = :category_id, status = :status, published_at = :published_at';
        $params = [
            'title' => $title,
            'excerpt' => $excerpt,
            'content' => $content,
            'category_id' => $categoryId,
            'status' => $status,
            'published_at' => $publishedAt,
            'id' => $id,
        ];
        if ($image !== null) {
            $updateSql .= ', image = :image';
            $params['image'] = $image;
        }
        $updateSql .= ' WHERE id = :id';
        $updateStmt = db()->prepare($updateSql);
        $updateStmt->execute($params);
        flash_set('success', 'Đã cập nhật bài viết.');
    } else {
        $slug = slugify($title) . '-' . time();
        $insertStmt = db()->prepare('INSERT INTO posts (title, slug, excerpt, content, image, category_id, user_id, status, published_at) VALUES (:title, :slug, :excerpt, :content, :image, :category_id, :user_id, :status, :published_at)');
        $insertStmt->execute([
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $excerpt,
            'content' => $content,
            'image' => $image,
            'category_id' => $categoryId,
            'user_id' => current_user_id(),
            'status' => $status,
            'published_at' => $publishedAt,
        ]);
        flash_set('success', 'Đã thêm bài viết.');
    }

    redirect('/admin/posts.php');
}

$editId = (int) ($_GET['edit'] ?? 0);
$editPost = null;
if ($editId > 0) {
    $stmt = db()->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $editId]);
    $editPost = $stmt->fetch();
}

$posts = db()->query("SELECT p.*, c.name AS category_name, u.fullname AS author_name
    FROM posts p
    INNER JOIN categories c ON c.id = p.category_id
    INNER JOIN users u ON u.id = p.user_id
    ORDER BY p.created_at DESC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="clubit-card p-4">
            <h3 class="section-title mb-3"><?php echo $editPost ? 'Sửa bài viết' : 'Thêm bài viết'; ?></h3>
            <form method="post" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="id" value="<?php echo (int) ($editPost['id'] ?? 0); ?>">
                <div class="col-12">
                    <label class="form-label">Tiêu đề</label>
                    <input type="text" name="title" class="form-control" value="<?php echo e((string) ($editPost['title'] ?? '')); ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Chuyên mục</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Chọn chuyên mục</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo (int) $category['id']; ?>" <?php echo (int) ($editPost['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : ''; ?>><?php echo e($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Ảnh đại diện</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
                <div class="col-12">
                    <label class="form-label">Tóm tắt</label>
                    <textarea name="excerpt" class="form-control" rows="3" required><?php echo e((string) ($editPost['excerpt'] ?? '')); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Nội dung</label>
                    <textarea name="content" class="form-control" rows="8" required><?php echo e((string) ($editPost['content'] ?? '')); ?></textarea>
                </div>
                <div class="col-6">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="published" <?php echo ($editPost['status'] ?? 'published') === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo ($editPost['status'] ?? 'published') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>
                <div class="col-6 d-grid align-self-end">
                    <button class="btn btn-primary"><?php echo $editPost ? 'Cập nhật' : 'Thêm mới'; ?></button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="clubit-card p-4">
            <h3 class="section-title mb-3">Danh sách bài viết</h3>
            <div class="table-responsive">
                <table class="table table-clubit align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tiêu đề</th>
                            <th>Chuyên mục</th>
                            <th>Tác giả</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><?php echo e(short_text($post['title'], 40)); ?></td>
                                <td><?php echo e($post['category_name']); ?></td>
                                <td><?php echo e($post['author_name']); ?></td>
                                <td><span class="badge <?php echo e(badge_class_for_status($post['status'])); ?>"><?php echo e($post['status']); ?></span></td>
                                <td><?php echo e(format_datetime($post['created_at'])); ?></td>
                                <td class="d-flex gap-2 flex-wrap">
                                    <a class="btn btn-outline-primary btn-sm" href="?edit=<?php echo (int) $post['id']; ?>">Sửa</a>
                                    <form method="post" onsubmit="return confirm('Xóa bài viết này?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int) $post['id']; ?>">
                                        <button class="btn btn-outline-danger btn-sm">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
