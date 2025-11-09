<?php
session_start();
// Khuyến nghị: Dùng hằng số (constants) hoặc biến môi trường cho đường dẫn
include("../config/db.php"); // Giả định db.php chứa $conn

// Nếu chưa đăng nhập thì quay lại login
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Hàm cập nhật trạng thái Booking và Room (Sử dụng Prepared Statements)
function updateBookingStatus($conn, $booking_id, $booking_status, $room_status) {
    // Cập nhật trạng thái booking
    $stmt_booking = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt_booking->bind_param("si", $booking_status, $booking_id);
    $success = $stmt_booking->execute();
    $stmt_booking->close();

    // [THAY ĐỔI] Không cần cập nhật trạng thái của bảng `rooms` nữa
    // vì chúng ta đã có số lượng. Việc phòng có sẵn hay không
    // sẽ được quyết định bằng cách đếm các booking đang hoạt động.
    return $success;
}

// Xử lý Hành động (Xác nhận/Hủy)
if (isset($_GET['confirm']) && is_numeric($_GET['confirm'])) {
    $id = (int)$_GET['confirm'];
    if (updateBookingStatus($conn, $id, 'confirmed', 'booked')) {
        $_SESSION['message'] = "Xác nhận đặt phòng thành công!";
    }
    header("Location: bookings.php");
    exit();
}

if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $id = (int)$_GET['cancel'];
    if (updateBookingStatus($conn, $id, 'cancelled', 'available')) {
        $_SESSION['message'] = "Hủy đặt phòng thành công!";
    }
    header("Location: bookings.php");
    exit();
}

if (isset($_GET['complete']) && is_numeric($_GET['complete'])) {
    $id = (int)$_GET['complete'];
    $stmt_complete = $conn->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?");
    $stmt_complete->bind_param("i", $id);
    $stmt_complete->execute();
    header("Location: bookings.php");
    exit();
}

// Xử lý Xóa đặt phòng
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt_delete = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt_delete->bind_param("i", $id);
    if ($stmt_delete->execute()) {
        $_SESSION['message'] = "Xóa đặt phòng thành công!";
    } else {
        $_SESSION['error_message'] = "Lỗi khi xóa đặt phòng!";
    }
    header("Location: bookings.php");
    exit();
}

// Xử lý Cập nhật đặt phòng
if (isset($_POST['update_booking'])) {
    $id = $_POST['edit_id'];
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout'];
    $status = $_POST['status'];

    // Debug: Check if data is received
    error_log("Update booking: id=$id, checkin=$checkin, checkout=$checkout, status=$status");

    $stmt = $conn->prepare("UPDATE bookings SET checkin = ?, checkout = ?, status = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("sssi", $checkin, $checkout, $status, $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Cập nhật đặt phòng thành công!";
        } else {
            $_SESSION['error_message'] = "Lỗi khi cập nhật đặt phòng: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Lỗi chuẩn bị câu lệnh: " . $conn->error;
    }
    header("Location: bookings.php");
    exit();
}

// Xử lý tìm kiếm
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

// [THÊM] Logic phân trang
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Lấy tổng số booking
$count_sql = "SELECT COUNT(b.id) as total
              FROM bookings b
              JOIN customers c ON b.customer_id = c.id
              JOIN rooms r ON b.room_id = r.id";
if (!empty($search_keyword)) {
    $count_sql .= " WHERE (c.name REGEXP ? OR r.room_name LIKE ? OR c.phone LIKE ? OR b.status LIKE ?)";
    $stmt_total = $conn->prepare($count_sql);
    $search_param_total = "[[:<:]]" . $conn->real_escape_string($search_keyword) . "[[:>:]]";
    $stmt_total->bind_param("ssss", $search_param_total, $search_param_total, $search_param_total, $search_param_total);
    $stmt_total->execute();
    $total_records = $stmt_total->get_result()->fetch_assoc()['total'];
    $stmt_total->close();
} else {
    $total_records = $conn->query($count_sql)->fetch_assoc()['total'];
}
$total_pages = ceil($total_records / $records_per_page);

// Cập nhật câu lệnh SQL chính để lấy dữ liệu theo trang
$sql = "SELECT b.id AS booking_id, c.name AS customer_name, c.phone, r.room_name, b.checkin, b.checkout, b.status
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN rooms r ON b.room_id = r.id";
        
if (!empty($search_keyword)) {
    $sql .= " WHERE (c.name REGEXP ? OR r.room_name LIKE ? OR c.phone LIKE ? OR b.status LIKE ?)";
    $sql .= " ORDER BY b.id DESC LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $search_param = "[[:<:]]" . $conn->real_escape_string($search_keyword) . "[[:>:]]";
        $stmt->bind_param("ssssii", $search_param, $search_param, $search_param, $search_param, $offset, $records_per_page);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $_SESSION['error_message'] = "Lỗi truy vấn tìm kiếm: " . $conn->error;
        $result = false;
    }
} else {
    $sql .= " ORDER BY b.id DESC LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $offset, $records_per_page);
    $stmt->execute();
    $result = $stmt->get_result();
}

