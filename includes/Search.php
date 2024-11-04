<?php
declare(strict_types=1);

namespace App\Includes;

require_once 'BaseSearch.php';

use App\Includes\BaseSearch;

class UserSearch extends BaseSearch
{
    public function __construct(\PDO $pdo, int $recordsPerPage, int $currentPage)
    {
        parent::__construct($pdo, $recordsPerPage, $currentPage, 'Xusuarios', ['nome', 'sobrenome', 'email']);
    }

    public function searchUsers(string $searchTerm): array
    {
        return $this->search($searchTerm);
    }
}

class AssociadoSearch extends BaseSearch
{
    public function __construct(\PDO $pdo, int $recordsPerPage, int $currentPage)
    {
        parent::__construct($pdo, $recordsPerPage, $currentPage, 'associados', ['nome', 'sobrenome', 'email']);
    }

    public function searchAssociados(string $searchTerm): array
    {
        return $this->search($searchTerm);
    }
}

class CestaSearch extends BaseSearch
{
    public function __construct(\PDO $pdo, int $recordsPerPage, int $currentPage)
    {
        parent::__construct($pdo, $recordsPerPage, $currentPage, 'cesta', ['nome', 'tipo']);
    }

    public function searchCestas(string $searchTerm): array
    {
        return $this->search($searchTerm);
    }
}

class ProdutoSearch extends BaseSearch
{
    public function __construct(\PDO $pdo, int $recordsPerPage, int $currentPage)
    {
        parent::__construct($pdo, $recordsPerPage, $currentPage, 'produtos', ['nome', 'tipo']);
    }

    public function searchProduto(string $searchTerm): array
    {
        return $this->search($searchTerm);
    }
}

class CestaMontadaSearch extends BaseSearch
{
    public function __construct(\PDO $pdo, int $recordsPerPage, int $currentPage)
    {
        // Define a tabela e colunas relevantes
        parent::__construct($pdo, $recordsPerPage, $currentPage, 'cestas_criadas', ['data', 'quant_criada']);
    }

    // Método para buscar cestas montadas com base no nome da cesta e data
    public function searchCestasMontadas(string $searchTerm = ''): array
    {
        $offset = ($this->currentPage - 1) * $this->recordsPerPage;
        $query = "
            SELECT cc.id, c.nome AS nome_cesta, cc.data, cc.quant_criada
            FROM cestas_criadas cc
            JOIN cesta c ON cc.id_cesta = c.id
            WHERE c.nome LIKE :searchTerm
            ORDER BY cc.data DESC
            LIMIT :offset, :limit
        ";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $this->recordsPerPage, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Método para contar o total de registros de cestas montadas
    public function getTotalRecordsCestasMontadas(string $searchTerm = ''): int
    {
        $query = "
            SELECT COUNT(*) AS total
            FROM cestas_criadas cc
            JOIN cesta c ON cc.id_cesta = c.id
            WHERE c.nome LIKE :searchTerm
        ";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
        $stmt->execute();
        
        return (int) $stmt->fetchColumn();
    }
	
	// Método para atualizar uma cesta montada
    public function updateCestaMontada(int $id, int $quantidade, string $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE cestas_criadas
            SET quant_criada = :quantidade, data = :data
            WHERE id = :id
        ");
        return $stmt->execute([
            ':quantidade' => $quantidade,
            ':data' => $data,
            ':id' => $id
        ]);
    }

    // Método para excluir uma cesta montada
    public function deleteCestaMontada(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM cestas_criadas WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}