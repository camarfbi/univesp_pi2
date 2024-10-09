<?php
declare(strict_types=1);

namespace App\Includes;

class UserSearch
{
    private $pdo;
    private $recordsPerPage;
    private $currentPage;

    public function __construct(\PDO $pdo, int $recordsPerPage, int $currentPage)
    {
        $this->pdo = $pdo;
        $this->recordsPerPage = $recordsPerPage;
        $this->currentPage = $currentPage;
    }

	public function searchUsers(string $searchTerm): array
	{
		// Verifique se a página atual é pelo menos 1
		$this->currentPage = max(1, $this->currentPage);

		// Calcula o offset com base na página atual
		$offset = ($this->currentPage - 1) * $this->recordsPerPage;

		// Construa a consulta SQL
		$sql = 'SELECT * FROM Xusuarios';
		if ($searchTerm) {
			$sql .= ' WHERE nome LIKE :searchTerm OR sobrenome LIKE :searchTerm OR email LIKE :searchTerm';
		}
		$sql .= ' LIMIT :offset, :recordsPerPage';

		// Prepara e executa a consulta
		$stmt = $this->pdo->prepare($sql);
		if ($searchTerm) {
			$stmt->bindValue(':searchTerm', "%$searchTerm%", \PDO::PARAM_STR);
		}
		$stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
		$stmt->bindValue(':recordsPerPage', $this->recordsPerPage, \PDO::PARAM_INT);

		$stmt->execute();

		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}


    public function getTotalRecords(string $searchTerm): int
    {
        $sql = 'SELECT COUNT(*) FROM Xusuarios';
        if ($searchTerm) {
            $sql .= ' WHERE nome LIKE :searchTerm OR sobrenome LIKE :searchTerm OR email LIKE :searchTerm';
        }

        $stmt = $this->pdo->prepare($sql);
        if ($searchTerm) {
            $stmt->bindValue(':searchTerm', "%$searchTerm%", \PDO::PARAM_STR);
        }

        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

	public function generatePagination(int $totalRecords, string $searchTerm, string $currentPageUrl): string
{
    $totalPages = ceil($totalRecords / $this->recordsPerPage);
    $pagination = '';

    if ($totalPages > 1) {
        // Remove o parâmetro page= da URL atual (para evitar duplicação)
        $currentPageUrl = preg_replace('/&?page=\d+/', '', $currentPageUrl);
        
        for ($page = 1; $page <= $totalPages; $page++) {
            // Constrói a URL corretamente, concatenando parâmetros de consulta
            $paginationUrl = $currentPageUrl . '&pageNum=' . $page . '&recordsPerPage=' . $this->recordsPerPage . '&search=' . urlencode($searchTerm);
            $pagination .= '<a href="' . $paginationUrl . '" class="pagination-link">' . $page . '</a> ';
        }
    }

    return $pagination;
}

}

class AssociadoSearch {
    private $pdo;
    private $recordsPerPage;
    private $currentPage;

    public function __construct(\PDO $pdo, int $recordsPerPage, int $currentPage)
    {
        $this->pdo = $pdo;
        $this->recordsPerPage = $recordsPerPage;
        $this->currentPage = $currentPage;
    }

    public function searchAssociados(string $searchTerm): array
    {
        $offset = ($this->currentPage - 1) * $this->recordsPerPage;

        $sql = 'SELECT * FROM associados';
        if ($searchTerm) {
            $sql .= ' WHERE nome LIKE :searchTerm OR sobrenome LIKE :searchTerm OR email LIKE :searchTerm';
        }
        $sql .= ' LIMIT :offset, :recordsPerPage';

        $stmt = $this->pdo->prepare($sql);
        if ($searchTerm) {
            $stmt->bindValue(':searchTerm', "%$searchTerm%", \PDO::PARAM_STR);
        }
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->bindValue(':recordsPerPage', $this->recordsPerPage, \PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getTotalRecords(string $searchTerm): int
    {
        $sql = 'SELECT COUNT(*) FROM associados';
        if ($searchTerm) {
            $sql .= ' WHERE nome LIKE :searchTerm OR sobrenome LIKE :searchTerm OR email LIKE :searchTerm';
        }

        $stmt = $this->pdo->prepare($sql);
        if ($searchTerm) {
            $stmt->bindValue(':searchTerm', "%$searchTerm%", \PDO::PARAM_STR);
        }

        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    public function generatePagination(int $totalRecords, string $searchTerm): string
    {
        $totalPages = ceil($totalRecords / $this->recordsPerPage);
        $pagination = '';

        if ($totalPages > 1) {
            for ($page = 1; $page <= $totalPages; $page++) {
                $pagination .= '<a href="?search=' . urlencode($searchTerm) . '&page=' . $page . '&recordsPerPage=' . $this->recordsPerPage . '" class="pagination-link">' . $page . '</a> ';
            }
        }

        return $pagination;
    }
}
