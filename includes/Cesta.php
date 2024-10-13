<?php
namespace App\Includes;

use PDO;

class Cesta {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Insere novo associado
    public function insertCesta(array $data): int {
        $stmt = $this->pdo->prepare('
            INSERT INTO cesta (
                nome, tipo, produtos
            ) VALUES (
                :nome, :tipo, :produtos
            )');

        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId(); // Retorna o ID do novo associado
    }

    // Atualiza um associado existente
    public function updateCesta(int $id, array $data): bool {
        $fields = [
            'nome', 'tipo', 'produtos'
        ];

        // Monta a cláusula SET da query SQL
        $setClause = implode(', ', array_map(fn($field) => "$field = :$field", $fields));

        // Prepara a query de atualização
        $sql = "UPDATE cesta SET $setClause WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);

        // Garante que todos os campos obrigatórios estão definidos
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                $data[$field] = null; // Se o campo não existe, define como null
            }
        }

        // Adiciona o ID ao array de dados
        $data['id'] = $id;

        // Executa a query e retorna o resultado
        return $stmt->execute($data);
    }

    // Verifica se a cesta já existe
    public function cestaExists(string $nome, int $excludeId = 0): bool {
        $stmt = $this->pdo->prepare('SELECT id FROM cesta WHERE nome = :nome AND id != :id');
        $stmt->execute(['nome' => $nome, 'id' => $excludeId]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtém uma cesta pelo ID
    public function getCestaById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM cesta WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

	// Processa o formulário de dados
	public function getFormData(array $postData, ?array $currentData, int $idAsso = 0): array {
		return [
			'nome' => $postData['nome_cesta'] ?? $currentData['nome'] ?? '', // Captura o campo nome_cesta corretamente
			'tipo' => $postData['tipo_cesta'] ?? $currentData['tipo'] ?? '', // Captura o campo tipo_cesta corretamente
			'produtos' => $postData['produtos'] ?? $currentData['produtos'] ?? [] // Captura os produtos corretamente
		];
	}

    // Método para excluir uma cesta pelo ID
    public function deleteCesta(int $id): bool {
        $stmt = $this->pdo->prepare('DELETE FROM cesta WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
}
?>
