<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$pageLayout = 'public';
$pageTitle = 'Tạo bài viết mới';

$categories = db()->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $content = $_POST['content'] ?? '';
    
    if ($title === '' || $categoryId <= 0 || $content === '') {
        flash_set('error', 'Vui lòng điền tiêu đề, danh mục và nội dung chi tiết bài viết.');
        redirect('/create-post.php');
    }

    // Check category exists
    $catStmt = db()->prepare('SELECT id FROM categories WHERE id = :id LIMIT 1');
    $catStmt->execute(['id' => $categoryId]);
    if (!$catStmt->fetch()) {
        flash_set('error', 'Danh mục không tồn tại.');
        redirect('/create-post.php');
    }

    // Auto-extract excerpt (first 150 characters of plain text)
    $plainText = strip_tags($content);
    $plainText = preg_replace('/\s+/', ' ', $plainText);
    $excerpt = mb_substr($plainText, 0, 150);
    if (mb_strlen($plainText) > 150) {
        $excerpt .= '...';
    }

    $images = [];
    $image = null;

    // Handle multiple image uploads
    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $imgCount = count($_FILES['images']['name']);
        for ($i = 0; $i < $imgCount; $i++) {
            if (($_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) {
                flash_set('error', 'Tải lên một trong số các ảnh bị thất bại.');
                redirect('/create-post.php');
            }
            
            $originalName = (string) $_FILES['images']['name'][$i];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                flash_set('error', 'Định dạng ảnh không hợp lệ. Chỉ hỗ trợ JPG, PNG, WebP và GIF.');
                redirect('/create-post.php');
            }

            if (!is_dir(UPLOAD_PATH)) {
                mkdir(UPLOAD_PATH, 0777, true);
            }

            $safeName = bin2hex(random_bytes(8)) . '_' . time() . '.' . $extension;
            $targetPath = UPLOAD_PATH . '/' . $safeName;

            if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $targetPath)) {
                $images[] = $safeName;
            }
        }
    }

    // Set first image as the primary cover image for backwards-compatibility
    if (!empty($images)) {
        $image = $images[0];
    }

    $files = [];

    // Handle multiple document uploads
    if (isset($_FILES['files']) && is_array($_FILES['files']['name'])) {
        $fileCount = count($_FILES['files']['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            if (($_FILES['files']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) {
                flash_set('error', 'Tải lên một số file đính kèm bị thất bại.');
                redirect('/create-post.php');
            }

            $originalName = (string) $_FILES['files']['name'][$i];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowedExtensions = ['doc', 'docx', 'pdf', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip', 'rar'];
            
            if (!in_array($extension, $allowedExtensions, true)) {
                flash_set('error', 'Chỉ được tải lên các tài liệu định dạng: Word, PDF, PowerPoint, Excel, TXT, ZIP, RAR.');
                redirect('/create-post.php');
            }

            if (!is_dir(UPLOAD_PATH)) {
                mkdir(UPLOAD_PATH, 0777, true);
            }

            $safeName = bin2hex(random_bytes(8)) . '_' . time() . '.' . $extension;
            $targetPath = UPLOAD_PATH . '/' . $safeName;

            if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $targetPath)) {
                $bytes = $_FILES['files']['size'][$i];
                if ($bytes >= 1048576) {
                    $sizeStr = number_format($bytes / 1048576, 1) . ' MB';
                } else {
                    $sizeStr = number_format($bytes / 1024, 0) . ' KB';
                }
                
                $files[] = [
                    'name' => $originalName,
                    'path' => $safeName,
                    'type' => $extension,
                    'size' => $sizeStr
                ];
            }
        }
    }

    $privacy = $_POST['privacy'] ?? 'public';
    if (!in_array($privacy, ['public', 'private'], true)) {
        $privacy = 'public';
    }

    $slug = slugify($title) . '-' . time();
    $insertStmt = db()->prepare('INSERT INTO posts (title, slug, category_id, excerpt, content, image, images, files, user_id, status, privacy, created_at, published_at) VALUES (:title, :slug, :category_id, :excerpt, :content, :image, :images, :files, :user_id, :status, :privacy, NOW(), NULL)');
    $insertStmt->execute([
        'title' => $title,
        'slug' => $slug,
        'category_id' => $categoryId,
        'excerpt' => $excerpt,
        'content' => $content,
        'image' => $image,
        'images' => !empty($images) ? json_encode($images) : null,
        'files' => !empty($files) ? json_encode($files) : null,
        'user_id' => current_user_id(),
        'status' => 'pending',
        'privacy' => $privacy,
    ]);

    flash_set('success', 'Bài viết của bạn đã được nộp thành công! Quản trị viên sẽ kiểm duyệt và đăng bài trong thời gian sớm nhất.');
    redirect('/my-posts.php');
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row g-4 justify-content-center animate-on-scroll">
    <div class="col-lg-8">
        <!-- Premium Facebook-style Post Composer Card -->
        <div class="clubit-card p-4">
            <h4 class="fw-bold mb-4 d-flex align-items-center gap-2 border-bottom border-white border-opacity-10 pb-3">
                <i class="bi bi-pencil-square text-primary"></i> Tạo bài viết mới
            </h4>
            
            <form method="post" enctype="multipart/form-data" id="composer-form" class="d-flex flex-column gap-3">
                <!-- User Meta Information exactly like Facebook -->
                <div class="d-flex align-items-center gap-3">
                    <img src="<?php echo e(current_user_avatar()); ?>" class="rounded-circle border border-2 border-primary border-opacity-25" width="52" height="52" alt="Avatar" style="object-fit: cover;">
                    <div>
                        <div class="fw-bold text-white fs-6"><?php echo e(current_user_name()); ?></div>
                        <!-- Privacy Dropdown -->
                        <div class="dropdown mt-1">
                            <button type="button" class="btn btn-sm btn-dark bg-opacity-25 border border-white border-opacity-10 text-white d-inline-flex align-items-center gap-1 py-1 px-2.5 rounded-pill fs-7 hover-bg" data-bs-toggle="dropdown" aria-expanded="false" id="privacy-dropdown-btn" style="background: rgba(255, 255, 255, 0.08);">
                                <i class="bi bi-globe-americas text-primary fs-7" id="privacy-icon"></i> 
                                <span id="privacy-text" class="fw-semibold text-white-50 ms-1 me-1">Công khai</span>
                                <i class="bi bi-caret-down-fill text-muted" style="font-size: 0.65rem;"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-dark border border-white border-opacity-10 shadow-lg py-1 fs-7" style="background: #1e1e2e;">
                                <li>
                                    <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" onclick="setPrivacy('public')">
                                        <i class="bi bi-globe-americas text-primary fs-6"></i>
                                        <div>
                                            <div class="fw-bold text-white">Công khai</div>
                                            <div class="small text-white-50" style="font-size: 0.75rem;">Ai cũng xem được bài viết này</div>
                                        </div>
                                    </button>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" onclick="setPrivacy('private')">
                                        <i class="bi bi-lock-fill text-warning fs-6"></i>
                                        <div>
                                            <div class="fw-bold text-white">Riêng tư</div>
                                            <div class="small text-white-50" style="font-size: 0.75rem;">Chỉ mình bạn xem được bài viết này</div>
                                        </div>
                                    </button>
                                </li>
                            </ul>
                            <input type="hidden" name="privacy" id="privacy-input" value="public">
                        </div>
                    </div>
                </div>

                <!-- Post Title -->
                <div class="mt-2">
                    <input type="text" name="title" class="form-control bg-transparent border-0 border-bottom border-white border-opacity-10 text-white rounded-0 px-0 py-2 fs-5 fw-semibold focus-none" placeholder="Tiêu đề bài viết của bạn..." required style="box-shadow: none;">
                </div>

                <!-- Category Selector -->
                <div>
                    <select name="category_id" class="form-select bg-dark border-secondary border-opacity-25 text-white" required>
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int) $cat['id']; ?>"><?php echo e($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Main Content Textarea -->
                <div>
                    <textarea name="content" id="composer-content" class="form-control bg-transparent border-0 text-white px-0 focus-none" rows="6" placeholder="<?php echo e(current_user_name()); ?> ơi, bạn muốn chia sẻ điều gì hôm nay?" required style="box-shadow: none; font-size: 1.1rem; resize: none;"></textarea>
                </div>

                <!-- Attachment & Preview Panel (Hidden by default, shown via JS when files selected) -->
                <div id="attachment-preview-panel" class="d-none border border-white border-opacity-10 rounded-4 p-3 mb-2" style="background: rgba(0, 0, 0, 0.2);">
                    <!-- Images Grid Preview -->
                    <div id="images-preview-grid" class="row g-2 mb-2 d-none"></div>
                    
                    <!-- Documents List Preview -->
                    <div id="files-preview-list" class="d-flex flex-column gap-2 d-none"></div>
                </div>

                <!-- Hidden Input Fields for Multiple File Select -->
                <input type="file" name="images[]" id="composer-images-input" multiple accept="image/*" class="d-none">
                <input type="file" name="files[]" id="composer-files-input" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar" class="d-none">

                <!-- Facebook-style 'Add to your post' Toolbar -->
                <div class="d-flex align-items-center justify-content-between p-3 rounded-4 border border-white border-opacity-15" style="background: rgba(255, 255, 255, 0.02);">
                    <span class="fw-semibold text-white-50 small">Thêm vào bài viết của bạn</span>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-dark btn-sm rounded-circle d-flex align-items-center justify-content-center text-success" id="btn-trigger-images" title="Thêm hình ảnh" style="width: 40px; height: 40px; background: rgba(255,255,255,0.05); border: none;">
                            <i class="bi bi-images fs-5"></i>
                        </button>
                        <button type="button" class="btn btn-dark btn-sm rounded-circle d-flex align-items-center justify-content-center text-primary" id="btn-trigger-files" title="Thêm tài liệu" style="width: 40px; height: 40px; background: rgba(255,255,255,0.05); border: none;">
                            <i class="bi bi-file-earmark-arrow-up fs-5"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="d-grid mt-2">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold rounded-3 py-2.5">📤 Nộp bài viết</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Information Widget -->
    <div class="col-lg-4">
        <div class="clubit-card p-4">
            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-info-circle text-primary"></i> Hướng dẫn viết bài
            </h5>
            <ul class="small mb-0 ps-3 d-flex flex-column gap-2 text-white-50">
                <li>Bài viết mới sẽ ở trạng thái <strong>"Chờ duyệt"</strong>. Admin sẽ duyệt nhanh bài đăng hợp lệ.</li>
                <li>Hệ thống <strong>tự động tạo tóm tắt</strong> từ những câu đầu tiên của bài viết.</li>
                <li>Bạn có thể đính kèm <strong>nhiều hình ảnh</strong> để tạo thành lưới ảnh Facebook đẹp mắt.</li>
                <li>Hỗ trợ chia sẻ tài liệu học tập: <strong>PDF, Word (DOCX), PowerPoint (PPTX), Excel, ZIP</strong>.</li>
            </ul>
        </div>
    </div>
