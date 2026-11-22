CREATE DATABASE IF NOT EXISTS qlkdctt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE qlkdctt;

CREATE TABLE nguoi_dung (
    ma_nd INT AUTO_INCREMENT PRIMARY KEY,
    ho_ten VARCHAR(100) NOT NULL,
    ten_dang_nhap VARCHAR(50) NOT NULL UNIQUE,
    mat_khau VARCHAR(255) NOT NULL,
    vai_tro ENUM('admin', 'thukho', 'nguoimuon') DEFAULT 'nguoimuon'
);

CREATE TABLE dung_cu (
    ma_dc INT AUTO_INCREMENT PRIMARY KEY,
    ten_dc VARCHAR(100) NOT NULL,
    loai_dc VARCHAR(50) NOT NULL,
    don_vi_tinh VARCHAR(20) NOT NULL,
    so_luong_tong INT NOT NULL DEFAULT 0,
    so_luong_kha_dung INT NOT NULL DEFAULT 0,
    tinh_trang ENUM('Tốt', 'Hư hỏng', 'Bảo trì') DEFAULT 'Tốt',
    hinh_anh VARCHAR(255) NULL
);

CREATE TABLE phieu_muon (
    ma_pm INT AUTO_INCREMENT PRIMARY KEY,
    ma_nd INT NOT NULL,
    ngay_muon DATETIME DEFAULT CURRENT_TIMESTAMP,
    han_tra DATETIME NOT NULL,
    ngay_tra_thuc_te DATETIME NULL,
    trang_thai ENUM('Chờ duyệt', 'Đang mượn', 'Đã trả', 'Quá hạn') DEFAULT 'Chờ duyệt',
    ghi_chu TEXT NULL,
    FOREIGN KEY (ma_nd) REFERENCES nguoi_dung(ma_nd)
);

CREATE TABLE chi_tiet_muon (
    ma_ct INT AUTO_INCREMENT PRIMARY KEY,
    ma_pm INT NOT NULL,
    ma_dc INT NOT NULL,
    so_luong_muon INT NOT NULL,
    tinh_trang_khi_tra VARCHAR(100) NULL,
    FOREIGN KEY (ma_pm) REFERENCES phieu_muon(ma_pm) ON DELETE CASCADE,
    FOREIGN KEY (ma_dc) REFERENCES dung_cu(ma_dc)
);

INSERT INTO nguoi_dung (ho_ten, ten_dang_nhap, mat_khau, vai_tro) VALUES
('Võ Trương Bảo Trung', 'admin', '123456', 'admin'),
('Thủ Kho Thể Chất', 'thukho', '123456', 'thukho'),
('Nguyễn Văn A', 'sinhvien1', '123456', 'nguoimuon');

INSERT INTO dung_cu (ten_dc, loai_dc, don_vi_tinh, so_luong_tong, so_luong_kha_dung, tinh_trang) VALUES
('Bóng đá Động Lực số 5', 'Bóng đá', 'Quả', 20, 18, 'Tốt'),
('Vợt cầu lông Yonex', 'Cầu lông', 'Cây', 15, 15, 'Tốt'),
('Bóng chuyền Thăng Long', 'Bóng chuyền', 'Quả', 12, 10, 'Tốt'),
('Nệm tập thể dục', 'Thể dục dụng cụ', 'Tấm', 10, 8, 'Tốt');