<?php
declare(strict_types=1);

namespace App\Includes;

// Função para verificar se o usuário está autenticado
function checkAuthentication(Auth $auth, string $redirectPage = 'index.php'): void {
    if (!$auth->isAuthenticated()) {
        header('Location: ' . $redirectPage);
        exit();
    }
}

// Função para verificar permissões
function checkPermission(PermissionManager $permissionManager, string $currentLink, string $currentDir, string $redirectPage = 'dashboard.php'): void {
    if (!$permissionManager->hasPermission($currentLink, $currentDir)) {
        $_SESSION['responseMessage'] = "Você não tem permissão para acessar esta página.";
        $_SESSION['responseStyle'] = 'bg-danger-500';
        header('Location: ' . $redirectPage);
        exit();
    }
}

// Função para lidar com mensagens de resposta
function handleResponseMessage(): ?array {
    if (isset($_SESSION['responseMessage'])) {
        $responseMessage = $_SESSION['responseMessage'];
        $style = $_SESSION['responseStyle'] ?? 'bg-success-500';
        unset($_SESSION['responseMessage'], $_SESSION['responseStyle']);
        return ['message' => $responseMessage, 'style' => $style];
    }
    return null;
}
