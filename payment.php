<?php
// config/payment.php - Thông tin nhận tiền cho thanh toán QR (VietQR)
// >>> SỬA CÁC GIÁ TRỊ DƯỚI ĐÂY THÀNH THÔNG TIN THẬT CỦA SHOP <<<

const PAY_BANK_ID      = 'VCB';                  // Mã ngân hàng: VCB, MB, ACB, TCB, BIDV, VPB, TPB...
const PAY_ACCOUNT_NO   = '0123456789';           // Số tài khoản nhận tiền
const PAY_ACCOUNT_NAME = 'CUA HANG GOCNHACCU';   // Tên chủ tài khoản (không dấu)
const PAY_QR_TEMPLATE  = 'compact2';             // compact2 = QR + thông tin chuyển khoản
const PAY_PREFIX       = 'GNC';                  // Nội dung chuyển khoản = GNC + mã đơn (vd: GNC15)

// Danh sách email được xem là quản trị viên (ngoài việc xét theo loại người dùng)
const ADMIN_EMAILS = [
    // 'admin@gocnhaccu.vn',
];

// Nội dung chuyển khoản của một đơn hàng
function pay_content(int $orderId): string
{
    return PAY_PREFIX . $orderId;
}

// Đường dẫn ảnh QR theo VietQR Quick Link
function pay_qr_url(int $amount, string $content): string
{
    return 'https://img.vietqr.io/image/' . rawurlencode(PAY_BANK_ID) . '-' . rawurlencode(PAY_ACCOUNT_NO)
        . '-' . rawurlencode(PAY_QR_TEMPLATE) . '.png'
        . '?amount=' . $amount
        . '&addInfo=' . rawurlencode($content)
        . '&accountName=' . rawurlencode(PAY_ACCOUNT_NAME);
}