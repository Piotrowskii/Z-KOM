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
    <title>Edytowanie przecen</title>
</head>
<body>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $discountId = $_POST['discount_id'] ?? null;
    $action = $_POST['action'] ?? null;
    $name = $_POST['name'] ?? null;
    $discountPercent = $_POST['discount_percent'] ?? null;
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;

    if(isset($_POST['active'])) $active = true;
    else $active = false;

    // Zapisywanie przeceny
    if ($action === 'save') {
        if ($name === null || trim($name) === '') {
            SessionStorage::sendAlert("Nazwa przeceny nie może być pusta.", "danger");
        }
        else if (!$discountId || !is_numeric($discountId)) {
            SessionStorage::sendAlert("Nieprawidłowe ID przeceny.", "danger");
        } 
        else if($discountPercent < 0 || $discountPercent > 100){
            SessionStorage::sendAlert("Podano nie prawidłowy procent przeceny", "danger");
        }
        else if (strtotime($endDate) <= strtotime($startDate)) {
            SessionStorage::sendAlert("Data zakończenia musi być późniejsza niż data rozpoczęcia.", "danger");
        }
        else {
            $success = $db->updateDiscount((int)$discountId,trim($name),$discountPercent,$startDate,$endDate,$active);
            if ($success) {
                SessionStorage::sendAlert("Przecena została zapisana.", "success");
            } else {
                SessionStorage::sendAlert("Wystąpił błąd podczas zapisu przeceny.", "danger");
            }
        }
    } 
    // Usuwanie przeceny
    elseif($action === 'delete') {
        if (!$discountId || !is_numeric($discountId)) {
            SessionStorage::sendAlert("Nieprawidłowe ID przeceny.", "danger");
        }
        else{
            $success = $db->deleteDiscount((int)$discountId);
            if ($success) {
                SessionStorage::sendAlert("Przecena została usunięta.", "success");
            } else {
                SessionStorage::sendAlert("Wystąpił błąd podczas usuwania przeceny.", "danger");
            }
        }
    
    }
    // Dodawanie przeceny
    elseif($action === 'add'){

        if ($name === null || trim($name) === '') {
            SessionStorage::sendAlert("Nazwa nowej przeceny nie może być pusta.", "danger");
        }
        else if($discountPercent < 0 || $discountPercent > 100){
            SessionStorage::sendAlert("Podano nie prawidłowy procent przeceny", "danger");
        }
        else if (strtotime($endDate) <= strtotime($startDate)) {
            SessionStorage::sendAlert("Data zakończenia musi być późniejsza niż data rozpoczęcia.", "danger");
        }
        else {
            $added = $db->addDiscount(trim($name),$discountPercent,$startDate,$endDate,$active);
            if ($added) {
                SessionStorage::sendAlert("Nowa przecena została dodana.", "success");
            } else {
                SessionStorage::sendAlert("Wystąpił błąd podczas dodawania przeceny.", "danger");
            }
        }
    }


    header("Location: editDiscountsPage.php");
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

<?php 
$discounts = $db->getAllDiscounts();
?>

<!-- Formularz edycji -->
<div class="card my-4 mx-5">
    <div class="card-header bg-primary text-white">
        Edycja przecen
    </div>
    <div class="card-body">

        <!-- Dodaj przecenę -->
        <div class="card mb-3">
            <div class="card-body border border-success">
                <h5 class="card-title">Dodaj nową przecenę</h5>
                <form method="post">
                    <input type="hidden" name="action" value="add" />
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label for="name" class="form-label">Nazwa przeceny</label>
                            <input type="text" id="name" name="name" class="form-control" required />
                        </div>

                        <div class="col-md-2">
                            <label for="discount_percent" class="form-label">Procent (%)</label>
                            <input type="number" id="discount_percent" name="discount_percent" class="form-control" step="0.01" min="0" max="100" required />
                        </div>

                        <div class="col-md-2">
                            <label for="start_date" class="form-label">Data początku</label>
                            <input type="date" id="start_date" name="start_date" class="form-control" required />
                        </div>

                        <div class="col-md-2">
                            <label for="end_date" class="form-label">Data końca</label>
                            <input type="date" id="end_date" name="end_date" class="form-control" required />
                        </div>

                        <div class="col-md-1 d-flex flex-column justify-content-end">
                            <div class="form-check">
                                <input type="checkbox" id="active" name="active" class="form-check-input" />
                                <label for="active" class="form-check-label">Aktywna</label>
                            </div>
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">Dodaj</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php foreach ($discounts as $discount): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <p class="mb-2 fw-bold"><?= htmlspecialchars($discount->name) ?></p>

                    <form method="post">
                        <input type="hidden" name="action" value="save" />
                        <input type="hidden" name="discount_id" value="<?= $discount->id ?>" />
                        <div class="row g-2">

                            <div class="col-md-3">
                                <label for="name" class="form-label">Nazwa przeceny</label>
                                <input type="text" id="name" name="name" value="<?= $discount->name ?>" class="form-control" required />
                            </div>

                            <div class="col-md-2">
                                <label for="discount_percent" class="form-label">Procent (%)</label>
                                <input type="number" id="discount_percent" name="discount_percent" value="<?= $discount->discountPercent ?>" class="form-control" step="0.01" min="0" max="100" required />
                            </div>

                            <div class="col-md-2">
                                <label for="start_date" class="form-label">Data początku</label>
                                <input type="date" id="start_date" name="start_date" value="<?= $discount->startDate ?>" class="form-control" required />
                            </div>

                            <div class="col-md-2">
                                <label for="end_date" class="form-label">Data końca</label>
                                <input type="date" id="end_date" name="end_date" value="<?= $discount->endDate ?>" class="form-control" required />
                            </div>

                            <div class="col-md-1 d-flex flex-column justify-content-end">
                                <div class="form-check">
                                    <input type="checkbox" id="active_<?= $discount->id ?>" name="active" class="form-check-input" <?= $discount->active ? 'checked' : '' ?> />
                                    <label for="active_<?= $discount->id ?>" class="form-check-label">Aktywna</label>
                                </div>
                            </div>

                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-sm btn-primary w-100">Zapisz</button>
                            </div>
                            </form>
                            
                            <div class="col-md-1 d-flex align-items-end">
                                <form method="post" class="w-100">
                                    <input type="hidden" name="action" value="delete" />
                                    <input type="hidden" name="discount_id" value="<?= $discount->id ?>" />
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Na pewno usunąć tę przecenę?');">Usuń</button>
                                </form>
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
<!-- Usuwanie tosta po czasie -->
<script>
  const toastEl = document.getElementById('statusToast');
  if (toastEl) {
    const bsToast = new bootstrap.Toast(toastEl, { delay: 5000 });
    bsToast.show();
  }
</script>
</body>
</html>

