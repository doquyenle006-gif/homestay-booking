<?php
session_start();
include("../config/db.php");

// Nếu chưa đăng nhập thì quay lại login
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Xử lý xóa khách hàng
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Kiểm tra xem khách hàng có đang có booking không
    $check_booking = $conn->prepare("SELECT COUNT(*) as booking_count FROM bookings WHERE customer_id = ?");
    $check_booking->bind_param("i", $id);
    $check_booking->execute();
    $result = $check_booking->get_result();
    $booking_count = $result->fetch_assoc()['booking_count'];
    $check_booking->close();
    
    if ($booking_count > 0) {
        $_SESSION['error_message'] = "Không thể xóa khách hàng này vì đang có đơn đặt phòng liên quan.";
        header("Location: customers.php");
        exit();
    }
    
    // Xóa khách hàng
    $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Xóa khách hàng thành công!";
    } else {
        $_SESSION['error_message'] = "Có lỗi xảy ra khi xóa khách hàng.";
    }
    
    $stmt->close();
}

header("Location: customers.php");
exit();
?>