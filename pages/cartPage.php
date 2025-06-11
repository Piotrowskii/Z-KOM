<?php
require_once '../php/db.php';
require_once '../php/loginManager.php';
require_once '../php/sessionStorage.php';

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
    <title>Koszyk</title>
</head>
<body>
<!-- Wyświetlanie alertu -->
<?php
SessionStorage::renderAlert();
?>


<?php
// Usuwanie produktów
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_product_id'])) {

        $removeId = $_POST['remove_product_id'];

        if (isset($_SESSION['cart'][$removeId])) {
            $_SESSION['cart'][$removeId]--;

            if ($_SESSION['cart'][$removeId] <= 0) {
                unset($_SESSION['cart'][$removeId]);
            }
        }

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


<!-- Nagłówek -->
<div class="my-5">
    <h1 class="text-center">Koszyk</h1>
</div>

<!-- Produkty -->
<?php
require_once "../viewModels/productViewModel.php";

$cartProducts = [];
$messages = [];
$totalPrice = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $productId => $qty) {
        

        // usuwanie niestniejących produktó z koszyka
        if (!$db->doesProductExists($productId)) {
            unset($_SESSION['cart'][$productId]);
            $messages[] = "Produkt o ID {$productId} został usunięty z koszyka, ponieważ nie istnieje.";
            continue;
        }


        $product = $db->getProductById($productId);
        if ($product && $qty > 0) {

            // Zabezpieczenie przed zbyt duża ilością porodkutów w koszyku
            if ($qty > $product->stock) {
                
                $_SESSION['cart'][$productId] = $product->stock;
                $qty = $product->stock;

                $messages[] = "Liczba sztuk produktu <strong>" . htmlspecialchars($product->name) . "</strong> została zmniejszona do {$product->stock}, ponieważ więcej nie ma w magazynie.";
            }


            $cartProducts[] = ['product' => $product, 'qty' => $qty];
            $totalPrice += $product->finalPrice * $qty;
        }
    }
}
?>

<div class="container my-5">
  <div class="row">


    <!-- Komunikaty -->
    <?php if (!empty($messages)): ?>
    <div class="alert alert-warning">
        <ul class="mb-0">
        <?php foreach ($messages as $msg): ?>
            <li><?= $msg ?></li>
        <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Lista produktów -->
    <div class="col-md-8">

      <?php if (empty($cartProducts)): ?>
        <p>Twój koszyk jest pusty.</p>
      <?php else: ?>
        <?php foreach ($cartProducts as $item): 
          $product = $item['product'];
          $qty = $item['qty'];
        ?>
          <div class="card mb-3">

            
            <div class="d-flex g-0 align-items-center">

              <!-- Zdjęcie -->
              <div class="col-md-4">
                <img src="<?= htmlspecialchars($product->imageUrl) ?>" class="rounded ms-auto" style="height:100%;width:100%;max-height: 200px;max-width: 200px;object-fit: cover">
              </div>

              <!-- Informacje -->
              <div class="col-md-8 float-start">
                <div class="card-body">
                  <h5 class="card-title"><?= htmlspecialchars($product->name) ?></h5>
                  <p class="card-text mb-3">Marka: <?= htmlspecialchars($product->brand) ?></p>

                  <?php if($product->hasDiscount()): ?>
                    <p class="card-text text-danger text-decoration-line-through mb-1"><?= $product->getFormattedPrice() ?></p>
                  <?php endif; ?>

                  <p class="card-text mb-1">Cena: <span class="text-success fw-bold"><?= $product->getFormattedFinalPrice() ?></span> x <?= $qty ?></p>
                  <p class="card-text fw-bold">Suma: <?= number_format($product->finalPrice * $qty, 2) ?> zł</p>
                </div>
              </div>

            </div>

            <!-- Guzik usuwania produktów -->
            <form method="post" class="position-absolute top-0 end-0 m-2">
              <input type="hidden" name="remove_product_id" value="<?= $product->id ?>"/>
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Usuń jedną sztukę">
                  <i class="bi bi-dash-lg"></i>
                </button>
            </form>

          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>


    <div class="col-md-4">


      <form action="./user/checkoutPage.php" method="post">

      <!-- Panel karty kredytowej -->
      <?php if($loginManager->isLoggedIn() && $loginManager->getLoggedInUser()->hasAddress()): ?>

        <div class="card p-3 mb-2 shadow-sm">
          <h4>Podaj kartę kredytową</h4>

            <div class="mb-3">
              <label for="card_number" class="form-label">Numer karty</label>
              <input type="text" class="form-control" id="card_number" name="card_number" maxlength="19" pattern="\d{4} \d{4} \d{4} \d{4}" placeholder="3434 3434 3434 3434" required>
            </div>

            <div class="row mb-3">

              <div class="col-md-6">
                <label for="expiry_date" class="form-label">Data ważności</label>
                <input type="text" class="form-control" id="expiry_date" name="expiry_date" maxlength="5" placeholder="MM/RR" required>
              </div>

              <div class="col-md-6">
                <label for="cvv" class="form-label">CVV</label>
                <input type="text" class="form-control" id="cvv" name="cvv" maxlength="3" maxlength="3" placeholder="123" required>
              </div>
              

            </div>
        </div>

      <hr>
      <?php endif; ?>

      

      <!-- Panel podsumowania -->
      <div class="card p-3 shadow-sm">
        <h4>Podsumowanie</h4>
        <hr>
        <p class="mb-2">Liczba produktów: <strong><?= array_sum(array_column($cartProducts, 'qty')) ?></strong></p>
        <p class="mb-4">Łączna cena: <strong><?= number_format($totalPrice, 2) ?> zł</strong></p>

        <?php if($loginManager->isLoggedIn() && $loginManager->getLoggedInUser()->hasAddress()): ?>
          
            <input type="hidden" name="expectedTotal" value="<?= $totalPrice ?>">
            <button type="submit" class="btn btn-success w-100">Kup teraz</button>
          
        <?php elseif($loginManager->isLoggedIn()): ?>
          <a class="btn btn-info w-100" href="userPage.php">Musisz posiadać adres</a>
        <?php else: ?>
          <a class="btn btn-danger w-100" href="loginPage.php">Musisz się zalogować</a>
        <?php endif; ?>
      
      </div>

      </form>

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
 

<script src="../bootstrap/bootstrap.bundle.min.js"></script>

<!-- Usuwanie tosta -->
<script>
  const toastEl = document.getElementById('statusToast');
  if (toastEl) {
    const bsToast = new bootstrap.Toast(toastEl, { delay: 2500 });
    bsToast.show();
  }
</script>

<!-- Formatowanie karty kredytowej -->
<script>
const cardInput = document.getElementById('card_number');

cardInput.addEventListener('input', function (event) {
  let value = event.target.value.replace(/\D/g, ''); 
  value = value.match(/.{1,4}/g)?.join(' ') ?? ''; 
  event.target.value = value;
});
</script>

<!-- Formatowanie daty wygaszenia karty -->
<script>
  const expiryInput = document.getElementById('expiry_date');

  expiryInput.addEventListener('input', function(event) {
    let value = event.target.value.replace(/\D/g, ''); 

    if (value.length >= 1) {
      if (!['0', '1'].includes(value[0])) {
        value = ''; 
      }
    }

    if (value.length >= 2) {
      const month = parseInt(value.slice(0, 2));
      if (month < 1 || month > 12) {
        value = value.slice(0, 1); 
      }
    }

    if (value.length > 2) {
      value = value.slice(0, 2) + '/' + value.slice(2);
    }

    event.target.value = value.slice(0, 5); 
  });
</script>

</body>
</html>

