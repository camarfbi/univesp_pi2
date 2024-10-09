<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../vendor/autoload.php'; // Caminho para o autoload do Composer
require __DIR__ . '/../includes/Database.php';
require __DIR__ . '/../includes/Familias.php';

use App\Includes\Database;
use App\Includes\Associado;
// Inicializa a conexão com o banco de dados
$pdo = (new Database())->getPdo();
$associadoModel = new Associado($pdo);

// Verifica se o ID do associado foi passado
if (isset($_GET['id'])) {
    $associadoId = (int)$_GET['id'];
    $associado = $associadoModel->getAssociadoById($associadoId);

    if ($associado) {
        // Retorna os dados do associado em formato JSON
        echo json_encode($associado);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Associado não encontrado']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ID não fornecido']);
}
?>
