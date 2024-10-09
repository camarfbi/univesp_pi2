<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require './includes/Search.php'; 
require './includes/helpers.php'; // Inclua corretamente o helpers.php

use App\Includes\Auth;
use App\Includes\Database;
use App\Includes\User;
use App\Includes\UserSearch;
use App\Includes\PermissionManager;

// Importa as funções utilitárias
use function App\Includes\checkAuthentication;
use function App\Includes\checkPermission;
use function App\Includes\handleResponseMessage;

// Inicializa a conexão com o banco de dados e a autenticação
$db = new Database();
$pdo = $db->getPdo();

$auth = new Auth(new User($pdo));
checkAuthentication($auth); // Verifica se o usuário está autenticado

$userId = $_SESSION['user_id'];

// Inicializa o PermissionManager para verificar permissões
$permissionManager = new PermissionManager($pdo, $userId);
$pageAndDir = $permissionManager->getCurrentPageAndDirectory();
checkPermission($permissionManager, $pageAndDir['page'], $pageAndDir['dir']); // Verifica permissões

// Captura os parâmetros de filtro e paginação
$recordsPerPage = $_GET['recordsPerPage'] ?? 10;
$currentPage = $_GET['pageNum'] ?? 1;
$searchTerm = trim($_GET['search'] ?? '');

// Chamada para buscar os usuários
$userSearch = new UserSearch($pdo, (int)$recordsPerPage, (int)$currentPage);
$users = $userSearch->searchUsers($searchTerm);
$totalRecords = $userSearch->getTotalRecords($searchTerm);
$pagination = $userSearch->generatePagination($totalRecords, $searchTerm, $_SERVER['REQUEST_URI']);

// Exibe as informações do usuário logado
$userModel = new User($pdo);
$userInfo = $userModel->getUserById($userId);
$foto = !empty($userInfo['foto']) ? $userInfo['foto'] : '../assets/images/all-img/user.png';
$nome = $userInfo['nome'] ?? 'Usuário';
$admin = $userInfo['admin'] ?? 0;

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listagem de Usuários</title>
    <style>
    .table-td {
        text-transform: none;
    }
/* Certifique-se que o menu está acessível em termos de z-index */
.menu {
    z-index: 1100; /* Mais alto que o modal */
}

/* O modal deve ter z-index mais baixo ou ser manipulado adequadamente */
.modal {
    z-index: 1050;
}

/* Overlay para modais */
.modal-backdrop {
    z-index: 1040; /* Menor que o modal, maior que o conteúdo de fundo */
}

    </style>
</head>
<body>

<!-- Breadcrumb -->
<div class="mb-5">
    <ul class="m-0 p-0 list-none navItem">
        <li style="padding:0px;display: flex;align-items: center;">
            <iconify-icon icon="heroicons-outline:home"></iconify-icon>
            <iconify-icon icon="heroicons-outline:chevron-right"></iconify-icon>Usuários
            <iconify-icon icon="heroicons-outline:chevron-right"></iconify-icon><b>Listar Usuários</b>
        </li>
    </ul>
</div>

<!-- Card para a listagem de usuários -->
<div class="card">
    <header class="card-header noborder">
        <h4 class="card-title">Listagem de Usuários</h4>
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
											<select name="recordsPerPage" aria-controls="DataTables_Table_0">
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
											<input type="search" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Digite para pesquisar">
										</label>
									</div>
									<button type="submit" class="btn">Filtrar</button>
								</div>
							</div>
							
							<!-- Adicione campos ocultos para incluir a página e diretório corretos -->
							<input type="hidden" name="page" value="admin/list-users">
						</form>


                        <!-- Tabela de usuários -->
                        <div class="min-w-full">
                            <table class="min-w-full divide-y divide-slate-100 table-fixed dark:divide-slate-700 data-table dataTable no-footer" id="DataTables_Table_0">
                                <thead class="bg-slate-200 dark:bg-slate-700">
                                    <tr>
                                        <th scope="col" class="table-th">Id</th>
                                        <th scope="col" class="table-th">Nome</th>
                                        <th scope="col" class="table-th">E-mail</th>
                                        <th scope="col" class="table-th">Usuário</th>
                                        <th scope="col" class="table-th">Status</th>
                                        <th scope="col" class="table-th">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="user-table-body" class="bg-white divide-y divide-slate-100 dark:bg-slate-800 dark:divide-slate-700">
                                    <?php if ($users): ?>
                                        <?php foreach ($users as $user): ?>
                                        <?php $foto = $user['foto']; ?>
                                    <tr class="odd">
                                        <td class="table-td"><?php echo $user['id']; ?></td>
                                        <td class="table-td">
                                            <span class="flex">
                                                <span class="w-7 h-7 rounded-full ltr:mr-3 rtl:ml-3 flex-none">
                                                    <img src="<?php echo $foto; ?>" alt="1" class="object-cover w-full h-full rounded-full">
                                                </span>
                                                <span class="text-sm text-slate-600 dark:text-slate-300 capitalize"><?php echo $user['nome']; ?></span>
                                            </span>
                                        </td>
                                        <td class="table-td"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td class="table-td "><?php echo $user['user']; ?></td>
                                        <td class="table-td ">
                                            <?php if ($user['bloqueado'] == 0): ?>
                                                <div class='inline-block px-3 min-w-[90px] text-center mx-auto py-1 rounded-[999px] bg-opacity-25 text-success-500 bg-success-500'>Habilitado</div>
                                            <?php else: ?>
                                                <div class='inline-block px-3 min-w-[90px] text-center mx-auto py-1 rounded-[999px] bg-opacity-25 text-danger-500 bg-danger-500'>Bloqueado</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="table-td ">
                                            <div class="flex space-x-3 rtl:space-x-reverse">
                                                <button class="action-btn" type="button">
                                                    <iconify-icon icon="heroicons:eye"></iconify-icon>
                                                </button>
												<?php if (($admin > 0 && $user['admin'] < 2) || $admin == 2) : ?>
													<a href="dashboard.php?page=admin/users&id=<?php echo $user['id']; ?>">
														<button class="action-btn" type="button">
															<iconify-icon icon="heroicons:pencil-square"></iconify-icon>
														</button>
													</a>
													<button class="action-btn" type="button">
														<iconify-icon icon="heroicons:trash"></iconify-icon>
													</button>
												<?php endif; ?>

                                            </div>
                                        </td>
                                    </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" style="padding-top: 20px;"><span style="padding-left: 30px">Nenhum usuário encontrado!</span></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
						<table class="min-w-full divide-y divide-slate-100 table-fixed dark:divide-slate-700 data-table dataTable no-footer" id="DataTables_Table_0">
						<thead class="bg-slate-200 dark:bg-slate-700">
							<tr>
								<th scope="col" class="table-th">Id</th>
								<th scope="col" class="table-th">Nome</th>
								<th scope="col" class="table-th">E-mail</th>
								<th scope="col" class="table-th">Usuário</th>
								<th scope="col" class="table-th">Status</th>
								<th scope="col" class="table-th">Ações</th>
							</tr>
						</thead>
						</table>

                        <!-- Paginação -->
                        <div class="flex justify-end items-center">
                            <?php if ($totalRecords > 1): ?>
                            <div class="dataTables_paginate paging_simple_numbers" id="DataTables_Table_0_paginate">
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

<script src="../assets/js/users.js"></script>
<script src="../assets/js/common.js"></script>
<script>
$(document).ready(function() {
	reinitializeScripts();
	loadData();
});

</script>
</body>
</html>
