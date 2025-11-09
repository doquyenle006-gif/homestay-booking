<?php
session_start();
include("../config/db.php");

// Kiểm tra quyền admin
if (!isset($_SESSION['admin'])) {
    $_SESSION['error_message'] = "Bạn không có quyền thực hiện hành động này!";
    header("Location: login.php");
    exit();
}

// Kiểm tra ID hợp lệ
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID phòng không hợp lệ.";
    header("Location: rooms.php");
    exit();
}

$id = (int)$_GET['id'];

// **[THÊM MỚI]** Kiểm tra xem phòng có booking nào không
$stmt_check = $conn->prepare("SELECT COUNT(*) as booking_count FROM bookings WHERE room_id = ?");
$stmt_check->bind_param("i", $id);
$stmt_check->execute();
$result_check = $stmt_check->get_result()->fetch_assoc();
$stmt_check->close();

if ($result_check['booking_count'] > 0) {
    // Nếu có booking, không cho xóa và thông báo lỗi
    $_SESSION['error_message'] = "Không thể xóa phòng này vì đã có đơn đặt phòng liên quan. Bạn có thể ẩn phòng thay vì xóa.";
    header("Location: rooms.php");
    exit();
}

// Lấy tên ảnh để xóa file
$stmt_img = $conn->prepare("SELECT img FROM rooms WHERE id = ?");
$stmt_img->bind_param("i", $id);
$stmt_img->execute();
$img_to_delete = $stmt_img->get_result()->fetch_assoc()['img'] ?? '';
$stmt_img->close();

// Xóa file ảnh nếu tồn tại
if ($img_to_delete && file_exists("../assets/img/" . $img_to_delete)) {
    unlink("../assets/img/" . $img_to_delete);
}

// Xóa phòng khỏi cơ sở dữ liệu
$stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$_SESSION['message'] = "Đã xóa phòng thành công!";
header("Location: rooms.php");
exit();
?>