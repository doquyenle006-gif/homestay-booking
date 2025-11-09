<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include("../config/db.php");

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Hàm xử lý upload ảnh (tương tự file rooms.php)
function handleImageUpload($file_input_name) {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
        $target_dir = "../assets/img/";
        $img_name = uniqid() . '_' . basename($_FILES[$file_input_name]["name"]);
        $target_file = $target_dir . $img_name;
        if (move_uploaded_file($_FILES[$file_input_name]["tmp_name"], $target_file)) {
            return $img_name;
        }
    }
    return ''; // Trả về chuỗi rỗng nếu không có ảnh hoặc upload lỗi
}

// Xử lý Thêm phòng
if (isset($_POST['add'])) {
    $room_name = $_POST['room_name'];
    $price = $_POST['price'];
    $quantity = (int)$_POST['quantity'];
    $description = $_POST['description'];
    $img_name = handleImageUpload('room_img');
    
    // Xử lý tiện nghi
    $amenities = $_POST['amenities'] ?? [];
    $has_wifi = isset($amenities['wifi']) ? 1 : 0;
    $has_tv = isset($amenities['tv']) ? 1 : 0;
    $has_ac = isset($amenities['ac']) ? 1 : 0;
    $has_heater = isset($amenities['heater']) ? 1 : 0;
    $has_projector = isset($amenities['projector']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO rooms (room_name, price, quantity, description, img, has_wifi, has_tv, has_ac, has_heater, has_projector) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdisssiiii", $room_name, $price, $quantity, $description, $img_name, $has_wifi, $has_tv, $has_ac, $has_heater, $has_projector);
    $stmt->execute();
    header("Location: rooms.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Thêm phòng mới</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="includes/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="admin-area">
    <div class="layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="main-panel">
            <h2>Thêm phòng mới</h2>
            
            <div class="card mt-4">
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Tên phòng</label>
                            <input type="text" name="room_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Giá</label>
                            <input type="number" name="price" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số lượng phòng</label>
                            <input type="number" name="quantity" class="form-control" required value="1" min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tiện nghi</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="add_wifi" name="amenities[wifi]" value="1">
                                    <label class="form-check-label" for="add_wifi">📶 Wifi</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="add_tv" name="amenities[tv]" value="1">
                                    <label class="form-check-label" for="add_tv">📺 Tivi</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="add_ac" name="amenities[ac]" value="1">
                                    <label class="form-check-label" for="add_ac">❄️ Điều hòa</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="add_heater" name="amenities[heater]" value="1">
                                    <label class="form-check-label" for="add_heater">🔥 Nước nóng</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="add_projector" name="amenities[projector]" value="1">
                                    <label class="form-check-label" for="add_projector">🎬 Máy chiếu</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ảnh phòng</label>
                            <input type="file" name="room_img" id="add_room_img" class="form-control" accept="image/*">
                            <img id="add_img_preview" src="#" alt="Xem trước ảnh" class="img-preview mt-2" />
                        </div>
                        <button type="submit" name="add" class="btn btn-success">Lưu phòng</button>
                        <a href="rooms.php" class="btn btn-secondary">Hủy</a>
                    </form>
                </div>
            </div>
        </div> <!-- end main-panel -->
    </div> <!-- end layout -->
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const imgInput = document.getElementById('add_room_img');
    const imgPreview = document.getElementById('add_img_preview');

    imgInput.addEventListener('change', function(event) {
        if (event.target.files && event.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imgPreview.src = e.target.result;
                imgPreview.style.display = 'block';
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    });
});
</script>
</body>
</html>