<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$pageLayout = 'admin';
$pageTitle = 'Quản lý tài liệu';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        $stmt = db()->prepare('SELECT file_path FROM documents WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $document = $stmt->fetch();
        if ($document) {
            delete_uploaded_file((string) $document['file_path']);
            $deleteStmt = db()->prepare('DELETE FROM documents WHERE id = :id');
            $deleteStmt->execute(['id' => $id]);
        }
        flash_set('success', 'Đã xóa tài liệu.');
        redirect('/admin/documents.php');
    }

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $fileType = $_POST['file_type'] ?? 'pdf';

    if ($title === '') {
        flash_set('error', 'Tiêu đề tài liệu không được để trống.');
        redirect('/admin/documents.php');
    }

    $filePath = upload_file('file_upload', ['pdf', 'zip', 'doc', 'docx']);
    if ($filePath === null && $id === 0) {
        flash_set('error', 'Vui lòng tải lên file tài liệu.');
        redirect('/admin/documents.php');
    }

    if ($filePath !== null) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $fileType = match ($ext) {
            'pdf' => 'pdf',
            'zip' => 'zip',
            'doc', 'docx' => 'doc',
            default => 'other',
        };
    }

    if ($id > 0) {
        $stmt = db()->prepare('SELECT file_path FROM documents WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $current = $stmt->fetch();
        $filePath = $filePath ?? ($current['file_path'] ?? null);

        $updateSql = 'UPDATE documents SET title = :title, description = :description, file_type = :file_type';
        $params = [
            'title' => $title,
            'description' => $description,
            'file_type' => $fileType,
            'id' => $id,
        ];
        if ($filePath !== null) {
            $updateSql .= ', file_path = :file_path';
            $params['file_path'] = $filePath;
        }
        $updateSql .= ' WHERE id = :id';
        $updateStmt = db()->prepare($updateSql);
        $updateStmt->execute($params);
        flash_set('success', 'Đã cập nhật tài liệu.');
    } else {
        $insertStmt = db()->prepare('INSERT INTO documents (title, description, file_path, file_type, uploaded_by) VALUES (:title, :description, :file_path, :file_type, :uploaded_by)');
        $insertStmt->execute([
            'title' => $title,
            'description' => $description,
            'file_path' => $filePath,
            'file_type' => $fileType,
            'uploaded_by' => current_user_id(),
        ]);
        flash_set('success', 'Đã thêm tài liệu.');
    }

    redirect('/admin/documents.php');
}

$editId = (int) ($_GET['edit'] ?? 0);
$editDocument = null;
if ($editId > 0) {
    $stmt = db()->prepare('SELECT * FROM documents WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $editId]);
    $editDocument = $stmt->fetch();
}

$documents = db()->query("SELECT d.*, u.fullname AS uploader_name FROM documents d INNER JOIN users u ON u.id = d.uploaded_by ORDER BY d.created_at DESC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="clubit-card p-4">
            <h3 class="section-title mb-3"><?php echo $editDocument ? 'Sửa tài liệu' : 'Thêm tài liệu'; ?></h3>
            <form method="post" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="id" value="<?php echo (int) ($editDocument['id'] ?? 0); ?>">
                <div class="col-12">
                    <label class="form-label">Tiêu đề</label>
                    <input type="text" name="title" class="form-control" value="<?php echo e((string) ($editDocument['title'] ?? '')); ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo e((string) ($editDocument['description'] ?? '')); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">File tài liệu</label>
                    <input type="file" name="file_upload" class="form-control" accept=".pdf,.zip,.doc,.docx">
                </div>
                <div class="col-12">
                    <label class="form-label">Loại file</label>
                    <select name="file_type" class="form-select">
                        <option value="pdf" <?php echo ($editDocument['file_type'] ?? 'pdf') === 'pdf' ? 'selected' : ''; ?>>PDF</option>
                        <option value="zip" <?php echo ($editDocument['file_type'] ?? 'pdf') === 'zip' ? 'selected' : ''; ?>>ZIP</option>
                        <option value="doc" <?php echo ($editDocument['file_type'] ?? 'pdf') === 'doc' ? 'selected' : ''; ?>>DOC</option>
                        <option value="other" <?php echo ($editDocument['file_type'] ?? 'pdf') === 'other' ? 'selected' : ''; ?>>Khác</option>
                    </select>
                </div>
                <div class="col-12 d-grid">
                    <button class="btn btn-primary"><?php echo $editDocument ? 'Cập nhật' : 'Thêm mới'; ?></button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="clubit-card p-4">
            <h3 class="section-title mb-3">Danh sách tài liệu</h3>
            <div class="table-responsive">
                <table class="table table-clubit align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tiêu đề</th>
                            <th>Loại</th>
                            <th>Người tải lên</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $document): ?>
                            <tr>
                                <td><?php echo e(short_text($document['title'], 35)); ?></td>
                                <td><span class="badge text-bg-info"><?php echo e($document['file_type']); ?></span></td>
                                <td><?php echo e($document['uploader_name']); ?></td>
                                <td><?php echo e(format_datetime($document['created_at'])); ?></td>
                                <td class="d-flex gap-2 flex-wrap">
                                    <a class="btn btn-outline-primary btn-sm" href="?edit=<?php echo (int) $document['id']; ?>">Sửa</a>
                                    <a class="btn btn-outline-success btn-sm" href="<?php echo e(UPLOAD_URL . '/' . $document['file_path']); ?>" download>Tải xuống</a>
                                    <form method="post" onsubmit="return confirm('Xóa tài liệu này?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int) $document['id']; ?>">
                                        <button class="btn btn-outline-danger btn-sm">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
