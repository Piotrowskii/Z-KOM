<?php

require_once '../../php/db.php';
require_once '../../php/loginManager.php';
require_once '../../php/SessionStorage.php';
require_once '../../php/creditCardValidator.php';
session_start();

$db = new Db();
$loginManager = new LoginManager($db);

if (!$loginManager->isLoggedIn()) {
    SessionStorage::sendAlert("Musisz być zalogowany, aby złożyć zamówienie.", "danger");
    header("Location: ../cartPage.php");
    exit;
}

$user = $loginManager->getLoggedInUser();
$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    SessionStorage::sendAlert("Koszyk jest pusty.", "danger");
    header("Location: ../cartPage.php");
    exit;
}

if(!isset($_POST['card_number']) || !isset($_POST['expiry_date']) || !isset($_POST['cvv'])){
    SessionStorage::sendAlert("Musisz podać informacje o karcie kredytowej", "danger");
    header("Location: ../cartPage.php");
    exit;
}

$cardNumber = str_replace(' ', '', $_POST['card_number']);
$expiryDate = $_POST['expiry_date'];
$cvv = $_POST['cvv'];

if (strlen($expiryDate) !== 5 || strpos($expiryDate, '/') !== 2) {
    SessionStorage::sendAlert("Nieprawidłowy format daty ważności (oczekiwany MM/RR)", "danger");
    header("Location: ../cartPage.php");
    exit;
}

if (strlen($cvv) !== 3 || !ctype_digit($cvv)) {
    SessionStorage::sendAlert("CVV musi składać się z dokładnie 3 cyfr", "danger");
    header("Location: ../cartPage.php");
    exit;
}

if(!creditCardValidator($cardNumber)){
    SessionStorage::sendAlert("Nie poprawny numer karty kredytowej", "danger");
    header("Location: ../cartPage.php");
    exit;
}


$total = 0;
$orderItems = [];

foreach ($cart as $productId => $qty) {
    $product = $db->getProductById($productId);

    if (!$product) {
        SessionStorage::sendAlert("Produkt o ID $productId nie istnieje.", "danger");
        header("Location: ../cartPage.php");
        exit;
    }

    if ($qty > $product->stock) {
        SessionStorage::sendAlert("Brak wystarczającej ilości produktu: {$product->name}.", "danger");
        header("Location: ../cartPage.php");
        exit;
    }

    $finalPrice = $product->finalPrice;
    $total += $finalPrice * $qty;

    $orderItems[] = [
        'product_id' => $productId,
        'qty' => $qty,
        'price' => $finalPrice
    ];
}

$expectedTotal = $_POST['expectedTotal'] ?? null;

if (!is_numeric($expectedTotal) || round($total, 2) !== round((float)$expectedTotal, 2)) {
    SessionStorage::sendAlert("Cena całkowita uległa zmianie. Proszę odświeżyć stronę koszyka.", "danger");
    header("Location: ../cartPage.php");
    exit;
}

$orderId = $db->insertOrder($user->id, $total, $orderItems);

if (!$orderId) {
    SessionStorage::sendAlert("Nie udało się utworzyć zamówienia.", "danger");
    header("Location: ../cartPage.php");
    exit;
}

unset($_SESSION['cart']);

header("Location: orderInformationPage.php?order=$orderId");
exit;