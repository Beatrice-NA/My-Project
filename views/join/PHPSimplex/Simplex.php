<?php

namespace PHPSimplex;

class Simplex
{
    private $tableau = []; // Tableau do Simplex

    public function __construct($objective, $constraints)
    {
        $this->initializeTableau($objective, $constraints);
    }

    private function initializeTableau($objective, $constraints)
    {
        // Adicionar a linha Z (função objetivo)
        $this->tableau[] = array_merge($objective, [0]);

        // Adicionar as restrições como linhas na tabela
        foreach ($constraints as $constraint) {
            $this->tableau[] = $constraint;
        }
    }

    public function solve()
    {
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

    private function canImprove()
    {
        // Verificação se ainda há coeficientes positivos na linha Z
        foreach ($this->tableau[0] as $value) {
            if ($value > 0) return true;
        }
        return false;
    }

    private function findPivotColumn()
    {
        return array_search(max($this->tableau[0]), $this->tableau[0]);
    }

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
            return null; // Nenhum valor positivo encontrado
        }
        return array_search(min($ratios), $ratios);
    }

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

    private function getSolution()
    {
        $solution = [];
        $numVariables = count($this->tableau[0]) - count($this->tableau) - 1;
        for ($i = 0; $i < $numVariables; $i++) {
            $solution["x{$i}"] = 0;
        }
        for ($i = 1; $i < count($this->tableau); $i++) {
            $column = array_search(1, $this->tableau[$i]);
            if ($column !== false && $column < $numVariables) {
                $solution["x{$column}"] = $this->tableau[$i][count($this->tableau[$i]) - 1];
            }
        }
        return $solution;
    }
}