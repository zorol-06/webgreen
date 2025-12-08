<?php
include 'components/connection.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = '';
    header("location: login.php");
    exit;
}

// 🔹 Xử lý đăng xuất
if (isset($_POST['logout'])) {
    session_destroy();
    header("location: login.php");
    exit;
}

// 🔹 Lấy thông tin người dùng
$select_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
$select_user->execute([$user_id]);
$user = $select_user->fetch(PDO::FETCH_ASSOC);

// 🔹 Lấy tất cả đơn hàng của người dùng
$select_orders = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY date DESC");
$select_orders->execute([$user_id]);
$orders = $select_orders->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Đếm số đơn hàng theo trạng thái
$pending_orders = 0;
$completed_orders = 0;
$shipped_orders = 0;
$delivered_orders = 0;

foreach ($orders as $order) {
    switch ($order['status']) {
        case 'pending':
            $pending_orders++;
            break;
        case 'completed':
            $completed_orders++;
            break;
        case 'shipped':
            $shipped_orders++;
            break;
        case 'delivered':
            $delivered_orders++;
            break;
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
  <title>Green Coffee - Hồ Sơ Của Tôi</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <style>
    .profile-container {
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 30px;
    }

    @media (max-width: 768px) {
        .profile-container {
            grid-template-columns: 1fr;
        }
    }

    .profile-sidebar {
        background: #fff;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .profile-content {
        background: #fff;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .profile-header {
        display: flex;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f0f0f0;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        overflow: hidden;
        margin-right: 25px;
        border: 5px solid #2ecc71;
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-info h2 {
        margin: 0 0 10px 0;
        color: #333;
        font-size: 24px;
    }

    .profile-info p {
        margin: 5px 0;
        color: #666;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin-bottom: 25px;
    }

    .stat-card {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        border-left: 4px solid #2ecc71;
    }

    .stat-card h3 {
        margin: 0 0 10px 0;
        font-size: 14px;
        color: #666;
        text-transform: uppercase;
    }

    .stat-number {
        font-size: 28px;
        font-weight: bold;
        color: #2c3e50;
    }

    .profile-actions {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 25px;
    }

    .btn-profile {
        display: inline-block;
        padding: 12px 25px;
        background: #3498db;
        color: white;
        text-decoration: none;
        border-radius: 6px;
        text-align: center;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        font-size: 16px;
    }

    .btn-profile:hover {
        background: #2980b9;
        transform: translateY(-2px);
    }

    .btn-change-password {
        background: #2ecc71;
    }

    .btn-change-password:hover {
        background: #27ae60;
    }

    .btn-logout {
        background: #e74c3c;
    }

    .btn-logout:hover {
        background: #c0392b;
    }

    .orders-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .orders-table th {
        background: #2c3e50;
        color: white;
        padding: 15px;
        text-align: left;
    }

    .orders-table td {
        padding: 15px;
        border-bottom: 1px solid #eee;
    }

    .orders-table tr:hover {
        background: #f9f9f9;
    }

    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .status-pending { background: #f39c12; color: white; }
    .status-completed { background: #2ecc71; color: white; }
    .status-shipped { background: #3498db; color: white; }
    .status-delivered { background: #9b59b6; color: white; }

    .section-title {
        font-size: 22px;
        margin-bottom: 20px;
        color: #2c3e50;
        padding-bottom: 10px;
        border-bottom: 2px solid #2ecc71;
    }

    .no-orders {
        text-align: center;
        padding: 40px;
        color: #7f8c8d;
        font-size: 18px;
    }

    .no-orders i {
        font-size: 50px;
        margin-bottom: 15px;
        display: block;
        color: #bdc3c7;
    }

    .order-link {
        color: #3498db;
        text-decoration: none;
        font-weight: bold;
    }

    .order-link:hover {
        text-decoration: underline;
    }
  </style>
</head>

<body>
  <?php include 'components/header.php'; ?>
  
  <div class="main">
    <div class="banner">
      <h1>Hồ Sơ Của Tôi</h1>
    </div>

    <div class="title2">
      <a href="home.php">Trang chủ</a><span> / Hồ Sơ Của Tôi</span>
    </div>

    <div class="profile-container">
      <!-- Sidebar thông tin cá nhân -->
      <div class="profile-sidebar">
        <div class="profile-header">
          <div class="profile-avatar">
            <?php if (!empty($user['profile_image'])): ?>
              <img src="<?= htmlspecialchars($user['profile_image']); ?>" alt="Ảnh đại diện">
            <?php else: ?>
              <img src="img/default-avatar.jpg" alt="Ảnh đại diện mặc định">
            <?php endif; ?>
          </div>
          <div class="profile-info">
            <h2><?= htmlspecialchars($user['name']); ?></h2>
            <p><i class='bx bx-envelope'></i> <?= htmlspecialchars($user['email']); ?></p>
            <p><i class='bx bx-user'></i> Thành viên từ: <?= date('d/m/Y', strtotime($user['created_at'] ?? 'now')); ?></p>
          </div>
        </div>

        <div class="stats-grid">
          <div class="stat-card">
            <h3>Tổng đơn hàng</h3>
            <div class="stat-number"><?= count($orders); ?></div>
          </div>
          <div class="stat-card">
            <h3>Đang xử lý</h3>
            <div class="stat-number"><?= $pending_orders; ?></div>
          </div>
          <div class="stat-card">
            <h3>Đã giao</h3>
            <div class="stat-number"><?= $delivered_orders; ?></div>
          </div>
       
        </div>

        <div class="profile-actions">
       
          <a href="change_password.php" class="btn-profile btn-change-password">
            <i class='bx bx-key'></i> Đổi mật khẩu
          </a>
          <form method="post" style="margin: 0;">
            <button type="submit" name="logout" class="btn-profile btn-logout">
              <i class='bx bx-log-out'></i> Đăng xuất
            </button>
          </form>
        </div>
      </div>

      <!-- Nội dung chính - Lịch sử đơn hàng -->
      <div class="profile-content">
        <h2 class="section-title">
          <i class='bx bx-history'></i> Lịch sử đơn hàng
        </h2>

        <?php if (count($orders) > 0): ?>
          <div style="overflow-x: auto;">
            <table class="orders-table">
              <thead>
                <tr>
                  <th>Mã đơn hàng</th>
                  <th>Ngày đặt</th>
                  <th>Sản phẩm</th>
                  <th>Số lượng</th>
                  <th>Tổng tiền</th>
                  <th>Trạng thái</th>
                  <th>Thanh toán</th>
                  <th>Chi tiết</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $order): ?>
                  <tr>
                    <td>#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($order['date'])); ?></td>
                    <td>
                      <?php 
                      // Lấy tên sản phẩm từ product_id
                      $select_product = $conn->prepare("SELECT name FROM products WHERE id = ?");
                      $select_product->execute([$order['product_id']]);
                      $product = $select_product->fetch(PDO::FETCH_ASSOC);
                      echo htmlspecialchars($product['name'] ?? 'N/A');
                      ?>
                    </td>
                    <td><?= $order['qty']; ?></td>
                    <td><?= number_format($order['final_price'] > 0 ? $order['final_price'] : $order['price'] * $order['qty'], 0, ',', '.'); ?>₫</td>
                    <td>
                      <span class="status-badge status-<?= $order['status']; ?>">
                        <?php 
                        $status_text = [
                          'pending' => 'Đang xử lý',
                          'shipped' => 'Đang giao',
                          'delivered' => 'Đã giao',
                          'completed' => 'Hoàn thành'
                        ];
                        echo $status_text[$order['status']] ?? $order['status'];
                        ?>
                      </span>
                    </td>
                    <td>
                      <?php if ($order['payment_status'] == 'complete'): ?>
                        <span style="color: #2ecc71; font-weight: bold;">Đã thanh toán</span>
                      <?php else: ?>
                        <span style="color: #e74c3c; font-weight: bold;">Chưa thanh toán</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <a href="order_status.php" class="order-link">
                        <i class='bx bx-show'></i> Xem
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="no-orders">
            <i class='bx bx-package'></i>
            <p>Bạn chưa có đơn hàng nào</p>
            <a href="view_products.php" class="btn-profile" style="margin-top: 20px; display: inline-block;">
              <i class='bx bx-shopping-bag'></i> Mua sắm ngay
            </a>
          </div>
        <?php endif; ?>

        <!-- Thông tin bổ sung -->
        <div style="margin-top: 40px; background: #f8f9fa; padding: 20px; border-radius: 8px;">
          <h3 style="margin-top: 0; color: #2c3e50;">
            <i class='bx bx-info-circle'></i> Thông tin tài khoản
          </h3>
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div>
              <p><strong>Loại tài khoản:</strong> 
                <?= $user['user_type'] == 'admin' ? 'Quản trị viên' : 'Thành viên'; ?>
              </p>
            </div>
            <div>
              <p><strong>Địa chỉ giao hàng mặc định:</strong></p>
              <p style="color: #666; font-size: 14px;">
                <?php 
                // Lấy địa chỉ từ đơn hàng gần nhất
                if (count($orders) > 0) {
                  echo htmlspecialchars($orders[0]['address']);
                } else {
                  echo "Chưa có địa chỉ";
                }
                ?>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
      
    <?php include 'components/footer.php'; ?>
  </div>
 
  <!-- Scripts -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
  <script src="script.js"></script>
  <?php include 'components/alert.php'; ?>
  
  <script>
    // Xác nhận đăng xuất
    document.querySelector('button[name="logout"]').addEventListener('click', function(e) {
      if (!confirm('Bạn có chắc chắn muốn đăng xuất?')) {
        e.preventDefault();
      }
    });
  </script>
</body>

</html>