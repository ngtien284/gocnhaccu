<?php
/**
 * Khu vực "Đánh giá của khách hàng".
 * Cần có sẵn các biến từ chitietsanpham.php: $conn, $type, $id, $product, $reviewSummary
 */
$h = static fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

/* ---- Bộ lọc / sắp xếp / tìm kiếm / phân trang (qua GET) ---- */
$filterStar = isset($_GET['sao']) ? (int)$_GET['sao'] : 0;
if ($filterStar < 1 || $filterStar > 5) {
    $filterStar = 0;
}
$search = trim((string)($_GET['q'] ?? ''));
$sortMap = [
    'moi'  => 'DG_NgayTao DESC',
    'cu'   => 'DG_NgayTao ASC',
    'cao'  => 'DG_SoSao DESC, DG_NgayTao DESC',
    'thap' => 'DG_SoSao ASC, DG_NgayTao DESC',
];
$sort = isset($sortMap[$_GET['sx'] ?? '']) ? $_GET['sx'] : 'moi';
$page = max(1, (int)($_GET['trang'] ?? 1));

$where = 'DG_LoaiSanPham = :loai AND DG_MaSanPham = :id AND DG_TrangThai = 1';
$params = ['loai' => $type, 'id' => $id];
if ($filterStar) {
    $where .= ' AND DG_SoSao = :sao';
    $params['sao'] = $filterStar;
}
if ($search !== '') {
    $where .= ' AND (DG_TieuDe LIKE :q1 OR DG_NoiDung LIKE :q2 OR DG_TenHienThi LIKE :q3)';
    $like = '%' . addcslashes($search, '%_\\') . '%';
    $params['q1'] = $params['q2'] = $params['q3'] = $like;
}

$stmtCount = $conn->prepare("SELECT COUNT(*) FROM danhgia WHERE $where");
$stmtCount->execute($params);
$totalFiltered = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($totalFiltered / DG_PER_PAGE));
$page = min($page, $totalPages);

$stmtList = $conn->prepare("
    SELECT * FROM danhgia WHERE $where
    ORDER BY {$sortMap[$sort]}
    LIMIT " . DG_PER_PAGE . ' OFFSET ' . (($page - 1) * DG_PER_PAGE)
);
$stmtList->execute($params);
$reviews = $stmtList->fetchAll(PDO::FETCH_ASSOC);

/* ---- Quyền viết đánh giá ---- */
$customer = dg_current_customer();
$canReview = false;
$reviewNotice = '';
if (!$customer) {
    $reviewNotice = 'login';
} elseif (dg_has_reviewed($conn, $customer['id'], $type, (int)$id)) {
    $reviewNotice = 'Bạn đã đánh giá sản phẩm này rồi.';
} elseif (!dg_customer_has_purchased($conn, $customer['id'], $type, (int)$id)) {
    $reviewNotice = 'Chỉ khách hàng đã mua sản phẩm này mới có thể viết đánh giá.';
} else {
    $canReview = true;
}

/** Tạo URL giữ nguyên id/type và các bộ lọc hiện tại, trỏ về khu vực #danh-gia. */
$reviewUrl = static function (array $override = []) {
    $query = array_merge($_GET, $override);
    foreach ($query as $k => $v) {
        if ($v === null || $v === '' || $v === 0) {
            unset($query[$k]);
        }
    }
    return '?' . http_build_query($query) . '#danh-gia';
};

