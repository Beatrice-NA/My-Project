<?php

namespace PHPSimplex;

class Simplex
{
    private $objective;  // Função objetivo
    private $constraints; // Restrições
    private $tableau;    // Tableau do Simplex
    private $numVariables;
    private $numConstraints;

    public function __construct(array $objective, array $constraints)
    {
        $this->objective = $this->parseObjective($objective);
        $this->constraints = $this->parseConstraints($constraints);

        $this->numVariables = count($this->objective);
        $this->numConstraints = count($this->constraints);

        $this->initializeTableau();
    }

    private function parseObjective(array $objective)
    {
        return array_map('floatval', $objective);
    }

    private function parseConstraints(array $constraints)
    {
        $parsedConstraints = [];
        foreach ($constraints as $constraint) {
            $parsedConstraints[] = $this->parseConstraint($constraint);
        }
        return $parsedConstraints;
    }

    private function parseConstraint($constraint)
    {
        // Dividir a restrição em coeficientes e valores
        $parts = preg_split('/(<=|>=|=)/', $constraint, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (count($parts) !== 3) {
            throw new \Exception("Formato de restrição inválido: $constraint");
        }

        $coefficients = array_map('floatval', preg_split('/[+\-]/', trim($parts[0])));
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
            $row[] = $constraint['value']; // Valor da restrição
            $this->tableau[] = $row;
        }

        // Adicionar a função objetivo (com coeficientes negativos)
        $objectiveRow = array_map(function ($value) {
            return -$value;
        }, $this->objective);
        $objectiveRow[] = 0; // Valor da função objetivo (Z)
        $this->tableau[] = $objectiveRow;
    }

    public function solve()
    {
        $iterations = 0;

        while ($this->hasNegativeCoefficientInObjective()) {
            if ($iterations++ > 1000) {
                throw new \Exception("Número máximo de iterações atingido.");
            }

            $pivotColumn = $this->selectPivotColumn();
            $pivotRow = $this->selectPivotRow($pivotColumn);
            $this->performPivotOperation($pivotRow, $pivotColumn);
        }

        return $this->getSolution();
    }

    private function hasNegativeCoefficientInObjective()
    {
        $objectiveRow = end($this->tableau);
        foreach ($objectiveRow as $value) {
            if ($value < 0) {
                return true;
            }
        }
        return false;
    }

    private function selectPivotColumn()
    {
        $objectiveRow = end($this->tableau);
        $minValue = min($objectiveRow);
        return array_search($minValue, $objectiveRow);
    }

    private function selectPivotRow($pivotColumn)
    {
        $ratios = [];
        foreach ($this->tableau as $index => $row) {
            if ($row[$pivotColumn] > 0) {
                $ratios[$index] = $row[count($row) - 1] / $row[$pivotColumn];
            }
        }

        if (empty($ratios)) {
            throw new \Exception("Problema não possui solução viável.");
        }

        return array_search(min($ratios), $ratios);
    }

    private function performPivotOperation($pivotRow, $pivotColumn)
    {
        $pivotValue = $this->tableau[$pivotRow][$pivotColumn];

        // Dividir a linha do pivô pelo valor do pivô
        foreach ($this->tableau[$pivotRow] as $index => &$value) {
            $value /= $pivotValue;
        }

        // Ajustar as outras linhas do tableau
        foreach ($this->tableau as $rowIndex => &$row) {
            if ($rowIndex !== $pivotRow) {
                $factor = $row[$pivotColumn];
                foreach ($row as $index => &$value) {
                    $value -= $factor * $this->tableau[$pivotRow][$index];
                }
            }
        }
    }

    private function getSolution()
    {
        $solution = [];
        foreach ($this->tableau as $index => $row) {
            if ($index < $this->numConstraints) {
                $solution[] = $row[count($row) - 1];
            }
        }

        return [
            'solution' => $solution,
            'optimalValue' => end($this->tableau)[count(end($this->tableau)) - 1],
        ];
    }
}
