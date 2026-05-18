# 🌟 HỆ THỐNG WEBSITE CÂU LẠC BỘ IT (CLB IT WEBSITE)

Chào mừng bạn đến với dự án **CLB IT** — Một nền tảng website cộng đồng, chia sẻ tri thức, quản lý sự kiện và tài liệu dành riêng cho các thành viên và quản trị viên câu lạc bộ công nghệ thông tin. Hệ thống được thiết kế với giao diện **Dark/Light Mode** hiện đại, hiệu ứng **Glassmorphism** cao cấp, và trải nghiệm người dùng cực kì mượt mà.

---

## 🛠️ Công Nghệ Sử Dụng

- **Backend:** PHP (PHP 7.4 - 8.x trở lên), viết theo cấu trúc tối ưu, bảo mật PDO chống SQL Injection.
- **Database:** MySQL.
- **Frontend:** Bootstrap v5.3.3 (UI framework), Bootstrap Icons, và Vanilla CSS tự tùy chỉnh (`assets/css/app.css`).
- **Javascript:** Vanilla JS thuần cho các hiệu ứng tương tác động, Ajax bình luận không tải lại trang, và Lưu trữ trạng thái giao diện qua `localStorage`.

---

## 💻 Các Tính Năng & Trang Hệ Thống (Phân Hệ Thành Viên)

### 1. 🏠 Trang Chủ (`index.php`)
- Banner chào mừng hoành tráng phong cách công nghệ.
- Thống kê nhanh hoạt động CLB (Số lượng thành viên, bài viết, sự kiện).
- Hiển thị danh sách sự kiện mới nhất và các bài viết nổi bật.

### 2. 📰 Bản Tin / Bài Viết (`posts.php` & `post.php`)
- Nơi tổng hợp các bài viết kỹ thuật, chia sẻ kinh nghiệm của các thành viên.
- **Phân loại & Tìm kiếm:** Lọc bài viết theo danh mục (Lập trình, AI, Web...) và tìm kiếm bài viết thời gian thực.
- **Tương tác Động:**
  - Nhấp giữ (Long-press) trên nút thích để hiển thị **Khay cảm xúc (Emoji Reactions)** tuyệt đẹp.
  - Xem bình luận trực tiếp ngay trên trang danh sách mà không cần chuyển trang (Tải Ajax không đồng bộ).
- **Trang chi tiết bài viết:** Trình bày nội dung bài viết đẹp mắt, hỗ trợ viết bình luận mới tức thì.

### 3. ✍️ Đăng Bài Mới (`create-post.php` & `my-posts.php`)
- Giao diện soạn thảo bài viết trực quan với các công cụ định dạng chữ.
- Upload hình ảnh đại diện cho bài viết.
- **Chế độ hiển thị linh hoạt:** Lựa chọn **Công khai** (ai cũng thấy) hoặc **Riêng tư** (chỉ bản thân thấy) thông qua dropdown tiện lợi.
- Quản lý các bài viết cá nhân và theo dõi trạng thái duyệt bài từ Admin.

### 4. 📅 Sự Kiện (`events.php` & `event.php`)
- Danh sách sự kiện của CLB (Đang diễn ra, Sắp diễn ra, Đã kết thúc).
- Chi tiết sự kiện bao gồm thời gian, địa điểm, bản đồ và nội dung chi tiết.
- Nút **Đăng ký tham gia** nhanh chóng cho thành viên đã đăng nhập.

### 5. 📚 Kho Tài Liệu (`documents.php`)
- Nơi chia sẻ các giáo trình, slide bài giảng, code mẫu và sách chuyên ngành IT.
- Hỗ trợ xem thông tin tài liệu và tải xuống nhanh chóng.

### 6. 👤 Hồ Sơ Thành Viên (`profile.php`)
- Hiển thị thông tin cá nhân: Ảnh đại diện (avatar), tiểu sử (Bio), thông tin liên hệ.
- Liệt kê danh sách các **Kỹ năng IT** chuyên nghiệp của cá nhân.
- Tích hợp nút chỉnh sửa thông tin trực tiếp.

