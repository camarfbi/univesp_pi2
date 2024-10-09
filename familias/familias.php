<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require './includes/Search.php'; 
require './includes/Familias.php';
require './includes/SessionMessage.php';
require './includes/funcoes.php';

use App\Includes\Auth;
use App\Includes\Database;
use App\Includes\User;
use App\Includes\Associado;
use App\Includes\PermissionManager;
use App\Includes\SessionMessage;

// Inicializa a conexão com o banco de dados e a autenticação
$db = new Database();
$pdo = $db->getPdo();

// Verifica se o usuário está autenticado
$auth = new Auth(new User($pdo));
if (!$auth->isAuthenticated()) {
    header('Location: index.php');
    exit();
}

// ID do usuário logado (admin)
$userLogadoId = $_SESSION['user_id'];

// Inicializa o PermissionManager
$permissionManager = new PermissionManager($pdo, $userLogadoId);

// Obtém o caminho da página dinamicamente
$pageAndDir = $permissionManager->getCurrentPageAndDirectory();
$dirAndPage = $pageAndDir['dir'] . "/" . $pageAndDir['page'];

// Verifica permissão
if (!$permissionManager->hasPermission($pageAndDir['page'], $pageAndDir['dir'])) {
    SessionMessage::setResponseAndRedirect("Você não tem permissão para acessar esta página.", 'bg-danger-500', $dirAndPage);
}

// Inicializa o model do Associado
$associadoModel = new Associado($pdo);

// Captura o ID do associado da URL (se existir)
$editAssociadoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Se for edição, busca as informações do associado
$editAssociado = $editAssociadoId ? $associadoModel->getAssociadoById($editAssociadoId) : null;

// Preenche as variáveis com os valores do associado, se for edição
$nome = $editAssociado['nome'] ?? '';
$sobrenome = $editAssociado['sobrenome'] ?? '';
$cpf = $editAssociado['cpf'] ?? '';
$rg = $editAssociado['rg'] ?? '';
$nascimento = $editAssociado['nascimento'] ?? '';
$email = $editAssociado['email'] ?? '';
$telefone = $editAssociado['telefone'] ?? '';
$celular = $editAssociado['celular'] ?? '';
$cep = $editAssociado['cep'] ?? '';
$endereco = $editAssociado['endereco'] ?? '';
$num = $editAssociado['num'] ?? '';
$complemento = $editAssociado['complemento'] ?? '';
$bairro = $editAssociado['bairro'] ?? '';
$cidade = $editAssociado['cidade'] ?? '';
$uf = $editAssociado['uf'] ?? '';
$foto = $editAssociado['foto'] ?? './assets/images/all-img/family-icon.png';
$status = $editAssociado['status'] ?? 0;
$bloqueado = $editAssociado['bloqueado'] ?? 0;
$user = $editAssociado['user'] ?? '';
$password = '';

