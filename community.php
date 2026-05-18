<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageLayout = 'public';
$pageTitle = 'Cộng đồng';

function ensure_community_messages_table(): void
{
    $pdo = db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS community_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        content VARCHAR(500) NOT NULL,
        status ENUM('visible', 'hidden') NOT NULL DEFAULT 'visible',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_community_messages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_community_messages_status (status),
        INDEX idx_community_messages_created_at (created_at)
    ) ENGINE=InnoDB");
}

ensure_community_messages_table();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();

    $action = $_POST['action'] ?? 'send';
    $content = trim($_POST['content'] ?? '');

    if ($action === 'delete') {
        $messageId = (int) ($_POST['id'] ?? 0);
        if ($messageId > 0) {
            $deleteSql = 'DELETE FROM community_messages WHERE id = :id AND (user_id = :user_id OR :is_admin = 1)';
            $deleteStmt = db()->prepare($deleteSql);
            $deleteStmt->execute([
                'id' => $messageId,
                'user_id' => current_user_id(),
                'is_admin' => current_user_role() === 'admin' ? 1 : 0,
            ]);
            flash_set('success', 'Đã xóa tin nhắn.');
        }

        redirect('/community.php');
    }

    if ($content === '') {
        flash_set('error', 'Vui lòng nhập nội dung tin nhắn.');
        redirect('/community.php');
    }

    if (mb_strlen($content) > 500) {
        flash_set('error', 'Tin nhắn không được dài quá 500 ký tự.');
        redirect('/community.php');
    }

    $insertStmt = db()->prepare('INSERT INTO community_messages (user_id, content, status) VALUES (:user_id, :content, :status)');
    $insertStmt->execute([
        'user_id' => current_user_id(),
        'content' => $content,
        'status' => 'visible',
    ]);

    flash_set('success', 'Đã gửi tin nhắn.');
    redirect('/community.php');
}

$messagesStmt = db()->query("SELECT m.*, u.fullname, u.avatar, u.role
    FROM community_messages m
    INNER JOIN users u ON u.id = m.user_id
    WHERE m.status = 'visible'
    ORDER BY m.created_at ASC");
$messages = $messagesStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="clubit-card p-0 overflow-hidden community-shell">
    <div class="community-header d-flex justify-content-between align-items-center flex-wrap gap-2 px-4 py-3">
        <div>
            <h1 class="section-title mb-1">Cộng đồng CLB IT</h1>
            <div class="text-secondary small">Chat chung chỉ gửi tin nhắn văn bản, không có GIF hay file đính kèm.</div>
        </div>
        <span class="soft-badge"><?php echo count($messages); ?> tin nhắn</span>
    </div>

    <div class="community-chat px-4 py-4 <?php echo empty($messages) ? 'is-empty' : ''; ?>" data-community-chat id="community-chat-container" data-last-id="<?php echo !empty($messages) ? end($messages)['id'] : 0; ?>">
        <?php if (empty($messages)): ?>
            <div class="community-empty-state text-center text-secondary">Chưa có tin nhắn nào. Hãy mở lời trước!</div>
        <?php else: ?>
            <?php foreach ($messages as $message): ?>
                <?php $isMine = (int) $message['user_id'] === (int) (current_user_id() ?? 0); ?>
                        <div class="community-message <?php echo $isMine ? 'is-mine ms-auto' : ''; ?>">
                    <div class="d-flex align-items-start gap-3">
                        <a href="<?php echo e(BASE_URL); ?>/profile.php?id=<?php echo (int) $message['user_id']; ?>" class="text-decoration-none">
                            <img src="<?php echo e($message['avatar'] ? UPLOAD_URL . '/' . $message['avatar'] : BASE_URL . '/assets/images/default-avatar.svg'); ?>" alt="<?php echo e($message['fullname']); ?>" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">
                        </a>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-1">
                                <div class="fw-bold text-white">
                                    <a href="<?php echo e(BASE_URL); ?>/profile.php?id=<?php echo (int) $message['user_id']; ?>" class="text-white text-decoration-none">
                                        <?php echo e($message['fullname']); ?>
                                    </a>
                                    <?php echo $message['role'] === 'admin' ? ' <span class="badge text-bg-info ms-1">Admin</span>' : ''; ?>
                                </div>
                                <div class="small text-white-50"><?php echo e(format_datetime($message['created_at'])); ?></div>
                            </div>
                            <div class="text-white"><?php echo nl2br(e($message['content'])); ?></div>
                        </div>
                        <?php if (!$isMine): ?>
                            <img src="<?php echo e($message['avatar'] ? UPLOAD_URL . '/' . $message['avatar'] : BASE_URL . '/assets/images/default-avatar.svg'); ?>" alt="<?php echo e($message['fullname']); ?>" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;visibility:hidden;">
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="community-composer border-top border-white border-opacity-10 px-4 py-3">
        <?php if (is_logged_in()): ?>
            <form id="form-chat" method="post" class="d-flex gap-2 align-items-end">
                <textarea name="content" class="form-control community-input" rows="2" maxlength="500" placeholder="Nhập tin nhắn..." required></textarea>
                <button class="btn btn-primary px-4">Gửi</button>
            </form>
            <div class="small text-white-50 mt-2">Chỉ nhập văn bản, tối đa 500 ký tự.</div>
        <?php else: ?>
            <div class="alert alert-warning mb-0">
                Bạn cần <a href="<?php echo e(BASE_URL); ?>/login.php" class="alert-link">đăng nhập</a> để chat chung.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>