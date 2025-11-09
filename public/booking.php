<?php
include_once(__DIR__ . '/../config/db.php');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $room_id = (int)$_POST['room_id'];
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout'];
    // [THÊM] Lấy phương thức thanh toán khách hàng đã chọn
    $payment_method = $_POST['payment_method'] ?? 'Tiền mặt';

    if (empty($name) || empty($phone) || empty($email) || empty($checkin) || empty($checkout)) {
        $errors[] = "Vui lòng điền đầy đủ tất cả các trường bắt buộc.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Địa chỉ Email không hợp lệ.";
    } elseif (strtotime($checkin) >= strtotime($checkout)) {
        $errors[] = "Ngày nhận phòng phải trước ngày đi. Vui lòng chọn lại.";
    } elseif (strtotime($checkin) < strtotime(date('Y-m-d'))) {
        $errors[] = "Ngày nhận phòng không được là ngày trong quá khứ.";
    }

    if (empty($errors)) {
        // Kiểm tra số lượng phòng còn trống
        $stmt_check_availability = $conn->prepare(
            "SELECT quantity FROM rooms WHERE id = ?"
        );
        $stmt_check_availability->bind_param("i", $room_id);
        $stmt_check_availability->execute();
        $room_info = $stmt_check_availability->get_result()->fetch_assoc();
        $total_quantity = $room_info['quantity'] ?? 0;
        $stmt_check_availability->close();

        $stmt_booked_count = $conn->prepare(
            "SELECT COUNT(id) as booked_count FROM bookings 
             WHERE room_id = ? AND status IN ('pending', 'confirmed') 
             AND checkin < ? AND checkout > ?"
        );
        $stmt_booked_count->bind_param("iss", $room_id, $checkout, $checkin);
        $stmt_booked_count->execute();
        $booked_count = $stmt_booked_count->get_result()->fetch_assoc()['booked_count'];
        $stmt_booked_count->close();

        if ($booked_count >= $total_quantity) {
            $errors[] = "Rất tiếc, phòng này đã hết vào ngày bạn chọn. Vui lòng chọn ngày khác.";
        }
    }
    
    if (empty($errors)) {
        $customer_id = null;
        $customer_name = '';

        // Ưu tiên lấy customer_id từ session nếu đã đăng nhập
        if (isset($_SESSION['customer'])) {
            $stmt_cust_session = $conn->prepare("SELECT id, name FROM customers WHERE username = ?");
            $stmt_cust_session->bind_param("s", $_SESSION['customer']);
            $stmt_cust_session->execute();
            $result = $stmt_cust_session->get_result()->fetch_assoc();
            $customer_id = $result['id'] ?? null;
            $customer_name = $result['name'] ?? '';
            $stmt_cust_session->close();
        } else {
            // Nếu chưa đăng nhập, tìm hoặc tạo khách hàng mới bằng email/phone
            $stmt_cust = $conn->prepare("SELECT id FROM customers WHERE email = ? OR phone = ?");
            $stmt_cust->bind_param("ss", $email, $phone);
            $stmt_cust->execute();
            $result_cust = $stmt_cust->get_result();

            if ($result_cust->num_rows > 0) {
                $customer_id = $result_cust->fetch_assoc()['id'];
            } else {
                $stmt_insert_cust = $conn->prepare("INSERT INTO customers (name, phone, email) VALUES (?, ?, ?)");
                $stmt_insert_cust->bind_param("sss", $name, $phone, $email);
                if ($stmt_insert_cust->execute()) {
                    $customer_id = $stmt_insert_cust->insert_id;
                } else {
                    $errors[] = "Lỗi hệ thống khi lưu thông tin khách hàng: " . $conn->error;
                }
                $stmt_insert_cust->close();
            }
            $stmt_cust->close();
        }

        if (!empty($customer_id)) {
            // [THAY ĐỔI] Sử dụng transaction để đảm bảo tính toàn vẹn dữ liệu
            $conn->begin_transaction();
            try {
                // 1. Tạo đơn đặt phòng
                $stmt_booking = $conn->prepare("INSERT INTO bookings (customer_id, room_id, checkin, checkout, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt_booking->bind_param("iiss", $customer_id, $room_id, $checkin, $checkout);
                $stmt_booking->execute();
                $booking_id = $conn->insert_id;
                $stmt_booking->close();

                // 2. [THÊM LẠI] Tự động tạo một bản ghi thanh toán với số tiền là 0
                // Quản trị viên sẽ cập nhật số tiền thực tế sau khi xác nhận
                $initial_amount = 0;
                $notes = "Khách hàng chọn thanh toán bằng " . $payment_method;

                $stmt_payment = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, notes) VALUES (?, ?, ?, ?)");
                $stmt_payment->bind_param("idss", $booking_id, $initial_amount, $payment_method, $notes);
                $stmt_payment->execute();
                $stmt_payment->close();

                // Nếu mọi thứ thành công, commit transaction
                $conn->commit();
                $_SESSION['message'] = "Đặt phòng **THÀNH CÔNG!** Đơn hàng của bạn đang chờ xác nhận từ quản trị viên.";
                header("Location: " . $_SERVER['PHP_SELF'] . "?room_id=" . $room_id . "&checkin=" . $checkin . "&checkout=" . $checkout);
                exit();

            } catch (mysqli_sql_exception $exception) {
                $conn->rollback(); // Hoàn tác nếu có lỗi
                $errors[] = "Lỗi hệ thống, không thể hoàn tất đặt phòng. Vui lòng thử lại. " . $exception->getMessage();
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }
}

$room_id = $_GET['room_id'] ?? '';
$room_name = 'Phòng';
$room_quantity = 0;
$room_price = 0;
$customer_name = '';

// Lấy ngày từ URL nếu có (từ trang tìm kiếm)
$url_checkin = $_GET['checkin'] ?? '';
$url_checkout = $_GET['checkout'] ?? '';

if ($room_id) {
    $stmt_room = $conn->prepare("SELECT room_name, quantity, price FROM rooms WHERE id = ?");
    $stmt_room->bind_param("i", $room_id);
    $stmt_room->execute();
    $result_room = $stmt_room->get_result();
    $room_data = $result_room->fetch_assoc();
    $room_price = $room_data['price'] ?? 0;
    $room_name = $room_data['room_name'] ?? 'Phòng';
    $stmt_room->close();
}

// Lấy thông tin customer nếu đã đăng nhập
if (isset($_SESSION['customer'])) {
    $stmt_cust_info = $conn->prepare("SELECT name FROM customers WHERE username = ?");
    $stmt_cust_info->bind_param("s", $_SESSION['customer']);
    $stmt_cust_info->execute();
    $customer_name = $stmt_cust_info->get_result()->fetch_assoc()['name'] ?? '';
    $stmt_cust_info->close();
}

$session_errors = $_SESSION['errors'] ?? [];
$session_message = $_SESSION['message'] ?? '';
unset($_SESSION['errors']);
unset($_SESSION['message']);

$page_title = 'Đặt phòng - ' . htmlspecialchars($room_name);
ob_start();
?>
    <style>
        .content-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        .booking-container {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 500px;
            width: 100%;
            color: #333;
            animation: fadeIn 0.5s ease-in-out;
        }
        .booking-header {
            background: linear-gradient(90deg, #007bff, #0056b3);
            color: #fff;
            padding: 15px;
            border-radius: 15px 15px 0 0;
            text-align: center;
            margin: -30px -30px 20px -30px;
        }
        .form-label {
            font-weight: 500;
            color: #333;
        }
        .form-control {
            border-radius: 10px;
            border-color: #ced4da;
            padding: 12px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.3);
            background-color: #f8f9fa;
        }
        .btn-primary {
            background: linear-gradient(90deg, #007bff, #0056b3);
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #0056b3, #003366);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
        }
        .btn-link {
            color: #007bff;
            text-decoration: none;
        }
        .btn-link:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 576px) {
            .booking-container {
                margin: 20px;
                padding: 20px;
            }
            .booking-header {
                margin: -20px -20px 20px -20px;
            }
        }
    </style>
<?php 
$extra_css = ob_get_clean();
include_once(__DIR__ . '/header.php'); 
?>
    <main class="content-wrapper">
        <div class="booking-container">
            <div class="booking-header">
                <h2>📅 Đặt phòng: <?= htmlspecialchars($room_name) ?></h2>
            </div>
            <?php if ($session_message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($session_message) ?></div>
            <?php endif; ?>
            <?php if (!empty($session_errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($session_errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form method="post" class="needs-validation" novalidate>
                <input type="hidden" name="room_id" value="<?= htmlspecialchars($room_id) ?>">
                <div class="mb-3">
                    <label for="name" class="form-label">Họ tên:</label>
                    <input type="text" id="name" name="name" class="form-control" required
                           value="<?= htmlspecialchars($_POST['name'] ?? $customer_name) ?>" 
                           placeholder="Tên đầy đủ của bạn">
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">SĐT:</label>
                    <input type="text" id="phone" name="phone" class="form-control" required 
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="Số điện thoại liên hệ">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" required 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="Email để nhận xác nhận">
                </div>
                
                <div class="mb-3">
                    <label for="checkin" class="form-label">Ngày nhận phòng:</label>
                    <input type="date" id="checkin_date_booking" name="checkin" class="form-control" required
                           value="<?= htmlspecialchars($_POST['checkin'] ?? $url_checkin) ?>" min="<?= date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label for="checkout" class="form-label">Ngày trả phòng:</label>
                    <input type="date" id="checkout_date_booking" name="checkout" class="form-control" required
                           value="<?= htmlspecialchars($_POST['checkout'] ?? $url_checkout) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                </div>

                <!-- [THÊM] Form thanh toán -->
                <div class="payment-section mt-4 pt-3 border-top">
                    <h4 class="mb-3">Thông tin thanh toán</h4>
                    <div class="mb-3">
                        <label for="total_cost" class="form-label">Số tiền cần thanh toán:</label>
                        <input type="text" id="total_cost" class="form-control" readonly style="font-weight: bold; background-color: #e9ecef;">
                    </div>
                    <div class="mb-3">
                        <label for="payment_method_select" class="form-label">Phương thức thanh toán:</label>
                        <select id="payment_method_select" name="payment_method" class="form-select">
                            <option value="Tiền mặt" selected>Thanh toán tại quầy (Tiền mặt)</option>
                            <option value="Chuyển khoản">Chuyển khoản ngân hàng</option>
                        </select>
                    </div>
                    <div id="bank_info" class="alert alert-info" style="display: none;">
                        <p class="small mb-2">Vui lòng thực hiện chuyển khoản đến thông tin tài khoản bên dưới với nội dung: <strong>[Tên của bạn] - [Số điện thoại]</strong>.</p>
                        <strong>Ngân hàng:</strong> Vietcombank<br>
                        <strong>Số tài khoản:</strong> 999988887777<br>
                        <strong>Chủ tài khoản:</strong> HOMESTAY MANAGEMENT
                    </div>
                </div>
                <button type="submit" name="book" class="btn btn-primary w-100">HOÀN TẤT ĐẶT PHÒNG</button>
            </form>
            <a href="index.php" class="btn btn-link mt-3">⬅ Quay lại trang chủ</a>
        </div>
    </main>
<?php 
include_once(__DIR__ . '/footer.php');
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkinInput = document.getElementById('checkin_date_booking');
    const checkoutInput = document.getElementById('checkout_date_booking');
    const totalCostInput = document.getElementById('total_cost');
    const paymentMethodSelect = document.getElementById('payment_method_select');
    const roomPrice = <?= $room_price ?>;

    function updateCheckoutMinDate() {
        if (checkinInput.value) {
            // Ngày trả phòng phải sau ngày đến ít nhất 1 ngày
            const checkinDate = new Date(checkinInput.value);
            checkinDate.setDate(checkinDate.getDate() + 1);
            const minCheckoutDate = checkinDate.toISOString().split('T')[0];
            checkoutInput.min = minCheckoutDate;

            // Nếu ngày đi hiện tại không hợp lệ, tự động cập nhật
            if (!checkoutInput.value || checkoutInput.value <= checkinInput.value) {
                checkoutInput.value = minCheckoutDate;
            }
        }
    }

    function calculateTotalCost() {
        if (checkinInput.value && checkoutInput.value && roomPrice > 0) {
            const checkinDate = new Date(checkinInput.value);
            const checkoutDate = new Date(checkoutInput.value);
            const nights = (checkoutDate - checkinDate) / (1000 * 60 * 60 * 24);
            if (nights > 0) {
                const total = nights * roomPrice;
                totalCostInput.value = total.toLocaleString('vi-VN') + ' ₫';
            }
        }
    }

    function toggleBankInfo() {
        const bankInfoDiv = document.getElementById('bank_info');
        if (paymentMethodSelect.value === 'Chuyển khoản') {
            bankInfoDiv.style.display = 'block';
        } else {
            bankInfoDiv.style.display = 'none';
        }
    }

    if (checkinInput && checkoutInput) {
        checkinInput.addEventListener('change', updateCheckoutMinDate);
        checkinInput.addEventListener('change', calculateTotalCost);
        checkoutInput.addEventListener('change', calculateTotalCost);
        updateCheckoutMinDate(); // Chạy lần đầu khi tải trang
        calculateTotalCost(); // Chạy lần đầu khi tải trang

        paymentMethodSelect.addEventListener('change', toggleBankInfo);
        toggleBankInfo(); // Chạy lần đầu để ẩn/hiện thông tin ngân hàng
    }
});
</script>