<?php
require_once '../php/db.php';
require_once '../php/loginManager.php';
require_once '../php/sessionStorage.php';

session_start();
$db = new Db();
$loginManager = new LoginManager($db);
$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;

if (!$loginManager->isLoggedIn()) {
    header('Location: loginPage.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../bootstrap/bootstrap.min.css"/>
    <link rel="stylesheet" href="../bootstrap/bootstrap-icons.min.css"/>
    <link rel="icon" href="../assets/images/general/pc.svg" sizes="any" type="image/svg+xml">
    <title>Login</title>
</head>
<body>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    $loginManager->logout();
    header("Location: loginPage.php");
    exit;
}
?>


<!-- Wyświetlanie alertu -->
<?php
SessionStorage::renderAlert();
?>


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

        <?php $categories = $db->getAllCategories(); ?>

        <?php foreach ($categories as $category) : ?>
          <li class="nav-item"><a class="nav-link" href="categoryPage.php?category=<?= $category->id ?>"><?= $category->name ?></a></li>
        <?php endforeach; ?>


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

<?php

$userId = $_SESSION['user_id'];
$user = $db->getUserById($userId);

$isAdmin = $loginManager->isAdmin();

$address = null;
if ($user->hasAddress()) {
    $address = $db->getAddressById($user->addressId);
}

?>

<div class="container mt-5">
    <div class="d-flex align-items-center mb-4">

        <!-- Witaj -->
        <div class="avatar-placeholder me-4">
            <span>👤</span>
        </div>
        <h2>Witaj, <?= htmlspecialchars($user->name) ?>!</h2>

        <!-- Karta Wyloguj się -->
        <div class="w-25 ms-auto">
            <form method="post" action="">
                <button type="submit" name="logout" class="btn btn-danger w-100">Wyloguj się</button>
            </form>
        </div>

    </div>

    <div class="row g-4">

        <!-- Zamówienia -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Zamówienia</h5>
                    <p class="card-text">Tutaj możesz zobaczyć swoje zamówienia.</p>
                    <a href="./user/ordersPage.php" class="btn btn-primary">Zobacz zamówienia</a>
                </div>
            </div>
        </div>

        <!-- Adres -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Adres</h5>
                    <?php if ($address): ?>
                        <p class="card-text">
                            <?= 'ul. '.htmlspecialchars($address->street).' '.htmlspecialchars($address->houseNumber) ?><br>
                            <?= htmlspecialchars($address->city) ?>, <?= htmlspecialchars($address->postalCode) ?><br>
                            <?= htmlspecialchars($address->country) ?>
                        </p>
                    <?php else: ?>
                        <p class="card-text text-muted">Nie dodano adresu.</p>
                    <?php endif; ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addressModal">
                        Edytuj adres
                    </button>
                </div>
            </div>
        </div>

        <!-- Edycja bazy -->
        <?php if ($isAdmin): ?>
        <div class="col-md-4">
            <div class="card shadow-sm border-warning">
                <div class="card-body">
                    <h5 class="card-title text-warning">Edytuj bazę produktów</h5>
                    <p class="card-text">Masz uprawnienia administratora, możesz zarządzać produktami.</p>
                    <a href="./admin/editDbAdminPage.php" class="btn btn-warning">Przejdź do panelu</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Rejestracja użytkownika -->
        <?php if ($isAdmin): ?>
        <div class="col-md-4">
            <div class="card shadow-sm border-warning">
                <div class="card-body">
                    <h5 class="card-title text-warning">Zarejestruj nowego użytkownika</h5><br>
                    <a href="registerPage.php" class="btn btn-warning">Przejdź do panelu</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

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
 


<!-- Modal z formularzem adresu -->
<div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="./user/changeAddressPage.php">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addressModalLabel">Edytuj adres</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
        </div>
        <div class="modal-body">

          <div class="mb-3">
            <label for="street" class="form-label">Ulica</label>
            <input type="text" class="form-control" id="street" name="street" value="<?= htmlspecialchars($address->street ?? '') ?>" maxlength="255" required>
          </div>

          <div class="mb-3">
            <label for="houseNumber" class="form-label">Numer domu</label>
            <input type="text" class="form-control" id="houseNumber" name="house_number" value="<?= htmlspecialchars($address->houseNumber ?? '') ?>" maxlength="10" required>
          </div>

          <div class="mb-3">
            <label for="city" class="form-label">Miasto</label>
            <input type="text" class="form-control" id="city" name="city" value="<?= htmlspecialchars($address->city ?? '') ?>" maxlength="255" required>
          </div>

          <div class="mb-3">
            <label for="postalCode" class="form-label">Kod pocztowy</label>
            <input type="text" class="form-control" id="postalCode" name="postal_code" value="<?= htmlspecialchars($address->postalCode ?? '') ?>" maxlength="20" required>
          </div>

          <div class="mb-3">
            <label for="country" class="form-label">Kraj</label>
            <input type="text" class="form-control" id="country" name="country" value="<?= htmlspecialchars($address->country ?? '') ?>" maxlength="255" required>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
          <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
        </div>
      </div>
    </form>
  </div>
</div>



<script src="../bootstrap/bootstrap.bundle.min.js"></script>
<script>
  const toastEl = document.getElementById('statusToast');
  if (toastEl) {
    const bsToast = new bootstrap.Toast(toastEl, { delay: 5000 });
    bsToast.show();
  }
</script>
</body>
</html>

