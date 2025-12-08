<?php
include 'components/connection.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = '';
}

if (isset($_POST['logout'])) {
    session_destroy();
    header("location: login.php");
    exit;
}

//  Thêm sản phẩm vào Wishlist
if (isset($_POST['add_to_wishlist'])) {
    if ($user_id == '') {
        header("location: login.php");
        exit;
    }

    $product_id = $_POST['product_id'];

    $verify_wishlist = $conn->prepare("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?");
    $verify_wishlist->execute([$user_id, $product_id]);

    $verify_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $verify_cart->execute([$user_id, $product_id]);

    if ($verify_wishlist->rowCount() > 0) {
        $warning_msg[] = 'Sản phẩm đã có trong danh sách yêu thích';
    } elseif ($verify_cart->rowCount() > 0) {
        $warning_msg[] = 'Sản phẩm đã có trong giỏ hàng';
    } else {
        // 🔹 Sửa: Kiểm tra sản phẩm tồn tại trước khi lấy giá
        $select_price = $conn->prepare("SELECT price FROM products WHERE id = ? AND status = 'active' LIMIT 1");
        $select_price->execute([$product_id]);
        if ($select_price->rowCount() > 0) {
            $fetch_price = $select_price->fetch(PDO::FETCH_ASSOC);

            $insert_wishlist = $conn->prepare("INSERT INTO wishlist (user_id, product_id, price) VALUES (?, ?, ?)");
            $insert_wishlist->execute([$user_id, $product_id, $fetch_price['price']]);
            $success_msg[] = 'Đã thêm sản phẩm vào danh sách yêu thích thành công';
        } else {
            $warning_msg[] = 'Sản phẩm không tồn tại!';
        }
    }
}

