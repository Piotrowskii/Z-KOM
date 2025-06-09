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
    <title>Produkt</title>
</head>
<body>

<?php


// Zmiana widocznego stocku produktu na - ile jest w koszyku
if(!isset($_GET['product'])){
  header('Location: ../index.php');
  exit;
}

$productId = $_GET['product'];
$product = $db->getProductById($productId);

if(!$product){
  header('Location: ../index.php');
  exit;
}

$inCartQty = 0;
if (isset($_SESSION['cart'][$product->id])) {
    $inCartQty = $_SESSION['cart'][$product->id];
}

$product->stock -= $inCartQty;
$product->stock = max(0, $product->stock);


// Wstawianie opinii
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_rating'], $_POST['review_comment'], $_POST['product_id']) && $loginManager->isLoggedIn() && !$db->hasUserReviewedProduct((int)$_SESSION['user_id'],$productId)) {
    $rating = (int) $_POST['review_rating'];
    $comment = trim($_POST['review_comment']);
    $productId = (int) $_POST['product_id'];
    $userId = $_SESSION['user_id'];

    if ($rating >= 1 && $rating <= 5  && $comment !== '') {
        $db->addReview($userId, $productId, $rating, $comment);
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}
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

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $productId = $_POST['product_id'];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]++;
    } else {
        $_SESSION['cart'][$productId] = 1;
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}


?>

<!-- Opcje administracyjne -->
<?php if ($loginManager->isLoggedIn() && $loginManager->isAdmin()): ?>
  <div class="container mt-4">
    <div class="alert alert-warning d-flex justify-content-between align-items-center">
      <div>
        <strong>Panel administratora:</strong> Możesz edytować ten produkt.
      </div>
      <div>
        <a href="./admin/editProductAttributesPage.php?product=<?= htmlspecialchars($productId) ?>" class="btn btn-sm btn-secondary me-2">Edytuj atrybuty</a>
        <a href="./admin/editProductPage.php?product=<?= htmlspecialchars($productId) ?>" class="btn btn-sm btn-primary me-2">Edytuj produkt</a>
        <a href="./admin/deleteProductPage.php?product=<?= htmlspecialchars($productId) ?>" class="btn btn-sm btn-danger me-2" onclick="return confirm('Czy na pewno chcesz usunąć ten produkt?');">Usuń produkt</a>
      </div>
    </div>
  </div>
<?php endif; ?>


<!-- Góra -->
<div class="container my-5">
    <div class="d-flex flex-column flex-md-row border rounded p-3 align-items-center justify-content-between">

        <!-- Zdjęcie po lewej -->
        <div class="text-center">
            <img src="<?= htmlspecialchars($product->imageUrl) ?>" class="rounded ms-auto" style="height:350px;width:350px;object-fit: cover" alt="Zdjęcie produktu">
        </div>

        <!-- Szczegóły po prawej -->
        <div class="d-flex flex-column align-items-center w-100">
            <h3><?= htmlspecialchars($product->name) ?></h3>
            <p class="text-muted">Marka: <?= htmlspecialchars($product->brand) ?></p>

            <?php if($product->hasDiscount()): ?>
              <p class="card-text text-danger text-decoration-line-through mt-4" style="font-size: 0.9em;"><?= $product->getFormattedPrice() ?></p>
            <?php endif; ?>

            <p class="text-success h1"><?= htmlspecialchars($product->getFormattedFinalPrice()) ?></p>
            <?php if ($product->stock > 0): ?>
                <p class="text-muted">Dostępny – <?= $product->stock ?> szt.</p>
                <form method="post">
                  <input type="hidden" name="product_id" value="<?= $product->id ?>">
                  <button type="submit" class="btn btn-success btn-lg">Kup</button>
                </form>
            <?php else: ?>
                <p class="text-danger">Niedostępny</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Opis -->
<div class="container p-3">
  <h1>Opis</h1>
  <?= $product->description ?>
</div>

<!-- Specyfikacja -->
<div class="container p-3">
  <h1>Specyfikacja Techniczna</h1>
  <table class="table table-bordered mt-3">
    <thead class="table-light">
        <tr>
            <th scope="col">Atrybut</th>
            <th scope="col">Wartość</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($db->getProductsAttributes($product->id) as $attrybut): ?>
            <tr>
                <td><?= htmlspecialchars($attrybut['name']) ?></td>
                <td><?= htmlspecialchars($attrybut['value'])." ".htmlspecialchars($attrybut['unit']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<!-- Opinie -->
<?php
$reviews = $db->getReviewsByProductId($product->id);
?>
<div class="container p-3">
  <h1 class="mb-4">Recenzje</h1>

  <?php if (empty($reviews)): ?>

    <div class="alert alert-secondary text-center">
      Brak recenzji dla tego produktu.
    </div>

  <?php else: ?>

    <div class="row">
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

  <?php endif; ?>

</div>

<!-- Formularz dodawania opinii -->
<?php if ($loginManager->isLoggedIn() && !$db->hasUserReviewedProduct((int)$_SESSION['user_id'],$productId)): ?>
<div class="container p-3 my-4">

    <h2>Dodaj opinię</h2>

    <form method="post" class="mb-4">
        <input type="hidden" name="product_id" value="<?= htmlspecialchars($product->id) ?>">

        <div class="mb-3">
            <label for="review_rating" class="form-label">Ocena</label>
            <select id="review_rating" name="review_rating" class="form-select" required>
                <option value="1">1 gwiazdka</option>
                <option value="2">2 gwiazdki</option>
                <option value="3">3 gwiazdki</option>
                <option value="4">4 gwiazdki</option>
                <option value="5" selected>5 gwiazdek</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="review_comment" class="form-label">Komentarz</label>
            <textarea id="review_comment" name="review_comment" class="form-control" rows="3" maxlength="1024" required></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Dodaj opinię</button>
    </form>

</div>
<?php else: ?>

  <!-- Już wystawił opinie -->
  <?php if (isset($_SESSION['user_id']) && $db->hasUserReviewedProduct((int)$_SESSION['user_id'],$productId)): ?>
    <div class="container p-3">
        <div class="alert alert-info">
            Wystawiłeś już opinie dla tego produktu.
        </div>
    </div>
  <!-- Wymaganie zalogowania -->
  <?php else: ?>
    <div class="container p-3">
        <div class="alert alert-info">
            Aby dodać opinię, <a href="loginPage.php">zaloguj się</a>.
        </div>
    </div>
  <?php endif; ?>

<?php endif; ?>


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

