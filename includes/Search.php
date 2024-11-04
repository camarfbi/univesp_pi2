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