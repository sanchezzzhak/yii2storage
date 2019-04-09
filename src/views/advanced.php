<?php
/**
 * @var \yii\web\View $this
 * @var \kak\storage\UploadAdvanced $context
 */

use yii\helpers\Html;

$context = $this->context;
?>


<?= Html::beginTag('div', $context->options); ?>
    <div class="wgt-wrap-header wgt-stage-hide">
        <div class="wgt-header-back"></div>
        <div class="wgt-header-title"></div>
        <div class="wgt-header-more"></div>
    </div>
    <div class="wgt-wrap-content"></div>
    <div class="wgt-all-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100">
        <div class="bar" style="width: 0%"><span></span></div>
    </div>
<?= Html::endTag('div'); ?>
