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
    <title>Login</title>
</head>
<body>

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
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $name = trim($_POST['name']);
    $surname = trim($_POST['surname']);
    $phone = trim($_POST['phone']) ?: null;
    $permissionId = $loginManager->isAdmin() ? (int)($_POST['permission_id'] ?? 1) : 1;

    if ($email && $password && $name && $surname) {
        if ($db->userExistsByEmail($email)) {
            $message = "Użytkownik o podanym adresie e-mail już istnieje.";
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $success = $db->addUser($email, $passwordHash, $name, $surname, $phone, $permissionId);

            if ($success) {
                $message = "Użytkownik zarejestrowany pomyślnie.";
            } else {
                $message = "Błąd podczas rejestracji. Spróbuj ponownie.";
            }
        }
    } else {
        $message = "Proszę uzupełnić wszystkie wymagane pola.";
    }
}


?>

<!-- Formularz rejestracji -->
<div class="d-flex justify-content-center align-items-center bg-light" style="height: 100vh;">
  <div class="card shadow-sm" style="width: 100%; max-width: 400px;">
    <div class="card-body">
      <h2 class="card-title mb-4 text-center">Rejestracja</h2>

      <?php if (!empty($message)): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <form method="POST" action="registerPage.php">

        <div class="mb-3">
          <label for="email" class="form-label">Adres email</label>
          <input type="email" class="form-control" id="email" name="email" placeholder="Wpisz email" maxlength="255" required>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Hasło</label>
          <input type="password" class="form-control" id="password" name="password" placeholder="Wpisz hasło" required>
        </div>

        <?php if ($loginManager->isAdmin()): ?>
          <div class="mb-3">
            <label for="permission_id" class="form-label">Uprawnienia</label>
            <select name="permission_id" id="permission_id" class="form-select" required>
            <option value="1">user</option>
            <option value="2">admin</option>
            </select>
          </div>
        <?php else: ?>
          <input type="hidden" name="permission_id" value="1">
        <?php endif; ?>

        <div class="mb-3">
          <label for="name" class="form-label">Imię</label>
          <input type="text" class="form-control" id="name" name="name" placeholder="Twoje imię" maxlength="255" required>
        </div>

        <div class="mb-3">
          <label for="surname" class="form-label">Nazwisko</label>
          <input type="text" class="form-control" id="surname" name="surname" placeholder="Twoje nazwisko" maxlength="255" required>
        </div>

        <div class="mb-3">
          <label for="phone" class="form-label">Telefon (opcjonalny)</label>
          <input type="tel" class="form-control" id="phone" name="phone" placeholder="Numer telefonu" maxlength="20">
        </div>

        <button type="submit" class="btn btn-primary w-100">Zarejestruj się</button>
      </form><br>

      <p class="mt-3 text-center">Masz już konto? <a href="loginPage.php">Zaloguj się</a></p>
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
</body>
</html>

