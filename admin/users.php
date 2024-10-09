<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require './includes/Search.php'; // Certifique-se de que o caminho está correto

use App\Includes\Auth;
use App\Includes\Database;
use App\Includes\User;
use App\Includes\UserSearch;
use App\Includes\PermissionManager;

// Inicializa a conexão com o banco de dados e a autenticação
$db = new Database();
$pdo = $db->getPdo();

// Verifica se o usuário está autenticado
$auth = new Auth(new User($pdo));
if (!$auth->isAuthenticated()) {
    header('Location: index.php');
    exit();
}

$userLogadoId = $_SESSION['user_id'];

// Obtém o caminho da página dinamicamente através do PermissionManager
$pageAndDir = $permissionManager->getCurrentPageAndDirectory();
$currentLink = $pageAndDir['page'];
$currentDir = $pageAndDir['dir'];

$dirAndPage = $currentDir . "/" . $currentLink;

// Verifica se o usuário tem permissão para acessar a página atual
if (!$permissionManager->hasPermission($currentLink, $currentDir)) {
	$status = '';
    $responseMessage =  "Você não tem permissão para acessar esta página.";
	$_SESSION['responseStyle'] = 'bg-danger-500'; 
	header("Location: ./dashboard.php?page={$dirAndPage}");
    exit();
}

// Busca o usuário logado e suas permissões
$userModel = new User($pdo);
$userLogado = $userModel->getUserById($userLogadoId);
$userLogadoAdmin = $userLogado['admin']; // Nível de admin do usuário logado

// Obtém o ID do usuário que está sendo editado a partir da URL
$editUserId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editUser = $userModel->getUserById($editUserId);

// Inicializa o PermissionManager para verificar permissões
$permissionManager = new PermissionManager($pdo, $userLogadoId);
// Exibir todas as categorias e subcategorias no formulário
$permissions = $userModel->getAllCategoriesAndSubcategories($editUser ? $editUser['perfil_id'] : 0);


// Verificar se há uma mensagem de resposta armazenada na sessão
if (isset($_SESSION['responseMessage'])) {
// Verificar se há uma mensagem e estilo de resposta armazenados na sessão
$responseMessage = $_SESSION['responseMessage'] ?? null;
$style = $_SESSION['responseStyle'] ?? 'bg-success-500'; // Define um padrão de estilo, se necessário

// Limpar a sessão após exibir a mensagem e o estilo
unset($_SESSION['responseMessage'], $_SESSION['responseStyle']);

}