$flash = $_SESSION['dg_flash'] ?? null;
unset($_SESSION['dg_flash']);
$S = $reviewSummary;
?>
<section id="danh-gia" class="dg mt-10 scroll-mt-28">
    <h2 class="dg-heading">Đánh giá của khách hàng</h2>

    <?php if ($flash): ?>
        <div class="dg-flash <?= $flash['ok'] ? 'dg-flash-ok' : 'dg-flash-err' ?>" role="status">
            <?= $h($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <!-- 3 ô thống kê -->
    <div class="dg-summary">
        <div class="dg-box dg-box-center">
            <div class="dg-avg"><?= $S['total'] ? rtrim(rtrim(number_format($S['avg'], 1), '0'), '.') : '–' ?></div>
            <?= dg_stars($S['avg']) ?>
            <div class="dg-small"><?= $S['total'] ?> đánh giá</div>

            <?php if ($canReview): ?>
                <button type="button" class="dg-btn" id="dg-open-form">Viết đánh giá</button>
            <?php elseif ($reviewNotice === 'login'): ?>
                <a class="dg-btn" href="<?= $h(DG_LOGIN_URL) ?>?redirect=<?= urlencode($_SERVER['REQUEST_URI'] . '#danh-gia') ?>">Đăng nhập để đánh giá</a>
            <?php else: ?>
                <p class="dg-notice"><?= $h($reviewNotice) ?></p>
            <?php endif; ?>
        </div>

        <div class="dg-box dg-box-center">
            <div class="dg-recommend">✓ <?= $S['recommend'] ?>%</div>
            <div class="dg-small dg-small-lg">khách hàng sẽ giới thiệu<br>sản phẩm này cho bạn bè</div>
        </div>

        <div class="dg-box">
            <?php foreach ([5, 4, 3, 2, 1] as $star):
                $count = $S['dist'][$star];
                $pct = $S['total'] ? round($count * 100 / $S['total']) : 0;
                $active = ($filterStar === $star);
            ?>
                <a class="dg-bar-row <?= $active ? 'is-active' : '' ?> <?= $count === 0 ? 'is-empty' : '' ?>"
                   href="<?= $h($reviewUrl(['sao' => $active ? null : $star, 'trang' => null])) ?>"
                   title="<?= $active ? 'Bỏ lọc' : 'Chỉ hiện đánh giá ' . $star . ' sao' ?>">
                    <span class="dg-bar-label"><?= $star ?>★</span>
                    <span class="dg-bar-track"><span class="dg-bar-fill" style="width: <?= $pct ?>%"></span></span>
                    <span class="dg-bar-count"><?= $count ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Thanh tìm kiếm + sắp xếp -->
    <form method="get" action="#danh-gia" class="dg-toolbar">
        <?php foreach (['id', 'type', 'loai', 'PK_Ma', 'NC_Ma', 'ma', 'masp', 'maSP', 'idsp', 'idSP', 'sao'] as $keep): ?>
            <?php if (isset($_GET[$keep]) && $_GET[$keep] !== ''): ?>
                <input type="hidden" name="<?= $h($keep) ?>" value="<?= $h($_GET[$keep]) ?>">
            <?php endif; ?>
        <?php endforeach; ?>

        <h3 class="dg-count">
            <?= $filterStar || $search !== '' ? $totalFiltered . ' kết quả' : 'Đánh giá từ ' . $S['total'] . ' khách hàng' ?>
            <?php if ($filterStar || $search !== ''): ?>
                <a class="dg-clear" href="<?= $h($reviewUrl(['sao' => null, 'q' => null, 'trang' => null])) ?>">Xóa bộ lọc</a>
            <?php endif; ?>
        </h3>

        <div class="dg-toolbar-right">
            <label class="sr-only" for="dg-q">Tìm trong đánh giá</label>
            <input id="dg-q" type="search" name="q" value="<?= $h($search) ?>" placeholder="Tìm trong đánh giá..." class="dg-input">
            <label class="sr-only" for="dg-sx">Sắp xếp</label>
            <select id="dg-sx" name="sx" class="dg-input" onchange="this.form.submit()">
                <option value="moi"  <?= $sort === 'moi'  ? 'selected' : '' ?>>Mới nhất</option>
                <option value="cu"   <?= $sort === 'cu'   ? 'selected' : '' ?>>Cũ nhất</option>
                <option value="cao"  <?= $sort === 'cao'  ? 'selected' : '' ?>>Sao cao nhất</option>
                <option value="thap" <?= $sort === 'thap' ? 'selected' : '' ?>>Sao thấp nhất</option>
            </select>
        </div>
    </form>

    <!-- Danh sách đánh giá -->
    <div class="dg-list">
        <?php if (!$reviews): ?>
            <p class="dg-empty">
                <?= ($filterStar || $search !== '')
                    ? 'Không có đánh giá nào phù hợp với bộ lọc.'
                    : 'Chưa có đánh giá nào. Hãy là người đầu tiên đánh giá sản phẩm này!' ?>
            </p>
        <?php endif; ?>

        <?php foreach ($reviews as $r): ?>
            <article class="dg-item">
                <div class="dg-item-main">
                    <h4 class="dg-item-title"><?= $h($r['DG_TieuDe']) ?></h4>
                    <div class="dg-item-rating">
                        <?= dg_stars((float)$r['DG_SoSao']) ?>
                        <span class="dg-badge"><?= (int)$r['DG_SoSao'] ?></span>
                    </div>
                    <p class="dg-item-text"><?= nl2br($h($r['DG_NoiDung'])) ?></p>
                    <button type="button" class="dg-more" hidden>Xem thêm ▾</button>
                    <?php if ((int)$r['DG_GioiThieu'] === 1): ?>
                        <p class="dg-item-rec">✓ Sẽ giới thiệu sản phẩm này cho bạn bè</p>
                    <?php endif; ?>
                </div>
                <aside class="dg-item-meta">
                    <p><strong>đăng</strong> <?= $h(dg_time_ago($r['DG_NgayTao'])) ?></p>
                    <p><strong>bởi</strong> <?= $h($r['DG_TenHienThi']) ?></p>
                    <?php if (!empty($r['DG_KhuVuc'])): ?>
                        <p><strong>từ</strong> <?= $h($r['DG_KhuVuc']) ?></p>
                    <?php endif; ?>
                    <p class="dg-verified">✔ Đã mua hàng</p>
                </aside>
            </article>
        <?php endforeach; ?>
    </div>

    <!-- Phân trang -->
    <?php if ($totalPages > 1): ?>
        <nav class="dg-pager" aria-label="Phân trang đánh giá">
            <?php if ($page > 1): ?>
                <a href="<?= $h($reviewUrl(['trang' => $page - 1])) ?>">‹ Trước</a>
            <?php else: ?>
                <span class="is-disabled">‹ Trước</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?= $h($reviewUrl(['trang' => $i])) ?>" class="<?= $i === $page ? 'is-current' : '' ?>"
                   <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= $h($reviewUrl(['trang' => $page + 1])) ?>">Sau ›</a>
            <?php else: ?>
                <span class="is-disabled">Sau ›</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

    <!-- Form viết đánh giá -->
    <?php if ($canReview): ?>
        <dialog id="dg-dialog" class="dg-dialog">
            <form method="post" action="danh-gia-gui.php" class="dg-form">
                <input type="hidden" name="csrf" value="<?= $h(dg_csrf_token()) ?>">
                <input type="hidden" name="type" value="<?= $h($type) ?>">
                <input type="hidden" name="product_id" value="<?= (int)$id ?>">
                <input type="hidden" name="back" value="<?= $h($_SERVER['REQUEST_URI']) ?>">

                <div class="dg-form-head">
                    <h3>Viết đánh giá</h3>
                    <button type="button" class="dg-close" id="dg-close" aria-label="Đóng">×</button>
                </div>
                <p class="dg-small"><?= $h($product['NC_Ten']) ?></p>

                <fieldset class="dg-rate">
                    <legend>Bạn chấm mấy sao? *</legend>
                    <div class="dg-rate-stars">
                        <?php for ($s = 5; $s >= 1; $s--): ?>
                            <input type="radio" name="so_sao" id="dg-star-<?= $s ?>" value="<?= $s ?>" required>
                            <label for="dg-star-<?= $s ?>" title="<?= $s ?> sao">★</label>
                        <?php endfor; ?>
                    </div>
                </fieldset>

                <label class="dg-field">Tiêu đề *
                    <input type="text" name="tieu_de" maxlength="150" required class="dg-input">
                </label>

                <label class="dg-field">Nội dung đánh giá *
                    <textarea name="noi_dung" rows="5" minlength="10" maxlength="3000" required class="dg-input"
                              placeholder="Chia sẻ trải nghiệm của bạn: âm thanh, chất lượng, đóng gói..."></textarea>
                </label>

                <div class="dg-form-row">
                    <label class="dg-field">Tên hiển thị *
                        <input type="text" name="ten_hien_thi" maxlength="100" required class="dg-input"
                               value="<?= $h($customer['name']) ?>">
                    </label>
                    <label class="dg-field">Tỉnh/Thành phố
                        <input type="text" name="khu_vuc" maxlength="100" class="dg-input">
                    </label>
                </div>

                <label class="dg-check">
                    <input type="checkbox" name="gioi_thieu" value="1" checked>
                    Tôi sẽ giới thiệu sản phẩm này cho bạn bè
                </label>

                <div class="dg-form-actions">
                    <button type="button" class="dg-btn dg-btn-ghost" id="dg-cancel">Hủy</button>
                    <button type="submit" class="dg-btn">Gửi đánh giá</button>
                </div>
            </form>
        </dialog>
    <?php endif; ?>
</section>

<style>
.dg { --gold:#a28d46; --gold-dark:#8e7938; --bg:#f5f4f1; color:#111; }
.dg-heading { font-size:1.25rem; font-weight:700; padding-bottom:.6rem; border-bottom:2px solid #000; margin-bottom:1rem; }
.dg-flash { padding:.7rem 1rem; margin-bottom:1rem; font-size:.9rem; border-radius:2px; }
.dg-flash-ok { background:#e8f5e9; color:#1b5e20; }
.dg-flash-err { background:#fdecea; color:#b71c1c; }

.dg-summary { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
.dg-box { background:var(--bg); padding:20px; display:flex; flex-direction:column; justify-content:center; gap:6px; min-height:190px; }
.dg-box-center { align-items:center; text-align:center; }
.dg-avg { font-size:2.8rem; font-weight:700; line-height:1; }
.dg-recommend { font-size:2.2rem; font-weight:700; color:#1b5e20; }
.dg-small { font-size:.75rem; color:#444; }
.dg-small-lg { font-size:.85rem; }
.dg-notice { font-size:.78rem; color:#666; max-width:200px; margin-top:6px; }

.dg-stars { color:#f2b01e; letter-spacing:1px; }
.dg-star-off { color:#ddd; }

.dg-btn { display:inline-block; margin-top:8px; padding:9px 22px; background:var(--gold); color:#fff; font-weight:600; font-size:.875rem; border:0; border-radius:3px; cursor:pointer; text-decoration:none; }
.dg-btn:hover { background:var(--gold-dark); }
.dg-btn-ghost { background:#fff; color:#333; border:1px solid #ccc; margin-top:0; }
.dg-btn-ghost:hover { background:#f0f0f0; }

.dg-bar-row { display:grid; grid-template-columns:30px 1fr 24px; align-items:center; gap:8px; padding:3px 4px; font-size:.8rem; text-decoration:none; color:inherit; border-radius:3px; }
.dg-bar-row:hover { background:rgba(0,0,0,.05); }
.dg-bar-row.is-active { background:#efe8cf; font-weight:700; }
.dg-bar-label { font-weight:700; }
.dg-bar-track { height:16px; background:#fff; border-radius:2px; overflow:hidden; }
.dg-bar-fill { display:block; height:100%; background:var(--gold); }
.dg-bar-count { text-align:right; }

.dg-toolbar { display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between; margin-top:16px; padding:10px 14px; background:#e6e4de; }
.dg-count { font-weight:700; font-size:1.05rem; }
.dg-clear { margin-left:10px; font-size:.78rem; font-weight:400; color:var(--gold-dark); text-decoration:underline; }
.dg-toolbar-right { display:flex; gap:8px; flex-wrap:wrap; }
.dg-input { border:1px solid #ccc; background:#fff; border-radius:3px; padding:7px 10px; font-size:.85rem; width:100%; }
.dg-toolbar-right .dg-input { width:auto; }

.dg-item { display:grid; grid-template-columns:minmax(0,1fr) 190px; gap:24px; padding:22px 8px; border-bottom:1px solid #e5e5e5; }
.dg-item-title { font-weight:700; font-size:.95rem; margin-bottom:4px; }
.dg-item-rating { display:flex; align-items:center; gap:6px; margin-bottom:10px; }
.dg-badge { background:#666; color:#fff; font-size:.7rem; padding:0 5px; border-radius:2px; }
.dg-item-text { font-size:.875rem; line-height:1.6; color:#222; overflow:hidden; display:-webkit-box; -webkit-line-clamp:4; -webkit-box-orient:vertical; }
.dg-item-text.is-open { display:block; }
.dg-more { margin-top:6px; font-size:.82rem; font-weight:700; background:none; border:0; padding:0; cursor:pointer; }
.dg-item-rec { margin-top:8px; font-size:.78rem; color:#1b5e20; }
.dg-item-meta { font-size:.75rem; line-height:1.7; color:#444; }
.dg-item-meta strong { color:#111; }
.dg-verified { color:#1b5e20; margin-top:4px; }
.dg-empty { padding:36px 0; text-align:center; color:#666; font-size:.9rem; }

.dg-pager { display:flex; justify-content:center; gap:8px; margin:20px 0 4px; font-size:.85rem; }
.dg-pager a, .dg-pager span { padding:4px 10px; border:1px solid transparent; text-decoration:none; color:#333; }
.dg-pager a:hover { border-color:#ccc; }
.dg-pager .is-current { border-color:#111; font-weight:700; }
.dg-pager .is-disabled { color:#aaa; }

.dg-dialog { border:0; border-radius:4px; padding:0; width:min(560px,92vw); box-shadow:0 10px 40px rgba(0,0,0,.3); }
.dg-dialog::backdrop { background:rgba(0,0,0,.5); }
.dg-form { padding:22px; display:flex; flex-direction:column; gap:14px; }
.dg-form-head { display:flex; justify-content:space-between; align-items:center; }
.dg-form-head h3 { font-size:1.15rem; font-weight:700; }
.dg-close { font-size:1.6rem; line-height:1; background:none; border:0; cursor:pointer; }
.dg-field { display:flex; flex-direction:column; gap:4px; font-size:.85rem; font-weight:600; }
.dg-form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.dg-check { display:flex; gap:8px; align-items:center; font-size:.85rem; }
.dg-form-actions { display:flex; justify-content:flex-end; gap:10px; }
.dg-rate { border:0; padding:0; }
.dg-rate legend { font-size:.85rem; font-weight:600; margin-bottom:4px; }
.dg-rate-stars { display:inline-flex; flex-direction:row-reverse; }
.dg-rate-stars input { position:absolute; opacity:0; pointer-events:none; }
.dg-rate-stars label { font-size:2rem; color:#ddd; cursor:pointer; padding:0 2px; }
.dg-rate-stars label:hover, .dg-rate-stars label:hover ~ label,
.dg-rate-stars input:checked ~ label { color:#f2b01e; }
.dg-rate-stars input:focus-visible + label { outline:2px solid var(--gold); }

@media (max-width: 768px) {
  .dg-summary { grid-template-columns:1fr; }
  .dg-box { min-height:0; }
  .dg-item { grid-template-columns:1fr; gap:10px; }
  .dg-form-row { grid-template-columns:1fr; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // "Xem thêm": chỉ hiện nút khi nội dung bị cắt
    document.querySelectorAll('.dg-item').forEach(function (item) {
        var text = item.querySelector('.dg-item-text');
        var more = item.querySelector('.dg-more');
        if (!text || !more) return;
        if (text.scrollHeight > text.clientHeight + 1) {
            more.hidden = false;
            more.addEventListener('click', function () {
                var open = text.classList.toggle('is-open');
                more.textContent = open ? 'Thu gọn ▴' : 'Xem thêm ▾';
            });
        }
    });

    // Mở/đóng form viết đánh giá
    var dialog = document.getElementById('dg-dialog');
    if (dialog) {
        document.getElementById('dg-open-form').addEventListener('click', function () { dialog.showModal(); });
        ['dg-close', 'dg-cancel'].forEach(function (id) {
            document.getElementById(id).addEventListener('click', function () { dialog.close(); });
        });
        dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });
    }
});
</script>
