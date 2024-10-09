<?php
declare(strict_types=1);

namespace App\Includes;

class SessionMessage {

    // Define a mensagem e redireciona para a página
    public static function setResponseAndRedirect(string $message, string $style, string $redirectPage): void {
        self::setMessage($message, $style);
        self::redirect($redirectPage);
    }

    // Define a mensagem na sessão
    public static function setMessage(string $message, string $style): void {
        $_SESSION['responseMessage'] = $message;
        $_SESSION['responseStyle'] = $style;
    }

    // Redireciona para uma página
    public static function redirect(string $redirectPage): void {
        header("Location: ./dashboard.php?page={$redirectPage}");
        exit();
    }

    // Obtém a mensagem e o estilo da sessão (se existirem) e limpa a sessão
    public static function getMessage(): ?array {
        if (isset($_SESSION['responseMessage'])) {
            $response = [
                'message' => $_SESSION['responseMessage'],
                'style' => $_SESSION['responseStyle'] ?? 'bg-success-500', // Estilo padrão
            ];
            unset($_SESSION['responseMessage'], $_SESSION['responseStyle']); // Limpa a sessão
            return $response;
        }
        return null;
    }
}
