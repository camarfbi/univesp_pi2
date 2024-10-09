<?php
// Parâmetros de paginação e busca
$recordsPerPage = isset($_GET['recordsPerPage']) ? (int)$_GET['recordsPerPage'] : 10;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Instancie a classe de busca e obtenha os resultados
$userSearch = new UserSearch($pdo, $recordsPerPage, $currentPage);
$users = $userSearch->searchUsers($searchTerm);
$totalRecords = $userSearch->getTotalRecords($searchTerm);
$totalPages = ceil($totalRecords / $recordsPerPage);
$pagination = $userSearch->generatePagination($totalRecords, $searchTerm);

// Se a requisição for via AJAX, retorne os dados em JSON
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    echo json_encode([
        'data' => $users,
        'pagination' => $pagination
    ]);
    exit();
}
