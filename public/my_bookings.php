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
$customer_id = null;
$stmt_cust = $conn->prepare("SELECT id FROM customers WHERE username = ?");
$stmt_cust->bind_param("s", $_SESSION['customer']);
$stmt_cust->execute();
$result_cust = $stmt_cust->get_result();
if ($result_cust->num_rows > 0) {
    $customer_id = $result_cust->fetch_assoc()['id'];
}
$stmt_cust->close();

if (!$customer_id) {
    // Nếu không tìm thấy customer, đăng xuất và về trang login
    session_destroy();
    header("Location: ../admin/login.php");
    exit();
}

// 3. Xử lý hủy đơn hàng
if (isset($_GET['cancel_id'])) {
    $booking_id_to_cancel = (int)$_GET['cancel_id'];

    // Lấy room_id từ booking để cập nhật lại trạng thái phòng
    $stmt_get_room = $conn->prepare("SELECT room_id FROM bookings WHERE id = ? AND customer_id = ? AND status IN ('pending', 'confirmed')");
    $stmt_get_room->bind_param("ii", $booking_id_to_cancel, $customer_id);
    $stmt_get_room->execute();
    $result_room = $stmt_get_room->get_result();

    if ($result_room->num_rows > 0) {
        // Bắt đầu transaction để đảm bảo cả 2 lệnh update cùng thành công
        $conn->begin_transaction();
        try {
            // Cập nhật trạng thái booking thành 'cancelled'
            $stmt_cancel = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            $stmt_cancel->bind_param("i", $booking_id_to_cancel);
            $stmt_cancel->execute();
            $stmt_cancel->close();

            $conn->commit();
            $_SESSION['message'] = "Hủy đơn đặt phòng thành công!";
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $_SESSION['error'] = "Lỗi: Không thể hủy đơn đặt phòng.";
        }
    }
    $stmt_get_room->close();
    header("Location: my_bookings.php");
    exit();
}

// [THÊM MỚI] Xử lý trả phòng sớm từ phía khách hàng
if (isset($_GET['checkout_now'])) {
    $booking_id_to_checkout = (int)$_GET['checkout_now'];

    // Xác thực đơn hàng thuộc về khách hàng và đang ở trạng thái 'confirmed'
    $stmt_checkout = $conn->prepare("UPDATE bookings SET status = 'completed' WHERE id = ? AND customer_id = ? AND status = 'confirmed'");
    $stmt_checkout->bind_param("ii", $booking_id_to_checkout, $customer_id);
    
    if ($stmt_checkout->execute() && $stmt_checkout->affected_rows > 0) {
        $_SESSION['message'] = "Đã xác nhận trả phòng. Cảm ơn bạn đã sử dụng dịch vụ! Bây giờ bạn có thể để lại đánh giá.";
    } else {
        $_SESSION['error'] = "Không thể xác nhận trả phòng hoặc đơn hàng không hợp lệ.";
    }
    $stmt_checkout->close();
    header("Location: my_bookings.php");
    exit();
}

// 4. Lấy danh sách các phòng đã đặt của khách hàng
$stmt_bookings = $conn->prepare(
    "SELECT b.id, b.checkin, b.checkout, b.status, r.room_name, r.price, r.img
     FROM bookings b
     JOIN rooms r ON b.room_id = r.id 
     WHERE b.customer_id = ? 
     ORDER BY b.id DESC"
);
$stmt_bookings->bind_param("i", $customer_id);
$stmt_bookings->execute();
$bookings = $stmt_bookings->get_result();

