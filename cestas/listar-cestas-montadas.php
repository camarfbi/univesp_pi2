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
use App\Includes\CestaMontadaSearch; // Supondo que você tenha uma classe semelhante para buscar cestas
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

// Busca as cestas montadas
$cestaMontadaSearch = new CestaMontadaSearch($pdo, (int)$recordsPerPage, (int)$currentPage);
$cestasMontadas = $cestaMontadaSearch->searchCestasMontadas($searchTerm);
$totalRecords = $cestaMontadaSearch->getTotalRecordsCestasMontadas($searchTerm);
$pagination = $cestaMontadaSearch->generatePagination($totalRecords, $searchTerm, $_SERVER['REQUEST_URI']);

// Processamento do formulário (exclusão de cestas montadas)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['acao'] === 'DEL_CESTA_MONTADA') {
    $idCestaMontada = (int) $_POST['id']; 
    if ($cestaMontadaSearch->deleteCestaMontada($idCestaMontada)) {
        $responseMessage = ['message' => 'Cesta montada excluída com sucesso!', 'style' => 'bg-success-500'];
        header("Location: dashboard.php?page=cestas/listar-cestas-montadas");
        exit();
    } else {
        $responseMessage = ['message' => 'Falha ao excluir a cesta montada.', 'style' => 'bg-danger-500'];
    }
}

// Processamento do formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['acao'] === 'EDIT_CESTA_MONTADA') {
    $idCestaMontada = (int)$_POST['id'];
    $quantidade = (int)$_POST['quant_criada'];
    $data = $_POST['data'];

    // Atualiza a cesta montada
    if ($cestaMontadaSearch->updateCestaMontada($idCestaMontada, $quantidade, $data)) {
        $responseMessage = ['message' => 'Cesta montada atualizada com sucesso!', 'style' => 'bg-success-500'];
		header("Location: dashboard.php?page=cestas/listar-cestas-montadas");
    } else {
        $responseMessage = ['message' => 'Falha ao atualizar a cesta montada.', 'style' => 'bg-danger-500'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listagem de Cestas Montadas</title>
</head>
<body>

<!-- Breadcrumb -->
<div class="mb-5">
    <ul class="m-0 p-0 list-none navItem">
        <li style="padding:0px;display: flex;align-items: center;">
            <iconify-icon icon="heroicons-outline:home"></iconify-icon>
            <iconify-icon icon="heroicons-outline:chevron-right"></iconify-icon>Cestas
            <iconify-icon icon="heroicons-outline:chevron-right"></iconify-icon><b>Listar Cestas Montadas</b>
        </li>
    </ul>
</div>

<!-- Card para a listagem de cestas montadas -->
<div class="card">
    <header class="card-header noborder">
        <h4 class="card-title">Listagem de Cestas Montadas</h4>
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
                            <input type="hidden" name="page" value="cestas/listar-cestas-montadas">
                        </form>

						<!-- Tabela de cestas montadas -->
						<table class="min-w-full divide-y divide-slate-100 table-fixed dark:divide-slate-700 data-table no-footer">
							<thead class="bg-slate-200 dark:bg-slate-700">
								<tr>
									<th class="table-th">Id</th>
									<th class="table-th">Nome da Cesta</th>
									<th class="table-th">Data</th>
									<th class="table-th">Quantidade</th>
									<th class="table-th">Ações</th>
								</tr>
							</thead>
							<tbody class="bg-white divide-y divide-slate-100 dark:bg-slate-800 dark:divide-slate-700">
								<?php if ($cestasMontadas): ?>
									<?php foreach ($cestasMontadas as $cesta): ?>
								<tr>
									<td class="table-td"><?php echo $cesta['id']; ?></td>
									<td class="table-td"><?php echo htmlspecialchars($cesta['nome_cesta']); ?></td>
									<td class="table-td"><?php echo date('d/m/Y', strtotime($cesta['data'])); ?></td>
									<td class="table-td"><?php echo $cesta['quant_criada']; ?></td>
									<td class="table-td">
										<div class="flex space-x-3 rtl:space-x-reverse">
											<button class="action-btn" type="button" onclick="openEditModal(<?php echo $cesta['id']; ?>, '<?php echo $cesta['data']; ?>', <?php echo $cesta['quant_criada']; ?>)">
												<iconify-icon icon="heroicons:pencil-square"></iconify-icon>
											</button>
											<button class="action-btn" type="button" onclick="deletarCestaMontada(<?php echo $cesta['id']; ?>)">
												<iconify-icon icon="heroicons:trash"></iconify-icon>
											</button>
										</div>
									</td>
								</tr>
									<?php endforeach; ?>
								<?php else: ?>
									<tr>
										<td colspan="5">Nenhuma cesta montada encontrada!</td>
									</tr>
								<?php endif; ?>
							</tbody>
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
</div>

<!-- Modal de Edição -->
<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-500 bg-opacity-50">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-2xl font-semibold mb-4">Editar Cesta Montada</h2>
        
        <form method="POST" action="">
            <input type="hidden" name="acao" value="EDIT_CESTA_MONTADA">
            <input type="hidden" name="id" id="editCestaId">

            <label for="data">Data:</label>
            <input type="date" name="data" id="editData" class="form-control w-full mt-1 mb-4" required>

            <label for="quant_criada">Quantidade:</label>
            <input type="number" name="quant_criada" id="editQuantidade" class="form-control w-full mt-1 mb-4" required>

            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(id, data, quantidade) {
    document.getElementById('editCestaId').value = id;
    document.getElementById('editData').value = data;
    document.getElementById('editQuantidade').value = quantidade;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function deletarCestaMontada(idCestaMontada) {
    if (confirm('Tem certeza que deseja deletar esta cesta montada?')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;

        var hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = 'acao';
        hiddenField.value = 'DEL_CESTA_MONTADA';

        var idField = document.createElement('input');
        idField.type = 'hidden';
        idField.name = 'id';
        idField.value = idCestaMontada;

        form.appendChild(hiddenField);
        form.appendChild(idField);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

</body>
</html>