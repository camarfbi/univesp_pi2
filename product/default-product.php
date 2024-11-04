<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require './includes/helpers.php';
require './includes/Produto.php';
require './includes/SessionMessage.php';

use App\Includes\Auth;
use App\Includes\Database;
use App\Includes\User;
use App\Includes\PermissionManager;
use App\Includes\Produto;
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

//Iniciar modal
$produtoModal = new Produto($pdo);

// Captura o ID da produto para edição
$editProdutoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Se for edição, busca as informações da produto
$editProduto = $editProdutoId ? $produtoModal->getProdutoById($editProdutoId) : null;

// Busca todos os produtos disponíveis no banco de dados
$query_estoque = $pdo->query("SELECT id_prod, quant FROM produtos_estoque");
$produtos_disponiveis = $query_estoque->fetchAll(PDO::FETCH_ASSOC);

$responseMessage = [];

// Inicializa as variáveis
$nome_produto = $editProduto['nome'] ?? '';
$tipo_produto = $editProduto['tipo'] ?? '';
$un_produto = $editProduto['un_med'] ?? '';
$quant = isset($_POST['estoque']) ? (int)$_POST['estoque'] : 0;

// Verifica se o valor de produtos está armazenado como JSON ou string separada por vírgulas
$produtos_selecionados = $editProduto['produtos'] ?? [];
if (is_string($produtos_selecionados)) {
    $produtos_selecionados = json_decode($produtos_selecionados, true) ?: explode(',', $produtos_selecionados);
}

/// Processamento do formulário (inserção ou edição)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && $_POST['acao'] == 'CESTA') {
        // Captura os dados do formulário
        $formData = $produtoModal->getFormData($_POST, $editProduto, $editProdutoId);

        // Verifica se a produto já existe
        if ($produtoModal->produtoExists($formData['nome'], $editProdutoId)) {
            // Em vez de redirecionar, apenas defina a mensagem de erro
            $responseMessage = [
                'message' => 'A produto ' . $formData['nome'] . ' já existe!',
                'style' => 'bg-danger-500'
            ];
        } else {
            // Processa os produtos selecionados
            $produtos_json = json_encode(isset($formData['produtos']) ?? '');

            if ($editProdutoId > 0) {
                // Atualiza a produto existente
                $stmt = $pdo->prepare("UPDATE produtos SET nome = :nome, tipo = :tipo, un_med = :un_med WHERE id = :id");
                $stmt->execute([
                    ':nome' => $formData['nome'],
                    ':tipo' => $formData['tipo'],
                    ':un_med' => $formData['un_med'],
                    ':id' => $editProdutoId
                ]);

                // Atualiza o estoque se já existe, senão insere
                if ($produtoModal->estoqueExists($editProdutoId)) {
                    $produtoModal->updateEstoque($editProdutoId, $quant);
                } else {
                    $produtoModal->insertEstoque($editProdutoId, $quant);
                }

                $responseMessage = [
                    'message' => 'Produto atualizado com sucesso!',
                    'style' => 'bg-success-500'
                ];
            } else {
                // Cria uma nova produto
                $stmt = $pdo->prepare("INSERT INTO produtos (nome, tipo, un_med) VALUES (:nome, :tipo, :un_med)");
                $stmt->execute([
                    ':nome' => $formData['nome'],
                    ':tipo' => $formData['tipo'],
                    ':un_med' => $formData['un_med'],
                ]);
                
                // Obtém o último ID de produto e insere o estoque correspondente
                $newProductId = (int) $pdo->lastInsertId();
                $produtoModal->insertEstoque($newProductId, $quant);

                $responseMessage = [
                    'message' => 'Produto criado com sucesso!',
                    'style' => 'bg-success-500'
                ];
            }
        }
    }
}


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

<!-- Formulário de Criação/Edição de Produtos -->
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" value="CESTA" name="acao">
    <input type="hidden" id="userId" name="userId" value="<?= $userId; ?>">
    <div class="card xl:col-span-2">
        <div class="card-body flex flex-col p-6">
            <header class="flex mb-5 items-center border-b border-slate-100 dark:border-slate-700 pb-5">
                <div class="flex-2">
                    <div class="card-title text-slate-900 dark:text-white">
                        <?php echo $editProdutoId == 0 ? 'Cadastrar Nova Produto' : 'Editar Produto ' . $nome_produto; ?>
                    </div>
                </div>
            </header>
            <div class="card-text h-full space-y-4">
                <div class="input-area">
                    <label for="nome_produto">Nome da Produto*:</label>
                    <input type="text" id="nome_produto" name="nome_produto" class="form-control" value="<?php echo htmlspecialchars($nome_produto); ?>" required>
                </div>

                <div class="input-area">
                    <label for="tipo">Tipo da Produto*:</label>
                    <select id="tipo" name="tipo" class="form-control" required>
                        <option value="">Selecione</option>
                        <option value="Não Perecível" <?php echo $tipo_produto == 'Não Perecível' ? 'selected' : ''; ?>>Não Perecível</option>
                        <option value="Perecível" <?php echo $tipo_produto == 'Perecível' ? 'selected' : ''; ?>>Perecível</option>
                    </select>
                </div>

                <div class="input-area">
                    <label for="un_med">Unidade de Medida*:</label>
                    <select id="un_med" name="un_med" class="form-control" required>
                        <option value="">Selecione</option>
                        <option value="Quilo" <?php echo $un_produto == 'Quilo' ? 'selected' : ''; ?>>Quilo</option>
                        <option value="Litro" <?php echo $un_produto == 'Litro' ? 'selected' : ''; ?>>Litro</option>
                        <option value="Sachê" <?php echo $un_produto == 'Sachê' ? 'selected' : ''; ?>>Sachê</option>
                        <option value="Unidade" <?php echo $un_produto == 'Unidade' ? 'selected' : ''; ?>>Unidade</option>
                        <option value="Peça" <?php echo $un_produto == 'Peça' ? 'selected' : ''; ?>>Peça</option>
                    </select>
                </div>

                <div class="input-area">
                    <label for="produtos"><b>Informe a quantidade em estoque:</b></label>
                    <?php
						foreach ($produtos_disponiveis as $produto){
							if($produto['id_prod'] == $editProdutoId){
								$quant = isset($produto['quant']) ? $produto['quant'] : 0;
							}
						}?>								
                        <div>
                            <input type="text" name="estoque" class="form-control" value="<?= $quant; ?>" >
                        </div>
                </div>

                <button type="submit" class="btn inline-flex justify-center btn-dark">Salvar</button>
            </div>
        </div>
    </div>
</form>

