<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once(__DIR__ . "/../config/db.php");

// Lấy thông tin khách hàng nếu đã đăng nhập
$customer_name = '';
if (isset($_SESSION['customer'])) {
    $stmt_cust_header = $conn->prepare("SELECT name FROM customers WHERE username = ?");
    $stmt_cust_header->bind_param("s", $_SESSION['customer']);
    $stmt_cust_header->execute();
    $result_cust_header = $stmt_cust_header->get_result();
    if ($result_cust_header->num_rows > 0) {
        $customer_name = $result_cust_header->fetch_assoc()['name'];
    }
    $stmt_cust_header->close();
}

// Đọc settings
$site = []; // Khởi tạo mảng rỗng để tránh lỗi

// Lấy tên trang hiện tại để làm nổi bật menu
$current_page_header = basename($_SERVER['PHP_SELF']);

// Thiết lập tiêu đề trang mặc định nếu chưa có
$page_title = $page_title ?? 'Homestay Luxury';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" href="../assets/img/logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Thêm CSS cho header và các trang khác nếu cần -->
    <?php if (isset($extra_css)): echo $extra_css; endif; ?>

    <style>
        /* [THÊM] Cấu trúc Sticky Footer */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Cải tiến Header */
        .header {
            background: rgba(17, 17, 17, 0.85); /* Nền đen mờ */
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            border-bottom: 1px solid transparent;
            position: sticky;
            top: 0;
            z-index: 1020;
            transition: background-color 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        }
        .header.scrolled {
            background: #111111; /* Nền đen đặc khi cuộn */
            border-bottom-color: #333;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .header-logo img {
            height: 50px;
            transition: transform 0.3s ease;
        }
        .header-logo img:hover {
            transform: scale(1.05);
        }
        .main-nav a {
            color: #e9ecef; /* Chữ màu trắng ngà */
            text-decoration: none;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 8px;
            transition: background-color 0.3s, color 0.3s;
        }
        .main-nav a:hover, .main-nav a.active, .main-nav a:focus {
            background-color: var(--primary-color, #007bff); /* Giữ màu xanh khi active/hover */
            color: #ffffff;
        }
        .topbar-login .btn, .topbar-login .dropdown-toggle {
            font-weight: 600;
            border-radius: 8px;
        }
        .topbar-login .dropdown-menu {
            border-radius: var(--border-radius, 12px);
            box-shadow: var(--shadow, 0 10px 30px rgba(0,0,0,0.1));
            border: none;
        }

        /* Cải tiến Footer */
        .footer {
            padding-top: 5rem; /* Giảm thêm khoảng đệm để đẩy nội dung footer lên */
            padding-bottom: 2rem;
        }
        .footer-divider {
            border: none;
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(0, 0, 0, 0.7), transparent); /* Đậm hơn */
            margin-top: 5rem; /* Giảm thêm khoảng trống để đẩy đường kẻ lên */
            margin-bottom: 0;
        }
        .footer-divider-inner {
            border: none;
            height: 1px;
            background: rgba(0, 0, 0, 0.4); /* Đậm hơn */
            margin: 3.5rem 0; /* Giảm khoảng cách trên và dưới để đẩy đường kẻ lên */
        }
    </style>
    <script>
        // Thêm một lớp vào thẻ body để dễ dàng chọn trong CSS
        document.addEventListener('DOMContentLoaded', () => document.body.classList.add('d-flex', 'flex-column', 'min-vh-100'));
    </script>
</head>
<body>
<header class="header">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="header-logo"> <a href="index.php"><img src="../assets/img/logo3.jpg" alt="Homestay Logo"></a> </div> <nav class="main-nav d-none d-lg-flex">
            <a href="index.php">Trang Chủ</a>
            <a href="index.php#room-list-section">Tìm Phòng</a>
            <a href="#footer">Liên Hệ</a>
        </nav>
        <div class="topbar-login">
            <?php if (isset($_SESSION['customer'])): ?>
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-2"></i><?= htmlspecialchars($customer_name) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                        <li><a class="dropdown-item <?= ($current_page_header == 'my_account.php') ? 'active' : '' ?>" href="my_account.php"><i class="fas fa-user-cog me-2"></i>Tài khoản của tôi</a></li>
                        <li><a class="dropdown-item <?= ($current_page_header == 'my_bookings.php') ? 'active' : '' ?>" href="my_bookings.php"><i class="fas fa-history me-2"></i>Lịch sử đặt phòng</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#loginModal">Đăng nhập</button>
                <button type="button" class="btn btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#registerModal">Đăng ký</button>
            <?php endif; ?>
            <button class="btn btn-outline-secondary ms-2 d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</header>

