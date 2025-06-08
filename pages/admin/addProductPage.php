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
    <title>Produkt</title>
</head>
<body>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float) ($_POST['price'] ?? -1);
    $stock = (int) ($_POST['stock'] ?? -1);
    $imageUrl = trim($_POST['image_url'] ?? '');
    $categoryId = $_POST['category_id'] === '' ? null : (int)$_POST['category_id'];
    $discountId = $_POST['discount_id'] === '' ? null : (int)$_POST['discount_id'];

    if (!$name) $errors[] = "Nazwa jest wymagana.";
    if (!$brand) $errors[] = "Marka jest wymagana.";
    if ($price < 0 || $price > 99999999.99) $errors[] = "Nieprawidłowa cena.";
    if ($stock < 0) $errors[] = "Nieprawidłowy stan magazynowy.";
    if (!$imageUrl) $errors[] = "Adres URL zdjęcia jest wymagany.";

    if (empty($errors)) {
        $newId = $db->addProduct($name, $brand, $description, $price, $stock, $imageUrl, $categoryId, $discountId);
        if($newId){
            header('Location: editProductAttributesPage.php?product='.$newId);
            exit;
        }
        else{
            SessionStorage::sendAlert("Wystąpił błąd podczas zapisu produktu.", "danger");
            header('Location: addProductPage.php');
            exit;
        }
    }
    else{
        $fullErrorMessage = implode("<br>", $errors);
        SessionStorage::sendAlert($fullErrorMessage, "danger");
        header('Location: addProductPage.php');
        exit;
    }
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


<!-- Formularz edycji -->
<div class="container mt-5">
    <div class="card shadow-sm mx-auto" style="max-width: 700px;">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Dodawanie nowego produktu</h4>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <!-- Id produktu -->
                <input type="hidden" name="id"/>

                <div class="mb-3">
                    <label for="name" class="form-label">Nazwa</label>
                    <input type="text" id="name" name="name" class="form-control" maxlength="255" required />
                </div>

                <div class="mb-3">
                    <label for="brand" class="form-label">Marka</label>
                    <input type="text" id="brand" name="brand" class="form-control"  maxlength="255" required />
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Opis</label>
                    <textarea id="description" name="description" class="form-control" rows="4"></textarea>
                </div>

                <!-- Wybór kategorii -->
                <div class="mb-3">
                    <label for="category_id" class="form-label">Kategoria</label>
                    <select id="category_id" name="category_id" class="form-select">
                        <option value="">Brak kategori</option>

                        <?php foreach ($db->getAllCategories() as $category): ?>
                            <option value="<?= htmlspecialchars($category->id) ?>" ><?= htmlspecialchars($category->name) ?></option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <!-- Wybór przeceny -->
                <div class="mb-3">
                    <label for="discount_id" class="form-label">Przecena</label>
                    <select id="discount_id" name="discount_id" class="form-select">
                        <option value="">Brak zniżki</option>

                        <?php foreach ($db->getAllDiscounts() as $discount): ?>
                            <option class="<?= !$discount->isActive() ? 'text-danger' : '' ?>" value="<?= htmlspecialchars($discount->id) ?>" ><?= htmlspecialchars($discount->name) ?> <?= ' ('.htmlspecialchars($discount->discountPercent).'%)' ?> <?= !$discount->isActive() ? '!!! NIEWAŻNA !!!' : '' ?></option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="price" class="form-label">Cena (zł)</label>
                        <input type="number" step="0.01" id="price" name="price" class="form-control" min="0" max="99999999.99" required />
                    </div>
                    <div class="col-md-6">
                        <label for="stock" class="form-label">Stan magazynowy</label>
                        <input type="number" id="stock" name="stock" class="form-control" required />
                    </div>
                </div>

                <div class="mb-4">
                    <label for="image_url" class="form-label">URL zdjęcia</label>
                    <div class="input-group">
                        <input type="text" id="image_url" name="image_url" class="form-control" />
                        <button class="btn btn-outline-secondary" type="button" id="togglePreviewBtn">Wyłącz podgląd</button>
                    </div>
                    <div id="image_preview" class="mt-3" style="max-width: 200px;">

                    </div>
                </div>

                <hr />

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-success">Zapisz zmiany</button>
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
 

<script src="../../bootstrap/bootstrap.bundle.min.js"></script>

<!-- Usuwanie tosta po czasie -->
<script>
  const toastEl = document.getElementById('statusToast');
  if (toastEl) {
    const bsToast = new bootstrap.Toast(toastEl, { delay: 5000 });
    bsToast.show();
  }
</script>

<!-- Pokazywanie zjęcia -->
<script>
    const imageUrlInput = document.getElementById('image_url');
    const imagePreviewDiv = document.getElementById('image_preview');
    const togglePreviewBtn = document.getElementById('togglePreviewBtn');

    let previewEnabled = true;

    function updatePreview() {
        if (!previewEnabled) {
            imagePreviewDiv.innerHTML = '';
            return;
        }
        const url = imageUrlInput.value.trim();
        if (url) {
            imagePreviewDiv.innerHTML = `<img src="${url}" alt="Podgląd zdjęcia" class="img-fluid" onerror="this.style.display='none'" onload="this.style.display='block'" />`;
        } else {
            imagePreviewDiv.innerHTML = '';
        }
    }

    imageUrlInput.addEventListener('input', updatePreview);

    togglePreviewBtn.addEventListener('click', () => {
        previewEnabled = !previewEnabled;
        if (previewEnabled) {
            togglePreviewBtn.textContent = 'Wyłącz podgląd';
            updatePreview();
        } else {
            togglePreviewBtn.textContent = 'Włącz podgląd';
            imagePreviewDiv.innerHTML = '';
        }
    });

    updatePreview();
</script>
</body>
</html>

