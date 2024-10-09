<?php
declare(strict_types=1);

namespace App\Includes;

use PDO;

class User {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
		
	//Grava tentativas de login
	public function incrementLoginAttempts(int $userId): void {
        try {
            $stmt = $this->pdo->prepare('UPDATE Xusuarios SET login_attempts = login_attempts + 1, last_login_attempt = NOW() WHERE id = :id');
            $stmt->execute(['id' => $userId]);

            if ($stmt->rowCount() === 0) {
                throw new \Exception('Erro ao atualizar tentativas de login. Nenhuma linha foi afetada.');
            }
        } catch (\Exception $e) {
            // Exibe o erro para fins de depuração
            echo "Erro ao incrementar as tentativas de login: " . $e->getMessage();
        }
    }
	
	//Reseta tentativas após login
    public function resetLoginAttempts(int $userId): void {
        try {
            $stmt = $this->pdo->prepare('UPDATE Xusuarios SET login_attempts = 0, last_login_attempt = NULL WHERE id = :id');
            $stmt->execute(['id' => $userId]);

            if ($stmt->rowCount() === 0) {
                throw new \Exception('Erro ao resetar tentativas de login. Nenhuma linha foi afetada.');
            }
        } catch (\Exception $e) {
            // Exibe o erro para fins de depuração
            echo "Erro ao resetar as tentativas de login: " . $e->getMessage();
        }
    }
	
    // Método para criptografar a senha
    private function encryptPassword(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
    }
	
	    // Inserir novo usuário
    public function insertUser(array $data): bool {
        try {
            // Verificar se a senha foi fornecida antes de criptografá-la
            if (!empty($data['password'])) {
                $data['password'] = $this->encryptPassword($data['password']);
            }

            // Ajuste o nome da coluna para 'user'
            $columns = array_keys($data);
            $placeholders = array_map(fn($col) => ":$col", $columns);

            // Construir a query de inserção
            $sql = 'INSERT INTO Xusuarios (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);

            // Obter o ID do usuário recém-inserido
            $userId = (int) $this->pdo->lastInsertId();

            // Se uma imagem foi enviada, processá-la
            if (!empty($data['foto'])) {
                $imagePath = $this->processImage($userId, $data['user'], $data['foto']);

                // Atualizar a coluna 'foto' no banco de dados com o caminho da imagem
                $updateStmt = $this->pdo->prepare("UPDATE Xusuarios SET foto = :foto WHERE id = :id");
                $updateStmt->execute(['foto' => $imagePath, 'id' => $userId]);
            }

            return true;
        } catch (\PDOException $e) {
            echo "Erro ao inserir o usuário: " . $e->getMessage();
            return false;
        }
    }

	// Atualizar usuário existente
	public function updateUser(int $id, array $data): bool {
		try {
			// Buscar os dados atuais do usuário
			$currentUserData = $this->getUserById($id);

			// Lista de campos que queremos verificar e atualizar se necessário
			$fields = ['nome', 'sobrenome', 'user', 'email', 'descricao', 'celular', 'telefone', 'bloqueado', 'admin'];

			// Campos que foram alterados e que precisamos atualizar
			$fieldsToUpdate = [];

			// Iterar sobre os campos e verificar se foram alterados
			foreach ($fields as $field) {
				if (isset($data[$field]) && $data[$field] != $currentUserData[$field]) {
					$fieldsToUpdate[$field] = $data[$field];
				}
			}

			// Verificar a senha apenas se foi fornecida e o hash da senha atual não for nulo
			if (!empty($data['password'])) {
				// Somente verificar a senha se o valor atual de 'password' no banco não for nulo
				if ($currentUserData['password'] !== null && !password_verify($data['password'], $currentUserData['password'])) {
					$fieldsToUpdate['password'] = $this->encryptPassword($data['password']);
				} elseif ($currentUserData['password'] === null) {
					// Se o hash atual é nulo, criptografa a nova senha diretamente
					$fieldsToUpdate['password'] = $this->encryptPassword($data['password']);
				}
			}

			// Processar a foto se ela foi enviada e é diferente
			if (!empty($data['foto'])) {
				$imagePath = $this->processImage($id, $data['user'], $data['foto']);
				if ($imagePath !== $currentUserData['foto']) {
					$fieldsToUpdate['foto'] = $imagePath;
				}
			}

			// Se não houver nada para atualizar, retorna true (sem necessidade de atualizar)
			if (empty($fieldsToUpdate)) {
				return true;
			}

			// Construir a query de atualização
			$setClause = implode(', ', array_map(fn($key) => "$key = :$key", array_keys($fieldsToUpdate)));
			$fieldsToUpdate['id'] = $id; // Adiciona o ID para a cláusula WHERE

			$stmt = $this->pdo->prepare("UPDATE Xusuarios SET $setClause WHERE id = :id");
			return $stmt->execute($fieldsToUpdate);
		} catch (\PDOException $e) {
			echo "Erro ao atualizar o usuário: " . $e->getMessage();
			return false;
		}
	}

