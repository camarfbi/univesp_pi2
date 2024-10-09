<?php
declare(strict_types=1);

require '../vendor/autoload.php';
require '../includes/Database.php';
require '../includes/User.php';

use App\Includes\Database;
use App\Includes\User;

$db = new Database();
$pdo = $db->getPdo();
$userModel = new User($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = isset($_POST['userId']) ? (int)$_POST['userId'] : null;
    $nome = $_POST['nome'] ?? '';
    $sobrenome = $_POST['sobrenome'] ?? '';
    $email = $_POST['email'] ?? '';
    $user = $_POST['user'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $celular = $_POST['celular'] ?? '';
    $password = !empty($_POST['password']) ? $_POST['password'] : null;
    $foto = $_FILES['foto']['name'] ?? null;
    $permissions = $_POST['permissions'] ?? [];

    // Verificar se o nome de usuário já existe
    $existingUser = $userModel->getUserByUsername($user);
    if ($existingUser && $existingUser['id'] !== $userId) {
        // Redirecionar de volta para o formulário com mensagem de erro
        header('Location: ../users.php?error=nome_ja_existe');
        exit;
    }

    if (!$userId) {
        // Inserir novo usuário
        $data = [
            'nome' => $nome,
            'sobrenome' => $sobrenome,
            'user' => $user,
            'email' => $email,
            'descricao' => $descricao,
            'celular' => $celular,
            'password' => $password ? password_hash($password, PASSWORD_DEFAULT) : null,
            'foto' => $foto ? '../admin/imagens/' . $foto : null,
        ];

        if ($userModel->insertUser($data)) {
            // Redirecionar para uma página de sucesso
            header('Location: ../users.php?success=1');
            exit;
        } else {
            // Redirecionar de volta com erro
            header('Location: ../users.php?error=erro_ao_inserir');
            exit;
        }
    } else {
        // Atualizar o usuário existente
        $data = [
            'nome' => $nome,
            'sobrenome' => $sobrenome,
            'user' => $user,
            'email' => $email,
            'descricao' => $descricao,
            'celular' => $celular,
        ];

        if ($password) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($userModel->updateUser($userId, $data)) {
            // Redirecionar para uma página de sucesso
            header('Location: ../users.php?success=1');
            exit;
        } else {
            // Redirecionar de volta com erro
            header('Location: ../users.php?error=erro_ao_atualizar');
            exit;
        }
    }
}
