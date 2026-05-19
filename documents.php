<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageLayout = 'public';
$pageTitle = 'Tài liệu CLB';

$type = trim($_GET['type'] ?? '');
$search = trim($_GET['q'] ?? '');
$where = ['1=1'];
$params = [];

if ($type !== '') {
    $where[] = 'file_type = :file_type';
    $params['file_type'] = $type;
}

if ($search !== '') {
    $where[] = '(title LIKE :search1 OR description LIKE :search2)';
    $params['search1'] = '%' . $search . '%';
    $params['search2'] = '%' . $search . '%';
}

$whereSql = implode(' AND ', $where);
$stmt = db()->prepare("SELECT d.*, u.fullname AS uploader_name FROM documents d INNER JOIN users u ON u.id = d.uploaded_by WHERE $whereSql ORDER BY d.created_at DESC");
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->execute();
$documents = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="clubit-card p-4 mb-4">
    <div class="hero-kicker mb-3"><i class="bi bi-folder2-open"></i> Khu vực tài liệu</div>
    <h1 class="section-title mb-3">Slide workshop, ebook và bộ tài nguyên học tập</h1>
    <form method="get" class="row g-3">
        <div class="col-lg-5">
            <input type="text" name="q" class="form-control form-control-lg" value="<?php echo e($search); ?>" placeholder="Tìm tài liệu...">
        </div>
        <div class="col-lg-4">
            <select name="type" class="form-select form-select-lg">
                <option value="">Tất cả loại</option>
                <option value="pdf" <?php echo $type === 'pdf' ? 'selected' : ''; ?>>PDF</option>
                <option value="zip" <?php echo $type === 'zip' ? 'selected' : ''; ?>>ZIP</option>
                <option value="doc" <?php echo $type === 'doc' ? 'selected' : ''; ?>>DOC</option>
                <option value="other" <?php echo $type === 'other' ? 'selected' : ''; ?>>Khác</option>
            </select>
        </div>
        <div class="col-lg-3 d-grid">
            <button class="btn btn-primary btn-lg">Lọc</button>
        </div>
    </form>
</div>

<div class="row g-4">
    <?php foreach ($documents as $document): ?>
        <div class="col-md-6 col-xl-4" id="doc-<?php echo (int) $document['id']; ?>">
            <div class="clubit-card h-100 p-3 card-hover">
                <div class="soft-badge mb-2"><i class="bi bi-file-earmark"></i> <?php echo e(strtoupper((string) $document['file_type'])); ?></div>
                <h5 class="fw-bold"><?php echo e($document['title']); ?></h5>
                <p class="text-secondary mb-3"><?php echo e(short_text($document['description'], 120)); ?></p>
                <div class="small text-secondary mb-3">Tải lên bởi <?php echo e($document['uploader_name']); ?></div>
                <a href="<?php echo e(UPLOAD_URL . '/' . $document['file_path']); ?>" class="btn btn-outline-success btn-sm" download>Tải xuống</a>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$documents): ?>
        <div class="col-12">
            <div class="clubit-card p-4 text-center text-secondary">Chưa có tài liệu nào.</div>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