// Processamento do formulário (inserção ou edição)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && $_POST['acao'] == 'ASSOCIADO') {
        // Captura os dados do formulário
        $formData = $associadoModel->getFormData($_POST, $editAssociado, $editAssociadoId);

        // Verifica se o email já existe para outro responsável
        if ($associadoModel->emailExists($formData['email'], $editAssociadoId)) {
            SessionMessage::setResponseAndRedirect('Este e-mail já existe!', 'bg-danger-500', "{$dirAndPage}&id={$editAssociadoId}");
        }
        
        // Verifica se o usuário já existe para outro responsável
        if ($associadoModel->userExists($formData['user'], $editAssociadoId)) {
            SessionMessage::setResponseAndRedirect('O usuário ' . $formData['user'] . ' já existe!', 'bg-danger-500', "{$dirAndPage}&id={$editAssociadoId}");
        }

        // Atualização de um responsável existente
        if ($editAssociadoId) {
            if ($associadoModel->updateAssociado($editAssociadoId, $formData)) {
                // Verifica e processa a imagem, caso exista no envio do formulário
                if (!empty($_FILES['foto']['name'])) {
                    $formData['foto'] = $associadoModel->processImage($editAssociadoId, $_FILES['foto'], $formData['foto']);
                    $associadoModel->updateAssociado($editAssociadoId, ['foto' => $formData['foto']]);
                }
                SessionMessage::setResponseAndRedirect('Responsável atualizado com sucesso!', 'bg-success-500', "{$dirAndPage}&id={$editAssociadoId}");
            } else {
                SessionMessage::setResponseAndRedirect('Erro ao atualizar responsável!', 'bg-danger-500', "{$dirAndPage}&id={$editAssociadoId}");
            }
        } else {
            // Inserção de um novo responsável
            $newId = $associadoModel->insertAssociado($formData);

            if ($newId) {
                // Processa a imagem com o novo ID
                if (!empty($_FILES['foto']['name'])) {
                    $formData['foto'] = $associadoModel->processImage($newId, $_FILES['foto'], null);
                    $associadoModel->updateAssociado($newId, ['foto' => $formData['foto']]);
                }
                SessionMessage::setResponseAndRedirect('Responsável cadastrado com sucesso!', 'bg-success-500', "{$dirAndPage}&id={$newId}");
            } else {
                SessionMessage::setResponseAndRedirect('Erro ao inserir responsável!', 'bg-danger-500', "{$dirAndPage}");
            }
        }
    }
}

// Exibir mensagem de resposta, se houver
$responseMessage = SessionMessage::getMessage();
?>

<!-- Exibe a mensagem de resposta -->
<?php if ($responseMessage): ?>
<div class="py-[18px] px-6 font-normal text-sm rounded-md <?php echo htmlspecialchars($responseMessage['style']); ?> text-white">
  <div class="flex items-center space-x-3 rtl:space-x-reverse">
    <iconify-icon class="text-2xl flex-0" icon="system-uicons:target"></iconify-icon>
    <p class="flex-1 font-Inter">
      <?php echo htmlspecialchars($responseMessage['message']); ?>
    </p>
    <div class="flex-0 text-xl cursor-pointer">
      <iconify-icon icon="line-md:close"></iconify-icon>
    </div>
  </div>
</div>
<?php endif; ?>

<style>
	.autocomplete-suggestions {
		border: 1px solid #ccc;
		background: #fff;
		max-height: 150px;
		overflow-y: auto;
		position: absolute;
		z-index: 1000;
	}

	.autocomplete-suggestion {
		padding: 8px;
		cursor: pointer;
	}

	.autocomplete-suggestion:hover {
		background: #e9e9e9;
	}

	button.action-btn {
		display: inline-flex;
	}

	.relative-1{
		padding-left: 150px;
	}
	iconify-icon.absolute {
    padding-left: 150px;
	}
	.switch {
    position: relative;
    width: 60px;
    height: 30px;
	}
	
	.form-group {
    margin-bottom: 15px;
	}
</style>
<!-- BEGIN: Breadcrumb -->
<div class="mb-5">
	<ul class="m-0 p-0 list-none navItem">
		<li style="padding:0px;display: flex;align-items: center;">
			<a href="./dashboard.php?page=dash" class="navItem" style="display: inline-block!important;"><iconify-icon icon="heroicons-outline:home"></iconify-icon></a>
			<iconify-icon icon="heroicons-outline:chevron-right" ></iconify-icon>Famílias
			<iconify-icon icon="heroicons-outline:chevron-right"></iconify-icon><a href="dashboard.php?page=familias/list-familias" class="navItem" style="display: inline-block!important;">Listar Famílias</a>
			<iconify-icon icon="heroicons-outline:chevron-right"></iconify-icon><b><?php echo $editAssociadoId == 0 ? 'Cadastrar Responsável' : 'Editar Responsável ' . $nome; ?></b>
		</li>
	</ul>
</div>
<!-- END: BreadCrumb -->

