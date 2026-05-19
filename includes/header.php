<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$pageLayout = $pageLayout ?? 'public';
$pageTitle = $pageTitle ?? APP_NAME;
$flash = flash_get();
$bodyClass = is_logged_in() ? 'clubit-logged-in' : 'clubit-public';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?> | <?php echo e(APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/assets/css/app.css">
    <script>
        (function() {
            let theme = localStorage.getItem('theme');
            if (!theme) {
                theme = '<?php echo is_logged_in() ? "dark" : "light"; ?>';
            }
            document.documentElement.setAttribute('data-theme', theme);

            let compact = localStorage.getItem('compact');
            if (compact === 'true') {
                document.documentElement.setAttribute('data-compact', 'true');
            }
        })();
        window.clubitConfig = {
            baseUrl: "<?php echo e(BASE_URL); ?>",
            uploadUrl: "<?php echo e(UPLOAD_URL); ?>",
            isLoggedIn: <?php echo is_logged_in() ? 'true' : 'false'; ?>,
            userAvatar: "<?php echo e(current_user_avatar()); ?>"
        };
    </script>
</head>
<body class="<?php echo e($bodyClass); ?>">
<div id="page-loader" class="page-loader">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>
<?php if ($pageLayout === 'auth'): ?>
<div class="container py-5">
    <?php if ($flash): ?>
        <div class="alert alert-<?php echo e($flash['type'] === 'error' ? 'danger' : 'success'); ?> clubit-card border-0 mb-4">
            <?php echo e((string) $flash['message']); ?>
        </div>
    <?php endif; ?>
<?php else: ?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-glass sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo e(BASE_URL); ?>/index.php"><?php echo e(APP_NAME); ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#clubitNav" aria-controls="clubitNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="clubitNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link" href="<?php echo e(BASE_URL); ?>/posts.php">Bài viết</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo e(BASE_URL); ?>/events.php">Sự kiện</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo e(BASE_URL); ?>/documents.php">Tài liệu</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo e(BASE_URL); ?>/community.php">Cộng đồng</a></li>
                <?php if (is_logged_in()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo e(BASE_URL); ?>/my-posts.php">Bài của tôi</a></li>
                    <?php if (current_user_role() === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link fw-semibold text-warning" href="<?php echo e(BASE_URL); ?>/admin/posts.php"><i class="bi bi-patch-check-fill me-1"></i>Duyệt bài</a></li>
                        <li class="nav-item"><a class="nav-link fw-semibold text-info" href="<?php echo e(BASE_URL); ?>/admin/users.php"><i class="bi bi-people-fill me-1"></i>Quản lý tài khoản</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link btn btn-outline-primary btn-sm mx-lg-2" href="<?php echo e(BASE_URL); ?>/create-post.php">Viết bài</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle p-0 d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?php echo e(current_user_avatar()); ?>" class="rounded-circle border border-2 border-primary border-opacity-25" width="36" height="36" alt="Avatar" style="object-fit: cover;">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="<?php echo e(BASE_URL); ?>/profile.php"><i class="bi bi-person me-2"></i> Hồ sơ</a></li>
                            <li><a class="dropdown-item" href="<?php echo e(BASE_URL); ?>/settings.php"><i class="bi bi-gear me-2"></i> Cài đặt</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" id="theme-toggle"><i class="bi bi-moon-stars me-2"></i> Chế độ tối</a></li>
                            <?php if (current_user_role() === 'admin'): ?>
                                <li><a class="dropdown-item" href="<?php echo e(BASE_URL); ?>/admin/index.php"><i class="bi bi-speedometer2 me-2"></i> Quản trị</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo e(BASE_URL); ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Đăng xuất</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo e(BASE_URL); ?>/login.php">Đăng nhập</a></li>
                    <li class="nav-item"><a class="btn btn-warning btn-sm" href="<?php echo e(BASE_URL); ?>/register.php">Đăng ký</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="py-4">
    <div class="container">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo e($flash['type'] === 'error' ? 'danger' : 'success'); ?> clubit-card border-0 mb-4">
                <?php echo e((string) $flash['message']); ?>
            </div>
        <?php endif; ?>
        <?php if ($pageLayout === 'admin'): ?>
            <div class="clubit-card p-3 mb-4 border border-primary border-opacity-10" style="background: rgba(var(--primary-rgb), 0.03);">
                <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-3 px-2.5 py-1.5 fw-bold"><i class="bi bi-shield-lock-fill"></i> ADMIN</span>
                        <h4 class="fw-bold mb-0 text-white"><?php echo e($pageTitle ?? 'Quản trị'); ?></h4>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?php echo e(BASE_URL); ?>/admin/index.php" class="btn btn-sm btn-outline-light rounded-pill px-3 <?php echo strpos($_SERVER['SCRIPT_NAME'], '/admin/index.php') !== false ? 'active' : ''; ?>"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
                        <a href="<?php echo e(BASE_URL); ?>/admin/posts.php" class="btn btn-sm btn-outline-light rounded-pill px-3 <?php echo strpos($_SERVER['SCRIPT_NAME'], '/admin/posts.php') !== false ? 'active' : ''; ?>"><i class="bi bi-journal-text me-1"></i> Duyệt bài</a>
                        <a href="<?php echo e(BASE_URL); ?>/admin/categories.php" class="btn btn-sm btn-outline-light rounded-pill px-3 <?php echo strpos($_SERVER['SCRIPT_NAME'], '/admin/categories.php') !== false ? 'active' : ''; ?>"><i class="bi bi-tags me-1"></i> Chuyên mục</a>
                        <a href="<?php echo e(BASE_URL); ?>/admin/users.php" class="btn btn-sm btn-outline-light rounded-pill px-3 <?php echo strpos($_SERVER['SCRIPT_NAME'], '/admin/users.php') !== false ? 'active' : ''; ?>"><i class="bi bi-people me-1"></i> Thành viên</a>
                        <a href="<?php echo e(BASE_URL); ?>/admin/events.php" class="btn btn-sm btn-outline-light rounded-pill px-3 <?php echo (strpos($_SERVER['SCRIPT_NAME'], '/admin/events.php') !== false || strpos($_SERVER['SCRIPT_NAME'], '/admin/event-registrations.php') !== false) ? 'active' : ''; ?>"><i class="bi bi-calendar-event me-1"></i> Sự kiện</a>
                        <a href="<?php echo e(BASE_URL); ?>/admin/documents.php" class="btn btn-sm btn-outline-light rounded-pill px-3 <?php echo strpos($_SERVER['SCRIPT_NAME'], '/admin/documents.php') !== false ? 'active' : ''; ?>"><i class="bi bi-folder2-open me-1"></i> Tài liệu</a>
                        <a href="<?php echo e(BASE_URL); ?>/admin/comments.php" class="btn btn-sm btn-outline-light rounded-pill px-3 <?php echo strpos($_SERVER['SCRIPT_NAME'], '/admin/comments.php') !== false ? 'active' : ''; ?>"><i class="bi bi-chat-dots me-1"></i> Bình luận</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
<?php endif; ?>
