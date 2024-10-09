<?php
declare(strict_types=1);

namespace App\Includes;

use PDO;
use PDOException;

class PermissionManager
{
    private PDO $pdo;
    private int $userId;

    public function __construct(PDO $pdo, int $userId)
    {
        $this->pdo = $pdo;
        $this->userId = $userId;

        // Carrega as permissões para a sessão diretamente no construtor
        $this->loadPermissionsToSession();
    }

    // Carrega as permissões do banco de dados e armazena na sessão
    public function loadPermissionsToSession(): void
    {
        try {
            // Obtenha as permissões de categorias e subcategorias
            $sql = "
                SELECT 
                    perm.categoria_id,
                    perm.subcategoria_id
                FROM Xpermissoes perm
                WHERE perm.perfil_id = (
                    SELECT perfil_id FROM Xusuarios WHERE id = :user_id
                )
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
            $stmt->execute();
            $permissions = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($permissions) {
                // Extrai os IDs das categorias e subcategorias
                $categoriaIds = explode(',', $permissions['categoria_id']);
                $subcategoriaIds = explode(',', $permissions['subcategoria_id']);

                // Consultar os links e diretórios das categorias e subcategorias permitidas
                $sql = "
                    SELECT 
                        cat.link AS categoria_link,
                        cat.dir AS categoria_dir,
                        sub.link AS subcategoria_link,
                        sub.dir AS subcategoria_dir
                    FROM Xcategorias cat
                    LEFT JOIN Xsubcategorias sub ON cat.id = sub.categoria_id
                    WHERE cat.id IN (" . implode(',', array_map('intval', $categoriaIds)) . ")
                    OR sub.id IN (" . implode(',', array_map('intval', $subcategoriaIds)) . ")
                ";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Organiza as permissões em arrays de categorias e subcategorias
                $subcategorias = [];
                foreach ($permissions as $permission) {
                    if (!empty($permission['subcategoria_link']) && !empty($permission['subcategoria_dir'])) {
                        $subcategorias[] = [
                            'link' => $permission['subcategoria_link'],
                            'dir' => $permission['subcategoria_dir']
                        ];
                    }
                }

                // Salva as permissões na sessão
                $_SESSION['user_permissions'] = [
                    'subcategorias' => $subcategorias,
                ];
            }

        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    // Verifica se o usuário tem permissão para a página
    public function hasPermissionForPage(string $pageLink): bool
    {
        try {
            $sql = "
                SELECT 
                    cat.id AS categoria_id, 
                    sub.id AS subcategoria_id
                FROM Xcategorias cat
                LEFT JOIN Xsubcategorias sub ON cat.id = sub.categoria_id
                WHERE sub.link = :page_link OR cat.link = :page_link
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':page_link', $pageLink, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return false; // Página não encontrada na categoria/subcategoria
            }

            $categoriaId = $result['categoria_id'];
            $subcategoriaId = $result['subcategoria_id'];

            // Verifica se a categoria e subcategoria estão nas permissões do usuário na sessão
            $subcategoriasPermitidas = $_SESSION['user_permissions']['subcategorias'] ?? [];

            foreach ($subcategoriasPermitidas as $permission) {
                if ($permission['link'] === $pageLink) {
                    return true; // Usuário tem permissão
                }
            }

            return false; // Sem permissão
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    // Função para verificar se o usuário tem permissão para acessar a página
    public function hasPermission(string $requiredLink, string $requiredDir): bool
    {
        // Verifica se as permissões estão armazenadas na sessão
        if (isset($_SESSION['user_permissions']['subcategorias'])) {
            // Percorre o array de subcategorias com permissão
            foreach ($_SESSION['user_permissions']['subcategorias'] as $permission) {
                // Verifica se a subcategoria corresponde ao link e diretório desejado
                if ($permission['link'] === $requiredLink && $permission['dir'] === $requiredDir) {
                    return true; // O usuário tem permissão
                }
            }
        }
        return false; // O usuário não tem permissão
    }

    // Função para obter a página e diretório atual
    public function getCurrentPageAndDirectory(): array
    {
        // Verifica se o parâmetro 'page' está definido na URL
        if (isset($_GET['page'])) {
            $fullPath = $_GET['page'];

            // Remove a extensão '.php' caso exista
            $fullPath = preg_replace('/\.php$/', '', $fullPath);

            // Divide o caminho em partes (diretório e página)
            $pathParts = explode('/', $fullPath);

            // O nome da página é o último elemento do array
            $page = array_pop($pathParts);

            // O diretório é o restante do array (se existir), caso contrário, retorna 'root'
            $directory = !empty($pathParts) ? implode('/', $pathParts) : 'root';

            return [
                'page' => $page,
                'dir' => $directory
            ];
        }

        // Se o parâmetro 'page' não existir, retorna valores padrão
        return [
            'page' => 'dashboard',
            'dir' => 'root'
        ];
    }
}
