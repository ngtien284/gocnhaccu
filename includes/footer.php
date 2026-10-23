<?php
// includes/footer.php
?>
  <!-- Footer Tone Nâu Ấm Áp -->
  <footer class="mt-auto bg-[#2D2319] text-[#E0D2C4] pt-16 pb-12 border-t border-[#432612]">
    <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-4 gap-10 mb-12">
      
      <!-- Cột 1: Thông tin thương hiệu -->
      <div>
        <div class="flex items-center gap-3 mb-4">
          <div class="w-9 h-9 rounded-lg bg-[#8C5835] flex items-center justify-center text-white">
            <i data-lucide="music-2" class="w-5 h-5"></i>
          </div>
          <span class="text-xl font-bold text-white font-serif">GOCNHACCU</span>
        </div>
        <p class="text-xs text-[#B8977E] leading-relaxed mb-4">
          Không gian phân phối nhạc cụ mộc thủ công, đàn acoustic và thiết bị phòng thu tiêu chuẩn audiophile. Đại lý chính thức của Taylor, Martin, Fender, Roland và Yamaha tại Việt Nam.
        </p>
        <div class="text-[11px] text-[#8C7E72] space-y-1">
          <p>Trụ sở: <?= SITE_ADDRESS ?></p>
          <p>Hotline: <strong class="text-[#EFE7DF]"><?= SITE_HOTLINE ?></strong> (8:00 - 21:30)</p>
        </div>
      </div>

      <!-- Cột 2: Phân loại -->
      <div>
        <h4 class="text-white font-bold text-sm mb-4 uppercase tracking-wider text-xs">Danh Mục Sản Phẩm</h4>
        <ul class="space-y-2 text-xs text-[#B8977E]">
          <li><a href="san-pham.php?cat=acoustic" class="hover:text-white transition">Guitar Acoustic Gỗ Solid Top</a></li>
          <li><a href="san-pham.php?cat=classic" class="hover:text-white transition">Guitar Classic Cổ Điển Spain</a></li>
          <li><a href="san-pham.php?cat=piano" class="hover:text-white transition">Grand Piano & Upright Cao Cấp</a></li>
          <li><a href="san-pham.php?cat=digital-piano" class="hover:text-white transition">Piano Điện Phím Gỗ PHA-50</a></li>
          <li><a href="san-pham.php?cat=strings" class="hover:text-white transition">Dây Đàn Elixir & D'Addario USA</a></li>
        </ul>
      </div>

      <!-- Cột 3: Cam kết & Dịch vụ -->
      <div>
        <h4 class="text-white font-bold text-sm mb-4 uppercase tracking-wider text-xs">Tiêu Chuẩn Luthier</h4>
        <ul class="space-y-2 text-xs text-[#B8977E]">
          <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-[#8C5835]"></i> Kiểm định 55 điểm trước khi xuất kho</li>
          <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-[#8C5835]"></i> Cân chỉnh Action êm tay miễn phí</li>
          <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-[#8C5835]"></i> Bảo hành thân gỗ mộc trọn đời</li>
          <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-[#8C5835]"></i> Trải nghiệm phòng Studio VIP RT60</li>
          <li class="flex items-center gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-[#8C5835]"></i> Đổi mới 30 ngày nếu không ưng âm</li>
        </ul>
      </div>

      <!-- Cột 4: Đăng ký nhận tin -->
      <div>
        <h4 class="text-white font-bold text-sm mb-4 uppercase tracking-wider text-xs">Bản Tin Nghệ Sĩ</h4>
        <p class="text-xs text-[#B8977E] mb-3">Đăng ký để nhận voucher 500.000₫ cho đơn hàng nhạc cụ đầu tiên:</p>
        <form action="#" method="POST" class="flex gap-2 mb-4">
          <input type="email" placeholder="Nhập email của bạn..." required 
                 class="bg-[#3D2E24] border border-[#5F371D] rounded-xl px-3 py-2 text-xs text-white placeholder-[#8C7E72] focus:outline-none focus:border-[#8C5835] flex-1" />
          <button type="submit" class="bg-[#8C5835] hover:bg-[#754728] text-white text-xs font-bold px-4 py-2 rounded-xl transition shadow">
            Gửi
          </button>
        </form>
        <span class="text-[10px] text-[#8C7E72] block">Bảo mật thông tin 100% theo tiêu chuẩn SSL.</span>
      </div>
    </div>

    <!-- Bản quyền -->
    <div class="max-w-7xl mx-auto px-6 pt-8 border-t border-[#3D2E24] text-xs text-[#8C7E72] flex flex-col sm:flex-row items-center justify-between gap-4">
      <p>© <?= date('Y') ?> <?= SITE_NAME ?>. Bản quyền được bảo lưu.</p>
      <div class="flex items-center gap-6">
        <span>Chứng nhận phân phối chính hãng</span>
        <span>Thanh toán an toàn 256-Bit SSL</span>
      </div>
    </div>
  </footer>

  <script>
    lucide.createIcons();
  </script>
</body>
</html>