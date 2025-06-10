<?php
require_once './php/db.php';
require_once './php/loginManager.php';

session_start();
$db = new Db();
$loginManager = new LoginManager($db);
$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="bootstrap/bootstrap.min.css"/>
    <link rel="stylesheet" href="bootstrap/bootstrap-icons.min.css"/>
    <link rel="stylesheet" href="css/indexPage.css"/>
    <link rel="icon" href="assets/images/general/pc.svg" sizes="any" type="image/svg+xml">
    <title>Z-kom</title>
</head>
<body>

<!-- "Nawigacja" -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow">
  <div class="container">
    <a class="navbar-brand fw-bold fs-3" href="../index.php">Z-Kom</a>

    

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav w-100 d-flex align-items-center mt-2 mb-2">

        <!-- Seachbar -->
        <li class="nav-item me-auto px-4 w-100 d-flex align-items-center">
          <form action="./pages/searchPage.php" method="get" class="w-100">
            <div class="input-group">
              <input type="text" name="search" class="form-control" placeholder="Wyszukaj produkt" aria-label="Wyszukaj produkt" aria-describedby="button-addon2">
              <button class="btn btn-secondary" type="submit" id="button-addon2">
                <i class="bi bi-search"></i>
              </button>
            </div>
          </form>
        </li>

        <?php $categories = $db->getAllCategories(); ?>

        <?php foreach ($categories as $category) : ?>
          <li class="nav-item"><a class="nav-link" href="./pages/categoryPage.php?category=<?= $category->id ?>"><?= $category->name ?></a></li>
        <?php endforeach; ?>

        <li class="nav-item">
          <a class="nav-link position-relative" href="./pages/cartPage.php">
            <i class="bi bi-cart fs-5"></i>
            <?php if ($cartCount > 0): ?>
              <span class="position-absolute top-80 start-10 translate-middle badge rounded-pill bg-success">
                <?= $cartCount ?>
                <span class="visually-hidden">produkty w koszyku</span>
              </span>
            <?php endif; ?>
          </a>
        </li>

        <li class="nav-item"><a class="nav-link" href="./pages/userPage.php"><i class="bi bi-person fs-5"></i></a></li>

      </ul>
    </div>
  </div>
</nav>

<!-- Nagłówek -->
<div class="p-4">
  <header class="text-black rounded text-center d-flex align-items-center justify-content-center main-hero" style="height: 500px">
    <div>
      <h1 class="display-1 fw-bold">Witaj w Z-Kom!</h1><br>
      <p class="lead fw-bold">Najlepsza elektronika w najlepszych cenach od 10 lat!</p>
    </div>
  </header>
</div>

<!-- Lepsza karuzela -->
<div id="carouselExampleDark" class="carousel carousel-dark slide" data-bs-ride="carousel">
  <div class="carousel-indicators">
    <button type="button" data-bs-target="#carouselExampleDark" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
    <button type="button" data-bs-target="#carouselExampleDark" data-bs-slide-to="1" aria-label="Slide 2"></button>
    <button type="button" data-bs-target="#carouselExampleDark" data-bs-slide-to="2" aria-label="Slide 3"></button>
  </div>
  <div class="carousel-inner">
    
    <div class="carousel-item active" data-bs-interval="5000">
      <img src="assets/images/indexPage/delivery.jpg" height="240px" style="object-fit: cover;" class="d-block w-100" alt="...">
      <div class="carousel-caption">
        <div class="p-3 border rounded shadow-sm" style="background-color: rgba(255, 255, 255, 0.5);">
          <i class="bi bi-box-seam fs-1 text-primary"></i>
          <h5 class="mt-2 fw-bold">1 000 000+</h5>
          <p class="mb-0">zrealizowanych zamówień</p>
        </div>
      </div>
    </div>

    <div class="carousel-item" data-bs-interval="5000">
      <img src="assets/images/indexPage/clients.jpg" height="240px" style="object-fit: cover;" class="d-block w-100" alt="...">
      <div class="carousel-caption">
        <div class="p-3 border rounded shadow-sm" style="background-color: rgba(255, 255, 255, 0.5);">
          <i class="bi bi-emoji-smile fs-1 text-success"></i>
          <h5 class="mt-2 fw-bold">96%</h5>
          <p class="mb-0">zadowolonych klientów</p>
        </div>
      </div>
    </div>

    <div class="carousel-item">
      <img src="assets/images/indexPage/company.jpg" height="240px" style="object-fit: cover;object-position: center center;" class="d-block w-100" alt="...">
      <div class="carousel-caption">
        <div class="p-3 border rounded shadow-sm" style="background-color: rgba(255, 255, 255, 0.5);">
          <i class="bi bi-award fs-1 text-warning"></i>
          <h5 class="mt-2 fw-bold">10 lat</h5>
          <p class="mb-0">na rynku elektroniki</p>
        </div>
      </div>
    </div>

  </div>
  <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleDark" data-bs-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Previous</span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleDark" data-bs-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Next</span>
  </button>
</div>

<!-- Opinie klientów -->
<section class="container my-5">
  <h2 class="text-center mb-4">Opinie naszych klientów</h2>
  <div class="row g-4">

    <?php 
    $reviews = $db->getTop3StoreReviews(); 
    ?>
    <?php foreach ($reviews as $review): ?>
    <div class="col-md-4">

      <?php if ($loginManager->isLoggedIn() && (int)$_SESSION['user_id'] === $review->userId): ?>
        <div class="card h-100 shadow-sm bg-success bg-opacity-50">
      <?php else: ?>
        <div class="card h-100 shadow-sm">
      <?php endif; ?>

        <div class="card-body position-relative">
          <small class="text-muted position-absolute" style="top: 10px; right: 15px; font-size: 0.8rem;">
            <?= htmlspecialchars(date('Y-m-d', strtotime($review->createdAt ?? ''))) ?>
          </small>
          <h5 class="card-title"><?= htmlspecialchars($review->name ?? 'Unknown') ?></h5>
          <h6 class="card-subtitle mb-2 text-muted">
            <?= str_repeat('★', (int)$review->rating) . str_repeat('☆', 5 - (int)$review->rating) ?>
          </h6>
          <p class="card-text"><?= nl2br(htmlspecialchars($review->comment ?? '')) ?></p>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

  </div>
</section>

<!-- Stopka -->
<footer class="py-3 my-4">
    <ul class="nav justify-content-center border-bottom pb-3 mb-3">
        <li class="nav-item"><a href="#" class="nav-link px-2 text-body-secondary">Strona główna</a></li> 
        <li class="nav-item"><a href="#" class="nav-link px-2 text-body-secondary">Płatność i dostawa</a></li> 
        <li class="nav-item"><a href="#" class="nav-link px-2 text-body-secondary">FAQ</a></li> 
        <li class="nav-item"><a href="#" class="nav-link px-2 text-body-secondary">O nas</a></li> 
    </ul> <p class="text-center text-body-secondary">© 2025 Z-Kom, Inc</p> 
</footer>

<script src="bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>

