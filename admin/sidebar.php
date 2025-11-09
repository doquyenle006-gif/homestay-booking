<?php
// Lấy tên tệp hiện tại để làm nổi bật (active) menu
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <h4>🏡 HOMESTAY ADMIN</h4>
    <div>
        <a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
            📊 Thống kê
        </a>
        <a href="rooms.php" class="<?= in_array($current_page, ['rooms.php', 'add_room.php']) ? 'active' : '' ?>">
            🛏️ Quản lý phòng
        </a>
        <a href="bookings.php" class="<?= ($current_page == 'bookings.php') ? 'active' : '' ?>">
            📅 Quản lý đặt phòng
        </a>
        <a href="customers.php" class="<?= ($current_page == 'customers.php') ? 'active' : '' ?>">
            👥 Quản lý khách hàng
        </a>
        <a href="../public/index.php">
            🏠 Về trang chủ
        </a>
    </div>
    <a href="logout.php" class="logout-link">
        🔒 Đăng xuất
    </a>
</aside>