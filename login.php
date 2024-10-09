<?php
session_start();

use App\Includes\Database;
use App\Includes\User;
use App\Includes\Auth;

require 'vendor/autoload.php';
require 'includes/Database.php';
require 'includes/User.php';
require 'includes/Auth.php';

$db = new Database();
$userModel = new User($db->getPdo());
$auth = new Auth($userModel);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = 'Preencha todos os campos.';
        header('Location: index.php');
        exit();
    }

    // Tenta o login
    if ($auth->login($username, $password)) {
        if ($remember) {
            setcookie('user_id', $_SESSION['user_id'], time() + (86400 * 30), "/");
        } else {
            setcookie('user_id', '', time() - 3600, "/");
        }
        header('Location: dashboard');
        exit();
    } else {
        // Exibe a mensagem de erro de login ou bloqueio
        header('Location: index.php');
        exit();
    }
}


?>
