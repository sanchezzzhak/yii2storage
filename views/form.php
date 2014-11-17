<?php
    use yii\helpers\Html;
    /** @var $this \yii\web\View */
?>

<div class="yii2upload"
     data-url="<?=$url?>"
     data-multiple="<?=($multiple?1:0)?>"
     data-crop="<?=($crop? 1:0)?>"
     data-autoupload="<?=($auto_upload ? 1: 0)?>">
    <div>
        <span class="btn fileinput-button">
            <i class="glyphicon glyphicon-plus"></i>
            <span><?=$label_btn?></span>
            <?=Html::activeFileInput($model, 'file', $options) . "\n"; ?>
        </span>
    </div>
    <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100"><div class="bar" style="width: 0%"><span></span></div></div>
    <div role="presentation" class="files"></div>
</div>

<script id="tmpl-add" type="text/x-tmpl" >
    {% for (var i=0, file; file=o.files[i]; i++) { %}
    <div class="template-upload">
        <span class="preview"></span>
            <p class="name">{%=file.name%}</p>
            <strong class="error text-danger"></strong>
            <p class="size">Processing upload...</p>
            <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                <div class="progress-bar bar" style="width:0%;"></div>
            </div>
            {% if (!i && !o.autoUpload) { %}
            <button class="btn inline btn-primary start" disabled>
                <i class="glyphicon glyphicon-upload"></i>
                <span>Start</span>
            </button>
            {% } %}
            {% if (!i) { %}
            <button class="btn inline btn-warning cancel">
                <i class="glyphicon glyphicon-ban-circle"></i>
                <span>Cancel</span>
            </button>
            {% } %}
    </div>
    {% } %}
</script>
<script id="tmpl-download" type="text/x-tmpl">
{% for (var i=0, file; file = o.files[i]; i++) { %}
     <div class="template-upload">
         {% if( o.result.errors.length > 0){ %}
             <p>File :{%=file.name%} uploaded error</p>
             <strong class="error text-danger"></strong>
        {% } else { %}
            <p>File :{%=file.name%} <span class="size">{%=o.formatFileSize(file.size)%}</span>   uploaded success</p>
             <div class="act">
                <a class="cancel btn inline" href="javascript:;">Delete</a>
            </div>
            {% if(o.result.image_preview_url.length > 0 ) { %}
                <span>
                    <a class="preview" href="javascript:;"><img src="{%=o.result.thumbnail_url%}"></a>
                    <div class="preview-box hide">
                        <div class="act">
                             <a href="javascript:;" class="crop btn inline" data-url="{%=o.result.crop_url%}">Crop</a>
                             <a href="javascript:;" class="crop-cancel btn inline">Cancel</a>
                        </div>
                        <img src="{%=o.result.image_preview_url%}">
                    </div>
                </span>
            {% } %}
        {% } %}

     </div>
{% } %}
</script>