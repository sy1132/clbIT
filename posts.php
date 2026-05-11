<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageLayout = 'public';
$pageTitle = 'Bài viết công nghệ';

$search = trim($_GET['q'] ?? '');
$categoryId = (int) ($_GET['category_id'] ?? 0);
$page = safe_page((int) ($_GET['page'] ?? 1));
$perPage = 6;
$offset = ($page - 1) * $perPage;

$categories = db()->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();

$where = ["p.status = 'published'"];
$params = [];

if ($search !== '') {
    $where[] = '(p.title LIKE :search OR p.excerpt LIKE :search OR p.content LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($categoryId > 0) {
    $where[] = 'p.category_id = :category_id';
    $params['category_id'] = $categoryId;
}

$whereSql = implode(' AND ', $where);
$countStmt = db()->prepare("SELECT COUNT(*) FROM posts p WHERE $whereSql");
$countStmt->execute($params);
$totalItems = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalItems / $perPage));

$listSql = "SELECT p.id, p.title, p.slug, p.excerpt, p.image, p.published_at, c.name AS category_name, u.fullname AS author_name
            FROM posts p
            INNER JOIN categories c ON c.id = p.category_id
            INNER JOIN users u ON u.id = p.user_id
            WHERE $whereSql
            ORDER BY COALESCE(p.published_at, p.created_at) DESC
            LIMIT :limit OFFSET :offset";

$listStmt = db()->prepare($listSql);
foreach ($params as $key => $value) {
    $listStmt->bindValue(':' . $key, $value);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$posts = $listStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="clubit-card p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center mb-3">
        <div>
            <div class="hero-kicker mb-2"><i class="bi bi-journal-text"></i> Bài viết công nghệ</div>
            <h1 class="section-title mb-0">Khám phá kiến thức cho thành viên CLB</h1>
        </div>
    </div>
    <form method="get" class="row g-3" data-live-search-form>
        <div class="col-lg-6">
            <input type="text" name="q" class="form-control form-control-lg" value="<?php echo e($search); ?>" placeholder="Tìm kiếm bài viết, từ khóa, nội dung...">
        </div>
        <div class="col-lg-3">
            <select name="category_id" class="form-select form-select-lg" data-post-filter-type>
                <option value="0">Tất cả chuyên mục</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo (int) $category['id']; ?>" <?php echo $categoryId === (int) $category['id'] ? 'selected' : ''; ?>><?php echo e($category['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-3 d-grid">
            <button class="btn btn-primary btn-lg">Tìm kiếm</button>
        </div>
    </form>
</div>

<div class="row g-4">
    <?php foreach ($posts as $post): ?>
        <div class="col-md-6 col-xl-4">
            <div class="clubit-card h-100 p-3 card-hover">
                <?php if (!empty($post['image'])): ?>
                    <img src="<?php echo e(UPLOAD_URL . '/' . $post['image']); ?>" class="card-media mb-3" alt="<?php echo e($post['title']); ?>">
                <?php else: ?>
                    <div class="card-media mb-3 d-flex align-items-center justify-content-center text-white"><i class="bi bi-file-earmark-richtext fs-1"></i></div>
                <?php endif; ?>
                <span class="soft-badge mb-2"><?php echo e($post['category_name']); ?></span>
                <h5 class="fw-bold"><?php echo e($post['title']); ?></h5>
                <p class="text-secondary mb-3"><?php echo e(short_text($post['excerpt'], 120)); ?></p>
                <div class="small text-secondary d-flex justify-content-between align-items-center">
                    <span><?php echo e($post['author_name']); ?></span>
                    <span><?php echo e(format_date($post['published_at'])); ?></span>
                </div>
                <a href="<?php echo e(BASE_URL); ?>/post.php?id=<?php echo (int) $post['id']; ?>" class="btn btn-outline-primary btn-sm mt-3">Xem chi tiết</a>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$posts): ?>
        <div class="col-12">
            <div class="clubit-card p-4 text-center text-secondary">Không tìm thấy bài viết phù hợp.</div>
        </div>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?<?php echo http_build_query(array_filter([
                        'q' => $search,
                        'category_id' => $categoryId ?: null,
                        'page' => $i,
                    ], static fn ($value) => $value !== null && $value !== '')); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
