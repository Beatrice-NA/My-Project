<?php

namespace PHPSimplex;

class Simplex
{
    private $objective;  // Função objetivo
    private $constraints; // Restrições
    private $tableau;    // Tableau do Simplex
    private $numVariables;
    private $numConstraints;

    public function __construct($objective, $constraints)
    {
        $this->objective = $this->parseObjective($objective);
        $this->constraints = $this->parseConstraints($constraints);

        $this->numVariables = count($this->objective);
        $this->numConstraints = count($this->constraints);

        $this->initializeTableau();
    }

    private function parseObjective($objective)
    {
        return array_map('floatval', $objective);
    }

    private function parseConstraints($constraints)
    {
        $parsedConstraints = [];
        foreach ($constraints as $constraint) {
            $parsedConstraints[] = $this->parseConstraint($constraint);
        }
        return $parsedConstraints;
    }

    private function parseConstraint($constraint)
    {
        // Melhorar validação do formato da restrição
        $parts = preg_split('/(<=|>=|=)/', $constraint, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (count($parts) !== 3) {
            throw new \Exception("Formato de restrição inválido: $constraint");
        }

        // Processar coeficientes e valores
        $coefficients = array_map('floatval', preg_split('/[\s+\-*\/]/', trim($parts[0])));
        $operator = trim($parts[1]);
        $value = floatval(trim($parts[2]));

        return [
            'coefficients' => $coefficients,
            'operator' => $operator,
            'value' => $value,
        ];
    }

    private function initializeTableau()
    {
        $this->tableau = [];

        // Adicionar restrições ao tableau
        foreach ($this->constraints as $constraint) {
            $row = $constraint['coefficients'];
            $row[] = $constraint['value'];
            $this->tableau[] = $row;
        }

        // Adicionar a função objetivo (coeficientes negativos)
        $objectiveRow = array_map(fn($value) => -$value, $this->objective);
        $objectiveRow[] = 0;
        $this->tableau[] = $objectiveRow;
    }

    public function solve()
    {
        $iterations = 0;

        while ($this->hasNegativeCoefficientInObjective()) {
            if (++$iterations > 1000) {
                throw new \Exception("Número máximo de iterações atingido.");
            }

            $pivotColumn = $this->selectPivotColumn();
            $pivotRow = $this->selectPivotRow($pivotColumn);
            $this->performPivotOperation($pivotRow, $pivotColumn);
        }

        return $this->getSolution();
    }
}
