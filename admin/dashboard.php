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

// [THÊM] Lấy bộ lọc ngày từ URL
$filter_checkin = isset($_GET['checkin']) && !empty($_GET['checkin']) ? $_GET['checkin'] : null;
$filter_checkout = isset($_GET['checkout']) && !empty($_GET['checkout']) ? $_GET['checkout'] : null;

// [THÊM] Xây dựng mệnh đề WHERE và tham số cho việc lọc ngày
$date_where_clause = "";
$date_params = [];
$date_types = "";

if ($filter_checkin && $filter_checkout) {
    // Lọc các booking có khoảng thời gian giao với khoảng thời gian đã chọn
    // (start1 < end2) and (end1 > start2)
    $date_where_clause = " WHERE b.checkin < ? AND b.checkout > ?";
    $date_params = [$filter_checkout, $filter_checkin];
    $date_types = "ss";
}

// Fetch stats
$total_users = executeQuery("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'] ?? 0;
$total_rooms = executeQuery("SELECT COUNT(*) as count FROM rooms")->fetch_assoc()['count'] ?? 0;

// [CẬP NHẬT] Các truy vấn thống kê để áp dụng bộ lọc ngày
$base_booking_query = "FROM bookings b ";

// Tổng số đặt phòng
$total_bookings_sql = "SELECT COUNT(*) as count " . $base_booking_query . $date_where_clause;
$stmt_total = $conn->prepare($total_bookings_sql);
if ($filter_checkin && $filter_checkout) $stmt_total->bind_param($date_types, ...$date_params);
$stmt_total->execute();
$total_bookings = $stmt_total->get_result()->fetch_assoc()['count'] ?? 0;
$stmt_total->close();

// [CẬP NHẬT] Tính doanh thu từ bảng `payments`
$revenue_sql = "SELECT SUM(p.amount) AS revenue FROM payments p";
$revenue_params = []; // Khởi tạo lại để tránh lỗi
$revenue_types = "";  // Khởi tạo lại

if ($filter_checkin && $filter_checkout) {
    // Join với bảng bookings để lọc theo ngày
    $revenue_sql = "SELECT SUM(p.amount) AS revenue FROM payments p JOIN bookings b ON p.booking_id = b.id WHERE b.checkin < ? AND b.checkout > ?";
    $revenue_params = [$filter_checkout, $filter_checkin];
    $revenue_types = "ss";
}

$stmt_revenue = $conn->prepare($revenue_sql);
if (!empty($revenue_params)) {
    $stmt_revenue->bind_param($revenue_types, ...$revenue_params);
}
$stmt_revenue->execute();
$total_revenue = $stmt_revenue->get_result()->fetch_assoc()['revenue'] ?? 0;
$stmt_revenue->close();

// Đặt phòng gần đây
$recent_bookings_sql = "SELECT b.*, c.name as full_name, r.room_name as room_name
                        FROM bookings b JOIN customers c ON b.customer_id = c.id JOIN rooms r ON b.room_id = r.id"
                        . $date_where_clause . " ORDER BY b.id DESC LIMIT 5";
$stmt_recent = $conn->prepare($recent_bookings_sql);
if ($filter_checkin && $filter_checkout) $stmt_recent->bind_param($date_types, ...$date_params);
$stmt_recent->execute();
$recent_bookings = $stmt_recent->get_result();

// Thống kê trạng thái đặt phòng
$booking_stats_sql = "SELECT status, COUNT(*) as count " . $base_booking_query . $date_where_clause . " GROUP BY status";
$stmt_stats = $conn->prepare($booking_stats_sql);
if ($filter_checkin && $filter_checkout) $stmt_stats->bind_param($date_types, ...$date_params);
$stmt_stats->execute();
$booking_stats = $stmt_stats->get_result();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="includes/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .filter-form-label {
            color: var(--admin-accent, #d4af37);
        }
    </style>
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

            <!-- [DI CHUYỂN] Form lọc theo ngày xuống dưới khu vực thống kê -->
            <div class="card my-4">
                <div class="card-body">
                    <form method="get" class="row g-3 align-items-center">
                        <div class="col-md-4">
                            <label for="checkin" class="form-label filter-form-label">Thống kê từ ngày</label>
                            <input type="date" name="checkin" id="checkin" class="form-control" value="<?= htmlspecialchars($filter_checkin ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="checkout" class="form-label filter-form-label">Đến ngày</label>
                            <input type="date" name="checkout" id="checkout" class="form-control" value="<?= htmlspecialchars($filter_checkout ?? '') ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">📊 Lọc</button>
                            <?php if ($filter_checkin && $filter_checkout): ?>
                                <a href="dashboard.php" class="btn btn-secondary">Xóa bộ lọc</a>
                            <?php endif; ?>
                        </div>
                    </form>
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
