<?php
require './includes/Database.php';

$db = new Database();
$pdo = $db->getPdo();

// Captura todas as famílias (associados)
$queryFamilias = $pdo->query("SELECT id, nome FROM associados");
$familias = $queryFamilias->fetchAll(PDO::FETCH_ASSOC);

// Captura todas as cestas disponíveis
$queryCestas = $pdo->query("SELECT id, nome FROM cesta");
$cestas = $queryCestas->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_familia = (int)$_POST['id_familia'];
    $id_cesta = (int)$_POST['id_cesta'];
    $data = date('Y-m-d');
    $quantidade = 1; // Quantidade de cestas doadas

    // Registrar doação na tabela 'cestas_doadas'
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
        <select name="id_familia" required>
            <?php foreach ($familias as $familia): ?>
                <option value="<?= $familia['id']; ?>"><?= $familia['nome']; ?></option>
            <?php endforeach; ?>
        </select><br>

        <label for="id_cesta">Cesta (ID da Cesta):</label>
        <select name="id_cesta" required>
            <?php foreach ($cestas as $cesta): ?>
                <option value="<?= $cesta['id']; ?>"><?= $cesta['nome']; ?></option>
            <?php endforeach; ?>
        </select><br>

        <button type="submit">Doar Cesta</button>
    </form>
</body>
</html>
