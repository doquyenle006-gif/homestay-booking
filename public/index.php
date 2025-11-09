<?php
include_once(__DIR__ . '/../config/db.php');

// Xử lý tìm kiếm theo ngày và từ khóa
include_once(__DIR__ . '/room_search.php');
$errors = [];
$checkin = isset($_GET['checkin']) ? trim($_GET['checkin']) : '';
$checkout = isset($_GET['checkout']) ? trim($_GET['checkout']) : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// Logic phân trang
$records_per_page = 30;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;


if (isset($_GET['search'])) {
    // [THAY ĐỔI] Chỉ xác thực ngày khi người dùng nhập đủ cả hai
    if (!empty($checkin) && !empty($checkout)) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkin) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkout)) {
            $errors[] = "Định dạng ngày không hợp lệ.";
        } elseif (strtotime($checkin) >= strtotime($checkout)) {
            $errors[] = "Ngày nhận phòng phải trước ngày đi. Vui lòng chọn lại.";
        } elseif (strtotime($checkin) < strtotime(date('Y-m-d'))) {
            $errors[] = "Ngày nhận phòng không được là ngày trong quá khứ.";
        }
    }
}

// Lấy tổng số phòng để tính toán số trang
$total_rooms_result = null;
if (isset($_GET['search']) && empty($errors)) {
    // [THAY ĐỔI] Chọn hàm tìm kiếm phù hợp
    if (!empty($checkin) && !empty($checkout)) {
        // Nếu có đủ ngày, tìm phòng trống theo ngày và từ khóa
        $total_rooms_result = findAvailableRooms($conn, $checkin, $checkout, $keyword); // Lấy tất cả để đếm
        $rooms = findAvailableRooms($conn, $checkin, $checkout, $keyword, $records_per_page, $offset);
    } else {
        // Nếu không có ngày, chỉ tìm theo từ khóa
        $total_rooms_result = getAllAvailableRooms($conn, $keyword); // Lấy tất cả để đếm
        $rooms = getAllAvailableRooms($conn, $keyword, $records_per_page, $offset);
    }
} else {
    $total_rooms_result = getAllAvailableRooms($conn, $keyword); // Lấy tất cả để đếm
    $rooms = getAllAvailableRooms($conn, $keyword, $records_per_page, $offset);
}

$total_records = 0;
if ($total_rooms_result) {
    $total_records = $total_rooms_result->num_rows;
}
$total_pages = ceil($total_records / $records_per_page);

// Xây dựng query string cho các link phân trang
$query_params = $_GET;
unset($query_params['page']);
$pagination_query_string = http_build_query($query_params);

