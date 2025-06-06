<?php
require_once '../php/db.php';
require_once '../php/loginManager.php';

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
    <link rel="stylesheet" href="../bootstrap/bootstrap.min.css"/>
    <link rel="stylesheet" href="../bootstrap/bootstrap-icons.min.css"/>
    <link rel="icon" href="../assets/images/general/pc.svg" sizes="any" type="image/svg+xml">
    <title>Komputery</title>
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
          <form action="searchPage.php" method="get" class="w-100">
            <div class="input-group">
              <input type="text" name="search" class="form-control" placeholder="Wyszukaj produkt" aria-label="Wyszukaj produkt" aria-describedby="button-addon2">
              <button class="btn btn-secondary" type="submit" id="button-addon2">
                <i class="bi bi-search"></i>
              </button>
            </div>
          </form>
        </li>

        <li class="nav-item"><a class="nav-link" href="laptopPage.php">Laptopy</a></li>
        <li class="nav-item"><a class="nav-link" href="smartphonePage.php">Smartfony</a></li>
        <li class="nav-item"><a class="nav-link" href="computerPage.php">Komputery</a></li>
        <li class="nav-item"><a class="nav-link" href="monitorPage.php">Monitory</a></li>


        <li class="nav-item">
          <a class="nav-link position-relative" href="cartPage.php">
            <i class="bi bi-cart fs-5"></i>
            <?php if ($cartCount > 0): ?>
              <span class="position-absolute top-80 start-10 translate-middle badge rounded-pill bg-success">
                <?= $cartCount ?>
                <span class="visually-hidden">produkty w koszyku</span>
              </span>
            <?php endif; ?>
          </a>
        </li>

        <li class="nav-item"><a class="nav-link" href="userPage.php"><i class="bi bi-person fs-5"></i></a></li>

      </ul>
    </div>
  </div>
</nav>


<!-- Nagłówek -->
<div class="my-5">
    <h1 class="text-center">Komputery</h1>
</div>

<!-- Sortowanie -->
 <form method="GET" class="w-50 mx-auto mb-4">
  <div class="input-group">
    <label class="input-group-text" for="sort">Sortuj:</label>
    <select class="form-select" id="sort" name="sort" onchange="this.form.submit()">
      <option value="rating" <?= ($_GET['sort'] ?? '') === 'rating' ? 'selected' : '' ?>>Najbardziej polecane</option>
      <option value="price_asc" <?= ($_GET['sort'] ?? '') === 'price_asc' ? 'selected' : '' ?>>Od najtańszego</option>
      <option value="price_desc" <?= ($_GET['sort'] ?? '') === 'price_desc' ? 'selected' : '' ?>>Od najdroższego</option>
      <option value="random" <?= ($_GET['sort'] ?? '') === 'random' ? 'selected' : '' ?>>Losowo</option>
    </select>
  </div>
</form>


<!-- Produkty -->
<?php
$sort = $_GET['sort'] ?? 'rating';

switch ($sort) {
    case 'price_asc':
        $orderBy = 'ORDER BY final_price ASC';
        break;
    case 'price_desc':
        $orderBy = 'ORDER BY final_price DESC';
        break;
    case 'rating':
        $orderBy = 'ORDER BY rating DESC';
        break;
    case 'random':
        $orderBy = 'ORDER BY RANDOM()';
        break;
}

$products = $db->getAllProductsFromCategory(3,$orderBy);
?>

<div class="container mt-4">
  <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
    <?php foreach ($products as $product): ?>
      <div class="col">
        <div class="card h-100">
          <?php if ($product->imageUrl): ?>
            <img src="<?= htmlspecialchars($product->imageUrl) ?>" class="card-img-top" style="height: 250px;object-fit: cover;" alt="<?= htmlspecialchars($product->name) ?>">
          <?php endif; ?>
          <div class="card-body">
            <div class="d-flex justify-content-between">
                <h5 class="card-title"><?= htmlspecialchars($product->name) ?></h5>
                <p><?="{$product->rating} ". str_repeat('★', (int)$product->rating) . str_repeat('☆', 5 - (int)$product->rating) ?></p>
            </div>
            <p class="card-text"><?= htmlspecialchars($product->brand) ?></p>
            <div class="d-flex align-items-center justify-content-between gap-2">
              <div>
                <?php if($product->hasDiscount()): ?>
                <p class="card-text text-muted text-decoration-line-through mb-0" style="font-size: 0.9em;"><?= $product->getFormattedPrice() ?></p>
                <?php endif; ?>
                <p class="card-text fw-bold text-primary"><?= $product->getFormattedFinalPrice() ?></p>
              </div>
                <a href="productPage.php?product=<?= $product->id ?>" class="btn btn-secondary me-0">Szczegóły</a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

  </div>
</div>


<!-- Stopka -->
<footer class="py-3 my-4">
    <ul class="nav justify-content-center border-bottom pb-3 mb-3">
        <li class="nav-item"><a href="#" class="nav-link px-2 text-body-secondary">Strona główna</a></li> 
        <li class="nav-item"><a href="#" class="nav-link px-2 text-body-secondary">Płatność i dostawa</a></li> 
        <li class="nav-item"><a href="#" class="nav-link px-2 text-body-secondary">FAQ</a></li> 
        <li class="nav-item"><a href="#" class="nav-link px-2 text-body-secondary">O nas</a></li> 
    </ul> <p class="text-center text-body-secondary">© 2025 Z-Kom, Inc</p> 
</footer>
 

<script src="../bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>

