<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$pageLayout = 'public';
$pageTitle = 'Cài đặt tài khoản';

$user = current_user_row();

require_once __DIR__ . '/includes/header.php';
?>
<div class="row g-4 animate-on-scroll">
    <div class="col-lg-3">
        <!-- Sidebar Navigation for Settings -->
        <div class="clubit-card p-3 sidebar-glass">
            <div class="d-flex align-items-center gap-3 p-3 border-bottom border-white border-opacity-10 mb-3">
                <img src="<?php echo e($user['avatar'] ? UPLOAD_URL . '/' . $user['avatar'] : BASE_URL . '/assets/images/default-avatar.svg'); ?>" alt="Avatar" width="48" height="48" class="rounded-circle" style="object-fit: cover;">
                <div>
                    <h6 class="fw-bold mb-0"><?php echo e($user['fullname']); ?></h6>
                    <span class="small text-secondary"><?php echo e($user['role']); ?></span>
                </div>
            </div>
            <div class="d-grid gap-1">
                <a href="<?php echo e(BASE_URL); ?>/settings.php" class="sidebar-link active"><i class="bi bi-sliders me-2"></i> Cấu hình chung</a>
                <a href="<?php echo e(BASE_URL); ?>/profile.php" class="sidebar-link"><i class="bi bi-person me-2"></i> Hồ sơ</a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-9">
        <!-- General Settings Panel -->
        <div class="clubit-card p-4 mb-4">
            <h4 class="fw-bold mb-3"><i class="bi bi-sliders text-primary me-2"></i> Giao diện & Trải nghiệm</h4>
            <p class="text-secondary small mb-4">Tùy chỉnh giao diện và cách hiển thị của trang web để phù hợp với sở thích của bạn.</p>
            
            <div class="d-grid gap-4">
                <!-- Theme Option -->
                <div class="d-flex justify-content-between align-items-center p-3 rounded-4 bg-dark bg-opacity-25 border border-white border-opacity-10">
                    <div>
                        <h6 class="fw-bold mb-1">Giao diện chính</h6>
                        <p class="text-secondary small mb-0">Chuyển đổi giữa chế độ sáng và tối nhanh chóng.</p>
                    </div>
                    <div>
                        <button class="btn btn-outline-primary rounded-pill px-4" id="settings-theme-toggle">
                            <i class="bi bi-moon-stars me-2"></i> Chuyển chủ đề
                        </button>
                    </div>
                </div>
                
                <!-- Compact View Option -->
                <div class="d-flex justify-content-between align-items-center p-3 rounded-4 bg-dark bg-opacity-25 border border-white border-opacity-10">
                    <div>
                        <h6 class="fw-bold mb-1">Chế độ gọn nhẹ (Compact Mode)</h6>
                        <p class="text-secondary small mb-0">Thu nhỏ khoảng cách và kích thước để hiển thị nhiều thông tin hơn.</p>
                    </div>
                    <div class="form-check form-switch fs-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="compact-view-switch">
                    </div>
                </div>
                
                <!-- Autoplay Video Option -->
                <div class="d-flex justify-content-between align-items-center p-3 rounded-4 bg-dark bg-opacity-25 border border-white border-opacity-10">
                    <div>
                        <h6 class="fw-bold mb-1">Tự động phát đa phương tiện</h6>
                        <p class="text-secondary small mb-0">Tự động chạy video và ảnh động khi cuộn qua tin nhắn hoặc bài viết.</p>
                    </div>
                    <div class="form-check form-switch fs-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="autoplay-media-switch" checked>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Account Info Panel (View-Only) -->
        <div class="clubit-card p-4">
            <h4 class="fw-bold mb-3"><i class="bi bi-person-badge text-primary me-2"></i> Thông tin tài khoản</h4>
            <p class="text-secondary small mb-4">Các thông tin cơ bản được liên kết với tài khoản thành viên của bạn.</p>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold">Địa chỉ Email</label>
                    <input type="text" class="form-control" value="<?php echo e($user['email']); ?>" readonly disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold">Vai trò hệ thống</label>
                    <input type="text" class="form-control" value="<?php echo e(strtoupper($user['role'])); ?>" readonly disabled>
                </div>
                <div class="col-12 mt-4 text-end">
                    <a href="<?php echo e(BASE_URL); ?>/profile.php" class="btn btn-primary rounded-pill px-4"><i class="bi bi-pencil-square me-2"></i> Chỉnh sửa trong Hồ sơ</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Connect settings theme toggle button with main theme switcher behavior
    const settingsThemeToggle = document.getElementById('settings-theme-toggle');
    const mainThemeToggle = document.getElementById('theme-toggle');
    
    if (settingsThemeToggle && mainThemeToggle) {
        const updateSettingsThemeUI = () => {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            if (currentTheme === 'dark') {
                settingsThemeToggle.innerHTML = '<i class="bi bi-sun me-2"></i> Chế độ sáng';
                settingsThemeToggle.className = 'btn btn-primary rounded-pill px-4';
            } else {
                settingsThemeToggle.innerHTML = '<i class="bi bi-moon-stars me-2"></i> Chế độ tối';
                settingsThemeToggle.className = 'btn btn-outline-primary rounded-pill px-4';
            }
        };
        
        // Initial setup
        updateSettingsThemeUI();
        
        // Listen to click and trigger main switcher click
        settingsThemeToggle.addEventListener('click', (e) => {
            e.preventDefault();
            mainThemeToggle.click();
            updateSettingsThemeUI();
        });
        
        // Sync when main theme changes (in case clicked on header dropdown)
        mainThemeToggle.addEventListener('click', () => {
            setTimeout(updateSettingsThemeUI, 50);
        });
    }

    // Compact Mode Switch
    const compactSwitch = document.getElementById('compact-view-switch');
    if (compactSwitch) {
        // Set initial state
        const isCompact = localStorage.getItem('compact') === 'true';
        compactSwitch.checked = isCompact;
        
        // Listen to change
        compactSwitch.addEventListener('change', () => {
            if (compactSwitch.checked) {
                document.documentElement.setAttribute('data-compact', 'true');
                localStorage.setItem('compact', 'true');
            } else {
                document.documentElement.removeAttribute('data-compact');
                localStorage.setItem('compact', 'false');
            }
        });
    }

    // Autoplay Media Switch
    const autoplaySwitch = document.getElementById('autoplay-media-switch');
    if (autoplaySwitch) {
        // Set initial state (default to true if not set)
        const isAutoplay = localStorage.getItem('autoplay_media') !== 'false';
        autoplaySwitch.checked = isAutoplay;
        
        // Listen to change
        autoplaySwitch.addEventListener('change', () => {
            localStorage.setItem('autoplay_media', autoplaySwitch.checked ? 'true' : 'false');
        });
    }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
