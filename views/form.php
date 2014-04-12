<?php
namespace yii2\storage\views;
use yii\helpers\Html;

?>


<span class="btn btn-success fileinput-button">
    <i class="glyphicon glyphicon-plus"></i>
    <span>Add files...</span>
	<?php echo Html::activeFileInput($model, $attribute,[]) . "\n"; ?>
</span>