//*******ENVIOS*******/
// Processar o envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $sobrenome = $_POST['sobrenome'] ?? '';
    $email = $_POST['email'] ?? '';
    $user = $_POST['user'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $celular = $_POST['celular'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $password = !empty($_POST['password']) ? $_POST['password'] : null;
    $foto = $_FILES['foto']['name'] ?? null;
    $bloqueado = $_POST['bloqueado'] ?? 0;
	$admin = isset($_POST['admin']) ? ($_POST['admin'] == '1' ? 1 : ($_POST['admin'] == '2' ? 2 : 0)) : 0;

    // Recebe as permissões do formulário
    $categoriaPerms = $_POST['permissions']['categoria'] ?? [];
    $subcategoriaPerms = $_POST['permissions']['subcategoria'] ?? [];

    // Remover todas as permissões atuais (caso seja uma edição)
    $userModel->removeAllPermissions($editUserId);

    // Adicionar as novas permissões de categoria e subcategoria
    if (!empty($categoriaPerms)) {
        $userModel->addPermission($editUserId, 'categoria', $categoriaPerms);
    }

    if (!empty($subcategoriaPerms)) {
        $userModel->addPermission($editUserId, 'subcategoria', $subcategoriaPerms);
    }

    // Verificar se o nome de usuário já existe
    $existingUser = $userModel->getUserByUsername($user);
    if ($existingUser && $existingUser['id'] !== $editUserId) {
		$_SESSION['responseMessage'] = 'Nome de usuário já existe!';
		$_SESSION['responseStyle'] = 'bg-danger-500'; 
		header("Location: ./dashboard.php?page={$dirAndPage}&id={$editUserId}");
        exit;
	}

    // Verificar se o nome de usuário já existe
    $existingMail = $userModel->getUserByEmail($email);
    if ($existingMail && $existingMail['id'] !== $editUserId) {
		$_SESSION['responseMessage'] = 'Este e-mail já existe!';
		$_SESSION['responseStyle'] = 'bg-danger-500'; 
		header("Location: ./dashboard.php?page={$dirAndPage}&id={$editUserId}");
        exit;
		
    } else {
        // Se for uma nova inserção
        if (!$editUserId) {
            $data = [
                'nome' => $nome,
                'sobrenome' => $sobrenome,
                'user' => $user,
                'email' => $email,
                'descricao' => $descricao,
                'celular' => $celular,
                'telefone' => $telefone,
                'foto' => $foto,
                'bloqueado' => $bloqueado,
                'admin' => $admin,
            ];
			
			if ($password) {
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
			
            if ($userModel->insertUser($data)) {
                $_SESSION['responseMessage'] = 'Usuário cadastrado com Sucesso!';
				$_SESSION['responseStyle'] = 'bg-success-500'; 
				header("Location: ./dashboard.php?page={$dirAndPage}&id={$editUserId}");
                exit;
            } else {
                $_SESSION['responseMessage'] = 'Erro ao inserir usuário!';
				$_SESSION['responseStyle'] = 'bg-danger-500'; 
				header("Location: ./dashboard.php?page={$dirAndPage}&id={$editUserId}");
                exit;
            }
        } else {
            // Atualização de usuário existente
			$password = !empty($_POST['password']) ? $_POST['password'] : null;
            $data = [
                'nome' => $nome,
                'sobrenome' => $sobrenome,
                'user' => $user,
                'email' => $email,
                'descricao' => $descricao,
                'celular' => $celular,
                'telefone' => $telefone,
                'foto' => $foto,
                'bloqueado' => $bloqueado,
                'admin' => $admin,
				'password' => $password,  // Inclua a senha no array de dados
            ];

            if ($password) {
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
			
            // Se a senha for fornecida, ela será criptografada no método `updateUser`
			if ($userModel->updateUser($editUserId, $data)) {
				$_SESSION['responseMessage'] = 'Atualizado com sucesso!';
				$_SESSION['responseStyle'] = 'bg-success-500';
				header("Location: ./dashboard.php?page={$dirAndPage}&id={$editUserId}");
				exit;
			} else {
				$_SESSION['responseMessage'] = 'Erro ao atualizar o cadastro!';
				$_SESSION['responseStyle'] = 'bg-danger-500'; 
				header("Location: ./dashboard.php?page={$dirAndPage}&id={$editUserId}");
				exit();
			}
        }
    }
}

// Obtém informações do usuário logado
$userInfo = $userModel->getUserById($userLogadoId); // Exemplo de ID de usuário
$nomeAdmin = $userInfo['nome'] ?? '';
$userAdmin = $userInfo['admin'] ?? 0;

/*echo $nomeAdmin . $userAdmin;*/

if (!empty($foto)) {
    $foto = $foto;
} else {
    $foto = '../assets/images/all-img/user.png';
}

if ($editUser) {
    // Verifica se o usuário que está sendo editado é um superadmin
    if ($editUser['admin'] == 2 && $userLogadoAdmin != 2) {
        // Se o usuário logado não for superadmin, define a mensagem de erro
        $responseMessage =  "Você não tem permissão para editar este perfil.";
		$_SESSION['responseStyle'] = 'bg-danger-500'; 
        
        // Exibir mensagem de permissão negada e interromper o resto da página
        if (!empty($responseMessage)): ?>
            <div class="py-[18px] px-6 font-normal text-sm rounded-md bg-warning-500 text-white">
              <div class="flex items-center space-x-3 rtl:space-x-reverse">
                <iconify-icon class="text-2xl flex-0" icon="system-uicons:target"></iconify-icon>
                <p class="flex-1 font-Inter">
                  <?php echo htmlspecialchars($responseMessage); ?>
                </p>
                <div class="flex-0 text-xl cursor-pointer">
                  <iconify-icon icon="line-md:close"></iconify-icon>
                </div>
              </div>
            </div>
        <?php
        endif;
        
        // Interromper a execução do restante da página
        return;
    }
	    // Continua com a exibição e edição do perfil, se permitido
    $nome = $editUser['nome'];
    $sobrenome = $editUser['sobrenome'];
    $email = $editUser['email'];
    $usuario = $editUser['user'];
    $descricao = $editUser['descricao'];
    $bloqueado = $editUser['bloqueado'];
    $admin = $editUser['admin'];
    $telefone = $editUser['telefone'];
    $celular = $editUser['celular'];
    $foto = $editUser['foto'];
}

$user = $userModel->getUserById($editUserId);
 $nome = $user['nome'] ?? '';
    $sobrenome = $user['sobrenome'] ?? '';
    $email = $user['email'] ?? '';
    $usuario = $user['user'] ?? '';
    $descricao = $user['descricao'] ?? '';
    $bloqueado = $user['bloqueado'] ?? 0;
    $admin = $user['admin'] ?? 0;
    $telefone = $user['telefone'] ?? '';
    $celular = $user['celular'] ?? '';
    $foto = !empty($user['foto']) ? $user['foto'] : '../assets/images/all-img/user.png';


$idUrl = $_GET['id'] ?? 0;
?>

<style>
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
</style>

<?php if (!empty($responseMessage)): ?>
<div class="py-[18px] px-6 font-normal text-sm rounded-md <?php echo htmlspecialchars($style); ?> text-white">
  <div class="flex items-center space-x-3 rtl:space-x-reverse">
    <iconify-icon class="text-2xl flex-0" icon="system-uicons:target"></iconify-icon>
    <p class="flex-1 font-Inter">
      <?php echo htmlspecialchars($responseMessage); ?>
    </p>
    <div class="flex-0 text-xl cursor-pointer">
      <iconify-icon icon="line-md:close"></iconify-icon>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- BEGIN: Breadcrumb -->
<div class="mb-5">
	<ul class="m-0 p-0 list-none navItem">
		<li style="padding:0px;display: flex;align-items: center;">
			<a href="./dashboard.php?page=dash" class="navItem" style="display: inline-block!important;"><iconify-icon icon="heroicons-outline:home"></iconify-icon></a>
			<iconify-icon icon="heroicons-outline:chevron-right" ></iconify-icon>Usuários
			<iconify-icon icon="heroicons-outline:chevron-right"></iconify-icon><a href="dashboard.php?page=admin/list-users" class="navItem" style="display: inline-block!important;">Listar Usuários</a>
			<iconify-icon icon="heroicons-outline:chevron-right"></iconify-icon><b><?php echo $idUrl == 0 ? 'Cadastrar Usuário' : 'Editar Usuário ' . $nome; ?></b>
		</li>
	</ul>
</div>
<!-- END: BreadCrumb -->

<form id="userForm" method="POST" enctype="multipart/form-data" action="">
	<input type="hidden" id="userId" name="userId" value="<?= $userId; ?>">

<div class="card xl:col-span-2">
	<div class="card-body flex flex-col p-6">
	  <header class="flex mb-5 items-center border-b border-slate-100 dark:border-slate-700 pb-5 -mx-6 px-6">
		<div class="image" style="width: 100px; float: left; margin-right: 20px;">
			<img src="<?php echo $foto; ?>" style="width: 100px; height: 100px; border-radius: 50px" class="profile-user-img img-fluid img-circle" alt="User Image">
		</div>
		<div class="flex-2">
		  <div class="card-title text-slate-900 dark:text-white"><?php echo $idUrl == 0 ? 'Cadastrar Usuário' : 'Editar Usuário: ' . $nome; ?></div>
		</div>


	  </header>
	  <div class="card-text h-full space-y-4">

		<div class="input-area">
		  <div class="relative-1 relative">
			<label for="nome" class="inline-inputLabel">Nome:</label>
			<input type="text" id="nome" name="nome" class="form-control" autocomplete="name" placeholder="Nome" value="<?php echo htmlspecialchars($nome); ?>" required>
		  </div>
		</div>

		<div class="input-area">
		  <div class="relative-1 relative">
		<label for="sobrenome" class="inline-inputLabel">Sobrenome:</label>
			<input type="text" id="sobrenome" name="sobrenome" class="form-control" placeholder="Sobrenome" value="<?php echo htmlspecialchars($sobrenome); ?>" required>
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
		  <label for="descricao" class="inline-inputLabel">Cargo:</label>
			<input type="text" id="descricao" name="descricao" class="form-control !pr-32" placeholder="Descrição" value="<?php echo htmlspecialchars($descricao); ?>" required>
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
		  <div class="relative-1 relative group">
		  <label for="email" class="inline-inputLabel">E-mail:</label>
			<input type="text" id="email" name="email" class="form-control !pl-9" placeholder="E-mail" autocomplete="email" value="<?php echo htmlspecialchars($email); ?>" required>
			<iconify-icon icon="heroicons-outline:mail" class="absolute left-2 top-1/2 -translate-y-1/2 text-base text-slate-500"></iconify-icon>
		  </div>
		</div>
	
		<div class="input-area">
		  <div class="relative-1 relative">
			  <label for="user" class="inline-inputLabel">Username:</label>
			  <input type="text" id="user" name="user" class="form-control" placeholder="user" value="<?php echo htmlspecialchars($usuario); ?>" required>
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
			<label for="bloqueado" class="inline-inputLabel">Bloqueado:</label>
			<div class="switch">
				<input type="checkbox" id="bloqueado" name="bloqueado" value="1" <?php if ($bloqueado == 1) echo 'checked'; ?>>
				<label for="bloqueado"></label>
			</div>		  </div>
		</div>
		
		<?php if($userAdmin != 0 || $userAdmin == NULL): ?>
		<div class="input-area">
			<div class="relative-1 relative">
				<label for="admin" class="inline-inputLabel">Admin:</label>
				<div class="switch">
					<input type="checkbox" id="admin" name="admin" <?php if ($admin == 1) echo 'value="1" checked'; elseif ($admin == 2) echo 'value="2" checked';; ?>>
					<label for="admin"></label>
				</div>
			</div>
		</div>
		<?php endif; ?>
		
	  </div>
	</div>
</div>
<br>

<!-- Permissões do usuário -->
<div class="card">
	<div class="card-body flex flex-col p-6">
	<header class="flex mb-5 items-center border-b border-slate-100 dark:border-slate-700 pb-5 -mx-6 px-6">
		<div class="flex-1">
		  <div class="card-title text-slate-900 dark:text-white">Permissões</div>
		</div>
	</header>
	<div class="card-text h-full ">
		<div class="input-area">
			<?php if (isset($permissions) && !empty($permissions)): ?>
				<?php foreach ($permissions as $categoriaId => $categoria): ?>
					<div class="categoria">
						<iconify-icon class='nav-icon' icon='<?= htmlspecialchars($categoria['categoria_icon']) ?>'></iconify-icon>
						<span><b><?= ' - '. htmlspecialchars($categoria['categoria_nome']) ?></b></span>
						<div class="switch">
							<!-- Checkbox para a categoria -->
							<input type="checkbox" id="cat_<?= $categoriaId ?>" name="permissions[categoria][]" value="<?= $categoriaId ?>"
								<?php if ($categoria['tem_categoria']) echo 'checked'; ?>>
							<label for="cat_<?= $categoriaId ?>"></label>
						</div>
					</div>
					<?php if (!empty($categoria['subcategorias'])): ?>
						<?php foreach ($categoria['subcategorias'] as $subcategoria): ?>
							<div class="subcategoria">
								<label for="perm_<?= $subcategoria['subcategoria_id'] ?>">
								   <?= htmlspecialchars($subcategoria['subcategoria_nome']) ?>
								</label>
								<div class="switch">
									<!-- Checkbox para a subcategoria, desativado se a categoria não estiver marcada -->
									<input type="checkbox" id="perm_<?= $subcategoria['subcategoria_id'] ?>" name="permissions[subcategoria][]" value="<?= $subcategoria['subcategoria_id'] ?>"
										<?php if ($subcategoria['tem_subcategoria']) echo 'checked'; ?>
										<?php if (!$categoria['tem_categoria']) echo 'disabled'; ?>>
									<label for="perm_<?= $subcategoria['subcategoria_id'] ?>"></label>
								</div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php else: ?>
				<p>Sem permissões encontradas.</p>
			<?php endif; ?>
		</div>
		<br>
	  <button type="submit" style="width:100%" class="btn inline-flex justify-center btn-dark" onclick="console.log('Botão de Salvar foi clicado');">Salvar</button>                          
	</div>
	</div>
</div>
<!-- Permissões do usuário -->
</form>

	<script>
//Upload de arquivos
// Elementos
const fotoInput = document.getElementById('fotoInput');

// Clique na área de arrastar para abrir o seletor de arquivos
dropArea.addEventListener('click', () => {
    fotoInput.click();
});

// Atualiza o texto quando um arquivo é selecionado manualmente
fotoInput.addEventListener('change', (event) => {
    const fileName = event.target.files[0]?.name || 'Nenhum arquivo selecionado';
    dropText.textContent = fileName;
});


</script>

