<?php

namespace app\models;

use app\models\Paper;
use DateTime;
use Exception;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\Vector;
use yii\base\Model;
use Yii;
use yii\base\ErrorException;
use InvalidArgumentException;
use logic\solveLinearSystem;
use PHPSimplex\Simplex; 


class ConsultaModel extends Model
{
    
    public $nome;
    public $inicio;
    public $final;
    public $states_number;
    public $qtde_obs;
    public $periodo;
    public $metric;
    public $base;
    public $initial_year;
    public $qtde_up_down_constants;
    public $actions;
    public $paper;
    public $predictVector;
    private $predictionVector;
    private $predictedArray;
    public $optimalSolution;
    public $solution;
    public $three_state_matrix1;
    public $matrix;
    public $currentVector;
    public $three_state_vector;
    public $nextVector;
    private $w;
    private $initialVector;
    private $resultVector1;
    private $resultVector2;
    private $simplex;
    private $objective ;  
    private $constraints; 
    private $tableau;
    private $Variables;
    public $bounds;
    
    
    
    public function rules()
    {
        return [
            [['nome', 'inicio', 'final'], 'required'],
            [['states_number', 'periodo'], 'integer'],
            [['metric'], 'string'],
            [['inicio', 'final'], 'date', 'format' => 'dd/mm/yyyy'],
            ['qtde_obs', 'integer'],
            ['qtde_up_down_constants','integer'],
            ['actions','each','rule' => ['string']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'nome' => 'Ação',
            'inicio' => 'Data Inicial',
            'actions' => 'Ações',
            'final' => 'Data Final',
            'states_number' => 'Quantidade de intervalos',
            'metric' => 'Métrica',
            'qtde_obs' => 'Quantidade de observações',
            'qtde_up_down_constants' => 'Quantidade de subidas/descidas constantes antes da previsão'
        ];
    }

    public function getData($stock, $start, $final)
    {
        return Paper::find()->orderBy('date')->where(
            ['=', 'codneg', $stock],
            ['=', 'tpmerc', '010']
        )->andWhere(['>=', 'date', $start])->andWhere(['<=', 'date', $final])->addOrderBy('date ASC')->all();
    }

    public function getDataByMonth($stock, $start)
    {
        return Paper::find()->orderBy('date')->where(
            ['=', 'codneg', $stock],
            ['=', 'tpmerc', '010']
        )->andWhere(['>=', 'date', $start])->addOrderBy('date ASC')->one();
    }

    public function definePremin($cursor_by_price)
    {
        $premin = $cursor_by_price[0]; //array com o menor preço do conjunto

        foreach ($cursor_by_price as $cursor) {
            if ($cursor['preult'] < $premin['preult'])
                $premin = $cursor;
        }

        return $premin;
    }

    public function definePremax($cursor_by_price)
    {
        $premax = $cursor_by_price[0]; //array com o maior preço do conjunto

        foreach ($cursor_by_price as $cursor) {
            if ($cursor['preult'] > $premax['preult'])
                $premax = $cursor;
        }

        return $premax;
    }

    public static function getState($price, $premin, $interval, $states_number)
    {
        for ($i = 0; $i < $states_number; $i++) {
            if ($price >= ($premin + ($interval * $i)) && $price - 0.00001 <= ($premin + ($interval * ($i + 1)))) {
                return $i + 1;
            }
        }

        return 0;
    }
    
    public static function getThreeState($price, $price_before)
    {
        if ($price > $price_before) {
            return 1;
        } elseif ($price < $price_before) {
            return 3;
        } else
            return 2;
    }

    public function getSteadyState($matrix)
    {

        $stop_loop = 0;
        $R = $matrix->multiply($matrix);
        $tried_values = 1;
        $contador = 2;

        if ($this->isErgodicAndisIrreducible($matrix) === 0) {
            return 0;
        }

        if ($this->haveOnlyOneLimiting($matrix) === 0) {
            return 0;
        }

        if ($this->validateMatrix($R, 4) === 0) {
            while ($stop_loop != 1) {
                for ($i = 1; $i <= $tried_values; $i++) {
                    $R = $R->multiply($matrix);
                }

                if ($this->isErgodicAndisIrreducible($matrix) === 0) {
                    return 0;
                }

                if ($this->haveOnlyOneLimiting($matrix) === 0) {
                    return 0;
                }

                $contador += $tried_values;

                if ($this->validateMatrix($R, 4) === 0) {
                    $tried_values += 1;
                } else {
                    $stop_loop = 1;
                }
            }
        }
        return [$this->validateMatrix($R, 4), $contador];
    }

    private function validateMatrix($Matrix, $decimal_places)
    {
        if (number_format($Matrix[0][0], $decimal_places, '.', ' ') == number_format($Matrix[1][0], $decimal_places, '.', ' ') && number_format($Matrix[0][0], $decimal_places, '.', ' ') == number_format($Matrix[2][0], $decimal_places, '.', ' ') && number_format($Matrix[1][0], $decimal_places, '.', ' ') == number_format($Matrix[2][0], $decimal_places, '.', ' ')) {
            if (number_format($Matrix[0][1], $decimal_places, '.', ' ') == number_format($Matrix[1][1], $decimal_places, '.', ' ') && number_format($Matrix[0][1], $decimal_places, '.', ' ') == number_format($Matrix[2][1], $decimal_places, '.', ' ') && number_format($Matrix[1][1], $decimal_places, '.', ' ') == number_format($Matrix[2][1], $decimal_places, '.', ' ')) {
                if (number_format($Matrix[0][2], $decimal_places, '.', ' ') == number_format($Matrix[1][2], $decimal_places, '.', ' ') && number_format($Matrix[0][2], $decimal_places, '.', ' ') == number_format($Matrix[2][2], $decimal_places, '.', ' ') && number_format($Matrix[1][2], $decimal_places, '.', ' ') == number_format($Matrix[2][2], $decimal_places, '.', ' ')) {
                    $pi_one = number_format($Matrix[0][0], $decimal_places, '.', ' ');
                    $pi_two = number_format($Matrix[0][1], $decimal_places, '.', ' ');
                    $pi_three = number_format($Matrix[0][2], $decimal_places, '.', ' ');

                    $vector_stable = new Vector([$pi_one, $pi_two, $pi_three]);
                    return $vector_stable;
                }
            }
        }

        return 0;
    }

    private function isErgodicAndisIrreducible($matrix)
    {
        if ($matrix[0][0] == 0 && $matrix[1][1] == 0 && $matrix[2][2] == 0) {
            if ($matrix[0][2] == 0 && $matrix[1][1] == 0 && $matrix[2][0] == 0) {
                return 0;
            }
        }

        return 1;
    }

    private function haveOnlyOneLimiting($matrix)
    {
        if ($matrix[0][2] == 0 && $matrix[1][2] == 0 && $matrix[2][0] == 0 && $matrix[2][1] == 0) {
            return 0;
        }
        return 1;
    }

    //Constroi a matriz de transição a partir do conjunto de treinamento
    public function transitionMatrix($paper, $states, $states_number, $state_type)
    {
        
        $paper = [
            ['t_state' => 1],
            ['t_state' => 2],
            ['t_state' => 3]
       ];

        $matrix = [[]];

         // Contagem de transições e saídas de cada estado
       for ($i = 0; $i < $states_number; $i++)
            for ($j = 0; $j < $states_number; $j++)
                $matrix[$j][$i] = 0;
        
        //calculando a quantidade de elementos em cada transição da matriz
        for ($i = 0; $i < count($paper) - 1; $i++) {
            $j = $i + 1;
          $matrix[$paper[$i][$state_type] - 1][$paper[$j][$state_type] - 1] += 1;
        }   

        // Contagem do último valor do conjunto de treinamento
        $matrix[$paper[count($paper) - 1][$state_type] - 1][$paper[count($paper) - 1][$state_type] - 1] += 1;

        //construção da matriz de transição $states contem a quantidade de elementos total em cada estado
        for ($i = 0; $i < $states_number; $i++) {
            for ($j = 0; $j < $states_number; $j++) {
                 if ($states[$i] == 0){
                    $matrix[$i][$j] = 0;
            }else{
                $matrix[$i][$j] /= $states[$i];
            }
            
         }
                
        }
    
        return $matrix;
    }
        
    public function firstPassageTime($matrix)
    {

        // Retorna o vetor com os valores que a matriz converge
        $steady_states = $this->getSteadyState($matrix);
        


        try {
            // Up to up
            $m0_0 = 1 / $steady_states[0][0];
        } catch (ErrorException $err) {
            Yii::warning("Divisão por zero");
            return 0;
        }

        try {
            //Same to same
            $m1_1 = 1 / $steady_states[0][1];
        } catch (ErrorException $err) {
            Yii::warning("Divisão por zero");
            return 0;
        }

        try {
            //Down to down
            $m2_2 = 1 / $steady_states[0][2];
        } catch (ErrorException $err) {
            Yii::warning("Divisão por zero");
            return 0;
        }

        // Up to same
        //$m0_1 = 1 + $matrix[0][0] . 'm0_1' . $matrix[0][2] . 'm2_1';

        //Up to Up
        //$m0_0 = 1 + $matrix[0][1] . 'm1_0 . $matrix[0][2] . 'm2_0';
        //same to same
        //$m1_1 = 1 + $matrix[1][0] . 'm0_1 . $matrix[1][2] . 'm2_1';
        //down to down
        //$m2_2 = 1 + $matrix[1][0] . 'm0_2 . $matrix[1][1] . 'm1_2';

        // Up to down
        //$m0_2 = 1 + $matrix[0][0] . 'm0_2' . $matrix[0][1] . 'm1_2';


        // Same to up
        //$m1_0 = 1 + $matrix[1][1] . 'm1_0' . $matrix[1][2] . 'm2_0';

        // Same to down
        //$m1_2 = 1 + $matrix[1][0] . 'm0_2' . $matrix[1][1] . 'm1_2';


        // Down to up
        //$m2_0 = 1 + $matrix[2][1] . 'm1_0' . $matrix[2][2] . 'm2_0';

        // Down to same
        //$m2_1 = 1 + $matrix[2][0] . 'm0_1' . $matrix[2][2] . 'm2_1';


        /**
         * 
         * Formando os sistemas lineares
         * 
         */

        // Up to same
        //$m0_1 = 1 + $matrix[0][0] . 'm0_1' . $matrix[0][2] . 'm2_1';

        // Down to same
        //$m2_1 = 1 + $matrix[2][0] . 'm0_1' . $matrix[2][2] . 'm2_1';

        //same to same
        //$m1_1 = 1 + $matrix[1][0] . 'm0_1 . $matrix[1][2] . 'm2_1';



        // Up to down
        //$m0_2 = 1 + $matrix[0][0] . 'm0_2' . $matrix[0][1] . 'm1_2';

        // Same to down
        //$m1_2 = 1 + $matrix[1][0] . 'm0_2' . $matrix[1][1] . 'm1_2';


        // Same to up
        //$m1_0 = 1 + $matrix[1][1] . 'm1_0' . $matrix[1][2] . 'm2_0';

        // Down to up
        //$m2_0 = 1 + $matrix[2][1] . 'm1_0' . $matrix[2][2] . 'm2_0';


        /**
         * 
         * Criando matriz de cada sistema para resolução
         * 
         */

        // Up to same
        // -1 = $matrix[0][0] - 1$m0_1 . 'm0_1' . $matrix[0][2] . 'm2_1';

        // Down to same
        // -1 = $matrix[2][0] . 'm0_1' . $matrix[2][2] -1$m2_1 . 'm2_1';


        // Up to down
        // -1 = $matrix[0][0] -1$m0_2 . 'm0_2' . $matrix[0][1] . 'm1_2';

        // Same to down
        // -1 = $matrix[1][0] . 'm0_2' . $matrix[1][1] -1$m1_2 . 'm1_2';


        // Same to up
        // -1 = $matrix[1][1] -1$m1_0 . 'm1_0' . $matrix[1][2] . 'm2_0';

        // Down to up
        // -1 = $matrix[2][1] . 'm1_0' . $matrix[2][2] - 1$m2_0 . 'm2_0';


        $matrix_0 = [
            [($matrix[0][0] - 1), $matrix[0][2]],
            [$matrix[2][0], ($matrix[2][2] - 1)]
        ];

        $matrix_1 = [
            [($matrix[0][0] - 1),  $matrix[0][1]],
            [$matrix[1][0], ($matrix[1][1] - 1)]
        ];

        $matrix_2 = [
            [($matrix[1][1] - 1), $matrix[1][2]],
            [$matrix[2][1], ($matrix[2][2] - 1)]
        ];

        $matrix_0 = MatrixFactory::create($matrix_0);
        $matrix_1 = MatrixFactory::create($matrix_1);
        $matrix_2 = MatrixFactory::create($matrix_2);

        $vector_result = [-1, -1];

        $result_0 = $matrix_0->solve($vector_result);
        $result_1 = $matrix_1->solve($vector_result);
        $result_2 = $matrix_2->solve($vector_result);

        $matrix_result = [
            [$m0_0, $result_0[0], $result_0[1]],
            [$result_1[0], $m1_1, $result_1[1]],
            [$result_2[0], $result_2[1], $m2_2],
        ];

        return $matrix_result;
    }

  
    //Constroi o vetor de previsão
    public function predictVector($matrix, $paper, $states_number, $state_type)
  {
    if (!is_array($matrix) || !is_array($matrix[0])) {
        throw new \InvalidArgumentException('A matriz deve ser bidimensional.');
    }
       $matrix = MatrixFactory::create($matrix);
       
        // Inicializa o vetor de previsão corretamente
        $vector = [[]];
      
        for ($i = 0; $i < $states_number; $i++)
           $vector[0][$i] = 0;

        //declaração do vetor de estado inicial a partir do ultimo dia do conjunto de treinamento
        $vector[0][$paper[count($paper) - 1][$state_type] - 1] = 1;
        $vector = MatrixFactory::create($vector);

        $vector = $vector->multiply($matrix); //multiplicando

        return $vector;
    }
   

    public function getInterval($premin, $interval, $i)
    {
        $min = $premin + ($interval * $i);
        $max = $premin + ($interval * ($i + 1));

        return [$min, $max];
    }

    public function chartData($next, $intervals, $client, $t_datas)
    {
        //Dados para construção do gráfico
        $fechamentoData = array();
        $infData = array();
        $supData = array();
        $avgData = array();
        $actionsData = array();
        $cashData = array();
        $t_data = array();
        $tendencia = 0;

        //Dados dos preço de fechamento para o gráfico
        foreach ($next as $date) {
            $formattedDate = intval(($date['date']->toDateTime())->format('U') . '000');
            array_push($fechamentoData, [$formattedDate, $date['preult']]);
        }

        //Dados dos preços dos intervalos para o gráfico
        foreach ($intervals as $i => $interval) {
            $formattedDate = intval(($next[$i]['date']->toDateTime())->format('U') . '000');
            array_push($infData, [$formattedDate, $interval[0]]);
            array_push($supData, [$formattedDate, $interval[1]]);
        }

        //Dados do valor médio para o gráfico
        foreach ($intervals as $i => $interval) {
            $formattedDate = intval(($next[$i]['date']->toDateTime())->format('U') . '000');
            array_push($avgData, [$formattedDate, ($interval[0] + $interval[1]) / 2]);
        }

        foreach ($t_datas as $i => $t) {
            $formattedDate = intval(($next[$i]['date']->toDateTime())->format('U') . '000');
            array_push($t_data, [$formattedDate, $t]);
        }

        for ($i = 0; $i < count($avgData) - 1; $i++) {
            $avgAux = $avgData[$i + 1][1] - $avgData[$i][1];
            $fechamentoAux = $fechamentoData[$i + 1][1] - $fechamentoData[$i][1];

            if ($avgAux > 0 && $fechamentoAux > 0)
                $tendencia++;

            else if ($avgAux < 0 && $fechamentoAux < 0)
                $tendencia++;

            else if ($avgAux == 0 && $fechamentoAux == 0)
                $tendencia++;
        }

        foreach ($client as $data) {
            $formattedDate = intval(($data['date']->toDateTime())->format('U') . '000');
            array_push($actionsData, [$formattedDate, $data['client']['actions']]);
            array_push($cashData, [$formattedDate, $data['client']['cash']]);
        }

        return ([
            'fechamentoData' => $fechamentoData,
            'infData' => $infData,
            'supData' => $supData,
            'avgData' => $avgData,
            'tendencia' => $tendencia,
            'cashData' => $cashData,
            'actionsData' => $actionsData,
            't_data' => $t_data
        ]);
    }

    public function chartDataThreeStates($next, $client, $t_datas)
    {
        //Dados para construção do gráfico
        $fechamentoData = array();
        $infData = array();
        $supData = array();
        $avgData = array();
        $actionsData = array();
        $cashData = array();
        $t_data = array();
        $tendencia = 0;

        //Dados dos preço de fechamento para o gráfico
        foreach ($next as $date) {
            $formattedDate = intval(($date['date']->toDateTime())->format('U') . '000');
            array_push($fechamentoData, [$formattedDate, $date['preult']]);
        }


        foreach ($t_datas as $i => $t) {
            $formattedDate = intval(($next[$i]['date']->toDateTime())->format('U') . '000');
            array_push($t_data, [$formattedDate, $t]);
        }

        for ($i = 0; $i < count($avgData) - 1; $i++) {
            $avgAux = $avgData[$i + 1][1] - $avgData[$i][1];
            $fechamentoAux = $fechamentoData[$i + 1][1] - $fechamentoData[$i][1];

            if ($avgAux > 0 && $fechamentoAux > 0)
                $tendencia++;

            else if ($avgAux < 0 && $fechamentoAux < 0)
                $tendencia++;

            else if ($avgAux == 0 && $fechamentoAux == 0)
                $tendencia++;
        }

        foreach ($client as $data) {
            $formattedDate = intval(($data['date']->toDateTime())->format('U') . '000');
            array_push($actionsData, [$formattedDate, $data['client']['actions']]);
            array_push($cashData, [$formattedDate, $data['client']['cash']]);
        }

        return ([
            'fechamentoData' => $fechamentoData,
            'infData' => $infData,
            'supData' => $supData,
            'avgData' => $avgData,
            'tendencia' => $tendencia,
            'cashData' => $cashData,
            'actionsData' => $actionsData,
            't_data' => $t_data
        ]);
    }

    public function handleBuy($client, $price)
    {
        if ($client['cash'] >= $price) {
            $qtdBuy = floor($client['cash'] / $price);
            // $client['cash'] = 0;
            $client['cash'] -= ($price * $qtdBuy);
            $client['actions'] += $qtdBuy;
            return $client;
        } else {
            return $client;
        }
    }

    public function handleSell($client, $price)
    {
        if ($client['actions'] > 0) {
            $client['cash'] += ($client['actions'] * $price);
            $client['actions'] = 0;
            return $client;
        } else {
            return $client;
        }
    }

    public static function handleAverages($cursors, $base)
    {
        $cursors_avg = [];
        $limit = $base - 1;

        foreach ($cursors as $index => $cursor) { //Criação do array com médias móveis
            $acc = 0;

            if ($index >= $limit) {
                for ($i = 0; $i <= $limit; $i++) {
                    $acc += $cursors[$limit - $i]['preult'];
                }

                array_push($cursors_avg, $cursor);
                $cursors_avg[$index - $limit]['preult'] = $cursors[$index]['preult'] - ($acc / $limit + 1);
            }
        }

        return $cursors_avg;
    }

    public function returnHitsAndErrorsfromFile($file, $header = True)
    {
        //Verifica se o arquivo existe
        if (!file_exists($file)) {
            return 0;
        }

        // Abre o arquivo
        $csv = fopen($file, 'r');
        $data = [];

        // Cabeçalho dos dados
        $header_data = $header ? fgetcsv($csv, 0, ',') : [];

        // Lê todas as linhas do arquivo
        while ($line = fgetcsv($csv, 0, ',')) {
            $data[] = $line[11];
        }

        fclose($csv);

        return $data;
    }

    public function readFile($file, $header = True, $separeted_by = ',')
    {
        //Verifica se o arquivo existe
        if (!file_exists($file)) {
            return 0;
        }

        $total_hits = 0;
        $total_errors = 0;
        // Abre o arquivo
        $csv = fopen($file, 'r');

        // Cabeçalho dos dados
        $header_data = $header ? fgetcsv($csv, 0, $separeted_by) : [];

        // Lê todas as linhas do arquivo
        while ($line = fgetcsv($csv, 0, $separeted_by)) {
            if ($line[11] == 1) {
                $total_hits++;
            } else if ($line[11] == 0) {
                $total_errors++;
            }
        }
        fclose($csv);

        return [$total_hits, $total_errors];
    }

    private function stand_deviation($arr)
    {
        $num_of_elements = count($arr);

        $variance = 0.0;

        // calculating mean using array_sum() method
        $average = array_sum($arr) / $num_of_elements;

        foreach ($arr as $i) {
            // sum of squares of differences between 
            // all numbers and means.
            $variance += pow(($i - $average), 2);
        }

        return (float)sqrt($variance / $num_of_elements);
    }

    public function statsFromFile($file)
    {
        //Verifica se o arquivo existe
        if (!file_exists($file)) {
            return 0;
        }

        $total = 0;
        $total_valid_actions = 0;
        $means = [];
        // Abre o arquivo
        $csv = fopen($file, 'r');

        // Lê todas as linhas do arquivo
        while ($line = fgetcsv($csv, 0, ",")) {
            if (floatval($line[1]) != 0) {
                $total += floatval($line[1]);
                $total_valid_actions++;
                $means[] = $line[1];
            }
            continue;
        }

        fclose($csv);

        $mean = $total / $total_valid_actions;
        $sd = $this->stand_deviation($means);

        return [$mean, $sd, $total_valid_actions];
    }

    public function writeInFile($file, $data)
    {
        $csv = fopen($file, 'a');
        fputcsv($csv, $data);
        fclose($csv);

        return 1;
    }

    public function createFile($filename, $separeted_by = ',', $headers)
    {
        $csv = fopen($filename, 'w');
        fputcsv($csv, $headers, $separeted_by);

        fclose($csv);
    }

    public function getActionAfterIterations($action_name, $first_day, $string_format_final_date, $iteractions)
    {
        $final = \DateTime::createFromFormat('d/m/YH:i:s', $string_format_final_date . '24:00:00')->modify('+60 days');
        $last_day = Paper::toIsoDate($final->format('U'));
        $one_day_after_iteraction = $iteractions + 1;
        $two_days_after_iteraction = $iteractions + 2;

        $interval = Paper::find()->orderBy('date')->where(
            ['=', 'codneg', $action_name],
            ['=', 'tpmerc', '010']
        )->andWhere(['>=', 'date', $first_day])->andWhere(['<=', 'date', $last_day])->addOrderBy('date ASC')->all();

        try {
            $date = $interval[$iteractions];
            $one_day_after = $interval[$one_day_after_iteraction];
            $two_days_after = $interval[$two_days_after_iteraction];

            return [$date, $one_day_after, $two_days_after];
        } catch (\Throwable $th) {
            return null;
        }
    }

    public function hits($price_now, $price_after, $up, $down)
    {
        if ($up > $down) {
            $diff = $up - $down;
            if ($diff > 0.05) {
                if ($price_after > $price_now) {
                    return 1;
                } else {
                    return 0;
                }
            } else {
                return -1;
            }
        } else if ($up == $down) {
            return -1;
        } else {
            $diff = $down - $up;
            if ($diff > 0.05) {
                if ($price_after < $price_now) {
                    return 1;
                } else {
                    return 0;
                }
            } else {
                return -1;
            }
        }
    }

    public function forecastHeuristicBeforeInflection($before_forecast, $current_forecast, $real_value)
    {
        /* 
            Compara os tres estados atual, com o 3 estados anterior se forem iguais verifico se a previsão real anterior (next_day['t_state'] é menor ou igual a 2, se for ele muda para 3 se não for ele muda para 1
        */
        if ($before_forecast == $current_forecast) {
            if ($real_value <= 2) {
                return 3;
            } else {
                return 1;
            }
        } else {
            return $current_forecast;
        }
    }

    public function forecastHeuristicAfterInflection($before_forecast, $current_forecast, $real_value)
    {
        // Compara as previsões de 3 estados anterior e a atual
        if ($real_value != $current_forecast) {
            return $real_value;
        } else {
            return $current_forecast;
        }
    }

    public function searchProbInArrayReturnGreaterProb($arr_three_states_vector)
    {
        if ($arr_three_states_vector[0] > $arr_three_states_vector[2]) {
            return 0;
        } else if ($arr_three_states_vector[2] > $arr_three_states_vector[0]) {
            return 2;
        } else if ($arr_three_states_vector[0] == $arr_three_states_vector[2]) {
            return 0;
        }
    }

    public function verifyContinuosGrowthIsZero($continuos_growth)
    {
        if ($continuos_growth == 0) {
            return true;
        } else {
            return false;
        }
    }

    public function compareVerifyWithPrevision($real_value, $prevision)
    {
        if ($real_value == $prevision) {
            return 1;
        } else {
            return 0;
        }
    }
    public function verifyHitThreeTimes($times)
    {
        if ($times == 3) {
            return [true,3];
        }
        return [false,$times];
    }

    //Constroi a matriz de transição a partir do conjunto de treinamento
    public function transitionMatrix1($paper, $states, $states_number, $state_type) 
    {
        // Inicializa a matriz de transição com zeros
        $three_state_matrix1 = array_fill(0, $states_number, array_fill(0, $states_number, 0));
        
        // Inicializa os contadores de transições para cada estado
        $state_counts = array_fill(0, $states_number, 0);
    
        // Calcula as transições
        for ($i = 0; $i < count($paper) - 1; $i++) {
            $current_state = $paper[$i][$state_type] - 1;
            $next_state = $paper[$i + 1][$state_type] - 1;
    
            // Verifica se os estados estão dentro dos limites
            if ($current_state >= 0 && $current_state < $states_number &&
                $next_state >= 0 && $next_state < $states_number) {
                
                // Incrementa a contagem de transição
                $three_state_matrix1[$current_state][$next_state] += 1;
                $state_counts[$current_state] += 1;
            }
        }
    
        // Trata o último estado (auto-transição)
        $last_state = $paper[count($paper) - 1][$state_type] - 1;
        if ($last_state >= 0 && $last_state < $states_number) {
            $three_state_matrix1[$last_state][$last_state] += 1;
            $state_counts[$last_state] += 1;
        }
    
        // Normaliza a matriz de transição
        for ($i = 0; $i < $states_number; $i++) {
            if ($state_counts[$i] > 0) {
                // Normaliza apenas se houver transições
                for ($j = 0; $j < $states_number; $j++) {
                    $three_state_matrix1[$i][$j] /= $state_counts[$i];
                }
            } else {
                // Preenche linhas sem transições com probabilidades uniformes
                for ($j = 0; $j < $states_number; $j++) {
                    $three_state_matrix1[$i][$j] = 1 / $states_number;
                }
            }
        }
    
        return $three_state_matrix1;
    }
    

    //Constroi a matriz de transição de segunda ordem a partir do conjunto de treinamento
    public function transitionMatrixSegundaOrdem($paper, $states, $states_number, $state_type)
    {
        // Inicializa a matriz de transição com zeros
        $matrixSegundaOrdem = array_fill(0, $states_number, array_fill(0, $states_number, 0));
    
        // Calcula as transições de segunda ordem
        for ($i = 0; $i < count($paper) - 2; $i++) {
            $current_state = $paper[$i][$state_type] - 1;
            $next_state = $paper[$i + 2][$state_type] - 1;
    
            if ($current_state >= 0 && $current_state < $states_number &&
                $next_state >= 0 && $next_state < $states_number) {
                $matrixSegundaOrdem[$current_state][$next_state] += 1;
            } else {
                Yii::warning("Índices inválidos para \$current_state ou \$next_state: \$i=$i", __METHOD__);
            }
        }
    
        // Contagem dos últimos estados
        $last_state = $paper[count($paper) - 1][$state_type] - 1;
        $penultimate_state = $paper[count($paper) - 2][$state_type] - 1;
    
        if ($last_state >= 0 && $last_state < $states_number) {
            $matrixSegundaOrdem[$last_state][$last_state] += 1;
        }
    
        if ($penultimate_state >= 0 && $penultimate_state < $states_number) {
            $matrixSegundaOrdem[$penultimate_state][$penultimate_state] += 1;
        }
    
        // Normaliza a matriz de transição
        for ($i = 0; $i < $states_number; $i++) {
            $rowSum = array_sum($matrixSegundaOrdem[$i]);
            if ($rowSum > 0) {
                for ($j = 0; $j < $states_number; $j++) {
                    $matrixSegundaOrdem[$i][$j] /= $rowSum;
                }
            }
        }
    
        return $matrixSegundaOrdem;
    }
    

   public function getMatrix()
    {
        // Exemplo de teste
        $matrix = [
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
        ];

        // Verifica se é uma matriz válida
        if (!is_array($matrix) || count($matrix) === 0) {
            Yii::error("Matriz inválida gerada no método getMatrix: " . print_r($matrix, true));
            return [];
        }

        foreach ($matrix as $row) {
            if (!is_array($row)) {
                Yii::error("Matriz contém elementos que não são arrays: " . print_r($matrix, true));
                return [];
            }
        }

        return $matrix;
    }

   // Constroi o vetor inicial
   function calculateInitialVector($matrix) {  
    // Verificar se o argumento fornecido é uma matriz bidimensional
    if (!is_array($matrix) || !is_array($matrix[0])) {
        Yii::error("O argumento fornecido não é uma matriz bidimensional: " . print_r($matrix, true));
        throw new \yii\base\ErrorException("A matriz fornecida deve ser bidimensional.");
    }

    // Verificar se a matriz está vazia
    if (empty($matrix) || empty($matrix[0])) {
        Yii::error("A matriz fornecida está vazia: " . print_r($matrix, true));
        throw new \yii\base\ErrorException("A matriz fornecida está vazia.");
    }

    $total = 0;

    // Calcule a soma total da matriz e verifique se todos os elementos são numéricos
    foreach ($matrix as $row) {
        if (!is_array($row)) {
            Yii::error("Linha inválida na matriz: " . print_r($row, true));
            throw new \yii\base\ErrorException("Cada linha da matriz deve ser um array.");
        }

        foreach ($row as $value) {
            if (!is_numeric($value)) {
                Yii::error("Valor não numérico encontrado na matriz: " . print_r($value, true));
                throw new \yii\base\ErrorException("Todos os valores da matriz devem ser numéricos.");
            }
        }

        $total += array_sum($row);
    }

    // Verifique se o total é maior que 0 para evitar divisão por zero
    if ($total == 0) {
        Yii::error("A soma total da matriz é zero, impossibilitando a normalização.");
        throw new \yii\base\ErrorException("A soma total da matriz deve ser maior que zero.");
    }

    // Calcule o vetor inicial normalizando as somas das linhas pela soma total
    $initialVector = [];
    foreach ($matrix as $i => $row) {
        $rowSum = array_sum($row);
        $initialVector[$i] = $rowSum / $total;

        // Log para verificar os cálculos intermediários
        Yii::info("Linha $i: soma = $rowSum, total = $total, valor normalizado = " . $initialVector[$i]);
    }

    return $initialVector;
}


    // Define a função transposeVecto
    public static function transposeVector($initialVector) {
        if (!is_array($initialVector) || !isset($initialVector[0])) {
            throw new \yii\base\InvalidArgumentException("O vetor inicial deve ser um array unidimensional.");
        }
        
        $transposedVector = [];
        foreach ($initialVector as $key => $value) {
            $transposedVector[] = [$value];
        }
        return $transposedVector;
    }

    function multiplyMatrixByinitialVector($matrixSegundaOrdem, $initialVector) {
        // Inicializa o vetor de resultado
        $resultVector1 = [];
    
        // Realiza a multiplicação da matriz pelo vetor
        foreach ($matrixSegundaOrdem as $rowIndex => $row) {
            $sum = 0;
            foreach ($row as $colIndex => $value) {
                $sum += $value * $initialVector[$colIndex]; // Multiplica a célula pelo valor correspondente do vetor
            }
            $resultVector1[$rowIndex] = $sum; // Armazena o resultado para a linha correspondente
        }
        
        return $resultVector1;
    }
    
    
    function multiplyMatrixinitialVector($three_state_matrix1, $initialVector) {
        // Verifica se a matriz e o vetor são válidos
        if (!is_array($three_state_matrix1) || !is_array($initialVector)) {
            throw new InvalidArgumentException("Os argumentos fornecidos não são válidos: matriz e vetor devem ser arrays.");
        }
        
        // Verifica se as dimensões são compatíveis
        if (count($three_state_matrix1[0]) != count($initialVector)) {
            throw new Exception("A quantidade de colunas na matriz deve ser igual ao número de elementos no vetor.");
        }
    
        // Inicializa o vetor de resultado
        $resultVector2 = [];
    
        // Realiza a multiplicação
        foreach ($three_state_matrix1 as $rowIndex => $row) {
            $sum = 0;
            foreach ($row as $colIndex => $value) {
                $sum += $value * $initialVector[$colIndex]; // Multiplica cada elemento da linha pelo elemento correspondente do vetor
            }
            $resultVector2[$rowIndex] = $sum; // Armazena o resultado no índice correto
        }
    
        return $resultVector2;
    }

   
    public function prepareSimplexData() 
    {
        try {
            // Processar a função objetivo
            $objective = array_map('floatval', explode(',', $this->objective_function));
    
            // Processar as restrições
            $constraints = array_map(function ($line) {
                return array_map('floatval', preg_split('/,\s*/', $line));
            }, explode("\n", trim($this->constraints)));
    
            // Instanciar a classe Simplex
            $simplex = new Simplex($objective, $constraints);
    
            // Exibir o tableau inicial para depuração
            echo "Tableau Inicial:\n";
            var_dump($simplex->getTableau());
    
            // Resolver o problema
            $solution = $simplex->solve();
    
            // Depurar os dados intermediários
            var_dump([
                'objective' => $objective,
                'constraints' => $constraints,
                'tableau_inicial' => $simplex->getTableau(),
                'solution' => $solution,
            ]);
    
            // Retornar os dados processados
            return [
                'objective' => $objective,
                'constraints' => $constraints,
                'solution' => $solution,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    

//function solveOptimizationProblem($objectiveFunction, $numVariables) {
   // $variables = array_fill(0, $numVariables, 0); // Inicializa as variáveis lambda_i

    // Restrições:
   // $constraints = [
        // Cada λ_i ≥ 0
       // 'non_negative' => function($variables) {
           // foreach ($variables as $var) {
               // if ($var < 0) return false;
           // }
           // return true;
       // },
        // Soma de λ_i = 1
       // 'sum_to_one' => function($variables) {
           // return abs(array_sum($variables) - 1) < 1e-6;
       // }
   // ];

   // $bestLambdas = null;
   // $bestObjectiveValue = PHP_FLOAT_MIN;

   // $steps = 100;
    //for ($i = 0; $i <= $steps; $i++) {
       // $variables = array_fill(0, $numVariables, 0);
       // $variables[0] = $i / $steps; // Assume λ_1 varia entre 0 e 1
       // $variables[1] = 1 - $variables[0]; // λ_2 é complementar

       // if (!$constraints['non_negative']($variables) || !$constraints['sum_to_one']($variables)) {
           // continue; // Ignora soluções inválidas
       // }

       // $objectiveValue = $objectiveFunction($variables);

       // if ($objectiveValue > $bestObjectiveValue) {
           // $bestObjectiveValue = $objectiveValue;
           // $bestLambdas = $variables;
       // }
   // }

   // return $bestLambdas;
//}

private function normalizeVector($vector)
{
    $sum = array_sum($vector);
    if ($sum == 0) {
        throw new \InvalidArgumentException("A soma dos elementos do vetor não pode ser zero.");
    }

    return array_map(function ($value) use ($sum) {
        return $value / $sum;
    }, $vector);
}

    //public function calculateW($resultVector1, $resultVector2, $initialVector, $bestLambdas)
//{
    // Exemplos de entrada
   // $resultVector1 = [0.3, 0.2, 0.1];
   // $resultVector2 = [0.4, 0.3, 0.2];
   // $initialVector = [0.5, 0.4, 0.3];
    //$bestLambdas = [0.0, 1.0];

    // Verificação de entrada
   // if (empty($resultVector1) || empty($resultVector2) || empty($initialVector) || empty($bestLambdas)) {
       // throw new \InvalidArgumentException("Nenhum dos vetores de entrada pode estar vazio.");
   // }

   // $m = count($initialVector); // Número de restrições
   // $n = count($bestLambdas);   // Número de variáveis lambda

    // Validação de consistência
   // if (count($resultVector1) !== $m || count($resultVector2) !== $m) {
       // throw new \InvalidArgumentException("O tamanho de resultVector1 e resultVector2 deve ser igual ao número de restrições.");
   // }
   // if ($n < 2) {
        //throw new \InvalidArgumentException("O vetor bestLambdas deve conter pelo menos dois valores (λ1 e λ2).");
   // }

    // Inicializar o maior valor de W
   // $maxW = 0;

    // Construção das restrições
   // for ($i = 0; $i < $m; $i++) {
        // Inicializar as restrições para cada linha
       // $lhsPositive = $initialVector[$i]; // Atribui o valor do vetor inicial na posição $i à variável Left-Hand Side for positive
       // $lhsNegative = -$initialVector[$i]; // Atribui o valor negativo do vetor inicial na posição $i à variável Left-Hand Side negative

        // Aplicação dos valores de lambda para calcular
       // for ($j = 0; $j < $n; $j++) {
           // $lhsPositive -= $resultVector1[$i] * $bestLambdas[$j];
           // $lhsNegative += $resultVector2[$i] * $bestLambdas[$j];
      //  }
        // Determinar o maior valor para W considerando as restrições
       // $wCandidate = max(0, max($lhsPositive, $lhsNegative));

        // Atualizar o maior valor encontrado
       // $maxW = max($maxW, $wCandidate);
   // }

    // Retornar o maior valor calculado para W
   // return $maxW;
//}

//public function calculateOptimalSolution($bestLambdas, $W_star)
//{
   
    // Verifica se $bestLambdas é válido
    //if (!is_array($bestLambdas) || count($bestLambdas) < 2) {
       // throw new InvalidArgumentException("O array \$bestLambdas deve conter pelo menos dois valores.");
    //}

    // Definindo os melhores valores de λ
   // $lambda1_star = $bestLambdas[0]; // Primeiro valor de λ
    //$lambda2_star = $bestLambdas[1]; // Segundo valor de λ

    // Verifica se W* é válido
   // if (!is_numeric($W_star)) {
      //  throw new InvalidArgumentException("W* deve ser um número.");
   // }

    // A solução ótima será composta pelos valores das variáveis λ1*, λ2* e W*
    //$optimalSolution = [
       // 'lambda1_star' => $lambda1_star,
       // 'lambda2_star' => $lambda2_star,
       // 'W_star' => $W_star,
    //];

    // Retornar a solução ótima
   // return $optimalSolution;
//}
    
    //Constroi o vetor de previsão
    public function PredictionVector($matrix, $paper, $states_number, $state_type)  
{
    // Exemplo de entrada para $paper
    $paper = [
        ['state_type' => 1],
        ['state_type' => 2],
        ['state_type' => 3], // Último estado é 3
    ];
    
    $states_number = 3; // Número de estados
    $state_type = 'state_type';

    // Converte a matriz para objeto MatrixFactory
    $matrix = MatrixFactory::create($matrix);

    // Declaração do vetor inicial vazio
    $currentVector = [[]];
    for ($i = 0; $i < $states_number; $i++) {
        $currentVector[0][$i] = 0;
    }

    // Define o vetor de estado inicial com base no último estado do conjunto
    $currentVector[0][$paper[count($paper) - 1][$state_type] - 1] = 1;
    $currentVector = MatrixFactory::create($currentVector);
    

    // Multiplicação pelo vetor inicial
    $currentVector = $currentVector->multiply($matrix);

    return $currentVector->getMatrix(); // Retorna o vetor como array
    //return $currentVector->toArray(); // Retorna o vetor como array
}

//public function calculateNextVector($three_state_matrix1, $currentVector)
//{
    
    // Criar a matriz de transição e vetor inicial
   // $matrix = MatrixFactory::create($three_state_matrix1);
   // $vector = MatrixFactory::create([$currentVector]); // O vetor é uma matriz de 1 linha

    // Multiplicar matriz pelo vetor
   // $nextVector = $vector->multiply($matrix);

    // Verificar resultado antes de retornar
   // if ($nextVector->getM() === 0 || $nextVector->getN() === 0) {
        //throw new \Exception('Erro na multiplicação: vetor resultante vazio.');
   // }

    // Retornar como array simples
   // return $nextVector->toArray()[0]; // Pega a única linha do vetor
//}

public function calculateNextVector($three_state_matrix1, $currentVector)
{
    // Verifica se a matriz de transição não está vazia
    if (empty($three_state_matrix1) || empty($currentVector)) {
        throw new InvalidArgumentException("Matriz de transição e vetor atual não podem ser vazios.");
    }

    // Verifica se a matriz é quadrada
    $numStates = count($three_state_matrix1);
    foreach ($three_state_matrix1 as $row) {
        if (count($row) !== $numStates) {
            throw new Exception("A matriz de transição deve ser quadrada.");
        }
    }

    // Verifica se as dimensões da matriz e do vetor são compatíveis
    if ($numStates !== count($currentVector)) {
        throw new Exception("A matriz de transição e o vetor atual devem ter dimensões compatíveis.");
    }

    // Inicializa o vetor de resultado
    $nextVector = array_fill(0, $numStates, 0);

    // Realiza a multiplicação da matriz pelo vetor atual
    foreach ($three_state_matrix1 as $rowIndex => $row) {
        foreach ($row as $colIndex => $value) {
            $nextVector[$rowIndex] += $value * $currentVector[$colIndex];
        }
    }

    // Retorna o próximo vetor previsto
    return $nextVector;
}
}
// Normalizar o vetor para garantir que a soma seja 1
   // $totalSum = array_sum($nextVector);
    //if ($totalSum > 0) {
       // foreach ($nextVector as &$value) {
            //$value /= $totalSum;
       // }
   // }