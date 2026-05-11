<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$pageLayout = 'admin';
$pageTitle = 'Dashboard quản trị';

$stats = [
    'members' => (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'member'")->fetchColumn(),
    'posts' => (int) db()->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
    'events' => (int) db()->query('SELECT COUNT(*) FROM events')->fetchColumn(),
    'documents' => (int) db()->query('SELECT COUNT(*) FROM documents')->fetchColumn(),
    'registrations' => (int) db()->query('SELECT COUNT(*) FROM event_registrations')->fetchColumn(),
    'comments' => (int) db()->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn(),
];

$recentUsers = db()->query('SELECT fullname, email, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 5')->fetchAll();
$recentRegistrations = db()->query("SELECT er.full_name, er.phone, er.registered_at, e.event_name
    FROM event_registrations er
    INNER JOIN events e ON e.id = er.event_id
    ORDER BY er.registered_at DESC
    LIMIT 5")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4 mb-4">
    <div class="col-md-4 col-xl-2">
        <div class="clubit-card p-3 h-100">
            <div class="text-secondary small">Thành viên</div>
            <div class="fs-3 fw-bold"><?php echo e((string) $stats['members']); ?></div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="clubit-card p-3 h-100">
            <div class="text-secondary small">Bài viết</div>
            <div class="fs-3 fw-bold"><?php echo e((string) $stats['posts']); ?></div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="clubit-card p-3 h-100">
            <div class="text-secondary small">Sự kiện</div>
            <div class="fs-3 fw-bold"><?php echo e((string) $stats['events']); ?></div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="clubit-card p-3 h-100">
            <div class="text-secondary small">Tài liệu</div>
            <div class="fs-3 fw-bold"><?php echo e((string) $stats['documents']); ?></div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="clubit-card p-3 h-100">
            <div class="text-secondary small">Đăng ký</div>
            <div class="fs-3 fw-bold"><?php echo e((string) $stats['registrations']); ?></div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="clubit-card p-3 h-100">
            <div class="text-secondary small">Chờ duyệt</div>
            <div class="fs-3 fw-bold"><?php echo e((string) $stats['comments']); ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="clubit-card p-4 h-100">
            <h3 class="section-title mb-3">Thành viên mới</h3>
            <div class="table-responsive">
                <table class="table table-clubit align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $user): ?>
                            <tr>
                                <td><?php echo e($user['fullname']); ?></td>
                                <td><?php echo e($user['email']); ?></td>
                                <td><span class="badge text-bg-primary"><?php echo e($user['role']); ?></span></td>
                                <td><span class="badge <?php echo e(badge_class_for_status($user['status'])); ?>"><?php echo e($user['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="clubit-card p-4 h-100">
            <h3 class="section-title mb-3">Đăng ký gần đây</h3>
            <div class="table-responsive">
                <table class="table table-clubit align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Người đăng ký</th>
                            <th>Sự kiện</th>
                            <th>Thời gian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentRegistrations as $registration): ?>
                            <tr>
                                <td><?php echo e($registration['full_name']); ?></td>
                                <td><?php echo e($registration['event_name']); ?></td>
                                <td><?php echo e(format_datetime($registration['registered_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
