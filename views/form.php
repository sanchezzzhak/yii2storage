<?php
    use yii\helpers\Html;
    use \yii\helpers\ArrayHelper;
    /** @var $this \yii\web\View */

$function_download_tmpl = function($model = null , $isJS = true , $options = []) {

    $js = ($isJS? '{% for (var i=0, file; file = o.files[i]; i++) { %}' : '');
    $js.= '<div class="template-upload">';
    $js.= ($isJS? '{% if( o.result.errors){ %}':'');
    $js.= '<div>Error upload</div>';
    $js.= ($isJS? '{% } else { %}': '');
    $js.= '<p>File :{%=file.name%} <span class="size">{%=o.formatFileSize(file.size)%}</span>  '.ArrayHelper::getValue($options,'label_success','').'</p>
            <div class="act">
                <a class="cancel btn inline" href="javascript:;">'.ArrayHelper::getValue($options,'label_delete','Delete').'</a>
            </div>';

    $js.= Html::hiddenInput('meta[]', $model->meta, ['class'=>'meta']) . "\n";
    $js.= ($isJS ? '{% if(o.result.images.thumbnail) { %}':'');

    $js.= '<span>
                <a class="preview" href="javascript:;"><img src="{%=o.result.images.thumbnail.url%}"></a>
                <div class="preview-box hide">
                    <div class="act">';

    $js.= (isset($options['crop']) && $options['crop']) ?
        '<a href="javascript:;" class="crop btn inline" data-url="{%=o.result.crop_url%}">'.ArrayHelper::getValue($options,'label_crop','Crop').'</a>'."\n": '';

    $js.= '<a href="javascript:;" class="crop-cancel btn inline">'.ArrayHelper::getValue($options,'label_cancel','Cancel').'</a>'."\n";
    $js.= '         </div>
                    <img src="{%=o.result.images.preview.url%}">
                </div>
            </span>';

    $js.=  ($isJS? '
            {% } %}
        {% } %}': '');
    $js.=' </div>';
    $js.= ($isJS? '{% } %}':'');
    echo $js;
};
?>

<div class="yii2upload"
     data-url="<?=$url?>"
     data-multiple="<?=($multiple?1:0)?>"
     data-crop="<?=($crop? 1:0)?>"
     data-singleupload="<?=($single_upload? 1:0)?>"
     data-autoupload="<?=($auto_upload ? 1: 0)?>">
    <div>
        <span class="btn fileinput-button">
            <i class="glyphicon glyphicon-plus"></i>
            <span><?=$this->context->label_btn?></span>
            <?=Html::activeFileInput($model, 'file', $options) . "\n"; ?>
        </span>
    </div>
    <?php if($progressbarall):?>
        <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100"><div class="bar" style="width: 0%"><span></span></div></div>
    <?php endif;?>
    <div role="presentation" class="files"></div>
</div>

<script id="tmpl-add" type="text/x-tmpl" >
    {% for (var i=0, file; file=o.files[i]; i++) { %}
    <div class="template-upload">
        <span class="preview"></span>
            <p class="name">{%=file.name%}</p>
            <strong class="error text-danger"></strong>
            <p class="size"><?=$this->context->label_processing_upload?></p>
            <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                <div class="progress-bar bar" style="width:0%;"></div>
            </div>
            {% if (!i && !o.autoUpload) { %}
            <button class="btn inline btn-primary start" disabled>
                <i class="glyphicon glyphicon-upload"></i>
                <span><?=$this->context->label_start?></span>
            </button>
            {% } %}
            {% if (!i) { %}
            <button class="btn inline btn-warning cancel">
                <i class="glyphicon glyphicon-ban-circle"></i>
                <span><?=$this->context->label_cancel?></span>
            </button>
            {% } %}
    </div>
    {% } %}
</script>
<script id="tmpl-download" type="text/x-tmpl">
    <?php $function_download_tmpl($model,true,[
        'crop'=> $crop,
        'label_success' => $this->context->label_success,
        'label_crop'    => $this->context->label_crop,
        'label_cancel'  => $this->context->label_cancel,
        'label_delete'  => $this->context->label_delete,
    ]); ?>
</script>
