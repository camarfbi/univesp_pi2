<?php
declare(strict_types=1);

namespace App\Includes;

class Auth {
    private User $user;
    // Número máximo de tentativas de login antes de bloquear temporariamente
    private const MAX_LOGIN_ATTEMPTS = 5;
    // Tempo de bloqueio em segundos (por exemplo, 15 minutos)
    private const LOCKOUT_TIME = 900;

    public function __construct(User $user) {
        $this->user = $user;
    }

    public function login(string $username, string $password): bool {
        $user = $this->user->getUserByUsername($username);

        if ($user) {
            // Verifica se o usuário está bloqueado temporariamente devido a falhas repetidas
            if ($this->isTemporarilyBlocked($user['id'])) {
                $_SESSION['login_error'] = 'Usuário temporariamente bloqueado. Tente novamente mais tarde.';
                return false;
            }

            // Verifica se o usuário está bloqueado permanentemente
            if ($user['bloqueado'] == 1) {
                $_SESSION['login_error'] = 'Usuário bloqueado.';
                return false;
            }

            // Verifica se a senha está correta
            if (password_verify($password, $user['password'])) {
                // Regenerar ID de sessão para evitar fixação de sessão
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $this->resetLoginAttempts($user['id']);  // Reseta as tentativas de login

                return true;
            } else {
                $this->incrementLoginAttempts($user['id']);  // Incrementa as tentativas de login
            }
        }

        // Retorna falso em caso de falha na autenticação
        $_SESSION['login_error'] = 'Usuário ou senha inválidos.';
        return false;
    }

    public function isAuthenticated(): bool {
        return isset($_SESSION['user_id']);
    }

    public function logout(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_regenerate_id(true); // Regenerar ID ao deslogar
        }
    }

    // Incrementa as tentativas de login no banco de dados
    private function incrementLoginAttempts(int $userId): void {
        $this->user->incrementLoginAttempts($userId);
    }

    // Reseta as tentativas de login após um login bem-sucedido
    private function resetLoginAttempts(int $userId): void {
        $this->user->resetLoginAttempts($userId);
    }

    // Verifica se o usuário está temporariamente bloqueado devido a tentativas falhas repetidas
    private function isTemporarilyBlocked(int $userId): bool {
        $user = $this->user->getUserById($userId);

        if ($user) {
            $attempts = $user['login_attempts'] ?? 0;
            $lastAttempt = $user['last_login_attempt'] ?? null;

            // Se o número de tentativas exceder o máximo e o tempo de bloqueio não tiver expirado
            if ($attempts >= self::MAX_LOGIN_ATTEMPTS && $lastAttempt) {
                $elapsedTime = time() - strtotime($lastAttempt);
                if ($elapsedTime < self::LOCKOUT_TIME) {
                    return true; // Usuário ainda bloqueado temporariamente
                }
            }
        }

        return false;
    }

    // Funções comuns para autenticação e permissões
    function checkAuthentication($auth, $redirectPage = 'index.php') {
        if (!$auth->isAuthenticated()) {
            header('Location: ' . $redirectPage);
            exit();
        }
    }

    function checkPermission($permissionManager, $currentLink, $currentDir, $redirectPage = 'dashboard.php') {
        if (!$permissionManager->hasPermission($currentLink, $currentDir)) {
            $_SESSION['responseMessage'] = "Você não tem permissão para acessar esta página.";
            $_SESSION['responseStyle'] = 'bg-danger-500';
            header('Location: ' . $redirectPage);
            exit();
        }
    }

    function handleResponseMessage() {
        if (isset($_SESSION['responseMessage'])) {
            $responseMessage = $_SESSION['responseMessage'];
            $style = $_SESSION['responseStyle'] ?? 'bg-success-500';
            unset($_SESSION['responseMessage'], $_SESSION['responseStyle']);
            return ['message' => $responseMessage, 'style' => $style];
        }
        return null;
    }

    function validateFileUpload($file, $allowedTypes = ['image/png', 'image/jpeg']) {
        // Validar tipo de arquivo
        if ($file && !in_array($file['type'], $allowedTypes)) {
            $_SESSION['responseMessage'] = 'Arquivo inválido. Apenas PNG e JPG são permitidos.';
            $_SESSION['responseStyle'] = 'bg-danger-500';
            return false;
        }
        return true;
    }
}
