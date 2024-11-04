<?php
require './includes/helpers.php';
require './includes/Search.php'; 
require './includes/Familias.php';
require './includes/SessionMessage.php';
require './includes/funcoes.php';
require './includes/CalcularCestas.php';

use App\Includes\Auth;
use App\Includes\Database;
use App\Includes\User;
use App\Includes\Associado;
use App\Includes\PermissionManager;
use App\Includes\SessionMessage;
use App\Includes\CalcularCestas;

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

// Criar instância da classe CalculoCesta
$calculoCesta = new CalcularCestas($pdo);

// Atualizar estoque para cada cesta
$estoque_atualizado = $estoque;

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Cestas</title>
    <link rel="stylesheet" href="/node_modules/tailwind/tailwind.css">

  <!--  <div class="bg-blue-500 text-white text-center p-4">
        Tailwind CSS está funcionando!
    </div> -->

</head>
<body>
    <h1 class="text-3xl font-bold mb-5">Gerenciamento de Cestas</h1>

    <div class="grid md:grid-cols-1 sm:grid-cols-1 grid-cols-1 gap-3">
        <?php foreach ($cestas as $index => $cesta): ?>
            <?php
            $queryCestaProdutos->execute(['id_cesta' => $cesta['id']]);
            $produtos_cesta = $queryCestaProdutos->fetchAll(PDO::FETCH_ASSOC);

// Chamar o método calcularCestas da instância de CalculoCesta
list($cestas_completas, $produtos_faltantes, $detalhes_produtos) = $calculoCesta->calcularCestas($produtos_cesta, $estoque_atualizado);

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
                            <?= htmlspecialchars($produto['quant_necessaria']); ?> necessário(s), 
                            <?= htmlspecialchars($produto['quant_estoque']); ?> em estoque,
                            restam: <?= htmlspecialchars($produto['estoque_restante']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <span class="block text-sm text-slate-600 font-medium dark:text-white mb-1">Produtos Faltantes</span>
                <?php if (!empty($produtos_faltantes)): ?>
                    <ul class="block mb-2 text-slate-900 dark:text-white font-medium" style="font-weight: 800;">
                        <?php foreach ($produtos_faltantes as $produto => $quant): ?>
                            <li>Pelo menos  <?= htmlspecialchars($quant); ?> <?= htmlspecialchars($produto); ?> faltando para montar uma cesta</li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-danger-500"><b>Com os produtos disponíveis foi possível montar <?= $cestas_completas; ?> cestas.</b></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>