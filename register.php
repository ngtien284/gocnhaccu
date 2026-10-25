<?php
// register.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/db.php';

$page_title = "Đăng Ký Tài Khoản | GOCNHACCU";
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['signupPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    
    $street = trim($_POST['streetAddress'] ?? '');
    $ward = trim($_POST['ward'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $full_address = implode(', ', array_filter([$street, $ward, $district, $city]));

    // Regex kiểm tra mật khẩu mạnh
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';

    if (!preg_match($pattern, $password)) {
        $error_message = 'Mật khẩu phải dài ít nhất 8 ký tự và bao gồm cả chữ hoa, chữ thường, số và ký tự đặc biệt!';
    } elseif ($password !== $confirmPassword) {
        $error_message = 'Mật khẩu xác nhận không khớp!';
    } else {
        try {
            // 1. Kiểm tra Email đã tồn tại chưa
            $checkStmt = $pdo->prepare("SELECT ND_Ma FROM nguoi_dung WHERE ND_Email = ?");
            $checkStmt->execute([$email]);
            
            if ($checkStmt->rowCount() > 0) {
                $error_message = 'Email này đã được đăng ký tài khoản khác!';
            } else {
                // 2. Lập truy vấn INSERT theo tên cột thực tế: ND_Ten, ND_Email, ND_MatKhau, ND_SDT, ND_NgaySinh, ND_DiaChi, LND_Ma
                $sql = "INSERT INTO nguoi_dung (ND_Ten, ND_Email, ND_MatKhau, ND_SDT, ND_NgaySinh, ND_DiaChi, LND_Ma, ND_TrangThai) 
                        VALUES (?, ?, ?, ?, ?, ?, 2, 'Hoạt động')";
                $insertStmt = $pdo->prepare($sql);
                $insertStmt->execute([$fullName, $email, $password, $phone, $dob, $full_address]);

                // 3. Đăng nhập ngay sau khi đăng ký
                $_SESSION['user'] = $email;
                $_SESSION['user_name'] = !empty($fullName) ? $fullName : 'Thành viên mới';
                $_SESSION['user_avatar'] = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80';
                
                $_SESSION['registered_phone'] = $phone;
                $_SESSION['registered_dob'] = $dob;
                $_SESSION['registered_address'] = $full_address;

                echo "<script>
                    alert('Đăng ký tài khoản thành công! Chào mừng bạn đến với GOCNHACCU.');
                    window.location.href = 'index.php';
                </script>";
                exit;
            }
        } catch (PDOException $e) {
            $error_message = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title) ?></title>
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- FontAwesome Icon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  
  <style>
    :root {
      --wood-primary: #8C5835;
      --wood-dark: #754728;
      --wood-bg: #FBF8F5;
      --wood-border: #E7E0D8;
      --wood-text: #2D2319;
    }
    
    body {
      background-color: var(--wood-bg);
      color: var(--wood-text);
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
      min-height: 100vh;
    }

    .btn-wood {
      background-color: var(--wood-primary);
      color: #FFFFFF;
      border: none;
    }
    .btn-wood:hover {
      background-color: var(--wood-dark);
      color: #FFFFFF;
    }

    .form-control:focus, .form-select:focus {
      border-color: var(--wood-primary);
      box-shadow: 0 0 0 0.25rem rgba(140, 88, 53, 0.2);
    }

    .select-location {
      font-size: 0.75rem !important;
      padding-left: 0.75rem;
      padding-right: 1.75rem;
      text-overflow: ellipsis;
      white-space: nowrap;
      overflow: hidden;
    }
  </style>
</head>

<body class="d-flex align-items-center justify-content-center py-5">
  <main class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-md-9 col-lg-7 col-xl-6">
        
        <div class="bg-white rounded-4 p-4 p-md-5 shadow-sm border" style="border-color: var(--wood-border) !important;">
          
          <div class="text-center mb-4">
            <a href="index.php" class="text-decoration-none">
              <span class="fs-3 fw-bold font-serif" style="color: var(--wood-text);">GOCNHACCU</span>
            </a>
            <h1 class="fs-5 fw-bold text-dark mt-2">Tạo tài khoản thành viên mới</h1>
            <p class="text-muted small">Cùng khám phá bộ sưu tập nhạc cụ thủ công và ưu đãi VIP</p>
          </div>

          <!-- THÔNG BÁO LỖI PHÍA SERVER -->
          <div id="errorAlert" class="alert alert-danger py-2 text-center small mb-4 <?= empty($error_message) ? 'd-none' : '' ?>" role="alert">
            <?= htmlspecialchars($error_message) ?>
          </div>

          <form class="signup-form" method="POST" action="">
            
            <div class="row g-3 mb-3">
              <div class="col-12 col-md-6">
                <label for="fullName" class="form-label small fw-bold text-secondary">Họ và tên</label>
                <input class="form-control rounded-pill px-3 py-2 text-sm" type="text" id="fullName" name="fullName" placeholder="Nhập họ và tên" required />
              </div>

              <div class="col-12 col-md-6">
                <label for="dob" class="form-label small fw-bold text-secondary">Ngày sinh</label>
                <input class="form-control rounded-pill px-3 py-2 text-sm" type="date" id="dob" name="dob" required />
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-12 col-md-6">
                <label for="email" class="form-label small fw-bold text-secondary">Email</label>
                <input class="form-control rounded-pill px-3 py-2 text-sm" type="email" id="email" name="email" placeholder="Nhập email" required />
              </div>

              <div class="col-12 col-md-6">
                <label for="phone" class="form-label small fw-bold text-secondary">Số điện thoại</label>
                <input class="form-control rounded-pill px-3 py-2 text-sm" type="tel" id="phone" name="phone" placeholder="Nhập số điện thoại" required />
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold text-secondary">Địa chỉ nhận hàng chi tiết</label>
              
              <div class="row g-2">
                <div class="col-12 col-sm-4">
                  <select class="form-select rounded-pill py-2 select-location" id="city" name="city" required>
                    <option value="" selected disabled>-- Tỉnh/Thành --</option>
                  </select>
                </div>

                <div class="col-12 col-sm-4">
                  <select class="form-select rounded-pill py-2 select-location" id="district" name="district" required disabled>
                    <option value="" selected disabled>-- Quận/Huyện --</option>
                  </select>
                </div>

                <div class="col-12 col-sm-4">
                  <select class="form-select rounded-pill py-2 select-location" id="ward" name="ward" required disabled>
                    <option value="" selected disabled>-- Phường/Xã --</option>
                  </select>
                </div>
              </div>

              <div class="mt-2">
                <input class="form-control rounded-pill px-3 py-2 text-sm" type="text" id="streetAddress" name="streetAddress"
                  placeholder="Số nhà, tên đường cụ thể (Ví dụ: 123 Nguyễn Huệ)" required />
              </div>
            </div>

            <div class="row g-3 mb-4">
              <div class="col-12 col-md-6">
                <label for="signupPassword" class="form-label small fw-bold text-secondary">Tạo mật khẩu</label>
                <div class="position-relative">
                  <input class="form-control rounded-pill px-3 py-2 pe-5 text-sm" type="password" id="signupPassword" name="signupPassword" placeholder="Nhập mật khẩu" required autocomplete="off" />
                  <i class="password-toggle fa-solid fa-eye position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer text-muted" data-target="signupPassword" role="button"></i>
                </div>
                <div class="form-text text-muted" style="font-size: 0.7rem; line-height: 1.2; margin-top: 4px; padding-left: 10px;">
                  Mật khẩu từ 8 ký tự trở lên, gồm chữ hoa, chữ thường, số và ký tự đặc biệt.
                </div>
              </div>

              <div class="col-12 col-md-6">
                <label for="confirmPassword" class="form-label small fw-bold text-secondary">Xác nhận mật khẩu</label>
                <div class="position-relative">
                  <input class="form-control rounded-pill px-3 py-2 pe-5 text-sm" type="password" id="confirmPassword" name="confirmPassword" placeholder="Nhập lại mật khẩu" required autocomplete="off" />
                  <i class="password-toggle fa-solid fa-eye position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer text-muted" data-target="confirmPassword" role="button"></i>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <button type="button" id="btnRegister" class="btn btn-wood w-100 rounded-pill py-2.5 fw-bold shadow-sm">
                Đăng Ký Tài Khoản
              </button>
            </div>

            <div class="text-center">
              <span class="small text-muted">Đã có tài khoản? </span>
              <a href="login.php" class="small fw-bold text-decoration-none" style="color: var(--wood-primary);">Đăng nhập ngay</a>
            </div>

          </form>

        </div>
      </div>
    </div>
  </main>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      // 1. Ẩn/Hiện mật khẩu
      const toggleIcons = document.querySelectorAll('.password-toggle');
      toggleIcons.forEach(icon => {
        icon.addEventListener('click', function () {
          const targetId = this.getAttribute('data-target');
          const input = document.getElementById(targetId);
          if (input) {
            if (input.type === 'password') {
              input.type = 'text';
              this.classList.remove('fa-eye');
              this.classList.add('fa-eye-slash');
            } else {
              input.type = 'password';
              this.classList.remove('fa-eye-slash');
              this.classList.add('fa-eye');
            }
          }
        });
      });

      // 2. Kiểm tra mật khẩu bằng JS trước khi Submit
      const btnRegister = document.getElementById('btnRegister');
      const signupForm = document.querySelector('.signup-form');
      const errorAlert = document.getElementById('errorAlert');

      btnRegister.addEventListener('click', function () {
        const password = document.getElementById('signupPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;

        if (!passwordRegex.test(password)) {
          errorAlert.textContent = 'Mật khẩu phải dài ít nhất 8 ký tự và bao gồm cả chữ hoa, chữ thường, số và ký tự đặc biệt!';
          errorAlert.classList.remove('d-none');
          document.getElementById('signupPassword').focus();
          return;
        }

        if (password !== confirmPassword) {
          errorAlert.textContent = 'Mật khẩu xác nhận không khớp!';
          errorAlert.classList.remove('d-none');
          document.getElementById('confirmPassword').focus();
          return;
        }

        errorAlert.classList.add('d-none');
        signupForm.submit();
      });

      // 3. API Tỉnh/Thành
      const citySelect = document.getElementById("city");
      const districtSelect = document.getElementById("district");
      const wardSelect = document.getElementById("ward");

      const fallbackProvinces = [
        { name: "TP. Hồ Chí Minh", code: 79 },
        { name: "TP. Hà Nội", code: 1 },
        { name: "TP. Cần Thơ", code: 92 },
        { name: "TP. Đà Nẵng", code: 48 },
        { name: "Tỉnh Bình Dương", code: 74 },
        { name: "Tỉnh Đồng Nai", code: 75 },
        { name: "Tỉnh An Giang", code: 89 },
        { name: "Tỉnh Sơn La", code: 14 }
      ];

      function loadProvinces() {
        fetch("https://provinces.open-api.vn/api/p/")
          .then(response => {
            if (!response.ok) throw new Error("Network response was not ok");
            return response.json();
          })
          .then(data => {
            populateSelect(citySelect, data, "-- Tỉnh/Thành --");
          })
          .catch(err => {
            populateSelect(citySelect, fallbackProvinces, "-- Tỉnh/Thành --");
          });
      }

      function populateSelect(selectElement, items, defaultText) {
        selectElement.innerHTML = `<option value="" selected disabled>${defaultText}</option>`;
        items.forEach(item => {
          let opt = document.createElement("option");
          opt.value = item.name;
          opt.text = item.name;
          opt.dataset.code = item.code;
          selectElement.appendChild(opt);
        });
      }

      loadProvinces();

      citySelect.addEventListener("change", function () {
        const selectedOpt = citySelect.options[citySelect.selectedIndex];
        const cityCode = selectedOpt.dataset.code;
        
        districtSelect.innerHTML = '<option value="" selected disabled>-- Quận/Huyện --</option>';
        wardSelect.innerHTML = '<option value="" selected disabled>-- Phường/Xã --</option>';
        districtSelect.disabled = true;
        wardSelect.disabled = true;

        if (cityCode) {
          fetch(`https://provinces.open-api.vn/api/p/${cityCode}?depth=2`)
            .then(res => res.json())
            .then(data => {
              if (data.districts && data.districts.length > 0) {
                populateSelect(districtSelect, data.districts, "-- Quận/Huyện --");
                districtSelect.disabled = false;
              }
            })
            .catch(err => {
              populateSelect(districtSelect, [{name: "Quận 1", code: 1}, {name: "Quận Ninh Kiều", code: 2}], "-- Quận/Huyện --");
              districtSelect.disabled = false;
            });
        }
      });

      districtSelect.addEventListener("change", function () {
        const selectedOpt = districtSelect.options[districtSelect.selectedIndex];
        const districtCode = selectedOpt.dataset.code;
        
        wardSelect.innerHTML = '<option value="" selected disabled>-- Phường/Xã --</option>';
        wardSelect.disabled = true;

        if (districtCode) {
          fetch(`https://provinces.open-api.vn/api/d/${districtCode}?depth=2`)
            .then(res => res.json())
            .then(data => {
              if (data.wards && data.wards.length > 0) {
                populateSelect(wardSelect, data.wards, "-- Phường/Xã --");
                wardSelect.disabled = false;
              }
            })
            .catch(err => {
              populateSelect(wardSelect, [{name: "Phường Bến Nghé"}, {name: "Phường An Khánh"}], "-- Phường/Xã --");
              wardSelect.disabled = false;
            });
        }
      });
    });
  </script>
</body>

</html>