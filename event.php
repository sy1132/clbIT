<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageLayout = 'public';
$pageTitle = 'Chi tiết sự kiện';

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT e.*, u.fullname AS creator_name, COUNT(er.id) AS registered_count
    FROM events e
    INNER JOIN users u ON u.id = e.created_by
    LEFT JOIN event_registrations er ON er.event_id = e.id
    WHERE e.id = :id AND e.status = 'published'
    GROUP BY e.id");
$stmt->execute(['id' => $id]);
$event = $stmt->fetch();

if (!$event) {
    http_response_code(404);
    echo 'Không tìm thấy sự kiện.';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    $fullName = trim($_POST['full_name'] ?? current_user_name());
    $phone = trim($_POST['phone'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if ($event['registered_count'] >= $event['max_member']) {
        flash_set('error', 'Sự kiện đã đủ số lượng tham gia.');
        redirect('/event.php?id=' . $id);
    }

    $checkStmt = db()->prepare('SELECT id FROM event_registrations WHERE event_id = :event_id AND user_id = :user_id LIMIT 1');
    $checkStmt->execute([
        'event_id' => $id,
        'user_id' => current_user_id(),
    ]);
    if ($checkStmt->fetch()) {
        flash_set('error', 'Bạn đã đăng ký sự kiện này rồi.');
        redirect('/event.php?id=' . $id);
    }

    $insertStmt = db()->prepare('INSERT INTO event_registrations (event_id, user_id, full_name, phone, note) VALUES (:event_id, :user_id, :full_name, :phone, :note)');
    $insertStmt->execute([
        'event_id' => $id,
        'user_id' => current_user_id(),
        'full_name' => $fullName,
        'phone' => $phone,
        'note' => $note,
    ]);

    flash_set('success', 'Đăng ký sự kiện thành công.');
    redirect('/event.php?id=' . $id);
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="clubit-card p-4 mb-4">
    <div class="row g-4 align-items-start">
        <div class="col-lg-8">
            <div class="hero-kicker mb-3"><i class="bi bi-calendar2-event"></i> Sự kiện CLB IT</div>
            <h1 class="section-title mb-3"><?php echo e($event['event_name']); ?></h1>
            <div class="d-flex flex-wrap gap-3 text-secondary mb-3">
                <span><i class="bi bi-geo-alt"></i> <?php echo e($event['location']); ?></span>
                <span><i class="bi bi-clock"></i> <?php echo e(format_datetime($event['start_date'])); ?></span>
                <span><i class="bi bi-people"></i> <?php echo e((string) $event['registered_count']); ?>/<?php echo e((string) $event['max_member']); ?> người</span>
            </div>
            <p class="lead"><?php echo e(short_text($event['description'], 300)); ?></p>
            <div class="small text-secondary">Tạo bởi: <?php echo e($event['creator_name']); ?></div>
        </div>
        <div class="col-lg-4">
            <div class="clubit-panel p-4">
                <h3 class="section-title mb-3">Đăng ký tham gia</h3>
                <?php if (is_logged_in()): ?>
                    <form method="post" class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo e(current_user_name()); ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" name="phone" class="form-control" placeholder="090...">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Ghi chú</label>
                            <textarea name="note" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12 d-grid">
                            <button class="btn btn-warning btn-lg">Xác nhận đăng ký</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">Đăng nhập để đăng ký tham gia sự kiện.</div>
                    <a href="<?php echo e(BASE_URL); ?>/login.php" class="btn btn-primary">Đăng nhập</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
