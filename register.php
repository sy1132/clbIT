<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect(current_user_role() === 'admin' ? '/admin/index.php' : '/index.php');
}

$pageLayout = 'auth';
$pageTitle = 'Đăng ký';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($fullname === '' || $email === '' || $password === '' || $confirmPassword === '') {
        flash_set('error', 'Vui lòng nhập đầy đủ thông tin.');
        redirect('/register.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('error', 'Email không hợp lệ.');
        redirect('/register.php');
    }

    if (strlen($password) < 6) {
        flash_set('error', 'Mật khẩu phải từ 6 ký tự trở lên.');
        redirect('/register.php');
    }

    if ($password !== $confirmPassword) {
        flash_set('error', 'Xác nhận mật khẩu không khớp.');
        redirect('/register.php');
    }

    $checkStmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $checkStmt->execute(['email' => $email]);
    if ($checkStmt->fetch()) {
        flash_set('error', 'Email đã tồn tại.');
        redirect('/register.php');
    }

    $avatar = null;
    try {
        $avatar = upload_file('avatar', ['jpg', 'jpeg', 'png', 'webp']);
    } catch (Throwable $throwable) {
        flash_set('error', $throwable->getMessage());
        redirect('/register.php');
    }

    $stmt = db()->prepare('INSERT INTO users (fullname, email, password, avatar, bio, skills, role, status) VALUES (:fullname, :email, :password, :avatar, :bio, :skills, :role, :status)');
    $stmt->execute([
        'fullname' => $fullname,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'avatar' => $avatar,
        'bio' => '',
        'skills' => '',
        'role' => 'member',
        'status' => 'active',
    ]);

    flash_set('success', 'Đăng ký thành công. Hãy đăng nhập.');
    redirect('/login.php');
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="clubit-card p-4 p-lg-5">
            <div class="hero-kicker mb-3"><i class="bi bi-person-plus"></i> Tạo tài khoản thành viên</div>
            <h1 class="section-title mb-2">Tham gia CLB IT</h1>
            <p class="text-secondary mb-4">Tạo hồ sơ để đăng ký sự kiện, tải tài liệu và tham gia hoạt động của CLB.</p>
            <form method="post" enctype="multipart/form-data" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Họ và tên</label>
                    <input type="text" name="fullname" class="form-control form-control-lg" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control form-control-lg" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Mật khẩu</label>
                    <input type="password" name="password" class="form-control form-control-lg" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Xác nhận mật khẩu</label>
                    <input type="password" name="confirm_password" class="form-control form-control-lg" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Ảnh đại diện</label>
                    <input type="file" name="avatar" class="form-control" accept="image/*" data-auto-submit="false">
                </div>
                <div class="col-12 d-grid">
                    <button class="btn btn-warning btn-lg">Đăng ký</button>
                </div>
            </form>
            <div class="mt-3 text-center">
                <span class="text-secondary">Đã có tài khoản?</span> <a href="<?php echo e(BASE_URL); ?>/login.php">Đăng nhập</a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
