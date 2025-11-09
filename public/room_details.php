<?php
include_once(__DIR__ . '/../config/db.php');

$room_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($room_id <= 0) {
    header("Location: index.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT r.* FROM rooms r WHERE r.id = ?
");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Phòng không tồn tại, chuyển hướng về trang chủ
    header("Location: index.php");
    exit();
}

$room = $result->fetch_assoc();
$stmt->close();

$page_title = 'Chi tiết phòng: ' . htmlspecialchars($room['room_name']);
ob_start();
?>
<style>
    .room-detail-container {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        gap: 2.5rem;
        margin-top: 2rem;
    }
    .room-image-main img {
        width: 100%;
        height: auto;
        max-height: 500px;
        object-fit: cover;
        border-radius: var(--border-radius, 12px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
    .room-content h1 {
        font-family: 'Playfair Display', serif;
        font-size: 2.8rem;
        margin-bottom: 1rem;
    }
    .room-price {
        font-size: 1.8rem;
        font-weight: 600;
        color: var(--primary-color, #007bff);
        margin-bottom: 1.5rem;
    }
    .room-description {
        font-size: 1rem;
        line-height: 1.7;
        color: var(--color-text-light);
        margin-bottom: 2rem;
    }
    .amenities-section h3 {
        font-size: 1.5rem;
        font-weight: 600;
        border-bottom: 2px solid #eee;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
    }
    .amenities-list {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        list-style: none;
        padding: 0;
    }
    .amenities-list li {
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .btn-book-now {
        display: block;
        width: 100%;
        padding: 1rem;
        font-size: 1.2rem;
        font-weight: 600;
        text-align: center;
        background: var(--primary-color, #007bff);
        color: #fff;
        border-radius: 8px;
        text-decoration: none;
        margin-top: 2rem;
        transition: background 0.3s ease;
    }
    .btn-book-now:hover {
        background: #0056b3;
        color: #fff;
    }
    @media (max-width: 992px) {
        .room-detail-container {
            grid-template-columns: 1fr;
        }
    }
</style>
<?php
$extra_css = ob_get_clean();
include_once(__DIR__ . '/header.php');
?>

<main class="content-wrapper">
    <div class="room-detail-container">
        <div class="room-image-main">
            <img src="../assets/img/<?= htmlspecialchars($room['img'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($room['room_name']) ?>">
        </div>
        <div class="room-content">
            <h1><?= htmlspecialchars($room['room_name']) ?></h1>
            <div class="room-price"><?= number_format($room['price'], 0, ',', '.') ?> ₫ / đêm</div>
            <div class="room-description"> <!-- Mô tả đã là tiếng Việt -->
                <p><?= nl2br(htmlspecialchars($room['description'] ?? 'Không có mô tả cho phòng này.')) ?></p>
            </div>
            <div class="amenities-section">
                <h3>Tiện nghi</h3>
                <ul class="amenities-list">
                    <?php if($room['has_wifi']): ?><li>📶 Wifi tốc độ cao</li><?php endif; ?>
                    <?php if($room['has_tv']): ?><li>📺 Tivi màn hình phẳng</li><?php endif; ?>
                    <?php if($room['has_ac']): ?><li>❄️ Điều hòa không khí</li><?php endif; ?>
                    <?php if($room['has_heater']): ?><li>🔥 Hệ thống nước nóng</li><?php endif; ?>
                    <?php if($room['has_projector']): ?><li>🎬 Máy chiếu phim</li><?php endif; ?>
                </ul>
            </div>
            <a href="booking.php?room_id=<?= $room['id'] ?>" class="btn-book-now">Đặt Ngay</a>
        </div> 
    </div>
</main>

<?php
include_once(__DIR__ . '/footer.php');
?>