/**
 * Dịch trạng thái sang tiếng Việt.
 * @param string $status Trạng thái tiếng Anh.
 * @return string Trạng thái tiếng Việt.
 */
function translate_status_to_vietnamese($status) {
    switch (strtolower($status)) {
        case 'pending': return 'Đang chờ';
        case 'confirmed': return 'Đã xác nhận';
        case 'cancelled': return 'Đã hủy';
        case 'completed': return 'Đã hoàn thành';
        default: return htmlspecialchars($status);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách đặt phòng </title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="includes/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="admin-area">
    <div class="layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="main-panel">
    <?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <h2>Danh sách đặt phòng </h2>
    
    <!-- Search Form -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-center">
                <div class="col-md-8">
                    <input type="text" name="search" value="<?= htmlspecialchars($search_keyword) ?>" class="form-control" placeholder="Tìm kiếm theo tên khách hàng, tên phòng, số điện thoại hoặc trạng thái...">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary me-2">🔍 Tìm kiếm</button>
                    <?php if (!empty($search_keyword)): ?>
                        <a href="bookings.php" class="btn btn-secondary">Xóa tìm kiếm</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Khách hàng</th>
                        <th>SĐT</th>
                        <th>Phòng</th>
                        <th>Ngày nhận phòng</th>
                        <th>Ngày trả phòng</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()){ ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($row['booking_id']) ?></td>
                                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                <td><?= htmlspecialchars($row['room_name']) ?></td>
                                <td><?= htmlspecialchars(date("d/m/Y", strtotime($row['checkin']))) ?></td>
                                <td><?= htmlspecialchars(date("d/m/Y", strtotime($row['checkout']))) ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                        <?= translate_status_to_vietnamese($row['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex;gap:12px;align-items:center;justify-content:center;">
                                        <!-- Nút Sửa -->
                                        <button type="button" class="icon-btn btn-edit-booking"
                                            data-id="<?= $row['booking_id'] ?>"
                                            data-checkin="<?= htmlspecialchars($row['checkin']) ?>"
                                            data-checkout="<?= htmlspecialchars($row['checkout']) ?>"
                                            data-status="<?= htmlspecialchars($row['status']) ?>">✏️ <span>Sửa</span></button>
                                        
                                        <!-- Nút Xóa -->
                                        <button type="button" class="icon-btn btn-delete-booking" data-id="<?= $row['booking_id'] ?>">🗑️ <span>Xóa</span></button>
                                    </div>
                                </td>
                            </tr>
                        <?php
                        }
                    } else {
                        echo '<tr><td colspan="8" class="text-center py-4">Không có đặt phòng nào.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search_keyword) ?>">Trước</a></li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search_keyword) ?>"><?= $i ?></a></li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search_keyword) ?>">Sau</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>

    <!-- Edit Modal -->
    <div id="editBookingModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chỉnh sửa đặt phòng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" id="editFormContent">
                        <input type="hidden" name="edit_id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Ngày nhận phòng</label>
                            <input type="date" name="checkin" id="edit_checkin" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ngày trả phòng</label>
                            <input type="date" name="checkout" id="edit_checkout" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" id="edit_status" class="form-control" required>
                                <option value="pending">Đang chờ</option>
                                <option value="confirmed">Đã xác nhận</option>
                                <option value="cancelled">Đã hủy</option>
                                <option value="completed">Đã hoàn thành</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="update_booking" form="editFormContent" class="btn btn-primary">Cập nhật</button>
                </div>
            </div>
        </div>
    </div>
    
    </div>
        </div> <!-- end main-panel -->
    </div> <!-- end layout -->
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Initialize booking functions - no need to clear other functions
</script>
<script>
// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if booking modal element exists
    const editBookingModalElement = document.getElementById('editBookingModal');
    
    if (!editBookingModalElement) {
        console.error('Edit modal element not found!');
        return;
    }

    // Edit Modal Logic for Bookings
    const editModalBooking = new bootstrap.Modal(editBookingModalElement);
    
    // Event delegation for dynamic content
    document.querySelector('.main-panel').addEventListener('click', function(event) {
        const editButton = event.target.closest('.btn-edit-booking');
        if (editButton) {
            const id = editButton.getAttribute('data-id');
            const checkin = editButton.getAttribute('data-checkin');
            const checkout = editButton.getAttribute('data-checkout');
            const status = editButton.getAttribute('data-status');

            document.getElementById('edit_id').value = id;
            document.getElementById('edit_checkin').value = checkin;
            document.getElementById('edit_checkout').value = checkout;
            document.getElementById('edit_status').value = status;

            editModalBooking.show();
        }

        const deleteButton = event.target.closest('.btn-delete-booking');
        if (deleteButton) {
            const id = deleteButton.getAttribute('data-id');
            if (confirm('Bạn có chắc chắn muốn xóa đặt phòng này không? Hành động này không thể hoàn tác.')) {
                window.location.href = 'bookings.php?delete=' + id;
            }
        }
    });
});
</script>
</body>
</html>

 
