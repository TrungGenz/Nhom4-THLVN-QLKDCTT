<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$ma_dc = (int)($_POST['ma_dc'] ?? 0);
if ($ma_dc <= 0) {
    header("Location: index.php?msg=" . urlencode("Mã dụng cụ không hợp lệ.") . "&type=danger");
    exit;
}

$stmt_check = mysqli_prepare($conn, "SELECT COUNT(*) AS tong FROM chi_tiet_muon WHERE ma_dc = ?");
mysqli_stmt_bind_param($stmt_check, "i", $ma_dc);
mysqli_stmt_execute($stmt_check);
$row_check = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_check));
mysqli_stmt_close($stmt_check);

if ((int)($row_check['tong'] ?? 0) > 0) {
    header("Location: index.php?msg=" . urlencode("Không thể xóa vì dụng cụ đang nằm trong phiếu mượn!") . "&type=danger");
    exit;
}

$stmt_del = mysqli_prepare($conn, "DELETE FROM dung_cu WHERE ma_dc = ?");
mysqli_stmt_bind_param($stmt_del, "i", $ma_dc);
if (mysqli_stmt_execute($stmt_del)) {
    mysqli_stmt_close($stmt_del);
    header("Location: index.php?msg=" . urlencode("Đã xóa dụng cụ thành công.") . "&type=success");
    exit;
}

$err = mysqli_error($conn);
mysqli_stmt_close($stmt_del);
header("Location: index.php?msg=" . urlencode("Lỗi khi xóa: " . $err) . "&type=danger");
exit;