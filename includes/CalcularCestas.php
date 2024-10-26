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
}
