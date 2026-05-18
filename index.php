<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['posts_seed'] = rand(1, 999999);
$seed = $_SESSION['posts_seed'];
$hideFooter = true;

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageLayout = 'public';
$pageTitle = 'Trang chủ';

$stats = [
    'members' => (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'member' AND status = 'active'")->fetchColumn(),
    'posts' => (int) db()->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn(),
    'events' => (int) db()->query("SELECT COUNT(*) FROM events WHERE status = 'published' AND start_date >= NOW()")->fetchColumn(),
    'documents' => (int) db()->query('SELECT COUNT(*) FROM documents')->fetchColumn(),
];

$currentUserId = current_user_id() ?? 0;
$featuredPosts = db()->query("SELECT p.id, p.title, p.excerpt, p.image, p.images, p.files, p.published_at, p.privacy, c.name AS category_name, u.fullname AS author_name, u.avatar AS author_avatar FROM posts p INNER JOIN categories c ON c.id = p.category_id INNER JOIN users u ON u.id = p.user_id WHERE p.status = 'published' AND (p.privacy = 'public' OR p.user_id = $currentUserId) ORDER BY RAND($seed) LIMIT 3")->fetchAll();
$upcomingEvents = db()->query("SELECT e.*, COUNT(er.id) AS registered_count FROM events e LEFT JOIN event_registrations er ON er.event_id = e.id WHERE e.status = 'published' GROUP BY e.id ORDER BY e.start_date ASC LIMIT 3")->fetchAll();
$latestDocuments = db()->query("SELECT id, title, description, file_path, file_type FROM documents ORDER BY created_at DESC LIMIT 3")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="row g-4 animate-on-scroll">
    <!-- Main Newsfeed Column -->
    <div class="col-lg-8">
        <!-- Quick Post Box -->
        <div class="clubit-card p-3 p-lg-4 mb-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <img src="<?php echo e(current_user_avatar()); ?>" class="rounded-circle" width="48" height="48" alt="Avatar" style="object-fit: cover;">
                <button class="btn btn-light text-start text-muted w-100 rounded-pill px-4" style="height: 48px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--clubit-border);" onclick="window.location.href='<?php echo e(BASE_URL); ?>/create-post.php'">
                    <?php echo is_logged_in() ? e(current_user_name()) . ' ơi, bạn muốn chia sẻ điều gì hôm nay?' : 'Đăng nhập để chia sẻ câu chuyện của bạn...'; ?>
                </button>
            </div>
            <div class="d-flex gap-2 border-top pt-3 border-opacity-10 border-white">
                <a href="<?php echo e(BASE_URL); ?>/create-post.php" class="btn btn-sm text-white-50 flex-grow-1 hover-bg"><i class="bi bi-image text-success"></i> Hình ảnh / Code</a>
                <a href="<?php echo e(BASE_URL); ?>/create-post.php" class="btn btn-sm text-white-50 flex-grow-1 hover-bg"><i class="bi bi-tags text-info"></i> Gắn thẻ chủ đề</a>
            </div>
        </div>

        <!-- Timeline Posts -->
        <h5 class="fw-bold mb-3">Bảng tin mới nhất</h5>
        <div class="d-grid gap-4" id="posts-container">
            <?php foreach ($featuredPosts as $post): ?>
                <div class="clubit-card p-4 card-hover animate-on-scroll">
                    <!-- Post Header -->
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <?php if (!empty($post['author_avatar'])): ?>
                                <img src="<?php echo e(UPLOAD_URL . '/' . $post['author_avatar']); ?>" class="rounded-circle border border-2 border-primary border-opacity-25" width="48" height="48" alt="Avatar" style="object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 48px; height: 48px; font-size: 1.2rem;">
                                    <?php echo mb_substr(e($post['author_name']), 0, 1); ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <div class="fw-bold text-white fs-6"><?php echo e($post['author_name']); ?></div>
                                <div class="small text-secondary">
                                    <i class="bi bi-clock"></i> <?php echo e(format_datetime($post['published_at'] ?? $post['created_at'] ?? '')); ?> 
                                    · <?php if (($post['privacy'] ?? 'public') === 'private'): ?>
                                        <i class="bi bi-lock-fill text-warning" title="Riêng tư"></i>
                                    <?php else: ?>
                                        <i class="bi bi-globe-americas text-primary" title="Công khai"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <span class="soft-badge"><?php echo e($post['category_name']); ?></span>
                    </div>

                    <!-- Post Body -->
                    <h5 class="fw-bold mb-2">
                        <a href="<?php echo e(BASE_URL); ?>/post.php?id=<?php echo (int) $post['id']; ?>" class="text-white text-decoration-none hover-underline"><?php echo e($post['title']); ?></a>
                    </h5>
                    <p class="text-white-50 mb-3"><?php echo e($post['excerpt']); ?></p>

                    <!-- Embedded Multi-Images Grid -->
                    <?php 
                    $postImages = !empty($post['images']) ? json_decode($post['images'], true) : [];
                    if (!empty($postImages)): 
                        $imgCount = count($postImages);
                    ?>
                        <a href="<?php echo e(BASE_URL); ?>/post.php?id=<?php echo (int) $post['id']; ?>" class="d-block mb-3 post-media-grid grid-<?php echo min(4, $imgCount); ?>">
                            <?php foreach (array_slice($postImages, 0, 4) as $index => $img): ?>
                                <div class="grid-item position-relative">
                                    <img src="<?php echo e(UPLOAD_URL . '/' . $img); ?>" class="w-100 h-100" style="object-fit: cover; max-height: <?php echo $imgCount === 1 ? '400px' : ($imgCount === 2 ? '280px' : '200px'); ?>;" alt="Post image">
                                    <?php if ($index === 3 && $imgCount > 4): ?>
                                        <div class="grid-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-black bg-opacity-60 text-white fw-bold fs-4">
                                            +<?php echo ($imgCount - 4); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </a>
                    <?php elseif (!empty($post['image'])): ?>
                        <a href="<?php echo e(BASE_URL); ?>/post.php?id=<?php echo (int) $post['id']; ?>" class="d-block mb-3">
                            <img src="<?php echo e(UPLOAD_URL . '/' . $post['image']); ?>" class="img-fluid rounded-3 w-100" style="max-height: 400px; object-fit: cover;" alt="<?php echo e($post['title']); ?>">
                        </a>
                    <?php endif; ?>

                    <!-- Embedded File Attachments -->
                    <?php 
                    $postFiles = !empty($post['files']) ? json_decode($post['files'], true) : [];
                    if (!empty($postFiles)): 
                    ?>
                        <div class="post-attachments p-3 rounded-4 border border-white border-opacity-10 mb-3" style="background: rgba(255, 255, 255, 0.02);">
                            <div class="small text-white-50 fw-semibold mb-2"><i class="bi bi-paperclip"></i> Tài liệu đính kèm:</div>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($postFiles as $f): 
                                    $ext = strtolower($f['type'] ?? 'file');
                                    $iconClass = 'bi-file-earmark';
                                    $iconColor = 'text-secondary';

                                    if ($ext === 'pdf') { $iconClass = 'bi-file-earmark-pdf-fill'; $iconColor = 'text-danger'; }
                                    else if (in_array($ext, ['doc', 'docx'])) { $iconClass = 'bi-file-earmark-word-fill'; $iconColor = 'text-primary'; }
                                    else if (in_array($ext, ['ppt', 'pptx'])) { $iconClass = 'bi-file-earmark-slides-fill'; $iconColor = 'text-warning'; }
                                    else if (in_array($ext, ['xls', 'xlsx'])) { $iconClass = 'bi-file-earmark-excel-fill'; $iconColor = 'text-success'; }
                                    else if (in_array($ext, ['zip', 'rar'])) { $iconClass = 'bi-file-earmark-zip-fill'; $iconColor = 'text-info'; }
                                ?>
                                    <div class="d-flex align-items-center justify-content-between p-2 rounded-3 border border-white border-opacity-5" style="background: rgba(0, 0, 0, 0.15);">
                                        <div class="d-flex align-items-center gap-2 overflow-hidden">
                                            <i class="bi <?php echo $iconClass; ?> <?php echo $iconColor; ?> fs-4"></i>
                                            <div class="overflow-hidden">
                                                <div class="text-white text-truncate fw-semibold small" style="max-width: 250px;"><?php echo e($f['name']); ?></div>
                                                <div class="text-muted" style="font-size: 0.7rem;"><?php echo e($f['size'] ?? 'N/A'); ?></div>
                                            </div>
                                        </div>
                                        <a href="<?php echo e(UPLOAD_URL . '/' . $f['path']); ?>" download="<?php echo e($f['name']); ?>" class="btn btn-sm btn-primary py-1 px-3 rounded-pill fw-semibold fs-7 d-flex align-items-center gap-1">
                                            <i class="bi bi-download"></i> Tải về
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Post Actions -->
                    <div class="border-top pt-3 border-opacity-10 border-white d-flex justify-content-between">
                        <button type="button" class="btn btn-sm text-white-50 fw-semibold px-4 btn-reaction" data-post-id="<?php echo (int) $post['id']; ?>">
                            <span class="reaction-icon"><i class="bi bi-hand-thumbs-up"></i></span> 
                            <span class="reaction-text">Thích</span>
                            <span class="reaction-count ms-1"></span>
                        </button>
                        <button type="button" class="btn btn-sm text-white-50 fw-semibold px-4 btn-toggle-comments" data-post-id="<?php echo (int) $post['id']; ?>">
                            <i class="bi bi-chat-dots"></i> Bình luận
                        </button>
                        <button type="button" class="btn btn-sm text-white-50 fw-semibold px-4" onclick="navigator.clipboard.writeText('<?php echo BASE_URL; ?>/post.php?id=<?php echo (int) $post['id']; ?>'); alert('Đã copy link!');"><i class="bi bi-share"></i> Chia sẻ</button>
                    </div>

                    <!-- Inline Comments Section -->
                    <div class="inline-comments-section mt-3" id="inline-comments-<?php echo (int) $post['id']; ?>" style="display: none;">
                        <div class="comments-list mb-3 d-grid gap-2"></div>
                        <div class="text-center mb-2" style="display: none;"><button class="btn btn-sm btn-link text-white-50 text-decoration-none btn-load-more-comments" data-post-id="<?php echo (int) $post['id']; ?>" data-offset="0">Xem thêm bình luận</button></div>
                        <?php if (is_logged_in()): ?>
                            <form class="form-inline-comment d-flex gap-2 align-items-center" data-post-id="<?php echo (int) $post['id']; ?>">
                                <img src="<?php echo e(current_user_avatar()); ?>" class="rounded-circle" width="32" height="32" alt="Avatar" style="object-fit: cover;">
                                <input type="text" name="content" class="form-control form-control-sm text-white rounded-pill flex-grow-1" style="background: rgba(255, 255, 255, 0.05); border: 1px solid var(--clubit-border);" placeholder="Viết bình luận..." required>
                                <button type="submit" class="btn btn-sm btn-primary rounded-circle" style="width: 32px; height: 32px; padding: 0;"><i class="bi bi-send"></i></button>
                            </form>
                        <?php else: ?>
                            <div class="text-center text-muted small"><a href="<?php echo e(BASE_URL); ?>/login.php" class="text-primary text-decoration-none">Đăng nhập</a> để bình luận.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <!-- Loading spinner for infinite scroll -->
        <div id="posts-loader" class="text-center py-4 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Đang tải...</span>
            </div>
        </div>
    </div>

    <!-- Right Sidebar Column -->
    <div class="col-lg-4">
        <!-- Stats -->
        <div class="clubit-card p-3 mb-4 animate-on-scroll">
            <h6 class="fw-bold mb-3 border-bottom border-opacity-10 border-white pb-2">Thống kê cộng đồng</h6>
            <div class="row g-2 text-center">
                <div class="col-6">
                    <div class="stat-tile p-2">
                        <div class="fs-4 fw-bold text-info"><?php echo $stats['members']; ?></div>
                        <div class="small text-secondary">Thành viên</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-tile p-2">
                        <div class="fs-4 fw-bold text-success"><?php echo $stats['posts']; ?></div>
                        <div class="small text-secondary">Bài viết</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Events -->
        <div class="clubit-card p-3 mb-4 animate-on-scroll">
            <h6 class="fw-bold mb-3 border-bottom border-opacity-10 border-white pb-2">Sự kiện sắp tới</h6>
            <div class="d-grid gap-3">
                <?php foreach ($upcomingEvents as $event): ?>
                    <div class="d-flex gap-3 align-items-start">
                        <div class="bg-primary bg-opacity-10 rounded-3 text-center p-2" style="min-width: 55px;">
                            <div class="fs-5 fw-bold text-primary" style="line-height: 1;"><?php echo date('d', strtotime($event['start_date'])); ?></div>
                            <div class="small text-primary fw-semibold"><?php echo date('M', strtotime($event['start_date'])); ?></div>
                        </div>
                        <div>
                            <a href="<?php echo e(BASE_URL); ?>/event.php?id=<?php echo (int) $event['id']; ?>" class="fw-bold text-white text-decoration-none d-block mb-1" style="font-size: 0.95rem;"><?php echo e($event['event_name']); ?></a>
                            <div class="text-secondary small"><i class="bi bi-geo-alt"></i> <?php echo e($event['location']); ?></div>
                            <div class="text-secondary small"><i class="bi bi-people"></i> <?php echo e((string) $event['registered_count']); ?>/<?php echo e((string) $event['max_member']); ?> tham gia</div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($upcomingEvents)): ?>
                    <div class="text-secondary small text-center py-2">Chưa có sự kiện nào sắp tới.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Latest Documents -->
        <div class="clubit-card p-3 animate-on-scroll">
            <h6 class="fw-bold mb-3 border-bottom border-opacity-10 border-white pb-2">Tài liệu tham khảo</h6>
            <div class="d-grid gap-3">
                <?php foreach ($latestDocuments as $document): ?>
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-success bg-opacity-10 p-2 rounded text-success">
                            <i class="bi <?php echo $document['file_type'] === 'pdf' ? 'bi-file-pdf' : ($document['file_type'] === 'zip' ? 'bi-file-zip' : 'bi-file-earmark-text'); ?> fs-5"></i>
                        </div>
                        <div class="overflow-hidden">
                            <a href="<?php echo e(BASE_URL); ?>/documents.php#doc-<?php echo (int) $document['id']; ?>" class="fw-semibold text-white text-decoration-none d-block text-truncate" style="font-size: 0.9rem;"><?php echo e($document['title']); ?></a>
                            <div class="text-secondary small text-truncate"><?php echo e($document['description']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($latestDocuments)): ?>
                    <div class="text-secondary small text-center py-2">Chưa có tài liệu nào.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
