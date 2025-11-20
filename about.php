<?php
include 'components/connection.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = '';
}

// 🔹 Xử lý đăng xuất
if (isset($_POST['logout'])) {
    session_destroy();
    header("location: login.php");
    exit;
}
?>
<style type="text/css">
  <?php include 'style.css'; ?>
</style>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Green Coffee - Về Chúng Tôi</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>

<body>
  <?php include 'components/header.php'; ?>
  <div class="main">
    <div class="banner">
        <h1>Về Chúng Tôi</h1>
    </div>
    <div class="title2">
    <a href="home.php">Trang Chủ</a><span>/ Về Chúng Tôi</span>
    </div>
    <div class="about-category">
        <div class="box">
        <img src="img/3.webp" alt="Cà Phê Chanh Xanh">
        <div class="detail">
        <span>Cà phê</span>
        <h1>Chanh Xanh</h1>
        <a href="view_products.php" class="btn">Mua Ngay</a>
        </div>
        </div>
         <div class="box">
        <img src="img/2.webp" alt="Trà Chanh">
        <div class="detail">
        <span>Cà phê</span>
        <h1>Tên Trà Chanh</h1>
        <a href="view_products.php" class="btn">Mua Ngay</a>
        </div>
        </div>
          <div class="box">
        <img src="img/1.webp" alt="Chanh Xanh">
        <div class="detail">
        <span>Cà phê</span>
        <h1>Chanh Xanh</h1>
        <a href="view_products.php" class="btn">Mua Ngay</a>
        </div>
        </div>
         <div class="box">
        <img src="img/1.webp" alt="Chanh Xanh">
        <div class="detail">
        <span>Cà phê</span>
        <h1>Chanh Xanh</h1>
        <a href="view_products.php" class="btn">Mua Ngay</a>
        </div>
        </div>
    </div>
     <section class="services">
        <div class="title">
        <img src="img/download.png" class="logo" alt="Logo Green Coffee">
       <h1>Tại Sao Chọn Chúng Tôi</h1>
  <p>Trải nghiệm nhanh – dịch vụ tận tâm – chất lượng vượt trội.</p>

        <div class="box-container">
        <div class="box">
        <img src="img/icon2.png" alt="Tiết Kiệm Lớn">
        <div class="detail">
        <h3>Tiết kiệm lớn</h3>
        <p>Tiết kiệm lớn mỗi đơn hàng</p>
        </div>
        </div>
         <div class="box">
        <img src="img/icon1.png" alt="Hỗ Trợ 24/7">
        <div class="detail">
        <h3>Hỗ trợ 24/7</h3>
        <p>Hỗ trợ một-một</p>
        </div>
        </div>
         <div class="box">
        <img src="img/icon0.png" alt="Phiếu Quà Tặng">
        <div class="detail">
        <h3>Phiếu quà tặng</h3>
        <p>Phiếu quà trên mọi lễ hội</p>
        </div>
        </div>
         <div class="box">
        <img src="img/icon.png" alt="Giao Hàng Toàn Cầu">
        <div class="detail">
        <h3>Giao hàng toàn cầu</h3>
        <p>Giao hàng toàn thế giới</p>
        </div>
        </div>
        </div>
      </section>
      <div class="about">
        <div class="row">
        <div class="img-box">
          <img src="img/3.png" alt="Showroom Của Chúng Tôi">
        </div>
        <div class="detail">
          <h1>Thăm showroom đẹp của chúng tôi!</h1>
          <p>Showroom của chúng tôi là biểu hiện của những gì chúng tôi yêu thích; sáng tạo với sắp xếp hoa và cây cối.
            Dù bạn đang tìm kiếm một nhà cung cấp hoa cho đám cưới hoàn hảo của bạn, hay chỉ 
            muốn nâng tầm bất kỳ phòng nào với một số trang trí sống độc đáo, Bloss with love có thể giúp
          </p>
          <a href="view_products.php" class="btn">Mua Ngay</a>
        </div>
        </div>
      </div>
      <div class="testimonial-container">
      <div class="title">
        <img src="img/download.png" class="logo" alt="Logo Green Coffee">
        <h1>Mọi Người Nói Gì Về Chúng Tôi</h1>
<p>“Đơn giản là tuyệt vời! Không thể tìm thấy dịch vụ nào tốt hơn đâu!”</p>

      </div>
<div class="container">
    <?php
    // 🔹 Lấy 3 testimonials từ bảng message, JOIN với users để lấy ảnh profile (order mới nhất)
    $select_testimonials = $conn->prepare("
        SELECT m.*, u.profile_image 
        FROM message m 
        LEFT JOIN users u ON m.user_id = u.id 
        ORDER BY m.id DESC LIMIT 3
    ");
    $select_testimonials->execute();
    $testimonials = $select_testimonials->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($testimonials)) {
        foreach ($testimonials as $index => $testimonial): 
            // 🔹 Lấy ảnh từ profile_image nếu có, fallback placeholder theo index
            $img_src = !empty($testimonial['profile_image']) ? $testimonial['profile_image'] : "img/0" . ($index + 1) . ".jpg";
            $active_class = ($index == 0) ? 'active' : ''; // Chỉ slide đầu active
    ?>
            <div class="testimonial-item <?= $active_class; ?>">
                <img src="<?= htmlspecialchars($img_src); ?>" alt="<?= htmlspecialchars($testimonial['name']); ?>">
                <h1><?= htmlspecialchars($testimonial['name']); ?></h1>
                <p><?= htmlspecialchars($testimonial['message']); ?></p>
            </div>
    <?php 
        endforeach; 
    } else {
        // Fallback nếu không có data (hiển thị static như cũ)
        echo '<div class="testimonial-item active">
                <img src="img/01.jpg" alt="Sara Smith">
                <h1>Sara Smith</h1>
                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
              </div>';
    }
    ?>
    <div class="left-arrow" onclick="nextSlide()"><i class="bx bxs-left-arrow"></i></div>
    <div class="right-arrow" onclick="prevSlide()"><i class="bx bxs-right-arrow"></i></div>
</div>
      </div>
    <?php include 'components/footer.php'; ?>
  </div>
  <!-- Scripts -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
  <script src="script.js"></script>
  <?php include 'components/alert.php'; ?>
</body>

</html>