<?php
session_start();
include("../config/db.php");

// Helper functions
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['admin']) && !empty($_SESSION['admin']);
    }
}
if (!function_exists('executeQuery')) {
    function executeQuery($sql) {
        global $conn;
        return $conn->query($sql);
    }
}
if (!function_exists('formatPrice')) {
    function formatPrice($amount) {
        if ($amount === null) $amount = 0;
        return number_format((float)$amount, 0, ',', '.') . ' ₫';
    }
}
if (!function_exists('translate_status_to_vietnamese')) {
    function translate_status_to_vietnamese($status) {
        switch (strtolower($status)) {
            case 'pending': return 'Đang chờ';
            case 'confirmed': return 'Đã xác nhận';
            case 'cancelled': return 'Đã hủy';
            case 'completed': return 'Đã hoàn thành';
            default: return htmlspecialchars($status);
        }
    }
}
if (!function_exists('redirect')) {
    function redirect($url) { header("Location: $url"); exit(); }
}

if (!isAdmin()) {
    $_SESSION['error_message'] = "Bạn không có quyền truy cập trang quản trị!";
    redirect('login.php');
}

// Fetch stats
$total_users = executeQuery("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'] ?? 0;
$total_rooms = executeQuery("SELECT COUNT(*) as count FROM rooms")->fetch_assoc()['count'] ?? 0;
$total_bookings = executeQuery("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'] ?? 0;
$pending_booking = executeQuery("SELECT COUNT(*) as count FROM bookings WHERE status='pending'")->fetch_assoc()['count'] ?? 0;
$confirmed_booking = executeQuery("SELECT COUNT(*) as count FROM bookings WHERE status='confirmed'")->fetch_assoc()['count'] ?? 0;

$revenue_sql = "SELECT SUM(r.price * GREATEST(DATEDIFF(b.checkout, b.checkin), 1)) AS revenue
                FROM bookings b
                JOIN rooms r ON b.room_id = r.id
                WHERE b.status = 'confirmed'";
$revenue_result = executeQuery($revenue_sql);
$total_revenue = 0;
if ($revenue_result) {
    $row = $revenue_result->fetch_assoc();
    $total_revenue = $row['revenue'] ?? 0;
}

$recent_bookings_sql = "SELECT b.*, c.name as full_name, r.room_name as room_name
                        FROM bookings b
                        JOIN customers c ON b.customer_id = c.id
                        JOIN rooms r ON b.room_id = r.id
                        ORDER BY b.id DESC
                        LIMIT 5";
$recent_bookings = executeQuery($recent_bookings_sql);
$booking_stats = executeQuery("SELECT status, COUNT(*) as count FROM bookings GROUP BY status");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="includes/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="admin-area admin-dashboard">
    <div class="layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="main-panel">
            <div class="panel-header">
                <h2 class="greeting">Xin chào, <?= htmlspecialchars($_SESSION['admin']) ?> 👋</h2>
            </div>

            <div class="dashboard-main-header">
                <h1> 🏕️  Quản lý Homestay</h1>
                <p>Quản lý các homestay, giúp du khách có trải nghiệm tốt nhất</p>
            </div>
            <div class="stats-grid">
                <div class="stat-card gradient-indigo">
                    <div class="stat-value"><?= $total_users ?></div>
                    <div class="stat-label">Tổng người dùng</div>
                </div>
                <div class="stat-card gradient-green">
                    <div class="stat-value"><?= $total_rooms ?></div>
                    <div class="stat-label">Tổng phòng</div>
                </div>
                <div class="stat-card gradient-sun">
                    <div class="stat-value"><?= $total_bookings ?></div>
                    <div class="stat-label">Tổng đặt phòng</div>
                </div>
                <div class="stat-card gradient-blue">
                    <div class="stat-value"><?= formatPrice($total_revenue) ?></div>
                    <div class="stat-label">Tổng doanh thu</div>
                </div>
            </div>

            <div class="content-grid">
                <div> <!-- Cột chứa cả hai bảng -->
                    <div class="card block card-stats">
                        <div class="card-title">Thống kê đặt phòng</div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th class="text-left">Trạng thái</th>
                                    <th class="text-center">Số lượng</th>
                                    <th class="text-right">Tỷ lệ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($stat = $booking_stats->fetch_assoc()):
                                    $percentage = $total_bookings > 0 ? ($stat['count'] / $total_bookings) * 100 : 0;
                                ?>
                                    <tr>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($stat['status']) ?>"><?= translate_status_to_vietnamese($stat['status']) ?></span>
                                        </td>
                                        <td class="text-center"><?= $stat['count'] ?></td>
                                        <td class="text-right"><?= number_format($percentage,1) ?>%</td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card block card-recent mt-4"> <!-- Thêm mt-4 để tạo khoảng cách -->
                        <div class="card-title">Đặt phòng gần đây</div>
                        <?php if ($recent_bookings && $recent_bookings->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Khách hàng</th>
                                        <th>Phòng</th>
                                        <th>Ngày đặt</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while ($booking = $recent_bookings->fetch_assoc()):
                                    $date_to_show = $booking['checkin'] ?? $booking['checkout'] ?? null;
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($booking['full_name']) ?></td>
                                        <td><?= htmlspecialchars($booking['room_name']) ?></td>
                                        <td><?= $date_to_show ? date('d/m/Y', strtotime($date_to_show)) : '-' ?></td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($booking['status']) ?>"><?= translate_status_to_vietnamese($booking['status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <p class="muted">Chưa có đặt phòng nào.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div> <!-- end main-panel -->
    </div> <!-- end layout -->
</div>
</body>
</html>
