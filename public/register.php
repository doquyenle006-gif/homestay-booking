<?php
session_start();
include_once(__DIR__ . "/../config/db.php");

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (isset($_POST['name'], $_POST['username'], $_POST['email'], $_POST['password'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $conn->real_escape_string($_POST['password']);

    // Check if username already exists
    $check_sql = "SELECT id FROM customers WHERE username = '$username'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result && $check_result->num_rows > 0) {
        $response['message'] = 'Tên đăng nhập đã tồn tại!';
    } else {
        $sql = "INSERT INTO customers (name, username, email, password) VALUES ('$name', '$username', '$email', '$password')";
        if ($conn->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Đăng ký tài khoản thành công! Bạn có thể đăng nhập ngay.';
        } else {
            $response['message'] = 'Đăng ký thất bại! Vui lòng thử lại.';
        }
    }
} else {
    $response['message'] = 'Vui lòng nhập đầy đủ thông tin!';
}

echo json_encode($response);
exit();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Homestay Sang Trọng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');

        body {
            font-family: "Poppins", sans-serif;
            background: linear-gradient(135deg, #0f0f0f, #1e1e1e);
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background: rgba(25, 25, 25, 0.95);
            border-radius: 18px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
            padding: 40px 50px;
            width: 400px;
            text-align: center;
            border: 1px solid rgba(255, 215, 0, 0.25);
            animation: fadeIn 0.7s ease-in-out;
        }

        h2 {
            color: #f1c40f;
            margin-bottom: 20px;
            font-weight: 600;
            letter-spacing: 1px;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            margin: 10px 0;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 8px;
            font-size: 14px;
            color: #fff;
            transition: all 0.3s ease;
        }

        input:focus {
            border-color: #f1c40f;
            box-shadow: 0 0 8px rgba(241, 196, 15, 0.5);
            outline: none;
            background: rgba(255, 255, 255, 0.1);
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #f1c40f, #d4af37);
            color: #000;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-top: 15px;
            transition: all 0.3s ease;
        }

        button:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(241, 196, 15, 0.4);
        }

        p.success {
            margin-top: 10px;
            color: #27ae60;
            font-weight: 500;
        }

        p.error {
            margin-top: 10px;
            color: #e74c3c;
            font-weight: 500;
        }

        .back-home {
            display: inline-block;
            margin-top: 18px;
            color: #f1c40f;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s, transform 0.3s;
        }

        .back-home:hover {
            color: #fff;
            transform: translateY(-2px);
        }

        .login-link {
            margin-top: 15px;
            color: #f1c40f;
        }

        .login-link a {
            color: #f1c40f;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            color: #fff;
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>📝 Đăng ký</h2>
    <form method="post">
        <input type="text" name="name" placeholder="Họ và tên" required value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
        <input type="text" name="username" placeholder="Tên đăng nhập" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
        <input type="email" name="email" placeholder="Email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
        <input type="password" name="password" placeholder="Mật khẩu" required>
        <button type="submit">Đăng ký</button>
    </form>
    
    <?php if ($success): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    
    <div class="login-link">
        Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a>
    </div>
    <a href="index.php" class="back-home">🏠 Về trang chính</a>
</div>
</body>
</html>