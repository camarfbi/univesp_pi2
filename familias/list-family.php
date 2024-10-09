<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require './includes/Familias.php';
require './includes/Search.php';
require './includes/helpers.php';

use App\Includes\Auth;
use App\Includes\Database;
use App\Includes\User;
use App\Includes\Associado;
use App\Includes\AssociadoSearch;
use App\Includes\PermissionManager;

// Importa as funções utilitárias
use function App\Includes\checkAuthentication;
use function App\Includes\checkPermission;
use function App\Includes\handleResponseMessage;

$db = new Database();
$pdo = $db->getPdo();

$auth = new Auth(new User($pdo));
checkAuthentication($auth); // Verifica se o usuário está autenticado

$userId = $_SESSION['user_id'];

// Inicializa o PermissionManager
$permissionManager = new PermissionManager($pdo, $userId);
$pageAndDir = $permissionManager->getCurrentPageAndDirectory();
checkPermission($permissionManager, $pageAndDir['page'], $pageAndDir['dir']); // Verifica permissões

// Captura os parâmetros de filtro e paginação
$recordsPerPage = $_GET['recordsPerPage'] ?? 10;
$currentPage = $_GET['pageNum'] ?? 1;
$searchTerm = trim($_GET['search'] ?? '');

// Chamada para buscar as familias 
$associadoSearch = new AssociadoSearch($pdo, (int)$recordsPerPage, (int)$currentPage);
$associados = $associadoSearch->searchAssociados($searchTerm);
$totalRecords = $associadoSearch->getTotalRecords($searchTerm);
$pagination = $associadoSearch->generatePagination($totalRecords, $searchTerm, $_SERVER['REQUEST_URI']);

// Exibe as informações da familia logado
$associadoModel = new Associado($pdo);
$associadoInfo = $associadoModel->getAssociadoById($userId);
$foto = !empty($associadoInfo['foto']) ? $associadoInfo['foto'] : '../assets/images/all-img/user.png';
$nome = $associadoInfo['nome'] ?? 'Associado';
$admin = $associadoInfo['admin'] ?? 0;
$user = $associadoInfo['user'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listagem de Famílias</title>
    <style>
    .table-td {
        text-transform: none;
    }
 	.modal-backdrop.fade.show {
    display: none;
	}
	
	div#disabled_backdrop {
    margin-top: -10px !important;
}
.flex-none.text-2xl.text-slate-600.dark\:text-slate-300 {
    margin-top: -8px;
    font-size: medium;
}
</style>
</head>
<body>

<!-- Breadcrumb -->
<div class="mb-5">
    <ul class="m-0 p-0 list-none navItem">
        <li style="padding:0px;display: flex;align-items: center;">
            <iconify-icon icon="heroicons-outline:home"></iconify-icon>
            <iconify-icon icon="heroicons-outline:chevron-right"></iconify-icon>Famílias
            <iconify-icon icon="heroicons-outline:chevron-right"></iconify-icon><b>Listar Famílias</b>
        </li>
    </ul>
</div>

