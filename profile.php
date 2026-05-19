<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$pageLayout = 'public';
$viewer = current_user_row();
$profileId = (int) ($_GET['id'] ?? 0);

if ($profileId > 0) {
    $profileStmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $profileStmt->execute(['id' => $profileId]);
    $user = $profileStmt->fetch();
    if (!$user) {
        http_response_code(404);
        echo 'Không tìm thấy hồ sơ.';
        exit;
    }
} else {
    $user = $viewer;
}

$isOwnProfile = (int) $viewer['id'] === (int) $user['id'];
$pageTitle = $isOwnProfile ? 'Hồ sơ của tôi' : ('Hồ sơ của ' . $user['fullname']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isOwnProfile) {
        http_response_code(403);
        echo 'Bạn không có quyền chỉnh sửa hồ sơ này.';
        exit;
    }

    $fullname = trim($_POST['fullname'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($fullname === '') {
        flash_set('error', 'Họ tên không được để trống.');
        redirect('/profile.php');
    }

    $avatar = $user['avatar'];
    try {
        $newAvatar = upload_file('avatar', ['jpg', 'jpeg', 'png', 'webp']);
        if ($newAvatar) {
            $avatar = $newAvatar;
        }
    } catch (Throwable $throwable) {
        flash_set('error', $throwable->getMessage());
        redirect('/profile.php');
    }

    $sql = 'UPDATE users SET fullname = :fullname, bio = :bio, skills = :skills, avatar = :avatar';
    $params = [
        'fullname' => $fullname,
        'bio' => $bio,
        'skills' => $skills,
        'avatar' => $avatar,
        'id' => $user['id'],
    ];

    if ($password !== '') {
        if (strlen($password) < 6) {
            flash_set('error', 'Mật khẩu mới phải từ 6 ký tự trở lên.');
            redirect('/profile.php');
        }
        $sql .= ', password = :password';
        $params['password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    $sql .= ' WHERE id = :id';
    $updateStmt = db()->prepare($sql);
    $updateStmt->execute($params);

    $_SESSION['user']['fullname'] = $fullname;
    $_SESSION['user']['avatar'] = $avatar;

    if ((int) $viewer['id'] === (int) $user['id']) {
        $user['fullname'] = $fullname;
        $user['bio'] = $bio;
        $user['skills'] = $skills;
        $user['avatar'] = $avatar;
    }

    flash_set('success', 'Cập nhật hồ sơ thành công.');
    redirect('/profile.php');
}

$registeredEventsStmt = db()->prepare("SELECT e.event_name, e.start_date, er.registered_at
    FROM event_registrations er
    INNER JOIN events e ON e.id = er.event_id
    WHERE er.user_id = :user_id
    ORDER BY er.registered_at DESC");
$registeredEventsStmt->execute(['user_id' => $user['id']]);
$registeredEvents = $registeredEventsStmt->fetchAll();

$myPostsStmt = db()->prepare('SELECT id, title, published_at, status FROM posts WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5');
$myPostsStmt->execute(['user_id' => $user['id']]);
$myPosts = $myPostsStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="row g-4 animate-on-scroll">
    <?php if ($isOwnProfile): ?>
        <div class="col-lg-3">
            <!-- Sidebar Navigation for Settings -->
            <div class="clubit-card p-3 sidebar-glass">
                <div class="d-flex align-items-center gap-3 p-3 border-bottom border-white border-opacity-10 mb-3">
                    <img src="<?php echo e($viewer['avatar'] ? UPLOAD_URL . '/' . $viewer['avatar'] : BASE_URL . '/assets/images/default-avatar.svg'); ?>" alt="Avatar" width="48" height="48" class="rounded-circle" style="object-fit: cover;">
                    <div>
                        <h6 class="fw-bold mb-0"><?php echo e($viewer['fullname']); ?></h6>
                        <span class="small text-secondary"><?php echo e($viewer['role']); ?></span>
                    </div>
                </div>
                <div class="d-grid gap-1">
                    <a href="<?php echo e(BASE_URL); ?>/settings.php" class="sidebar-link"><i class="bi bi-sliders me-2"></i> Cấu hình chung</a>
                    <a href="<?php echo e(BASE_URL); ?>/profile.php" class="sidebar-link active"><i class="bi bi-person me-2"></i> Hồ sơ</a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-9">
            <!-- Profile Update Panel -->
            <div class="clubit-card p-4 mb-4">
                <h4 class="fw-bold mb-3"><i class="bi bi-person-gear text-primary me-2"></i> Cập nhật hồ sơ</h4>
                <p class="text-secondary small mb-4">Cập nhật thông tin cá nhân, ảnh đại diện và kỹ năng của bạn.</p>
                
                <form method="post" enctype="multipart/form-data" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-semibold">Họ và tên</label>
                        <input type="text" name="fullname" class="form-control" value="<?php echo e($user['fullname']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-semibold">Ảnh đại diện</label>
                        <input type="file" name="avatar" class="form-control" accept="image/*">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-secondary small fw-semibold">Giới thiệu</label>
                        <textarea name="bio" class="form-control" rows="3"><?php echo e((string) $user['bio']); ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-secondary small fw-semibold">Kỹ năng IT</label>
                        <input type="text" name="skills" class="form-control" value="<?php echo e((string) $user['skills']); ?>" placeholder="PHP, MySQL, UI/UX..." autocomplete="off">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-secondary small fw-semibold">Mật khẩu mới</label>
                        <input type="password" name="password" class="form-control" placeholder="Để trống nếu không đổi" autocomplete="new-password">
                    </div>
                    <div class="col-12 text-end mt-4">
                        <button class="btn btn-primary rounded-pill px-4"><i class="bi bi-check-circle me-2"></i> Lưu hồ sơ</button>
                    </div>
                </form>
            </div>
    <?php else: ?>
        <div class="col-lg-3">
            <!-- Public User Info Sidebar -->
            <div class="clubit-card p-4 text-center">
                <img src="<?php echo e($user['avatar'] ? UPLOAD_URL . '/' . $user['avatar'] : BASE_URL . '/assets/images/default-avatar.svg'); ?>" alt="Avatar" width="120" height="120" class="rounded-circle mb-3 border border-3 border-primary border-opacity-25" style="object-fit:cover;">
                <h4 class="fw-bold mb-1"><?php echo e($user['fullname']); ?></h4>
                <div class="text-secondary small mb-3"><?php echo e($user['email']); ?></div>
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill small mb-3 d-inline-block"><?php echo e($user['role']); ?></span>
                <div class="small text-secondary border-top border-white border-opacity-10 pt-3 mt-2">
                    Tham gia từ<br><span class="fw-semibold text-white"><?php echo e(format_date($user['created_at'])); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-lg-9">
            <!-- Public Bio Panel -->
            <div class="clubit-card p-4 mb-4">
                <h4 class="fw-bold mb-3"><i class="bi bi-person-badge text-primary me-2"></i> Thông tin hồ sơ</h4>
                <p class="text-secondary small mb-4">Đây là hồ sơ công khai của thành viên.</p>
                
                <div class="mb-4">
                    <label class="form-label text-secondary small fw-semibold d-block">Giới thiệu</label>
                    <div class="p-3 rounded-4 bg-dark bg-opacity-25 border border-white border-opacity-10 text-white">
                        <?php echo nl2br(e((string) ($user['bio'] ?? 'Chưa có giới thiệu.'))); ?>
                    </div>
                </div>
                
                <div>
                    <label class="form-label text-secondary small fw-semibold d-block">Kỹ năng IT</label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($user['skills']): ?>
                            <?php foreach (explode(',', $user['skills']) as $skill): ?>
                                <span class="badge bg-light bg-opacity-10 text-light px-3 py-2 rounded-pill small"><?php echo e(trim($skill)); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-secondary small italic">Chưa cập nhật kỹ năng.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
    <?php endif; ?>

            <!-- Common Activity & Posts Section -->
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="clubit-card p-4 h-100">
                        <h5 class="fw-bold mb-3"><i class="bi bi-calendar-event text-primary me-2"></i> Lịch sử tham gia</h5>
                        <div class="d-grid gap-3">
                            <?php foreach ($registeredEvents as $event): ?>
                                <div class="p-3 rounded-4 bg-dark bg-opacity-25 border border-white border-opacity-10">
                                    <div class="fw-bold text-white small mb-1"><?php echo e($event['event_name']); ?></div>
                                    <div class="text-secondary small d-flex align-items-center gap-1">
                                        <i class="bi bi-clock small"></i> Đăng ký: <?php echo e(format_datetime($event['registered_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (!$registeredEvents): ?>
                                <div class="text-secondary small p-3 rounded-4 bg-dark bg-opacity-25 border border-white border-opacity-10 text-center">
                                    Chưa có hoạt động nào.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="clubit-card p-4 h-100">
                        <h5 class="fw-bold mb-3"><i class="bi bi-file-earmark-post text-primary me-2"></i> Bài viết của <?php echo $isOwnProfile ? 'tôi' : 'thành viên'; ?></h5>
                        <div class="d-grid gap-3">
                            <?php foreach ($myPosts as $post): ?>
                                <a href="<?php echo e(BASE_URL); ?>/post.php?id=<?php echo (int) $post['id']; ?>" class="p-3 rounded-4 bg-dark bg-opacity-25 border border-white border-opacity-10 text-decoration-none d-block hover-lift">
                                    <div class="fw-bold text-white small mb-1"><?php echo e($post['title']); ?></div>
                                    <div class="text-secondary small d-flex align-items-center gap-2 justify-content-between">
                                        <span><i class="bi bi-info-circle small"></i> <?php echo e($post['status']); ?></span>
                                        <span><?php echo e(format_datetime($post['published_at'] ?? null)); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                            <?php if (!$myPosts): ?>
                                <div class="text-secondary small p-3 rounded-4 bg-dark bg-opacity-25 border border-white border-opacity-10 text-center">
                                    Chưa có bài viết nào.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- Close col-lg-9 -->
</div> <!-- Close row -->
<?php require_once __DIR__ . '/includes/footer.php'; ?>
