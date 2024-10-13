<?php
require './includes/Database.php';

$db = new Database();
$pdo = $db->getPdo();

// Definir os produtos necessários para a cesta básica
$cesta_basica = ['arroz', 'feijao', 'oleo', 'macarrao', 'molho', 'farinha'];

// Consultar o estoque atual
$query = $pdo->query("SELECT nome, quant FROM produtos_estoque JOIN produtos ON produtos_estoque.id_prod = produtos.id_prod");
$estoque = $query->fetchAll(PDO::FETCH_ASSOC);

// Calcular cestas completas e incompletas
$cestas_completas = PHP_INT_MAX;
$produtos_faltantes = [];

foreach ($cesta_basica as $produto) {
    $quant_produto = 0;
    foreach ($estoque as $item) {
        if ($item['nome'] === $produto) {
            $quant_produto = $item['quant'];
        }
    }
    $cestas_possiveis = intdiv($quant_produto, 1); // 1 unidade de cada produto
    $cestas_completas = min($cestas_completas, $cestas_possiveis);

    // Calcular os produtos faltantes
    if ($quant_produto < $cestas_completas) {
        $produtos_faltantes[$produto] = 1 - $quant_produto;
    }
}

// Produtos faltantes para cestas incompletas
$cestas_incompletas = 0;
foreach ($cesta_basica as $produto) {
    foreach ($estoque as $item) {
        if ($item['nome'] === $produto && $item['quant'] < 1) {
            $cestas_incompletas++;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Cestas</title>
</head>
<body>
    <h1>Gerenciamento de Cestas</h1>

    <p>Cestas Completas Disponíveis: <?= $cestas_completas; ?></p>
    <p>Cestas Incompletas: <?= $cestas_incompletas; ?></p>

    <h2>Produtos Faltantes</h2>
    <?php if (!empty($produtos_faltantes)): ?>
        <ul>
            <?php foreach ($produtos_faltantes as $produto => $quant): ?>
                <li><?= $produto; ?>: <?= $quant; ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Não há produtos faltantes.</p>
    <?php endif; ?>
</body>
</html>
