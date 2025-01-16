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

    <?php if (isset($resultado['error'])): ?>
    <p>Erro: <?= htmlspecialchars($resultado['error'], ENT_QUOTES, 'UTF-8') ?></p>
  <?php elseif (isset($resultado['optimalValue'], $resultado['solution'])): ?>
    <h3>Resultado</h3>
    <p>Valor ótimo: <?= htmlspecialchars($resultado['optimalValue'], ENT_QUOTES, 'UTF-8') ?></p>
    <p>Solução: <?= htmlspecialchars(implode(', ', $resultado['solution']), ENT_QUOTES, 'UTF-8') ?></p>
  <?php else: ?>
    <p>Resultado não encontrado ou dados incompletos.</p>
  <?php endif; ?>

    <h3>Current Vector</h3>
    <pre><?= print_r($currentVector, true) ?></pre>

    <h3>Vetor Previsto para o Próximo Dia</h3>
    <pre><?= print_r($nextVector, true) ?></pre>
</body>
</html>