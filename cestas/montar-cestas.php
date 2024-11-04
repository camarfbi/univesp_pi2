<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require './includes/helpers.php';
require './includes/Cesta.php';
require './includes/CalcularCestas.php';
require './includes/SessionMessage.php';

use App\Includes\Auth;
use App\Includes\Database;
use App\Includes\User;
use App\Includes\PermissionManager;
use App\Includes\Cesta;
use App\Includes\CalcularCestas;
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

// Instância da classe CalcularCestas
$calcularCestas = new CalcularCestas($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cesta = (int)$_POST['id_cesta'];
    $mes_ano = $_POST['mes_ano'];
    $quantidade = (int)$_POST['quant_cesta'];

    // Formata a data para o primeiro dia do mês escolhido
    $data = $mes_ano . "-01";

    // Verificar e deduzir estoque
    if ($calcularCestas->deduzirEstoque($id_cesta, $quantidade)) {
        // Salva ou atualiza a quantidade de cestas criada
        $calcularCestas->salvarCestasCriadas($id_cesta, $data, $quantidade);
        echo "<p>Cestas criadas com sucesso e estoque atualizado!</p>";
    } else {
        echo "<p>Erro: Estoque insuficiente para montar a quantidade de cestas solicitada.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Montar Cesta</title>
</head>
<body>

   <div class="card xl:col-span-2">
        <div class="card-body flex flex-col p-6">
            <header class="flex mb-5 items-center border-b border-slate-100 dark:border-slate-700 pb-5">
                <div class="flex-2">
                    <div class="card-title text-slate-900 dark:text-white">
                    </div>
                </div>
            </header>
            <div class="card-text h-full space-y-4">
                 <label for="nome_cesta">Cesta a ser montada:</label>

    <form method="POST">
        <label for="mes_ano">Data (Ano e Mês):</label>
        <input class="form-control" type="month" name="mes_ano" required><br>
		
        <label for="id_cesta">Cesta:</label>
        <select class="form-control" name="id_cesta" required>
            <?php
            // Consultar cestas disponíveis para seleção
            $stmt = $pdo->query("SELECT id, nome FROM cesta");
            $cestas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cestas as $cesta) {
                echo "<option value='{$cesta['id']}'>{$cesta['nome']}</option>";
            }
            ?>
        </select><br>

        <label for="quant_cesta">Quantidade:</label>
        <input class="form-control" type="number" name="quant_cesta" min="1" required><br>

        <button class="btn inline-flex justify-center btn-dark" type="submit">Montar Cestas</button>
    </form>
	        </div>
		</div>
    </div>
</body>
</html>

