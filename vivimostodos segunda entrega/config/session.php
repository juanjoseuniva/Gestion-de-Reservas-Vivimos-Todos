<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): void {
    if (!isset($_SESSION['usuario'])) {
        header('Location: /vivimostodos/index.php');
        exit;
    }
}

function requireRole(array $roles): void {
    requireLogin();
    if (!in_array($_SESSION['usuario']['rol'], $roles)) {
        header('Location: /vivimostodos/403.php');
        exit;
    }
}

function isLoggedIn(): bool {
    return isset($_SESSION['usuario']);
}

function currentUser(): ?array {
    return $_SESSION['usuario'] ?? null;
}

function currentRole(): ?string {
    return $_SESSION['usuario']['rol'] ?? null;
}

function currentUserId(): ?int {
    return $_SESSION['usuario']['id_usuario'] ?? null;
}
