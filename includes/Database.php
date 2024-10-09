<?php
declare(strict_types=1);

namespace App\Includes;

use PDO;
use PDOException;
use Dotenv\Dotenv;

class Database {
    private PDO $pdo;

    public function __construct() {
        // Carrega as dependências do Composer
        $dotenv = Dotenv::createImmutable(__DIR__.'/../');
        $dotenv->load();

        $host = $_ENV['DB_HOST'];
        $dbname = $_ENV['DB_NAME'];
        $user = $_ENV['DB_USER'];
        $pass = $_ENV['DB_PASS'];

        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo 'Erro na conexão: ' . $e->getMessage();
        }
    }

    public function getPdo(): PDO {
        return $this->pdo;
    }
}