<div class="container">

<script>
    // Thêm hiệu ứng cho header khi cuộn trang
    document.addEventListener('DOMContentLoaded', function() {
        const header = document.querySelector('.header');
        if (header) {
            window.addEventListener('scroll', function() {
                header.classList.toggle('scrolled', window.scrollY > 10);
            });
        }
    });
</script>

<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: rgba(25, 25, 25, 0.95); border: 1px solid rgba(255, 215, 0, 0.25); border-radius: 18px;">
            <div class="modal-header" style="border-bottom: 1px solid rgba(255, 215, 0, 0.3);">
                <h5 class="modal-title" id="loginModalLabel" style="color: #f1c40f; font-weight: 600;">🔐 Đăng nhập</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
            </div>
            <div class="modal-body">
                <form id="loginForm" method="post" action="public/login.php">
                    <div class="mb-3">
                        <label class="form-label" style="color: #ffffff; font-weight: 500; margin-bottom: 8px; display: block;">Tên đăng nhập</label>
                        <input type="text" name="username" class="form-control" placeholder="Nhập tên đăng nhập của bạn" required style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 215, 0, 0.3); color: #fff; border-radius: 8px; padding: 12px 14px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color: #ffffff; font-weight: 500; margin-bottom: 8px; display: block;">Mật khẩu</label>
                        <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu của bạn" required style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 215, 0, 0.3); color: #fff; border-radius: 8px; padding: 12px 14px;">
                    </div>
                    <button type="submit" name="login" class="btn w-100" style="background: linear-gradient(135deg, #f1c40f, #d4af37); color: #000; border: none; border-radius: 8px; padding: 12px; font-weight: 600;">Đăng nhập</button>
                </form>
                <div class="text-center mt-3">
                    <small style="color: #f1c40f;">Chưa có tài khoản? <a href="#" style="color: #f1c40f; text-decoration: none;" data-bs-toggle="modal" data-bs-target="#registerModal" data-bs-dismiss="modal">Đăng ký ngay</a></small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Register Modal -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: rgba(25, 25, 25, 0.95); border: 1px solid rgba(255, 215, 0, 0.25); border-radius: 18px;">
            <div class="modal-header" style="border-bottom: 1px solid rgba(255, 215, 0, 0.3);">
                <h5 class="modal-title" id="registerModalLabel" style="color: #f1c40f; font-weight: 600;">📝 Đăng ký</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
            </div>
            <div class="modal-body">
                <form id="registerForm" method="post" action="register.php">
                    <div class="mb-3">
                        <label class="form-label" style="color: #ffffff; font-weight: 500; margin-bottom: 8px; display: block;">Họ và tên</label>
                        <input type="text" name="name" class="form-control" placeholder="Nhập họ và tên đầy đủ của bạn" required style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 215, 0, 0.3); color: #fff; border-radius: 8px; padding: 12px 14px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color: #ffffff; font-weight: 500; margin-bottom: 8px; display: block;">Tên đăng nhập</label>
                        <input type="text" name="username" class="form-control" placeholder="Chọn tên đăng nhập (không dấu, không khoảng trắng)" required style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 215, 0, 0.3); color: #fff; border-radius: 8px; padding: 12px 14px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color: #ffffff; font-weight: 500; margin-bottom: 8px; display: block;">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="Nhập địa chỉ email của bạn" required style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 215, 0, 0.3); color: #fff; border-radius: 8px; padding: 12px 14px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color: #ffffff; font-weight: 500; margin-bottom: 8px; display: block;">Mật khẩu</label>
                        <input type="password" name="password" class="form-control" placeholder="Tạo mật khẩu cho tài khoản" required style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 215, 0, 0.3); color: #fff; border-radius: 8px; padding: 12px 14px;">
                    </div>
                    <button type="submit" class="btn w-100" style="background: linear-gradient(135deg, #f1c40f, #d4af37); color: #000; border: none; border-radius: 8px; padding: 12px; font-weight: 600;">Đăng ký</button>
                </form>
                <div class="text-center mt-3">
                    <small style="color: #f1c40f;">Đã có tài khoản? <a href="#" style="color: #f1c40f; text-decoration: none;" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Đăng nhập ngay</a></small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- AJAX for form submission -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Login form AJAX
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi đăng nhập');
        });
    });

    // Register form AJAX
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('register.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                // Close modal and show login
                const registerModal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
                registerModal.hide();
                const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                loginModal.show();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi đăng ký');
        });
    });
});
</script>