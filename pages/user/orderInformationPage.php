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

if (!isset($_GET['order']) || !is_numeric($_GET['order'])) {
    header('Location: ../../index.php');
    exit;
}

$orderId = (int)$_GET['order'];

if (!$db->doesOrderBelongsToUser($orderId, $user->id)) {
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
$order = $db->getOrderById($orderId);

if ($order->isNew()) {
    $colorClass = 'primary';
} elseif ($order->isCompleted()) {
    $colorClass = 'success';
} elseif ($order->isCancelled()) {
    $colorClass = 'danger';
} else {
    $colorClass = 'secondary';
}
?>

<!-- Szczegóły zamowienia -->
<div class="card shadow my-4 mx-5">
    <div class="card-header bg-<?= $colorClass ?> text-white">
        <h5 class="mb-0">Zamówienie nr #<?= htmlspecialchars($order->id) ?></h5>
    </div>
    <div class="card-body">
        <p><strong>Data złożenia:</strong> <?= htmlspecialchars($order->createdAt) ?></p>
        <p><strong>Status:</strong><span class="mx-2 badge bg-<?= $colorClass ?>"> <?= htmlspecialchars($order->status) ?> </span></p>
        <p><strong>Łączna kwota:</strong> <?= number_format($order->total, 2) ?> zł</p>
        <p><strong>Tracking id:</strong> <?= $order->trackingId ?> </p>
    </div>
    <div class="card-footer text-muted">
        Dziękujemy za zakupy!
    </div>
</div>

<?php
$products = $db->getAllProductsFromOrder($orderId);
?>

<!-- Lista produktów -->
<div class="mt-4">
  <div class="row row-cols-1 row-cols-md-2 mx-5">

    <?php foreach ($products as $item): ?>
      <div class="col mt-2">
        <div class="card h-100 shadow-sm">
          <div class="row g-0 h-100">

            <div class="col-4">
              <?php if (!$item->doesProductExists()): ?>
                <img src="../../assets/images/general/null.png" class="img-fluid rounded-start h-100 object-fit-cover" style="height:350px;width:350px;object-fit: cover" alt="produkt nie istnieje">
              <?php elseif ($item->isImageLocal()): ?>
                <img src="<?= '../' . htmlspecialchars($item->imageUrl) ?>" class="img-fluid rounded-start h-100 object-fit-cover" style="height:350px;width:350px;object-fit: cover" alt="<?= htmlspecialchars($item->name) ?>">
              <?php else: ?>
                <img src="<?= htmlspecialchars($item->imageUrl) ?>" class="img-fluid rounded-start h-100 object-fit-cover" style="height:350px;width:350px;object-fit: cover" alt="<?= htmlspecialchars($item->name) ?>">
              <?php endif; ?>
            </div>

            <div class="col-8">
              <div class="card-body d-flex flex-column h-100">

                <?php if ($item->doesProductExists()): ?>
                  <h5 class="card-title"><?= htmlspecialchars($item->name) ?></h5>
                <?php else: ?>
                  <h5 class="card-title">[ Produkt usunięty ]</h5>
                <?php endif; ?>
                
                <p class="card-text mb-1">
                  Cena za sztukę:
                  <span><?= number_format($item->price, 2) ?> zł</span>
                </p>
                <p class="card-text mb-1">Ilość zamówiona: <?= (int)$item->quantity ?></p>
                <p class="card-text mb-1">
                  Łączna cena:
                  <span class="text-success fw-bold"><?= number_format($item->price * $item->quantity, 2) ?> zł</span>
                </p>

                <?php if ($item->doesProductExists()): ?>
                <div class="mt-3 text-center">
                  <a href="../productPage.php?product=<?= $item->productId ?>" class="btn btn-primary">Szczegóły</a>
                </div>
                <?php endif; ?>

              </div>
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
 

<script src="../../bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>

