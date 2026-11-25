<?php
require_once '../config/db.php';

$loi = '';
$upload_dir = dirname(__DIR__) . '/assets/images/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten_dc = trim($_POST['ten_dc'] ?? '');
    $loai_dc = trim($_POST['loai_dc'] ?? '');
    $don_vi_tinh = trim($_POST['don_vi_tinh'] ?? '');
    $so_luong_tong = max(0, (int)($_POST['so_luong_tong'] ?? 0));
    $so_luong_kha_dung = max(0, (int)($_POST['so_luong_kha_dung'] ?? $so_luong_tong));
    $tinh_trang = trim($_POST['tinh_trang'] ?? 'Tốt');
    $hinh_anh = null;

    if ($so_luong_kha_dung > $so_luong_tong) {
        $so_luong_kha_dung = $so_luong_tong;
    }

    if ($ten_dc === '' || $loai_dc === '' || $don_vi_tinh === '') {
        $loi = 'Vui lòng nhập đầy đủ Tên dụng cụ, Loại dụng cụ và Đơn vị tính.';
    }

    if ($loi === '' && isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['hinh_anh']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['hinh_anh']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowed, true)) {
                $loi = 'Chỉ cho phép tải file ảnh (jpg, jpeg, png, gif, webp).';
            } else {
                $ten_file = 'dc_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['hinh_anh']['tmp_name'], $upload_dir . $ten_file)) {
                    $hinh_anh = $ten_file;
                } else {
                    $loi = 'Không thể lưu ảnh vào thư mục src/assets/images/.';
                }
            }
        } else {
            $loi = 'Lỗi tải file ảnh (mã lỗi: ' . (int)$_FILES['hinh_anh']['error'] . ').';
        }
    }

    if ($loi === '') {
        $sql = "INSERT INTO dung_cu (ten_dc, loai_dc, don_vi_tinh, so_luong_tong, so_luong_kha_dung, tinh_trang, hinh_anh)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssiiss", $ten_dc, $loai_dc, $don_vi_tinh, $so_luong_tong, $so_luong_kha_dung, $tinh_trang, $hinh_anh);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: index.php?msg=" . urlencode("Thêm dụng cụ mới thành công!") . "&type=success");
            exit;
        }
        $loi = 'Lỗi thêm dữ liệu: ' . mysqli_error($conn);
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm dụng cụ mới - Quản lý kho</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-text: #cbd5e1;
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --page-bg: #f8fafc;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: var(--page-bg);
            color: var(--text-main);
        }
        .app-layout { display: flex; min-height: 100vh; }
        .sidebar {
            width: 248px;
            flex-shrink: 0;
            background: var(--sidebar-bg);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .brand-box {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 20px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .brand-logo {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
        }
        .brand-title { font-size: 14px; font-weight: 700; line-height: 1.3; }
        .nav-section-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.06em;
            color: #64748b;
            padding: 18px 20px 8px;
        }
        .nav-list {
            list-style: none;
            margin: 0;
            padding: 0 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .nav-item a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        .nav-item a:hover { background: var(--sidebar-hover); color: #fff; }
        .nav-item a.active { background: var(--primary); color: #fff; font-weight: 600; }
        .sidebar-bottom { padding: 14px; border-top: 1px solid rgba(255, 255, 255, 0.08); }
        .warehouse-status-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 12px;
        }
        .warehouse-status-card .w-title { font-size: 13px; font-weight: 600; margin-bottom: 4px; }
        .warehouse-status-card .w-sub { font-size: 12px; color: #4ade80; }
        .main-wrapper { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .topbar {
            height: 60px;
            background: var(--card-bg);
            border-bottom: 1px solid var(--border);
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .breadcrumb { font-size: 13px; color: var(--text-muted); display: flex; align-items: center; gap: 8px; }
        .breadcrumb a { color: var(--text-muted); text-decoration: none; }
        .breadcrumb strong { color: var(--text-main); font-weight: 600; }
        .content-area { padding: 24px 28px; max-width: 820px; width: 100%; margin: 0 auto; }
        .panel-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
        }
        .panel-card h1 { margin: 0; font-size: 22px; font-weight: 700; }
        .panel-card p.sub { margin: 6px 0 20px; font-size: 14px; color: var(--text-muted); }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }
        label { font-size: 13px; font-weight: 600; color: var(--text-main); }
        .control {
            height: 40px;
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            color: var(--text-main);
            background: #fff;
        }
        .control[type="file"] { height: auto; }
        .control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        .alert-danger {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .actions { display: flex; gap: 10px; margin-top: 22px; }
        .btn {
            height: 40px;
            padding: 0 18px;
            border-radius: 8px;
            border: 1px solid transparent;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary { background: #fff; color: var(--text-main); border-color: var(--border); }
        @media (max-width: 860px) { .sidebar { display: none; } .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="app-layout">
    <aside class="sidebar">
        <div>
            <div class="brand-box">
                <div class="brand-logo">⚡</div>
                <div class="brand-title">Hệ thống Quản lý Kho dụng cụ Thể thao</div>
            </div>
            <div class="nav-section-label">KHÔNG GIAN QUẢN TRỊ</div>
            <ul class="nav-list">
                <li class="nav-item"><a href="../index.php"><span>⊞</span><span>Trang chủ</span></a></li>
                <li class="nav-item"><a href="../auth/index.php"><span>👥</span><span>Quản lý tài khoản</span></a></li>
                <li class="nav-item"><a href="index.php" class="active"><span>📦</span><span>Quản lý kho</span></a></li>
                <li class="nav-item"><a href="../muontra/index.php"><span>✓</span><span>Duyệt phiếu</span></a></li>
                <li class="nav-item"><a href="../muontra/bangiao.php"><span>⚠</span><span>Quản lý bàn giao/vi phạm</span></a></li>
            </ul>
        </div>
        <div class="sidebar-bottom">
            <div class="warehouse-status-card">
                <div class="w-title">Kho dụng cụ thể thao</div>
                <div class="w-sub">● Hệ thống đang hoạt động</div>
            </div>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="topbar">
            <div class="breadcrumb">
                <span>🏠</span>
                <span>/</span>
                <a href="index.php">Quản lý kho</a>
                <span>/</span>
                <strong>Thêm dụng cụ</strong>
            </div>
        </header>

        <main class="content-area">
            <section class="panel-card">
                <h1>Thêm dụng cụ mới</h1>
                <p class="sub">Nhập thông tin thiết bị thể thao và tải ảnh lên thư mục <code>src/assets/images/</code>.</p>

                <?php if ($loi !== ''): ?>
                    <div class="alert-danger"><?= htmlspecialchars($loi) ?></div>
                <?php endif; ?>

                <form method="POST" action="them.php" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Tên dụng cụ *</label>
                            <input type="text" name="ten_dc" class="control" required value="<?= htmlspecialchars($_POST['ten_dc'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Loại dụng cụ *</label>
                            <input type="text" name="loai_dc" class="control" required placeholder="VD: Bóng đá, Cầu lông..." value="<?= htmlspecialchars($_POST['loai_dc'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Đơn vị tính *</label>
                            <input type="text" name="don_vi_tinh" class="control" required placeholder="VD: Quả, Chiếc, Tấm..." value="<?= htmlspecialchars($_POST['don_vi_tinh'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Tình trạng</label>
                            <?php $tt = $_POST['tinh_trang'] ?? 'Tốt'; ?>
                            <select name="tinh_trang" class="control">
                                <option value="Mới" <?= $tt === 'Mới' ? 'selected' : '' ?>>Mới</option>
                                <option value="Tốt" <?= $tt === 'Tốt' ? 'selected' : '' ?>>Tốt</option>
                                <option value="Bảo trì" <?= $tt === 'Bảo trì' ? 'selected' : '' ?>>Bảo trì</option>
                                <option value="Hư hỏng" <?= $tt === 'Hư hỏng' ? 'selected' : '' ?>>Hư hỏng</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tổng số lượng *</label>
                            <input type="number" name="so_luong_tong" class="control" min="0" required value="<?= (int)($_POST['so_luong_tong'] ?? 0) ?>">
                        </div>
                        <div class="form-group">
                            <label>Số lượng khả dụng *</label>
                            <input type="number" name="so_luong_kha_dung" class="control" min="0" required value="<?= (int)($_POST['so_luong_kha_dung'] ?? 0) ?>">
                        </div>
                        <div class="form-group full">
                            <label>Hình ảnh dụng cụ</label>
                            <input type="file" name="hinh_anh" class="control" accept="image/*">
                        </div>
                    </div>

                    <div class="actions">
                        <button type="submit" class="btn btn-primary">Lưu dụng cụ</button>
                        <a href="index.php" class="btn btn-secondary">Quay lại</a>
                    </div>
                </form>
            </section>
        </main>
    </div>
</div>
</body>
</html>