<?php
declare(strict_types=1);

namespace App\Includes;

abstract class BaseSearch
{
    protected $pdo;
    protected $recordsPerPage;
    protected $currentPage;
    protected $tableName;
    protected $searchableColumns;

    public function __construct(\PDO $pdo, int $recordsPerPage, int $currentPage, string $tableName, array $searchableColumns)
    {
        $this->pdo = $pdo;
        $this->recordsPerPage = $recordsPerPage;
        $this->currentPage = max(1, $currentPage);  // Garantir que a página seja no mínimo 1
        $this->tableName = $tableName;
        $this->searchableColumns = $searchableColumns;
    }

    public function search(string $searchTerm): array
    {
        $offset = ($this->currentPage - 1) * $this->recordsPerPage;

        $sql = 'SELECT * FROM ' . $this->tableName;
        if ($searchTerm) {
            $conditions = array_map(fn($column) => "$column LIKE :searchTerm", $this->searchableColumns);
            $sql .= ' WHERE ' . implode(' OR ', $conditions);
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
        $sql = 'SELECT COUNT(*) FROM ' . $this->tableName;
        if ($searchTerm) {
            $conditions = array_map(fn($column) => "$column LIKE :searchTerm", $this->searchableColumns);
            $sql .= ' WHERE ' . implode(' OR ', $conditions);
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
            $currentPageUrl = preg_replace('/&?page=\d+/', '', $currentPageUrl);

            for ($page = 1; $page <= $totalPages; $page++) {
                $paginationUrl = $currentPageUrl . '&pageNum=' . $page . '&recordsPerPage=' . $this->recordsPerPage . '&search=' . urlencode($searchTerm);
                $pagination .= '<a href="' . $paginationUrl . '" class="pagination-link">' . $page . '</a> ';
            }
        }

        return $pagination;
    }
}