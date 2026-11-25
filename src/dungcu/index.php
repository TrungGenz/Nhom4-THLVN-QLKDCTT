<?php
require_once '../config/db.php';

$thong_bao = '';
$loai_thong_bao = 'success';

// Xử lý Thêm mới hoặc Cập nhật dụng cụ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $ma_dc = isset($_POST['ma_dc']) && $_POST['ma_dc'] !== '' ? (int)$_POST['ma_dc'] : 0;
        $ten_dc = trim($_POST['ten_dc'] ?? '');
        $loai_dc = trim($_POST['loai_dc'] ?? '');
        $don_vi_tinh = trim($_POST['don_vi_tinh'] ?? '');
        $so_luong_tong = max(0, (int)($_POST['so_luong_tong'] ?? 0));
        $so_luong_kha_dung = max(0, (int)($_POST['so_luong_kha_dung'] ?? 0));
        $tinh_trang = trim($_POST['tinh_trang'] ?? 'Tốt');
        $hinh_anh_input = trim($_POST['hinh_anh'] ?? '');
        $hinh_anh = $hinh_anh_input !== '' ? $hinh_anh_input : null;

        if ($so_luong_kha_dung > $so_luong_tong) {
            $so_luong_kha_dung = $so_luong_tong;
        }

        if ($ten_dc === '' || $loai_dc === '' || $don_vi_tinh === '') {
            $thong_bao = 'Vui lòng nhập đầy đủ Tên dụng cụ, Loại dụng cụ và Đơn vị tính.';
            $loai_thong_bao = 'danger';
        } elseif ($ma_dc > 0) {
            $sql = "UPDATE dung_cu
                    SET ten_dc = ?, loai_dc = ?, don_vi_tinh = ?,
                        so_luong_tong = ?, so_luong_kha_dung = ?,
                        tinh_trang = ?, hinh_anh = ?
                    WHERE ma_dc = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param(
                $stmt,
                "sssiissi",
                $ten_dc,
                $loai_dc,
                $don_vi_tinh,
                $so_luong_tong,
                $so_luong_kha_dung,
                $tinh_trang,
                $hinh_anh,
                $ma_dc
            );
            if (mysqli_stmt_execute($stmt)) {
                $thong_bao = 'Cập nhật dụng cụ thành công!';
            } else {
                $thong_bao = 'Lỗi cập nhật: ' . mysqli_error($conn);
                $loai_thong_bao = 'danger';
            }
            mysqli_stmt_close($stmt);
        } else {
            $sql = "INSERT INTO dung_cu
                    (ten_dc, loai_dc, don_vi_tinh, so_luong_tong, so_luong_kha_dung, tinh_trang, hinh_anh)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param(
                $stmt,
                "sssiiss",
                $ten_dc,
                $loai_dc,
                $don_vi_tinh,
                $so_luong_tong,
                $so_luong_kha_dung,
                $tinh_trang,
                $hinh_anh
            );
            if (mysqli_stmt_execute($stmt)) {
                $thong_bao = 'Thêm dụng cụ mới thành công!';
            } else {
                $thong_bao = 'Lỗi thêm mới: ' . mysqli_error($conn);
                $loai_thong_bao = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Xử lý Xóa dụng cụ
    if ($action === 'delete') {
        $ma_dc = (int)($_POST['ma_dc'] ?? 0);
        if ($ma_dc > 0) {
            $sql = "DELETE FROM dung_cu WHERE ma_dc = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $ma_dc);
            if (mysqli_stmt_execute($stmt)) {
                $thong_bao = 'Đã xóa dụng cụ khỏi danh sách.';
            } else {
                $thong_bao = 'Không thể xóa dụng cụ (có thể đang nằm trong phiếu mượn): ' . mysqli_error($conn);
                $loai_thong_bao = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Lấy dữ liệu dụng cụ cần sửa (nếu bấm Sửa)
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    if ($edit_id > 0) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM dung_cu WHERE ma_dc = ?");
        mysqli_stmt_bind_param($stmt, "i", $edit_id);
        mysqli_stmt_execute($stmt);
        $res_edit = mysqli_stmt_get_result($stmt);
        $edit_item = mysqli_fetch_assoc($res_edit);
        mysqli_stmt_close($stmt);
    }
}

// Tìm kiếm & lọc theo tình trạng
$keyword = trim($_GET['q'] ?? '');
$filter_status = trim($_GET['tinh_trang'] ?? '');

$sql_list = "SELECT * FROM dung_cu WHERE 1=1";
$params = [];
$types = "";

if ($keyword !== '') {
    $sql_list .= " AND (ten_dc LIKE ? OR loai_dc LIKE ?)";
    $like = '%' . $keyword . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

if ($filter_status !== '') {
    $sql_list .= " AND tinh_trang = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$sql_list .= " ORDER BY ma_dc DESC";
$stmt_list = mysqli_prepare($conn, $sql_list);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_list, $types, ...$params);
}
mysqli_stmt_execute($stmt_list);
$result_list = mysqli_stmt_get_result($stmt_list);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Dụng cụ Thể thao - QLKDCTT</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; color: #222; }
        .container { max-width: 1140px; margin: 0 auto; background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        h1 { margin-top: 0; font-size: 22px; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; }
        .alert-success { background: #e6f4ea; color: #137333; border: 1px solid #ceead6; }
        .alert-danger { background: #fce8e6; color: #c5221f; border: 1px solid #fad2cf; }
        .form-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 24px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
        label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 4px; }
        input, select { width: 100%; box-sizing: border-box; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; }
        .btn { padding: 8px 14px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-primary { background: #1a73e8; color: #fff; }
        .btn-secondary { background: #64748b; color: #fff; }
        .btn-edit { background: #f59e0b; color: #fff; padding: 5px 10px; font-size: 13px; }
        .btn-delete { background: #dc2626; color: #fff; padding: 5px 10px; font-size: 13px; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; margin-bottom: 14px; }
        .toolbar form { display: flex; gap: 8px; flex-wrap: wrap; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e2e8f0; padding: 10px; text-align: left; font-size: 14px; }
        th { background: #f1f5f9; }
        .badge { padding: 3px 8px; border-radius: 12px; font-size: 12px; background: #e2e8f0; }
    </style>
</head>
<body>
<div class="container">
    <h1>Quản lý Dụng cụ Thể thao</h1>

    <?php if ($thong_bao !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($loai_thong_bao) ?>">
            <?= htmlspecialchars($thong_bao) ?>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <h3><?= $edit_item ? 'Cập nhật dụng cụ #' . (int)$edit_item['ma_dc'] : 'Thêm dụng cụ mới' ?></h3>
        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="ma_dc" value="<?= $edit_item ? (int)$edit_item['ma_dc'] : '' ?>">
            <div class="form-grid">
                <div>
                    <label>Tên dụng cụ *</label>
                    <input type="text" name="ten_dc" required value="<?= htmlspecialchars($edit_item['ten_dc'] ?? '') ?>">
                </div>
                <div>
                    <label>Loại dụng cụ *</label>
                    <input type="text" name="loai_dc" required value="<?= htmlspecialchars($edit_item['loai_dc'] ?? '') ?>">
                </div>
                <div>
                    <label>Đơn vị tính *</label>
                    <input type="text" name="don_vi_tinh" required value="<?= htmlspecialchars($edit_item['don_vi_tinh'] ?? '') ?>">
                </div>
                <div>
                    <label>Số lượng tổng *</label>
                    <input type="number" name="so_luong_tong" min="0" required value="<?= (int)($edit_item['so_luong_tong'] ?? 0) ?>">
                </div>
                <div>
                    <label>Số lượng khả dụng *</label>
                    <input type="number" name="so_luong_kha_dung" min="0" required value="<?= (int)($edit_item['so_luong_kha_dung'] ?? 0) ?>">
                </div>
                <div>
                    <label>Tình trạng</label>
                    <?php $tt = $edit_item['tinh_trang'] ?? 'Tốt'; ?>
                    <select name="tinh_trang">
                        <option value="Tốt" <?= $tt === 'Tốt' ? 'selected' : '' ?>>Tốt</option>
                        <option value="Cần bảo trì" <?= $tt === 'Cần bảo trì' ? 'selected' : '' ?>>Cần bảo trì</option>
                        <option value="Hỏng" <?= $tt === 'Hỏng' ? 'selected' : '' ?>>Hỏng</option>
                    </select>
                </div>
                <div>
                    <label>Hình ảnh (tên file hoặc URL)</label>
                    <input type="text" name="hinh_anh" value="<?= htmlspecialchars($edit_item['hinh_anh'] ?? '') ?>">
                </div>
            </div>
            <div style="margin-top: 14px; display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary"><?= $edit_item ? 'Lưu cập nhật' : 'Thêm dụng cụ' ?></button>
                <?php if ($edit_item): ?>
                    <a href="index.php" class="btn btn-secondary">Hủy sửa</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="toolbar">
        <form method="GET" action="index.php">
            <input type="text" name="q" placeholder="Tìm tên hoặc loại dụng cụ..." value="<?= htmlspecialchars($keyword) ?>">
            <select name="tinh_trang">
                <option value="">-- Tất cả tình trạng --</option>
                <option value="Tốt" <?= $filter_status === 'Tốt' ? 'selected' : '' ?>>Tốt</option>
                <option value="Cần bảo trì" <?= $filter_status === 'Cần bảo trì' ? 'selected' : '' ?>>Cần bảo trì</option>
                <option value="Hỏng" <?= $filter_status === 'Hỏng' ? 'selected' : '' ?>>Hỏng</option>
            </select>
            <button type="submit" class="btn btn-primary">Lọc</button>
            <a href="index.php" class="btn btn-secondary">Đặt lại</a>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Mã</th>
                <th>Tên dụng cụ</th>
                <th>Loại</th>
                <th>ĐVT</th>
                <th>Tổng SL</th>
                <th>Khả dụng</th>
                <th>Tình trạng</th>
                <th>Hình ảnh</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result_list) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result_list)): ?>
                    <tr>
                        <td><?= (int)$row['ma_dc'] ?></td>
                        <td><?= htmlspecialchars($row['ten_dc']) ?></td>
                        <td><?= htmlspecialchars($row['loai_dc']) ?></td>
                        <td><?= htmlspecialchars($row['don_vi_tinh']) ?></td>
                        <td><?= (int)$row['so_luong_tong'] ?></td>
                        <td><?= (int)$row['so_luong_kha_dung'] ?></td>
                        <td><span class="badge"><?= htmlspecialchars($row['tinh_trang']) ?></span></td>
                        <td><?= $row['hinh_anh'] ? htmlspecialchars($row['hinh_anh']) : '<em>Chưa có</em>' ?></td>
                        <td style="white-space: nowrap;">
                            <a href="index.php?edit=<?= (int)$row['ma_dc'] ?>" class="btn btn-edit">Sửa</a>
                            <form method="POST" action="index.php" style="display:inline;" onsubmit="return confirm('Xóa dụng cụ này?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="ma_dc" value="<?= (int)$row['ma_dc'] ?>">
                                <button type="submit" class="btn btn-delete">Xóa</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="9" style="text-align:center;">Không có dụng cụ nào phù hợp.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
<?php
mysqli_stmt_close($stmt_list);
?>