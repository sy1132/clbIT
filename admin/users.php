<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$pageLayout = 'admin';
$pageTitle = 'Quản lý thành viên';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'toggle' && $id > 0) {
        $toggleStmt = db()->prepare("UPDATE users SET status = CASE WHEN status = 'active' THEN 'locked' ELSE 'active' END WHERE id = :id");
        $toggleStmt->execute(['id' => $id]);
        flash_set('success', 'Đã thay đổi trạng thái tài khoản.');
        redirect('/admin/users.php');
    }

    if ($action === 'delete' && $id > 0) {
        $deleteStmt = db()->prepare('DELETE FROM users WHERE id = :id');
        $deleteStmt->execute(['id' => $id]);
        flash_set('success', 'Đã xóa tài khoản.');
        redirect('/admin/users.php');
    }

    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'member';
    $status = $_POST['status'] ?? 'active';
    $bio = trim($_POST['bio'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($fullname === '' || $email === '') {
        flash_set('error', 'Họ tên và email là bắt buộc.');
        redirect('/admin/users.php');
    }

    if (!in_array($role, ['admin', 'member'], true) || !in_array($status, ['active', 'locked'], true)) {
        flash_set('error', 'Dữ liệu vai trò/trạng thái không hợp lệ.');
        redirect('/admin/users.php');
    }

    $avatar = null;
    try {
        $avatar = upload_file('avatar', ['jpg', 'jpeg', 'png', 'webp']);
    } catch (Throwable $throwable) {
        flash_set('error', $throwable->getMessage());
        redirect('/admin/users.php');
    }

    if ($id > 0) {
        $sql = 'UPDATE users SET fullname = :fullname, email = :email, role = :role, status = :status, bio = :bio, skills = :skills';
        $params = [
            'fullname' => $fullname,
            'email' => $email,
            'role' => $role,
            'status' => $status,
            'bio' => $bio,
            'skills' => $skills,
            'id' => $id,
        ];

        if ($avatar) {
            $sql .= ', avatar = :avatar';
            $params['avatar'] = $avatar;
        }

        if ($password !== '') {
            if (strlen($password) < 6) {
                flash_set('error', 'Mật khẩu phải từ 6 ký tự trở lên.');
                redirect('/admin/users.php');
            }
            $sql .= ', password = :password';
            $params['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= ' WHERE id = :id';
        $updateStmt = db()->prepare($sql);
        $updateStmt->execute($params);
        flash_set('success', 'Đã cập nhật thành viên.');
    } else {
        if ($password === '') {
            flash_set('error', 'Mật khẩu là bắt buộc khi thêm mới.');
            redirect('/admin/users.php');
        }
        $insertStmt = db()->prepare('INSERT INTO users (fullname, email, password, avatar, bio, skills, role, status) VALUES (:fullname, :email, :password, :avatar, :bio, :skills, :role, :status)');
        $insertStmt->execute([
            'fullname' => $fullname,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'avatar' => $avatar,
            'bio' => $bio,
            'skills' => $skills,
            'role' => $role,
            'status' => $status,
        ]);
        flash_set('success', 'Đã thêm thành viên.');
    }

    redirect('/admin/users.php');
}

$editId = (int) ($_GET['edit'] ?? 0);
$editUser = null;
if ($editId > 0) {
    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $editId]);
    $editUser = $stmt->fetch();
}

$users = db()->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="clubit-card p-4">
            <h3 class="section-title mb-3"><?php echo $editUser ? 'Sửa thành viên' : 'Thêm thành viên'; ?></h3>
            <form method="post" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="id" value="<?php echo (int) ($editUser['id'] ?? 0); ?>">
                <div class="col-12">
                    <label class="form-label">Họ tên</label>
                    <input type="text" name="fullname" class="form-control" value="<?php echo e((string) ($editUser['fullname'] ?? '')); ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo e((string) ($editUser['email'] ?? '')); ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Mật khẩu <?php echo $editUser ? '(để trống nếu không đổi)' : ''; ?></label>
                    <input type="password" name="password" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Ảnh đại diện</label>
                    <input type="file" name="avatar" class="form-control" accept="image/*">
                </div>
                <div class="col-6">
                    <label class="form-label">Vai trò</label>
                    <select name="role" class="form-select">
                        <option value="member" <?php echo ($editUser['role'] ?? 'member') === 'member' ? 'selected' : ''; ?>>Member</option>
                        <option value="admin" <?php echo ($editUser['role'] ?? 'member') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo ($editUser['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="locked" <?php echo ($editUser['status'] ?? 'active') === 'locked' ? 'selected' : ''; ?>>Locked</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Giới thiệu</label>
                    <textarea name="bio" class="form-control" rows="3"><?php echo e((string) ($editUser['bio'] ?? '')); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Kỹ năng</label>
                    <input type="text" name="skills" class="form-control" value="<?php echo e((string) ($editUser['skills'] ?? '')); ?>">
                </div>
                <div class="col-12 d-grid">
                    <button class="btn btn-primary"><?php echo $editUser ? 'Cập nhật' : 'Thêm mới'; ?></button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="clubit-card p-4">
            <h3 class="section-title mb-3">Danh sách thành viên</h3>
            <div class="table-responsive">
                <table class="table table-clubit align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-white">Họ tên</th>
                            <th class="text-white">Email</th>
                            <th class="text-white">Vai trò</th>
                            <th class="text-white">Trạng thái</th>
                            <th class="text-white">Ngày tạo</th>
                            <th class="text-white">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="text-white"><?php echo e($user['fullname']); ?></td>
                                <td class="text-white"><?php echo e($user['email']); ?></td>
                                <td><span class="badge text-bg-primary"><?php echo e($user['role']); ?></span></td>
                                <td><span class="badge <?php echo e(badge_class_for_status($user['status'])); ?>"><?php echo e($user['status']); ?></span></td>
                                <td class="text-white"><?php echo e(format_datetime($user['created_at'])); ?></td>
                                <td class="d-flex gap-2 flex-wrap">
                                    <a class="btn btn-outline-primary btn-sm" href="?edit=<?php echo (int) $user['id']; ?>">Sửa</a>
                                    <form method="post">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
                                        <button class="btn btn-outline-warning btn-sm">Khóa/Mở</button>
                                    </form>
                                    <form method="post" onsubmit="return confirm('Xóa tài khoản này?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
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
