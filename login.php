<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect(current_user_role() === 'admin' ? '/admin/index.php' : '/index.php');
}

$pageLayout = 'auth';
$pageTitle = 'Đăng nhập';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        flash_set('error', 'Vui lòng nhập email và mật khẩu.');
        redirect('/login.php');
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, (string) $user['password'])) {
        flash_set('error', 'Email hoặc mật khẩu không đúng.');
        redirect('/login.php');
    }

    if ($user['status'] !== 'active') {
        flash_set('error', 'Tài khoản đang bị khóa.');
        redirect('/login.php');
    }

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'fullname' => (string) $user['fullname'],
        'email' => (string) $user['email'],
        'avatar' => $user['avatar'],
        'role' => (string) $user['role'],
        'status' => (string) $user['status'],
    ];

    flash_set('success', 'Đăng nhập thành công.');
    redirect($user['role'] === 'admin' ? '/admin/index.php' : '/index.php');
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="clubit-card p-4 p-lg-5">
            <div class="hero-kicker mb-3"><i class="bi bi-shield-lock"></i> Đăng nhập thành viên</div>
            <h1 class="section-title mb-2">Chào mừng quay lại</h1>
            <p class="text-secondary mb-4">Đăng nhập để quản lý hồ sơ, đăng ký sự kiện và bình luận bài viết.</p>
            <form method="post" class="row g-3">
                <div class="col-12">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control form-control-lg" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Mật khẩu</label>
                    <input type="password" name="password" class="form-control form-control-lg" required>
                </div>
                <div class="col-12 d-grid">
                    <button class="btn btn-primary btn-lg">Đăng nhập</button>
                </div>
            </form>
            <div class="mt-3 text-center">
                <span class="text-secondary">Chưa có tài khoản?</span> <a href="<?php echo e(BASE_URL); ?>/register.php">Đăng ký ngay</a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
