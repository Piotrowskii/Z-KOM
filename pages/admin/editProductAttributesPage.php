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

if(!isset($_GET['product'])){
  header('Location: ../index.php');
  exit;
}

$productId = $_GET['product'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../bootstrap/bootstrap.min.css"/>
    <link rel="stylesheet" href="../../bootstrap/bootstrap-icons.min.css"/>
    <link rel="icon" href="../../assets/images/general/pc.svg" sizes="any" type="image/svg+xml">
    <title>Zarządzanie atrybutami produktów</title>
</head>
<body>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attributeId = $_POST['attribute_id'] ?? null;
    $action = $_POST['action'] ?? null;
    $value = $_POST['value'] ?? null;

    if (!$attributeId || !is_numeric($attributeId)) {
        SessionStorage::sendAlert("Nieprawidłowe ID atrybutu.", "danger");
        header("Location: editProductAttributesPage.php?product_id=".urlencode($productId));
        exit;
    }

    // Zapisywanie atrybutu
    if ($action === 'save') {
        if ($value === null || trim($value) === '') {
            SessionStorage::sendAlert("Wartość atrybutu nie może być pusta.", "danger");
        } else {
            $success = $db->updateProductAttribute((int)$attributeId, trim($value));
            if ($success) {
                SessionStorage::sendAlert("Atrybut został zapisany.", "success");
            } else {
                SessionStorage::sendAlert("Wystąpił błąd podczas zapisu atrybutu.", "danger");
            }
        }
    } 
    // Usuwanie atrybutu
    elseif($action === 'delete') {

        $success = $db->deleteProductAttribute((int)$attributeId);
        if ($success) {
            SessionStorage::sendAlert("Atrybut został usunięty.", "success");
        } else {
            SessionStorage::sendAlert("Wystąpił błąd podczas usuwania atrybutu.", "danger");
        }
    }
    // Dodawanie atrybutu
    elseif($action === 'add'){

        if ($value === null || trim($value) === '') {
            SessionStorage::sendAlert("Wartość nowego atrybutu nie może być pusta.", "danger");
        } else {
            $added = $db->addProductAttribute((int)$productId, (int)$attributeId, trim($value));
            if ($added) {
                SessionStorage::sendAlert("Nowy atrybut został dodany.", "success");
            } else {
                SessionStorage::sendAlert("Wystąpił błąd podczas dodawania atrybutu.", "danger");
            }
        }
    }

    header("Location: editProductAttributesPage.php?product=".urlencode($productId));
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

<!-- Return button -->
<a class="btn btn-secondary mx-5 mt-2" href="../productPage.php?product=<?= htmlspecialchars($productId)?>">Wróc na strone produktu</a>

<?php 
$product = $db->getProductById($productId);
if (!$product) {
    header('Location: ../../index.php');
    exit;
}

$attributes = $db->getProductsAttributes($productId);
$missingAttributes = $db->getProductMissingAttributes($productId);
?>
<!-- Formularz edycji -->
<div class="card my-4 mx-5">
    <div class="card-header bg-primary text-white">
        Edycja atrybutów produktu
    </div>
    <div class="card-body">

        <!-- Dodaj atrybut -->
        <div class="card mb-3">
            <div class="card-body border border-success">
                <h5 class="card-title">Dodaj nowy atrybut</h5>

                <form method="post" class="d-flex gap-2 align-items-center">
                    <input type="hidden" name="action" value="add" />
                    <input type="hidden" name="product_id" value="<?= htmlspecialchars($productId) ?>" />

                    <select name="attribute_id" class="form-select flex-fill" required>
                        <?php foreach ($missingAttributes as $attr): ?>
                            <option value="<?= htmlspecialchars($attr['id']) ?>">
                                <?= htmlspecialchars($attr['name']) ?><?= $attr['unit'] ? ' (' . htmlspecialchars($attr['unit']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="text" name="value" class="form-control flex-fill" placeholder="Wartość atrybutu" required />

                    <button type="submit" class="btn btn-success flex-shrink-0">Dodaj</button>
                </form>
            </div>
        </div>


        <?php foreach ($attributes as $attr): ?>
            <div class="card mb-3">
                <div class="card-body">

                    <p class="mb-0 fw-bold"> <?= htmlspecialchars($attr['name']) ?> <?= $attr['unit'] ? '(' . htmlspecialchars($attr['unit']) . ')' : '' ?></p><br>
                    
                    <div class="d-flex align-items-center gap-2 flex-wrap">

                        <!-- Zapisywanie zmian -->
                        <form method="post" class="d-flex align-items-center gap-2 mb-0 flex-grow-1">
                            <input type="hidden" name="attribute_id" value="<?= $attr['id'] ?>" />
                            <input type="text" name="value" class="form-control flex-grow-1" value="<?= htmlspecialchars($attr['value']) ?>" required/>
                            <button type="submit" name="action" value="save" class="btn btn-sm btn-primary">Zapisz</button>
                        </form>

                        <!-- Usuwanie atrybutów -->
                        <form method="post" class="mb-0">
                            <input type="hidden" name="action" value="delete" />
                            <input type="hidden" name="attribute_id" value="<?= $attr['id'] ?>" />
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Na pewno usunąć ten atrybut?');">Usuń</button>
                        </form>

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

