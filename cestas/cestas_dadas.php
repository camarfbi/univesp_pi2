<?php
require './includes/Database.php';

$db = new Database();
$pdo = $db->getPdo();

// Captura o ID da família (associado)
$id_familia = isset($_POST['id_familia']) ? (int)$_POST['id_familia'] : null;
$id_cesta = isset($_POST['id_cesta']) ? (int)$_POST['id_cesta'] : null;
$data = date('Y-m-d');
$quantidade = 1; // Quantidade de cestas doadas

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica se há uma cesta completa disponível
    $stmt = $pdo->prepare("INSERT INTO cestas_doadas (id_familia, id_cesta, data, quant) VALUES (:id_familia, :id_cesta, :data, :quant)");
    $stmt->execute([
        ':id_familia' => $id_familia,
        ':id_cesta' => $id_cesta,
        ':data' => $data,
        ':quant' => $quantidade
    ]);

    echo "Cesta doada com sucesso!";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doar Cesta</title>
</head>
<body>
    <h1>Doar Cesta</h1>

    <form method="POST">
        <label for="id_familia">Família (ID do Associado):</label>
        <input type="text" name="id_familia" required><br>

        <label for="id_cesta">Cesta (ID da Cesta):</label>
        <input type="text" name="id_cesta" required><br>

        <button type="submit">Doar Cesta</button>
    </form>
</body>
</html>
