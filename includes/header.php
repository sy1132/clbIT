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
</head>
<body class="<?php echo e($bodyClass); ?>">
<?php if ($pageLayout === 'admin'): ?>
<div class="container-fluid py-3">
    <div class="row g-4">
        <div class="col-lg-3 col-xl-2">
            <div class="sidebar-shell">
                <div class="sidebar-glass p-3 p-lg-4">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="rounded-4 bg-white text-dark fw-bold d-grid place-items-center" style="width:56px;height:56px;">IT</div>
                        <div>
                            <div class="fw-bold fs-5"><?php echo e(APP_NAME); ?></div>
                            <div class="small text-white-50">Admin panel</div>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="<?php echo e(BASE_URL); ?>/admin/index.php" class="sidebar-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
                        <a href="<?php echo e(BASE_URL); ?>/admin/users.php" class="sidebar-link"><i class="bi bi-people"></i> Thành viên</a>
                        <a href="<?php echo e(BASE_URL); ?>/admin/posts.php" class="sidebar-link"><i class="bi bi-journal-text"></i> Bài viết</a>
                        <a href="<?php echo e(BASE_URL); ?>/admin/events.php" class="sidebar-link"><i class="bi bi-calendar-event"></i> Sự kiện</a>
                        <a href="<?php echo e(BASE_URL); ?>/admin/documents.php" class="sidebar-link"><i class="bi bi-folder2-open"></i> Tài liệu</a>
                        <a href="<?php echo e(BASE_URL); ?>/admin/comments.php" class="sidebar-link"><i class="bi bi-chat-dots"></i> Bình luận</a>
                        <a href="<?php echo e(BASE_URL); ?>/index.php" class="sidebar-link"><i class="bi bi-house"></i> Trang CLB</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-9 col-xl-10">
            <div class="clubit-panel p-3 p-lg-4 mb-4 d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center">
                <div>
                    <div class="text-uppercase text-white-50 small">Xin chào</div>
                    <div class="fs-4 fw-bold"><?php echo e(current_user_name()); ?></div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?php echo e(BASE_URL); ?>/profile.php" class="btn btn-outline-light">Hồ sơ</a>
                    <a href="<?php echo e(BASE_URL); ?>/logout.php" class="btn btn-light">Đăng xuất</a>
                </div>
            </div>
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['type'] === 'error' ? 'danger' : 'success'); ?> clubit-card border-0">
                    <?php echo e((string) $flash['message']); ?>
                </div>
            <?php endif; ?>
<?php elseif ($pageLayout === 'auth'): ?>
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
                    <li class="nav-item"><a class="nav-link btn btn-outline-primary btn-sm" href="<?php echo e(BASE_URL); ?>/create-post.php">Viết bài</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo e(BASE_URL); ?>/profile.php">Hồ sơ</a></li>
                    <?php if (current_user_role() === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo e(BASE_URL); ?>/admin/index.php">Quản trị</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo e(BASE_URL); ?>/logout.php">Đăng xuất</a></li>
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
<?php endif; ?>
