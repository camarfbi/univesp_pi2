<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require './includes/Search.php'; 
require './includes/Cesta.php'; 
require './includes/helpers.php';

use App\Includes\Auth;
use App\Includes\Database;
use App\Includes\User;
use App\Includes\PermissionManager;
use App\Includes\CestaSearch; // Supondo que você tenha uma classe semelhante para buscar cestas
use App\Includes\Cesta; // Supondo que você tenha uma classe semelhante para buscar cestas

// Funções utilitárias
use function App\Includes\checkAuthentication;
use function App\Includes\checkPermission;

$db = new Database();
$pdo = $db->getPdo();

$auth = new Auth(new User($pdo));
checkAuthentication($auth); // Verifica se o usuário está autenticado

$userId = $_SESSION['user_id'];
$permissionManager = new PermissionManager($pdo, $userId);
$pageAndDir = $permissionManager->getCurrentPageAndDirectory();
checkPermission($permissionManager, $pageAndDir['page'], $pageAndDir['dir']); // Verifica permissões

// Parâmetros de filtro e paginação
$recordsPerPage = $_GET['recordsPerPage'] ?? 10;
$currentPage = $_GET['pageNum'] ?? 1;
$searchTerm = trim($_GET['search'] ?? '');

// Busca as cestas
$cestaSearch = new CestaSearch($pdo, (int)$recordsPerPage, (int)$currentPage);
$cestas = $cestaSearch->searchCestas($searchTerm);
$totalRecords = $cestaSearch->getTotalRecords($searchTerm);
$pagination = $cestaSearch->generatePagination($totalRecords, $searchTerm, $_SERVER['REQUEST_URI']);

