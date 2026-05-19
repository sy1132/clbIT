<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageLayout = 'public';
$pageTitle = 'Sự kiện CLB';

$search = trim($_GET['q'] ?? '');
$where = ["e.status = 'published'"];
$params = [];

if ($search !== '') {
    $where[] = '(e.event_name LIKE :search1 OR e.description LIKE :search2 OR e.location LIKE :search3)';
    $params['search1'] = '%' . $search . '%';
    $params['search2'] = '%' . $search . '%';
    $params['search3'] = '%' . $search . '%';
}

$whereSql = implode(' AND ', $where);
$stmt = db()->prepare("SELECT e.*, COUNT(er.id) AS registered_count
    FROM events e
    LEFT JOIN event_registrations er ON er.event_id = e.id
    WHERE $whereSql
    GROUP BY e.id
    ORDER BY e.start_date ASC");
$stmt->execute($params);
$events = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="clubit-card p-4 mb-4">
    <div class="hero-kicker mb-3"><i class="bi bi-calendar-event"></i> Sự kiện và workshop</div>
    <h1 class="section-title mb-3">Các hoạt động nổi bật của CLB IT</h1>
    <form method="get" class="row g-3">
        <div class="col-lg-9">
            <input type="text" name="q" class="form-control form-control-lg" value="<?php echo e($search); ?>" placeholder="Tìm kiếm workshop, seminar, hackathon...">
        </div>
        <div class="col-lg-3 d-grid">
            <button class="btn btn-primary btn-lg">Tìm kiếm</button>
        </div>
    </form>
</div>

<div class="row g-4">
    <?php foreach ($events as $event): ?>
        <div class="col-md-6 col-xl-4">
            <div class="clubit-card h-100 p-3 card-hover">
                <div class="soft-badge mb-2"><i class="bi bi-people"></i> <?php echo e((string) $event['registered_count']); ?>/<?php echo e((string) $event['max_member']); ?></div>
                <h5 class="fw-bold"><?php echo e($event['event_name']); ?></h5>
                <p class="text-secondary mb-2"><?php echo e(short_text($event['description'], 120)); ?></p>
                <div class="small text-secondary mb-1"><i class="bi bi-geo-alt"></i> <?php echo e($event['location']); ?></div>
                <div class="small text-secondary mb-3"><i class="bi bi-clock"></i> <?php echo e(format_datetime($event['start_date'])); ?></div>
                <a href="<?php echo e(BASE_URL); ?>/event.php?id=<?php echo (int) $event['id']; ?>" class="btn btn-outline-primary btn-sm">Xem và đăng ký</a>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$events): ?>
        <div class="col-12">
            <div class="clubit-card p-4 text-center text-secondary">Chưa có sự kiện nào phù hợp.</div>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
