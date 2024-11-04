<?php
namespace App\Includes;

use PDO;

class CalcularCestas {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    // Função para calcular as cestas completas e os produtos faltantes
    public function calcularCestas($produtos_cesta, &$estoque) {
        $cestas_completas = PHP_INT_MAX;
        $produtos_faltantes = [];
        $detalhes_produtos = [];

        // Calcular o número máximo de cestas que podem ser feitas com o estoque atual
        foreach ($produtos_cesta as $produto) {
            if (!isset($produto['produto'])) {
                continue;
            }

            $quant_produto_estoque = 0;
            $produto_encontrado_no_estoque = false;

            foreach ($estoque as &$item) {
                if (isset($item['nome']) && $item['nome'] === $produto['produto']) {
                    $quant_produto_estoque = $item['quant'];
                    $produto_encontrado_no_estoque = true;
                    break;
                }
            }

            if (!$produto_encontrado_no_estoque) {
                $quant_produto_estoque = 0;
            }

            if ($produto['quantidade'] > 0) {
                $cestas_possiveis = intdiv($quant_produto_estoque, $produto['quantidade']);
                $cestas_completas = min($cestas_completas, $cestas_possiveis);
            }

            $detalhes_produtos[] = [
                'nome' => $produto['produto'],
                'quant_necessaria' => $produto['quantidade'],
                'quant_estoque' => $quant_produto_estoque,
                'estoque_restante' => max(0, $quant_produto_estoque - ($cestas_completas * $produto['quantidade']))
            ];

            if ($quant_produto_estoque < $produto['quantidade']) {
                $produtos_faltantes[$produto['produto']] = $produto['quantidade'] - $quant_produto_estoque;
            }
        }

        if ($cestas_completas === PHP_INT_MAX) {
            $cestas_completas = 0;
        }

        // Subtrair a quantidade correta de produtos usados do estoque global
        if ($cestas_completas > 0) {
            foreach ($produtos_cesta as $produto) {
                if (!isset($produto['produto'])) {
                    continue;
                }

                foreach ($estoque as &$item) {
                    if (isset($item['nome']) && $item['nome'] === $produto['produto']) {
                        $item['quant'] -= $cestas_completas * $produto['quantidade'];
                        break;
                    }
                }
            }
        }

        // Atualizar o estoque_restante no array detalhes_produtos após a criação de cestas completas
        foreach ($detalhes_produtos as &$detalhe) {
            foreach ($estoque as $item) {
                if ($item['nome'] === $detalhe['nome']) {
                    $detalhe['estoque_restante'] = $item['quant'];
                    break;
                }
            }
        }
        
        return [$cestas_completas, $produtos_faltantes, $detalhes_produtos];
    }
	
    // Função para salvar ou atualizar a quantidade de cestas criadas
    public function salvarCestasCriadas(int $id_cesta, string $data, int $quantidade) {
        // Verificar se já existe uma entrada para o mesmo ano, mês e tipo de cesta
        $stmt = $this->pdo->prepare("
            SELECT id FROM cestas_criadas 
            WHERE id_cesta = :id_cesta AND DATE_FORMAT(data, '%Y-%m') = DATE_FORMAT(:data, '%Y-%m')
        ");
        $stmt->execute([
            ':id_cesta' => $id_cesta,
            ':data' => $data
        ]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Atualizar a quantidade se já existir uma entrada
            $stmt = $this->pdo->prepare("
                UPDATE cestas_criadas 
                SET quant_criada = :quant_criada
                WHERE id = :id
            ");
            $stmt->execute([
                ':quant_criada' => $quantidade,
                ':id' => $existing['id']
            ]);
        } else {
            // Inserir uma nova entrada se não existir
            $stmt = $this->pdo->prepare("
                INSERT INTO cestas_criadas (id_cesta, data, quant_criada) 
                VALUES (:id_cesta, :data, :quant_criada)
            ");
            $stmt->execute([
                ':id_cesta' => $id_cesta,
                ':data' => $data,
                ':quant_criada' => $quantidade
            ]);
        }
    }

    // Função para deduzir o estoque com base na quantidade de cestas criadas
    public function deduzirEstoque(int $id_cesta, int $quantidade): bool {
        // Obter os produtos e quantidades necessárias para a cesta
        $stmt = $this->pdo->prepare("
            SELECT cp.id_produto, cp.quantidade AS quant_por_cesta, e.quant AS estoque_atual
            FROM cesta_produtos cp
            JOIN produtos_estoque e ON cp.id_produto = e.id_prod
            WHERE cp.id_cesta = :id_cesta
        ");
        $stmt->execute([':id_cesta' => $id_cesta]);
        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Verificar se há estoque suficiente para todos os produtos
        foreach ($produtos as $produto) {
            $quantidade_necessaria = $produto['quant_por_cesta'] * $quantidade;

            if ($produto['estoque_atual'] < $quantidade_necessaria) {
                // Se o estoque é insuficiente para qualquer produto, retornar false
                return false;
            }
        }

        // Deduzir o estoque se todos os produtos tiverem quantidade suficiente
        foreach ($produtos as $produto) {
            $quantidade_deduzir = $produto['quant_por_cesta'] * $quantidade;

            $stmtUpdate = $this->pdo->prepare("
                UPDATE produtos_estoque 
                SET quant = GREATEST(quant - :quantidade_deduzir, 0)
                WHERE id_prod = :id_produto
            ");
            $stmtUpdate->execute([
                ':quantidade_deduzir' => $quantidade_deduzir,
                ':id_produto' => $produto['id_produto']
            ]);
        }

        return true;
    }
}
