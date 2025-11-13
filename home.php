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

// 🔹 Lấy sản phẩm trending (active, order by id DESC - giả sử mới nhất là trending)
$trending_products = [];
$select_products = $conn->prepare("SELECT * FROM products WHERE status = 'active' ORDER BY id DESC LIMIT 6");
$select_products->execute();
$trending_products = $select_products->fetchAll(PDO::FETCH_ASSOC);
?>
<style type="text/css">
  <?php include 'style.css'; ?>
</style>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Green Coffee - Trang chủ</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>

<body>
  <?php include 'components/header.php'; ?>
  <div class="main">
    <section class="home-section">
      <div class="slider">
        <!-- Slide 1 -->
        <div class="slider__slider slider1">
          <div class="overlay"></div>
          <div class="slider-detail">
            <h1>Hạt Cà Phê Xanh Cao Cấp</h1>
            <p>Khám phá hạt cà phê được nguồn gốc bền vững từ vùng cao nguyên.</p>
            <a href="view_products.php" class="btn">Mua Ngay</a>
          </div>
          <div class="hero-dec-top"></div>
          <div class="hero-dec-bottom"></div>
        </div>

        <!-- Slide 2 -->
        <div class="slider__slider slider2">
          <div class="overlay"></div>
          <div class="slider-detail">
            <h1>Chào Mừng Đến Với Green Coffee</h1>
            <p>Trải nghiệm hương vị tươi mới, đậm đà của cà phê hữu cơ.</p>
            <a href="view_products.php" class="btn">Mua Ngay</a>
          </div>
          <div class="hero-dec-top"></div>
          <div class="hero-dec-bottom"></div>
        </div>

        <!-- Slide 3 -->
        <div class="slider__slider slider3">
          <div class="overlay"></div>
          <div class="slider-detail">
            <h1>Sản Phẩm Bán Chạy</h1>
            <p>Các hỗn hợp hàng đầu dành cho mọi người yêu cà phê.</p>
            <a href="view_products.php" class="btn">Mua Ngay</a>
          </div>
          <div class="hero-dec-top"></div>
          <div class="hero-dec-bottom"></div>
        </div>

        <!-- Slide 4 -->
        <div class="slider__slider slider4">
          <div class="overlay"></div>
          <div class="slider-detail">
            <h1>Rang Xay Tươi Hôm Nay</h1>
            <p>Rang xay hoàn hảo, giao đến tận cửa nhà bạn.</p>
            <a href="view_products.php" class="btn">Mua Ngay</a>
          </div>
          <div class="hero-dec-top"></div>
          <div class="hero-dec-bottom"></div>
        </div>

        <!-- Slide 5 -->
        <div class="slider__slider slider5">
          <div class="overlay"></div>
          <div class="slider-detail">
            <h1>Tham Gia Cộng Đồng Của Chúng Tôi</h1>
            <p>Đăng ký để nhận mẹo, công thức và ưu đãi độc quyền.</p>
            <a href="view_products.php" class="btn">Mua Ngay</a>
          </div>
          <div class="hero-dec-top"></div>
          <div class="hero-dec-bottom"></div>
        </div>

        <!-- Arrows với accessibility -->
        <div class="left-arrow"><i class='bx bxs-left-arrow'></i></div>
        <div class="right-arrow"><i class='bx bxs-right-arrow'></i></div>
      </div>
    </section>
    <!-----home slider end--->
      <section class="thumb">
        <div class="box-container">
        <div class="box">
          <img src="img/thumb2.jpg" alt="Trà Xanh">
          <h3>Trà Xanh</h3>
           <p>Đăng ký để nhận mẹo, công thức và ưu đãi độc quyền.</p>
           <i class="bx bx-chevron-right"></i>
        </div>
        <div class="box">
          <img src="img/thumb0.jpg" alt="Trà Chanh">
          <h3>Trà Chanh</h3>
           <p>Đăng ký để nhận mẹo, công thức và ưu đãi độc quyền.</p>
           <i class="bx bx-chevron-right"></i>
        </div>
        <div class="box">
          <img src="img/thumb1.jpg" alt="Cà Phê Xanh">
          <h3>Cà Phê Xanh</h3>
           <p>Đăng ký để nhận mẹo, công thức và ưu đãi độc quyền.</p>
           <i class="bx bx-chevron-right"></i>
        </div>
        <div class="box">
          <img src="img/thumb.jpg" alt="Trà Xanh">
          <h3>Trà Xanh</h3>
           <p>Đăng ký để nhận mẹo, công thức và ưu đãi độc quyền.</p>
           <i class="bx bx-chevron-right"></i>
        </div>
        </div>
      </section>
      <section class="container">
        <div class="box-container">
        <div class="box">
          <img src="img/about-us.jpg" alt="Về Chúng Tôi">
        </div>
        <div class="box">
          <img src="img/download.png" alt="Cà Phê Lành Mạnh">
          <span>Cà phê lành mạnh</span>
          <h1>Tiết kiệm đến 50%</h1>
          <p>Đăng ký để nhận mẹo, công thức và ưu đãi độc quyền.</p>
        </div>
        </div>
      </section>
      <section class="shop">
      <div class="title">
       <img src="img/download.png" alt="Logo">
       <h1>Sản phẩm nổi bật</h1> 
      </div>
      <div class="row">
      <img src="img/about.jpg" alt="Giới thiệu">
      <div class="row-detail">
        <img src="img/basil.jpg" alt="Basil">
        <div class="top-footer">
          <h1>Một tách cà phê xanh giúp bạn khỏe mạnh</h1>
        </div>
      </div>
      </div>
      <div class="box-container">
      <?php if (!empty($trending_products)): ?>
        <?php foreach ($trending_products as $product): ?>
        <div class="box">
          <img src="img/<?= htmlspecialchars($product['image']); ?>" alt="<?= htmlspecialchars($product['name']); ?>">
          <a href="view_products.php" class="btn">Mua Ngay</a>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="empty">Chưa có sản phẩm nào!</p>
      <?php endif; ?>
      </div>
      </section>
      <section class="shop-category">
      <div class="box-container">
      <div class="box">
      <img src="img/6.jpg" alt="Ưu Đãi Lớn">
      <div class="detail">
        <span>ƯU ĐÃI LỚN</span>
      <h1>Giảm thêm 15%</h1>
      <a href="view_products.php" class="btn">Mua Ngay</a>
      </div>
      </div>  
      <div class="box">
      <img src="img/7.jpg" alt="Hương Vị Mới">
      <div class="detail">
        <span>Hương vị mới</span>
      <h1>Nhà Cà Phê</h1>
      <a href="view_products.php" class="btn">Mua Ngay</a>
      </div>
      </div>  
      </div>
      </section>
      <section class="services">
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
      <section class="brand">
        <div class="box-container">
          <div class="box">
           <img src="img/brand (1).jpg" alt="Thương Hiệu 1"> 
          </div>
           <div class="box">
           <img src="img/brand (2).jpg" alt="Thương Hiệu 2"> 
          </div>
           <div class="box">
           <img src="img/brand (3).jpg" alt="Thương Hiệu 3"> 
          </div>
           <div class="box">
           <img src="img/brand (4).jpg" alt="Thương Hiệu 4"> 
          </div>
           <div class="box">
           <img src="img/brand (5).jpg" alt="Thương Hiệu 5"> 
          </div>
        </div>
      </section>
     <?php include 'components/footer.php'; ?>
  </div>
 
  <!-- Scripts -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
  <script src="script.js"></script>
  <?php include 'components/alert.php'; ?>
</body>

</html>