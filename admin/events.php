<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$pageLayout = 'admin';
$pageTitle = 'Quản lý sự kiện';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        $deleteStmt = db()->prepare('DELETE FROM events WHERE id = :id');
        $deleteStmt->execute(['id' => $id]);
        flash_set('success', 'Đã xóa sự kiện.');
        redirect('/admin/events.php');
    }

    $eventName = trim($_POST['event_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $startDate = trim($_POST['start_date'] ?? '');
    $endDate = trim($_POST['end_date'] ?? '');
    $maxMember = (int) ($_POST['max_member'] ?? 0);
    $status = $_POST['status'] ?? 'published';

    if ($eventName === '' || $description === '' || $location === '' || $startDate === '' || $maxMember <= 0) {
        flash_set('error', 'Vui lòng nhập đầy đủ thông tin sự kiện.');
        redirect('/admin/events.php');
    }

    $slug = slugify($eventName) . '-' . time();

    if ($id > 0) {
        $updateStmt = db()->prepare('UPDATE events SET event_name = :event_name, description = :description, location = :location, start_date = :start_date, end_date = :end_date, max_member = :max_member, status = :status WHERE id = :id');
        $updateStmt->execute([
            'event_name' => $eventName,
            'description' => $description,
            'location' => $location,
            'start_date' => $startDate,
            'end_date' => $endDate !== '' ? $endDate : null,
            'max_member' => $maxMember,
            'status' => $status,
            'id' => $id,
        ]);
        flash_set('success', 'Đã cập nhật sự kiện.');
    } else {
        $insertStmt = db()->prepare('INSERT INTO events (event_name, slug, description, location, start_date, end_date, max_member, status, created_by) VALUES (:event_name, :slug, :description, :location, :start_date, :end_date, :max_member, :status, :created_by)');
        $insertStmt->execute([
            'event_name' => $eventName,
            'slug' => $slug,
            'description' => $description,
            'location' => $location,
            'start_date' => $startDate,
            'end_date' => $endDate !== '' ? $endDate : null,
            'max_member' => $maxMember,
            'status' => $status,
            'created_by' => current_user_id(),
        ]);
        flash_set('success', 'Đã thêm sự kiện.');
    }

    redirect('/admin/events.php');
}

$editId = (int) ($_GET['edit'] ?? 0);
$editEvent = null;
if ($editId > 0) {
    $stmt = db()->prepare('SELECT * FROM events WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $editId]);
    $editEvent = $stmt->fetch();
}

$events = db()->query("SELECT e.*, u.fullname AS creator_name, COUNT(er.id) AS registered_count
    FROM events e
    INNER JOIN users u ON u.id = e.created_by
    LEFT JOIN event_registrations er ON er.event_id = e.id
    GROUP BY e.id
    ORDER BY e.start_date DESC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="clubit-card p-4">
            <h3 class="section-title mb-3"><?php echo $editEvent ? 'Sửa sự kiện' : 'Thêm sự kiện'; ?></h3>
            <form method="post" class="row g-3">
                <input type="hidden" name="id" value="<?php echo (int) ($editEvent['id'] ?? 0); ?>">
                <div class="col-12">
                    <label class="form-label">Tên sự kiện</label>
                    <input type="text" name="event_name" class="form-control" value="<?php echo e((string) ($editEvent['event_name'] ?? '')); ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" class="form-control" rows="4" required><?php echo e((string) ($editEvent['description'] ?? '')); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Địa điểm</label>
                    <input type="text" name="location" class="form-control" value="<?php echo e((string) ($editEvent['location'] ?? '')); ?>" required>
                </div>
                <div class="col-6">
                    <label class="form-label">Thời gian bắt đầu</label>
                    <input type="datetime-local" name="start_date" class="form-control" value="<?php echo e(isset($editEvent['start_date']) ? date('Y-m-d\TH:i', strtotime($editEvent['start_date'])) : ''); ?>" required>
                </div>
                <div class="col-6">
                    <label class="form-label">Kết thúc</label>
                    <input type="datetime-local" name="end_date" class="form-control" value="<?php echo e(isset($editEvent['end_date']) ? date('Y-m-d\TH:i', strtotime($editEvent['end_date'])) : ''); ?>">
                </div>
                <div class="col-6">
                    <label class="form-label">Giới hạn</label>
                    <input type="number" name="max_member" min="1" class="form-control" value="<?php echo e((string) ($editEvent['max_member'] ?? 40)); ?>" required>
                </div>
                <div class="col-6">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="published" <?php echo ($editEvent['status'] ?? 'published') === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo ($editEvent['status'] ?? 'published') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="archived" <?php echo ($editEvent['status'] ?? 'published') === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>
                <div class="col-12 d-grid">
                    <button class="btn btn-primary"><?php echo $editEvent ? 'Cập nhật' : 'Thêm mới'; ?></button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="clubit-card p-4">
            <h3 class="section-title mb-3">Danh sách sự kiện</h3>
            <div class="table-responsive">
                <table class="table table-clubit align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-white">Tên</th>
                            <th class="text-white">Địa điểm</th>
                            <th class="text-white">Đăng ký</th>
                            <th class="text-white">Trạng thái</th>
                            <th class="text-white">Ngày bắt đầu</th>
                            <th class="text-white">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td class="text-white"><?php echo e(short_text($event['event_name'], 35)); ?></td>
                                <td class="text-white"><?php echo e($event['location']); ?></td>
                                <td class="text-white"><?php echo e((string) $event['registered_count']); ?>/<?php echo e((string) $event['max_member']); ?></td>
                                <td><span class="badge <?php echo e(badge_class_for_status($event['status'])); ?>"><?php echo e($event['status']); ?></span></td>
                                <td class="text-white"><?php echo e(format_datetime($event['start_date'])); ?></td>
                                <td class="d-flex gap-2 flex-wrap">
                                    <a class="btn btn-outline-info btn-sm" href="event-registrations.php?event_id=<?php echo (int) $event['id']; ?>"><i class="bi bi-list-check"></i> Danh sách</a>
                                    <a class="btn btn-outline-primary btn-sm" href="?edit=<?php echo (int) $event['id']; ?>">Sửa</a>
                                    <form method="post" onsubmit="return confirm('Xóa sự kiện này?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int) $event['id']; ?>">
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
