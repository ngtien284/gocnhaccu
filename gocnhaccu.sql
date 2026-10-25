-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 24, 2026 lúc 06:15 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `gocnhaccu`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitietsanpham`
--

CREATE TABLE `chitietsanpham` (
  `CTSP_Ma` int(11) NOT NULL,
  `NC_Ma` int(11) DEFAULT NULL,
  `PK_Ma` int(11) DEFAULT NULL,
  `CTSP_ChatLieu` varchar(150) DEFAULT NULL,
  `CTSP_MauSac` varchar(50) DEFAULT NULL,
  `CTSP_KichThuoc` varchar(100) DEFAULT NULL,
  `CTSP_TrongLuong` decimal(8,2) DEFAULT NULL,
  `CTSP_SoDay` int(11) DEFAULT NULL,
  `CTSP_ChatLieuDay` varchar(100) DEFAULT NULL,
  `CTSP_ChatLieuThan` varchar(100) DEFAULT NULL,
  `CTSP_ChatLieuCan` varchar(100) DEFAULT NULL,
  `CTSP_KieuDan` varchar(100) DEFAULT NULL,
  `CTSP_CoDan` varchar(50) DEFAULT NULL,
  `CTSP_SoPhim` int(11) DEFAULT NULL,
  `CTSP_SoGiaiDieu` int(11) DEFAULT NULL,
  `CTSP_SoAmSac` int(11) DEFAULT NULL,
  `CTSP_CongSuat` int(11) DEFAULT NULL,
  `CTSP_LoaiPhim` varchar(100) DEFAULT NULL,
  `CTSP_CheDoDan` varchar(100) DEFAULT NULL,
  `CTSP_SoTrong` int(11) DEFAULT NULL,
  `CTSP_SoLa` int(11) DEFAULT NULL,
  `CTSP_ChatLieuMatTrong` varchar(100) DEFAULT NULL,
  `CTSP_LoaiTrong` varchar(100) DEFAULT NULL,
  `CTSP_LoaiPhuKien` varchar(100) DEFAULT NULL,
  `CTSP_TuongThich` varchar(255) DEFAULT NULL,
  `CTSP_CongDung` varchar(255) DEFAULT NULL,
  `CTSP_QuyCach` varchar(100) DEFAULT NULL,
  `CTSP_MoTaKyThuat` text DEFAULT NULL
) ;

--
-- Đang đổ dữ liệu cho bảng `chitietsanpham`
--

INSERT INTO `chitietsanpham` (`CTSP_Ma`, `NC_Ma`, `PK_Ma`, `CTSP_ChatLieu`, `CTSP_MauSac`, `CTSP_KichThuoc`, `CTSP_TrongLuong`, `CTSP_SoDay`, `CTSP_ChatLieuDay`, `CTSP_ChatLieuThan`, `CTSP_ChatLieuCan`, `CTSP_KieuDan`, `CTSP_CoDan`, `CTSP_SoPhim`, `CTSP_SoGiaiDieu`, `CTSP_SoAmSac`, `CTSP_CongSuat`, `CTSP_LoaiPhim`, `CTSP_CheDoDan`, `CTSP_SoTrong`, `CTSP_SoLa`, `CTSP_ChatLieuMatTrong`, `CTSP_LoaiTrong`, `CTSP_LoaiPhuKien`, `CTSP_TuongThich`, `CTSP_CongDung`, `CTSP_QuyCach`, `CTSP_MoTaKyThuat`) VALUES
(1, 1, NULL, 'Gỗ', NULL, 'Kích thước tiêu chuẩn', NULL, 6, 'Kim loại', 'Mặt đàn gỗ vân sam (Spruce)', 'Gỗ Nato', 'Acoustic', 'Full size', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Đàn guitar acoustic 6 dây, mặt đàn vân sam, phù hợp cho người mới học và đệm hát.'),
(2, 2, NULL, 'Gỗ', NULL, 'Dreadnought', NULL, 6, 'Kim loại', 'Mặt đàn gỗ vân sam nguyên tấm (Solid Spruce)', 'Gỗ Mahogany', 'Acoustic', 'Dreadnought', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Đàn guitar acoustic 6 dây, thiết kế dreadnought, mặt đàn vân sam nguyên tấm.'),
(3, 3, NULL, NULL, 'Đen', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 88, NULL, 15, 12, 'PHA-4 Standard', 'Piano điện', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Piano điện 88 phím với bàn phím PHA-4 Standard và công nghệ âm thanh piano của Roland.'),
(4, 4, NULL, NULL, 'Đen', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 88, NULL, 10, 16, 'Scaled Hammer Action Keyboard II', 'Piano điện', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Piano điện 88 phím, thiết kế nhỏ gọn, hỗ trợ sử dụng nguồn điện hoặc pin.'),
(5, 5, NULL, NULL, 'Đen', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Trống điện tử', NULL, NULL, NULL, NULL, 'Bộ trống điện tử Yamaha DTX402K, hỗ trợ luyện tập và thực hành chơi trống.');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loainhaccu`
--