<form id="userForm" method="POST" enctype="multipart/form-data" action="">
	<input type="hidden" value="ASSOCIADO" name="acao"> 
	<input type="hidden" id="userId" name="userId" value="<?= $userId; ?>">

<div class="card xl:col-span-2">
	<div class="card-body flex flex-col p-6">
	  <header class="flex mb-5 items-center border-b border-slate-100 dark:border-slate-700 pb-5 -mx-6 px-6">
		<div class="image" style="width: 100px; float: left; margin-right: 20px;">
			<img src="<?php echo $foto; ?>" style="width: 100px; height: 100px; border-radius: 50px" class="profile-user-img img-fluid img-circle" alt="User Image">
		</div>
		<div class="flex-2">
		  <div class="card-title text-slate-900 dark:text-white"><?php echo $editAssociadoId == 0 ? 'Cadastrar Família' : 'Editar Família: ' . $nome; ?></div>
		</div>
	  </header>
	  <div class="card-text h-full space-y-4">

		<div class="input-area">
		  <div class="relative-1 relative">
			<label for="nome" class="inline-inputLabel">Responsável*:</label>
			<input type="text" id="nome" name="nome" class="form-control" autocomplete="name" placeholder="Nome" value="<?php echo htmlspecialchars($nome); ?>" required>
		  </div>
		</div>

		<div class="input-area">
		  <div class="relative-1 relative">
		<label for="sobrenome" class="inline-inputLabel">Sobrenome*:</label>
			<input type="text" id="sobrenome" name="sobrenome" class="form-control" placeholder="Sobrenome" value="<?php echo htmlspecialchars($sobrenome); ?>" required>
		  </div>
		</div>

		<div class="input-area">
		  <div class="relative-1 relative">
			  <label for="cpf" class="inline-inputLabel">CPF*:</label>
			  <input type="text" id="CPF" name="cpf" class="form-control" placeholder="CPF" value="<?php echo htmlspecialchars($cpf); ?>" required>
			  <span id="userError" class="text-danger"></span> <!-- Mensagem de erro aqui -->
		  </div>
		</div>

		<div class="input-area">
		  <div class="relative-1 relative">
			  <label for="rg" class="inline-inputLabel">RG:</label>
			  <input type="text" id="rg" name="rg" class="form-control" placeholder="rg" value="<?php echo htmlspecialchars($rg); ?>" required>
			  <span id="userError" class="text-danger"></span> <!-- Mensagem de erro aqui -->
		  </div>
		</div>
		
		<div class="input-area">
			<div class="relative-1 relative">
				<label for="nascimento" class="inline-inputLabel">Nascimento*:</label>
				<input type="text" id="nascimento" name="nascimento" class="form-control  !pl-9" placeholder="Somente numeros" value="<?php echo InverteData($nascimento); ?>" required>
				<iconify-icon icon="heroicons-outline:calendar" class="absolute left-2 top-1/2 -translate-y-1/2 text-base text-slate-500"></iconify-icon>
			</div>
		</div>

			
		<div class="input-area">
		  <div class="relative-1 relative group">
		  <label for="email" class="inline-inputLabel">E-mail*:</label>
			<input type="text" id="email" name="email" class="form-control !pl-9" placeholder="E-mail" autocomplete="email" value="<?php echo htmlspecialchars($email); ?>" required>
			<iconify-icon icon="heroicons-outline:mail" class="absolute left-2 top-1/2 -translate-y-1/2 text-base text-slate-500"></iconify-icon>
		  </div>
		</div>	
				
		<div class="input-area">
		  <div class="relative-1 relative group">
		  <label for="telefone" class="inline-inputLabel">Telefone:</label>
			<input type="text" id="telefone" name="telefone" autocomplete="telefone" class="form-control !pl-9" placeholder="Telefone"  value="<?php echo $telefone; ?>">
			<iconify-icon icon="heroicons-outline:phone" class="absolute left-2 top-1/2 -translate-y-1/2 text-base text-slate-500"></iconify-icon>
		  </div>
		</div>
				
		<div class="input-area">
		  <div class="relative-1 relative group">
		  <label for="celular" class="inline-inputLabel">Celular*:</label>
			<input type="text" id="celular" name="celular" class="form-control !pl-9" placeholder="Celular" value="<?php echo $celular; ?>" required>
			<iconify-icon icon="heroicons-outline:device-phone-mobile" class="absolute left-2 top-1/2 -translate-y-1/2 text-base text-slate-500"></iconify-icon>
		  </div>
		</div>

		<div class="input-area">
		  <div class="relative-1 relative">
			  <label for="cep" class="inline-inputLabel">CEP*:</label>
			  <input type="text" id="cep" name="cep" class="form-control" placeholder="cep" onkeyup="mascara(this, mcep, 9);" value="<?php echo htmlspecialchars($cep); ?>" required>
			  <span id="userError" class="text-danger"></span> <!-- Mensagem de erro aqui -->
		  </div>
		</div>

		<div class="input-area">
		  <div class="relative-1 relative">
		  <label for="endereco" class="inline-inputLabel">Endereço:</label>
			<input type="text" id="endereco" name="endereco" class="form-control !pr-32" placeholder="Descrição" value="<?php echo htmlspecialchars($endereco); ?>" required>
			<span class="absolute right-0 top-1/2 px-3 -translate-y-1/2 h-full border-none flex items-center justify-center"></span>
		  </div>
		</div>

		<div class="input-area">
		  <div class="relative-1 relative">
		  <label for="num" class="inline-inputLabel">Numero:</label>
			<input type="text" id="num" name="num" class="form-control !pr-32" placeholder="Numero" value="<?php echo htmlspecialchars($num); ?>" required>
			<span class="absolute right-0 top-1/2 px-3 -translate-y-1/2 h-full border-none flex items-center justify-center"></span>
		  </div>
		</div>

		<div class="input-area">
		  <div class="relative-1 relative">
		  <label for="complemento" class="inline-inputLabel">Complemento:</label>
			<input type="text" id="complemento" name="complemento" class="form-control !pr-32" placeholder="Complemento" value="<?php echo htmlspecialchars($complemento); ?>">
			<span class="absolute right-0 top-1/2 px-3 -translate-y-1/2 h-full border-none flex items-center justify-center"></span>
		  </div>
		</div>

		<div class="input-area">
		  <div class="relative-1 relative">
		  <label for="bairro" class="inline-inputLabel">Bairro:</label>
			<input type="text" id="bairro" name="bairro" class="form-control !pr-32" placeholder="Bairro" value="<?php echo htmlspecialchars($bairro); ?>" required>
			<span class="absolute right-0 top-1/2 px-3 -translate-y-1/2 h-full border-none flex items-center justify-center"></span>
		  </div>
		</div>

		<div class="input-area">
		  <div class="relative-1 relative">
		  <label for="cidade" class="inline-inputLabel">Cidade:</label>
			<input type="text" id="cidade" name="cidade" class="form-control !pr-32" placeholder="Cidade" value="<?php echo htmlspecialchars($cidade); ?>" required>
			<span class="absolute right-0 top-1/2 px-3 -translate-y-1/2 h-full border-none flex items-center justify-center"></span>
		  </div>
		</div>

		<div class="input-area">
		  <div class="relative-1 relative">
		  <label for="uf" class="inline-inputLabel">UF:</label>
			<input type="text" id="uf" name="uf" class="form-control !pr-32" placeholder="UF" value="<?php echo htmlspecialchars($uf); ?>" required>
			<span class="absolute right-0 top-1/2 px-3 -translate-y-1/2 h-full border-none flex items-center justify-center"></span>
		  </div>
		</div>
		
		<div class="input-area" style="width: 800px;">
			<div class="relative-1 relative">
			<label for="foto" class="inline-inputLabel">Foto</label>
			<input type="file" id="foto" name="foto" class="btn inline-flex justify-center btn-outline-secondary rounded-[25px]" id="file_foto" name="foto" accept=".png, .jpg, .jpeg" onchange="validarArquivo(this)"><br>
			<label class="col-form-label custom-file-label" for="customFile">Selecione um arquivo PNG ou JPG</label>
			</div>
		</div>
		
		<div class="input-area">
		  <div class="relative-1 relative">
			  <label for="user" class="inline-inputLabel">Username:</label>
			  <input type="text" id="user" name="user" class="form-control" placeholder="user" value="<?php echo htmlspecialchars($user); ?>" required>
			  <span id="userError" class="text-danger"></span> <!-- Mensagem de erro aqui -->
		  </div>
		</div>

		
		<div class="input-area">
			<div class="relative-1 relative">
			<label for="password" class="inline-inputLabel">Password</label>
			  <input type="password" id="password" name="password" class="form-control !pl-9" placeholder="Senha">
			  <iconify-icon icon="heroicons-outline:lock-closed" class="absolute left-2 top-1/2 -translate-y-1/2 text-base text-slate-500"></iconify-icon>
			</div>
		 </div>
		  
		<div class="input-area">
		  <div class="relative-1 relative">
			<label for="status" class="inline-inputLabel">Ativo:</label>
			<div class="switch">
				<input type="checkbox" id="status" name="status" value="1" <?php if ($status == 1) echo 'checked'; ?>>
				<label for="status"></label>
			</div>
		  </div>
		</div>
				  
		<div class="input-area">
		  <div class="relative-1 relative">
			<label for="bloqueado" class="inline-inputLabel">Bloqueado:</label>
			<div class="switch">
				<input type="checkbox" id="bloqueado" name="bloqueado" value="1" <?php if ($bloqueado == 1) echo 'checked'; ?>>
				<label for="bloqueado"></label>
			</div>
		  </div>
		</div>
	
		<button type="submit" style="width:100%" class="btn inline-flex justify-center btn-dark" onclick="console.log('Botão de Salvar foi clicado');">Salvar</button> 

	  </div>
	</div>
