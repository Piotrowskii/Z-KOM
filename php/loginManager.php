<?php

require_once __DIR__ . '/../php/db.php';

class LoginManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function loginUser($email, $password): bool {
        return $this->db->loginUser($email, $password);
    }

    public function isLoggedIn(): bool {
        if (isset($_SESSION['user_id'], $_SESSION['user_ip']) && $_SESSION['user_ip'] === $_SERVER['REMOTE_ADDR'] && $this->db->userExistsById($_SESSION['user_id'])) 
        {
            return true;
        }
        $this->logout();
        return false;
    }

    public function getLoggedInUser(): ?User {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $this->db->getUserById((int)$_SESSION['user_id']);
    }


    public function isAdmin(): bool {
        if (!$this->isLoggedIn()) {
            return false;
        }
        return (isset($_SESSION['permission_id']) && $_SESSION['permission_id'] == 2);
    }

    public function logout(): void {
        unset($_SESSION['user_id'], $_SESSION['permission_id'], $_SESSION['user_ip']);
    }

}
