<?php
declare(strict_types=1);

use App\Includes\Auth;
use App\Includes\Database;
use App\Includes\User;

require 'vendor/autoload.php'; 
require 'includes/Database.php'; 
require 'includes/User.php';
require 'includes/Auth.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$userModel = new User($db->getPdo());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $data = [
        'nome' => $_POST['nome'],
        'sobrenome' => $_POST['sobrenome'],
        'email' => $_POST['email']
    ];

    if ($userModel->updateUser($id, $data)) {
        echo 'Usuário atualizado com sucesso!';
        header('Location: dashboard.php');
        exit();
    } else {
        echo 'Erro ao atualizar usuário.';
    }
} else {
    echo 'Método de solicitação inválido!';
}
?>