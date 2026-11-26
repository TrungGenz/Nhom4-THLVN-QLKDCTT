<?php
session_start();
require_once '../config/db.php';

// Nếu đã đăng nhập thì chuyển thẳng sang Dashboard
if (isset($_SESSION['ma_nd'])) {
    header("Location: dashboard.php");
    exit();
}

$alert_title = '';
$alert_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten_dang_nhap = trim($_POST['ten_dang_nhap'] ?? '');
    $mat_khau = trim($_POST['mat_khau'] ?? '');

    if ($ten_dang_nhap === '' || $mat_khau === '') {
        $alert_title = 'THIẾU THÔNG TIN ĐĂNG NHẬP';
        $alert_msg = 'Vui lòng nhập đầy đủ tài khoản và mật khẩu để tiếp tục sử dụng hệ thống.';
    } else {
        $stmt = $conn->prepare("SELECT ma_nd, ho_ten, ten_dang_nhap, mat_khau, vai_tro FROM nguoi_dung WHERE ten_dang_nhap = ? LIMIT 1");
        $stmt->bind_param("s", $ten_dang_nhap);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if ($mat_khau === $user['mat_khau'] || password_verify($mat_khau, $user['mat_khau'])) {
                $_SESSION['ma_nd'] = $user['ma_nd'];
                $_SESSION['ho_ten'] = $user['ho_ten'];
                $_SESSION['ten_dang_nhap'] = $user['ten_dang_nhap'];
                $_SESSION['vai_tro'] = $user['vai_tro'];

                $stmt->close();
                header("Location: dashboard.php");
                exit();
            }
        }

        $stmt->close();
        $alert_title = 'ĐĂNG NHẬP THẤT BẠI';
        $alert_msg = 'Tài khoản hoặc mật khẩu không chính xác. Vui lòng kiểm tra lại!';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập hệ thống - Quản lý Kho dụng cụ Thể thao</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-page">

    <div class="login-top-brand">
        <div class="brand-icon"><i class="fa-solid fa-bolt"></i></div>
        <div class="brand-text">
            Hệ thống Quản lý<br>
            Kho dụng cụ<br>
            Thể thao
        </div>
    </div>

    <div class="login-wrapper">
        <div class="login-card">
            <h1 class="login-title">Đăng nhập hệ thống</h1>
            <p class="login-sub">Nhập tài khoản và mật khẩu để tiếp tục sử dụng hệ thống.</p>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="ten_dang_nhap">Tài khoản</label>
                    <input type="text" id="ten_dang_nhap" name="ten_dang_nhap"
                           placeholder="Tên đăng nhập / Email"
                           value="<?php echo htmlspecialchars($_POST['ten_dang_nhap'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="mat_khau">Mật khẩu</label>
                    <input type="password" id="mat_khau" name="mat_khau"
                           placeholder="Nhập mật khẩu" required>
                </div>

                <button type="submit" class="btn-login">Đăng nhập</button>
            </form>
        </div>

        <?php if ($alert_msg !== ''): ?>
            <div class="login-alert-box" id="loginAlertBox">
                <div class="login-alert-title"><?php echo htmlspecialchars($alert_title, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="login-alert-msg"><?php echo htmlspecialchars($alert_msg, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close-alert" onclick="document.getElementById('loginAlertBox').style.display='none'">
                    Đóng
                </button>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>