	// Salvar usuário (insere ou atualiza dependendo do ID)
	public function saveUser(array $data): bool {
		// Verificar se o email já existe no banco de dados
		$existingUserByEmail = $this->getUserByEmail($data['email']);
		
		// Se o email existir e pertencer a outro usuário, lançar exceção
		if ($existingUserByEmail && (int)$existingUserByEmail['id'] !== (int)$data['id']) {
			throw new \Exception('Email já cadastrado.');
		}
		
		// Verificar se o username já existe no banco de dados
		$existingUserByUsername = $this->getUserByUsername($data['user']);
		
		// Se o username existir e pertencer a outro usuário, lançar exceção
		if ($existingUserByUsername && (int)$existingUserByUsername['id'] !== (int)$data['id']) {
			throw new \Exception('Username já cadastrado.');
		}

		// Inserir ou atualizar usuário
		if ($data['id'] == 0) {
			return $this->insertUser($data);
		} else {
			return $this->updateUser((int)$data['id'], $data);
		}
	}

    // Processar o upload da imagem e salvar no diretório correto
    private function processImage(int $userId, string $username, string $imageFile): string {
        $directory = "./admin/imagens/$userId";
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = pathinfo($imageFile, PATHINFO_EXTENSION);
        $imagePath = "$directory/" . preg_replace('/[^A-Za-z0-9_\-]/', '_', $username) . '.' . $extension;

        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $imagePath)) {
            throw new \RuntimeException('Falha ao mover o arquivo para o diretório de destino.');
        }

        return $imagePath;
    }

    // Função para remover todas as permissões de um usuário
    public function removeAllPermissions(int $perfilId): bool {
        $stmt = $this->pdo->prepare('DELETE FROM Xpermissoes WHERE perfil_id = :perfil_id');
        return $stmt->execute(['perfil_id' => $perfilId]);
    }

    // Função para adicionar permissões de categoria ou subcategoria
    public function addPermission(int $perfilId, string $tipo, array $ids): bool {
        $idString = implode(',', $ids);

        $stmt = $this->pdo->prepare('SELECT * FROM Xpermissoes WHERE perfil_id = :perfil_id');
        $stmt->execute(['perfil_id' => $perfilId]);
        $existingPermissions = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($tipo === 'categoria') {
            if ($existingPermissions) {
                $stmt = $this->pdo->prepare('UPDATE Xpermissoes SET categoria_id = :categoria_id WHERE perfil_id = :perfil_id');
            } else {
                $stmt = $this->pdo->prepare('INSERT INTO Xpermissoes (perfil_id, categoria_id) VALUES (:perfil_id, :categoria_id)');
            }
            return $stmt->execute(['categoria_id' => $idString, 'perfil_id' => $perfilId]);
        }

        if ($tipo === 'subcategoria') {
            if ($existingPermissions) {
                $stmt = $this->pdo->prepare('UPDATE Xpermissoes SET subcategoria_id = :subcategoria_id WHERE perfil_id = :perfil_id');
            } else {
                $stmt = $this->pdo->prepare('INSERT INTO Xpermissoes (perfil_id, subcategoria_id) VALUES (:perfil_id, :subcategoria_id)');
            }
            return $stmt->execute(['subcategoria_id' => $idString, 'perfil_id' => $perfilId]);
        }

        return false;
    }
	
	// Buscar usuário por email
	public function getUserByEmail(string $email): ?array {
		$stmt = $this->pdo->prepare('SELECT * FROM Xusuarios WHERE email = :email');
		$stmt->execute(['email' => $email]);
		return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
	}
	
	// Buscar usuário por username
    public function getUserByUsername(string $username): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM Xusuarios WHERE user = :username');
        $stmt->execute(['username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Buscar nome do usuário pelo ID
    public function getUserNameById(int $userId): ?string {
        $stmt = $this->pdo->prepare('SELECT nome FROM Xusuarios WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ? $user['nome'] : null;
    }
	
	public function getUserById(int $userId): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM Xusuarios WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Buscar permissões do usuário agrupadas por categoria e subcategoria
    public function getAllCategoriesAndSubcategories(int $perfilId): array {
        $stmt = $this->pdo->prepare("
            SELECT 
                c.id AS categoria_id, 
                c.nome AS categoria_nome, 
                c.icon AS categoria_icon, 
                s.id AS subcategoria_id, 
                s.nome AS subcategoria_nome,
                FIND_IN_SET(c.id, p.categoria_id) AS tem_categoria,
                FIND_IN_SET(s.id, p.subcategoria_id) AS tem_subcategoria
            FROM Xcategorias c
            LEFT JOIN Xsubcategorias s ON s.categoria_id = c.id
            LEFT JOIN Xpermissoes p ON p.perfil_id = :perfil_id
        ");
        $stmt->execute(['perfil_id' => $perfilId]);
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agrupar as permissões por categoria
        $groupedPermissions = [];
        foreach ($permissions as $perm) {
            if (!isset($groupedPermissions[$perm['categoria_id']])) {
                $groupedPermissions[$perm['categoria_id']] = [
                    'categoria_nome' => $perm['categoria_nome'],
                    'tem_categoria' => $perm['tem_categoria'],
                    'categoria_icon' => $perm['categoria_icon'],
                    'subcategorias' => []
                ];
            }

            if ($perm['subcategoria_id']) {
                $groupedPermissions[$perm['categoria_id']]['subcategorias'][] = [
                    'subcategoria_id' => $perm['subcategoria_id'],
                    'subcategoria_nome' => $perm['subcategoria_nome'],
                    'tem_subcategoria' => $perm['tem_subcategoria']
                ];
            }
        }

        return $groupedPermissions;
    }
}
