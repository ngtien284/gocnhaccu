<?php
// Bật hiển thị lỗi để dễ dàng debug nếu còn lỗi phát sinh
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('SITE_NAME', 'GOCNHACCU Acoustic & Fine Instruments');
define('SITE_HOTLINE', '1900 8888');

// Bổ sung các hằng số mà footer đang cần:
define('SITE_ADDRESS', '123 Đường Nhạc Cụ, Quận 1, TP. Hồ Chí Minh');
define('SITE_EMAIL', 'support@gocnhaccu.vn');
define('SITE_WORKING_HOURS', '8:00 - 21:00 (Thứ Hai - Chủ Nhật)');

// Định nghĩa hàm format tiền tệ VND nếu chưa có
if (!function_exists('format_vnd')) {
    function format_vnd($price) {
        return number_format($price, 0, ',', '.') . '₫';
    }
}

// Định nghĩa hàm kiểm tra trang active trên menu nếu chưa có
if (!function_exists('is_active_page')) {
    function is_active_page($page_name) {
        $current = basename($_SERVER['PHP_SELF'], '.php');
        return ($current === $page_name) ? 'text-[#8C5835] font-bold border-b-2 border-[#8C5835] pb-1' : 'text-[#5C5349] hover:text-[#8C5835] transition font-medium';
    }
}
