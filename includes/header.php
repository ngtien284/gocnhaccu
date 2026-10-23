<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra trạng thái đăng nhập
$is_logged_in = isset($_SESSION['user']) && !empty($_SESSION['user']);
$display_name = $_SESSION['user_name'] ?? ($_SESSION['user'] ?? '');
$user_avatar = $_SESSION['user_avatar'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80';

// Hàm kiểm tra trang active (nếu chưa có trong project)
if (!function_exists('is_active_page')) {
    function is_active_page($page) {
        return basename($_SERVER['PHP_SELF']) == $page . '.php' ? 'text-[#8C5835] font-bold border-b-2 border-[#8C5835]' : 'text-[#5C5349] hover:text-[#8C5835] font-medium';
    }
}
?>
<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' | GOCNHACCU' : 'GOCNHACCU - Cửa Hàng Nhạc Cụ' ?></title>
  
  <!-- Google Fonts & Tailwind CDN -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
  
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
            serif: ['"Playfair Display"', 'serif'],
          },
          colors: {
            wood: {
              50: '#FDFBF9', 100: '#FBF8F5', 200: '#EFE7DF', 300: '#E0D2C4',
              400: '#B8977E', 500: '#8C5835', 600: '#754728', 700: '#5F371D',
              800: '#432612', 900: '#2A170A',
            },
            sand: {
              card: '#FFFFFF', border: '#E7E0D8', muted: '#7A6F64', heading: '#2D2319',
            }
          }
        }
      }
    }
  </script>
