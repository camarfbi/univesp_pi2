<?php
namespace App\Includes;

use PDO;

class Associado {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Insere novo associado
    public function insertAssociado(array $data): int {
        $stmt = $this->pdo->prepare('
            INSERT INTO associados (
                nome, sobrenome, cpf, rg, nascimento, email, telefone, celular, cep, endereco, num, complemento, bairro, cidade, uf, status, bloqueado, user, password, foto
            ) VALUES (
                :nome, :sobrenome, :cpf, :rg, :nascimento, :email, :telefone, :celular, :cep, :endereco, :num, :complemento, :bairro, :cidade, :uf, :status, :bloqueado, :user, :password, :foto
            )');

        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId(); // Retorna o ID do novo associado
    }

    // Atualiza um associado existente
    public function updateAssociado(int $id, array $data): bool {
        $fields = [
            'nome', 'sobrenome', 'cpf', 'rg', 'nascimento', 'email', 'telefone', 'celular',
            'cep', 'endereco', 'num', 'complemento', 'bairro', 'cidade', 'uf', 'status',
            'bloqueado', 'user', 'foto'
        ];

        // Monta a cláusula SET da query SQL
        $setClause = implode(', ', array_map(fn($field) => "$field = :$field", $fields));

        // Se a senha estiver presente, inclua 'password' na atualização
        if (!empty($data['password'])) {
            $setClause .= ', password = :password';
        } else {
            unset($data['password']); // Remove a senha do array de dados
        }

        // Prepara a query de atualização
        $sql = "UPDATE associados SET $setClause WHERE id = :id";
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

    // Função para converter data de 'DD/MM/YYYY' ou 'DDMMYYYY' para 'YYYY-MM-DD'
    private function convertDateToDbFormat(string $date): string {
        if (strpos($date, '/') !== false) {
            $parts = explode('/', $date);
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0]; // 'YYYY-MM-DD'
        } elseif (strlen($date) === 8) { // 'DDMMYYYY'
            return substr($date, 4, 4) . '-' . substr($date, 2, 2) . '-' . substr($date, 0, 2);
        }
        return ''; // Caso inválido
    }

    // Verifica se o email já existe
    public function emailExists(string $email, int $excludeId = 0): bool {
        $stmt = $this->pdo->prepare('SELECT id FROM associados WHERE email = :email AND id != :id');
        $stmt->execute(['email' => $email, 'id' => $excludeId]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // Verifica se o user já existe
    public function userExists(string $user, int $excludeId = 0): bool {
        $stmt = $this->pdo->prepare('SELECT id FROM associados WHERE user = :user AND id != :id');
        $stmt->execute(['user' => $user, 'id' => $excludeId]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtém um associado pelo ID
    public function getAssociadoById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM associados WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Processa o formulário de dados
    public function getFormData(array $postData, ?array $currentData, int $idAsso = 0): array {
        return [
            'nome' => $postData['nome'] ?? '',
            'sobrenome' => $postData['sobrenome'] ?? '',
            'cpf' => $postData['cpf'] ?? '',
            'rg' => $postData['rg'] ?? '',
            'nascimento' => $this->convertDateToDbFormat($postData['nascimento'] ?? ''), // Convertendo a data para 'YYYY-MM-DD'
            'email' => $postData['email'] ?? '',
            'telefone' => $postData['telefone'] ?? '',
            'celular' => $postData['celular'] ?? '',
            'cep' => $postData['cep'] ?? '',
            'endereco' => $postData['endereco'] ?? '',
            'num' => $postData['num'] ?? '',
            'complemento' => $postData['complemento'] ?? '',
            'bairro' => $postData['bairro'] ?? '',
            'cidade' => $postData['cidade'] ?? '',
            'uf' => $postData['uf'] ?? '',
            'status' => $postData['status'] ?? '',
            'bloqueado' => $postData['bloqueado'] ?? '',
            'user' => $postData['user'] ?? '',
            'password' => $postData['password'] ?? '',
            'foto' => $this->processImage($idAsso, $_FILES['foto'], $currentData['foto'] ?? null) // Processa a imagem
        ];
    }

    // Processa o upload de imagem e retorna o caminho
    public function processImage(int $idAsso, array $file, ?string $existingImage): string {
        if (isset($file['name']) && !empty($file['name'])) {
            $targetDir = 'uploads/familias/' . $idAsso . '/';

            // Cria o diretório caso não exista
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $targetFile = $targetDir . uniqid() . '.' . $extension;

            // Valida e move o arquivo
            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                return $targetFile;
            }
        }
        return $existingImage ?? 'default-image.png'; // Retorna imagem padrão se não houver upload
    }
}
?>
