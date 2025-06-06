<?php

class SessionStorage
{
    public static function sendAlert(string $content, string $severity): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION['alertMessage'] = $content;
        $_SESSION['alertType'] = $severity; 
    }

    public static function renderAlert(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (isset($_SESSION['alertMessage'], $_SESSION['alertType'])) {
            $message = htmlspecialchars($_SESSION['alertMessage']);
            $type = htmlspecialchars($_SESSION['alertType']);

            echo <<<HTML
            <div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1080;">
                <div id="statusToast" class="toast align-items-center text-bg-{$type} border-0 show" role="alert" aria-live="assertive" aria-atomic="true"
                    style="font-size: 1.1rem; min-width: 400px; max-width: 800px; padding: 1rem 1.5rem;">
                <div class="d-flex">
                    <div class="toast-body">
                    {$message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                </div>
            </div>
            HTML;

            unset($_SESSION['alertMessage'], $_SESSION['alertType']);
        }
    }
}
