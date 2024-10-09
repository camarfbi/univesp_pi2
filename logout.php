<?php
declare(strict_types=1);

require 'vendor/autoload.php';

use App\Includes\Database;
use App\Includes\User;
use App\Includes\Auth;

// Inicialize a autenticação
$db = new Database();
$userModel = new User($db->getPdo());
$auth = new Auth($userModel);

// Executa o logout
$auth->logout();

// Remover o cookie de sessão PHPSESSID
if (isset($_COOKIE['PHPSESSID'])) {
    setcookie('PHPSESSID', '', time() - 3600, '/'); // Remove o cookie de sessão
}

// Remover qualquer cookie persistente (ex: user_id)
if (isset($_COOKIE['user_id'])) {
    setcookie('user_id', '', time() - 3600, "/"); // Remove o cookie persistente de login
}

// Regenera o ID da sessão e destrói o que restar
session_start();
session_regenerate_id(true);
session_destroy();

// Redirecionar para a página de login
header('Location: index.php');
exit();
