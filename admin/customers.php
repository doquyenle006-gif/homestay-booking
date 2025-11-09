<?php
session_start();
include("../config/db.php");

// Nếu chưa đăng nhập thì quay lại login
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Xử lý Cập nhật khách hàng
if (isset($_POST['update'])) {
    $id = $_POST['edit_id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("UPDATE customers SET name = ?, phone = ?, email = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("sssi", $name, $phone, $email, $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Cập nhật thông tin khách hàng thành công!";
        } else {
            $_SESSION['error_message'] = "Lỗi khi cập nhật: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Lỗi chuẩn bị câu lệnh: " . $conn->error;
    }
    header("Location: customers.php");
    exit();
}

// Xử lý tìm kiếm
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

// [THÊM] Logic phân trang
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Lấy tổng số khách hàng
$total_sql = "SELECT COUNT(*) as total FROM customers";
if (!empty($search_keyword)) {
    $total_sql .= " WHERE (name REGEXP ? OR phone LIKE ? OR email LIKE ?)";
    $stmt_total = $conn->prepare($total_sql);
    $search_param_total = "[[:<:]]" . $conn->real_escape_string($search_keyword) . "[[:>:]]";
    $stmt_total->bind_param("sss", $search_param_total, $search_param_total, $search_param_total);
    $stmt_total->execute();
    $total_records = $stmt_total->get_result()->fetch_assoc()['total'];
    $stmt_total->close();
} else {
    $total_records = $conn->query($total_sql)->fetch_assoc()['total'];
}
$total_pages = ceil($total_records / $records_per_page);

// Cập nhật câu lệnh SQL chính để lấy dữ liệu theo trang
$sql = "SELECT * FROM customers";
if (!empty($search_keyword)) {
    $sql .= " WHERE (name REGEXP ? OR phone LIKE ? OR email LIKE ?)";
    $sql .= " ORDER BY id DESC LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $search_param = "[[:<:]]" . $conn->real_escape_string($search_keyword) . "[[:>:]]";
        $stmt->bind_param("sssii", $search_param, $search_param, $search_param, $offset, $records_per_page);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $_SESSION['error_message'] = "Lỗi truy vấn tìm kiếm: " . $conn->error;
        $result = false;
    }
} else {
    $sql .= " ORDER BY id DESC LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $offset, $records_per_page);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quản lý khách hàng</title>
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
                    <?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
    <h2>Danh sách khách hàng</h2>
    
    <!-- Search Form -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-center">
                <div class="col-md-8">
                    <input type="text" name="search" value="<?= htmlspecialchars($search_keyword) ?>" class="form-control" placeholder="Tìm kiếm theo tên, số điện thoại hoặc email...">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary me-2">🔍 Tìm kiếm</button>
                    <?php if (!empty($search_keyword)): ?>
                        <a href="customers.php" class="btn btn-secondary">Xóa tìm kiếm</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ tên</th>
                        <th>Số điện thoại</th>
                        <th>Email</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td>
                            <div style="display:flex;gap:8px;align-items:center;">
                                <button type="button" class="icon-btn btn-edit-customer"
                                    data-id="<?= $row['id'] ?>"
                                    data-name="<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>"
                                    data-phone="<?= htmlspecialchars($row['phone'], ENT_QUOTES) ?>"
                                    data-email="<?= htmlspecialchars($row['email'], ENT_QUOTES) ?>">✏️ <span>Sửa</span></button>
                                <button type="button" class="icon-btn btn-delete-customer" data-id="<?= $row['id'] ?>">🗑️ <span>Xóa</span></button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
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
    <div id="editCustomerModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chỉnh sửa thông tin khách hàng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" id="editFormContent">
                        <input type="hidden" name="edit_id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Họ tên</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="update" form="editFormContent" class="btn btn-primary">Cập nhật</button>
                </div>
            </div>
        </div>
    </div>

        </div> <!-- end main-panel -->
    </div> <!-- end layout -->
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Initialize customer functions - no need to clear other functions
</script>
<script>
// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if customer modal element exists
    const editCustomerModalElement = document.getElementById('editCustomerModal');
    
    if (!editCustomerModalElement) {
        console.error('Edit modal element not found!');
        return;
    }

    // Edit Modal Logic for Customers
    const editModalCustomer = new bootstrap.Modal(editCustomerModalElement);
    
    // Event delegation for dynamic content
    document.querySelector('.main-panel').addEventListener('click', function(event) {
        const editButton = event.target.closest('.btn-edit-customer');
        if (editButton) {
            const id = editButton.getAttribute('data-id');
            const name = editButton.getAttribute('data-name');
            const phone = editButton.getAttribute('data-phone');
            const email = editButton.getAttribute('data-email');

            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_phone').value = phone;
            document.getElementById('edit_email').value = email;

            editModalCustomer.show();
        }

        const deleteButton = event.target.closest('.btn-delete-customer');
        if (deleteButton) {
            const id = deleteButton.getAttribute('data-id');
            if (confirm('Bạn có chắc chắn muốn xóa khách hàng này không? Hành động này không thể hoàn tác.')) {
                window.location.href = 'delete_customer.php?id=' + id;
            }
        }
    });
});
</script>
</body>
</html>