<!-- Card para a listagem de familias -->
<div class="card">
    <header class="card-header noborder">
        <h4 class="card-title">Listagem de Famílias</h4>
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
							<input type="hidden" name="page" value="familias/list-family">
						</form>

                        <!-- Tabela de familias -->
                        <div class="min-w-full">
                            <table class="min-w-full divide-y divide-slate-100 table-fixed dark:divide-slate-700 data-table dataTable no-footer" id="DataTables_Table_0">
                                <thead class="bg-slate-200 dark:bg-slate-700">
                                    <tr>
                                        <th scope="col" class="table-th">Id</th>
                                        <th scope="col" class="table-th">Nome</th>
                                        <th scope="col" class="table-th">E-mail</th>
                                        <th scope="col" class="table-th">Usuário</th>
                                        <th scope="col" class="table-th">Ativo</th>
                                        <th scope="col" class="table-th">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="associado-table-body" class="bg-white divide-y divide-slate-100 dark:bg-slate-800 dark:divide-slate-700">
                                    <?php if ($associados): ?>
                                        <?php foreach ($associados as $associado): ?>
                                    <tr class="odd">
                                        <td class="table-td"><?php echo $associado['id']; ?></td>
                                        <td class="table-td">
                                            <span class="flex">
                                                <span class="w-7 h-7 rounded-full ltr:mr-3 rtl:ml-3 flex-none">
                                                    <img src="<?php echo !empty($associado['foto']) ? $associado['foto'] : '../assets/images/all-img/family-icon.png';; ?>" alt="1" class="object-cover w-full h-full rounded-full">
                                                </span>
                                                <span class="text-sm text-slate-600 dark:text-slate-300 capitalize"><?php echo $associado['nome']; ?></span>
                                            </span>
                                        </td>
                                        <td class="table-td"><?php echo $associado['email']; ?></td>
                                        <td class="table-td "><?php echo $associado['user']; ?></td>
                                        <td class="table-td ">
                                            <?php if ($associado['bloqueado'] != 1): ?>
                                                <div class='inline-block px-3 min-w-[90px] text-center mx-auto py-1 rounded-[999px] bg-opacity-25 text-success-500 bg-success-500'>Habilitado</div>
                                            <?php else: ?>
                                                <div class='inline-block px-3 min-w-[90px] text-center mx-auto py-1 rounded-[999px] bg-opacity-25 text-danger-500 bg-danger-500'>Bloqueado</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="table-td ">
                                            <div class="flex space-x-3 rtl:space-x-reverse">
                                                <button class="action-btn" type="button" onclick="abrirModalAssociado(<?php echo $associado['id']; ?>)">
													<iconify-icon icon="heroicons:eye"></iconify-icon>
												</button>

													<a href="dashboard.php?page=familias/familias&id=<?php echo $associado['id']; ?>">
														<button class="action-btn" type="button">
															<iconify-icon icon="heroicons:pencil-square"></iconify-icon>
														</button>
													</a>
													<button class="action-btn" type="button">
														<iconify-icon icon="heroicons:trash"></iconify-icon>
													</button>
                                            </div>
                                        </td>
                                    </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" style="padding-top: 20px;"><span style="padding-left: 30px">Nenhum associado encontrado!</span></td>
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
								<th scope="col" class="table-th">Ativo</th>
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
					<!-----Modal------->
					
