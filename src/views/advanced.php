<?php
/**
 * @var \yii\web\View $this
 * @var \kak\storage\UploadAdvanced $context
 */

$context = $this->context;
?>

<!--<div class="wgt-dash-content-header">-->
<!--    <div class="wgt-dash-content-bar">-->
<!--        bar-->
<!--    </div>-->
<!--</div>-->

<div class="kak-upload-dashboard-wgt" id="<?= $context->id ?>">
    <div class="wgt-wrap-header wgt-stage-hide">
        <div class="wgt-header-back"></div>
        <div class="wgt-header-title"></div>
        <div class="wgt-header-more"></div>
    </div>
    <div class="wgt-wrap-content"></div>
    <div class="wgt-all-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100">
        <div class="bar" style="width: 0%"><span></span></div>
    </div>
</div>
