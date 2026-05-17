<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$pageLayout = 'admin';
$pageTitle = 'Danh sách đăng ký sự kiện';

$eventId = (int) ($_GET['event_id'] ?? 0);
if ($eventId <= 0) {
    redirect('/admin/events.php');
}

$eventStmt = db()->prepare('SELECT * FROM events WHERE id = :id LIMIT 1');
$eventStmt->execute(['id' => $eventId]);
$event = $eventStmt->fetch();

if (!$event) {
    redirect('/admin/events.php');
}

// Handle delete registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $registrationId = (int) ($_POST['registration_id'] ?? 0);
    $deleteStmt = db()->prepare('DELETE FROM event_registrations WHERE id = :id AND event_id = :event_id');
    $deleteStmt->execute(['id' => $registrationId, 'event_id' => $eventId]);
    flash_set('success', 'Đã xóa đơn đăng ký.');
    redirect('/admin/event-registrations.php?event_id=' . $eventId);
}

$registrations = db()->prepare("SELECT er.*, u.fullname, u.email, u.avatar
    FROM event_registrations er
    INNER JOIN users u ON u.id = er.user_id
    WHERE er.event_id = :event_id
    ORDER BY er.registered_at DESC")->execute(['event_id' => $eventId]) || 
    db()->prepare("SELECT er.*, u.fullname, u.email, u.avatar
    FROM event_registrations er
    INNER JOIN users u ON u.id = er.user_id
    WHERE er.event_id = :event_id
    ORDER BY er.registered_at DESC");

$stmt = db()->prepare("SELECT er.*, u.fullname, u.email, u.avatar
    FROM event_registrations er
    INNER JOIN users u ON u.id = er.user_id
    WHERE er.event_id = :event_id
    ORDER BY er.registered_at DESC");
$stmt->execute(['event_id' => $eventId]);
$registrations = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="clubit-card p-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <a href="/admin/events.php" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-arrow-left"></i> Quay lại</a>
            <h3 class="section-title mb-2"><?php echo e($event['event_name']); ?></h3>
            <p class="text-secondary small mb-0">📍 <?php echo e($event['location']); ?> | 🕐 <?php echo e(format_datetime($event['start_date'])); ?></p>
        </div>
        <div class="text-end">
            <div class="fs-5 fw-bold text-white"><?php echo count($registrations); ?>/<?php echo $event['max_member']; ?> người</div>
            <small class="text-secondary">Đã đăng ký</small>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-clubit align-middle mb-0">
            <thead>
                <tr>
                    <th class="text-white">Thành viên</th>
                    <th class="text-white">Email</th>
                    <th class="text-white">SĐT</th>
                    <th class="text-white">Ghi chú</th>
                    <th class="text-white">Thời gian đăng ký</th>
                    <th class="text-white">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registrations)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">
                            Chưa có đơn đăng ký nào
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registrations as $reg): ?>
                        <tr>
                            <td class="text-white">
                                <div class="d-flex align-items-center gap-2">
                                    <?php if (!empty($reg['avatar'])): ?>
                                        <img src="<?php echo e(UPLOAD_URL . '/' . $reg['avatar']); ?>" alt="<?php echo e($reg['fullname']); ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                                    <?php else: ?>
                                        <img src="<?php echo e(BASE_URL); ?>/assets/images/default-avatar.svg" alt="Avatar" style="width:32px;height:32px;border-radius:50%;">
                                    <?php endif; ?>
                                    <span><?php echo e($reg['fullname']); ?></span>
                                </div>
                            </td>
                            <td class="text-white"><?php echo e($reg['email']); ?></td>
                            <td class="text-white"><?php echo e($reg['phone']); ?></td>
                            <td class="text-white"><?php echo e(short_text($reg['note'] ?? '', 50)); ?></td>
                            <td class="text-white"><?php echo e(format_datetime($reg['registered_at'])); ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Xóa đơn đăng ký này?');" class="d-inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="registration_id" value="<?php echo (int) $reg['id']; ?>">
                                    <button class="btn btn-outline-danger btn-sm">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
