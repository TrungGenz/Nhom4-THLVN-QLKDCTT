<?php
require_once '../config/db.php';

$thong_bao =$_GET['msg'] ?? '';
$loai_thong_bao =$_GET['type'] ?? 'success';

$keyword = trim($_GET['q'] ?? '');
$loai_loc = trim($_GET['loai_dc'] ?? '');

$ds_loai = [];
$res_loai = mysqli_query($conn, "SELECT DISTINCT loai_dc FROM dung_cu WHERE loai_dc <> '' ORDER BY loai_dc ASC");
if ($res_loai) {
    while ($r = mysqli_fetch_assoc($res_loai)) {
        $ds_loai[] =$r['loai_dc'];
    }
}

$sql = "SELECT * FROM dung_cu WHERE 1=1";
$params = [];$types = "";

if ($keyword !== '') {$sql .= " AND ten_dc LIKE ?";
    $params[] = '%' . $keyword . '\%';$types .= "s";
}

if ($loai_loc !== '') {$sql .= " AND loai_dc = ?";
    $params[] =$loai_loc;
    $types .= "s";
}

$sql .= " ORDER BY ma_dc ASC";
$stmt = mysqli_prepare($conn,$sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$items = [];$tong_tat_ca = 0;
$tong_kha_dung = 0;
while ($row = mysqli_fetch_assoc($result)) {$tong_sl = (int)$row['so_luong_tong'];$kha_dung = (int)$row['so_luong_kha_dung'];$dang_muon = max(0, $tong_sl -$kha_dung);

    $row['dang_muon_hien_thi'] =$dang_muon;
    $row['duoc_giu_hien_thi'] = 0;
    $row['khong_cho_muon_hien_thi'] = 0;

    $items[] =$row;
    $tong_tat_ca +=$tong_sl;
    $tong_kha_dung +=$kha_dung;
}
mysqli_stmt_close($stmt);

$so_luong_loai_hien_thi = count($items);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý kho - Hệ thống Quản lý Kho dụng cụ Thể thao</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-text: #cbd5e1;
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --primary-soft: #eff6ff;
            --page-bg: #f8fafc;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --green: #16a34a;
            --green-soft: #dcfce7;
            --blue-soft: #dbeafe;
            --amber: #d97706;
            --amber-soft: #fef3c7;
            --red: #ef4444;
            --red-soft: #fee2e2;
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
            color: #fff;
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
        .sidebar-bottom {
            padding: 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .warehouse-status-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 12px;
        }
        .warehouse-status-card .w-title { font-size: 13px; font-weight: 600; margin-bottom: 4px; }
        .warehouse-status-card .w-sub { font-size: 12px; color: #4ade80; }
        .user-box { display: flex; align-items: center; gap: 10px; padding-top: 4px; }
        .avatar-circle {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            background: #334155;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
        }
        .user-meta .u-name { font-size: 13px; font-weight: 600; }
        .user-meta .u-role { font-size: 11px; color: #94a3b8; }
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
        .breadcrumb strong { color: var(--text-main); font-weight: 600; }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .topbar-user { text-align: right; line-height: 1.25; }
        .topbar-user .name { font-size: 13px; font-weight: 700; }
        .topbar-user .role { font-size: 11px; color: var(--text-muted); }
        .content-area { padding: 24px 28px; max-width: 1380px; width: 100%; margin: 0 auto; }
        .hero-header h1 { margin: 0; font-size: 26px; font-weight: 700; color: var(--text-main); }
        .hero-header p { margin: 6px 0 0; font-size: 14px; color: var(--text-muted); }
        .action-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin: 20px 0;
        }
        .filter-controls { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
        .select-sport, .input-search {
            height: 40px;
            padding: 0 14px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            color: var(--text-main);
        }
        .select-sport { min-width: 240px; }
        .input-search { min-width: 220px; }
        .btn-add {
            height: 40px;
            padding: 0 18px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }
        .btn-add:hover { background: var(--primary-dark); }
        .btn-reset {
            height: 40px;
            padding: 0 14px;
            background: #fff;
            color: var(--text-muted);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .panel-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }
        .panel-top {
            padding: 18px 22px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .panel-title-row { display: flex; align-items: center; gap: 10px; }
        .panel-title-row h2 { margin: 0; font-size: 16px; font-weight: 700; }
        .pill-count {
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 999px;
        }
        .panel-sub { margin: 4px 0 0; font-size: 13px; color: var(--text-muted); }
        .summary-stats { display: flex; align-items: center; gap: 14px; font-size: 13px; color: var(--text-muted); }
        .summary-stats .avail-stat { color: var(--green); font-weight: 600; }
        .table-wrap { width: 100%; overflow-x: auto; }
        table.eq-table { width: 100%; border-collapse: collapse; }
        table.eq-table th {
            background: #f8fafc;
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 600;
            text-align: left;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
        }
        table.eq-table th.num, table.eq-table td.num { text-align: center; }
        table.eq-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            vertical-align: middle;
        }
        table.eq-table tr:hover td { background: #fafcff; }
        .code-text { color: var(--text-muted); font-weight: 500; }
        .thumb-box {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            background: #f1f5f9;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: #94a3b8;
            font-size: 16px;
        }
        .thumb-box img { width: 100%; height: 100%; object-fit: cover; }
        .item-name { font-weight: 700; color: var(--text-main); font-size: 14px; }
        .item-meta { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
        .val-total { font-weight: 700; }
        .val-muted { color: var(--text-muted); }
        .val-avail { color: var(--green); font-weight: 700; }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-moi { background: var(--blue-soft); color: var(--primary-dark); }
        .status-tot { background: var(--green-soft); color: var(--green); }
        .status-baotri { background: var(--amber-soft); color: var(--amber); }
        .status-huhong { background: var(--red-soft); color: var(--red); }
        .row-actions { display: inline-flex; align-items: center; gap: 6px; }
        .icon-btn {
            width: 30px;
            height: 30px;
            border-radius: 7px;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 13px;
            cursor: pointer;
            background: transparent;
        }
        .icon-edit { color: var(--primary); background: var(--primary-soft); }
        .icon-edit:hover { background: #dbeafe; }
        .icon-del { color: var(--red); background: #fef2f2; }
        .icon-del:hover { background: var(--red-soft); }
        .panel-footer {
            padding: 14px 22px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 12px;
            color: var(--text-muted);
        }
        .pager { display: inline-flex; align-items: center; gap: 6px; }
        .pager span {
            min-width: 28px;
            height: 28px;
            border-radius: 6px;
            border: 1px solid var(--border);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .pager .active-page {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
            font-weight: 600;
        }
        .formula-note {
            margin-top: 12px;
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        @media (max-width: 860px) { .sidebar { display: none; } }
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
            <div class="user-box">
                <div class="avatar-circle">QT</div>
                <div class="user-meta">
                    <div class="u-name">Quản trị viên</div>
                    <div class="u-role">Quản lý kho</div>
                </div>
            </div>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="topbar">
            <div class="breadcrumb">
                <span>🏠</span>
                <span>/</span>
                <strong>Quản lý kho</strong>
            </div>
            <div class="topbar-right">
                <span>🔔</span>
                <div class="topbar-user">
                    <div class="name">Chào mừng Quản trị viên</div>
                    <div class="role">Quản trị viên</div>
                </div>
                <div class="avatar-circle" style="background:#dbeafe;color:#1d4ed8;">QT</div>
            </div>
        </header>

        <main class="content-area">
            <div class="hero-header">
                <h1>Hệ thống Quản lý Kho dụng cụ Thể thao</h1>
                <p>Quản lý danh mục, theo dõi số lượng và tình trạng dụng cụ trong kho.</p>
            </div>

            <?php if ($thong_bao !== ''): ?>
                <div class="alert alert-<?= $loai_thong_bao === 'danger' ? 'danger' : 'success' ?>">
                    <?= htmlspecialchars($thong_bao) ?>
                </div>
            <?php endif; ?>

            <div class="action-toolbar">
                <form method="GET" action="index.php" class="filter-controls">
                    <select name="loai_dc" class="select-sport" onchange="this.form.submit()">
                        <option value="">Chọn nhóm môn thể thao</option>
                        <?php foreach ($ds_loai as$loai): ?>
                            <option value="<?= htmlspecialchars($loai) ?>" <?= $loai_loc ===$loai ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loai) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="q" class="input-search" placeholder="Tìm tên dụng cụ..." value="<?= htmlspecialchars($keyword) ?>">
                    <button type="submit" class="btn-reset" style="cursor:pointer;background:#fff;">Tìm kiếm</button>
                    <?php if ($keyword !== '' ||$loai_loc !== ''): ?>
                        <a href="index.php" class="btn-reset">Đặt lại</a>
                    <?php endif; ?>
                </form>

                <a href="them.php" class="btn-add">+ Thêm dụng cụ</a>
            </div>

            <section class="panel-card">
                <div class="panel-top">
                    <div>
                        <div class="panel-title-row">
                            <h2>Chi tiết Kho thiết bị</h2>
                            <span class="pill-count"><?= $so_luong_loai_hien_thi ?> loại dụng cụ</span>
                        </div>
                        <p class="panel-sub">Danh sách dụng cụ và số lượng hiện có trong kho.</p>
                    </div>
                    <div class="summary-stats">
                        <span><?= $tong_tat_ca ?> dụng cụ</span>
                        <span>•</span>
                        <span class="avail-stat">● <?= $tong_kha_dung ?> khả dụng</span>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="eq-table">
                        <thead>
                            <tr>
                                <th>Mã đồ dùng</th>
                                <th>Hình ảnh</th>
                                <th>Tên dụng cụ</th>
                                <th class="num">Tổng số lượng</th>
                                <th class="num">Đang mượn</th>
                                <th class="num">Được giữ</th>
                                <th class="num">Không thể cho mượn</th>
                                <th class="num">Khả dụng</th>
                                <th class="num">Tình trạng</th>
                                <th class="num">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($so_luong_loai_hien_thi > 0): ?>
                                <?php foreach ($items as$row): ?>
                                    <?php
                                    $ma_code = 'DC' . str_pad((string)(int)$row['ma_dc'], 3, '0', STR_PAD_LEFT);$tt = $row['tinh_trang'];$pillClass = 'status-tot';
                                    if ($tt === 'Mới') {
                                        $pillClass = 'status-moi';
                                    } elseif ($tt === 'Bảo trì') {
                                        $pillClass = 'status-baotri';
                                    } elseif ($tt === 'Hư hỏng') {
                                        $pillClass = 'status-huhong';
                                    }
                                    ?>
                                    <tr>
                                        <td class="code-text"><?= htmlspecialchars($ma_code) ?></td>
                                        <td>
                                            <div class="thumb-box">
                                                <?php if (!empty($row['hinh_anh'])): ?>
                                                    <img src="../assets/images/<?= htmlspecialchars(basename($row['hinh_anh'])) ?>" alt="<?= htmlspecialchars($row['ten_dc']) ?>">
                                                <?php else: ?>
                                                    🖼
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="item-name"><?= htmlspecialchars($row['ten_dc']) ?></div>
                                            <div class="item-meta"><?= htmlspecialchars($row['loai_dc']) ?> · Đơn vị: <?= htmlspecialchars($row['don_vi_tinh']) ?></div>
                                        </td>
                                        <td class="num val-total"><?= (int)$row['so_luong_tong'] ?></td>
                                        <td class="num val-muted"><?= (int)$row['dang_muon_hien_thi'] ?></td>
                                        <td class="num val-muted"><?= (int)$row['duoc_giu_hien_thi'] ?></td>
                                        <td class="num val-muted"><?= (int)$row['khong_cho_muon_hien_thi'] ?></td>
                                        <td class="num val-avail"><?= (int)$row['so_luong_kha_dung'] ?></td>
                                        <td class="num">
                                            <span class="status-pill <?= $pillClass ?>">• <?= htmlspecialchars($tt) ?></span>
                                        </td>
                                        <td class="num">
                                            <div class="row-actions">
                                                <a href="sua.php?ma_dc=<?= (int)$row['ma_dc'] ?>" class="icon-btn icon-edit" title="Sửa dụng cụ">✎</a>
                                                <form method="POST" action="xoa.php" style="display:inline;" onsubmit="return confirm('Xóa dụng cụ này?');">
                                                    <input type="hidden" name="ma_dc" value="<?= (int)$row['ma_dc'] ?>">
                                                    <button type="submit" class="icon-btn icon-del" title="Xóa dụng cụ">🗑</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" style="text-align:center;color:var(--text-muted);padding:28px;">
                                        Chưa có dụng cụ nào khớp với bộ lọc hiện tại.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="panel-footer">
                    <span>Hiển thị 1–<?= $so_luong_loai_hien_thi ?> trong tổng số <?= $so_luong_loai_hien_thi ?> loại dụng cụ</span>
                    <div class="pager">
                        <span>‹</span>
                        <span class="active-page">1</span>
                        <span>›</span>
                    </div>
                </div>
            </section>

            <div class="formula-note">
                <span>ⓘ Số lượng khả dụng = Tổng số lượng − Đang mượn − Được giữ − Không thể cho mượn.</span>
                <span>Hệ thống Quản lý Kho dụng cụ Thể thao · Quản lý kho</span>
            </div>
        </main>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>