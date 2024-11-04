<?php
namespace App\Includes;

use PDO;

class Produto {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Insere novo associado
    public function insertProduto(array $data): int {
        $stmt = $this->pdo->prepare('
            INSERT INTO produtos (
                nome, tipo, un_med
            ) VALUES (
                :nome, :tipo, :un_med
            )');

        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId(); // Retorna o ID do novo associado
    }

    // Atualiza um associado existente
    public function updateProduto(int $id, array $data): bool {
        $fields = [
            'nome', 'tipo', 'un_med'
        ];

        // Monta a cláusula SET da query SQL
        $setClause = implode(', ', array_map(fn($field) => "$field = :$field", $fields));

        // Prepara a query de atualização
        $sql = "UPDATE produtos SET $setClause WHERE id = :id";
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

    // Verifica se a produto já existe
    public function produtoExists(string $nome, int $excludeId = 0): bool {
        $stmt = $this->pdo->prepare('SELECT id FROM produtos WHERE nome = :nome AND id != :id');
        $stmt->execute(['nome' => $nome, 'id' => $excludeId]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtém uma produtos pelo ID
    public function getProdutoById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM produtos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

	// Processa o formulário de dados
	public function getFormData(array $postData, ?array $currentData, int $idAsso = 0): array {
		return [
			'nome' => $postData['nome_produto'] ?? $currentData['nome'] ?? '', // Corrige para capturar 'nome_produto'
			'tipo' => $postData['tipo'] ?? $currentData['tipo'] ?? '', // Captura o campo tipo_produtos corretamente
			'un_med' => $postData['un_med'] ?? $currentData['un_med'] ?? '' // Captura a unidade de medida corretamente
		];
	}


    // Método para excluir uma produtos pelo ID
    public function deleteProduto(int $id): bool {
        $stmt = $this->pdo->prepare('DELETE FROM produtos WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    // Insere uma nova quantidade de estoque para um produto
    public function insertEstoque(int $idProd, int $quant): bool {
        $stmt = $this->pdo->prepare('
            INSERT INTO produtos_estoque (id_prod, quant)
            VALUES (:id_prod, :quant)
        ');
        return $stmt->execute(['id_prod' => $idProd, 'quant' => $quant]);
    }

    // Atualiza a quantidade de estoque de um produto existente
    public function updateEstoque(int $idProd, int $quant): bool {
        $stmt = $this->pdo->prepare('
            UPDATE produtos_estoque
            SET quant = :quant
            WHERE id_prod = :id_prod
        ');
        return $stmt->execute(['id_prod' => $idProd, 'quant' => $quant]);
    }

    // Verifica se o produto já possui entrada de estoque
    public function estoqueExists(int $idProd): bool {
        $stmt = $this->pdo->prepare('
            SELECT 1 FROM produtos_estoque WHERE id_prod = :id_prod
        ');
        $stmt->execute(['id_prod' => $idProd]);
        return (bool) $stmt->fetchColumn();
    }
	
}