$page_title = 'Lịch sử đặt phòng';
ob_start();
?>
<style>
    .booking-card {
        display: flex;
        background: #fff;
        border-radius: var(--border-radius, 12px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    .booking-card-img img {
        width: 200px;
        height: 100%;
        object-fit: cover;
    }
    .booking-card-info {
        padding: 1.5rem;
        flex-grow: 1;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
    }
    .booking-card-info h3 { font-size: 1.5rem; width: 100%; margin-bottom: 0.5rem; }
    .booking-details, .booking-actions { flex: 1; min-width: 250px; }
    .status-badge { padding: 5px 12px; border-radius: 20px; color: #fff; font-weight: 600; font-size: 0.85rem; }
    .status-pending { background-color: #ffc107; color: #333; }
    .status-confirmed { background-color: #28a745; }
    .status-cancelled { background-color: #dc3545; }
    .status-completed { background-color: #007bff; }
    .btn-cancel { background-color: #dc3545; color: #fff; padding: 8px 15px; border-radius: 8px; text-decoration: none; transition: background 0.3s; }
    .btn-cancel:hover { background-color: #c82333; }
    @media (max-width: 768px) {
        .booking-card { flex-direction: column; }
        .booking-card-img img { width: 100%; height: 200px; }
    }
</style>
<?php
$extra_css = ob_get_clean();
include_once(__DIR__ . '/header.php');
?>

<main class="content-wrapper">
    <div class="room-list-header">
        <h2>Lịch sử đặt phòng của bạn</h2>
        <p>Quản lý các đơn đặt phòng và lịch trình của bạn tại đây.</p>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if ($bookings && $bookings->num_rows > 0): ?>
        <?php while ($booking = $bookings->fetch_assoc()): 
            $checkin_date = new DateTime($booking['checkin']);
            $checkout_date = new DateTime($booking['checkout']);
            $nights = $checkout_date->diff($checkin_date)->days;
            $total_price = $nights * $booking['price'];
            $can_cancel = $booking['status'] === 'pending' && (new DateTime() < $checkin_date);

            // Điều kiện để hiển thị nút "Trả phòng": đơn đã xác nhận và đang trong thời gian ở
            $today = new DateTime();
            $can_checkout_now = ($booking['status'] === 'confirmed' && $today >= $checkin_date && $today < $checkout_date);

            // Dịch trạng thái
            $status_text = '';
            switch ($booking['status']) {
                case 'pending': $status_text = 'Đang chờ'; break;
                case 'confirmed': $status_text = 'Đã xác nhận'; break;
                case 'cancelled': $status_text = 'Đã hủy'; break;
                case 'completed': $status_text = 'Đã hoàn thành'; break;
                default: $status_text = htmlspecialchars($booking['status']);
            }
        ?>
            <div class="booking-card">
                <div class="booking-card-img">
                    <img src="../assets/img/<?= htmlspecialchars($booking['img'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($booking['room_name']) ?>">
                </div>
                <div class="booking-card-info">
                    <div class="booking-details">
                        <h3><?= htmlspecialchars($booking['room_name']) ?></h3>
                        <p><strong>Ngày nhận phòng:</strong> <?= $checkin_date->format('d/m/Y') ?></p>
                        <p><strong>Ngày trả phòng:</strong> <?= $checkout_date->format('d/m/Y') ?> (<?= $nights ?> đêm)</p>
                        <p><strong>Tổng chi phí:</strong> <?= number_format($total_price, 0, ',', '.') ?> ₫</p>
                        <p><strong>Trạng thái:</strong> <span class="status-badge status-<?= strtolower($booking['status']) ?>"><?= $status_text ?></span></p>
                    </div>
                    <div class="booking-actions d-flex align-items-center justify-content-end">
                        <?php
                        switch ($booking['status']) {
                            case 'pending':
                                echo '<div class="text-end">';
                                echo '<span class="text-muted d-block mb-2">Chờ người quản trị xác nhận</span>';
                                if ($can_cancel) { // Đã là tiếng Việt
                                    echo '<a href="my_bookings.php?cancel_id=' . $booking['id'] . '" class="btn-cancel" onclick="return confirm(\'Bạn có chắc chắn muốn hủy đơn đặt phòng này không?\');">Hủy đơn</a>';
                                }
                                echo '</div>';
                                break;
                            case 'confirmed':
                                if ($can_checkout_now) {
                                    echo '<a href="my_bookings.php?checkout_now=' . $booking['id'] . '" class="btn btn-info" onclick="return confirm(\'Bạn có chắc chắn muốn xác nhận trả phòng ngay bây giờ không?\');">Trả phòng</a>';
                                } else {
                                    echo '<span class="text-success fw-bold">Đơn đã được xác nhận.</span>';
                                }
                                break;
                            case 'completed':
                                echo '<span class="text-success">Đã hoàn thành.</span>';
                                break;
                            case 'cancelled':
                                echo '<span class="text-danger">Đơn đã được hủy.</span>';
                                break;
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center p-5">
            <p>Bạn chưa có đơn đặt phòng nào.</p>
            <a href="index.php#room-list-section" class="btn btn-primary">Tìm phòng ngay</a>
        </div>
    <?php endif; ?>
</main>

<?php
include_once(__DIR__ . '/footer.php');
?>