<?php
session_start();
include_once(__DIR__ . "/../config/db.php");

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (isset($_POST['username'], $_POST['password'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']);

    $sql = "SELECT * FROM customers WHERE username='$username' AND password='$password'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $_SESSION['customer'] = $username;
        $response['success'] = true;
        $response['message'] = 'Đăng nhập thành công!';
    } else {
        $response['message'] = "Sai tài khoản hoặc mật khẩu!";
    }
} else {
    $response['message'] = "Vui lòng nhập đầy đủ thông tin!";
}

echo json_encode($response);
exit();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Homestay Sang Trọng</title>
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
            width: 380px;
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

        p {
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

        .register-link {
            margin-top: 15px;
            color: #f1c40f;
        }

        .register-link a {
            color: #f1c40f;
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
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
    <h2>🔐 Đăng nhập</h2>
    <form method="post">
        <input type="text" name="username" placeholder="Tài khoản" required>
        <input type="password" name="password" placeholder="Mật khẩu" required>
        <button type="submit" name="login">Đăng nhập</button>
    </form>
    <p><?= isset($error) ? $error : '' ?></p>
    <div class="register-link">
        Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
    </div>
    <a href="index.php" class="back-home">🏠 Về trang chính</a>
</div>
</body>
</html>