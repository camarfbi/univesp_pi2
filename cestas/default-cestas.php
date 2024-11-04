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

//Iniciar modal
$cestaModal = new Cesta($pdo);

// Captura o ID da cesta para edição
$editCestaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Se for edição, busca as informações da cesta
$editCesta = $editCestaId ? $cestaModal->getCestaById($editCestaId) : null;

// Busca todos os produtos disponíveis no banco de dados
$query_produtos = $pdo->query("SELECT id, nome FROM produtos");
$produtos_disponiveis = $query_produtos->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT p.nome, cp.quantidade, cp.id
    FROM cesta_produtos cp
    JOIN produtos p ON cp.id_produto = p.id
    WHERE cp.id_cesta = :id_cesta
");
$stmt->execute([':id_cesta' => $editCestaId]);
$produtosRegistrados = $stmt->fetchAll(PDO::FETCH_ASSOC);

$responseMessage = [];

// Inicializa as variáveis
$nome_cesta = $editCesta['nome'] ?? '';
$tipo_cesta = $editCesta['tipo'] ?? '';

// Verifica se o valor de produtos está armazenado como JSON ou string separada por vírgulas
$produtos_selecionados = $editCesta['produtos'] ?? [];
if (is_string($produtos_selecionados)) {
    $produtos_selecionados = json_decode($produtos_selecionados, true) ?: explode(',', $produtos_selecionados);
}

