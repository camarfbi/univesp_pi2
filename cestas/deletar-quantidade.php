<?php
declare(strict_types=1);

//var_dump($_POST);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require './includes/helpers.php';
require './includes/Cesta.php';
require './includes/SessionMessage.php';

use App\Includes\Auth;
use App\Includes\Database;
use App\Includes\User;
use App\Includes\PermissionManager;
use App\Includes\Cesta;
use App\Includes\SessionMessage;

use function App\Includes\checkAuthentication;
use function App\Includes\checkPermission;
use function App\Includes\buildRedirectUrl;

$db = new Database();
$pdo = $db->getPdo();

$auth = new Auth(new User($pdo));
checkAuthentication($auth);

$userId = $_SESSION['user_id'];
$permissionManager = new PermissionManager($pdo, $userId);
$pageAndDir = $permissionManager->getCurrentPageAndDirectory();
checkPermission($permissionManager, $pageAndDir['page'], $pageAndDir['dir']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['acao1'] === 'DELETE_CESTA') {
    $idCesta = (int)$_POST['id_cesta'];

    if ($idCesta > 0) {
        // Inicia uma transação para garantir a consistência
        $pdo->beginTransaction();

        try {
            // Exclui todos os produtos associados a esta cesta na tabela `cesta_produtos`
            $stmt = $pdo->prepare("DELETE FROM cesta_produtos WHERE id_cesta = :id_cesta");
            $stmt->execute([':id_cesta' => $idCesta]);

            // Exclui a cesta da tabela `cesta`
            $stmt = $pdo->prepare("DELETE FROM cesta WHERE id = :id_cesta");
            $stmt->execute([':id_cesta' => $idCesta]);

            // Confirma a transação
            $pdo->commit();

            $_SESSION['message'] = 'Cesta e produtos excluídos com sucesso!';
        } catch (Exception $e) {
            // Reverte a transação em caso de erro
            $pdo->rollBack();
            $_SESSION['message'] = 'Erro ao excluir a cesta: ' . $e->getMessage();
        }

        // Redireciona de volta para a página principal de cestas
        header("Location: dashboard.php?page=cestas/default-cestas");
        exit;
    }
}

?>
