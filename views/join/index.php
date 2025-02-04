<?php

use app\models\ConsultaModel;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use yii\jui\DatePicker;
use PHPSimplex\Simplex;

ini_set('max_execution_time', 0); //300 seconds = 5 minutes
ini_set('memory_limit', '-1');


echo "<h2>Otimização com Simplex</h2>";

$this->title = 'Solução Simplex';

$consultaModel = new ConsultaModel;

?>

<div class="container">
    <div class="row">
        <h2>Previsão de grandes períodos usando CMTD</h2>
        <p>Data Inicial: Primeira data do período a ser previsto</p>
        <p>Data Final: Última data do períiodo a ser previsto</p>
        <p>Periodo: Número (inteiro) de meses ou anos que formarão o conjunto de treinamento</p>
        <p>Métrica: Métrica para criação do conjunto de treinamento</p>
        <p>Base Média Móvel: Número de elementos usados para o cálculo da média móvel</p>
    </div>
    <hr>
    <div class="row">
        <h2>Heuristica</h2>
        <p>Quantidade de observações: Esse campo servirá para a heuristica, nele podemos enviar a quantidade de observações necessarias antes de localizar um ponto de inflexão</p>
    </div>
</div>


<hr>

<div class="container">
    <?php $form = ActiveForm::begin(['layout' => 'horizontal']) ?>

    <?= $form->field($consultaModel, 'nome')->dropDownList(
        [
            'ABCB4' => 'ABCB4',
            'ABCP11' => 'ABCP11',
            'AFLT3' => 'AFLT3',
            'AGRO3' => 'AGRO3',
            'ALPA3' => 'ALPA3',
            'ALPA4' => 'ALPA4',
            'ALSC3' => 'ALSC3',
            'AMAR3' => 'AMAR3',
            'ANCR11B' => 'ANCR11B',
            'BAHI3' => 'BAHI3',
            'BALM4' => 'BALM4',
            'BAUH4' => 'BAUH4',
            'BAZA3' => 'BAZA3',
            'BBAS3' => 'BBAS3',
            'BBDC3' => 'BBDC3',
            'BBDC4' => 'BBDC4',
            'BBFI11B' => 'BBFI11B',
            'BBRK3' => 'BBRK3',
            'BDLL4' => 'BDLL4',
            'BEEF3' => 'BEEF3',
            'BEES3' => 'BEES3',
            'BEES4' => 'BEES4',
            'BGIP3' => 'BGIP3',
            'BGIP4' => 'BGIP4',
            'BIOM3' => 'BIOM3',
            'BMEB3' => 'BMEB3',
            'BMEB4' => 'BMEB4',
            'BMIN3' => 'BMIN3',
            'BMIN4' => 'BMIN4',
            'BMKS3' => 'BMKS3',
            'BNBR3' => 'BNBR3',
            'BOBR4' => 'BOBR4',
            'BOVA11' => 'BOVA11',
            'BRAP3' => 'BRAP3',
            'BRAP4' => 'BRAP4',
            'BRAX11' => 'BRAX11',
            'BRFS3' => 'BRFS3',
            'BRGE11' => 'BRGE11',
            'BRGE12' => 'BRGE12',
            'BRGE3' => 'BRGE3',
            'BRGE6' => 'BRGE6',
            'BRGE8' => 'BRGE8',
            'BRIV3' => 'BRIV3',
            'BRIV4' => 'BRIV4',
            'BRKM3' => 'BRKM3',
            'BRKM5' => 'BRKM5',
            'BRKM6' => 'BRKM6',
            'BRML3' => 'BRML3',
            'BRPR3' => 'BRPR3',
            'BRSR3' => 'BRSR3',
            'BRSR5' => 'BRSR5',
            'BRSR6' => 'BRSR6',
            'BSLI4' => 'BSLI4',
            'BTOW3' => 'BTOW3',
            'BTTL3' => 'BTTL3',
            'CAMB4' => 'CAMB4',
            'CARD3' => 'CARD3',
            'CBEE3' => 'CBEE3',
            'CCPR3' => 'CCPR3',
            'CCRO3' => 'CCRO3',
            'CEBR3' => 'CEBR3',
            'CEBR5' => 'CEBR5',
            'CEBR6' => 'CEBR6',
            'CEDO3' => 'CEDO3',
            'CEDO4' => 'CEDO4',
            'CEEB3' => 'CEEB3',
            'CEEB5' => 'CEEB5',
            'CEED3' => 'CEED3',
            'CEED4' => 'CEED4',
            'CEGR3' => 'CEGR3',
            'CELP5' => 'CELP5',
            'CELP7' => 'CELP7',
            'CEPE5' => 'CEPE5',
            'CEPE6' => 'CEPE6',
            'CESP3' => 'CESP3',
            'CESP5' => 'CESP5',
            'CESP6' => 'CESP6',
            'CGAS3' => 'CGAS3',
            'CGAS5' => 'CGAS5',
            'CGRA3' => 'CGRA3',
            'CGRA4' => 'CGRA4',
            'CIEL3' => 'CIEL3',
            'CLSC3' => 'CLSC3',
            'CMIG3' => 'CMIG3',
            'CMIG4' => 'CMIG4',
            'COCE3' => 'COCE3',
            'COCE5' => 'COCE5',
            'CPFE3' => 'CPFE3',
            'CPLE3' => 'CPLE3',
            'CPLE6' => 'CPLE6',
            'CRDE3' => 'CRDE3',
            'CRIV3' => 'CRIV3',
            'CRIV4' => 'CRIV4',
            'CSAB3' => 'CSAB3',
            'CSAB4' => 'CSAB4',
            'CSAN3' => 'CSAN3',
            'CSMG3' => 'CSMG3',
            'CSNA3' => 'CSNA3',
            'CSRN3' => 'CSRN3',
            'CSRN5' => 'CSRN5',
            'CSRN6' => 'CSRN6',
            'CTKA3' => 'CTKA3',
            'CTKA4' => 'CTKA4',
            'CTNM3' => 'CTNM3',
            'CTNM4' => 'CTNM4',
            'CTSA3' => 'CTSA3',
            'CTSA4' => 'CTSA4',
            'CXCE11B' => 'CXCE11B',
            'CYRE3' => 'CYRE3',
            'DASA3' => 'DASA3',
            'DIRR3' => 'DIRR3',
            'DOHL3' => 'DOHL3',
            'DOHL4' => 'DOHL4',
            'DTCY3' => 'DTCY3',
            'DTEX3' => 'DTEX3',
            'EALT4' => 'EALT4',
            'ECOR3' => 'ECOR3',
            'ECPR3' => 'ECPR3',
            'EDFO11B' => 'EDFO11B',
            'EEEL3' => 'EEEL3',
            'EEEL4' => 'EEEL4',
            'EKTR4' => 'EKTR4',
            'ELEK3' => 'ELEK3',
            'ELEK4' => 'ELEK4',
            'ELET3' => 'ELET3',
            'ELET5' => 'ELET5',
            'ELET6' => 'ELET6',
            'ELPL3' => 'ELPL3',
            'EMAE4' => 'EMAE4',
            'EMBR3' => 'EMBR3',
            'ENBR3' => 'ENBR3',
            'ENGI11' => 'ENGI11',
            'ENGI3' => 'ENGI3',
            'ENGI4' => 'ENGI4',
            'ENMA3B' => 'ENMA3B',
            'EQTL3' => 'EQTL3',
            'ESTC3' => 'ESTC3',
            'ESTR4' => 'ESTR4',
            'ETER3' => 'ETER3',
            'EUCA4' => 'EUCA4',
            'EURO11' => 'EURO11',
            'EVEN3' => 'EVEN3',
            'EZTC3' => 'EZTC3',
            'FAMB11B' => 'FAMB11B',
            'FBMC4' => 'FBMC4',
            'FESA3' => 'FESA3',
            'FESA4' => 'FESA4',
            'FFCI11' => 'FFCI11',
            'FHER3' => 'FHER3',
            'FIBR3' => 'FIBR3',
            'FIIP11B' => 'FIIP11B',
            'FJTA3' => 'FJTA3',
            'FJTA4' => 'FJTA4',
            'FLMA11' => 'FLMA11',
            'FLRY3' => 'FLRY3',
            'FMOF11' => 'FMOF11',
            'FNAM11' => 'FNAM11',
            'FNOR11' => 'FNOR11',
            'FPAB11' => 'FPAB11',
            'FRAS3' => 'FRAS3',
            'FRIO3' => 'FRIO3',
            'FSPE11' => 'FSPE11',
            'FSRF11' => 'FSRF11',
            'FSTU11' => 'FSTU11',
            'GEPA3' => 'GEPA3',
            'GEPA4' => 'GEPA4',
            'GFSA3' => 'GFSA3',
            'GGBR3' => 'GGBR3',
            'GGBR4' => 'GGBR4',
            'GOAU3' => 'GOAU3',
            'GOAU4' => 'GOAU4',
            'GOLL4' => 'GOLL4',
            'GPAR3' => 'GPAR3',
            'GPCP3' => 'GPCP3',
            'GRND3' => 'GRND3',
            'GSHP3' => 'GSHP3',
            'GUAR3' => 'GUAR3',
            'GUAR4' => 'GUAR4',
            'HAGA3' => 'HAGA3',
            'HAGA4' => 'HAGA4',
            'HBOR3' => 'HBOR3',
            'HBTS5' => 'HBTS5',
            'HETA4' => 'HETA4',
            'HGBS11' => 'HGBS11',
            'HGJH11' => 'HGJH11',
            'HGRE11' => 'HGRE11',
            'HGTX3' => 'HGTX3',
            'HOOT4' => 'HOOT4',
            'HYPE3' => 'HYPE3',
            'IBOV11' => 'IBOV11',
            'IDNT3' => 'IDNT3',
            'IDVL4' => 'IDVL4',
            'IGBR3' => 'IGBR3',
            'IGTA3' => 'IGTA3',
            'INEP3' => 'INEP3',
            'INEP4' => 'INEP4',
            'ITEC3' => 'ITEC3',
            'ITSA3' => 'ITSA3',
            'ITSA4' => 'ITSA4',
            'ITUB3' => 'ITUB3',
            'ITUB4' => 'ITUB4',
            'JBDU3' => 'JBDU3',
            'JBDU4' => 'JBDU4',
            'JBSS3' => 'JBSS3',
            'JFEN3' => 'JFEN3',
            'JHSF3' => 'JHSF3',
            'JOPA3' => 'JOPA3',
            'JOPA4' => 'JOPA4',
            'JSLG3' => 'JSLG3',
            'KEPL3' => 'KEPL3',
            'KLBN3' => 'KLBN3',
            'KLBN4' => 'KLBN4',
            'KNRI11' => 'KNRI11',
            'LAME3' => 'LAME3',
            'LAME4' => 'LAME4',
            'LIGT3' => 'LIGT3',
            'LIPR3' => 'LIPR3',
            'LLIS3' => 'LLIS3',
            'LOGN3' => 'LOGN3',
            'LPSB3' => 'LPSB3',
            'LREN3' => 'LREN3',
            'LUPA3' => 'LUPA3',
            'MAGG3' => 'MAGG3',
            'MAPT4' => 'MAPT4',
            'MDIA3' => 'MDIA3',
            'MEND5' => 'MEND5',
            'MEND6' => 'MEND6',
            'MERC3' => 'MERC3',
            'MERC4' => 'MERC4',
            'MGEL4' => 'MGEL4',
            'MILS3' => 'MILS3',
            'MMXM3' => 'MMXM3',
            'MNDL3' => 'MNDL3',
            'MNPR3' => 'MNPR3',
            'MOAR3' => 'MOAR3',
            'MPLU3' => 'MPLU3',
            'MRFG3' => 'MRFG3',
            'MRVE3' => 'MRVE3',
            'MSPA3' => 'MSPA3',
            'MSPA4' => 'MSPA4',
            'MTIG4' => 'MTIG4',
            'MTSA4' => 'MTSA4',
            'MULT3' => 'MULT3',
            'MWET4' => 'MWET4',
            'MYPK3' => 'MYPK3',
            'NAFG4' => 'NAFG4',
            'NATU3' => 'NATU3',
            'ODPV3' => 'ODPV3',
            'OGXP3' => 'OGXP3',
            'OSXB3' => 'OSXB3',
            'PABY11' => 'PABY11',
            'PATI3' => 'PATI3',
            'PATI4' => 'PATI4',
            'PDGR3' => 'PDGR3',
            'PEAB3' => 'PEAB3',
            'PEAB4' => 'PEAB4',
            'PETR3' => 'PETR3',
            'PETR4' => 'PETR4',
            'PFRM3' => 'PFRM3',
            'PIBB11' => 'PIBB11',
            'PINE4' => 'PINE4',
            'PLAS3' => 'PLAS3',
            'PMAM3' => 'PMAM3',
            'PNVL3' => 'PNVL3',
            'PNVL4' => 'PNVL4',
            'POMO3' => 'POMO3',
            'POMO4' => 'POMO4',
            'POSI3' => 'POSI3',
            'PQDP11' => 'PQDP11',
            'PRSV11' => 'PRSV11',
            'PSSA3' => 'PSSA3',
            'PTBL3' => 'PTBL3',
            'PTNT3' => 'PTNT3',
            'PTNT4' => 'PTNT4',
            'RANI3' => 'RANI3',
            'RANI4' => 'RANI4',
            'RAPT3' => 'RAPT3',
            'RAPT4' => 'RAPT4',
            'RBDS11' => 'RBDS11',
            'RBRD11' => 'RBRD11',
            'RCSL3' => 'RCSL3',
            'RCSL4' => 'RCSL4',
            'RDNI3' => 'RDNI3',
            'REDE3' => 'REDE3',
            'RENT3' => 'RENT3',
            'RNEW11' => 'RNEW11',
            'ROMI3' => 'ROMI3',
            'RPAD3' => 'RPAD3',
            'RPAD5' => 'RPAD5',
            'RPAD6' => 'RPAD6',
            'RPMG3' => 'RPMG3',
            'RSID3' => 'RSID3',
            'SANB11' => 'SANB11',
            'SANB3' => 'SANB3',
            'SANB4' => 'SANB4',
            'SAPR4' => 'SAPR4',
            'SBSP3' => 'SBSP3',
            'SCAR3' => 'SCAR3',
            'SGPS3' => 'SGPS3',
            'SHPH11' => 'SHPH11',
            'SHUL4' => 'SHUL4',
            'SLCE3' => 'SLCE3',
            'SLED3' => 'SLED3',
            'SLED4' => 'SLED4',
            'SMAL11' => 'SMAL11',
            'SMTO3' => 'SMTO3',
            'SNSY5' => 'SNSY5',
            'SOND5' => 'SOND5',
            'SOND6' => 'SOND6',
            'SPRI3' => 'SPRI3',
            'SPRI5' => 'SPRI5',
            'SULA11' => 'SULA11',
            'TCNO3' => 'TCNO3',
            'TCNO4' => 'TCNO4',
            'TCSA3' => 'TCSA3',
            'TEKA3' => 'TEKA3',
            'TEKA4' => 'TEKA4',
            'TELB3' => 'TELB3',
            'TELB4' => 'TELB4',
            'TGMA3' => 'TGMA3',
            'TKNO4' => 'TKNO4',
            'TOTS3' => 'TOTS3',
            'TOYB3' => 'TOYB3',
            'TOYB4' => 'TOYB4',
            'TPIS3' => 'TPIS3',
            'TRIS3' => 'TRIS3',
            'TRPL3' => 'TRPL3',
            'TRPL4' => 'TRPL4',
            'TRPN3' => 'TRPN3',
            'TRXL11' => 'TRXL11',
            'TUPY3' => 'TUPY3',
            'TXRX3' => 'TXRX3',
            'TXRX4' => 'TXRX4',
            'UGPA3' => 'UGPA3',
            'UNIP3' => 'UNIP3',
            'UNIP5' => 'UNIP5',
            'UNIP6' => 'UNIP6',
            'USIM3' => 'USIM3',
            'USIM5' => 'USIM5',
            'USIM6' => 'USIM6',
            'VALE3' => 'VALE3',
            'VLID3' => 'VLID3',
            'VULC3' => 'VULC3',
            'WEGE3' => 'WEGE3',
            'WHRL3' => 'WHRL3',
            'WHRL4' => 'WHRL4'
        ],
        [
            'style' => ['width' => '100px', 'height' => '30px']
        ]
    ) ?>

    <?= $form->field($consultaModel, 'inicio')->widget(DatePicker::className(), [
        'language' => 'pt-BR',
        'dateFormat' => 'dd/MM/yyyy'
    ]) ?>

    <?= $form->field($consultaModel, 'final')->widget(DatePicker::className(), [
        'language' => 'pt-BR',
        'dateFormat' => 'dd/MM/yyyy'
    ]) ?>


    <?= $form->field($consultaModel, 'periodo')->textInput([
        'style' => ['width' => '190px', 'height' => '30px']
    ]) ?>

    <?= $form->field($consultaModel, 'qtde_obs')->textInput([
        'style' => ['width' => '190px', 'height' => '30px']
    ]) ?>


    <?= $form->field($consultaModel, 'metric')->dropDownList(
        [
            'month' => 'Meses',
            'year' => 'Anos'
        ],
        [
            'style' => ['width' => '190px', 'height' => '30px']
        ]
    ) ?>

    <?= $form->field($consultaModel, 'states_number')->textInput([
        'style' => ['width' => '190px', 'height' => '30px']
    ]) ?>
    <!-- <?= $form->field($consultaModel, 'base')->textInput() ?> -->

    <div class="form-group">
        <div class="col-lg-offset-6">
            <?= Html::submitButton('Enviar', ['class' => 'btn btn-primary']) ?>
        </div>
    </div>

    <?php $form = ActiveForm::end() ?>

    <h1>Solução do Problema Simplex</h1>

<p>A solução ótima do problema de minimização é:</p>
<ul>
    <?php foreach ($solution as $variable => $value): ?>
        <li><?= Html::encode($variable) ?> = <?= Html::encode($value) ?></li>
    <?php endforeach; ?>
</ul>




</div>