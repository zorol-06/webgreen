<?php
include 'components/connection.php';

$output = '';

if (isset($_GET['keyword'])) {
    $keyword = trim($_GET['keyword']);
    if ($keyword !== '') {
        $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ? AND status = 'active' LIMIT 8");
        $stmt->execute(['%' . $keyword . '%']);
        
        if ($stmt->rowCount() > 0) {
            $output .= '<div class="search-dropdown">';
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $output .= '
                <a href="view_page.php?pid=' . $row['id'] . '" class="search-item">
                  <img src="img/' . htmlspecialchars($row['image']) . '" alt="">
                  <div class="search-info">
                    <div class="search-name">' . htmlspecialchars($row['name']) . '</div>
                    <div class="search-price">$ ' . number_format($row['price']) . '</div>
                  </div>
                </a>';
            }
            $output .= '</div>';
        } else {
            $output = '<div class="search-dropdown"><div class="no-result">Không tìm thấy sản phẩm</div></div>';
        }
    }
}
echo $output;
?>