// Processamento do formulário (inserção ou exclusão)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && $_POST['acao'] == 'DEL_CESTA') {
        if (isset($_POST['id'])) {
            $idCesta = (int) $_POST['id']; // Captura o ID da cesta a partir do POST
            $cestaModal = new Cesta($pdo);

            if ($cestaModal->deleteCesta($idCesta)) {
                // Armazena a mensagem de sucesso
                $responseMessage = [
                    'message' => 'Cesta excluída com sucesso!',
                    'style' => 'bg-success-500'
                ];
                // Recarrega a página após excluir
                header("Location: dashboard.php?page=cestas/list-cestas");
                exit();
            } else {
                // Armazena a mensagem de erro
                $responseMessage = [
                    'message' => 'Falha ao excluir a cesta.',
                    'style' => 'bg-danger-500'
                ];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listagem de Cestas</title>
</head>
<body>

<!-- Breadcrumb -->
<div class="mb-5">
    <ul class="m-0 p-0 list-none navItem">
        <li style="padding:0px;display: flex;align-items: center;">
            <iconify-icon icon="heroicons-outline:home"></iconify-icon>
            <iconify-icon icon="heroicons-outline:chevron-right"></iconify-icon>Cestas
            <iconify-icon icon="heroicons-outline:chevron-right"></iconify-icon><b>Listar Cestas</b>
        </li>
    </ul>
</div>

<!-- Card para a listagem de cestas -->
<div class="card">
    <header class="card-header noborder">
        <h4 class="card-title">Listagem de Cestas</h4>
    </header>
    <div class="card-body px-6 pb-6">
        <div class="overflow-x-auto -mx-6 dashcode-data-table">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-hidden">
                    <div id="DataTables_Table_0_wrapper" class="dataTables_wrapper no-footer">
                        <form method="GET" action="dashboard.php">
                            <div class="grid grid-cols-12 gap-5 px-6 mt-6">
                                <div class="col-span-4">
                                    <div class="dataTables_length">
                                        <label>Exibir 
                                            <select name="recordsPerPage">
                                                <option value="10" <?php echo $recordsPerPage == 10 ? 'selected' : ''; ?>>10</option>
                                                <option value="25" <?php echo $recordsPerPage == 25 ? 'selected' : ''; ?>>25</option>
                                                <option value="50" <?php echo $recordsPerPage == 50 ? 'selected' : ''; ?>>50</option>
                                                <option value="100" <?php echo $recordsPerPage == 100 ? 'selected' : ''; ?>>100</option>
                                            </select> registros
                                        </label>
                                    </div>
                                </div>
                                <div class="col-span-8 flex justify-end">
                                    <div class="dataTables_filter">
                                        <label>Pesquisar:
                                            <input type="search" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Pesquisar por nome">
                                        </label>
                                    </div>
                                    <button type="submit" class="btn">Filtrar</button>
                                </div>
                            </div>
                            <input type="hidden" name="page" value="cestas/list-cestas">
                        </form>

                        <!-- Tabela de cestas -->
                        <div class="min-w-full">
                            <table class="min-w-full divide-y divide-slate-100 table-fixed dark:divide-slate-700 data-table no-footer" id="DataTables_Table_0">
                                <thead class="bg-slate-200 dark:bg-slate-700">
                                    <tr>
                                        <th scope="col" class="table-th">Id</th>
                                        <th scope="col" class="table-th">Nome da Cesta</th>
                                        <th scope="col" class="table-th">Tipo</th>
                                        <th scope="col" class="table-th">Produtos</th>
                                        <th scope="col" class="table-th">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-100 dark:bg-slate-800 dark:divide-slate-700">
                                    <?php if ($cestas): ?>
                                        <?php foreach ($cestas as $cesta): ?>
                                    <tr>
                                        <td class="table-td"><?php echo $cesta['id']; ?></td>
                                        <td class="table-td"><?php echo htmlspecialchars($cesta['nome']); ?></td>
                                        <td class="table-td"><?php echo htmlspecialchars($cesta['tipo']); ?></td>
                                        <td class="table-td">
											<?php 
												$produtos = json_decode($cesta['produtos'], true);
												if (is_array($produtos) && !empty($produtos)) {
													echo implode(', ', $produtos);
												} else {
													echo 'Nenhum produto selecionado';
												}
											?>
										</td>
                                        <td class="table-td ">
                                            <div class="flex space-x-3 rtl:space-x-reverse">
                                                <a href="dashboard.php?page=cestas/default-cestas&id=<?php echo $cesta['id']; ?>">
                                                    <button class="action-btn" type="button">
                                                        <iconify-icon icon="heroicons:pencil-square"></iconify-icon>
                                                    </button>
                                                </a>
                                                <button class="action-btn" type="button" onclick="deletarCesta(<?php echo $cesta['id']; ?>)">
                                                    <iconify-icon icon="heroicons:trash"></iconify-icon>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5">Nenhuma cesta encontrada!</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
								<thead class="bg-slate-200 dark:bg-slate-700">
                                    <tr>
                                        <th scope="col" class="table-th">Id</th>
                                        <th scope="col" class="table-th">Nome da Cesta</th>
                                        <th scope="col" class="table-th">Tipo</th>
                                        <th scope="col" class="table-th">Produtos</th>
                                        <th scope="col" class="table-th">Ações</th>
                                    </tr>
                                </thead>
                            </table>


                        <!-- Paginação -->
                        <div class="flex justify-end items-center">
                            <?php if ($totalRecords > 1): ?>
                                <div class="dataTables_paginate paging_simple_numbers">
                                    <?php echo $pagination; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div></div>

<script>
function deletarCesta(idCesta) {
    if (confirm('Tem certeza que deseja deletar esta cesta?')) {
        // Submete o formulário com o ID da cesta para exclusão via POST
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = ''; // A ação será a mesma página

        var hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = 'acao';
        hiddenField.value = 'DEL_CESTA';

        var idField = document.createElement('input');
        idField.type = 'hidden';
        idField.name = 'id';
        idField.value = idCesta;

        form.appendChild(hiddenField);
        form.appendChild(idField);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

</body>
</html>
