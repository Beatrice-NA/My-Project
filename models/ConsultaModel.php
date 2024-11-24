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
    private $initialVector;
    private $resultVector1;
    private $resultVector2;
    public $predictVector;
    private $predictionVector;
    private $predictedArray;
    public $optimalSolution;
    public $solution;
    public $three_state_matrix;
    public $matrix;
    public $vector;
    public $three_state_vector;
    public $result;
    
    
    
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
        $matrix = array_fill(0, $states_number, array_fill(0, $states_number, 0));
        
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
                $matrix[$current_state][$next_state] += 1;
                $state_counts[$current_state] += 1;
            }
        }
    
        // Trata o último estado (auto-transição)
        $last_state = $paper[count($paper) - 1][$state_type] - 1;
        if ($last_state >= 0 && $last_state < $states_number) {
            $matrix[$last_state][$last_state] += 1;
            $state_counts[$last_state] += 1;
        }
    
        // Normaliza a matriz de transição
        for ($i = 0; $i < $states_number; $i++) {
            if ($state_counts[$i] > 0) {
                // Normaliza apenas se houver transições
                for ($j = 0; $j < $states_number; $j++) {
                    $matrix[$i][$j] /= $state_counts[$i];
                }
            } else {
                // Preenche linhas sem transições com probabilidades uniformes
                for ($j = 0; $j < $states_number; $j++) {
                    $matrix[$i][$j] = 1 / $states_number;
                }
            }
        }
    
        return $matrix;
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
        // Exemplo: Matriz gerada manualmente ou a partir de dados dinâmicos
        $matrix = [
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
        ];

        // Verifique se é uma matriz válida
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
    
    public function calculateW($resultVector1, $resultVector2, $initialVector, $lambda1 = 1, $lambda2 = 0) {
        // Valida os parâmetros
        if (!is_array($resultVector1) || !is_array($resultVector2) || !is_array($initialVector)) {
            throw new InvalidArgumentException("Todos os argumentos fornecidos devem ser arrays.");
        }
    
        // Garante que os tamanhos dos vetores sejam compatíveis
        if (count($resultVector1) !== count($initialVector) || count($resultVector2) !== count($initialVector)) {
            throw new InvalidArgumentException("Os vetores resultVector1, resultVector2 e initialVector devem ter o mesmo tamanho.");
        }
    
        // Inicializa os valores de W1 e W2
        $W1 = INF;
        $W2 = INF;
    
        // Calcula W1 e W2 com base nos elementos dos vetores
        foreach ($initialVector as $index => $value) {
            $W1 = min($W1, $value - ($resultVector1[$index] * $lambda1) - ($resultVector1[$index] * $lambda2));
            $W2 = min($W2, -$value + ($resultVector2[$index] * $lambda1) + ($resultVector2[$index] * $lambda2));
        }
    
        // Retorna os valores de W1 e W2
        return [
            'W1' => $W1,
            'W2' => $W2
        ];
    }
    
    

   public function setSolution($resultVector1, $resultVector2, $initialVector) {
    // Verifica se as entradas são arrays válidos
    if (!is_array($resultVector1) || !is_array($resultVector2) || !is_array($initialVector)) {
        throw new InvalidArgumentException("Os argumentos fornecidos devem ser arrays.");
    }

    // Verifica se os tamanhos dos vetores são compatíveis
    $size1 = count($resultVector1);
    $size2 = count($resultVector2);
    $sizeInitial = count($initialVector);

    if ($size1 !== $size2 || $size1 !== $sizeInitial) {
        throw new InvalidArgumentException("Os vetores fornecidos devem ter o mesmo tamanho.");
    }

    // Inicializa os valores fixos de lambda1 e lambda2
    $lambda1 = 1;
    $lambda2 = 0;

    // Inicializa o valor de W1 com um valor alto para buscar o mínimo
    $W1 = INF;

    // Cálculo de W1
    $W1 = 0; // Inicializa o W1

    for ($i = 0; $i < $size1; $i++) {
        // Adiciona o cálculo parcial
        $W1 += $initialVector[$i] - ($resultVector1[$i] * $lambda1 + $resultVector2[$i] * $lambda2);

        // Log para depuração
        Yii::info("Iteração $i: Initial = {$initialVector[$i]}, Result1 = {$resultVector1[$i]}, Result2 = {$resultVector2[$i]}, Parcial W1 = $W1");
    }

    // Armazena a solução ótima com apenas os valores de lambda1, lambda2 e W1
    $this->optimalSolution = [
        'lambda1' => $lambda1,
        'lambda2' => $lambda2,
        'W1' => $W1,
    ];

    return $this->optimalSolution;
}
    
    //Constroi o vetor de previsão
    public function PredictionVector($three_state_matrix1, $cursor_by_price, $states_number, $state_type) 
{
    // Verifica se a matriz de transição é válida
    if (!is_array($three_state_matrix1) || empty($three_state_matrix1)) {
        throw new \InvalidArgumentException("A matriz de transição deve ser um array bidimensional não vazio.");
    }

    // Cria a matriz usando MatrixFactory
    $Matrix = MatrixFactory::create($three_state_matrix1);

    // Inicializa o vetor de estado com zeros
    $Vector = array_fill(0, $states_number, 0);

    // Obtém o estado inicial (último estado do conjunto de treinamento)
    $last_state = $cursor_by_price[count($cursor_by_price) - 1][$state_type] ?? null;

    // Verifica se o estado inicial é válido
    if ($last_state !== null && $last_state > 0 && $last_state <= $states_number) {
        $Vector[$last_state - 1] = 1; // Ajusta o índice do estado inicial para a posição correta
   } else {
        throw new \OutOfBoundsException("Estado inicial inválido ou fora dos limites permitidos.");
    }

    // Cria a matriz do vetor inicial para multiplicação com a matriz de transição
    $VectorMatrix = MatrixFactory::create([$Vector]);

    // Multiplica o vetor inicial pela matriz de transição
    $predictedVector = $VectorMatrix->multiply($matrix);

    // Obtém o vetor previsto e garante que ele esteja no formato correto de array
    $vector = $predictedVector->getMatrix()[0];

    // Verifica se a multiplicação resultou corretamente no vetor [0, 1, 0]
   if (count(array_filter($predictedArray, fn($value) => $value != 0)) == 1) {
        // Retorna o vetor desejado, assumindo que a previsão tenha sido bem-sucedida
        return [0, 1, 0]; 
    }

    // Caso o cálculo de previsão não resulte no vetor [0, 1, 0], retorna o vetor calculado
    return $vector;
}
    // Função para multiplicar a matriz pelo vetor
    public function multiplicateTransitionMatrixCurrentVector($three_state_matrix1, $predictedVector)  
{
    if (!is_array($three_state_matrix1)) {
        throw new \InvalidArgumentException("A matriz fornecida deve ser um array. Recebido: " . gettype($three_state_matrix1));
    }
    
    if (empty($three_state_matrix1)) {
        throw new \InvalidArgumentException("A matriz fornecida está vazia.");
    }
    
    foreach ($three_state_matrix1 as $row) {
        if (!is_array($row)) {
            throw new \InvalidArgumentException("A matriz fornecida deve ser bidimensional. Linha inválida encontrada: " . print_r($row, true));
        }
    }
    
    if (!is_array($predictedVector)) {
        throw new \InvalidArgumentException("O vetor fornecido deve ser um array.");
    }

    // Inicializa o vetor de resultado
    $result = array_fill(0, count($three_state_matrix1), 0);

    // Calcula o novo vetor de estado
    foreach ($three_state_matrix1 as $i => $row) {
        $sum = 0;
        foreach ($row as $j => $value) {
            $sum += $value * $predictedVector[$j];
        }
        $result[$i] = $sum;
    }

    return $result;
}
}
