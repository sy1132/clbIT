<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageLayout = 'public';
$pageTitle = 'Trang chủ';

$stats = [
    'members' => (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'member' AND status = 'active'")->fetchColumn(),
    'posts' => (int) db()->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn(),
    'events' => (int) db()->query("SELECT COUNT(*) FROM events WHERE status = 'published' AND start_date >= NOW()")->fetchColumn(),
    'documents' => (int) db()->query('SELECT COUNT(*) FROM documents')->fetchColumn(),
];

$featuredPosts = db()->query("SELECT p.id, p.title, p.excerpt, p.image, p.published_at, c.name AS category_name, u.fullname AS author_name FROM posts p INNER JOIN categories c ON c.id = p.category_id INNER JOIN users u ON u.id = p.user_id WHERE p.status = 'published' ORDER BY COALESCE(p.published_at, p.created_at) DESC LIMIT 3")->fetchAll();
$upcomingEvents = db()->query("SELECT e.*, COUNT(er.id) AS registered_count FROM events e LEFT JOIN event_registrations er ON er.event_id = e.id WHERE e.status = 'published' GROUP BY e.id ORDER BY e.start_date ASC LIMIT 3")->fetchAll();
$latestDocuments = db()->query("SELECT id, title, description, file_path, file_type FROM documents ORDER BY created_at DESC LIMIT 3")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<section class="clubit-hero mb-4">
    <div class="row align-items-center g-4 position-relative">
        <div class="col-lg-7">
            <div class="hero-kicker mb-3"><i class="bi bi-motherboard"></i> Website CLB IT dùng PHP & MySQL</div>
            <h1 class="display-5 fw-bold mb-3">Quản lý bài viết, sự kiện, tài liệu và thành viên trong một hệ thống gọn, dễ mở rộng.</h1>
            <p class="lead text-white-50 mb-4">Đề tài phù hợp môn phát triển web nhờ có đủ CRUD, xác thực, upload file, phân quyền admin và dữ liệu demo để trình bày đồ án.</p>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?php echo e(BASE_URL); ?>/posts.php" class="btn btn-warning btn-lg">Khám phá bài viết</a>
                <a href="<?php echo e(BASE_URL); ?>/events.php" class="btn btn-outline-light btn-lg">Xem sự kiện</a>
                <?php if (!is_logged_in()): ?>
                    <a href="<?php echo e(BASE_URL); ?>/register.php" class="btn btn-outline-info btn-lg">Gia nhập CLB</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="clubit-card p-3 p-lg-4">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="stat-tile">
                            <div class="small text-white-50">Thành viên</div>
                            <div class="fs-3 fw-bold"><?php echo e((string) $stats['members']); ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-tile">
                            <div class="small text-white-50">Bài viết</div>
                            <div class="fs-3 fw-bold"><?php echo e((string) $stats['posts']); ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-tile">
                            <div class="small text-white-50">Sự kiện sắp tới</div>
                            <div class="fs-3 fw-bold"><?php echo e((string) $stats['events']); ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-tile">
                            <div class="small text-white-50">Tài liệu</div>
                            <div class="fs-3 fw-bold"><?php echo e((string) $stats['documents']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <h2 class="section-title mb-3">Bài viết nổi bật</h2>
        <div class="row g-3">
            <?php foreach ($featuredPosts as $post): ?>
                <div class="col-md-6">
                    <div class="clubit-card h-100 p-3 card-hover">
                        <?php if (!empty($post['image'])): ?>
                            <img src="<?php echo e(UPLOAD_URL . '/' . $post['image']); ?>" class="card-media mb-3" alt="<?php echo e($post['title']); ?>">
                        <?php else: ?>
                            <div class="card-media mb-3 d-flex align-items-center justify-content-center text-white"><i class="bi bi-journal-code fs-1"></i></div>
                        <?php endif; ?>
                        <span class="soft-badge mb-2"><?php echo e($post['category_name']); ?></span>
                        <h5 class="fw-bold"><?php echo e($post['title']); ?></h5>
                        <p class="text-secondary mb-2"><?php echo e(short_text($post['excerpt'], 120)); ?></p>
                        <div class="small text-secondary d-flex justify-content-between gap-2">
                            <span><?php echo e($post['author_name']); ?></span>
                            <span><?php echo e(format_date($post['published_at'])); ?></span>
                        </div>
                        <a href="<?php echo e(BASE_URL); ?>/post.php?id=<?php echo (int) $post['id']; ?>" class="btn btn-outline-primary btn-sm mt-3">Đọc chi tiết</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-lg-4">
        <h2 class="section-title mb-3">Sự kiện sắp diễn ra</h2>
        <div class="d-grid gap-3">
            <?php foreach ($upcomingEvents as $event): ?>
                <div class="clubit-card p-3 card-hover">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div>
                            <div class="soft-badge mb-2"><i class="bi bi-calendar-event"></i> <?php echo e((string) $event['registered_count']); ?>/<?php echo e((string) $event['max_member']); ?></div>
                            <h5 class="fw-bold mb-1"><?php echo e($event['event_name']); ?></h5>
                        </div>
                    </div>
                    <div class="text-secondary small mb-2"><i class="bi bi-geo-alt"></i> <?php echo e($event['location']); ?></div>
                    <div class="text-secondary small mb-3"><i class="bi bi-clock"></i> <?php echo e(format_datetime($event['start_date'])); ?></div>
                    <a href="<?php echo e(BASE_URL); ?>/event.php?id=<?php echo (int) $event['id']; ?>" class="btn btn-outline-light btn-sm">Đăng ký ngay</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <h2 class="section-title mb-3">Tài liệu mới</h2>
        <div class="clubit-card p-3">
            <?php foreach ($latestDocuments as $document): ?>
                <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                    <div>
                        <div class="fw-semibold"><?php echo e($document['title']); ?></div>
                        <div class="text-secondary small"><?php echo e(short_text($document['description'], 100)); ?></div>
                    </div>
                    <a href="<?php echo e(BASE_URL); ?>/documents.php#doc-<?php echo (int) $document['id']; ?>" class="btn btn-outline-success btn-sm">Xem</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <h2 class="section-title mb-3">Tại sao đề tài này mạnh?</h2>
        <div class="clubit-card p-4">
            <ul class="mb-0 ps-3">
                <li>Đủ luồng người dùng: xem bài viết, đăng ký sự kiện, tải tài liệu và cập nhật hồ sơ.</li>
                <li>Đủ luồng quản trị: CRUD thành viên, bài viết, sự kiện, tài liệu và bình luận.</li>
                <li>Dễ demo vì có dữ liệu mẫu, responsive và có thể mở rộng thêm QR, realtime hoặc chat.</li>
            </ul>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
