<?php
namespace app\controllers;

use yii\web\Controller;
use app\models\ConsultaModel;
use app\models\Paper;
use Yii;
use MathPHP\LinearAlgebra\MatrixFactory;
use PHPSimplex\Simplex;

//date_default_timezone_set("america/bahia");

class SegundaOrdemController extends Controller
{
    //public $w = 0; // Declara a propriedade 'w'
    
    //public $InitialVector = 0;
    //public $resultVector1 = 0;
    //public $resultVector2 = 0;

    public function actionIndex(){
        $this->layout = 'navbar';
        
        $model = new ConsultaModel;
        $post = $_POST;
        $consultas = 0;
        $cursor_by_price = 0;
        $states = 0;
        $transposeVector = 0;
        $transposedVector = 0;
        $resultVector1 = 0;
        $resultVector2 = 0;
        $sumstates = 0;
        $paper = 0;
        $initialVector = 0;
        $optimalSolution = 0;
        $solution = 0;
        $Vector = 0;
        $states_number = 0;
        $state_type = 0;
        $three_state_matrix = 0;
        $nextStateVector = 0;
        $three_state_matrix1 = 0;
        $matrix = 0;
        $currentVector = 0;
        $nextVector = 0;
        $solution = 0;
        $objectiveFunction = 0;
        $result = 0;
        $lambda = 0;
        $w = 0;
        $maxW = 0;
        $bestLambdas = 0;
        $variables = 0;
        $n = 0;
        $m = 0;
        $w = 0;
        $lambda1_star = 0;
        $lambda2_star = 0;
        $W_star = 0;
        $data = 0;
        $resultado = 0;
    

        // uso do uniqueId
       $uniqueId = Yii::$app->controller->uniqueId;
        Yii::info("O uniqueId do controller atual é: $uniqueId");


        if ($model->load($post) && $model->validate()) {
            $start = $model->inicio;
            $final = $model->final;
    
            // Dia de início do conjunto de treinamento
            $start = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('-1 day');
    
            // Dia final do conjunto de treinamento
            $final = \DateTime::createFromFormat('d/m/YH:i:s', $final . '24:00:00');
    
            // Passando para o padrão de datas do banco
            $start = Paper::toIsoDate($start->getTimestamp());
            $final = Paper::toIsoDate($final->getTimestamp());
    
            $action_name = $model->nome;
    
            // Obtendo dados do modelo
           $cursor_by_price = $model->getData($action_name, $start, $final);
            $consultas = count($cursor_by_price);

            if (count($cursor_by_price) > 1){
                $cursor_by_price[0]["t_state"] = 2; // inicializa o primeiro estado
    
                $three_states = [0, 0, 0];
            
                //atribui um estado a partir do preço de fechamento para cada data no conjunto de treinamento
                foreach ($cursor_by_price as $index => $cursor) {
                    if ($index > 1) {
                        $cursor['t_state'] = $model->getThreeState($cursor['preult'], $cursor_by_price[$index - 2]['preult']);
                        $three_states[$cursor['t_state'] - 1] += 1;
                    }
                }
            
                //$three_state_matrix = $model->transitionMatrix($cursor_by_price, $three_states, 3, "t_state");
                //$three_state_matrix = $model->transitionMatrixSegundaOrdem($cursor_by_price, $three_states, 3, "t_state"); // Construir a matriz de transição de segunda ordem
               // $three_state_vector = $model->predictVector($three_state_matrix, $cursor_by_price, 3, "t_state"); // Construir o vetor de predição
        
                /* Validação .................................................................*/ 

            $matrixSegundaOrdem = $model->transitionMatrixSegundaOrdem($cursor_by_price, $three_states, 3, "t_state") ?? [];
            $three_state_matrix1 = $model->transitionMatrix1($cursor_by_price, $three_states, 3, "t_state") ?? [];
            $matrix = $model->getMatrix();

        try {
            // Definindo a função objetivo para a otimização
           // $objectiveFunction = function ($variables) {
                //  maximiza λ_1^2 + λ_2^2
                //return pow($variables[0], 2) + pow($variables[1], 2);
               // };
    
                //try {
                 //$result = $model->solveOptimizationProblem($objectiveFunction, 2);
                //} catch (Exception $e) {
                   // $result = null;
                   // Yii::error($e->getMessage(), __METHOD__);
               // }
    
                 // Exibindo o resultado no log ou usando-o em cálculos posteriores
               // if (is_array($result) && !empty($result)) {
                   // Yii::info("Solução ótima encontrada: " . implode(", ", $result), __METHOD__);
            // } else {
                   // Yii::warning("Nenhuma solução válida foi encontrada.", __METHOD__);
                //}
            $initialVector = $model->calculateInitialVector($matrix);
            $resultVector1 = $model->multiplyMatrixByinitialVector($matrixSegundaOrdem, $initialVector);
            $resultVector2 = $model->multiplyMatrixByinitialVector($three_state_matrix1, $initialVector);
            $transposedVector = $model->transposeVector($initialVector);
            $currentVector = $model->PredictionVector($three_state_matrix1, $cursor_by_price, $states_number, $state_type);
        } catch (\Exception $e) {
            Yii::error("Erro no processamento: " . $e->getMessage());
        }

        $resultado = $model->calcularSistemaLinear();
        
      if (isset($resultado['error'])) {
        echo "Erro: " . $resultado['error'];
      } else {
        echo "Solução ótima: " . $resultado['optimalValue'] . "<br>";
        echo "Solução: " . implode(", ", $resultado['solution']);
      }
        //try {
            // Obter o maior valor de W
           // $W_star = $model->calculateW($resultVector1, $resultVector2, $initialVector, $bestLambdas);
       // } catch (\Exception $e) {
           // $W_star = null; // Em caso de erro, definir como nulo
           // Yii::error("Erro ao calcular W: " . $e->getMessage());
       // }

        //$bestLambdas = [0, 1]; // Melhores valores calculados
       // $optimalSolution = $model->calculateOptimalSolution($bestLambdas, $W_star);

        $three_state_matrix1 = [
            [0.5, 0.3, 0.2],
            [0.1, 0.6, 0.3],
            [0.4, 0.1, 0.5],
        ];

        $currentVector = [0, 1, 0];
        $nextVector = $model->calculateNextVector($three_state_matrix1, $currentVector);


                // Cria a matriz com o MathPHP
                $Matrix = MatrixFactory::create($three_state_matrix1);
                
                 return $this->render('result', [
                'matrixSegundaOrdem' =>  $matrixSegundaOrdem,
                 //'vector' => $three_state_vector,
                 'three_state_matrix1' => $three_state_matrix1,
                 'initialVector' => $initialVector,
                 'transposedVector' => $transposedVector,
                 'resultVector1' => $resultVector1,
                 'resultVector2' => $resultVector2,
                 'optimalValue' => $optimalValue,
                'solution' => $solution,
                 //'optimalSolution' => $optimalSolution,
                 //'result' => $result,  
                 //'w' => $w,
                 //'lambda1_star' => $lambda1_star,
                //'lambda2_star' => $lambda2_star,
               // 'W_star' => $W_star,
                'currentVector' => [0, 1, 0],
                'nextVector' => $nextVector,
             ]);
            }  else{
              //Tratamento de erro se não houver dados suficientes
                Yii::$app->session->setFlash('error', 'Conjunto de dados insuficiente para calcular.');
                return $this->redirect(['home']);
        }
    }else {
        return $this->render('home');
       }
    }

}  
?>