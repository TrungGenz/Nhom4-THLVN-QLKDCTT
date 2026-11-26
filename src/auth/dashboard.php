<?php
session_start();
require_once '../config/db.php';

// Chặn truy cập nếu chưa đăng nhập
if (!isset($_SESSION['ma_nd'])) {
    header("Location: login.php");
    exit();
}

$ma_nd = (int)$_SESSION['ma_nd'];
$ho_ten = $_SESSION['ho_ten'];
$vai_tro = $_SESSION['vai_tro']; // 'admin', 'thukho', 'nguoimuon'

$ten_vai_tro = match ($vai_tro) {
    'admin' => 'Quản trị viên',
    'thukho' => 'Thủ kho',
    default => 'Sinh viên',
};

$words = preg_split('/\s+/', trim($ho_ten));
if (count($words) >= 2) {
    $initials = mb_strtoupper(mb_substr($words[count($words) - 2], 0, 1) . mb_substr($words[count($words) - 1], 0, 1));
} else {
    $initials = mb_strtoupper(mb_substr($ho_ten, 0, 2));
}

$thu_vn = [
    'Sunday' => 'Chủ Nhật', 'Monday' => 'Thứ Hai', 'Tuesday' => 'Thứ Ba',
    'Wednesday' => 'Thứ Tư', 'Thursday' => 'Thứ Năm', 'Friday' => 'Thứ Sáu', 'Saturday' => 'Thứ Bảy'
];
$ngay_hien_tai = $thu_vn[date('l')] . ', ' . date('d/m/Y');

// 1. Lấy Tổng dụng cụ từ bảng dung_cu
$res_dc = $conn->query("SELECT COALESCE(SUM(so_luong_tong), 0) AS tong_dc, COALESCE(SUM(so_luong_kha_dung), 0) AS kha_dung FROM dung_cu")->fetch_assoc();
$tong_dung_cu = (int)$res_dc['tong_dc'];
$kha_dung = (int)$res_dc['kha_dung'];

