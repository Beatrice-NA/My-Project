<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado Segunda Ordem</title>
</head>
<body>
    <h3>matrix Segunda Ordem</h3>
    <pre><?= print_r($matrixSegundaOrdem, true) ?></pre>
    <h3>Three State Matrix1</h3>
    <pre><?= print_r($three_state_matrix1, true) ?></pre>
    <h3>initial vector</h3>
    <pre><?= print_r($initialVector, true) ?></pre>
    <h3>transposed Vector</h3>
    <pre><?= print_r($transposedVector, true) ?></pre>
    <h3> result Vector1</h3>
    <?php if (isset($resultVector1)): ?>
    <pre><?= print_r($resultVector1, true) ?></pre>
    <?php else: ?>
    <p>O vetor de resultado não foi definido.</p>
    <?php endif; ?>
    <h3> result Vector2</h3>
    <?php if (isset($resultVector2)): ?>
    <pre><?= print_r($resultVector2, true) ?></pre>
    <?php else: ?>
    <p>O vetor de resultado não foi definido.</p>
    <?php endif; ?>
    <pre>
    Valor de W na primeira equação: <?= $resultados['W1'] ?><br>
    Valor de W na segunda equação: <?= $resultados['W2'] ?>
    </pre>
    <h3>optimal Solution</h3>
    <pre><?= print_r($optimalSolution, true) ?></pre>
    <h3> Vector</h3>
    <pre><?= print_r($Vector, true) ?></pre>
    
</body>
</html>