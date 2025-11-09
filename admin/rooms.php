<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include("../config/db.php");

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Hàm xử lý upload ảnh
function handleImageUpload($file_input_name, $current_img = '')
{
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
        $target_dir = "../assets/img/";
        // Xóa ảnh cũ nếu có
        if ($current_img && file_exists($target_dir . $current_img)) {
            unlink($target_dir . $current_img);
        }
        // Tạo tên file duy nhất
        $img_name = uniqid() . '_' . basename($_FILES[$file_input_name]["name"]);
        $target_file = $target_dir . $img_name;
        if (move_uploaded_file($_FILES[$file_input_name]["tmp_name"], $target_file)) {
            return $img_name;
        }
    }
    return $current_img; // Trả về ảnh cũ nếu không có ảnh mới hoặc upload lỗi
}

// Xử lý Cập nhật phòng
if (isset($_POST['update'])) {
    $id = $_POST['edit_id'];
    $room_name = $_POST['room_name'];
    $price = $_POST['price'];
    $quantity = (int) $_POST['quantity'];
    $description = $_POST['description'];

    // Xử lý tiện nghi
    $amenities = $_POST['amenities'] ?? [];
    $has_wifi = isset($amenities['wifi']) ? 1 : 0;
    $has_tv = isset($amenities['tv']) ? 1 : 0;
    $has_ac = isset($amenities['ac']) ? 1 : 0;
    $has_heater = isset($amenities['heater']) ? 1 : 0;
    $has_projector = isset($amenities['projector']) ? 1 : 0;

    // Lấy tên ảnh hiện tại
    $stmt_img = $conn->prepare("SELECT img FROM rooms WHERE id = ?");
    $stmt_img->bind_param("i", $id);
    $stmt_img->execute();
    $current_img = $stmt_img->get_result()->fetch_assoc()['img'] ?? '';
    $stmt_img->close();

    $img_name = handleImageUpload('room_img', $current_img);

    $stmt = $conn->prepare("UPDATE rooms SET room_name = ?, price = ?, quantity = ?, description = ?, img = ?, has_wifi = ?, has_tv = ?, has_ac = ?, has_heater = ?, has_projector = ? WHERE id = ?");
    $stmt->bind_param("sdisssiiiii", $room_name, $price, $quantity, $description, $img_name, $has_wifi, $has_tv, $has_ac, $has_heater, $has_projector, $id);
    $stmt->execute();
    header("Location: rooms.php");
    exit();
}

// Xử lý Xóa toàn bộ
if (isset($_POST['clear_all'])) {
    // Cân nhắc: Có thể thêm xóa tất cả file ảnh trong thư mục assets/img
    $conn->query("DELETE FROM rooms");
    $conn->query("ALTER TABLE rooms AUTO_INCREMENT = 1");
    header("Location: rooms.php");
    exit();
}

// Xử lý tìm kiếm
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

// [THÊM] Logic phân trang
$records_per_page = 10; // Số phòng mỗi trang
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Lấy tổng số phòng để tính toán số trang
$total_sql = "SELECT COUNT(*) as total FROM rooms" . (!empty($search_keyword) ? " WHERE room_name LIKE ?" : "");
$stmt_total = $conn->prepare($total_sql);
if (!empty($search_keyword)) {
    $search_param_total = "%$search_keyword%";
    $stmt_total->bind_param("s", $search_param_total);
}
$stmt_total->execute();
$total_records = $stmt_total->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$stmt_total->close();

// Cập nhật câu lệnh SQL chính để lấy dữ liệu theo trang
$sql = "
    SELECT
        r.*,
        (SELECT COUNT(*)
         FROM bookings b
         WHERE b.room_id = r.id
           AND b.status IN ('pending', 'confirmed')
           AND CURDATE() >= b.checkin
           AND CURDATE() < b.checkout
        ) as booked_today
    FROM rooms r
    " . (!empty($search_keyword) ? "WHERE r.room_name LIKE ? " : "") . "
    ORDER BY r.id DESC
    LIMIT ?, ?
";

