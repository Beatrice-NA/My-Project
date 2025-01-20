<?php

namespace PHPSimplex;

class Simplex
{
    private $tableau = []; // Tableau do Simplex
    private $objective = [];
    private $constraints = [];

    public function __construct($objective, $constraints)
    {
        $this->objective = $objective;
        $this->constraints = $constraints;
        $this->initializeTableau($objective, $constraints);
    }

    /**
     * Inicializa o tableau para o Simplex, incluindo variáveis de folga e artificiais.
     */
    private function initializeTableau($objective, $constraints)
    {
        $numConstraints = count($constraints);
        $numVariables = count($objective);

        // Linha Z (função objetivo, minimização: coeficientes negativos)
        $this->tableau[] = array_merge(
            array_map(fn($coef) => -1 * $coef, $objective), // Coeficientes da função objetivo
            array_fill(0, $numConstraints, 0),             // Coeficientes das variáveis de folga
            array_fill(0, $numConstraints, 1),             // Coeficientes das variáveis artificiais
            [0]                                            // Valor da função objetivo (à direita)
        );

        // Adiciona as restrições ao tableau
        foreach ($constraints as $index => $constraint) {
            $this->tableau[] = array_merge(
                array_slice($constraint, 0, $numVariables),  // Coeficientes das variáveis originais
                array_fill(0, $numConstraints, 0),           // Espaço para variáveis de folga
                array_fill(0, $numConstraints, 0),           // Espaço para variáveis artificiais
                [$constraint[$numVariables]]                // Valor à direita da equação
            );

            // Adicionar 1 na coluna da variável artificial ou de folga
            $this->tableau[$index + 1][$numVariables + $index] = 1;
        }
    }

    /**
     * Método principal para resolver o problema usando o Simplex.
     */
    public function solve()
    {
        // Fase 1: Resolver para as variáveis artificiais
        while ($this->canImprove()) {
            $pivotColumn = $this->findPivotColumn();
            $pivotRow = $this->findPivotRow($pivotColumn);
            if ($pivotRow === null) {
                throw new \Exception("Problema não tem solução viável.");
            }
            $this->performPivot($pivotRow, $pivotColumn);
        }

        // Fase 2: Resolver o problema original sem as artificiais
        $this->removeArtificialVariables();

        // Repetir o processo até encontrar a solução ótima
        while ($this->canImprove()) {
            $pivotColumn = $this->findPivotColumn();
            $pivotRow = $this->findPivotRow($pivotColumn);
            if ($pivotRow === null) {
                throw new \Exception("Problema não tem solução viável.");
            }
            $this->performPivot($pivotRow, $pivotColumn);
        }

        return $this->getSolution();
    }

    /**
     * Verifica se ainda é possível melhorar a solução (minimização).
     */
    private function canImprove()
    {
        foreach ($this->tableau[0] as $value) {
            if ($value < 0) return true; // Minimização busca valores negativos
        }
        return false;
    }

    /**
     * Encontra a coluna pivô com o menor valor na linha Z.
     */
    private function findPivotColumn()
    {
        return array_search(min($this->tableau[0]), $this->tableau[0]);
    }

    /**
     * Encontra a linha pivô com base nas razões mínimas (restrições).
     */
    private function findPivotRow($pivotColumn)
    {
        $ratios = [];
        for ($i = 1; $i < count($this->tableau); $i++) {
            $row = $this->tableau[$i];
            if ($row[$pivotColumn] > 0) { // Evitar divisão por zero ou valores negativos
                $ratios[$i] = $row[count($row) - 1] / $row[$pivotColumn];
            }
        }
        if (empty($ratios)) {
            return null; // Nenhuma razão válida encontrada
        }
        return array_search(min($ratios), $ratios);
    }

    /**
     * Realiza o pivoteamento em torno de um elemento pivô.
     */
    private function performPivot($pivotRow, $pivotColumn)
    {
        $pivotValue = $this->tableau[$pivotRow][$pivotColumn];

        // Normalizar a linha do pivô
        foreach ($this->tableau[$pivotRow] as &$value) {
            $value /= $pivotValue;
        }

        // Atualizar as outras linhas
        for ($i = 0; $i < count($this->tableau); $i++) {
            if ($i === $pivotRow) continue;
            $factor = $this->tableau[$i][$pivotColumn];
            foreach ($this->tableau[$i] as $j => &$value) {
                $value -= $factor * $this->tableau[$pivotRow][$j];
            }
        }
    }

    /**
     * Remove as variáveis artificiais do tableau após a Fase 1.
     */
    private function removeArtificialVariables()
    {
        $numOriginalVariables = count($this->objective);

        // Remover colunas das variáveis artificiais
        foreach ($this->tableau as &$row) {
            $row = array_slice($row, 0, $numOriginalVariables + count($this->constraints) + 1);
        }
    }

    /**
     * Obtém a solução final do problema.
     */
    private function getSolution()
    {
        $solution = [];
        $numVariables = count($this->objective);
        for ($i = 0; $i < $numVariables; $i++) {
            $solution["x{$i}"] = 0;
        }

        // Encontrar valores das variáveis básicas
        for ($i = 1; $i < count($this->tableau); $i++) {
            $column = array_search(1, $this->tableau[$i]);
            if ($column !== false && $column < $numVariables) {
                $solution["x{$column}"] = $this->tableau[$i][count($this->tableau[$i]) - 1];
            }
        }

        return $solution;
    }
}
