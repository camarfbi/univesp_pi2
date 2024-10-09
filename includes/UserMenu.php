<?php

namespace App\Includes;

use PDO;

class UserMenu
{
    private $pdo;
    private $userId;

    public function __construct(PDO $pdo, int $userId)
    {
        $this->pdo = $pdo;
        $this->userId = $userId;
    }

    // Obtém categorias considerando as permissões do usuário
    public function getCategories(): array
    {
        $sql = "SELECT DISTINCT c.id AS categoria_id, c.nome AS categoria_nome, c.link AS categoria_link, c.icon AS categoria_icon, c.active AS categoria_active, c.ordem AS categoria_ordem
                FROM Xcategorias c
                JOIN Xpermissoes p ON FIND_IN_SET(c.id, p.categoria_id)
				WHERE p.perfil_id = (SELECT perfil_id FROM Xusuarios WHERE id = :userId)
				AND c.active = 1
				ORDER BY c.ordem ASC";
        
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute(['userId' => $this->userId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

    // Obtém subcategorias para uma categoria específica, considerando as permissões
    public function getSubcategories(int $categoryId): array
    {
        $sql = "SELECT s.id AS subcategoria_id, s.nome AS subcategoria_nome, s.link AS subcategoria_link, s.icon AS subcategoria_icon, s.dir AS subcategoria_dir, s.visible AS subcategoria_visible
                FROM Xsubcategorias s
                JOIN Xpermissoes p ON FIND_IN_SET(s.id, p.subcategoria_id)
                WHERE s.categoria_id = :categoriaId
				AND s.visible = 1
                AND p.perfil_id = (SELECT perfil_id FROM Xusuarios WHERE id = :userId)
				ORDER BY s.ordem ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['categoriaId' => $categoryId, 'userId' => $this->userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
