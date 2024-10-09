<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
    $_SESSION['user_id'] = $_COOKIE['user_id'];
}

require 'vendor/autoload.php';
require 'includes/Database.php';
require 'includes/User.php';
require 'includes/Auth.php';
require 'includes/UserMenu.php';
require 'includes/PermissionManager.php';

use App\Includes\Auth;
use App\Includes\Database;
use App\Includes\User;
use App\Includes\UserMenu;
use App\Includes\PermissionManager;

$db = new Database();
$pdo = $db->getPdo(); // Obtenha o PDO do Database

$userModel = new User($pdo);
$auth = new Auth($userModel);

// Verifique se o usuário está autenticado
if (!$auth->isAuthenticated()) {
    header('Location: index.php');
    exit();
}

// Instancie o PermissionManager
$permissionManager = new PermissionManager($pdo, $_SESSION['user_id']);

// Verifique a permissão para a página atual
$pageLink = 'teste'; // O link da página que você está acessando
if (!$permissionManager->hasPermissionForPage($pageLink)) {
    // Redirecionar ou mostrar uma mensagem de erro se não tiver permissão
    header('HTTP/1.0 403 Forbidden');
    echo 'Você não tem permissão para acessar esta página.';
    exit();
}

// Consultar todos os usuários
$stmt = $pdo->prepare('SELECT * FROM Xusuarios'); // Substitua 'users' pelo nome real da sua tabela de usuários
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

//header('Location: dashboard');

// A senha que você deseja criptografar
$password = '123';

// Gerar o hash da senha
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Exibir o hash para armazenamento
echo $hashedPassword;

try {
    $db = new Database();
    echo '<br>Conexão com o banco de dados estabelecida com sucesso!';
} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage();
}
?>