$page_title = 'Trang chủ - Homestay Sang Trọng';
ob_start();
?>
<style>
    :root {
        --primary-color: #007bff;
        --secondary-color: #343a40;
        --light-gray: #f8f9fa;
        --text-color: #495057;
        --border-radius: 12px;
        --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    /* Hero Section */
    .hero-section {
        position: relative;
        height: 55vh;
        /* Giảm chiều cao */
        min-height: 450px;
        /* Giảm chiều cao tối thiểu */
        background: url('../assets/img/Ảnh nền3.jpg') no-repeat center center/cover;
        /* Khôi phục ảnh nền */
        display: flex;
        align-items: center;
        /* Hoàn tác: căn giữa theo chiều dọc */
        justify-content: center;
        /* Hoàn tác: căn giữa theo chiều ngang */
        text-align: center;
        /* Hoàn tác: căn chữ ra giữa */
        color: #fff;
        overflow: hidden;
        margin-top: 0;
        /* Đẩy khung ảnh lên sát header */
    }

    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.4);
        /* Lớp phủ tối để làm nổi bật chữ */
    }

    .hero-content {
        position: relative;
        z-index: 2;
        animation: fadeInDown 1s ease-out;
    }

    .hero-content h1 {
        font-family: 'Playfair Display', serif;
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .hero-content p {
        font-size: 1.2rem;
        max-width: 600px;
        margin: 0 auto 2rem auto;
        /* Hoàn tác: căn giữa */
    }

    /* Search Box */
    .search-box {
        background: rgba(255, 255, 255, 0.9);
        padding: 0.5rem;
        /* Giảm padding để khung nhỏ lại */
        border-radius: 50px;
        /* Bo tròn nhiều hơn để tạo hình viên thuốc */
        box-shadow: var(--shadow);
        backdrop-filter: blur(10px);
        max-width: 850px;
        /* Giới hạn chiều rộng */
        margin: 0 auto;
        /* Hoàn tác: Căn giữa thanh tìm kiếm */
    }

    .search-box form {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .search-box input[type="text"],
    .search-box input[type="date"] {
        /* [THAY ĐỔI] Loại bỏ khung riêng */
        background: transparent;
        flex-grow: 1;
        color: #333;
        outline: none;
        /* Bỏ viền khi focus */
        border: none;
        padding: 0.25rem 1rem 0.5rem 1rem;
        /* Điều chỉnh padding cho label */
        width: 100%;
    }

    /* [THÊM] CSS cho label và input field */
    .search-box .input-field {
        display: flex;
        flex-direction: column;
        padding: 0.25rem 0 0.25rem 1rem;
        /* Thêm padding-left để xích chữ vào */
        flex: 1;
        min-width: 150px;
    }

    .search-box .input-field label,
    .search-box .input-field input {
        font-size: 0.75rem;
        font-weight: 600;
        color: #6c757d;
    }

    .search-box .input-field input::placeholder {
        color: #6c757d;
    }

    /* [THÊM] Dấu ngăn cách */
    .search-box .input-group {
        display: flex;
        align-items: center;
        flex-grow: 1;
    }

    .search-box .input-group>*:not(:last-child) {
        border-right: 1px solid #ddd;
    }

    .search-box .keyword-input {
        min-width: 200px;
    }

    .search-box button {
        background: var(--primary-color);
        padding: 0.75rem 1.5rem;
        /* Giảm padding để nút nhỏ lại */
        border-radius: 50%;
        /* Chuyển thành hình tròn */
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s ease;
        /* [THÊM] Điều chỉnh cho nút chỉ có icon */
        width: 48px;
        height: 48px;
        flex-shrink: 0;
        /* Ngăn nút bị co lại */
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .search-box button:hover {
        background: #0056b3;
    }

    /* [THÊM] Filter Tags Section */
    .filter-tags-section {
        /* Loại bỏ khung nền */
        padding: 0;
        background-color: transparent;
    }

    .filter-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        justify-content: flex-start;
        /* Căn các thẻ về bên trái */
    }

    .filter-tag {
        text-decoration: none;
        color: #495057;
        background-color: #fff;
        padding: 0.4rem 1.1rem;
        /* Giảm padding để thu gọn thẻ */
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.85rem;
        /* Giảm cỡ chữ */
        border: 1px solid #dee2e6;
        transition: all 0.3s ease;
    }

    .filter-tag:hover,
    .filter-tag.active {
        background-color: var(--primary-color);
        color: #fff;
        border-color: var(--primary-color);
    }

    /* Room List */
    .room-list-header {
        text-align: center;
        margin-bottom: 3rem;
        /* Tăng khoảng cách dưới tiêu đề */
    }

    .room-list-header h2 {
        font-family: 'Playfair Display', serif;
        font-size: 2.2rem;
        /* Giảm cỡ chữ cho nhỏ lại */
    }

    .room-list-header p {
        margin-top: -2rem;
        /* Dịch dòng mô tả lên trên nữa */
        color: #6c757d;
    }

    /* [THÊM] Đường kẻ ngang trang trí */
    .title-divider {
        border: none;
        height: 1px;
        background: linear-gradient(to right, transparent, #666, transparent);
        /* Đậm hơn */
        width: 120px;
        margin: 1.5rem auto 0 auto;
        /* Giảm margin-bottom để dịch chữ lên */
    }

    /* [THÊM] Đường kẻ ngăn cách section */
    .section-divider {
        border: none;
        height: 1px;
        background-color: #777;
        /* Đậm hơn */
    }

    .room-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 2rem;
    }

    .room-card {
        background: #fff;
        border-radius: var(--border-radius);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        /* [THÊM] Chuyển card thành flex container để các phần tử con có thể co giãn */
        display: flex;
        flex-direction: column;
    }

    .room-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    }

    .room-card img {
        width: 100%;
        height: 220px;
        /* Tăng chiều cao ảnh */
        object-fit: cover;
    }

    .room-info {
        padding: 1.25rem;
        /* Giảm khoảng đệm chung của phần thông tin */
        display: flex;
        flex-direction: column;
        /* Giữ nguyên hướng cột */
        /* [THÊM] Cho phép phần này phát triển để lấp đầy không gian trống */
        flex-grow: 1;
    }

    .room-info h3 {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--secondary-color);
        margin-bottom: 0.25rem;
    }

    .room-card-rating {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #6c757d;
    }

    .available-rooms-tag {
        background-color: #fff3cd;
        color: #664d03;
        padding: 0.3rem 0.8rem;
        border-radius: 6px;
        font-size: 0.85rem;
        margin-top: 0.75rem;
    }

    .room-info .price {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--primary-color);
        margin: 0.5rem 0 0.5rem 0;
        /* Giảm thêm khoảng cách dưới giá */
    }

    .room-info p {
        font-size: 0.95rem;
        line-height: 1.3;
        /* Giảm thêm khoảng cách dòng chữ */
        /* [THAY ĐỔI] Loại bỏ giới hạn chiều cao để nội dung linh hoạt */
        margin-bottom: 0.5rem;
        /* Giảm thêm khoảng cách dưới mô tả */
        color: #6c757d;
        /* Thêm màu cho chữ mô tả để dễ đọc hơn */
    }

    .room-amenities {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.5rem;
        /* Giảm khoảng cách trên tiện nghi */
        padding-top: 0.75rem;
        /* Giảm khoảng đệm trên tiện nghi */
        border-top: 1px solid #eee;
    }

    .amenity-tag {
        background-color: #e9ecef;
        color: #495057;
        padding: 0.25rem 0.6rem;
        border-radius: 20px;
        font-size: 0.8rem;
    }

    .room-actions {
        display: flex;
        gap: 0.75rem;
        padding-top: 0.75rem;
        /* Giảm khoảng đệm phía trên các nút */
        margin-top: auto;
        /* Đẩy phần này xuống dưới cùng */
    }

    .btn-booking,
    .btn-details {
        flex: 1;
        text-align: center;
        padding: 0.7rem 1rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-booking {
        background: var(--primary-color);
        color: #fff;
    }

    .btn-booking:hover {
        background: #0056b3;
        color: #fff;
    }

    .btn-details {
        background: #f1f3f5;
        color: #343a40;
        border: 1px solid #dee2e6;
    }

    .btn-details:hover {
        background: #e9ecef;
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 768px) {
        .hero-content h1 {
            font-size: 2.5rem;
        }

        .search-inputs {
            flex-direction: column;
        }

        .search-box .keyword-input {
            min-width: auto;
        }
    }
</style>
<?php
$extra_css = ob_get_clean();
include_once(__DIR__ . '/header.php');
?>
<main class="content-wrapper">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1>Homestay Sang Trọng</h1>
            <p>Không gian nghỉ dưỡng mang dấu ấn riêng – đẳng cấp và khác biệt</p>

            <!-- Search Box -->
            <div id="search" class="search-box">
                <form method="get" action="#room-list-section">
                    <div class="input-group">
                        <div class="input-field keyword-input">
                            <label style="text-align: left; padding-left: 16px;">Tên phòng</label>
                            <input type="text" name="keyword" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>"
                                placeholder="Nhập từ khóa...">
                        </div>
                        <div class="input-field">
                            <label style="text-align: left;padding-left: 14px;">Ngày nhận phòng</label>
                            <input type="date" id="checkin_date" name="checkin"
                                value="<?= htmlspecialchars($checkin) ?>" min="<?= date('Y-m-d') ?>"
                                title="Ngày nhận phòng">
                        </div>
                        <div class="input-field">
                            <label style="text-align: left; padding-left: 1rem;">Ngày trả phòng</label>
                            <input type="date" id="checkout_date" name="checkout"
                                value="<?= htmlspecialchars($checkout) ?>"
                                min="<?= date('Y-m-d', strtotime('+1 day')) ?>" title="Ngày trả phòng">
                        </div>
                    </div>
                    <button type="submit" name="search" title="Tìm phòng"><i class="fas fa-search"></i></button>
                </form>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger mt-3 p-2">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- [THÊM] Filter Tags Section -->
    <section class="filter-tags-section mt-5">
        <div class="filter-tags">
            <a href="#" class="filter-tag active">Tất cả</a>
            <a href="#" class="filter-tag">Sang trọng</a>
            <a href="#" class="filter-tag">Gần gũi thiên nhiên</a>
            <a href="#" class="filter-tag">Cổ điển</a>
            <a href="#" class="filter-tag">Hiện đại</a>
            <a href="#" class="filter-tag">Yên bình</a>
            <a href="#" class="filter-tag">Check-in đẹp</a>
        </div>
    </section>

    <!-- [THÊM] Đường kẻ ngăn cách -->
    <hr class="section-divider my-5">

    <!-- Room List Section -->
    <section id="room-list-section">
        <div class="room-list-header">
            <h2>💫 Không gian nghỉ dưỡng chạm đến cảm xúc</h2>
            <hr class="title-divider" style="margin-bottom: 0.4rem;">
            <hr class="title-divider">
            <p>🌻 Tận hưởng từng khoảnh khắc trong không gian của riêng bạn</p>
        </div>
        <div class="room-list">
            <?php if ($rooms && $rooms->num_rows > 0): ?>
                <?php while ($room = $rooms->fetch_assoc()): ?>
                    <div class="room-card">
                        <img src="../assets/img/<?= htmlspecialchars($room['img'] ?? 'default.jpg') ?>"
                            alt="<?= htmlspecialchars($room['room_name']) ?>">
                        <div class="room-info">
                            <h3><?= htmlspecialchars($room['room_name']) ?></h3>
                            <?php if (isset($room['available_quantity']) && $room['available_quantity'] < $room['quantity']): ?>
                                <p class="available-rooms-tag">
                                    Chỉ còn <strong><?= htmlspecialchars($room['available_quantity']) ?></strong> phòng trống
                                </p>
                            <?php endif; ?>
                            <p class="price"><?= number_format($room['price'], 0, ',', '.') ?> ₫ / đêm</p>
                            <div class="room-amenities">
                                <?php if ($room['has_wifi']): ?><span class="amenity-tag">📶 Wifi</span><?php endif; ?>
                                <?php if ($room['has_tv']): ?><span class="amenity-tag">📺 Tivi</span><?php endif; ?>
                                <?php if ($room['has_ac']): ?><span class="amenity-tag">❄️ Điều hòa</span><?php endif; ?>
                                <?php if ($room['has_heater']): ?><span class="amenity-tag">🔥 Bình nóng
                                        lạnh</span><?php endif; ?>
                                <?php if ($room['has_projector']): ?><span class="amenity-tag">🎬 Máy
                                        chiếu</span><?php endif; ?>
                            </div>
                            <div class="room-actions">
                                <?php
                                // Thêm ngày checkin/checkout vào link để trang booking.php có thể nhận
                                $booking_link = "booking.php?room_id={$room['id']}";
                                if (!empty($checkin) && !empty($checkout)) {
                                    $booking_link .= "&checkin={$checkin}&checkout={$checkout}";
                                }
                                ?>
                                <a href="room_details.php?id=<?= $room['id'] ?>" class="btn-details">Chi tiết</a> <a
                                    href="<?= $booking_link ?>" class="btn-booking">Đặt ngay</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state" style="text-align:center;width:100%;padding:18px;">
                    <?php if (isset($_GET['search']) && empty($errors)): ?>
                        <p>Rất tiếc, không có phòng trống cho khoảng thời gian bạn đã chọn. Vui lòng thử ngày khác.</p>
                    <?php else: ?>
                        <p>Chưa có phòng khả dụng để hiển thị.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-5 d-flex justify-content-center">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>&<?= $pagination_query_string ?>">Trước</a></li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&<?= $pagination_query_string ?>"><?= $i ?></a></li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>&<?= $pagination_query_string ?>">Sau</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>

    </section>
</main>

<?php
include_once(__DIR__ . '/footer.php');
?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkinInput = document.getElementById('checkin_date');
        const checkoutInput = document.getElementById('checkout_date');

        if (checkinInput && checkoutInput) {
            checkinInput.addEventListener('change', function () {
                if (this.value) {
                    // Ngày trả phòng phải sau ngày đến ít nhất 1 ngày
                    const checkinDate = new Date(this.value);
                    checkinDate.setDate(checkinDate.getDate() + 1);
                    const minCheckoutDate = checkinDate.toISOString().split('T')[0];
                    checkoutInput.min = minCheckoutDate;

                    // Nếu ngày đi hiện tại không hợp lệ (trước hoặc bằng ngày đến mới),
                    // tự động cập nhật nó thành ngày hợp lệ gần nhất (ngày hôm sau).
                    if (!checkoutInput.value || checkoutInput.value < minCheckoutDate) {
                        checkoutInput.value = minCheckoutDate;
                    }
                }
            });
        }
    });
</script>