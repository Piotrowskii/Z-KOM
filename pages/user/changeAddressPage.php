<?php
require_once '../../php/db.php';
require_once '../../php/loginManager.php';
require_once '../../php/sessionStorage.php';

session_start();
$db = new Db();
$loginManager = new LoginManager($db);


if (!$loginManager->isLoggedIn()) {
    header('Location: ../loginPage.php');
    exit;
}

$userId = $_SESSION['user_id'];
$street = $_POST['street'] ?? '';
$houseNumber = $_POST['house_number'] ?? '';
$city = $_POST['city'] ?? '';
$postalCode = $_POST['postal_code'] ?? '';
$country = $_POST['country'] ?? '';


if (!$street || !$houseNumber || !$city || !$postalCode || !$country) {
    SessionStorage::sendAlert('Wszystkie pola są wymagane.', 'danger');
    header('Location: ../userPage.php');
    exit;
}

if (!preg_match('/^\d{2}-\d{3}$/', $postalCode)) {
    SessionStorage::sendAlert('Kod pocztowy musi być w formacie 11-111.', 'danger');
    header('Location: ../userPage.php');
    exit;
}

$success = $db->upsertUserAddress($userId, $street, $houseNumber, $city, $postalCode, $country);

if ($success) {
    SessionStorage::sendAlert('Adres został pomyślnie zapisany.', 'success');
} else {
    SessionStorage::sendAlert('Wystąpił błąd podczas zapisu adresu.', 'danger');
}

header('Location: ../userPage.php');
exit;