</div>
</form>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function buscarCEP(cepId, enderecoId, bairroId, cidadeId, ufId, complementoId) {
        var cep = document.getElementById(cepId).value.replace(/\D/g, '');
        if (cep.length === 8) {
            var url = 'https://viacep.com.br/ws/' + cep + '/json/';
            fetch(url)
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('Erro na consulta do CEP');
                    }
                    return response.json();
                })
                .then(function(data) {
                    if (!data.erro) {
                        document.getElementById(enderecoId).value = data.logradouro;
                        document.getElementById(bairroId).value = data.bairro;
                        document.getElementById(cidadeId).value = data.localidade;
                        document.getElementById(ufId).value = data.uf;
                        if (complementoId) {
                            document.getElementById(complementoId).focus();
                        }
                    } else {
                        alert('CEP não encontrado.');
                        limparCamposEndereco(enderecoId, bairroId, cidadeId, ufId, complementoId);
                    }
                })
                .catch(function(error) {
                    alert('Erro ao buscar CEP: ' + error.message);
                });
        } else {
            alert('Formato de CEP inválido.');
            limparCamposEndereco(enderecoId, bairroId, cidadeId, ufId, complementoId);
        }
    }

    function limparCamposEndereco(enderecoId, bairroId, cidadeId, ufId, complementoId) {
        document.getElementById(enderecoId).value = '';
        document.getElementById(bairroId).value = '';
        document.getElementById(cidadeId).value = '';
        document.getElementById(ufId).value = '';
        if (complementoId) {
            document.getElementById(complementoId).value = '';
        }
    }


    document.getElementById('cep').addEventListener('blur', function() {
        buscarCEP('cep', 'endereco', 'bairro', 'cidade', 'uf');
    });
});

</script>