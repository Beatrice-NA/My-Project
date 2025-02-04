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
        $simplex = 0;
        $objective = 0 ;  
        $constraints = 0; 
        $tableau = 0;
        $Variables = 0;
        $bounds = 0;
        $numConstraints = 0;
    

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
            
            $initialVector = $model->calculateInitialVector($matrix);
            $resultVector1 = $model->multiplyMatrixByinitialVector($matrixSegundaOrdem, $initialVector);
            $resultVector2 = $model->multiplyMatrixByinitialVector($three_state_matrix1, $initialVector);
            $transposedVector = $model->transposeVector($initialVector);
            $currentVector = $model->PredictionVector($three_state_matrix1, $cursor_by_price, $states_number, $state_type);
        } catch (\Exception $e) {
            Yii::error("Erro no processamento: " . $e->getMessage());
        }

        $objective = [-0.46, -0.414]; // Função objetivo (simplificada, sem variáveis de decisão aqui)
        $constraints = [
            [0.46, -0.414, 0.444],  // Restrição 1: coeficientes + RHS(0.46x1 - 0.414x2  = 0.444) 
            [0.46,  0.414, 0.444],   // Restrição 2: coeficientes + RHS(-0.46x1 + 0.414x2 = 0.444) 
            //[-1, 1, 0, 0, 1, 1],  // -x1 + x2 + x5 = 1
        ];

        $simplex = new Simplex($objective, $constraints);
        $solution = $simplex->solve();

    // o método getTableau() 
    echo "Tableau Inicial:\n";
    var_dump($simplex->getTableau());

    // Mostra a solução final
    echo "Solução:\n";
    var_dump($solution);

       
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
                 'objective' => $objective,
                 'constraints' => $constraints,
                 'solution' => $solution,
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