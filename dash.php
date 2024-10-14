<?php
require './includes/helpers.php';
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

$db = new Database();
$pdo = $db->getPdo();

// Consultar todas as cestas do banco de dados
$queryCestas = $pdo->query("SELECT * FROM cesta");
$cestas = $queryCestas->fetchAll(PDO::FETCH_ASSOC);

// Consultar o estoque atual
$queryEstoque = $pdo->query("SELECT p.nome, e.quant FROM produtos_estoque e JOIN produtos p ON e.id_prod = p.id");
$estoque = $queryEstoque->fetchAll(PDO::FETCH_ASSOC);

// Consultar os produtos e quantidades de cada cesta
$queryCestaProdutos = $pdo->prepare("
    SELECT cp.id_cesta, p.nome AS produto, cp.quantidade
    FROM cesta_produtos cp
    JOIN produtos p ON cp.id_produto = p.id
    WHERE cp.id_cesta = :id_cesta
");

// Função para calcular as cestas completas e os produtos faltantes
function calcularCestas($produtos_cesta, &$estoque) {
    $cestas_completas = PHP_INT_MAX;  // Máximo inicial
    $produtos_faltantes = [];
    $detalhes_produtos = [];

    // Primeiro, determinar quantas cestas podem ser feitas
    foreach ($produtos_cesta as $produto) {
        // Verificar se o campo 'produto' está presente
        if (!isset($produto['produto'])) {
            continue; // Ignorar caso não exista o campo 'produto'
        }

        $quant_produto_estoque = 0;
        $produto_encontrado_no_estoque = false;

        // Buscar a quantidade no estoque do produto correspondente
        foreach ($estoque as &$item) {
            if (isset($item['nome']) && $item['nome'] === $produto['produto']) {
                $quant_produto_estoque = $item['quant'];
                $produto_encontrado_no_estoque = true;
                break;
            }
        }

        // Se o produto não for encontrado no estoque, considerar como faltando
        if (!$produto_encontrado_no_estoque) {
            $quant_produto_estoque = 0;
        }

        // Calcular quantas cestas podem ser feitas com o estoque disponível
        if ($produto['quantidade'] > 0) {
            $cestas_possiveis = intdiv($quant_produto_estoque, $produto['quantidade']);
            $cestas_completas = min($cestas_completas, $cestas_possiveis);
        }

        // Guardar detalhes de cada produto (quantidade necessária e disponível)
        $detalhes_produtos[] = [
            'nome' => $produto['produto'],
            'quant_necessaria' => $produto['quantidade'],
            'quant_estoque' => $quant_produto_estoque,
            'estoque_restante' => max(0, $quant_produto_estoque)  // Garantir que estoque_restante sempre tenha um valor
        ];

        // Se faltar algum produto, adicionar na lista de faltantes
        if ($quant_produto_estoque < $produto['quantidade']) {
            $produtos_faltantes[$produto['produto']] = $produto['quantidade'] - $quant_produto_estoque;
        }
    }

    // Se não for possível montar nenhuma cesta, retornar 0 como valor de cestas completas
    if ($cestas_completas === PHP_INT_MAX) {
        $cestas_completas = 0;
    }

    // Subtrair a quantidade de produtos usados para as cestas completas do estoque
    if ($cestas_completas > 0) {
        foreach ($produtos_cesta as $produto) {
            if (!isset($produto['produto'])) {
                continue;
            }

            foreach ($estoque as &$item) {
                if (isset($item['nome']) && $item['nome'] === $produto['produto']) {
                    $item['quant'] -= $cestas_completas * $produto['quantidade'];
                    break;
                }
            }
        }
    }

    // Atualizar detalhes_produtos com o estoque restante
    foreach ($detalhes_produtos as &$detalhe) {
        foreach ($estoque as $item) {
            if ($item['nome'] === $detalhe['nome']) {
                $detalhe['estoque_restante'] = $item['quant'];
                break;
            }
        }
    }

    return [$cestas_completas, $produtos_faltantes, $detalhes_produtos];
}

// Simulando a criação de cestas
$estoque_atualizado = $estoque; // Copiar o estoque inicial para atualização

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Cestas</title>
    <link rel="stylesheet" href="path/to/your/tailwind.css">
</head>
<body>
    <h1 class="text-3xl font-bold mb-5">Gerenciamento de Cestas</h1>

    <div class="grid md:grid-cols-4 sm:grid-cols-2 grid-cols-1 gap-3">
        <?php foreach ($cestas as $index => $cesta): ?>
            <?php
            // Buscar os produtos e quantidades associados a esta cesta
            $queryCestaProdutos->execute(['id_cesta' => $cesta['id']]);
            $produtos_cesta = $queryCestaProdutos->fetchAll(PDO::FETCH_ASSOC);

            // Chamar a função calcularCestas com o estoque atualizado após a criação da cesta anterior
            list($cestas_completas, $produtos_faltantes, $detalhes_produtos) = calcularCestas($produtos_cesta, $estoque_atualizado);
            
            // Alternar entre as cores com base no índice da cesta
            $cores = ['bg-info-500', 'bg-warning-500', 'bg-primary-500', 'bg-success-500'];
            $cor = $cores[$index % count($cores)];
            ?>

            <div class="<?= $cor; ?> rounded-md p-4 bg-opacity-[0.15] dark:bg-opacity-50 text-center">
                <div class="text-<?= str_replace('bg-', '', $cor); ?> mx-auto h-10 w-10 flex flex-col items-center justify-center rounded-full bg-white text-2xl mb-4">
                    <iconify-icon icon="heroicons-outline:menu-alt-1"></iconify-icon>
                </div>
                <span class="block text-sm text-slate-600 font-medium dark:text-white mb-1">
                    <?= htmlspecialchars($cesta['nome']); ?> (<?= htmlspecialchars($cesta['tipo']); ?>)
                </span>
                <span class="block mb-2 text-2xl text-slate-900 dark:text-white font-medium">
                    Cestas Completas: <?= $cestas_completas; ?>
                </span>

                <span class="block text-sm text-slate-600 font-medium dark:text-white mb-1">Detalhes dos Produtos</span>
                <ul class="list-disc list-inside text-slate-600 dark:text-white">
                    <?php foreach ($detalhes_produtos as $produto): ?>
                        <li>
                            <?= htmlspecialchars($produto['nome']); ?>: 
                            <?= htmlspecialchars($produto['quant_necessaria']); ?> necessária, 
                            <?= htmlspecialchars($produto['quant_estoque']); ?> em estoque,
                            restam: <?= htmlspecialchars($produto['estoque_restante']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <span class="block text-sm text-slate-600 font-medium dark:text-white mb-1">Produtos Faltantes</span>
                <?php if (!empty($produtos_faltantes)): ?>
                    <ul class="list-disc list-inside text-slate-600 dark:text-white">
                        <?php foreach ($produtos_faltantes as $produto => $quant): ?>
                            <li><?= htmlspecialchars($produto); ?>: <?= htmlspecialchars($quant); ?> faltando</li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-success-500">Todos os produtos disponíveis.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>
