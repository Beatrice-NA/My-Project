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
        $this->initializeTableau();
    }

    // Inicializa o tableau para o Simplex
    private function initializeTableau()
    {
        $numConstraints = count($this->constraints);
        $numVariables = count($this->objective);

        // Adicionar a linha Z (função objetivo para minimização)
        $this->tableau[] = array_merge(
            array_map(fn($coef) => -1 * $coef, $this->objective), // Coeficientes negativos para minimização
            array_fill(0, $numConstraints, -1000), //  alta valor para variáveis artificiais
            [0] // Valor inicial de Z
        );

        // Adicionar as restrições
        foreach ($this->constraints as $index => $constraint) {
            $rhsValue = $constraint[$numVariables]; // Último elemento é o lado direito da equação (RHS) 
            $constraintCoefficients = array_slice($constraint, 0, $numVariables);

            // Criar a linha da restrição
            $this->tableau[] = array_merge(
                $constraintCoefficients,
                array_fill(0, $numConstraints, 0), // Folgas
                array_fill(0, $numConstraints, 0), // Artificiais
                [$rhsValue]
            );

            // Adicionar 1 na coluna da variável de folga correspondente
            $this->tableau[$index + 1][$numVariables + $index] = 1;
        }
    }

    public function solve()
    {
        // Resolver em duas fases (Fase 1: Artificiais, Fase 2: Original)
        while ($this->canImprove()) {
            $pivotColumn = $this->findPivotColumn();
            $pivotRow = $this->findPivotRow($pivotColumn);
            if ($pivotRow === null) {
                throw new \Exception("Problema não tem solução viável.");
            }
            $this->performPivot($pivotRow, $pivotColumn);
        }

        $this->removeArtificialVariables();

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
        foreach ($this->tableau[0] as $value) {
            if ($value < 0) return true;
        }
        return false;
    }

    private function findPivotColumn()
{
    return array_search(min($this->tableau[0]), $this->tableau[0]);
}

    private function findPivotRow($pivotColumn)
{
    $ratios = [];
    for ($i = 1; $i < count($this->tableau); $i++) {
        $row = $this->tableau[$i];
        // Verifica se o elemento no pivotColumn é positivo e suficientemente grande para evitar divisões por quase zero
        if (abs($row[$pivotColumn]) > 1e-10) { 
            $ratios[$i] = $row[count($row) - 1] / $row[$pivotColumn];
        }
    }
    if (empty($ratios)) {
        return null; // Nenhuma razão válida encontrada
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

    private function removeArtificialVariables()
    {
        $numOriginalVariables = count($this->objective);

        // Remover colunas das variáveis artificiais
        foreach ($this->tableau as &$row) {
            $row = array_slice($row, 0, $numOriginalVariables + count($this->constraints) + 1);
        }
    }

    public function getTableau()
    {
        return $this->tableau;
    }

    private function getSolution()
    {
        $solution = [];
        $numVariables = count($this->objective);  // O número de variáveis é dado pela quantidade de coeficientes na função objetivo.
        
        // Inicializa todas as variáveis com valor 0
        for ($i = 0; $i < $numVariables; $i++) {
            $solution["x{$i}"] = 0;
        }
    
        // Itera sobre as linhas do tableau (exceto a linha de custos/função objetivo)
        for ($i = 1; $i < count($this->tableau); $i++) {
            // Verifica se há exatamente um valor 1 em uma coluna (indicando uma variável básica)
            $columnIndex = array_search(1, $this->tableau[$i]);
    
            // Verifica se a variável básica encontrada está dentro do número de variáveis
            if ($columnIndex !== false && $columnIndex < $numVariables) {
                // O valor da variável básica será o valor na última coluna (a constante)
                $solution["x{$columnIndex}"] = $this->tableau[$i][count($this->tableau[$i]) - 1];
            }
        }
    
        return $solution;
    }    
}
