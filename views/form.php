<?php
    use yii\helpers\Html;
    use \yii\helpers\ArrayHelper;
    /** @var $this \yii\web\View */
?>

<div class="yii2upload"
     data-url="<?=$this->context->url?>"
     data-multiple="<?=($this->context->multiple?1:0)?>"
     data-crop="<?=($this->context->crop ? 1:0)?>"
     data-singleupload="<?=($this->context->single_upload? 1:0)?>"
     data-autoupload="<?=($this->context->auto_upload ? 1: 0)?>">
    <div>
        <span class="btn fileinput-button">
            <i class="glyphicon glyphicon-plus"></i>
            <span><?=$this->context->label_btn?></span>
            <?=Html::activeFileInput($model, 'file', $options) . "\n"; ?>
        </span>
    </div>
    <?php if($this->context->progressbarall):?>
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

{% for (var i=0, file; file = o.files[i]; i++) { %}
    <div class="template-upload">

    {% if( o.result.errors){ %}
        <div class="error-upload cancel "> <p> File :{%=file.name%} <?=$this->context->label_upload_error?> </p>
        {% for (var key in o.result.errors) { %}
        {%=o.result.errors[key][0]%}</div>
        {% } %}
        </div>
    {% } else { %}

        <p>File :{%=file.name%} <span class="size">{%=o.formatFileSize(file.size)%}</span>  <?=$this->context->label_success?></p>
        <div class="act">
            <a class="cancel btn inline" href="javascript:;"><?=$this->context->label_delete?></a>
        </div>

        <?= Html::hiddenInput('meta[]', $model->meta, ['class'=>'meta'])?>

        {% if(o.result.images.thumbnail) { %}
            <span>
                <a class="preview" href="javascript:;"><img src="{%=o.result.images.thumbnail.url%}"></a>
                <div class="preview-box hide">
                    <div class="act">
                        <?php if($this->context->crop): ?>
                            <a href="javascript:;" class="crop btn inline" data-url="{%=o.result.crop_url%}"> <?=$this->context->label_crop;?></a>
                        <?php endif;?>
                            <a href="javascript:;" class="crop-cancel btn inline"> <?=$this->context->label_cancel?></a>
                    </div>
                    <img src="{%=o.result.images.preview.url%}">
                </div>
            </span>

        {% } %}
    {% } %}

    </div>
{% } %}

</script>
