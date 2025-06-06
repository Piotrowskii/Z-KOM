<?php
require_once '../../php/db.php';
require_once '../../php/loginManager.php';

session_start();
$db = new Db();
$loginManager = new LoginManager($db);
$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;

if(!$loginManager->isLoggedIn()){
    header('Location: ../../index.php');
    exit;
}

$user = $loginManager->getLoggedInUser();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../bootstrap/bootstrap.min.css"/>
    <link rel="stylesheet" href="../../bootstrap/bootstrap-icons.min.css"/>
    <link rel="icon" href="../../assets/images/general/pc.svg" sizes="any" type="image/svg+xml">
    <title>Produkt</title>
</head>
<body>

<?php

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
          <form action="searchPage.php" method="get" class="w-100">
            <div class="input-group">
              <input type="text" name="search" class="form-control" placeholder="Wyszukaj produkt" aria-label="Wyszukaj produkt" aria-describedby="button-addon2">
              <button class="btn btn-secondary" type="submit" id="button-addon2">
                <i class="bi bi-search"></i>
              </button>
            </div>
          </form>
        </li>

        <li class="nav-item"><a class="nav-link" href="../laptopPage.php">Laptopy</a></li>
        <li class="nav-item"><a class="nav-link" href="../smartphonePage.php">Smartfony</a></li>
        <li class="nav-item"><a class="nav-link" href="../computerPage.php">Komputery</a></li>
        <li class="nav-item"><a class="nav-link" href="../monitorPage.php">Monitory</a></li>


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


<?php
$orders = $db->getOrdersByUserId($user->id);
?>
<!-- Lista zamówien -->
<div class="container mt-4">
  <h2 class="mb-4">Twoje zamówienia</h2>

  <?php if (empty($orders)): ?>
    <div class="alert alert-info">Nie masz jeszcze żadnych zamówień.</div>

  <?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 g-4">
      <?php foreach ($orders as $order): ?>
        <div class="col">

          <!-- Ładne formatowanie obramówki -->
          <div class="card h-100 shadow-sm border-<?php 
              if ($order->isNew()) echo 'primary';
              elseif ($order->isCompleted()) echo 'success';
              elseif ($order->isCancelled()) echo 'danger';
              else echo 'secondary';
            ?>">

            <div class="card-body">
              <h5 class="card-title">Zamówienie #<?= $order->id ?></h5>
              <p class="card-text mb-1"><strong>Status:</strong> 

                <!-- Ładne formatowanie statusu -->
                <?php if ($order->isNew()): ?>
                  <span class="badge bg-primary">Nowe</span>
                <?php elseif ($order->isCompleted()): ?>
                  <span class="badge bg-success">Zrealizowane</span>
                <?php elseif ($order->isCancelled()): ?>
                  <span class="badge bg-danger">Anulowane</span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?= htmlspecialchars($order->status) ?></span>
                <?php endif; ?>

              </p>
              <p class="card-text mb-1"><strong>Data:</strong> <?= date('d.m.Y H:i', strtotime($order->createdAt)) ?></p>
              <p class="card-text"><strong>Kwota:</strong> <?= number_format($order->total, 2) ?> zł</p>
            </div>
            <div class="card-footer text-center">
              <a href="orderInformationPage.php?order=<?= $order->id ?>" class="btn btn-outline-primary btn-sm">Szczegóły</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
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

