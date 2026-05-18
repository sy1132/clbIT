CREATE DATABASE IF NOT EXISTS clubit_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clubit_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS community_messages;
DROP TABLE IF EXISTS event_registrations;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) NULL,
    bio TEXT NULL,
    skills VARCHAR(255) NULL,
    role ENUM('admin', 'member') NOT NULL DEFAULT 'member',
    status ENUM('active', 'locked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role_status (role, status)
) ENGINE=InnoDB;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(120) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    excerpt VARCHAR(300) NOT NULL,
    content LONGTEXT NOT NULL,
    image VARCHAR(255) NULL,
    images TEXT NULL,
    files TEXT NULL,
    category_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('draft', 'published', 'pending') NOT NULL DEFAULT 'pending',
    privacy ENUM('public', 'private') NOT NULL DEFAULT 'public',
    published_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_posts_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_posts_category (category_id),
    INDEX idx_posts_user (user_id),
    INDEX idx_posts_status_date (status, published_at)
) ENGINE=InnoDB;

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NULL,
    max_member INT NOT NULL DEFAULT 0,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'published',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_events_status_date (status, start_date)
) ENGINE=InnoDB;

CREATE TABLE event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NULL,
    note VARCHAR(255) NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_event_regs_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_regs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT uq_event_user UNIQUE (event_id, user_id),
    INDEX idx_event_regs_event (event_id),
    INDEX idx_event_regs_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type ENUM('pdf', 'zip', 'doc', 'other') NOT NULL DEFAULT 'pdf',
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_documents_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_documents_type (file_type),
    INDEX idx_documents_user (uploaded_by)
) ENGINE=InnoDB;

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'spam') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comments_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_comments_post (post_id),
    INDEX idx_comments_user (user_id),
    INDEX idx_comments_status (status)
) ENGINE=InnoDB;

CREATE TABLE community_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content VARCHAR(500) NOT NULL,
    status ENUM('visible', 'hidden') NOT NULL DEFAULT 'visible',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_community_messages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_community_messages_status (status),
    INDEX idx_community_messages_created_at (created_at)
) ENGINE=InnoDB;

INSERT INTO users (id, fullname, email, password, avatar, bio, skills, role, status) VALUES
(1, 'Quản trị viên CLB', 'admin@clubit.local', '$2y$10$7JQ6mwy6WfX6Q/zc8w8vUOU4o93zA2zy4M4jW5q9uzS8aM2hVe9Fe', NULL, 'Điều phối hoạt động của câu lạc bộ.', 'PHP, MySQL, Linux', 'admin', 'active'),
(2, 'Nguyễn Văn A', 'member@clubit.local', '$2y$10$7JQ6mwy6WfX6Q/zc8w8vUOU4o93zA2zy4M4jW5q9uzS8aM2hVe9Fe', NULL, 'Thành viên yêu thích web và AI.', 'HTML, CSS, JS, PHP', 'member', 'active');

INSERT INTO categories (id, name, slug) VALUES
(1, 'Web', 'web'),
(2, 'AI', 'ai'),
(3, 'Cyber Security', 'cyber-security'),
(4, 'Mobile', 'mobile'),
(5, 'Database', 'database');

INSERT INTO posts (id, title, slug, excerpt, content, image, category_id, user_id, status, published_at) VALUES
(1, 'Bắt đầu với PHP và MySQL', 'bat-dau-voi-php-va-mysql', 'Lộ trình khởi động nhanh cho sinh viên mới học web.', '<p>PHP và MySQL là nền tảng phù hợp để xây dựng website quản lý CLB. Bài viết này giới thiệu cấu trúc dự án, cách kết nối CSDL và tổ chức CRUD.</p><p>Bạn có thể mở rộng thành hệ thống đăng ký sự kiện, chia sẻ tài liệu và quản lý thành viên.</p>', NULL, 1, 1, 'published', NOW()),
(2, '5 kỹ năng nền tảng cho sinh viên IT', '5-ky-nang-nen-tang-cho-sinh-vien-it', 'Tổng hợp các kỹ năng nên luyện tập song song với lập trình.', '<p>Học HTML, CSS, JavaScript, SQL và Git giúp bạn đi nhanh hơn ở giai đoạn đầu.</p><p>CLB IT là nơi phù hợp để luyện tập qua dự án thật.</p>', NULL, 1, 2, 'published', NOW());

INSERT INTO events (id, event_name, slug, description, location, start_date, end_date, max_member, status, created_by) VALUES
(1, 'Workshop Git và GitHub', 'workshop-git-va-github', 'Buổi thực hành quản lý mã nguồn cho thành viên mới.', 'Phòng máy A1', DATE_ADD(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL 3 DAY), 40, 'published', 1),
(2, 'Hackathon mini CLB IT', 'hackathon-mini-clb-it', 'Thi giải nhanh theo nhóm với chủ đề website sinh viên.', 'Hội trường tầng 3', DATE_ADD(NOW(), INTERVAL 10 DAY), DATE_ADD(NOW(), INTERVAL 11 DAY), 60, 'published', 1);

INSERT INTO documents (id, title, description, file_path, file_type, uploaded_by) VALUES
(1, 'Slide PHP Cơ bản', 'Bộ slide nhập môn cho buổi học đầu tiên.', 'sample-php-basics.pdf', 'pdf', 1),
(2, 'Bộ tài nguyên Web Project', 'Mẫu giao diện và cấu trúc thư mục dùng cho đồ án.', 'web-project-kit.zip', 'zip', 1);

INSERT INTO comments (id, post_id, user_id, content, status) VALUES
(1, 1, 2, 'Bài viết ngắn gọn và dễ áp dụng cho đồ án.', 'approved'),
(2, 2, 2, 'Mong CLB có thêm tài liệu về Git nâng cao.', 'pending');

INSERT INTO community_messages (id, user_id, content, status) VALUES
(1, 1, 'Chào mừng mọi người vào phòng chat cộng đồng của CLB IT!', 'visible'),
(2, 2, 'Nếu ai cần hỗ trợ PHP/MySQL thì cứ nhắn ở đây nhé.', 'visible');
