<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$pageLayout = 'public';
$pageTitle = 'Viết bài';

$categories = db()->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';
    $image = null;

    if ($title === '' || $categoryId <= 0 || $excerpt === '' || $content === '') {
        flash_set('error', 'Vui lòng nhập đầy đủ thông tin bài viết.');
        redirect('/create-post.php');
    }

    // Check category exists
    $catStmt = db()->prepare('SELECT id FROM categories WHERE id = :id LIMIT 1');
    $catStmt->execute(['id' => $categoryId]);
    if (!$catStmt->fetch()) {
        flash_set('error', 'Danh mục không tồn tại.');
        redirect('/create-post.php');
    }

    // Upload image if provided
    if (!empty($_FILES['image']['name'])) {
        $uploadResult = upload_file($_FILES['image'], 'image');
        if ($uploadResult['success']) {
            $image = $uploadResult['filename'];
        } else {
            flash_set('error', $uploadResult['message']);
            redirect('/create-post.php');
        }
    }

    $slug = slugify($title) . '-' . time();
    $insertStmt = db()->prepare('INSERT INTO posts (title, slug, category_id, excerpt, content, image, user_id, status, created_at, published_at) VALUES (:title, :slug, :category_id, :excerpt, :content, :image, :user_id, :status, NOW(), NULL)');
    $insertStmt->execute([
        'title' => $title,
        'slug' => $slug,
        'category_id' => $categoryId,
        'excerpt' => $excerpt,
        'content' => $content,
        'image' => $image,
        'user_id' => current_user_id(),
        'status' => 'pending',
    ]);

    flash_set('success', 'Bài viết đã được nộp! Admin sẽ xem xét và duyệt trong thời gian sớm nhất.');
    redirect('/my-posts.php');
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="clubit-card p-4">
            <h1 class="section-title mb-4">✍️ Viết bài viết mới</h1>
            <form method="post" enctype="multipart/form-data" class="row g-3">
                <div class="col-12">
                    <label class="form-label">Tiêu đề bài viết</label>
                    <input type="text" name="title" class="form-control" placeholder="Nhập tiêu đề..." required>
                </div>

                <div class="col-12">
                    <label class="form-label">Danh mục</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int) $cat['id']; ?>"><?php echo e($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Ảnh đại diện (tùy chọn)</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <small class="text-secondary">JPG, PNG hoặc WebP, tối đa 5MB</small>
                </div>

                <div class="col-12">
                    <label class="form-label">Tóm tắt ngắn</label>
                    <textarea name="excerpt" class="form-control" rows="2" placeholder="Viết tóm tắt 1-2 dòng..." required></textarea>
                </div>

                <div class="col-12">
                    <label class="form-label">Nội dung chi tiết</label>
                    <textarea name="content" class="form-control" rows="10" placeholder="Viết nội dung bài viết..." required></textarea>
                    <small class="text-secondary">Bạn có thể sử dụng HTML cơ bản (b, i, u, strong, em, p, br, ...)</small>
                </div>

                <div class="col-12 d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">📤 Nộp bài viết</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="clubit-card p-4">
            <h5 class="fw-bold mb-3">📋 Ghi chú</h5>
            <ul class="small mb-0 ps-3">
                <li class="mb-2">Bài viết sẽ được lưu với trạng thái <strong>"Chờ duyệt"</strong></li>
                <li class="mb-2">Admin sẽ kiểm tra nội dung và công khai hoặc từ chối</li>
                <li class="mb-2">Bạn có thể xem trạng thái bài viết ở "<strong>Bài viết của tôi</strong>"</li>
                <li>Không gửi nội dung spam hoặc vi phạm tiêu chuẩn CLB</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