if (!empty($search_keyword)) {
    $stmt = $conn->prepare($sql);
    $search_param = "%$search_keyword%";
    $stmt->bind_param("sii", $search_param, $offset, $records_per_page);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $offset, $records_per_page);
    $stmt->execute();
    $result = $stmt->get_result();
}

// Hàm dịch trạng thái không còn cần thiết cho logic mới này
// function translate_room_status_to_vietnamese($status) { ... }
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Quản lý phòng</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="includes/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="admin-area">
        <div class="layout">
            <?php include __DIR__ . '/sidebar.php'; ?>
            <div class="main-panel">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['message']);
                        unset($_SESSION['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['error_message']);
                        unset($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <h2>Danh sách phòng</h2>

                <!-- Search Form -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="get" class="row g-3 align-items-center">
                            <div class="col-md-8">
                                <input type="text" name="search" value="<?= htmlspecialchars($search_keyword) ?>"
                                    class="form-control" placeholder="Tìm kiếm theo tên phòng">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary me-2">🔍 Tìm kiếm</button>
                                <?php if (!empty($search_keyword)): ?>
                                    <a href="rooms.php" class="btn btn-secondary">Xóa tìm kiếm</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <a href="add_room.php" class="btn btn-success">➕ Thêm phòng mới</a>
                    <!-- <form method="post"
                        onsubmit="return confirm('Bạn có chắc chắn muốn XÓA TẤT CẢ phòng không? Hành động này không thể hoàn tác.');">
                        <button type="submit" name="clear_all" class="btn btn-danger">Xóa toàn bộ dữ liệu</button>
                    </form> -->
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên phòng</th>
                                    <th>Giá</th>
                                    <th>Số lượng</th>
                                    <th>Ảnh</th>
                                    <th>Tình trạng</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()) { ?>
                                    <?php
                                // Tính toán số lượng phòng còn trống
                                $total_quantity = (int) ($row['quantity'] ?? 1);
                                $booked_count = (int) ($row['booked_today'] ?? 0);
                                $available_count = $total_quantity - $booked_count;

                                $status_text = "Còn " . $available_count . " phòng";
                                $status_class = 'text-success';
                                if ($available_count <= 0) {
                                    $status_text = "Hết phòng";
                                    $status_class = 'text-danger fw-bold';
                                }
                                    ?>
                                    <tr>
                                        <td><?= $row['id'] ?></td>
                                        <td><?= htmlspecialchars($row['room_name']) ?></td>
                                        <td><?= $row['price'] ?></td>
                                        <td><?= $row['quantity'] ?? 1 ?></td>

                                        <td><?php if ($row['img']) { ?><img src="../assets/img/<?= $row['img'] ?>" alt="Ảnh phòng" class="table-img" /><?php } ?></td>
                                        <td class="<?= $status_class ?>"><?= $status_text ?></td>
                                        <td>
                                            <div style="display:flex;gap:8px;align-items:center;">
                                                <button type="button" class="icon-btn btn-edit-room" data-id="<?= $row['id'] ?>"
                                                    data-name="<?= htmlspecialchars($row['room_name'], ENT_QUOTES) ?>"
                                                    data-price="<?= $row['price'] ?>"
                                                    data-quantity="<?= $row['quantity'] ?? 1 ?>"
                                                    data-desc="<?= htmlspecialchars($row['description'], ENT_QUOTES) ?>"
                                                    data-img="<?= $row['img'] ?>" data-amenities='<?= json_encode(['wifi' => $row['has_wifi'], 'tv' => $row['has_tv'], 'ac' => $row['has_ac'], 'heater' => $row['has_heater'], 'projector' => $row['has_projector']]) ?>'>✏️ <span>Sửa</span></button>
                                                <button type="button" class="icon-btn btn-delete-room" data-id="<?= $row['id'] ?>">🗑️ <span>Xóa</span></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
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

                <!-- Edit modal (shared) -->
                <div id="editRoomModal" class="modal fade" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Chỉnh sửa thông tin phòng</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post" enctype="multipart/form-data" id="editFormContent">
                                    <input type="hidden" name="edit_id" id="edit_id">
                                    <div class="mb-3"><label class="form-label">Tên phòng</label><input type="text"
                                            name="room_name" id="edit_room_name" class="form-control" required></div>
                                    <div class="mb-3"><label class="form-label">Giá</label><input type="number"
                                            name="price" id="edit_price" class="form-control" required></div>
                                    <div class="mb-3"><label class="form-label">Số lượng</label><input type="number"
                                            name="quantity" id="edit_quantity" class="form-control" required min="1">
                                    </div>
                                    <div class="mb-3"><label class="form-label">Mô tả</label><textarea
                                            name="description" id="edit_description" class="form-control"
                                            rows="4"></textarea></div>
                                    <div class="mb-3">
                                        <label class="form-label">Tiện nghi</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="edit_wifi"
                                                    name="amenities[wifi]" value="1">
                                                <label class="form-check-label" for="edit_wifi">📶 Wifi</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="edit_tv"
                                                    name="amenities[tv]" value="1">
                                                <label class="form-check-label" for="edit_tv">📺 Tivi</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="edit_ac"
                                                    name="amenities[ac]" value="1">
                                                <label class="form-check-label" for="edit_ac">❄️ Điều hòa</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="edit_heater"
                                                    name="amenities[heater]" value="1">
                                                <label class="form-check-label" for="edit_heater">🔥 Nước nóng</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="edit_projector"
                                                    name="amenities[projector]" value="1">
                                                <label class="form-check-label" for="edit_projector">🎬 Máy
                                                    chiếu</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Ảnh hiện tại</label><br>
                                        <img id="edit_img_preview" src="" class="img-preview" />
                                    </div>
                                    <div class="mb-3"><label class="form-label">Tải ảnh mới (nếu muốn thay
                                            đổi)</label><input type="file" name="room_img" class="form-control"
                                            accept="image/*"></div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                <button type="submit" name="update" form="editFormContent" class="btn btn-primary">Cập
                                    nhật</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div> <!-- end main-panel -->
        </div> <!-- end layout -->
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize room functions - no need to clear other functions
    </script>
    <script>
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function () {
            // Check if room modal element exists
            const editRoomModalElement = document.getElementById('editRoomModal');

            if (!editRoomModalElement) {
                console.error('Edit modal element not found!');
                return;
            }

            // Edit Modal Logic for Rooms
            const editModalRoom = new bootstrap.Modal(editRoomModalElement);

            // Event delegation for dynamic content
            document.querySelector('.main-panel').addEventListener('click', function(event) {
                const editButton = event.target.closest('.btn-edit-room');
                if (editButton) {
                    const id = editButton.getAttribute('data-id');
                    const name = editButton.getAttribute('data-name');
                    const price = editButton.getAttribute('data-price');
                    const quantity = editButton.getAttribute('data-quantity');
                    const desc = editButton.getAttribute('data-desc');
                    const img = editButton.getAttribute('data-img');
                    const amenities = JSON.parse(editButton.getAttribute('data-amenities'));

                    document.getElementById('edit_id').value = id;
                    document.getElementById('edit_room_name').value = name;
                    document.getElementById('edit_price').value = price;
                    document.getElementById('edit_quantity').value = quantity;
                    document.getElementById('edit_description').value = desc;

                    document.getElementById('edit_wifi').checked = amenities.wifi == 1;
                    document.getElementById('edit_tv').checked = amenities.tv == 1;
                    document.getElementById('edit_ac').checked = amenities.ac == 1;
                    document.getElementById('edit_heater').checked = amenities.heater == 1;
                    document.getElementById('edit_projector').checked = amenities.projector == 1;

                    const preview = document.getElementById('edit_img_preview');
                    if (img) {
                        preview.src = '../assets/img/' + img;
                        preview.style.display = 'block';
                    } else {
                        preview.style.display = 'none';
                    }
                    editModalRoom.show();
                }

                const deleteButton = event.target.closest('.btn-delete-room');
                if (deleteButton) {
                    const id = deleteButton.getAttribute('data-id');
                    if (confirm('Bạn có chắc chắn muốn xóa phòng này không? Hành động này không thể hoàn tác.')) {
                        window.location.href = 'delete_room.php?id=' + id;
                    }
                }
            });
        });
    </script>
</body>

</html>