</head>
<body class="bg-[#FBF8F5] text-[#2D2319] font-sans antialiased min-h-full flex flex-col selection:bg-[#E0D2C4] selection:text-[#5F371D]">

  <!-- 1. Top Announcement Bar -->
  <div class="bg-[#3D2E24] text-[#EFE7DF] text-xs py-2 px-6">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-2">
      <div class="flex items-center gap-2">
        <span class="inline-block w-2 h-2 rounded-full bg-[#B8977E]"></span>
        <span>Kiểm định Luthier 55 tiêu chí • Miễn phí vận chuyển toàn quốc cho đơn từ 2.000.000₫</span>
      </div>
      <div class="flex items-center gap-6">
        <span>Tư vấn kỹ thuật: <strong class="text-[#E0D2C4]">1900 8888</strong></span>
        <?php if ($is_logged_in): ?>
          <span class="text-emerald-400 font-semibold">Xin chào, <?= htmlspecialchars($display_name) ?></span>
        <?php else: ?>
          <a href="login.php" class="hover:underline flex items-center gap-1">
            <i data-lucide="user" class="w-3.5 h-3.5"></i> Tài khoản VIP
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- 2. Header Main -->
  <header class="sticky top-0 z-50 bg-[#FBF8F5]/95 backdrop-blur-md border-b border-[#E7E0D8]">
    <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between gap-8">
      
      <!-- Brand Logo -->
      <a href="index.php" class="flex items-center gap-3 shrink-0 group">
        <div class="w-11 h-11 rounded-xl bg-[#8C5835] group-hover:bg-[#754728] transition flex items-center justify-center text-white shadow-md shadow-[#8C5835]/20">
          <i data-lucide="music" class="w-6 h-6"></i>
        </div>
        <div>
          <span class="text-xl font-extrabold tracking-tight text-[#2D2319] block leading-tight font-serif">GOCNHACCU</span>
          <span class="text-[10px] tracking-widest uppercase font-semibold text-[#8C5835]">Acoustic & Fine Instruments</span>
        </div>
      </a>

      <!-- Thanh tìm kiếm -->
      <div class="flex-1 max-w-xl relative">
        <form action="san-pham.php" method="GET">
          <input type="text" name="q" placeholder="Tìm guitar mộc, grand piano, dây elixir, amply đèn..." 
                 value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>"
                 class="w-full bg-white border border-[#E7E0D8] rounded-full py-2.5 pl-11 pr-24 text-sm text-[#2D2319] placeholder-[#9E948A] focus:outline-none focus:ring-2 focus:ring-[#8C5835]/30 focus:border-[#8C5835] transition shadow-sm" />
          <i data-lucide="search" class="w-4 h-4 text-[#9E948A] absolute left-4 top-1/2 -translate-y-1/2"></i>
          <button type="submit" class="absolute right-1.5 top-1/2 -translate-y-1/2 bg-[#8C5835] hover:bg-[#754728] text-white text-xs font-semibold px-4 py-1.5 rounded-full transition">
            Tìm
          </button>
        </form>
      </div>

      <!-- Action Icons & Auth State -->
      <div class="flex items-center gap-4 shrink-0">
        <a href="khuyen-mai.php" class="hidden md:flex items-center gap-1.5 text-xs font-bold text-[#8C5835] bg-[#EFE7DF]/80 hover:bg-[#EFE7DF] px-3.5 py-2 rounded-full transition">
          <i data-lucide="sparkles" class="w-4 h-4"></i>
          <span>Ưu Đãi Hôm Nay</span>
        </a>

        <!-- Icon giỏ hàng -->
        <a href="cart.php" class="w-10 h-10 rounded-full border border-[#E7E0D8] bg-white flex items-center justify-center text-[#5C5349] hover:text-[#8C5835] hover:border-[#8C5835] transition relative shadow-sm" title="Xem giỏ hàng">
          <i data-lucide="shopping-bag" class="w-4 h-4"></i>
          <span class="absolute -top-1 -right-1 bg-[#8C5835] text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center shadow">
            <?= isset($_SESSION['cart_count']) ? $_SESSION['cart_count'] : '3' ?>
          </span>
        </a>

        <!-- HIỂN THỊ ĐỘNG: ĐÃ ĐĂNG NHẬP (AVATAR) HOẶC CHƯA ĐĂNG NHẬP -->
        <?php if ($is_logged_in): ?>
          <a href="profile.php" class="flex items-center gap-2.5 bg-white border border-[#E7E0D8] rounded-full py-1 pl-1 pr-3 shadow-sm">
            <img src="<?= htmlspecialchars($user_avatar) ?>" alt="Avatar" class="w-8 h-8 rounded-full object-cover border border-[#8C5835]"/>
            <span class="text-xs font-bold text-[#2D2319] hidden sm:inline max-w-[100px] truncate"><?= htmlspecialchars($display_name) ?></span>
            <a href="logout.php" class="text-xs text-red-600 hover:text-red-800 ml-1 font-semibold p-1" title="Đăng xuất">
              <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
            </a>
          </div>
        <?php else: ?>
          <a href="login.php" class="w-9 h-9 rounded-full bg-[#E0D2C4] border border-[#B8977E]/50 flex items-center justify-center text-[#5F371D] hover:bg-[#B8977E] hover:text-white transition shadow-sm" title="Đăng nhập tài khoản">
            <i data-lucide="user" class="w-4 h-4"></i>
          </a>
        <?php endif; ?>

      </div>
    </div>

    <!-- Navigation Bar -->
    <div class="border-t border-[#E7E0D8] bg-[#FDFBF9]">
      <div class="max-w-7xl mx-auto px-6 flex items-center gap-8 text-sm py-2.5 overflow-x-auto">
        <a href="index.php" class="<?= is_active_page('index') ?>">Trang Chủ</a>
        <a href="san-pham.php" class="<?= is_active_page('san-pham') ?>">Tất Cả Nhạc Cụ</a>
        <a href="san-pham.php?cat=guitar" class="text-[#5C5349] hover:text-[#8C5835] transition font-medium">Guitars & Bass</a>
        <a href="san-pham.php?cat=piano" class="text-[#5C5349] hover:text-[#8C5835] transition font-medium">Piano & Keyboards</a>
        <a href="san-pham.php?cat=strings" class="text-[#5C5349] hover:text-[#8C5835] transition font-medium">Violin & Đàn Dây</a>
        <a href="khuyen-mai.php" class="<?= is_active_page('khuyen-mai') ?>">Khuyến Mãi & Voucher</a>
      </div>
    </div>
  </header>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      if (typeof lucide !== 'undefined') {
        lucide.createIcons();
      }
    });
  </script>
