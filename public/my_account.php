<?php
include_once(__DIR__ . '/../config/db.php');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Kiểm tra khách hàng đã đăng nhập chưa
if (!isset($_SESSION['customer'])) {
    header("Location: ../admin/login.php");
    exit();
}

// 2. Lấy thông tin khách hàng từ session
$customer_info = null;
$stmt_cust = $conn->prepare("SELECT id, name, email, phone FROM customers WHERE username = ?");
$stmt_cust->bind_param("s", $_SESSION['customer']);
$stmt_cust->execute();
$result_cust = $stmt_cust->get_result();
if ($result_cust->num_rows > 0) {
    $customer_info = $result_cust->fetch_assoc();
}
$stmt_cust->close();

if (!$customer_info) {
    session_destroy();
    header("Location: ../admin/login.php");
    exit();
}

// 3. Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    $stmt_update = $conn->prepare("UPDATE customers SET name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt_update->bind_param("sssi", $name, $email, $phone, $customer_info['id']);
    if ($stmt_update->execute()) {
        $_SESSION['message'] = "Cập nhật thông tin tài khoản thành công!";
    } else {
        $_SESSION['error'] = "Lỗi khi cập nhật thông tin.";
    }
    $stmt_update->close();
    header("Location: my_account.php");
    exit();
}

// 4. Xử lý cập nhật mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $password = $_POST['password'];
    if (!empty($password)) {
        // Lưu ý: Trong thực tế, bạn nên mã hóa mật khẩu trước khi lưu
        $stmt_pass = $conn->prepare("UPDATE customers SET password = ? WHERE id = ?");
        $stmt_pass->bind_param("si", $password, $customer_info['id']);
        if ($stmt_pass->execute()) {
            $_SESSION['message'] = "Cập nhật mật khẩu thành công!";
        } else {
            $_SESSION['error'] = "Lỗi khi cập nhật mật khẩu.";
        }
        $stmt_pass->close();
        header("Location: my_account.php#change-password");
        exit();
    }
}

// Lấy tên trang hiện tại để làm nổi bật menu
$current_page = basename($_SERVER['PHP_SELF']);

$page_title = 'Tài khoản của tôi';
ob_start();
?>
<style>
    .form-container {
        background: #fff; /* Nền trắng */
        border-radius: var(--border-radius, 12px);
        border: 1px solid #e9ecef; /* Viền xám nhạt */
        padding: 1.5rem; /* Giảm padding */
        box-shadow: 0 4px 20px rgba(0,0,0,0.08); /* Thêm bóng đổ cho nổi bật */
    }
    .profile-header {
        text-align: center;
        margin-bottom: 1.5rem;
    }
    .profile-avatar {
        font-size: 4rem; /* Thu nhỏ avatar */
        color: var(--primary-color, #007bff);
        margin-bottom: 0.8rem; /* Giảm margin */
    }
    .profile-name {
        font-size: 1.25rem; /* Thu nhỏ tên */
        font-weight: 600;
        color: #212529;
    }
    .profile-email {
        color: #6c757d;
        font-size: 0.9rem; /* Thu nhỏ email */
    }
    .form-label { font-weight: 500; color: #495057; }
    .btn-update { background: linear-gradient(135deg, #f1c40f, #d4af37); color: #000; border: none; font-weight: 600; }
</style>
<?php
$extra_css = ob_get_clean();
include_once(__DIR__ . '/header.php');
?>

<main class="content-wrapper">
    <div class="room-list-header">
        <h2>Tài khoản của tôi</h2>
        <p>Quản lý thông tin cá nhân và mật khẩu của bạn.</p>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="form-container">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h3 class="profile-name"><?= htmlspecialchars($customer_info['name']) ?></h3>
                    <p class="profile-email"><?= htmlspecialchars($customer_info['email']) ?></p>
                </div>
                <hr class="my-4">
                <!-- Form thông tin cá nhân -->
                <div>
                    <h4 class="mb-4">Thông tin cá nhân</h4>
                    <form method="post">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Họ và tên</label>
                                <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($customer_info['name']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Số điện thoại</label>
                                <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($customer_info['phone']) ?>" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($customer_info['email']) ?>" required>
                            </div>
                        </div>
                        <button type="submit" name="update_account" class="btn btn-update">Lưu thay đổi</button>
                    </form>
                </div>
                <hr class="my-4"> 
                <!-- Form thay đổi mật khẩu -->
                <div id="change-password">
                    <h4 class="mb-3">Thay đổi mật khẩu</h4>
                    <form method="post">
                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu mới</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Nhập mật khẩu mới" required>
                        </div>
                        <button type="submit" name="update_password" class="btn btn-update">Cập nhật mật khẩu</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include_once(__DIR__ . '/footer.php'); ?>