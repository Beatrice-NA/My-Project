<?php

/** @var \yii\web\View $this */
/** @var string $content */

use yii\helpers\Html;
use app\assets\TesteAsset;
use yii\bootstrap\ButtonDropdown;
use yii\bootstrap\Button;

TesteAsset::register($this);

?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">

<head>

  <meta charset="<?= Yii::$app->charset ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">
  <?= Html::csrfMetaTags() ?>

  <!--  <title>Business Frontpage - Start Bootstrap Template</title> -->
  <title><?= Html::encode($this->title) ?></title>
  <?php $this->head() ?>

</head>

<body>
  <?php $this->beginBody() ?>
  <div class="container">
    <div class="row">
      <ul class="navigation">
        <li>
          <a href="home">Home</a>
        </li>
        <li>
          <a href="steady-state-predict">Estado Estável</a>
        </li>
        <li>
        <?php
          echo ButtonDropdown::widget([
            'label' => 'Método com 3 estados',
            'options' => [
              'class' => 'btn btn-primary',
            ],
            'dropdown' => [
              'items' => [
                ['label' => 'Teste', 'url' => 'predict-three-states-test'],
                ['label' => 'Predição', 'url' => 'predict-three-states'],
              ],
            ],
          ]);
          ?>
        </li>
        <li>
          <a href="first-passage-time">Tempo de primeira passagem</a>
        </li>
      </ul>
    </div>
  </div>
  <?= $content ?>

  <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>