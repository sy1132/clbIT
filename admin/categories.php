<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$pageLayout = 'admin';
$pageTitle = 'Quản lý chuyên mục';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        flash_set('error', 'Tên chuyên mục không được để trống.');
        redirect('/admin/categories.php');
    }

    if ($action === 'delete' && $id > 0) {
        $deleteStmt = db()->prepare('DELETE FROM categories WHERE id = :id');
        $deleteStmt->execute(['id' => $id]);
        flash_set('success', 'Đã xóa chuyên mục.');
        redirect('/admin/categories.php');
    }

    $slug = slugify($name) . '-' . time();

    if ($id > 0) {
        $updateStmt = db()->prepare('UPDATE categories SET name = :name, slug = :slug WHERE id = :id');
        $updateStmt->execute([
            'name' => $name,
            'slug' => $slug,
            'id' => $id,
        ]);
        flash_set('success', 'Đã cập nhật chuyên mục.');
    } else {
        $insertStmt = db()->prepare('INSERT INTO categories (name, slug) VALUES (:name, :slug)');
        $insertStmt->execute([
            'name' => $name,
            'slug' => $slug,
        ]);
        flash_set('success', 'Đã thêm chuyên mục.');
    }

    redirect('/admin/categories.php');
}

$editId = (int) ($_GET['edit'] ?? 0);
$editCategory = null;
if ($editId > 0) {
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $editId]);
    $editCategory = $stmt->fetch();
}

$categories = db()->query('SELECT * FROM categories ORDER BY created_at DESC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="clubit-card p-4">
            <h3 class="section-title mb-3"><?php echo $editCategory ? 'Sửa chuyên mục' : 'Thêm chuyên mục'; ?></h3>
            <form method="post" class="row g-3">
                <input type="hidden" name="id" value="<?php echo (int) ($editCategory['id'] ?? 0); ?>">
                <div class="col-12">
                    <label class="form-label">Tên chuyên mục</label>
                    <input type="text" name="name" class="form-control" value="<?php echo e((string) ($editCategory['name'] ?? '')); ?>" required>
                </div>
                <div class="col-12 d-grid">
                    <button class="btn btn-primary"><?php echo $editCategory ? 'Cập nhật' : 'Thêm mới'; ?></button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="clubit-card p-4">
            <h3 class="section-title mb-3">Danh sách chuyên mục</h3>
            <div class="table-responsive">
                <table class="table table-clubit align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tên</th>
                            <th>Slug</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo e($category['name']); ?></td>
                                <td><?php echo e($category['slug']); ?></td>
                                <td><?php echo e(format_datetime($category['created_at'])); ?></td>
                                <td class="d-flex gap-2 flex-wrap">
                                    <a class="btn btn-outline-primary btn-sm" href="?edit=<?php echo (int) $category['id']; ?>">Sửa</a>
                                    <form method="post" onsubmit="return confirm('Xóa chuyên mục này?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int) $category['id']; ?>">
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
