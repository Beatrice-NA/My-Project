<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado Segunda Ordem</title>
</head>
<body>
    <h3>Matrix Segunda Ordem</h3>
    <pre><?= print_r($matrixSegundaOrdem, true) ?></pre>
    <h3> Three_state_matrix1</h3>
    <pre><?= print_r($three_state_matrix1, true) ?></pre>

    <h3>Initial Vector</h3>
    <pre><?= print_r($initialVector, true) ?></pre>

    <h3>Transposed Vector</h3>
    <pre><?= print_r($transposedVector, true) ?></pre>

    <h3> Result Vector1</h3>
    <?php if (isset($resultVector1)): ?>
    <pre><?= print_r($resultVector1, true) ?></pre>
    <?php else: ?>
    <p>O vetor de resultado não foi definido.</p>
    <?php endif; ?>

    <h3> Result Vector2</h3>
    <?php if (isset($resultVector2)): ?>
    <pre><?= print_r($resultVector2, true) ?></pre>
    <?php else: ?>
    <p>O vetor de resultado não foi definido.</p>
    <?php endif; ?>
    <h3>Variables of restrictions</h3>
    <p>
    <?php
    if (is_array($result) && !empty($result)) {
        echo "Melhores valores de λ: " . implode(", ", $result);
    } else {
        echo "Nenhuma solução válida foi encontrada.";
    }
    ?>
    </p>
    
    <h3>Resultados</h3>
    <?php if ($W_star !== null) : ?>
    <p><strong>O maior valor calculado de W:</strong> <?= htmlspecialchars($W_star, ENT_QUOTES, 'UTF-8') ?></p>
    <?php else : ?>
    <p><strong>Erro:</strong> Nenhum valor de W foi calculado.</p>
    <?php endif; ?>

    <h3>Optimal Solution</h3>
    <p>λ1* = <?= htmlspecialchars($optimalSolution['lambda1_star'], ENT_QUOTES, 'UTF-8') ?></p>
    <p>λ2* = <?= htmlspecialchars($optimalSolution['lambda2_star'], ENT_QUOTES, 'UTF-8') ?></p>
    <p>W* = <?= htmlspecialchars($optimalSolution['W_star'], ENT_QUOTES, 'UTF-8') ?></p>

    <h3>Current Vector</h3>
    <pre><?= print_r($currentVector, true) ?></pre>

    <h3>Vetor Previsto para o Próximo Dia</h3>
    <pre><?= print_r($nextVector, true) ?></pre>
</body>
</html>