<!-- Modal header -->
<div class="modal fade fixed top-0 left-0 hidden w-full h-full outline-none overflow-x-hidden overflow-y-auto" id="disabled_backdrop" tabindex="-1" aria-labelledby="disabled_backdrop" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" style="display: none;">
	<div class="modal-dialog relative w-auto pointer-events-none">
		<div style="width: 800px;" class="modal-content border-none shadow-lg relative flex flex-col w-full pointer-events-auto bg-white bg-clip-padding rounded-md outline-none text-current">
			<!-- Cabeçalho do Modal -->
			<div class="flex items-center justify-between p-2 border-b rounded-t dark:border-slate-600 bg-black-500">
			  <button type="button" class="text-slate-400 bg-transparent hover:text-slate-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-slate-600 dark:hover:text-white" data-bs-dismiss="modal">
				<svg aria-hidden="true" class="w-5 h-5" fill="#ffffff" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
				  <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
				</svg>
				<span class="sr-only">Fechar</span>
			  </button>
			</div>
			<div class="card xl:col-span-2">
				<div class="card-body flex flex-col p-6">
				<!-- Corpo do Modal -->
					<form id="dependenteForm" method="POST" enctype="multipart/form-data">
						<input type="hidden" value="DEPENDENTE" name="acao">
						<input type="hidden" name="dependenteId" id="dependenteId">
						<input type="hidden" name="associado_id" value="<?= $editAssociadoId; ?>">

						<div class="modal-header">
							<div class="flex-2">
							  <div class="card-title text-slate-900 dark:text-white"></div>
							</div>
						</div>
						<div class="modal-body">
							<!-- END: BreadCrumb -->
							<div class="space-y-5 profile-page">
							  <div class="profiel-wrap px-[35px] pb-10 md:pt-[84px] pt-10 rounded-lg bg-white dark:bg-slate-800 lg:flex lg:space-y-0
							space-y-6 justify-between items-end relative z-[1]">
								<div class="bg-slate-900 dark:bg-slate-700 absolute left-0 top-0 md:h-1/2 h-[150px] w-full z-[-1] rounded-t-lg"></div>
								<div class="profile-box flex-none md:text-start text-center">
								  <div class="md:flex items-end md:space-x-6 rtl:space-x-reverse">
									<div class="flex-none">
									  <div class="md:h-[186px] md:w-[186px] h-[140px] w-[140px] md:ml-0 md:mr-0 ml-auto mr-auto md:mb-0 mb-4 rounded-full ring-4
											ring-slate-100 relative">
										<img style="background-color: #fff; z-index:2;" src="../assets/images/all-img/user.png" alt="Foto do Associado" id="associadoFotoPreview" alt="" class="w-full h-full object-cover rounded-full">
										<a href="profile-setting" class="absolute right-2 h-8 w-8 bg-slate-50 text-slate-600 rounded-full shadow-sm flex flex-col items-center
												justify-center md:top-[140px] top-[100px]">
										  <iconify-icon icon="heroicons:pencil-square"></iconify-icon>
										</a>
									  </div>
									</div>
									<div class="flex-1">
									  <div class="text-2xl font-medium text-slate-900 dark:text-slate-200 mb-[3px]">
										<h5 class="modal-title">Nome do Associado</h5>
									  </div>
									  <div class="text-sm font-light text-slate-600 dark:text-slate-400">
										Profissão
									  </div>
									</div>
								  </div>
								</div>
							  </div>
							  <div class="grid">
								<div style="margin-top: -74px;z-index: 1;" class="lg:col-span-4 col-span-12">
								  <div class="card h-full">
									<header class="card-header">
									  <h4 class="card-title">Info</h4>
									</header>
									<div class="card-body p-6">
									  <ul class="list space-y-3">
										<li class="flex space-x-2 rtl:space-x-reverse">
										  <div class="flex-none text-2xl text-slate-600 dark:text-slate-300">
											<iconify-icon icon="heroicons:identification"></iconify-icon>
										  </div>
										  <div class="flex-1">
											<div class="text-xs text-slate-500 dark:text-slate-300 mb-1 leading-[12px]">
											  <b>CPF: </b> <span  class="text-xs text-slate-500 dark:text-slate-300 mb-1 leading-[12px]" id="cpf"></span></div>
										  </div>
										</li>
										
										<li class="flex space-x-2 rtl:space-x-reverse">
										  <div class="flex-none text-2xl text-slate-600 dark:text-slate-300">
											<iconify-icon icon="heroicons:identification"></iconify-icon>
										  </div>
										  <div class="flex-1">
											<div class="text-xs text-slate-500 dark:text-slate-300 mb-1 leading-[12px]">
											  <b>RG: </b> <span  class="text-xs text-slate-500 dark:text-slate-300 mb-1 leading-[12px]" id="rg"></span></div>
										  </div>
										</li>
										
										<li class="flex space-x-2 rtl:space-x-reverse">
										  <div class="flex-none text-2xl text-slate-600 dark:text-slate-300">
											<iconify-icon icon="heroicons:envelope"></iconify-icon>
										  </div>
										  <div class="flex-1">
											<div class="text-xs text-slate-500 dark:text-slate-300 mb-1 leading-[12px]">
											  <b>E-mail: </b> <span  class="text-xs text-slate-500 dark:text-slate-300 mb-1 leading-[12px]" id="email"></span></div>
										  </div>
										</li>
										
										<li class="flex space-x-2 rtl:space-x-reverse">
										  <div class="flex-none text-2xl text-slate-600 dark:text-slate-300">
											<iconify-icon icon="heroicons:phone-arrow-up-right"></iconify-icon>
										  </div>
										  <div class="flex-1">
											<div class="text-xs text-slate-500 dark:text-slate-300 mb-1 leading-[12px]">
											  <b>Tel: </b> <span id="telefone"></span></div>
											<div class=" text-xs text-slate-500 dark:text-slate-300 mb-1 leading-[12px]">
											  <b>Cel: </b> <span class=" text-xs text-slate-500 dark:text-slate-300 mb-1 leading-[12px]" id="celular"></span></div>
										  </div>
										</li>
										
										<li class="flex space-x-2 rtl:space-x-reverse">
										  <div class="flex-none text-2xl text-slate-600 dark:text-slate-300">
											<iconify-icon icon="heroicons:phone-arrow-up-right"></iconify-icon>
										  </div>
										  <div class="flex-1">
											<div class="text-xs text-slate-500 dark:text-slate-300 mb-1 leading-[12px]">
											  <b>Nascimento: </b><span id="nascimento"></span></div>
										  </div>
										</li>

										<li class="flex space-x-2 rtl:space-x-reverse">
										  <div class="flex-none text-2xl text-slate-600 dark:text-slate-300">
											<iconify-icon icon="heroicons:map"></iconify-icon>
										  </div>
										  <div class="flex-1">
											<div class=" text-xs text-slate-500 dark:text-slate-300 mb-1 leading-[12px]">
											  <b>Endereço:</b> <span class="text-xs text-slate-500 dark:text-slate-300 mb-1 leading-[12px]"  id="endereco"></span></div>
											</div>
										  </div>
										</li>
										<!-- end single list -->
									  </ul>
									</div>
								  </div>
								</div>
								
							  </div>
							</div>
							<!-- Rodapé do Modal -->
							<div style="padding-top: 20px" class="modal-footer">
								<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<!-----END Modal------->
                </div>
            </div>
        </div> 
    </div>
