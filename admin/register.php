    <?php
    session_start();
    include("../config/db.php");
    $success = $error = "";
    if (isset($_POST['name'], $_POST['username'], $_POST['email'], $_POST['password'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $username = $conn->real_escape_string($_POST['username']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = $conn->real_escape_string($_POST['password']);
        
        // Mặc định đăng ký là khách hàng
        $sql = "INSERT INTO customers (name, username, email, password) VALUES ('$name', '$username', '$email', '$password')";
        if ($conn->query($sql)) {
            $success = 'Đăng ký tài khoản khách hàng thành công!';
        } else {
            $error = 'Đăng ký thất bại! Tên đăng nhập hoặc email có thể đã tồn tại.';
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Đăng ký</title>
        <link rel="stylesheet" href="../assets/style.css">
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
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
        </style>
    </head>
    <body>
    <div class="container mt-5">
    <div class="card shadow p-4">
        <h2 class="text-center mb-4">📝 Đăng ký tài khoản</h2>
        <!-- Form như cũ, nhưng thêm class Bootstrap -->
        <form method="post" class="needs-validation" novalidate>
            <div class="mb-3">
                <input type="text" name="name" class="form-control" placeholder="Họ tên" required>
            </div>
            <div class="mb-3">
                <input type="text" name="username" class="form-control" placeholder="Tên đăng nhập" required>
            </div>
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Mật khẩu" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Đăng ký</button>
        </form>
        <?php if ($success) echo '<div class="alert alert-success mt-3">' . $success . '</div>'; ?>
        <?php if ($error) echo '<div class="alert alert-danger mt-3">' . $error . '</div>'; ?>
        <a href="../public/index.php" class="btn btn-link mt-3">🏠 Về trang chính</a>
    </div>
</div>
    </div>
    </body>
    </html>