</div>

<!-- Inline JavaScript for Composer dynamic attachment preview and deletion management -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const imagesInput = document.getElementById('composer-images-input');
    const filesInput = document.getElementById('composer-files-input');
    const btnTriggerImages = document.getElementById('btn-trigger-images');
    const btnTriggerFiles = document.getElementById('btn-trigger-files');
    const previewPanel = document.getElementById('attachment-preview-panel');
    const imagesGrid = document.getElementById('images-preview-grid');
    const filesList = document.getElementById('files-preview-list');

    // Track chosen files
    let chosenImages = [];
    let chosenFiles = [];

    // Trigger click on actual inputs
    btnTriggerImages.addEventListener('click', () => imagesInput.click());
    btnTriggerFiles.addEventListener('click', () => filesInput.click());

    // Update FileList property of standard Input via DataTransfer API
    const updateInputFileList = (input, fileArray) => {
        const dataTransfer = new DataTransfer();
        fileArray.forEach(file => dataTransfer.items.add(file));
        input.files = dataTransfer.files;
    };

    // Render previews
    const renderPreviews = () => {
        // Manage overall visibility of attachment box
        if (chosenImages.length === 0 && chosenFiles.length === 0) {
            previewPanel.classList.add('d-none');
            imagesGrid.classList.add('d-none');
            filesList.classList.add('d-none');
            return;
        }

        previewPanel.classList.remove('d-none');

        // 1. Render Images
        if (chosenImages.length > 0) {
            imagesGrid.classList.remove('d-none');
            imagesGrid.innerHTML = '';
            chosenImages.forEach((file, index) => {
                const col = document.createElement('div');
                col.className = 'col-3 position-relative';
                
                const objectUrl = URL.createObjectURL(file);
                col.innerHTML = `
                    <div class="ratio ratio-1x1 rounded-3 overflow-hidden border border-white border-opacity-10 bg-dark">
                        <img src="${objectUrl}" class="w-100 h-100" style="object-fit: cover;">
                    </div>
                    <button type="button" class="btn btn-danger btn-sm rounded-circle position-absolute top-0 end-0 m-1 d-flex align-items-center justify-content-center" 
                            style="width: 22px; height: 22px; padding: 0; font-size: 10px; border: none; z-index: 10;" 
                            data-index="${index}">
                        <i class="bi bi-x-lg"></i>
                    </button>
                `;

                // Add delete handler
                col.querySelector('button').addEventListener('click', (e) => {
                    e.stopPropagation();
                    chosenImages.splice(index, 1);
                    updateInputFileList(imagesInput, chosenImages);
                    renderPreviews();
                });

                imagesGrid.appendChild(col);
            });
        } else {
            imagesGrid.classList.add('d-none');
        }

        // 2. Render Files
        if (chosenFiles.length > 0) {
            filesList.classList.remove('d-none');
            filesList.innerHTML = '';
            chosenFiles.forEach((file, index) => {
                const ext = file.name.split('.').pop().toLowerCase();
                let iconClass = 'bi-file-earmark';
                let iconColor = 'text-secondary';

                if (ext === 'pdf') { iconClass = 'bi-file-earmark-pdf-fill'; iconColor = 'text-danger'; }
                else if (['doc', 'docx'].includes(ext)) { iconClass = 'bi-file-earmark-word-fill'; iconColor = 'text-primary'; }
                else if (['ppt', 'pptx'].includes(ext)) { iconClass = 'bi-file-earmark-slides-fill'; iconColor = 'text-warning'; }
                else if (['xls', 'xlsx'].includes(ext)) { iconClass = 'bi-file-earmark-excel-fill'; iconColor = 'text-success'; }
                else if (['zip', 'rar'].includes(ext)) { iconClass = 'bi-file-earmark-zip-fill'; iconColor = 'text-info'; }

                const sizeStr = file.size >= 1048576 
                    ? (file.size / 1048576).toFixed(1) + ' MB' 
                    : (file.size / 1024).toFixed(0) + ' KB';

                const card = document.createElement('div');
                card.className = 'd-flex align-items-center justify-content-between p-2 rounded-3 border border-white border-opacity-10';
                card.style.background = 'rgba(255,255,255,0.03)';
                card.innerHTML = `
                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                        <i class="bi ${iconClass} ${iconColor} fs-4"></i>
                        <div class="overflow-hidden">
                            <div class="text-white text-truncate fw-semibold small" style="max-width: 300px;">${file.name}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">${sizeStr}</div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-white-50 text-decoration-none px-2 py-1" data-index="${index}">
                        <i class="bi bi-trash3 text-danger"></i>
                    </button>
                `;

                card.querySelector('button').addEventListener('click', (e) => {
                    e.stopPropagation();
                    chosenFiles.splice(index, 1);
                    updateInputFileList(filesInput, chosenFiles);
                    renderPreviews();
                });

                filesList.appendChild(card);
            });
        } else {
            filesList.classList.add('d-none');
        }
    };

    // Listeners for file changes
    imagesInput.addEventListener('change', (e) => {
        const files = Array.from(e.target.files);
        // Append instead of replacing
        chosenImages = [...chosenImages, ...files];
        updateInputFileList(imagesInput, chosenImages);
        renderPreviews();
    });

    filesInput.addEventListener('change', (e) => {
        const files = Array.from(e.target.files);
        // Append instead of replacing
        chosenFiles = [...chosenFiles, ...files];
        updateInputFileList(filesInput, chosenFiles);
        renderPreviews();
    });
});

function setPrivacy(value) {
    const input = document.getElementById('privacy-input');
    const text = document.getElementById('privacy-text');
    const icon = document.getElementById('privacy-icon');
    
    input.value = value;
    if (value === 'public') {
        text.innerText = 'Công khai';
        icon.className = 'bi bi-globe-americas text-primary fs-7';
    } else {
        text.innerText = 'Riêng tư';
        icon.className = 'bi bi-lock-fill text-warning fs-7';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