</div>
<script src="../assets/js/associados.js"></script>
<script src="../assets/js/common.js"></script>
<script>
function abrirModalAssociado(associadoId) {
    // Limpa o conteúdo do modal antes de carregar novos dados
    $('#associadoFotoPreview').attr('src', '../assets/images/all-img/user.png'); 
    $('.modal-title').text('Carregando...');

    // Faz uma requisição AJAX para obter os dados da familia
    $.ajax({
        url: './familias/get_familia.php', // Esta rota deve retornar os dados da familia com base no ID
        type: 'GET',
        data: { id: associadoId },
        dataType: 'json',
        success: function(data) {
            // Preenche o modal com os dados da familia
            $('#associadoFotoPreview').attr('src', data.foto || '../assets/images/all-img/user.png');
            $('.modal-title').text(data.nome + ' ' + data.sobrenome);
			let dataNascimento = data.nascimento;
			let partesData = dataNascimento.split('-');
			let dataInvertida = `${partesData[2]}/${partesData[1]}/${partesData[0]}`;
            $('#nascimento').text(dataInvertida);
            $('#cpf').text(data.cpf);
            $('#rg').text(data.rg);
            $('#email').text(data.email);
            $('#telefone').text(data.telefone);
            $('#celular').text(data.celular);
            $('#endereco').text(data.endereco + ', ' + data.num + ', ' + data.bairro + ', ' + data.cidade + ' - ' + data.uf);

            // Abre o modal
            $('#disabled_backdrop').modal('show');
        },
        error: function(xhr, status, error) {
            console.error('Erro ao buscar os dados da familia:', error);
        }
    });
}

$(document).ready(function() {
	reinitializeScripts();
	loadData();
});
</script>
</body>
</html>