CREATE TABLE `loainhaccu` (
  `LNC_Ma` int(11) NOT NULL,
  `LNC_Ten` varchar(100) NOT NULL,
  `LNC_MoTa` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

--
-- Đang đổ dữ liệu cho bảng `loainhaccu`
--

INSERT INTO `loainhaccu` (`LNC_Ma`, `LNC_Ten`, `LNC_MoTa`) VALUES
(1, 'Guitar', 'Các dòng Guitar Acoustic, Electric, Classic'),
(2, 'Piano', 'Piano cơ Upright/Grand và Piano điện'),
(3, 'Trống', 'Trống Acoustic và Trống điện tử'),
(4, 'Ukulele & Violin', 'Nhạc cụ dây nhỏ gọn và nhạc cụ cổ điển'),
(5, 'Phụ Kiện Nhạc Cụ', 'Capo, Dây đàn, Bao đàn, Chân đế, Phím gảy'),
(6, 'Organ', 'Các dòng đàn Organ điện tử, Keyboard phục vụ học tập và trình diễn');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhaccu`
--

CREATE TABLE `nhaccu` (
  `NC_Ma` int(11) NOT NULL,
  `LNC_Ma` int(11) NOT NULL,
  `TH_Ma` int(11) NOT NULL,
  `NC_Ten` varchar(150) NOT NULL,
  `NC_GiaBan` decimal(15,2) NOT NULL,
  `NC_GiaKhuyenMai` decimal(15,2) DEFAULT NULL,
  `NC_SoLuongTon` int(11) NOT NULL DEFAULT 0,
  `NC_HinhAnh` varchar(255) DEFAULT NULL,
  `NC_MoTa` text DEFAULT NULL,
  `NC_ThoiGianBaoHanh` int(11) DEFAULT 12,
  `NC_TrangThai` tinyint(1) DEFAULT 1,
  `NC_NgayTao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

--
-- Đang đổ dữ liệu cho bảng `nhaccu`
--

INSERT INTO `nhaccu` (`NC_Ma`, `LNC_Ma`, `TH_Ma`, `NC_Ten`, `NC_GiaBan`, `NC_GiaKhuyenMai`, `NC_SoLuongTon`, `NC_HinhAnh`, `NC_MoTa`, `NC_ThoiGianBaoHanh`, `NC_TrangThai`, `NC_NgayTao`) VALUES
(1, 1, 1, 'Guitar Acoustic Yamaha F310', 3500000.00, 3150000.00, 10, '1.jpg', 'Guitar Acoustic phổ biến nhất cho người mới bắt đầu. Mặt top gỗ Vân Sam.', 12, 1, '2026-10-23 20:54:19'),
(2, 1, 2, 'Guitar Acoustic Fender CD-60S', 5500000.00, 4800000.00, 5, '2.jpg', 'Guitar Acoustic cao cấp mặt gỗ nguyên tấm (Solid Spruce), âm thanh vang ấm.', 12, 1, '2026-10-23 20:54:19'),
(3, 2, 3, 'Piano Điện Roland FP-10', 14800000.00, 13500000.00, 3, '3.jpg', 'Piano điện 88 phím chuẩn Hammer Action, công nghệ âm thanh SuperNATURAL.', 24, 1, '2026-10-23 20:54:19'),
(4, 2, 4, 'Piano Điện Casio CDP-S110', 10200000.00, NULL, 4, '4.jpg', 'Thiết kế siêu mỏng nhẹ, bàn phím chân thực, kết nối ứng dụng học tập.', 12, 1, '2026-10-23 20:54:19'),
(5, 3, 1, 'Bộ Trống Điện Tử Yamaha DTX402K', 12500000.00, 11200000.00, 2, '5.jpg', 'Bộ trống điện tập luyện tại nhà yên tĩnh, kết nối ứng dụng DTX Touch.', 12, 1, '2026-10-23 20:54:19');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhaccuphukien`
--

CREATE TABLE `nhaccuphukien` (
  `NC_Ma` int(11) NOT NULL,
  `PK_Ma` int(11) NOT NULL,
  `GhiChu` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

--
-- Đang đổ dữ liệu cho bảng `nhaccuphukien`
--

INSERT INTO `nhaccuphukien` (`NC_Ma`, `PK_Ma`, `GhiChu`) VALUES
(1, 1, 'Khuyên dùng cho Guitar Yamaha F310'),
(1, 2, 'Dây đàn dự phòng phù hợp'),
(2, 1, 'Capo cao cấp cho Fender CD-60S');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phukien`
--

CREATE TABLE `phukien` (
  `PK_Ma` int(11) NOT NULL,
  `LNC_Ma` int(11) NOT NULL,
  `TH_Ma` int(11) NOT NULL,
  `PK_Ten` varchar(150) NOT NULL,
  `PK_GiaBan` decimal(15,2) NOT NULL,
  `PK_GiaKhuyenMai` decimal(15,2) DEFAULT NULL,
  `PK_SoLuongTon` int(11) NOT NULL DEFAULT 0,
  `PK_HinhAnh` varchar(255) DEFAULT NULL,
  `PK_MoTa` text DEFAULT NULL,
  `PK_TrangThai` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

--
-- Đang đổ dữ liệu cho bảng `phukien`
--

INSERT INTO `phukien` (`PK_Ma`, `LNC_Ma`, `TH_Ma`, `PK_Ten`, `PK_GiaBan`, `PK_GiaKhuyenMai`, `PK_SoLuongTon`, `PK_HinhAnh`, `PK_MoTa`, `PK_TrangThai`) VALUES
(1, 5, 5, 'Capo Guitar Kẹp Hợp Kim Alice A007A', 80000.00, NULL, 50, 'pk1.webp', 'Capo kẹp lò xo hợp kim nhôm siêu bền cho Guitar Acoustic & Classic', 1),
(2, 5, 5, 'Bộ Dây Guitar Acoustic Alice A406', 120000.00, 95000.00, 30, 'pk2.webp', 'Dây đàn Acoustic bọc đồng chống gỉ sét, âm thanh đanh sáng', 1),
(3, 5, 1, 'Bao Đàn Guitar Acoustic 3 Lớp Dày', 250000.00, NULL, 20, 'pk3.webp', 'Bao da 3 lớp chống va đập và chống nước nhẹ cho Guitar', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thuonghieu`
--

CREATE TABLE `thuonghieu` (
  `TH_Ma` int(11) NOT NULL,
  `TH_Ten` varchar(100) NOT NULL,
  `TH_Logo` varchar(255) DEFAULT NULL,
  `TH_MoTa` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

--
-- Đang đổ dữ liệu cho bảng `thuonghieu`
--

INSERT INTO `thuonghieu` (`TH_Ma`, `TH_Ten`, `TH_Logo`, `TH_MoTa`) VALUES
(1, 'Yamaha', 'yamaha.png', 'Thương hiệu nhạc cụ hàng đầu Nhật Bản'),
(2, 'Fender', 'fender.png', 'Thương hiệu Guitar điện & Acoustic nổi tiếng thế giới từ Mỹ'),
(3, 'Roland', 'roland.png', 'Chuyên về Piano điện và Trống điện tử cao cấp'),
(4, 'Casio', 'casio.png', 'Đàn Organ và Piano điện phổ biến cho học sinh sinh viên'),
(5, 'Alice', 'alice.png', 'Thương hiệu phụ kiện nhạc cụ uy tín');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `chitietsanpham`
--
ALTER TABLE `chitietsanpham`
  ADD PRIMARY KEY (`CTSP_Ma`),
  ADD UNIQUE KEY `uq_ctsp_nc` (`NC_Ma`),
  ADD UNIQUE KEY `uq_ctsp_pk` (`PK_Ma`);

--
-- Chỉ mục cho bảng `loainhaccu`
--
ALTER TABLE `loainhaccu`
  ADD PRIMARY KEY (`LNC_Ma`),
  ADD UNIQUE KEY `LNC_Ten` (`LNC_Ten`);

--
-- Chỉ mục cho bảng `nhaccu`
--
ALTER TABLE `nhaccu`
  ADD PRIMARY KEY (`NC_Ma`),
  ADD KEY `fk_nhaccu_loainhaccu` (`LNC_Ma`),
  ADD KEY `fk_nhaccu_thuonghieu` (`TH_Ma`);

--
-- Chỉ mục cho bảng `nhaccuphukien`
--
ALTER TABLE `nhaccuphukien`
  ADD PRIMARY KEY (`NC_Ma`,`PK_Ma`),
  ADD KEY `fk_ncpk_phukien` (`PK_Ma`);

--
-- Chỉ mục cho bảng `phukien`
--
ALTER TABLE `phukien`
  ADD PRIMARY KEY (`PK_Ma`),
  ADD KEY `fk_phukien_loainhaccu` (`LNC_Ma`),
  ADD KEY `fk_phukien_thuonghieu` (`TH_Ma`);

--
-- Chỉ mục cho bảng `thuonghieu`
--
ALTER TABLE `thuonghieu`
  ADD PRIMARY KEY (`TH_Ma`),
  ADD UNIQUE KEY `TH_Ten` (`TH_Ten`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `chitietsanpham`
--
ALTER TABLE `chitietsanpham`
  MODIFY `CTSP_Ma` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `loainhaccu`
--
ALTER TABLE `loainhaccu`
  MODIFY `LNC_Ma` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `nhaccu`
--
ALTER TABLE `nhaccu`
  MODIFY `NC_Ma` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `phukien`
--
ALTER TABLE `phukien`
  MODIFY `PK_Ma` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `thuonghieu`
--
ALTER TABLE `thuonghieu`
  MODIFY `TH_Ma` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `chitietsanpham`
--
ALTER TABLE `chitietsanpham`
  ADD CONSTRAINT `fk_ctsp_nhac_cu` FOREIGN KEY (`NC_Ma`) REFERENCES `nhaccu` (`NC_Ma`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctsp_phu_kien` FOREIGN KEY (`PK_Ma`) REFERENCES `phukien` (`PK_Ma`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `nhaccu`
--
ALTER TABLE `nhaccu`
  ADD CONSTRAINT `fk_nhaccu_loainhaccu` FOREIGN KEY (`LNC_Ma`) REFERENCES `loainhaccu` (`LNC_Ma`),
  ADD CONSTRAINT `fk_nhaccu_thuonghieu` FOREIGN KEY (`TH_Ma`) REFERENCES `thuonghieu` (`TH_Ma`);

--
-- Các ràng buộc cho bảng `nhaccuphukien`
--
ALTER TABLE `nhaccuphukien`
  ADD CONSTRAINT `fk_ncpk_nhaccu` FOREIGN KEY (`NC_Ma`) REFERENCES `nhaccu` (`NC_Ma`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ncpk_phukien` FOREIGN KEY (`PK_Ma`) REFERENCES `phukien` (`PK_Ma`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `phukien`
--
ALTER TABLE `phukien`
  ADD CONSTRAINT `fk_phukien_loainhaccu` FOREIGN KEY (`LNC_Ma`) REFERENCES `loainhaccu` (`LNC_Ma`),
  ADD CONSTRAINT `fk_phukien_thuonghieu` FOREIGN KEY (`TH_Ma`) REFERENCES `thuonghieu` (`TH_Ma`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2026 at 06:03 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gocnhaccu`
--

-- --------------------------------------------------------

--
-- Table structure for table `don_hang`
--

CREATE TABLE `don_hang` (
  `DH_Ma` int(11) NOT NULL,
  `ND_Ma` int(11) NOT NULL,
  `DH_NgayDat` datetime NOT NULL DEFAULT current_timestamp(),
  `DH_TongTien` decimal(12,2) NOT NULL,
  `DH_DiaChiNhan_TP` varchar(100) NOT NULL,
  `DH_DiaChiNhan_PhuongXa` varchar(100) NOT NULL,
  `DH_DiaChiNhan_SoNha` varchar(100) NOT NULL,
  `DH_SoDTNhan` varchar(15) NOT NULL,
  `DH_TenNguoiNhan` varchar(100) NOT NULL,
  `DH_PhuongThucThanhToan` varchar(100) NOT NULL,
  `DH_TrangThaiThanhToan` varchar(30) DEFAULT NULL,
  `DH_TrangThai` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `don_hang`
--
ALTER TABLE `don_hang`
  ADD PRIMARY KEY (`DH_Ma`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `don_hang`
--
ALTER TABLE `don_hang`
  MODIFY `DH_Ma` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2026 at 06:02 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gocnhaccu`
--

-- --------------------------------------------------------

--
-- Table structure for table `chi_tiet_don_hang`
--

CREATE TABLE `chi_tiet_don_hang` (
  `CTDH_Ma` int(11) NOT NULL,
  `DH_Ma` int(11) NOT NULL,
  `NC_Ma` int(11) NOT NULL,
  `CTDH_SoLuongSP` int(11) NOT NULL,
  `CTDH_Gia` decimal(12,2) NOT NULL,
  `CTDH_ThanhTien` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  ADD PRIMARY KEY (`CTDH_Ma`),
  ADD KEY `DH_Ma` (`DH_Ma`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  MODIFY `CTDH_Ma` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  ADD CONSTRAINT `chi_tiet_don_hang_ibfk_1` FOREIGN KEY (`DH_Ma`) REFERENCES `don_hang` (`DH_Ma`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

------------------------------------------SAO----------------------------------

-- 1. Loại nhạc cụ "Sáo"
SET @new_lnc := (SELECT IFNULL(MAX(`LNC_Ma`), 0) + 1 FROM `loainhaccu`);
INSERT IGNORE INTO `loainhaccu` (`LNC_Ma`, `LNC_Ten`, `LNC_MoTa`)
SELECT @new_lnc, 'Sáo', 'Sáo flute, sáo trúc và các nhạc cụ hơi'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `loainhaccu` WHERE `LNC_Ten` LIKE 'S%o');
SET @lnc_sao := (SELECT `LNC_Ma` FROM `loainhaccu` WHERE `LNC_Ten` LIKE 'S%o' ORDER BY `LNC_Ma` LIMIT 1);

-- 2. Thương hiệu Yamaha
SET @new_th := (SELECT IFNULL(MAX(`TH_Ma`), 0) + 1 FROM `thuonghieu`);
INSERT IGNORE INTO `thuonghieu` (`TH_Ma`, `TH_Ten`, `TH_Logo`, `TH_MoTa`)
SELECT @new_th, 'Yamaha', 'yamaha.png', 'Thương hiệu nhạc cụ hàng đầu Nhật Bản'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `thuonghieu` WHERE `TH_Ten` LIKE 'Yamaha');
SET @th_yamaha := (SELECT `TH_Ma` FROM `thuonghieu` WHERE `TH_Ten` LIKE 'Yamaha' ORDER BY `TH_Ma` LIMIT 1);

-- 3. Thương hiệu sáo trúc truyền thống
SET @new_th := (SELECT IFNULL(MAX(`TH_Ma`), 0) + 1 FROM `thuonghieu`);
INSERT IGNORE INTO `thuonghieu` (`TH_Ma`, `TH_Ten`, `TH_Logo`, `TH_MoTa`)
SELECT @new_th, 'Làng nghề Việt', NULL, 'Nhạc cụ truyền thống Việt Nam làm thủ công'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `thuonghieu` WHERE `TH_Ten` LIKE 'L%Vi%t');
SET @th_viet := (SELECT `TH_Ma` FROM `thuonghieu` WHERE `TH_Ten` LIKE 'L%Vi%t' ORDER BY `TH_Ma` LIMIT 1);

-- 4. Sáo flute Yamaha YFL-222
SET @new_nc := (SELECT IFNULL(MAX(`NC_Ma`), 0) + 1 FROM `nhaccu`);
INSERT INTO `nhaccu`
  (`NC_Ma`, `LNC_Ma`, `TH_Ma`, `NC_Ten`, `NC_GiaBan`, `NC_GiaKhuyenMai`, `NC_SoLuongTon`, `NC_HinhAnh`, `NC_MoTa`, `NC_ThoiGianBaoHanh`, `NC_TrangThai`)
SELECT @new_nc, @lnc_sao, @th_yamaha, 'Sáo Flute Yamaha YFL-222', 16500000.00, 14900000.00, 6, 'sao-flute.jpg',
   'Sáo flute dành cho người mới học và học sinh nhạc viện. Thân ống mạ bạc, âm thanh sáng rõ, bấm phím nhẹ, đi kèm hộp đựng.',
   12, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `nhaccu` WHERE `NC_Ten` LIKE '%YFL-222%');
SET @nc_flute := (SELECT `NC_Ma` FROM `nhaccu` WHERE `NC_Ten` LIKE '%YFL-222%' ORDER BY `NC_Ma` LIMIT 1);

-- 5. Sáo trúc ngang 6 lỗ
SET @new_nc := (SELECT IFNULL(MAX(`NC_Ma`), 0) + 1 FROM `nhaccu`);
INSERT INTO `nhaccu`
  (`NC_Ma`, `LNC_Ma`, `TH_Ma`, `NC_Ten`, `NC_GiaBan`, `NC_GiaKhuyenMai`, `NC_SoLuongTon`, `NC_HinhAnh`, `NC_MoTa`, `NC_ThoiGianBaoHanh`, `NC_TrangThai`)
SELECT @new_nc, @lnc_sao, @th_viet, 'Sáo Trúc Ngang 6 Lỗ Thủ Công', 450000.00, 390000.00, 25, 'sao-truc.jpg',
   'Sáo trúc ngang 6 lỗ làm thủ công từ trúc già, tiếng sáo trong, ấm, dễ thổi. Phù hợp học nhạc cụ dân tộc và biểu diễn.',
   3, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `nhaccu` WHERE `NC_Ten` LIKE '%6 L%Th%C%ng');
SET @nc_truc := (SELECT `NC_Ma` FROM `nhaccu` WHERE `NC_Ten` LIKE '%6 L%Th%C%ng' ORDER BY `NC_Ma` LIMIT 1);

-- 6. Thông số kỹ thuật (INSERT IGNORE: mỗi sản phẩm chỉ có 1 dòng chi tiết)
SET @ct1 := (SELECT IFNULL(MAX(`CTSP_Ma`), 0) + 1 FROM `chitietsanpham`);
SET @ct2 := @ct1 + 1;
INSERT IGNORE INTO `chitietsanpham`
  (`CTSP_Ma`, `NC_Ma`, `CTSP_ChatLieu`, `CTSP_MauSac`, `CTSP_KichThuoc`, `CTSP_TrongLuong`, `CTSP_KieuDan`, `CTSP_MoTaKyThuat`)
VALUES
  (@ct1, @nc_flute, 'Hợp kim mạ bạc', 'Bạc', 'Dài khoảng 67 cm', 0.50, 'Flute ngang, giọng C',
   'Sáo flute 16 lỗ phím, thân ống mạ bạc, phù hợp người mới bắt đầu và luyện tập hằng ngày.'),
  (@ct2, @nc_truc, 'Trúc tự nhiên', 'Vàng trúc', 'Dài khoảng 55 cm', 0.08, 'Sáo ngang 6 lỗ',
   'Sáo trúc ngang 6 lỗ, sơn bóng chống nứt, thích hợp luyện tập và biểu diễn nhạc dân tộc.');


------------------------------------------SAO----------------------------------
---------------------------VIOLIN-------------------------------------

-- =====================================================================
-- THÊM 2 CÂY VIOLIN VÀO CƠ SỞ DỮ LIỆU gocnhaccu
-- Cách dùng: phpMyAdmin > chọn database `gocnhaccu` > tab SQL > dán và chạy
-- Script an toàn khi chạy lại: mục nào đã có thì dùng lại, không thêm trùng.
-- =====================================================================

SET NAMES utf8mb4;

-- 1. Loại nhạc cụ: dùng loại "Ukulele & Violin" có sẵn (không có thì tạo "Violin")
SET @new_lnc := (SELECT IFNULL(MAX(`LNC_Ma`), 0) + 1 FROM `loainhaccu`);
INSERT IGNORE INTO `loainhaccu` (`LNC_Ma`, `LNC_Ten`, `LNC_MoTa`)
SELECT @new_lnc, 'Violin', 'Đàn violin (vĩ cầm) và các nhạc cụ dây vĩ'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `loainhaccu` WHERE `LNC_Ten` LIKE '%Violin%');
SET @lnc_violin := (SELECT `LNC_Ma` FROM `loainhaccu` WHERE `LNC_Ten` LIKE '%Violin%' ORDER BY `LNC_Ma` LIMIT 1);

-- 2. Thương hiệu Yamaha
SET @new_th := (SELECT IFNULL(MAX(`TH_Ma`), 0) + 1 FROM `thuonghieu`);
INSERT IGNORE INTO `thuonghieu` (`TH_Ma`, `TH_Ten`, `TH_Logo`, `TH_MoTa`)
SELECT @new_th, 'Yamaha', 'yamaha.png', 'Thương hiệu nhạc cụ hàng đầu Nhật Bản'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `thuonghieu` WHERE `TH_Ten` LIKE 'Yamaha');
SET @th_yamaha := (SELECT `TH_Ma` FROM `thuonghieu` WHERE `TH_Ten` LIKE 'Yamaha' ORDER BY `TH_Ma` LIMIT 1);

-- 3. Thương hiệu Cremona
SET @new_th := (SELECT IFNULL(MAX(`TH_Ma`), 0) + 1 FROM `thuonghieu`);
INSERT IGNORE INTO `thuonghieu` (`TH_Ma`, `TH_Ten`, `TH_Logo`, `TH_MoTa`)
SELECT @new_th, 'Cremona', NULL, 'Thương hiệu đàn violin dành cho người mới học và học sinh'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `thuonghieu` WHERE `TH_Ten` LIKE 'Cremona');
SET @th_cremona := (SELECT `TH_Ma` FROM `thuonghieu` WHERE `TH_Ten` LIKE 'Cremona' ORDER BY `TH_Ma` LIMIT 1);

-- 4. Violin Yamaha V3SKA 4/4 (có giá khuyến mãi)
SET @new_nc := (SELECT IFNULL(MAX(`NC_Ma`), 0) + 1 FROM `nhaccu`);
INSERT INTO `nhaccu`
  (`NC_Ma`, `LNC_Ma`, `TH_Ma`, `NC_Ten`, `NC_GiaBan`, `NC_GiaKhuyenMai`, `NC_SoLuongTon`, `NC_HinhAnh`, `NC_MoTa`, `NC_ThoiGianBaoHanh`, `NC_TrangThai`)
SELECT @new_nc, @lnc_violin, @th_yamaha, 'Violin Yamaha V3SKA 4/4', 8200000.00, 7500000.00, 4, 'violin-yamaha.jpg',
   'Đàn violin cỡ 4/4 dành cho người học nghiêm túc. Mặt đàn gỗ vân sam, lưng và hông gỗ thích, âm thanh ấm và đều, đi kèm hộp đàn, vĩ và nhựa thông.',
   12, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `nhaccu` WHERE `NC_Ten` LIKE '%V3SKA%');
SET @nc_yamaha := (SELECT `NC_Ma` FROM `nhaccu` WHERE `NC_Ten` LIKE '%V3SKA%' ORDER BY `NC_Ma` LIMIT 1);

-- 5. Violin Cremona SV-75 4/4
SET @new_nc := (SELECT IFNULL(MAX(`NC_Ma`), 0) + 1 FROM `nhaccu`);
INSERT INTO `nhaccu`
  (`NC_Ma`, `LNC_Ma`, `TH_Ma`, `NC_Ten`, `NC_GiaBan`, `NC_GiaKhuyenMai`, `NC_SoLuongTon`, `NC_HinhAnh`, `NC_MoTa`, `NC_ThoiGianBaoHanh`, `NC_TrangThai`)
SELECT @new_nc, @lnc_violin, @th_cremona, 'Violin Cremona SV-75 4/4', 4500000.00, NULL, 8, 'violin-cremona.jpg',
   'Đàn violin cỡ 4/4 phù hợp học sinh và người mới bắt đầu. Mặt đàn gỗ vân sam, âm thanh sáng, giá thành hợp lý, đi kèm hộp đàn và vĩ.',
   12, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `nhaccu` WHERE `NC_Ten` LIKE '%SV-75%');
SET @nc_cremona := (SELECT `NC_Ma` FROM `nhaccu` WHERE `NC_Ten` LIKE '%SV-75%' ORDER BY `NC_Ma` LIMIT 1);

-- 6. Thông số kỹ thuật (INSERT IGNORE: mỗi sản phẩm chỉ có 1 dòng chi tiết)
SET @ct1 := (SELECT IFNULL(MAX(`CTSP_Ma`), 0) + 1 FROM `chitietsanpham`);
SET @ct2 := @ct1 + 1;
INSERT IGNORE INTO `chitietsanpham`
  (`CTSP_Ma`, `NC_Ma`, `CTSP_ChatLieu`, `CTSP_MauSac`, `CTSP_KichThuoc`, `CTSP_TrongLuong`, `CTSP_SoDay`, `CTSP_ChatLieuDay`, `CTSP_ChatLieuThan`, `CTSP_ChatLieuCan`, `CTSP_KieuDan`, `CTSP_CoDan`, `CTSP_MoTaKyThuat`)
VALUES
  (@ct1, @nc_yamaha, 'Gỗ', 'Nâu vàng bóng', 'Dài thân đàn khoảng 35.5 cm', 0.45, 4, 'Hợp kim', 'Mặt vân sam, lưng và hông gỗ thích', 'Gỗ thích', 'Violin Acoustic', '4/4',
   'Violin 4 dây cỡ 4/4, mặt đàn gỗ vân sam, lưng và hông gỗ thích, phù hợp người học nghiêm túc.'),
  (@ct2, @nc_cremona, 'Gỗ', 'Nâu bóng', 'Dài thân đàn khoảng 35.5 cm', 0.45, 4, 'Hợp kim', 'Mặt vân sam, lưng và hông gỗ thích', 'Gỗ thích', 'Violin Acoustic', '4/4',
   'Violin 4 dây cỡ 4/4, mặt đàn gỗ vân sam, phù hợp học sinh và người mới bắt đầu.');

-- 7. Kiểm tra kết quả
SELECT nc.NC_Ma, nc.NC_Ten, lnc.LNC_Ma, lnc.LNC_Ten, th.TH_Ten, nc.NC_GiaBan, nc.NC_GiaKhuyenMai, nc.NC_HinhAnh
FROM `nhaccu` nc
JOIN `loainhaccu` lnc ON nc.LNC_Ma = lnc.LNC_Ma
JOIN `thuonghieu` th  ON nc.TH_Ma  = th.TH_Ma
WHERE nc.NC_Ma IN (@nc_yamaha, @nc_cremona);
---------------------------VIOLIN---------------------------------