// 2. Lấy Số phiếu đang mượn & Phiếu quá hạn từ bảng phieu_muon
if ($vai_tro === 'nguoimuon') {
    $stmt_pm = $conn->prepare("
        SELECT
            SUM(CASE WHEN trang_thai = 'Đang mượn' THEN 1 ELSE 0 END) AS dang_muon,
            SUM(CASE WHEN trang_thai = 'Quá hạn' OR (trang_thai = 'Đang mượn' AND han_tra < NOW()) THEN 1 ELSE 0 END) AS qua_han
        FROM phieu_muon
        WHERE ma_nd = ?
    ");
    $stmt_pm->bind_param("i", $ma_nd);
    $stmt_pm->execute();
    $res_pm = $stmt_pm->get_result()->fetch_assoc();
    $stmt_pm->close();
} else {
    $res_pm = $conn->query("
        SELECT
            SUM(CASE WHEN trang_thai = 'Đang mượn' THEN 1 ELSE 0 END) AS dang_muon,
            SUM(CASE WHEN trang_thai = 'Quá hạn' OR (trang_thai = 'Đang mượn' AND han_tra < NOW()) THEN 1 ELSE 0 END) AS qua_han
        FROM phieu_muon
    ")->fetch_assoc();
}

$so_phieu_dang_muon = (int)($res_pm['dang_muon'] ?? 0);
$phieu_qua_han = (int)($res_pm['qua_han'] ?? 0);

// 3. Lấy danh sách phiếu mượn gần nhất
$danh_sach_phieu = [];
if ($vai_tro === 'nguoimuon') {
    $stmt_list = $conn->prepare("
        SELECT pm.ma_pm, nd.ho_ten, pm.ngay_muon, pm.han_tra, pm.trang_thai
        FROM phieu_muon pm
        JOIN nguoi_dung nd ON pm.ma_nd = nd.ma_nd
        WHERE pm.ma_nd = ?
        ORDER BY pm.ngay_muon DESC
        LIMIT 5
    ");
    $stmt_list->bind_param("i", $ma_nd);
    $stmt_list->execute();
    $danh_sach_phieu = $stmt_list->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_list->close();
} else {
    $res_list = $conn->query("
        SELECT pm.ma_pm, nd.ho_ten, pm.ngay_muon, pm.han_tra, pm.trang_thai
        FROM phieu_muon pm
        JOIN nguoi_dung nd ON pm.ma_nd = nd.ma_nd
        ORDER BY pm.ngay_muon DESC
        LIMIT 5
    ");
    if ($res_list) {
        $danh_sach_phieu = $res_list->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ - Hệ thống Quản lý Kho dụng cụ Thể thao</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <!-- SIDEBAR PHÂN QUYỀN THEO VAI_TRO (admin, thukho, nguoimuon) -->
    <aside class="sidebar">
        <div>
            <div class="brand">
                <div class="brand-icon"><i class="fa-solid fa-bolt"></i></div>
                <div class="brand-name">
                    Hệ thống Quản lý<br>
                    Kho dụng cụ<br>
                    Thể thao
                </div>
            </div>

            <div class="nav-section-title">
                <?php echo $vai_tro === 'nguoimuon' ? 'Không gian sinh viên' : 'Không gian quản trị'; ?>
            </div>

            <ul class="nav-menu">
                <li>
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fa-solid fa-table-cells-large"></i> Trang chủ
                    </a>
                </li>

                <?php if ($vai_tro === 'admin'): ?>
                    <li>
                        <a href="../baocao/index.php" class="nav-link">
                            <i class="fa-solid fa-users"></i> Quản lý tài khoản
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($vai_tro === 'admin' || $vai_tro === 'thukho'): ?>
                    <li>
                        <a href="../dungcu/index.php" class="nav-link">
                            <i class="fa-solid fa-cube"></i> Quản lý kho
                        </a>
                    </li>
                    <li>
                        <a href="../muontra/index.php" class="nav-link">
                            <i class="fa-regular fa-circle-check"></i> Duyệt phiếu
                        </a>
                    </li>
                    <li>
                        <a href="../muontra/vipham.php" class="nav-link">
                            <i class="fa-solid fa-triangle-exclamation"></i> Quản lý bàn giao/vi phạm
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($vai_tro === 'nguoimuon'): ?>
                    <li>
                        <a href="../dungcu/index.php" class="nav-link">
                            <i class="fa-solid fa-basketball"></i> Tra cứu dụng cụ
                        </a>
                    </li>
                    <li>
                        <a href="../muontra/index.php" class="nav-link">
                            <i class="fa-solid fa-file-lines"></i> Phiếu mượn của tôi
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="sidebar-bottom">
            <div class="system-status-box">
                <div class="system-status-title"><i class="fa-solid fa-box-archive"></i> Kho dụng cụ thể thao</div>
                <div class="system-status-sub"><span class="dot-green"></span> Hệ thống đang hoạt động</div>
            </div>

            <div class="user-footer">
                <div class="user-footer-info">
                    <div class="avatar-circle"><?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div>
                        <div class="user-footer-name"><?php echo htmlspecialchars($ho_ten, ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="user-footer-role"><?php echo $ten_vai_tro; ?></div>
                    </div>
                </div>
                <a href="logout.php" class="btn-logout" title="Đăng xuất">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main-wrapper">
        <header class="top-header">
            <div class="breadcrumb">
                <i class="fa-solid fa-house"></i> <span>/</span> <span class="current">Trang chủ</span>
            </div>
            <div class="header-right">
                <button class="icon-btn" title="Thông báo"><i class="fa-regular fa-bell"></i></button>
                <div class="header-user">
                    <div>
                        <div class="header-user-name">Chào mừng <?php echo htmlspecialchars($ho_ten, ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="header-user-role"><?php echo $ten_vai_tro; ?></div>
                    </div>
                    <div class="header-avatar"><?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
        </header>

        <main class="content">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Tổng quan kho dụng cụ</h1>
                    <p class="page-subtitle">Theo dõi số liệu dụng cụ và tình trạng phiếu mượn trong hệ thống.</p>
                </div>
                <div class="date-badge">
                    <i class="fa-regular fa-calendar"></i> <?php echo $ngay_hien_tai; ?>
                </div>
            </div>

            <!-- 3 THẺ SỐ LIỆU TỔNG QUAN -->
            <div class="kpi-grid">
                <div class="kpi-card kpi-blue">
                    <div class="kpi-top">
                        <span class="kpi-label">Tổng dụng cụ</span>
                        <span class="kpi-icon"><i class="fa-solid fa-box"></i></span>
                    </div>
                    <div class="kpi-value"><?php echo $tong_dung_cu; ?></div>
                    <div class="kpi-desc">Sẵn sàng trong kho: <?php echo $kha_dung; ?></div>
                </div>

                <div class="kpi-card kpi-green">
                    <div class="kpi-top">
                        <span class="kpi-label">Số phiếu đang mượn</span>
                        <span class="kpi-icon"><i class="fa-solid fa-hand-holding-heart"></i></span>
                    </div>
                    <div class="kpi-value"><?php echo $so_phieu_dang_muon; ?></div>
                    <div class="kpi-desc">Phiếu đang lưu hành</div>
                </div>

                <div class="kpi-card kpi-red">
                    <div class="kpi-top">
                        <span class="kpi-label">Phiếu quá hạn</span>
                        <span class="kpi-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    </div>
                    <div class="kpi-value"><?php echo $phieu_qua_han; ?></div>
                    <div class="kpi-desc">Cần nhắc nhở thu hồi</div>
                </div>
            </div>

            <!-- BẢNG PHIẾU MƯỢN GẦN NHẤT -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Phiếu mượn gần đây</h2>
                    <p class="card-subtitle">Cập nhật trạng thái mượn trả mới nhất từ cơ sở dữ liệu.</p>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Mã phiếu</th>
                                <th>Người mượn</th>
                                <th>Ngày mượn</th>
                                <th>Hạn trả</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($danh_sach_phieu)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 28px; color: #64748b;">
                                        Chưa có dữ liệu phiếu mượn trong hệ thống.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($danh_sach_phieu as $pm): ?>
                                    <?php
                                        $badge_class = match ($pm['trang_thai']) {
                                            'Đang mượn' => 'pill-blue',
                                            'Đã trả' => 'pill-green',
                                            'Quá hạn' => 'pill-red',
                                            default => 'pill-amber',
                                        };
                                    ?>
                                    <tr>
                                        <td><strong>#PM<?php echo str_pad($pm['ma_pm'], 3, '0', STR_PAD_LEFT); ?></strong></td>
                                        <td><?php echo htmlspecialchars($pm['ho_ten'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($pm['ngay_muon'])); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($pm['han_tra'])); ?></td>
                                        <td><span class="pill <?php echo $badge_class; ?>"><?php echo htmlspecialchars($pm['trang_thai'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="page-note">
                <span><i class="fa-solid fa-circle-info"></i> Dữ liệu đồng bộ trực tiếp từ bảng <code>dung_cu</code> và <code>phieu_muon</code>.</span>
                <span>Hệ thống Quản lý Kho dụng cụ Thể thao · Trang chủ</span>
            </div>
        </main>
    </div>

</body>
</html>