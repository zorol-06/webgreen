<?php
include 'components/connection.php';
session_start();
if(isset($_SESSION['user_id'])){
        $user_id = $_SESSION['user_id'];
    }
    else{
      $user_id='';
    }
    if(isset($_POST['logout'])){
        session_destroy();
        header("location: login.php");
    }
    if(isset($_POST['logout'])){
      session_destroy();
      header("location: login.php");
    }
?>
<style type="text/css">
  <?php include 'style.css'; ?>
</style>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Green Coffee - Home Page</title>
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
            <h1>Premium Green Coffee Beans</h1>
            <p>Discover our sustainably sourced beans from the highlands.</p>
            <a href="view_products.php" class="btn">Shop Now</a>
          </div>
          <div class="hero-dec-top"></div>
          <div class="hero-dec-bottom"></div>
        </div>

        <!-- Slide 2 -->
        <div class="slider__slider slider2">
          <div class="overlay"></div>
          <div class="slider-detail">
            <h1>Welcome to Green Coffee</h1>
            <p>Experience the fresh, bold flavors of organic coffee.</p>
            <a href="view_products.php" class="btn">Shop Now</a>
          </div>
          <div class="hero-dec-top"></div>
          <div class="hero-dec-bottom"></div>
        </div>

        <!-- Slide 3 -->
        <div class="slider__slider slider3">
          <div class="overlay"></div>
          <div class="slider-detail">
            <h1>Best Sellers</h1>
            <p>Our top-rated blends for every coffee lover.</p>
            <a href="view_products.php" class="btn">Shop Now</a>
          </div>
          <div class="hero-dec-top"></div>
          <div class="hero-dec-bottom"></div>
        </div>

        <!-- Slide 4 -->
        <div class="slider__slider slider4">
          <div class="overlay"></div>
          <div class="slider-detail">
            <h1>Fresh Roast Today</h1>
            <p>Roasted to perfection, delivered to your door.</p>
            <a href="view_products.php" class="btn">Shop Now</a>
          </div>
          <div class="hero-dec-top"></div>
          <div class="hero-dec-bottom"></div>
        </div>

        <!-- Slide 5 -->
        <div class="slider__slider slider5">
          <div class="overlay"></div>
          <div class="slider-detail">
            <h1>Join Our Community</h1>
            <p>Subscribe for tips, recipes, and exclusive offers.</p>
            <a href="view_products.php" class="btn">Shop Now</a>
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
          <img src="img/thumb2.jpg">
          <h3>green tea</h3>
           <p>Subscribe for tips, recipes, and exclusive offers.</p>
           <i class="bx bx-chevron-right"></i>
        </div>
        <div class="box">
          <img src="img/thumb0.jpg">
          <h3>lemon tea</h3>
           <p>Subscribe for tips, recipes, and exclusive offers.</p>
           <i class="bx bx-chevron-right"></i>
        </div>
        <div class="box">
          <img src="img/thumb1.jpg">
          <h3>green coffee</h3>
           <p>Subscribe for tips, recipes, and exclusive offers.</p>
           <i class="bx bx-chevron-right"></i>
        </div>
        <div class="box">
          <img src="img/thumb.jpg">
          <h3>green tea</h3>
           <p>Subscribe for tips, recipes, and exclusive offers.</p>
           <i class="bx bx-chevron-right"></i>
        </div>
        </div>
      </section>
      <section class="container">
        <div class="box-container">
        <div class="box">
          <img src="img/about-us.jpg">
        </div>
        <div class="box">
          <img src="img/download.png">
          <span>healthy tea</span>
          <h1>save up to 50% off</h1>
          <p>Subscribe for tips, recipes, and exclusive offers.</p>
        </div>
        </div>
      </section>
      <section class="shop">
      <div class=" title">
       <img src = "img/download.png">
       <h1>Trending products</h1> 
      </div>
      <div class="row">
      <img src="img/about.jpg">
      <div class="row-detail">
        <img src="img/basil.jpg">
        <div class="top-footer">
          <h1>a cup of green tea makes you healthy </h1>
        </div>
      </div>
      </div>
      <div class="box-container">
      <div class="box">
        <img src="img/card.jpg">
        <a href="view_products.php" class="btn">shop now</a>
      </div>
      <div class="box">
        <img src="img/card0.jpg">
        <a href="view_products.php" class="btn">shop now</a>
      </div>
      <div class="box">
        <img src="img/card1.jpg">
        <a href="view_products.php" class="btn">shop now</a>
      </div>
      <div class="box">
        <img src="img/card2.jpg">
        <a href="view_products.php" class="btn">shop now</a>
      </div>
      <div class="box">
        <img src="img/10.jpg">
        <a href="view_products.php" class="btn">shop now</a>
      </div>
      <div class="box">
        <img src="img/6.webp">
        <a href="view_products.php" class="btn">shop now</a>
      </div>
      </div>
      </section>
      <section class="shop-category">
      <div class="box-container">
      <div class="box">
      <img src="img/6.jpg">
      <div class="detail">
        <span>BIG OFFFES</span>
      <h1>Extra 15% off</h1>
      <a href="view_products.php" class="btn">shop now</a>
      </div>
      </div>  
      <div class="box">
      <img src="img/7.jpg">
      <div class="detail">
        <span>new in taste</span>
      <h1>coffee house</h1>
      <a href="view_products.php" class="btn">shop now</a>
      </div>
      </div>  
      </div>
      </section>
      <section class="services">
        <div class="box-container">
        <div class="box">
        <img src="img/icon2.png">
        <div class="detail">
        <h3> gerat savings</h3>
        <p>save big every order</p>
        </div>
        </div>
         <div class="box">
        <img src="img/icon1.png">
        <div class="detail">
        <h3>24*7 support</h3>
        <p>one-on-one support</p>
        </div>
        </div>
         <div class="box">
        <img src="img/icon0.png">
        <div class="detail">
        <h3>gift vouchers</h3>
        <p>vouchers on every festivals</p>
        </div>
        </div>
         <div class="box">
        <img src="img/icon.png">
        <div class="detail">
        <h3>worldwide delivery</h3>
        <p>dropship worldwide</p>
        </div>
        </div>
        </div>
      </section>
      <section class="brand">
        <div class="box-container">
          <div class="box">
           <img src="img/brand (1).jpg"> 
          </div>
           <div class="box">
           <img src="img/brand (2).jpg"> 
          </div>
           <div class="box">
           <img src="img/brand (3).jpg"> 
          </div>
           <div class="box">
           <img src="img/brand (4).jpg"> 
          </div>
           <div class="box">
           <img src="img/brand (5).jpg"> 
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