//  Thêm sản phẩm vào Giỏ hàng
if (isset($_POST['add_to_cart'])) {
    if ($user_id == '') {
        header("location: login.php");
        exit;
    }

    $product_id = $_POST['product_id'];
    $qty = isset($_POST['qty']) && $_POST['qty'] > 0 ? $_POST['qty'] : 1;
    $qty = filter_var($qty, FILTER_SANITIZE_NUMBER_INT);

    $verify_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $verify_cart->execute([$user_id, $product_id]);

    $max_cart_items = $conn->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
    $max_cart_items->execute([$user_id]);
    $count_items = $max_cart_items->fetchColumn();

    if ($verify_cart->rowCount() > 0) {
        $warning_msg[] = 'Sản phẩm đã có trong giỏ hàng';
    } elseif ($count_items >= 20) {
        $warning_msg[] = 'Giỏ hàng của bạn đã đầy (tối đa 20 sản phẩm)';
    } else {
        // 🔹 Sửa: Kiểm tra sản phẩm tồn tại trước khi lấy giá
        $select_price = $conn->prepare("SELECT price FROM products WHERE id = ? AND status = 'active' LIMIT 1");
        $select_price->execute([$product_id]);
        if ($select_price->rowCount() > 0) {
            $fetch_price = $select_price->fetch(PDO::FETCH_ASSOC);

            $insert_cart = $conn->prepare("INSERT INTO cart (user_id, product_id, price, qty) VALUES (?, ?, ?, ?)");
            $insert_cart->execute([$user_id, $product_id, $fetch_price['price'], $qty]);
            $success_msg[] = 'Đã thêm sản phẩm vào giỏ hàng thành công';
        } else {
            $warning_msg[] = 'Sản phẩm không tồn tại!';
        }
    }
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
  <title>Green Coffee - Trang cửa hàng</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>

<body>
  <?php include 'components/header.php'; ?>

  <div class="main">
    <div class="banner">
      <h1>Cửa hàng</h1>
    </div>

    <div class="title2">
      <a href="home.php">Trang chủ</a><span> / Cửa hàng của chúng tôi</span>
    </div>
<section class="products">
  <!-- Thanh tìm kiếm Live -->
  <div class="search-container">
   <div class="search-wrapper">
  <input type="text" id="live_search" name="search" autocomplete="off"
         placeholder="Tìm kiếm sản phẩm..." 
         value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
         class="search-input">
  <button type="button" class="search-btn-inside">
    <i class="bx bx-search"></i>
  </button>
</div>

    <!-- Khu vực hiển thị gợi ý -->
    <div id="search_result"></div>
  </div>

  <!-- Hiển thị kết quả tìm kiếm (nếu có) -->
  <?php if (isset($_GET['search']) && !empty(trim($_GET['search']))): ?>
    <div class="search-result">
      Kết quả tìm kiếm cho: <strong>"<?= htmlspecialchars($_GET['search']) ?>"</strong>
    </div>
  <?php endif; ?>

  <!-- Danh sách sản phẩm -->
  <div class="box-container" id="product_container">
    <?php
    $search = '';
    if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
        $search = trim($_GET['search']);
        $select_products = $conn->prepare("SELECT * FROM products WHERE name LIKE ? AND status = 'active' ORDER BY name ASC");
        $select_products->execute(['%' . $search . '%']);
    } else {
        $select_products = $conn->prepare("SELECT * FROM products WHERE status = 'active' ORDER BY name ASC");
        $select_products->execute();
    }

    if ($select_products->rowCount() > 0) {
      while ($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)) {
    ?>
        <form action="" method="post" class="box">
          <img src="img/<?= htmlspecialchars($fetch_products['image']); ?>" class="img">
          <div class="button">
            <button type="submit" name="add_to_cart"><i class="bx bx-cart"></i></button>
            <button type="submit" name="add_to_wishlist"><i class="bx bx-heart"></i></button>
            <a href="view_page.php?pid=<?= $fetch_products['id']; ?>" class="bx bxs-show"></a>
          </div>
          <h3 class="name"><?= htmlspecialchars($fetch_products['name']); ?></h3>
          <input type="hidden" name="product_id" value="<?= $fetch_products['id']; ?>">
          <div class="flex">
            <p class="price">Giá: <?= number_format($fetch_products['price']); ?> VND</p>
            <input type="number" name="qty" min="1" max="99" value="1" class="qty">
          </div>
          <a href="checkout.php?get_id=<?= $fetch_products['id']; ?>" class="btn">Mua ngay</a>
        </form>
    <?php
      }
    } else {
      echo '<p class="empty">Không tìm thấy sản phẩm nào!</p>';
    }
    ?>
  </div>
</section>

    <?php include 'components/footer.php'; ?>
  </div>

  <!-- Scripts -->
 <script>
// Live Search + Hỗ trợ Enter và click gợi ý
const liveSearch = document.getElementById('live_search');
const resultBox = document.getElementById('search_result');
const searchForm = document.getElementById('search_form');

liveSearch.addEventListener('keyup', function(e) {
    let keyword = this.value.trim();

    if (keyword === '') {
        resultBox.style.display = 'none';
        return;
    }

    fetch(`search.php?keyword=${encodeURIComponent(keyword)}`)
        .then(res => res.text())
        .then(data => {
            resultBox.innerHTML = data;
            resultBox.style.display = data.trim() ? 'block' : 'none';
        });
});

// Khi nhấn Enter → submit form bình thường (tìm kiếm chính thức)
searchForm.addEventListener('submit', function(e) {
    let keyword = liveSearch.value.trim();
    if (keyword === '') {
        e.preventDefault(); // Không submit nếu rỗng
    }
    // Nếu có từ khóa → để form GET bình thường → reload trang với ?search=...
});

// Ẩn kết quả khi click ra ngoài
document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-container')) {
        resultBox.style.display = 'none';
    }
});

// Giữ hộp gợi ý mở khi di chuột vào
resultBox.addEventListener('click', function(e) {
    e.stopPropagation(); // Ngăn ẩn khi click vào gợi ý
});
</script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
  <script src="script.js"></script>
  <?php include 'components/alert.php'; ?>
</body>
</html>