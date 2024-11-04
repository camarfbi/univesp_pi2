<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require './includes/Search.php'; 
require './includes/Produto.php'; 
require './includes/helpers.php';

use App\Includes\Auth;
use App\Includes\Database;
use App\Includes\User;
use App\Includes\PermissionManager;
use App\Includes\ProdutoSearch; // Supondo que você tenha uma classe semelhante para buscar cestas
use App\Includes\Produto; // Supondo que você tenha uma classe semelhante para buscar cestas

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


// Busca estoque de produtos no banco de dados
$query_estoque = $pdo->query("SELECT id_prod, quant FROM produtos_estoque");
$produtos_disponiveis = $query_estoque->fetchAll(PDO::FETCH_ASSOC);

// Busca as produtos
$produtoSearch = new ProdutoSearch($pdo, (int)$recordsPerPage, (int)$currentPage);
$produtos = $produtoSearch->searchProduto($searchTerm);
$totalRecords = $produtoSearch->getTotalRecords($searchTerm);
$pagination = $produtoSearch->generatePagination($totalRecords, $searchTerm, $_SERVER['REQUEST_URI']);

// Processamento do formulário (inserção ou exclusão)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && $_POST['acao'] == 'DEL_CESTA') {
        if (isset($_POST['id'])) {
            $idProduto = (int) $_POST['id']; // Captura o ID da cesta a partir do POST
            $produtoModal = new Produto($pdo);

            if ($produtoModal->deleteProduto($idProduto)) {
                // Armazena a mensagem de sucesso
                $responseMessage = [
                    'message' => 'Produto excluída com sucesso!',
                    'style' => 'bg-success-500'
                ];
                // Recarrega a página após excluir
                header("Location: dashboard.php?page=product/list-product");
                exit();
            } else {
                // Armazena a mensagem de erro
                $responseMessage = [
                    'message' => 'Falha ao excluir o produto.',
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
    <title>Listagem de Produtos</title>
</head>
<body>

<!-- Breadcrumb -->
<div class="mb-5">
    <ul class="m-0 p-0 list-none navItem">
        <li style="padding:0px;display: flex;align-items: center;">
            <iconify-icon icon="heroicons-outline:home"></iconify-icon>
            <iconify-icon icon="heroicons-outline:chevron-right"></iconify-icon>Produtos
            <iconify-icon icon="heroicons-outline:chevron-right"></iconify-icon><b>Listar Produtos</b>
        </li>
    </ul>
</div>

<!-- Card para a listagem de produtos -->
<div class="card">
    <header class="card-header noborder">
        <h4 class="card-title">Listagem de Produtos</h4>
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
                            <input type="hidden" name="page" value="product/list-product">
                        </form>

                        <!-- Tabela de produtos -->
                        <div class="min-w-full">
                            <table class="min-w-full divide-y divide-slate-100 table-fixed dark:divide-slate-700 data-table no-footer" id="DataTables_Table_0">
                                <thead class="bg-slate-200 dark:bg-slate-700">
                                    <tr>
                                        <th scope="col" class="table-th">Id</th>
                                        <th scope="col" class="table-th">Nome da Produto</th>
                                        <th scope="col" class="table-th">Tipo</th>
                                        <th scope="col" class="table-th">Un Medida</th>
                                        <th scope="col" class="table-th">Quant</th>
                                        <th scope="col" class="table-th">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-100 dark:bg-slate-800 dark:divide-slate-700">
                                    <?php if ($produtos): ?>
                                        <?php foreach ($produtos as $produto): ?>
                                    <tr>
                                        <td class="table-td"><?php echo $produto['id']; ?></td>
                                        <td class="table-td"><?php echo htmlspecialchars($produto['nome']); ?></td>
                                        <td class="table-td"><?php echo htmlspecialchars($produto['tipo']); ?></td>
                                        <td class="table-td">
												<?php 
													$unidades = json_decode($produto['un_med'], true);
													if (json_last_error() === JSON_ERROR_NONE && is_array($unidades) && !empty($unidades)) {
														echo implode(', ', $unidades);
													} else {
														echo htmlspecialchars($produto['un_med']) ?: 'Nenhuma definida';
													}
												?>
										</td>
										<td class="table-td ">
										<?php
											foreach ($produtos_disponiveis as $produtoEstoque){
												if($produtoEstoque['id_prod'] == $produto['id']) {
													echo $produtoEstoque['quant'];
												}
											};
										?>
										</td>
										<td class="table-td ">
                                            <div class="flex space-x-3 rtl:space-x-reverse">
                                                <a href="dashboard.php?page=product/default-product&id=<?php echo $produto['id']; ?>">
                                                    <button class="action-btn" type="button">
                                                        <iconify-icon icon="heroicons:pencil-square"></iconify-icon>
                                                    </button>
                                                </a>
                                                <button class="action-btn" type="button" onclick="deletarProduto(<?php echo $produto['id']; ?>)">
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
                                        <th scope="col" class="table-th">Nome da Produto</th>
                                        <th scope="col" class="table-th">Tipo</th>
                                        <th scope="col" class="table-th">Un Medida</th>
                                        <th scope="col" class="table-th">Quant</th>
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
function deletarProduto(idProduto) {
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
        idField.value = idProduto;

        form.appendChild(hiddenField);
        form.appendChild(idField);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

</body>
</html>
