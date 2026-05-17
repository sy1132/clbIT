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
<div class="row g-4">
    <div class="col-lg-4">
        <div class="clubit-card p-4 text-center">
            <img src="<?php echo e($user['avatar'] ? UPLOAD_URL . '/' . $user['avatar'] : BASE_URL . '/assets/images/default-avatar.svg'); ?>" alt="Avatar" width="140" height="140" class="rounded-circle mb-3" style="object-fit:cover;">
            <h3 class="fw-bold mb-1"><?php echo e($user['fullname']); ?></h3>
            <div class="text-secondary mb-3"><?php echo e($user['email']); ?></div>
            <div class="soft-badge mb-2"><?php echo e($user['role']); ?></div>
            <div class="small text-secondary">Tham gia từ <?php echo e(format_date($user['created_at'])); ?></div>
        </div>
    </div>
    <div class="col-lg-8">
        <?php if ($isOwnProfile): ?>
            <div class="clubit-card p-4 mb-4">
                <h2 class="section-title mb-3">Cập nhật hồ sơ</h2>
                <form method="post" enctype="multipart/form-data" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Họ và tên</label>
                        <input type="text" name="fullname" class="form-control" value="<?php echo e($user['fullname']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ảnh đại diện</label>
                        <input type="file" name="avatar" class="form-control" accept="image/*">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Giới thiệu</label>
                        <textarea name="bio" class="form-control" rows="3"><?php echo e((string) $user['bio']); ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Kỹ năng IT</label>
                        <input type="text" name="skills" class="form-control" value="<?php echo e((string) $user['skills']); ?>" placeholder="PHP, MySQL, UI/UX...">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Mật khẩu mới</label>
                        <input type="password" name="password" class="form-control" placeholder="Để trống nếu không đổi">
                    </div>
                    <div class="col-12 d-grid d-md-flex justify-content-md-end">
                        <button class="btn btn-primary">Lưu hồ sơ</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="clubit-card p-4 mb-4">
                <h2 class="section-title mb-3">Thông tin hồ sơ</h2>
                <div class="text-secondary mb-2"><?php echo nl2br(e((string) ($user['bio'] ?? 'Chưa có giới thiệu.'))); ?></div>
                <div class="soft-badge mb-2"><?php echo e((string) ($user['skills'] ?: 'Chưa có kỹ năng')); ?></div>
                <div class="small text-secondary">Đây là hồ sơ công khai của thành viên.</div>
            </div>
        <?php endif; ?>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="clubit-card p-4 h-100">
                    <h3 class="section-title mb-3">Lịch sử tham gia</h3>
                    <?php foreach ($registeredEvents as $event): ?>
                        <div class="border-bottom py-2">
                            <div class="fw-semibold"><?php echo e($event['event_name']); ?></div>
                            <div class="small text-secondary">Đăng ký lúc <?php echo e(format_datetime($event['registered_at'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$registeredEvents): ?>
                        <div class="text-secondary">Chưa có hoạt động nào.</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="clubit-card p-4 h-100">
                    <h3 class="section-title mb-3">Bài viết của tôi</h3>
                    <?php foreach ($myPosts as $post): ?>
                        <div class="border-bottom py-2">
                            <div class="fw-semibold"><?php echo e($post['title']); ?></div>
                            <div class="small text-secondary"><?php echo e($post['status']); ?> | <?php echo e(format_datetime($post['published_at'] ?? null)); ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$myPosts): ?>
                        <div class="text-secondary">Chưa có bài viết nào.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
