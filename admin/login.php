<?php
session_start();
include("../config/db.php");

if (isset($_POST['username'], $_POST['password'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']);

    // 1. Thử đăng nhập với vai trò Admin
    $sql_admin = "SELECT * FROM admins WHERE username='$username' AND password='$password'";
    $result_admin = $conn->query($sql_admin);
    if ($result_admin && $result_admin->num_rows > 0) {
        $_SESSION['admin'] = $username;
        header("Location: dashboard.php");
        exit();
    }
    // 2. Nếu đăng nhập admin thất bại
    else {
        $error = "Sai tài khoản hoặc mật khẩu!";
    }
} else if (isset($_POST['login'])) {
    $error = "Vui lòng nhập đầy đủ thông tin!";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');

        body {
            font-family: "Poppins", sans-serif;
            background: url('../assets/img/Ảnh nền3.jpg') no-repeat center center/cover;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            position: relative; /* Cần thiết cho lớp phủ */
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5); /* Lớp phủ tối */
            z-index: 1; /* Đặt lớp phủ trên ảnh nền */
        }

        .container {
            background: rgba(15, 15, 15, 0.85); /* Nền form tối hơn và mờ hơn */
            border-radius: 18px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
            padding: 40px 50px;
            width: 380px;
            text-align: center;
            border: 1px solid rgba(255, 215, 0, 0.25);
            animation: fadeIn 0.7s ease-in-out;
            position: relative; /* Đảm bảo form nằm trên lớp phủ */
            z-index: 2;
        }

        h2 {
            color: #f1c40f;
            margin-bottom: 20px;
            font-weight: 600;
            letter-spacing: 1px;
        }

        input, select {
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

        input:focus, select:focus {
            border-color: #f1c40f;
            box-shadow: 0 0 8px rgba(241, 196, 15, 0.5);
            outline: none;
            background: rgba(255, 255, 255, 0.1);
        }

        select option {
            background-color: #1e1e1e;
            color: #fff;
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
        <input type="text" name="username" placeholder="Tài khoản" required><br>
        <input type="password" name="password" placeholder="Mật khẩu" required><br>
        <button type="submit" name="login">Đăng nhập</button>
    </form>
    <p><?= isset($error) ? $error : '' ?></p>
    <a href="../public/index.php" class="back-home">🏠 Về trang chính</a>
</div>
</body>
</html>
