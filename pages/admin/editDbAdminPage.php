<?php
require_once '../../php/db.php';
require_once '../../php/loginManager.php';
require_once '../../php/sessionStorage.php';

session_start();
$db = new Db();
$loginManager = new LoginManager($db);
$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;

if(!$loginManager->isLoggedIn() || !$loginManager->isAdmin()){
    header('Location: ../../index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../bootstrap/bootstrap.min.css"/>
    <link rel="stylesheet" href="../../bootstrap/bootstrap-icons.min.css"/>
    <link rel="icon" href="../../assets/images/general/pc.svg" sizes="any" type="image/svg+xml">
    <title>Zarządzanie bazą</title>
</head>
<body>



<!-- Wyświetlanie alertu -->
<?php
SessionStorage::renderAlert();
?>

<!-- "Nawigacja" -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow">
  <div class="container">
    <a class="navbar-brand fw-bold fs-3" href="../../index.php">Z-Kom</a>

    

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav w-100 d-flex align-items-center mt-2 mb-2">

        <!-- Seachbar -->
        <li class="nav-item me-auto px-4 w-100 d-flex align-items-center">
          <form action="../searchPage.php" method="get" class="w-100">
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
          <li class="nav-item"><a class="nav-link" href="../categoryPage.php?category=<?= $category->id ?>"><?= $category->name ?></a></li>
        <?php endforeach; ?>


        <li class="nav-item">
          <a class="nav-link position-relative" href="../cartPage.php">
            <i class="bi bi-cart fs-5"></i>
            <?php if ($cartCount > 0): ?>
              <span class="position-absolute top-80 start-10 translate-middle badge rounded-pill bg-success">
                <?= $cartCount ?>
                <span class="visually-hidden">produkty w koszyku</span>
              </span>
            <?php endif; ?>
          </a>
        </li>

        <li class="nav-item"><a class="nav-link" href="../userPage.php"><i class="bi bi-person fs-5"></i></a></li>

      </ul>
    </div>
  </div>
</nav>

<!-- Opcje -->
<div class="container px-5 py-4"> <!-- Mocny padding-x (px-5) -->
  <div class="row g-4"> <!-- g-4 = odstępy między kolumnami i rzędami -->
    
    <!-- Dodawanie produktu -->
    <div class="col-md-6">
      <div class="card shadow border border-success">
        <div class="card-body">
          <h5 class="card-title">Nowy produkt</h5>
          <p class="card-text">Kliknij poniższy przycisk, aby dodać nowy produkt do bazy.</p>
          <a href="addProductPage.php" class="btn btn-success">Dodaj produkt</a>
        </div>
      </div>
    </div>

    <!-- Zarządzanie kategoriami -->
    <div class="col-md-6">
      <div class="card shadow border border-warning">
        <div class="card-body">
          <h5 class="card-title">Zarządzaj kategoriami</h5>
          <p class="card-text">Kliknij poniższy przycisk, aby zarządzać kategoriami w bazie.</p>
          <a href="editCategoriesPage.php" class="btn btn-warning">Zarządzaj kategoriami</a>
        </div>
      </div>
    </div>

    <!-- Zarządzanie atrybutami -->
    <div class="col-md-6">
      <div class="card shadow border border-warning">
        <div class="card-body">
          <h5 class="card-title">Zarządzaj atrybutami</h5>
          <p class="card-text">Kliknij poniższy przycisk, aby zarządzać atrybutami w bazie.</p>
          <a href="editAttributesPage.php" class="btn btn-warning">Zarządzaj atrybutami</a>
        </div>
      </div>
    </div>

    <!-- Usuwanie recenzji -->
    <div class="col-md-6">
      <div class="card shadow border border-danger">
        <div class="card-body">
          <h5 class="card-title">Usuń recenzje</h5>
          <p class="card-text">Kliknij poniższy przycisk, aby usunąć recenzje produktów.</p>
          <a href="deleteReviewsPage.php" class="btn btn-danger">Usun recenzje</a>
        </div>
      </div>
    </div>

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
 

<script src="../../bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>

