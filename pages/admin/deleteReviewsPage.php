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
    <title>Usuwanie recenzji</title>
</head>
<body>

<?php
$input = $_GET['input'] ?? '';
$reviewType = $_GET['review_type'] ?? 'product';

if($reviewType === 'product'){
    $reviews = $db->getProductReviewsByComment($input);
}
else{
    $reviews = $db->getStoreReviewsByComment($input);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reviewId = $_POST['review_id'] ?? null;
    $deleteType = $_POST['delete_type'] ?? null;

    if(!$reviewId || !$deleteType){
        SessionStorage::sendAlert("Błąd przy usuwaniu recenzji", "danger");
    }
    else{
        if($deleteType === 'product'){
            $success = $db->deleteProductReviewById($reviewId);
            if($success) SessionStorage::sendAlert("Pomyślnie usunięto recenzje", "success");
            else SessionStorage::sendAlert("Błąd przy usuwaniu recenzji", "danger");
        }
        else{
            $success = $db->deleteStoreReviewById($reviewId);
            if($success) SessionStorage::sendAlert("Pomyślnie usunięto recenzje", "success");
            else SessionStorage::sendAlert("Błąd przy usuwaniu recenzji", "danger");
        }
    }

    header("Location: deleteReviewsPage.php?input=".$input."&review_type=".$reviewType);
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

  <!-- Karta wyszukiwania -->
  <div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Wyszukaj recenzje</h5>
        <form method="get">
        <div class="row g-2 align-items-center">
            <div class="col-sm-8">
              <input type="text" name="input" class="form-control" placeholder="Wpisz treśc recenzji" value="<?= $input ?>">
            </div>
            <div class="col-sm-2">
              <select name="review_type" id="review_type" class="form-select" required>
                <option value="product" <?= $reviewType === 'product' ? 'selected' : '' ?>>Opinia produktu</option>
                <option value="store" <?= $reviewType === 'store' ? 'selected' : '' ?>>Opinia sklepu</option>
              </select>
            </div>
            <div class="col-sm-2">
              <button type="submit" class="btn btn-primary w-100">Szukaj</button>
            </div>
        </div>
        </form>
    </div>
  </div>

  <!-- Karty z recenzjami -->
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 mx-2 g-4">

    <?php if(empty($reviews)): ?>
        <div class="col-12">
            <div class="card shadow-sm text-center py-5">
            <div class="card-body">
                <h3 class="text-muted mb-3">
                <i class="bi bi-emoji-frown" style="font-size: 2rem;"></i><br>
                Brak recenzji
                </h3>
                <p class="text-secondary">Nie znaleziono żadnych recenzji dla wybranych kryteriów.</p>
            </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card shadow-sm text-center py-5">
            <div class="card-body">
                <h3 class="text-muted mb-3">
                <i class="bi bi-emoji-frown" style="font-size: 2rem;"></i><br>
                Brak recenzji
                </h3>
                <p class="text-secondary">Nie znaleziono żadnych recenzji dla wybranych kryteriów.</p>
            </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card shadow-sm text-center py-5">
            <div class="card-body">
                <h3 class="text-muted mb-3">
                <i class="bi bi-emoji-frown" style="font-size: 2rem;"></i><br>
                Brak recenzji
                </h3>
                <p class="text-secondary">Nie znaleziono żadnych recenzji dla wybranych kryteriów.</p>
            </div>
            </div>
        </div>
    <?php else: ?>

        <?php foreach ($reviews as $review) : ?>

        <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body position-relative">
            <small class="text-muted position-absolute" style="top: 10px; right: 15px; font-size: 0.8rem;">
                <?= htmlspecialchars(date('Y-m-d', strtotime($review->createdAt ?? ''))) ?>
            </small>
            <h5 class="card-title"><?= htmlspecialchars($review->name ?? 'Unknown') ?></h5> 
            <h6 class="card-subtitle mb-2 text-muted">
            <?= str_repeat('★', (int)$review->rating) . str_repeat('☆', 5 - (int)$review->rating) ?>
            </h6>
            <p class="card-text"><?= nl2br(htmlspecialchars($review->comment ?? '')) ?></p>

            <div class="d-flex flex-row">
                <!-- Formularz usuwania -->
                <form method="post" onsubmit="return confirm('Czy na pewno chcesz usunąć tę recenzję?');" class="mt-3">
                    <input type="hidden" name="review_id" value="<?= (int)$review->id ?>">
                    <input type="hidden" name="delete_type" value="<?= $reviewType ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Usuń</button>
                </form>

                <?php if($reviewType === 'product'): ?>
                    <a href="../productPage.php?product=<?= $review->productId ?>" class="btn btn-primary btn-sm ms-auto pt-2">link do produktu</a>
                <?php endif; ?>
            </div>

            </div>
        </div>
        </div>

        <?php endforeach; ?>

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

