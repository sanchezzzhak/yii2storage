<?php
    use yii\helpers\Html;
    use yii\helpers\ArrayHelper;
    /** @var $this \yii\web\View */
    /** @var $context \kak\storage\Upload */
    /** @var $model \kak\storage\models\UploadForm */
    $context = $this->context;
    $arrMeta = \yii\helpers\Json::decode($model->meta);
    $mataName = $model->meta_name
?>

<div class="yii2upload kak-storage-upload" id="<?=$context->id?>"
     data-url="<?=$context->url?>"
     data-multiple="<?=(int)$context->multiple?>"
     data-crop="<?=(int)$context->crop?>"
     data-singleupload="<?=(int)$context->singleUpload?>"
     data-tmpl-upload="<?=$context->id?>-tmpl-upload"
     data-tmpl-download="<?=$context->id?>-tmpl-download"
     data-autoupload="<?=(int)$context->autoUpload?>">
    <div>
        <span class="btn fileinput-button">
            <i class="glyphicon glyphicon-plus"></i>
            <span><?=$context->labelBtn?></span>
            <?=Html::activeFileInput($model, 'file', $options) . "\n"; ?>
        </span>
    </div>
    <?php if($context->progressbarAll):?>
        <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100"><div class="bar" style="width: 0%"><span></span></div></div>
    <?php endif;?>
    <div role="presentation" class="files">
        <?php
        if(is_array($arrMeta))
            foreach($arrMeta as $meta):?>
                <!-- start item -->
                <div class="template-download" data-url="<?=$meta['url']?>" data-storage="<?=$meta['storage']?>">
                    <p>File :<?=$meta['name_display']?> <span class="size"><?=round($meta['size']/1024,2)?> Kb</span> </p>

                    <div class="act">
                        <a class="cancel btn inline" href="javascript:;"><?=$context->labelDelete?></a>
                    </div>
                    <?= Html::hiddenInput($mataName.'[]', \yii\helpers\Json::encode($meta), ['class'=>'meta'])?>
                    <?php if(count($meta['images'])):?>
                        <span>
                            <a class="preview" href="javascript:;"><img src="<?=ArrayHelper::getValue($meta['images'],'thumbnail.url')?>"></a>
                            <div class="preview-box hide">
                                <div class="act">
                                    <?php if($context->crop): ?>
                                        <a href="javascript:;" class="crop btn inline"> <?=$context->labelCrop;?></a>
                                    <?php endif;?>
                                    <a href="javascript:;" class="crop-cancel btn inline"> <?=$context->labelCancel?></a>
                                </div>
                                <img src="<?=ArrayHelper::getValue($meta['images'],'preview.url')?>">
                            </div>
                        </span>
                </div>
                <?php endif;?>
                <!-- end item -->
            <?php endforeach;?>
    </div>

</div>

<script id="<?= $context->id ?>-tmpl-upload" type="text/x-tmpl">
    {% for (var i=0, file; file=o.files[i]; i++) { %}
    <div class="template-upload">
        <span class="preview"></span>
            <p class="name">{%=file.name_display%}</p>
            <strong class="error text-danger"></strong>
            <p class="size"><?=$context->labelProcessingUpload?></p>
            <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                <div class="progress-bar bar" style="width:0%;"></div>
            </div>
            {% if (!i && !o.autoUpload) { %}
            <button class="btn inline btn-primary start" disabled>
                <i class="glyphicon glyphicon-upload"></i>
                <span><?=$context->labelStart?></span>
            </button>
            {% } %}
            {% if (!i) { %}
            <button class="btn inline btn-warning cancel">
                <i class="glyphicon glyphicon-ban-circle"></i>
                <span><?=$context->labelCancel?></span>
            </button>
            {% } %}
    </div>
    {% } %}
</script>
<script id="<?=$context->id?>-tmpl-download" type="text/x-tmpl">
{% for (var i=0, file; file = o.files[i]; i++) { %}
    <div class="template-download"
      data-url="{%=file.url%}"
      data-storage="{%=file.storage%}">

    {% if( o.result.errors){ %}
        <div class="error-upload cancel "> <p> File :{%=file.name_display%} <?=$context->labelUploadError?> </p>
        {% for (var key in o.result.errors) { %}
        {%=o.result.errors[key][0]%}</div>
        {% } %}
        </div>
    {% } else { %}
        <p>File :{%=file.name_display%} <span class="size">{%=o.formatFileSize(file.size)%}</span>  <?=$context->labelSuccess?></p>
        <div class="act">
            <a class="cancel btn inline" href="javascript:;"><?=$context->labelDelete?></a>
        </div>
        <?= Html::hiddenInput($mataName.'[]', '', ['class'=>'meta'])?>
        {% if(o.result.images.thumbnail) { %}
            <span>
                <a class="preview" href="javascript:;"><img src="{%=o.result.images.thumbnail.url%}"></a>
                <div class="preview-box hide">
                    <div class="act">
                        <?php if($context->crop): ?>
                            <a href="javascript:;" class="crop btn inline"> <?=$context->labelCrop;?></a>
                        <?php endif;?>
                            <a href="javascript:;" class="crop-cancel btn inline"> <?=$context->labelCancel?></a>
                    </div>
                    <img src="{%=o.result.images.preview.url%}">
                </div>
            </span>

        {% } %}
    {% } %}

    </div>
{% } %}
</script>