/// Processamento do formulário (inserção ou edição)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && $_POST['acao'] == 'CESTA') {
        // Captura os dados do formulário
        $formData = $cestaModal->getFormData($_POST, $editCesta, $editCestaId);

        // Verifica se a cesta já existe
        if ($cestaModal->cestaExists($formData['nome'], $editCestaId)) {
            // Em vez de redirecionar, apenas defina a mensagem de erro
            $responseMessage = [
                'message' => 'A cesta ' . $formData['nome'] . ' já existe!',
                'style' => 'bg-danger-500'
            ];
        } else {
            // Processa os produtos selecionados
            $produtos_json = json_encode($formData['produtos']);

            // Atualiza ou cria uma nova cesta
            if ($editCestaId > 0) {
                // Atualiza a cesta existente
                $stmt = $pdo->prepare("UPDATE cesta SET nome = :nome, tipo = :tipo, produtos = :produtos WHERE id = :id");
                $stmt->execute([
                    ':nome' => $formData['nome'],
                    ':tipo' => $formData['tipo'],
                    ':produtos' => $produtos_json,
                    ':id' => $editCestaId
                ]);
				
				header("Location: dashboard.php?page=cestas/default-cestas&id=$editCestaId");
				
                $responseMessage = [
                    'message' => 'Cesta atualizada com sucesso!',
                    'style' => 'bg-success-500'
                ];
            } else {
                // Cria uma nova cesta
                $stmt = $pdo->prepare("INSERT INTO cesta (nome, tipo, produtos) VALUES (:nome, :tipo, :produtos)");
                $stmt->execute([
                    ':nome' => $formData['nome'],
                    ':tipo' => $formData['tipo'],
                    ':produtos' => $produtos_json
                ]);
                $responseMessage = [
                    'message' => 'Cesta criada com sucesso!',
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

<!-- Formulário de Criação/Edição de Cestas -->
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" value="CESTA" name="acao">
    <input type="hidden" id="userId" name="userId" value="<?= $userId; ?>">
    <div class="card xl:col-span-2">
        <div class="card-body flex flex-col p-6">
            <header class="flex mb-5 items-center border-b border-slate-100 dark:border-slate-700 pb-5">
                <div class="flex-2">
                    <div class="card-title text-slate-900 dark:text-white">
                        <?php echo $editCestaId == 0 ? 'Cadastrar Nova Cesta' : 'Editar Cesta ' . $nome_cesta; ?>
                    </div>
                </div>
            </header>
            <div class="card-text h-full space-y-4">
                <div class="input-area">
                    <label for="nome_cesta">Nome da Cesta*:</label>
                    <input type="text" id="nome_cesta" name="nome_cesta" class="form-control" value="<?php echo htmlspecialchars($nome_cesta); ?>" required>
                </div>

                <div class="input-area">
                    <label for="tipo_cesta">Tipo da Cesta*:</label>
                    <select id="tipo_cesta" name="tipo_cesta" class="form-control" required>
                        <option value="">Selecione</option>
                        <option value="Mínima" <?php echo $tipo_cesta == 'Mínima' ? 'selected' : ''; ?>>Mínima</option>
                        <option value="Básica" <?php echo $tipo_cesta == 'Básica' ? 'selected' : ''; ?>>Básica</option>
                        <option value="Completa" <?php echo $tipo_cesta == 'Completa' ? 'selected' : ''; ?>>Completa</option>
                    </select>
                </div>

                <div class="input-area">
                    <label for="produtos"><b>Selecione os Produtos:</b></label>
                    <?php foreach ($produtos_disponiveis as $produto): ?>
                        <div>
                            <input type="checkbox" name="produtos[]" value="<?= $produto['nome']; ?>" <?php echo in_array($produto['nome'], $produtos_selecionados) ? 'checked' : ''; ?>>
                            <label><?= $produto['nome']; ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn inline-flex justify-center btn-dark">Salvar</button>
            </div>
				<?php if ($produtosRegistrados): ?>
					<h3 class="text-lg font-semibold mt-6">Produtos Registrados na Cesta</h3>
					<ul class="list-disc list-inside">
						<?php foreach ($produtosRegistrados as $produto): ?>
							<li>
								<?= htmlspecialchars($produto['nome']); ?>: <?= (int)$produto['quantidade']; ?> unidade(s)
								
								<!-- Botão para excluir a quantidade -->
								<form method="POST" action="./dashboard.php?page=cestas/deletar-quantidade" style="display:inline;">
								    <input type="hidden" value="CESTA" name="acao">
									<input type="hidden" name="acao1" value="DELETE_CESTA">
									<input type="hidden" name="id_cesta" value="<?= $editCestaId; ?>">
									<input type="hidden" name="id_produto" value="<?= $produto['id']; ?>">
									<button type="submit" class="btn btn-danger inline-flex items-center text-sm">
										Excluir
									</button>
								</form>
							</li>
						<?php endforeach; ?>
					</ul>

				<?php endif; ?>
        </div>
    </div>
</form>
<?php
// Buscar produtos selecionados na cesta (se for edição)
$produtos_selecionados_ids = [];
if (!empty($produtos_selecionados)) {
    foreach ($produtos_disponiveis as $produto) {
        if (in_array($produto['nome'], $produtos_selecionados)) {
            $produtos_selecionados_ids[] = $produto['id'];
        }
    }
}
?>

<!-- Botão para abrir o modal -->
<button onclick="openModal()" class="btn inline-flex justify-center btn-primary">Adicionar Quantidade de Produtos</button>

<!-- Modal de Adição de Produtos -->
<div id="productModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
            <h2 class="text-2xl font-semibold mb-4">Adicionar Quantidade de Produtos</h2>
            
            <!-- Formulário para inserir quantidades -->
            <form method="POST" action="./dashboard.php?page=cestas/salvar-quantidade">
                <input type="hidden" name="id_cesta" value="<?= $editCestaId; ?>">
                
                <?php foreach ($produtos_disponiveis as $produto): ?>
                    <?php if (in_array($produto['id'], $produtos_selecionados_ids)): ?>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700"><?= htmlspecialchars($produto['nome']); ?></label>
                            <input type="number" name="quantidades[<?= $produto['id']; ?>]" min="1" class="form-control w-full mt-1" placeholder="Quantidade">
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('productModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('productModal').classList.add('hidden');
    }
</script>