### 7. ⚙️ Cài Đặt Giao Diện (`settings.php`)
- **Chuyển đổi giao diện:** Nút chuyển đổi nhanh Dark Mode (Chế độ tối) và Light Mode (Chế độ sáng).
- **Chế độ gọn nhẹ (Compact Mode):** Tự động thu nhỏ khoảng cách và cỡ chữ để xem được nhiều thông tin hơn cùng lúc.
- **Tự động phát đa phương tiện:** Cấu hình tự động chạy video hoặc ảnh động khi cuộn tin.

---

## 🔑 Phân Hệ Quản Trị Viên (Admin Panel)

*Chỉ tài khoản có quyền `admin` mới truy cập được phân hệ này. Giao diện tích hợp thanh điều hướng quản trị thông minh.*

### 1. 📊 Bảng Điều Khiển (`admin/index.php`)
- Thống kê tổng quan số liệu hoạt động của toàn bộ website dưới dạng thẻ báo cáo trực quan.

### 2. 🛡️ Quản Lý Tài Khoản / Thành Viên (`admin/users.php`)
- Hiển thị danh sách tất cả thành viên trong hệ thống.
- **Thêm thành viên mới:** Cho phép Admin khởi tạo trực tiếp tài khoản mới (Họ tên, Email, mật khẩu tự băm bảo mật).
- **Phân quyền & Chỉnh sửa:** Sửa đổi thông tin, nâng cấp vai trò hệ thống từ `Member` lên `Admin` hoặc ngược lại.
- **Khóa/Mở tài khoản:** Khóa tài khoản thành viên vi phạm chỉ với 1 cú click.
- **Xóa thành viên:** Xóa hoàn toàn tài khoản khỏi database.

### 3. 📝 Duyệt Bài Viết (`admin/posts.php`)
- Quản lý các bài viết do thành viên gửi lên.
- Admin có quyền nhấn **Duyệt bài** để công khai bài viết lên bảng tin chung, hoặc hủy duyệt để ẩn bài viết.

### 4. 📁 Các Tính Năng Quản Trị Khác
- **Quản lý Sự Kiện (`admin/events.php`):** Thêm sự kiện mới, theo dõi danh sách đăng ký tham gia của từng sự kiện.
- **Quản lý Tài Liệu (`admin/documents.php`):** Upload tài liệu học tập mới cho thành viên.
- **Quản lý Bình Luận (`admin/comments.php`):** Kiểm duyệt và xóa các bình luận không phù hợp.
- **Quản lý Danh Mục (`admin/categories.php`):** Thêm/Sửa/Xóa các chủ đề bài viết.

---

## 🚀 Hướng Dẫn Cài Đặt (Localhost)

1. Sao chép thư mục dự án `clbIT` vào thư mục `C:\xampp\htdocs\`.
2. Mở ứng dụng XAMPP Control Panel, khởi động **Apache** và **MySQL**.
3. Truy cập vào `http://localhost/phpmyadmin/`, tạo một cơ sở dữ liệu mới tên là `clb_it` (hoặc tên database tùy ý của bạn).
4. Nhập (Import) file cơ sở dữ liệu `.sql` của bạn vào database vừa tạo.
5. Cấu hình thông tin kết nối database tại file: [config/database.php](file:///c:/xampp/htdocs/clbIT/config/database.php).
6. Truy cập website tại địa chỉ: `http://localhost/clbIT/index.php`.

### 🔑 Thông tin tài khoản Admin mặc định:
- **Email:** `adminclbit@gmail.com`
- **Mật khẩu:** `admin123456@` (Hệ thống hỗ trợ tự động đối sánh cả dạng băm bcrypt an toàn lẫn dạng thô).

---
*Chúc bạn có những trải nghiệm tuyệt vời khi học tập và sinh hoạt tại CLB IT!*
