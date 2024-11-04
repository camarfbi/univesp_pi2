<?php
declare(strict_types=1);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idCesta = (int)$_POST['id_cesta'];
    $quantidades = $_POST['quantidades'] ?? [];
    $produtosSelecionadosIds = array_keys($quantidades);

    // Remover produtos que foram desmarcados na cesta
    $stmt = $pdo->prepare("
        DELETE FROM cesta_produtos
        WHERE id_cesta = :id_cesta AND id_produto NOT IN (" . implode(',', array_map('intval', $produtosSelecionadosIds)) . ")
    ");
    $stmt->execute([':id_cesta' => $idCesta]);

    // Atualizar ou inserir quantidades para os produtos selecionados
    foreach ($quantidades as $idProduto => $quantidade) {
        $quantidade = (int)$quantidade;

        $stmt = $pdo->prepare("
            INSERT INTO cesta_produtos (id_cesta, id_produto, quantidade)
            VALUES (:id_cesta, :id_produto, :quantidade)
            ON DUPLICATE KEY UPDATE quantidade = :quantidade
        ");
        $stmt->execute([
            ':id_cesta' => $idCesta,
            ':id_produto' => $idProduto,
            ':quantidade' => $quantidade
        ]);
    }

    // Redireciona de volta para a página de edição da cesta
    header('Location: ./dashboard.php?page=cestas/default-cestas&id=' . $idCesta);
    exit;
}
?>
